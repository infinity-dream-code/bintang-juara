<?php

namespace App\Support;

use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputSiswaProcedure
{
    /**
     * Memanggil stored procedure InputSiswa di DATA_MYSQL.
     *
     * @see InputSiswa(p_NIMRAW, p_NMCUSTRAW, p_DESC02, p_CODE02, p_CODE01,
     *      p_DESC03, p_DESC04, p_DESC05, p_CODE04, p_GENUS, p_CODE05)
     */
    public static function call(
        string $nimRaw,
        string $namaRaw,
        mst_kelas $kelas,
        mst_sekolah $sekolah,
        string $angkatan,
        ?string $alamat = null,
        ?string $gender = null,
        ?string $ortu = null,
        ?string $code05 = null,
    ): void {
        DB::connection('DATA_MYSQL')->statement(
            'CALL InputSiswa(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $nimRaw,
                $namaRaw,
                (string) ($kelas->jenjang ?? ''),
                (string) ($kelas->unit ?? ''),
                (string) ($sekolah->CODE01 ?? ''),
                (string) ($kelas->kelas ?? ''),
                $angkatan,
                (string) ($alamat ?? ''),
                (string) ($gender ?? ''),
                (string) ($ortu ?? ''),
                (string) ($code05 ?? ''),
            ]
        );

        Log::info('input-siswa.procedure.ok', [
            'nis' => $nimRaw,
            'kelas_id' => $kelas->id,
            'sekolah' => $sekolah->CODE01,
            'angkatan' => $angkatan,
        ]);
    }
}
