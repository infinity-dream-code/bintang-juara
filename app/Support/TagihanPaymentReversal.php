<?php

namespace App\Support;

use App\Models\scctbill;
use App\Models\sccttran;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TagihanPaymentReversal
{
    private const TELLER_FIDBANKS = ['1140000', '1140001', '1140003', '1140004', '1140005', '1200001', '1200002'];

    private const SALDO_FIDBANK = '1140002';

    public function hasBillPayments(scctbill $tagihan): bool
    {
        if ((int) ($tagihan->BILLPAID ?? 0) > 0) {
            return true;
        }

        return sccttran::query()
            ->where('BILLID', $tagihan->AA)
            ->where('CUSTID', $tagihan->CUSTID)
            ->where(function ($query) {
                $query->where('DEBET', '>', 0)
                    ->orWhere('KREDIT', '>', 0);
            })
            ->exists();
    }

    public function reverseOnTagihanDelete(scctbill $tagihan, Request $request): void
    {
        $username = $this->resolveUsername();

        if ($this->shouldCancelAsSaldoPayment($tagihan)) {
            $this->reverseSaldoOrVaPayment($tagihan, $request, $username);

            return;
        }

        if ($this->shouldCancelAsTellerPayment($tagihan)) {
            $this->cancelCashPayment($tagihan, $username);

            return;
        }

        if ($this->hasBillPayments($tagihan)) {
            $this->cancelCashPayment($tagihan, $username);
        }
    }

    public function deleteUnpaidTagihan(scctbill $tagihan, Request $request): void
    {
        if ($this->hasBillPayments($tagihan)) {
            $this->reverseOnTagihanDelete($tagihan, $request);
            $tagihan->refresh();
        }

        $tagihan->update([
            'FSTSBolehBayar' => 0,
        ]);
    }

    private function shouldCancelAsSaldoPayment(scctbill $tagihan): bool
    {
        if ($this->normalizeFidBank($tagihan->FIDBANK ?? '') === self::SALDO_FIDBANK) {
            return true;
        }

        return sccttran::query()
            ->where('BILLID', $tagihan->AA)
            ->where('CUSTID', $tagihan->CUSTID)
            ->where(function ($query) {
                $query->whereRaw('UPPER(TRIM(METODE)) = ?', ['JURNAL SALDO'])
                    ->orWhereRaw("TRIM(COALESCE(CAST(FIDBANK AS CHAR), '')) = ?", [self::SALDO_FIDBANK]);
            })
            ->exists();
    }

    private function shouldCancelAsTellerPayment(scctbill $tagihan): bool
    {
        $fidBank = $this->normalizeFidBank($tagihan->FIDBANK ?? '');

        if ($fidBank === self::SALDO_FIDBANK) {
            return false;
        }

        if (in_array($fidBank, self::TELLER_FIDBANKS, true)) {
            return true;
        }

        return sccttran::query()
            ->where('BILLID', $tagihan->AA)
            ->where('CUSTID', $tagihan->CUSTID)
            ->whereRaw('UPPER(TRIM(METODE)) = ?', ['FROM TELLER'])
            ->exists();
    }

    private function resolvePaidAmount(scctbill $tagihan): int
    {
        $billPaid = (int) ($tagihan->BILLPAID ?? 0);
        if ($billPaid > 0) {
            return $billPaid;
        }

        $fromTeller = (int) sccttran::query()
            ->where('BILLID', $tagihan->AA)
            ->where('CUSTID', $tagihan->CUSTID)
            ->whereRaw('UPPER(TRIM(METODE)) = ?', ['FROM TELLER'])
            ->sum('DEBET');

        if ($fromTeller > 0) {
            return $fromTeller;
        }

        return max(0, (int) ($tagihan->BILLAM ?? 0));
    }

    private function cancelCashPayment(scctbill $tagihan, string $username): void
    {
        DB::connection('DATA_MYSQL')->transaction(function () use ($tagihan, $username) {
            $billAm = (int) ($tagihan->BILLAM ?? 0);
            $nominalBayar = $this->resolvePaidAmount($tagihan);

            $this->clearTellerPaymentKredit($tagihan);

            if ($nominalBayar > 0) {
                $this->insertReversalTransaction($tagihan, $nominalBayar, 'REVERSAL', $username);
            }

            $tagihan->update([
                'PAIDST' => 0,
                'PAIDDT' => null,
                'PAIDDT_ACTUAL' => null,
                'BILLPAID' => 0,
                'PAYMENTLEFT' => $billAm,
                'INSTALLMENT' => 0,
            ]);
        });
    }

    private function clearTellerPaymentKredit(scctbill $tagihan): void
    {
        sccttran::query()
            ->where('BILLID', $tagihan->AA)
            ->where('CUSTID', $tagihan->CUSTID)
            ->whereRaw('UPPER(TRIM(METODE)) = ?', ['FROM TELLER'])
            ->where('KREDIT', '>', 0)
            ->update(['KREDIT' => 0]);
    }

    private function reverseSaldoOrVaPayment(scctbill $tagihan, Request $request, string $username): void
    {
        $custId = (string) $tagihan->CUSTID;
        $aa = (string) $tagihan->AA;
        $billCd = (string) ($tagihan->BILLCD ?? '');
        $userId = $this->resolveCyberKeyUserId();
        $hostname = Str::limit((string) ($request->ip() ?? ''), 250, '');

        try {
            $this->callCancelPaymentSaldo($custId, $aa, $billCd, $userId, $hostname);
        } catch (\Throwable $e) {
            if ($this->isCancelPaymentSaldoMissing($e)) {
                $this->reverseSaldoPaymentManually($tagihan, $username);

                return;
            }

            if ($this->shouldCancelAsTellerPayment($tagihan)) {
                $this->cancelCashPayment($tagihan, $username);

                return;
            }

            throw $e;
        }
    }

    private function reverseSaldoPaymentManually(scctbill $tagihan, string $username): void
    {
        DB::connection('DATA_MYSQL')->transaction(function () use ($tagihan, $username) {
            $billAm = (int) ($tagihan->BILLAM ?? 0);
            $nominalBayar = $this->resolvePaidAmount($tagihan);

            if ($nominalBayar > 0) {
                $this->insertReversalTransaction($tagihan, $nominalBayar, 'JURNAL SALDO', $username);
            }

            $tagihan->update([
                'PAIDST' => 0,
                'PAIDDT' => null,
                'PAIDDT_ACTUAL' => null,
                'BILLPAID' => 0,
                'PAYMENTLEFT' => $billAm,
                'INSTALLMENT' => 0,
            ]);
        });
    }

    private function callCancelPaymentSaldo(string $custId, string $aa, string $billCd, string $userId, string $hostname): void
    {
        Log::info('tagihan-delete.cancel.call_procedure', [
            'procedure' => 'CancelPaymentSaldo',
            'custid' => $custId,
            'aa' => $aa,
            'billcd' => $billCd,
            'users' => $userId,
            'hostname' => $hostname,
        ]);

        $pdo = DB::connection('DATA_MYSQL')->getPdo();
        $stmt = $pdo->prepare('CALL CancelPaymentSaldo(?, ?, ?, ?, ?)');
        $stmt->execute([$custId, $aa, $billCd, $userId, $hostname]);

        do {
            $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } while ($stmt->nextRowset());
    }

    private function isCancelPaymentSaldoMissing(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return stripos($message, 'CancelPaymentSaldo') !== false
            && (
                stripos($message, 'does not exist') !== false
                || stripos($message, 'doesn\'t exist') !== false
                || stripos($message, 'unknown procedure') !== false
                || stripos($message, '1305') !== false
            );
    }

    private function insertReversalTransaction(scctbill $tagihan, int $nominalBayar, string $metode, string $username): void
    {
        $lastInstallment = (int) sccttran::query()
            ->where('BILLID', $tagihan->AA)
            ->where('CUSTID', $tagihan->CUSTID)
            ->max('INSTALLMENT');

        $payload = [
            'CUSTID' => $tagihan->CUSTID,
            'METODE' => $metode,
            'TRXDATE' => now(),
            'NOREFF' => 'REVERSAL',
            'FIDBANK' => (string) ($tagihan->FIDBANK ?? ''),
            'DEBET' => 0,
            'KREDIT' => $nominalBayar,
            'BILLID' => $tagihan->AA,
            'BILLTARGET' => $tagihan->BILLNM,
            'INSTALLMENT' => $lastInstallment,
            'TRANSNO' => $tagihan->TRANSNO ?? $username,
            'isreversal' => 1,
        ];

        try {
            sccttran::create($payload);
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'isreversal') === false
                && stripos($e->getMessage(), 'Unknown column') === false) {
                throw $e;
            }

            unset($payload['isreversal']);
            sccttran::create($payload);
        }
    }

    private function normalizeFidBank(?string $fidBank): string
    {
        return preg_replace('/\D/', '', (string) $fidBank);
    }

    private function resolveUsername(): string
    {
        return (string) (Auth::user()->urut ?? Auth::id() ?? 'system');
    }

    private function resolveCyberKeyUserId(): string
    {
        $user = Auth::user();

        if ($user === null) {
            return '';
        }

        return (string) ($user->urut ?? Auth::id() ?? '');
    }
}
