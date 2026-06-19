<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AndroidLogonFixerProcedure
{
    /**
     * Reset / buat akun login Android (sm_user) berdasarkan NIS.
     *
     * @see AndroidLogonFixer(p_NIM varchar(20))
     */
    public static function call(string $nim): void
    {
        $nim = trim($nim);
        if ($nim === '' || $nim === '-') {
            throw new \InvalidArgumentException('NIS tidak valid untuk reset login Android.');
        }

        DB::connection('DATA_MYSQL')->statement(
            'CALL AndroidLogonFixer(?)',
            [$nim]
        );

        Log::info('android-logon-fixer.procedure.ok', ['nis' => $nim]);
    }
}
