<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Imports\MasterData\ImportSettingDataWa;
use App\Models\scctcust;
use App\Models\ValidationMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Validators\ValidationException;

class SettingDataWaController extends Controller
{
    public string $title = "Master Data";
    public string $mainTitle = "Update WA Walsan";
    public string $dataTitle = "Update WA Walsan";
    public string $cacheKey = "import_setting_data_wa";

    public function index()
    {
        $data["title"] = $this->title;
        $data["mainTitle"] = $this->mainTitle;
        $data["dataTitle"] = $this->dataTitle;
        $data["columnsUrl"] = route("admin.master-data.setting-data-wa.get-column");
        $data["datasUrl"] = route("admin.master-data.setting-data-wa.get-data");

        return view("admin.master_data.setting_data_wa.index", $data);
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                "fileImport" => ["required", "mimes:xls,xlsx", "max:1024"],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes(),
        );

        $file = $request->fileImport;

        try {
            $headingsData = (new HeadingRowImport())->toArray($file);
            $requiredColumns = ["nis", "nama", "no_wa"];
            if (empty($headingsData) || !isset($headingsData[0][0])) {
                throw new Exception(
                    "Tidak dapat membaca judul kolom dari file. Pastikan file memiliki header yang sesuai.",
                );
            }

            $headings = array_map(
                fn ($h) => strtolower(trim(str_replace(" ", "_", (string) $h))),
                $headingsData[0][0],
            );

            $missingColumns = [];
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $headings, true)) {
                    $missingColumns[] = $column;
                }
            }

            if (!empty($missingColumns)) {
                $formattedMissingColumns = strtoupper(
                    str_replace("_", " ", implode(", ", $missingColumns)),
                );
                $formattedRequiredColumns = strtoupper(
                    str_replace("_", " ", implode(", ", $requiredColumns)),
                );
                throw new Exception(
                    "Kolom {$formattedMissingColumns} tidak ditemukan.<br><hr> pastikan kolom berikut ada dan terisi pada file import: {$formattedRequiredColumns}.",
                );
            }

            DB::beginTransaction();
            Excel::import(new ImportSettingDataWa(), $file);
            DB::commit();

            $data = Cache::get($this->cacheKey);

            return response()->json(
                [
                    "message" =>
                        "Sukses, data WA telah diimport, silahkan periksa kembali",
                    "data" => $data,
                ],
                200,
            );
        } catch (ValidationException $e) {
            $errorMessages = $e->errors();
            $errorMessage =
                $errorMessages["error"][0] ??
                "Terjadi kesalahan saat melakukan import data.";

            return response()->json(
                ["message" => $errorMessage, "error" => $errorMessages],
                422,
            );
        } catch (Exception $e) {
            $error = $e->getMessage();

            return response()->json(
                [
                    "message" => "Gagal!<br> tidak dapat melakukan {$this->mainTitle}.<hr> {$error}",
                    "error" => $error,
                ],
                422,
            );
        }
    }

    public function getColumn()
    {
        return [
            [
                "data" => null,
                "name" => "no",
                "className" => "text-center",
                "columnType" => "row",
            ],
            ["data" => "nis", "name" => "NIS", "searchable" => true, "orderable" => true],
            ["data" => "nama", "name" => "NAMA", "searchable" => true, "orderable" => true],
            ["data" => "no_wa", "name" => "No WA", "searchable" => true, "orderable" => true],
            [
                "data" => "status",
                "name" => "Status",
                "searchable" => true,
                "orderable" => true,
                "columnType" => "importstatus",
            ],
            [
                "data" => "keterangan",
                "name" => "Keterangan",
                "searchable" => true,
                "orderable" => true,
            ],
        ];
    }

    public function getData(Request $request)
    {
        $draw = $request->get("draw");
        $cachedData = Cache::get($this->cacheKey, []);
        $nisCount = count($cachedData);

        $records = collect($cachedData)->values();

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $nisCount,
            "recordsFiltered" => $nisCount,
            "data" => $records,
        ]);
    }

    public function validateData()
    {
        $data = Cache::get($this->cacheKey);
        if (is_null($data) || (is_array($data) && empty($data))) {
            return response()->json(
                [
                    "message" =>
                        "Tidak ada data yang dapat diproses, silahkan upload file terlebih dahulu",
                ],
                422,
            );
        }

        DB::beginTransaction();
        try {
            foreach ($data as $item) {
                if (($item["status"] ?? 0) != 1) {
                    continue;
                }

                $existingCust = scctcust::where("NOCUST", $item["nis"])->first();
                if (!$existingCust) {
                    continue;
                }

                $existingCust->NO_WA = $item["no_wa"];
                $existingCust->LastUpdate = date("Y-m-d H:i:s");
                $existingCust->save();
            }

            DB::commit();
            Cache::forget($this->cacheKey);

            return response()->json(
                ["message" => "Sukses, nomor WA siswa telah diperbarui"],
                200,
            );
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "message" => "Gagal menyimpan data WA",
                    "error" => $e->getMessage(),
                ],
                422,
            );
        }
    }

    public function clearData()
    {
        Cache::forget($this->cacheKey);

        return response()->json(["message" => "Data dibersihkan"], 200);
    }
}
