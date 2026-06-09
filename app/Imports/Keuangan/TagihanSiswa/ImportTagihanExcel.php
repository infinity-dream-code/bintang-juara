<?php

namespace App\Imports\Keuangan\TagihanSiswa;

use App\Models\scctcust;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportTagihanExcel implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */

    public function collection(Collection $collection): void
    {
        $cacheKey = 'import_tagihan_excel';
        $processedData = [];

        foreach ($collection as $row) {
            if ($row->filter()->isEmpty()) continue;
            $rowData = $row->toArray();
            $rowData['status'] = 1;
            $status_ket = null;

            if (!isset($rowData['nis'])) {
                $rowData['status'] = 0;
                $status_ket = 'NIS tidak boleh kosong';
            }else {
                $rowData['nis'] = (string) $rowData['nis'];
                $checkData = scctcust::where('NOCUST', $rowData['nis'])->first();
                if (!$checkData) {
                    $rowData['status'] = 0;
                    if (!empty($status_ket)) $status_ket .= ', ';
                    $status_ket .= "NIS {$rowData['nis']} tidak ditemukan";
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
