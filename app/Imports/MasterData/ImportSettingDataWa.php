<?php

namespace App\Imports\MasterData;

use App\Models\scctcust;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportSettingDataWa implements WithMultipleSheets, ToCollection, WithHeadingRow
{
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function collection(Collection $collection): void
    {
        $cacheKey = "import_setting_data_wa";
        $processedData = [];

        foreach ($collection as $row) {
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $rowData = $row->toArray();
            $nis = trim((string) ($rowData["nis"] ?? ""));
            $nama = trim((string) ($rowData["nama"] ?? ""));
            $noWa = trim((string) ($rowData["no_wa"] ?? $rowData["no wa"] ?? ""));

            $rowData = [
                "nis" => $nis,
                "nama" => $nama,
                "no_wa" => $noWa,
                "status" => 1,
                "keterangan" => null,
            ];

            if ($nis === "") {
                $rowData["status"] = 0;
                $rowData["keterangan"] = "NIS tidak boleh kosong";
            } elseif ($noWa === "") {
                $rowData["status"] = 0;
                $rowData["keterangan"] = "No WA tidak boleh kosong";
            } else {
                $checkData = scctcust::where("NOCUST", $nis)->first();
                if (!$checkData) {
                    $rowData["status"] = 0;
                    $rowData["keterangan"] = "NIS {$nis} tidak ditemukan";
                } elseif ($nama === "") {
                    $rowData["nama"] = $checkData->NMCUST;
                }
            }

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
