<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class MetodeBayarHelper
{
    public const ANDROID_FIDBANK = '6';
    public const SALDO_FIDBANK = '1140002';

    public static function isMobileNoreff(?string $noreff): bool
    {
        return strcasecmp(trim((string) $noreff), 'Mobile') === 0;
    }

    public static function isMnlNoreff(?string $noreff): bool
    {
        return strcasecmp(trim((string) $noreff), 'MNL') === 0;
    }

    /**
     * Aturan tampilan metode:
     * - NOREFF Mobile (FIDBANK apa saja) → ANDROID
     * - selain itu pakai FIDBANK aslinya
     *   contoh: FIDBANK 1140002 + NOREFF MNL → Manual SALDO
     *   contoh: FIDBANK 1140000 + NOREFF MNL → Manual Cash
     *   contoh: FIDBANK 1140001 + NOREFF MNL → Manual BMI
     */
    public static function resolveDisplayFidBank(?string $fidBank, ?string ...$noreffs): string
    {
        foreach ($noreffs as $noreff) {
            if (self::isMobileNoreff($noreff)) {
                return self::ANDROID_FIDBANK;
            }
        }

        // NOREFF MNL (atau selain Mobile): ikut FIDBANK asli (1140002 = Manual SALDO, dll.)
        return (string) ($fidBank ?? '');
    }

    /**
     * Filter Bank = ANDROID: FIDBANK 6 ATAU NOREFF Mobile (tran/bill).
     *
     * @param  Builder|QueryBuilder  $query
     * @return Builder|QueryBuilder
     */
    public static function applyAndroidBankFilter($query, string $tranAlias = 'sccttran', string $billAlias = 'scctbill')
    {
        return $query->where(function ($q) use ($tranAlias, $billAlias) {
            $q->where("{$tranAlias}.FIDBANK", self::ANDROID_FIDBANK)
                ->orWhereRaw('UPPER(TRIM(COALESCE(' . $tranAlias . '.NOREFF, ""))) = ?', ['MOBILE'])
                ->orWhereRaw('UPPER(TRIM(COALESCE(' . $billAlias . '.NOREFF, ""))) = ?', ['MOBILE']);
        });
    }

    /**
     * Filter Bank selain ANDROID: jangan ikutkan baris NOREFF Mobile.
     *
     * @param  Builder|QueryBuilder  $query
     * @return Builder|QueryBuilder
     */
    public static function excludeMobileNoreff($query, string $tranAlias = 'sccttran', string $billAlias = 'scctbill')
    {
        return $query
            ->whereRaw('UPPER(TRIM(COALESCE(' . $tranAlias . '.NOREFF, ""))) <> ?', ['MOBILE'])
            ->whereRaw('UPPER(TRIM(COALESCE(' . $billAlias . '.NOREFF, ""))) <> ?', ['MOBILE']);
    }

    /**
     * Filter berdasarkan pilihan bank dropdown (sama aturan Data Tagihan Lunas).
     *
     * @param  Builder|QueryBuilder  $query
     * @return Builder|QueryBuilder
     */
    public static function applyBankFilter($query, ?string $bank, string $fidColumn, string $tranAlias = 'sccttran', string $billAlias = 'scctbill')
    {
        $bank = trim((string) $bank);
        if ($bank === '' || strtolower($bank) === 'all') {
            return $query;
        }

        if ($bank === self::ANDROID_FIDBANK) {
            return self::applyAndroidBankFilter($query, $tranAlias, $billAlias);
        }

        $query->where($fidColumn, $bank);

        return self::excludeMobileNoreff($query, $tranAlias, $billAlias);
    }
}
