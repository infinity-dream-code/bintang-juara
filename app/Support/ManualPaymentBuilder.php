<?php

namespace App\Support;

use App\Models\scctbill;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ManualPaymentBuilder
{
    private const SALDO_FIDBANK = '1140002';

    public function payBill(
        scctbill $tagihan,
        int $nominal,
        string $fidBank,
        string $paidAt,
        ?string $transno,
        Request $request
    ): void {
        $custId = (string) $tagihan->CUSTID;
        $aa = (string) $tagihan->AA;
        $billCd = (string) ($tagihan->BILLCD ?? '');
        $userId = $this->resolveCyberKeyUserId();
        $hostname = Str::limit((string) ($request->ip() ?? ''), 250, '');

        if ($fidBank === self::SALDO_FIDBANK) {
            $this->callBuilderPaymentBill($custId, $aa, $billCd, $nominal, $paidAt, $userId, $hostname);
            return;
        }

        $this->callBuilderPaymentCash(
            $custId,
            $aa,
            $billCd,
            $nominal,
            $fidBank,
            (string) ($transno ?? ''),
            $paidAt,
            $userId,
            $hostname
        );
    }

    private function callBuilderPaymentCash(
        string $custId,
        string $aa,
        string $billCd,
        int $nominal,
        string $fidBank,
        string $transno,
        string $paidAt,
        string $userId,
        string $hostname
    ): void {
        Log::info('manual-payment.builder.call', [
            'function' => 'BuilderPaymentCash',
            'custid' => $custId,
            'aa' => $aa,
            'billcd' => $billCd,
            'nominal' => $nominal,
            'fidbank' => $fidBank,
            'transno' => $transno,
            'paiddt' => $paidAt,
            'users' => $userId,
            'hostname' => $hostname,
        ]);

        $this->invokeStoredFunction('BuilderPaymentCash', [
            $custId,
            $aa,
            $billCd,
            $nominal,
            $fidBank,
            $transno,
            $paidAt,
            $userId,
            $hostname,
        ]);
    }

    private function callBuilderPaymentBill(
        string $custId,
        string $aa,
        string $billCd,
        int $nominal,
        string $paidAt,
        string $userId,
        string $hostname
    ): void {
        Log::info('manual-payment.builder.call', [
            'function' => 'BuilderPaymentBill',
            'custid' => $custId,
            'aa' => $aa,
            'billcd' => $billCd,
            'nominal' => $nominal,
            'paiddt' => $paidAt,
            'users' => $userId,
            'hostname' => $hostname,
        ]);

        $this->invokeStoredFunction('BuilderPaymentBill', [
            $custId,
            $aa,
            $billCd,
            $nominal,
            $paidAt,
            $userId,
            $hostname,
        ]);
    }

    /** MySQL FUNCTION (fx) — pakai SELECT, bukan CALL (procedure). */
    private function invokeStoredFunction(string $functionName, array $params): void
    {
        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        DB::connection('DATA_MYSQL')->select(
            "SELECT {$functionName}({$placeholders})",
            $params
        );
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
