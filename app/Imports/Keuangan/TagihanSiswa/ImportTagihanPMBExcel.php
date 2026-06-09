<?php

namespace App\Imports\Keuangan\TagihanSiswa;

use App\Models\scctcust;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportTagihanPMBExcel implements ToCollection, WithHeadingRow
{
    public function collection(Collection $collection): void
    {
        $cacheKey = 'import_tagihan_pmb_excel';
        $processedData = [];

        foreach ($collection as $row) {
            if ($row->filter()->isEmpty()) continue;
            $rowData = $row->toArray();
            $rowData['status'] = 1;
            $status_ket = null;

            if (!isset($rowData['nodaftar'])) {
                $rowData['status'] = 0;
                $status_ket = 'NIS tidak boleh kosong';
            }else{
                $rowData['nodaftar'] = (string) $rowData['nodaftar'];
                $checkData = scctcust::where('NUM2ND', $rowData['nodaftar'])->first();
                if (!$checkData) {
                    $rowData['status'] = 0;
                    if (!empty($status_ket)) $status_ket .= ', ';
                    $status_ket .= "Nomor Pendaftaran : {$rowData['nodaftar']} tidak ditemukan";
                }
            }

            if (!isset($rowData['nominal'])) {
                $rowData['status'] = 0;
                if (!empty($status_ket)) $status_ket .= ', ';
                $status_ket .= 'Nominal tidak boleh kosong';
            }

            $rowData['keterangan'] = $status_ket;
            $processedData[] = $rowData;
        }

        if (!empty($processedData)) {
            Cache::put($cacheKey, $processedData, now()->addMinutes(60));
        }
    }

    public function headingRow(): int
    {
        return 1;
    }
}
