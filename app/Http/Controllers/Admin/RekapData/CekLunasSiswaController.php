<?php

namespace App\Http\Controllers\Admin\RekapData;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CekLunasSiswaController extends Controller
{
    private string $title = "Rekap Data";
    private string $mainTitle = "Cek Lunas Siswa";
    private ?string $unitScope = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $this->unitScope = Auth::user()->unit;
            }
            return $next($request);
        });
    }

    public function index()
    {
        $data["title"] = $this->title;
        $data["mainTitle"] = $this->mainTitle;
        $data["columnsUrl"] = route("admin.rekap-data.cek-lunas-siswa.get-column");
        $data["datasUrl"] = route("admin.rekap-data.cek-lunas-siswa.get-data");
        $data["thn_aka"] = mst_thn_aka::select(["thn_aka"])
            ->whereNotNull("thn_aka")
            ->distinct()
            ->orderBy("thn_aka", "desc")
            ->get();
        $data["kelas"] = mst_kelas::when($this->unitScope, fn ($q) => $q->where("unit", $this->unitScope))
            ->orderByRaw("CASE WHEN kelas REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, kelas")
            ->get();
        $data["tagihan"] = mst_tagihan::orderBy("urut", "asc")->get();

        return view("admin.rekap_data.cek_lunas_siswa.index", $data);
    }

    public function getColumn()
    {
        return [
            ["data" => null, "name" => "no", "className" => "text-center", "columnType" => "row", "exportable" => true],
            ["data" => "BTA", "name" => "Thn Akademik", "searchable" => true, "orderable" => true, "exportable" => true],
            ["data" => "nmcust", "name" => "Nama Siswa", "searchable" => true, "orderable" => true, "exportable" => true],
            ["data" => "BILLNM", "name" => "Nama Tagihan", "searchable" => true, "orderable" => true, "exportable" => true],
            [
                "data" => "status",
                "name" => "Status",
                "searchable" => false,
                "orderable" => false,
                "exportable" => true,
                "columnType" => "boolean",
                "trueVal" => "LUNAS",
                "falseVal" => "BELUM LUNAS",
            ],
            ["data" => "kelas_display", "name" => "Kelas", "searchable" => true, "orderable" => false, "exportable" => true],
            ["data" => "CODE02", "name" => "Unit", "searchable" => true, "orderable" => true, "exportable" => true],
        ];
    }

    public function getData(Request $request)
    {
        $draw = (int) $request->get("draw");
        $start = (int) $request->get("start", 0);
        $length = $request->get("length", 10);
        $search_arr = $request->get("search", []);
        $searchValue = $search_arr["value"] ?? "";

        $filterInput = (array) $request->input("filter", []);

        $filterClosure = function ($query) use ($filterInput) {
            foreach ($filterInput as $key => $val) {
                if ($val === null || $val === "" || (is_string($val) && strtolower($val) === "all")) {
                    continue;
                }
                switch ($key) {
                    case "tahun_pelajaran":
                        $normalized = str_replace([" ", "-"], ["", "/"], trim((string) $val));
                        $query->whereRaw(
                            'REPLACE(REPLACE(TRIM(scctbill.BTA), " ", ""), "-", "/") = ?',
                            [$normalized]
                        );
                        break;
                    case "thn_aka":
                        $normalized = str_replace([" ", "-"], ["", "/"], trim((string) $val));
                        $query->whereRaw(
                            'REPLACE(REPLACE(TRIM(scctcust.DESC04), " ", ""), "-", "/") = ?',
                            [$normalized]
                        );
                        break;
                    case "nama_tagihan":
                        $query->where("scctbill.BILLNM", $val);
                        break;
                    case "kelas":
                        $raw = trim((string) $val);
                        if ($raw !== "" && ctype_digit($raw)) {
                            $query->whereRaw("TRIM(CAST(scctcust.CODE03 AS CHAR)) = ?", [$raw]);
                        }
                        break;
                    case "nis":
                        $query->where("scctcust.nocust", "like", "%{$val}%");
                        break;
                    case "nama":
                        $query->where("scctcust.nmcust", "like", "%{$val}%");
                        break;
                }
            }
        };

        $unitScope = $this->unitScope;

        $baseQuery = scctbill::leftJoin("scctcust", "scctcust.CUSTID", "scctbill.CUSTID")
            ->where("scctbill.FSTSBolehBayar", 1)
            ->when($unitScope, fn ($q) => $q->where("scctcust.CODE02", $unitScope))
            ->when(!blank($searchValue), function ($q) use ($searchValue) {
                $sanitized = str_replace(["\\", "%", "_"], ["\\\\", "\\%", "\\_"], $searchValue);
                $q->where(function ($q2) use ($sanitized) {
                    $q2->orWhere("scctcust.nmcust", "like", "%{$sanitized}%")
                        ->orWhere("scctcust.nocust", "like", "%{$sanitized}%");
                });
            })
            ->where($filterClosure);

        $totalRecords = (clone $baseQuery)->count("scctbill.AA");
        $totalRecordsWithFilter = $totalRecords;

        $rowsQuery = (clone $baseQuery)
            ->select([
                "scctbill.AA",
                "scctbill.CUSTID",
                "scctbill.BILLNM",
                "scctbill.BTA",
                "scctbill.PAIDST",
                "scctbill.BILLCD",
                "scctcust.nocust",
                "scctcust.nmcust",
                "scctcust.CODE02",
                "scctcust.DESC02",
                "scctcust.DESC03",
            ])
            ->orderBy("scctcust.nocust", "asc")
            ->orderBy("scctbill.BTA", "desc")
            ->orderBy("scctbill.BILLNM", "asc")
            ->orderBy("scctbill.AA", "asc");

        if ($length !== "poll") {
            $rowsQuery->skip($start)->take((int) $length);
        }

        $records = $rowsQuery->get()->map(function ($item) {
            return [
                "AA" => $item->AA,
                "CUSTID" => $item->CUSTID,
                "BTA" => $item->BTA,
                "nocust" => $item->nocust,
                "nmcust" => $item->nmcust,
                "BILLNM" => $item->BILLNM,
                "BILLCD" => $item->BILLCD,
                "status" => (int) $item->PAIDST === 1,
                "CODE02" => $item->CODE02,
                "kelas_display" => trim(($item->DESC02 ?? "") . " " . ($item->DESC03 ?? "")),
            ];
        });

        return response()->json([
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordsWithFilter,
            "data" => $records,
        ]);
    }

    public function cetakKartuSiswa(Request $request)
    {
        $custid = $request->get("custid");
        if (!$custid) {
            return response()->json(["error" => "Siswa tidak ditemukan"], 422);
        }

        $siswa = scctcust::where("custid", $custid)->first();
        if (!$siswa) {
            return response()->json(["error" => "Siswa tidak ditemukan"], 422);
        }

        $request->merge(["filter" => array_merge((array) $request->input("filter", []), ["nis" => $siswa->nocust])]);
        $request->merge(["length" => "poll", "draw" => 1, "start" => 0]);

        $response = $this->getData($request);
        $data = json_decode(json_encode($response), true);
        $tagihans = $data["original"]["data"] ?? [];

        try {
            $pdf = Pdf::loadView("pdf.data_tagihan.kartu-siswa-cek-pelunasan", [
                "tagihans" => $tagihans,
                "siswa" => $siswa,
            ]);
            return $pdf->download("kartu-siswa-{$siswa->nocust}.pdf");
        } catch (\Throwable $e) {
            return response()->json(["message" => "Tagihan tidak ditemukan", "error" => $e->getMessage()], 422);
        }
    }

    public function cetakPelaporan(Request $request)
    {
        $request->merge(["length" => "poll", "draw" => 1, "start" => 0]);
        $response = $this->getData($request);
        $data = json_decode(json_encode($response), true);
        $rows = $data["original"]["data"] ?? [];

        try {
            $pdf = Pdf::loadView("pdf.cek_lunas_siswa.laporan", [
                "rows" => $rows,
                "filter" => (array) $request->input("filter", []),
            ])->setPaper("a4", "landscape");
            return $pdf->download("cek-lunas-siswa.pdf");
        } catch (\Throwable $e) {
            return response()->json(["message" => "Gagal mencetak", "error" => $e->getMessage()], 422);
        }
    }
}
