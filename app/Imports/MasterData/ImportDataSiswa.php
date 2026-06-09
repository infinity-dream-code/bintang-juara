<?php

namespace App\Imports\MasterData;

use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_thn_aka;
use App\Models\scctcust;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportDataSiswa implements WithMultipleSheets, ToCollection, WithHeadingRow
{
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function collection(Collection $collection): void
    {
        $cacheKey = 'import_data_siswa';

        $requiredKeys = ['nama', 'unit', 'kelas', 'kelompok', 'angkatan'];

        $processedData = [];

        foreach ($collection as $row) {
            if ($row->filter()->isEmpty()) continue;

            $rowData = $row->toArray();

            if (count(array_intersect_key(array_flip($requiredKeys), $rowData)) !== count($requiredKeys)) {
                continue;
            }

            $rowData['unit'] = trim((string) ($rowData['unit'] ?? ''));
            $rowData['kelas'] = is_numeric($rowData['kelas'] ?? null)
                ? (string) (int) $rowData['kelas']
                : trim((string) ($rowData['kelas'] ?? ''));
            $rowData['kelompok'] = trim((string) ($rowData['kelompok'] ?? ''));

            $rowData['status'] = 1;
            $status_ket = null;

            $nis = isset($row['nis']) ? trim((string)$row['nis']) : null;
            $nodaftar = isset($row['nodaftar']) ? trim((string)$row['nodaftar']) : null;
            $rowData['nis'] = ($nis !== null && $nis !== '') ? $nis : null;
            $rowData['nodaftar'] = ($nodaftar !== null && $nodaftar !== '') ? $nodaftar : null;

            if (!$rowData['nis'] && !$rowData['nodaftar']) {
                $rowData['status'] = 0;
                $status_ket = 'NIS &/ NODAFTAR tidak boleh kosong';
            }

            if ($rowData['nis'] && !is_numeric($rowData['nis'])) {
                $rowData['status'] = 0;
                if (!empty($status_ket)) $status_ket .= ', ';
                $status_ket .= "NIS harus berupa angka";
            } elseif ($rowData['nis']) {
                $rowData['nis'] = (string) $rowData['nis'];
                $checkData = scctcust::where('NOCUST', $rowData['nis'])->first();
                if ($checkData) {
                    $rowData['status'] = 2;
                    if (!empty($status_ket)) $status_ket .= ', ';
                    $status_ket .= "Siswa dengan NIS {$rowData['nis']} sudah ada, data akan diupdate";

                }
            }

            if ($rowData['nodaftar'] && !is_numeric($rowData['nodaftar'])) {
                $rowData['status'] = 0;
                if (!empty($status_ket)) $status_ket .= ', ';
                $status_ket .= "NODAFTAR harus berupa angka";
            } elseif ($rowData['nodaftar']) {
                $rowData['nodaftar'] = (string) $rowData['nodaftar'];
                $checkData = scctcust::where('NUM2ND', $rowData['nodaftar'])->first();
                if ($checkData) {
                    $rowData['status'] = 2;
                    if (!empty($status_ket)) $status_ket .= ', ';
                    $status_ket .= "Siswa dengan nodaftar {$rowData['nodaftar']} sudah ada, data akan diupdate";

                }
            }

            $rowData['ortu'] = trim((string) ($rowData['ortu'] ?? $rowData['genus'] ?? $rowData['ayah'] ?? '')) ?: null;

            $matchedKelas = mst_kelas::findForImport(
                $rowData['unit'],
                $rowData['kelas'],
                $rowData['kelompok'],
            );

            if (!$matchedKelas) {
                $rowData['status'] = 0;
                if (!empty($status_ket)) {
                    $status_ket .= ', ';
                }
                $status_ket .= sprintf(
                    'Kelas tidak ditemukan (Unit: %s, Kelas: %s, Kelompok: %s). Buat dulu di Master Kelas.',
                    $rowData['unit'],
                    $rowData['kelas'],
                    $rowData['kelompok'],
                );
            }

            $matchedThnAka = mst_thn_aka::where('thn_aka', $rowData['angkatan'])->first();
            if (!$matchedThnAka) {
                $rowData['status'] = 0;
                if (!empty($status_ket)) {
                    $status_ket .= ', ';
                }
                $status_ket .= sprintf(
                    'Angkatan tidak ditemukan (%s). Buat dulu di Tahun Akademik.',
                    $rowData['angkatan'],
                );
            }

            $matchedSekolah = null;
            if ($matchedKelas) {
                $unitText = trim((string) ($rowData['unit'] ?? ''));
                $matchedSekolah = mst_sekolah::query()
                    ->where(function ($query) use ($unitText, $matchedKelas) {
                        if ($unitText !== '') {
                            $query->where('DESC01', 'like', '%' . $unitText . '%')
                                ->orWhere('CODE01', $unitText)
                                ->orWhereRaw('UPPER(TRIM(DESC01)) = ?', [strtoupper($unitText)]);
                        }

                        $kelasUnit = trim((string) ($matchedKelas->unit ?? ''));
                        if ($kelasUnit !== '') {
                            $query->orWhere('DESC01', 'like', '%' . $kelasUnit . '%')
                                ->orWhere('CODE01', $kelasUnit)
                                ->orWhereRaw('UPPER(TRIM(DESC01)) = ?', [strtoupper($kelasUnit)]);
                        }
                    })
                    ->first();
            }

            if ($matchedKelas && !$matchedSekolah) {
                $rowData['status'] = 0;
                if (!empty($status_ket)) {
                    $status_ket .= ', ';
                }
                $status_ket .= sprintf(
                    'Unit/sekolah tidak ditemukan (Unit: %s). Periksa Master Kelas atau data sekolah.',
                    $rowData['unit'],
                );
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
