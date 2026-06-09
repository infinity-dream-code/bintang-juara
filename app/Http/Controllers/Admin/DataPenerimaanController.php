<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetodeBayar;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DataPenerimaanController extends Controller
{
    public string $title;
    public $datasUrl;
    public $columnsUrl;

    public function __construct()
    {
        $this->title = "Data Penerimaan";
        $this->datasUrl = route("admin.data-penerimaan.get-data");
        $this->columnsUrl = route("admin.data-penerimaan.get-column");
    }

    public function getColumn(): array
    {
        return [
            [
                "data" => "AA",
                "name" => "no",
                "columnType" => "row",
                "exportable" => true,
            ],
            //            ['data' => 'item_id', 'name' => 'ITEM ID', 'visible' => false],
            [
                "data" => "nocust",
                "name" => "NIS",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => false,
            ],
            [
                "data" => "nmcust",
                "name" => "NAMA",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => false,
            ],
            [
                "data" => "CODE02",
                "name" => "Unit",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "DESC02",
                "name" => "Kelas",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "BILLNM",
                "name" => "Nama Tagihan",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "BILLAM",
                "name" => "Tagihan",
                "searchable" => true,
                "orderable" => true,
                "columnType" => "currency",
                "className" => "text-end",
                "exportable" => true,
            ],
            [
                "data" => "FIDBANK",
                "name" => "Metode",
                "columnType" => "custom_code_tagihan",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "PAIDDT",
                "name" => "Tanggal Bayar",
                "columnType" => "timestamp",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "BTA",
                "name" => "Tahun AKA",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
        ];
    }

    public function index()
    {
        $data = [];
        $data["title"] = $this->title;
        $data["columnsUrl"] = $this->columnsUrl;
        $data["datasUrl"] = $this->datasUrl;
        $data["post"] = mst_tagihan::select(["tagihan"])
            ->orderByRaw(
                "
                        CASE
                            WHEN kode BETWEEN '07' AND '12' THEN 0
                            WHEN kode BETWEEN '01' AND '06' THEN 1
                            ELSE 2
                        END,
                        kode ASC
                    ",
            )
            ->get();
        $data["thn_aka"] = mst_thn_aka::select(["thn_aka"])
            ->where("thn_aka", "!=", null)
            ->orderBy("thn_aka", "desc")
            ->get();
        $data["kelas"] = mst_kelas::get();
        $data["unit"] = mst_sekolah::get();
        $data["metode_bayar"] = MetodeBayar::attributes();

        return view("admin.data_penerimaan", $data);
    }

    public function cetakKartuSiswa(Request $request)
    {
        if (!$request["custid"]) {
            return response()->json(["error" => "siswa tidak ditemukan"]);
        }
        $request["draw"] = 2;
        $request["start"] = 0;
        $request["length"] = "poll";
        try {
            $val = Crypt::decrypt($request["custid"]);
        } catch (DecryptException $e) {
            return response()->json(["error" => "siswa tidak ditemukan"]);
        }

        $siswa = scctcust::where("custid", $val)->first();
        if (!$siswa) {
            return response()->json(["error" => "siswa tidak ditemukan"]);
        }

        $request->merge([
            "filter" => array_merge($request->input("filter", []), [
                "custid" => $val,
            ]),
        ]);

        $filter = $request;
        $tagihans = $this->getData($filter);

        try {
            $tagihans = json_decode(json_encode($tagihans), true);
            $tagihans = $tagihans["original"]["data"];
            if (!$tagihans) {
                return response()->json(
                    ["message" => "Tagihan Tidak Ditemukan"],
                    422,
                );
            }

            $nova = match (strtolower($siswa->CODE02)) {
                "mts" => scctcust::showVAMTS($siswa->NOCUST),
                "ma" => scctcust::showVAMA($siswa->NOCUST),
                default => "",
            };

            $pdf = Pdf::loadView("cetak.kartu-siswa", [
                "tagihans" => $tagihans,
                "siswa" => $siswa,
                "nova" => $nova,
            ]);
            return $pdf->download("kartu-siswa.pdf");
        } catch (\Throwable $e) {
            return response()->json(
                ["message" => "Tagihan Tidak Ditemukan", "error" => $e],
                422,
            );
        }
    }

    public function getData(Request $request)
    {
        $draw = $request->get("draw");
        $start = $request->get("start");
        $rowperpage = $request->get("length");

        $columnIndex_arr = $request->get("order", []);
        $columnName_arr = $request->get("columns", []);
        $order_arr = $request->get("order", []);
        $search_arr = $request->get("search", []);
        $searchValue = $search_arr["value"] ?? "";

        $columnName = "BILLAC";
        $columnSortOrder = "DESC";

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]["column"] ?? null;
            if (
                $columnIndex !== null &&
                !empty($columnName_arr[$columnIndex]["data"]) &&
                $columnName_arr[$columnIndex]["data"] !== "no"
            ) {
                $columnName = $columnName_arr[$columnIndex]["data"];
                $columnSortOrder = $order_arr[0]["dir"] ?? "desc";
            }
        }

        $filters = [];
        $filterQuery = null;

        $filter = $request->input("filter");
        if ($filter) {
            foreach ($filter as $key => $val) {
                if (
                    is_array($val) ||
                    (strtolower($val) != "all" &&
                        $val !== null &&
                        $val !== "")
                ) {
                    $colName = match ($key) {
                        "dari_tanggal",
                        "sampai_tanggal"
                        => "scctbill.FTGLTagihan",
                        "tanggal-transaksi" => "scctbill.PAIDDT",
                        "tahun_akademik" => "scctbill.BTA",
                        "post" => "scctbill.BILLNM",
                        "unit" => "scctcust.CODE01",
                        "kelas" => "scctcust.DESC02",
                        "angkatan" => "scctcust.DESC04",
                        "siswa" => "scctcust.nmcust",
                        "custid" => "scctbill.CUSTID",
                        "metode_bayar" => "scctbill.FIDBANK",
                        default => null,
                    };
                    switch ($key) {
                        case "tanggal-transaksi":
                            if (
                                preg_match(
                                    '/^\d{2}-\d{2}-\d{4} [-\/~] \d{2}-\d{2}-\d{4}$/',
                                    $val,
                                )
                            ) {
                                $val = preg_replace("/[-\/~]/", "-", $val);

                                [$startDate, $endDate] = explode(" - ", $val);
                                $startDate = Carbon::createFromFormat(
                                    "d-m-Y",
                                    $startDate,
                                )->startOfDay();
                                $endDate = Carbon::createFromFormat(
                                    "d-m-Y",
                                    $endDate,
                                )->endOfDay();
                                if ($startDate && $endDate) {
                                    $colName &&
                                    ($filters[] = [
                                        $colName,
                                        $startDate,
                                        $endDate,
                                        "whereBetween",
                                    ]);
                                }
                            }
                            break;
                        case "metode_bayar":
                            if ($val === "NULL") {
                                $colName && ($filters[] = [$colName, "=", null]);
                            } else if ($val === "empty") {
                                $colName && ($filters[] = [$colName, "=", '']);
                            } else {
                                $colName && ($filters[] = [$colName, "like", "$val"]);
                            }
                            break;
                        case "kelas":
                            $val = explode("~", $val);
                            if (count($val) == 3) {
                                $filters[] = ["scctcust.CODE02", "=", $val[0]];
                                $filters[] = ["scctcust.DESC02", "=", $val[1]];
                                $filters[] = ["scctcust.DESC03", "=", $val[2]];
                            }
                            break;
                        case "post":
                            $array = array_filter($val, function ($value) {
                                return $value !== "all";
                            });
                            if (count($array) > 0) {
                                $colName &&
                                ($filters[] = [$colName, "in", $array]);
                            }
                            break;
                        case 'siswa':
                            $val = is_numeric($val) ? $val : "%" . $val . "%";
                            $colName = is_numeric($val)
                                ? "scctcust.nocust"
                                : $colName;
                            $colName && ($filters[] = [$colName, "like", $val]);
                            break;
                        default:
                            $colName && ($filters[] = [$colName, "=", $val]);
                            break;
                    }
                }
            }

            if (!empty($filters)) {
                $filterQuery = function ($query) use ($filters) {
                    foreach ($filters as $filter) {
                        switch (count($filter)) {
                            case 3:
                                $filter[1] === "in"
                                    ? $query->whereIn(
                                    $filter[0],
                                    $filter[2],
                                )
                                    : $query->where(
                                    $filter[0],
                                    $filter[1],
                                    $filter[2],
                                );
                                break;

                            case 4:
                                $filter[3] === "whereBetween"
                                    ? $query->whereBetween($filter[0], [
                                    $filter[1],
                                    $filter[2],
                                ])
                                    : $query->{$filter[3]}(
                                    $filter[0],
                                    $filter[1],
                                    $filter[2],
                                );
                                break;
                        }
                    }
                };
            }
        }

        $whereAny = ["scctcust.nmcust", "scctcust.nocust"];

        $select = array_unique(
            array_merge($whereAny, [
                "scctbill.AA",
                "scctbill.BILLNM",
                "scctbill.BILLAM",
                "scctbill.PAIDST",
                "scctbill.PAIDDT",
                "scctbill.BTA",
                "scctbill.CUSTID",
                "scctbill.FIDBANK",
                "scctbill.FUrutan",
                "scctcust.CODE02",
                "scctcust.DESC02",
            ]),
        );

        $query = scctbill::leftJoin(
            "scctcust",
            "scctcust.CUSTID",
            "scctbill.CUSTID",
        )
            ->where("scctbill.PAIDST", 1)
            ->where("scctbill.FSTSBolehBayar", 1)
            ->where("scctcust.STCUST", 1)
            ->where("scctbill.PAIDDT", "!=", null)
            ->whereAny($whereAny, "like", "%" . $searchValue . "%")
            ->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            })->orderByRaw("
                    CASE
                        WHEN scctbill.BILLNM LIKE '%JULI%' THEN 1
                        WHEN scctbill.BILLNM LIKE '%AGUSTUS%' THEN 2
                        WHEN scctbill.BILLNM LIKE '%SEPTEMBER%' THEN 3
                        WHEN scctbill.BILLNM LIKE '%OKTOBER%' THEN 4
                        WHEN scctbill.BILLNM LIKE '%NOVEMBER%' THEN 5
                        WHEN scctbill.BILLNM LIKE '%DESEMBER%' THEN 6
                        WHEN scctbill.BILLNM LIKE '%JANUARI%' THEN 7
                        WHEN scctbill.BILLNM LIKE '%FEBRUARI%' THEN 8
                        WHEN scctbill.BILLNM LIKE '%MARET%' THEN 9
                        WHEN scctbill.BILLNM LIKE '%APRIL%' THEN 10
                        WHEN scctbill.BILLNM LIKE '%MEI%' THEN 11
                        WHEN scctbill.BILLNM LIKE '%JUNI%' THEN 12
                        ELSE 999
                    END
                ");

        // Total records
        $totalRecords = scctbill::select("count(*) as allcount")
            ->where("PAIDST", 1)
            ->where("scctbill.FSTSBolehBayar", 1)
            ->where("PAIDDT", "!=", null)
            ->count();

        $totalRecordswithFilter = (clone $query)->count();

        $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
        $records = (clone $query)
            ->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->whereAny($whereAny, "like", "%" . $searchValue . "%")
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $records = $records->map(function ($item, $index) use ($request) {
            $item->NOVA = match (strtolower($item->CODE02)) {
                "mts" => scctcust::showVAMTS($item->nocust),
                "ma" => scctcust::showVAMA($item->nocust),
                default => "",
            };

            return $item;
        });

        $records->toArray();

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
        ];
        return response()->json($response);
    }

    public function cetakRekapPenerimaan(Request $request)
    {
        if (
            !isset($request->filter["tanggal-transaksi"]) ||
            $request->filter["tanggal-transaksi"] == null ||
            !preg_match(
                '/^\d{2}-\d{2}-\d{4} [-\/~] \d{2}-\d{2}-\d{4}$/',
                $request->filter["tanggal-transaksi"],
            )
        ) {
            return response()->json(
                [
                    "message" =>
                        'Tidak dapat mencetak rekap penerimaan!<br> <span class="text-danger">*</span>Tanggal transaksi pembayaran tidak boleh kosong',
                ],
                422,
            );
        } elseif (
            (!isset($request->filter["unit"]) ||
                $request->filter["unit"] == null ||
                $request->filter["unit"] == "all") &&
            (!isset($request->filter["kelas"]) ||
                $request->filter["kelas"] == null ||
                $request->filter["kelas"] == "all")
        ) {
            return response()->json(
                [
                    "message" =>
                        "Tidak dapat mencetak rekap penerimaan!<br> * Tingkat atau Kelas Harus Diisi, silahkan pilih salah satu tingkat atau kelas" .
                        $request->filter["unit"],
                ],
                422,
            );
        }

        $filters = [];
        $filterQuery = null;
        $filter_scctbill = [];
        $post = false;
        $kelas = [];
        $unit = false;
        $tanggalMulai = null;
        $tanggalSelesai = null;
        $filter = $request->input("filter");
        if ($filter) {
            foreach ($filter as $key => $val) {
                if (
                    is_array($val) ||
                    (strtolower($val) != "all" && $val !== null && $val !== "")
                ) {
                    $colName = match ($key) {
                        "dari_tanggal",
                        "sampai_tanggal"
                        => "scctbill.FTGLTagihan",
                        "tanggal-transaksi" => "scctbill.PAIDDT",
                        "tahun_akademik" => "scctbill.BTA",
                        "post" => "scctbill.BILLNM",
                        "unit" => "scctcust.CODE01",
                        "kelas" => "scctcust.DESC02",
                        "siswa" => "scctcust.nmcust",
                        "custid" => "scctbill.CUSTID",
                        "metode_bayar" => "scctbill.FIDBANK",
                        default => null,
                    };
                    switch ($key) {
                        case "tanggal-transaksi":
                            if (
                                preg_match(
                                    '/^\d{2}-\d{2}-\d{4} [-\/~] \d{2}-\d{2}-\d{4}$/',
                                    $val,
                                )
                            ) {
                                $val = preg_replace("/[-\/~]/", "-", $val);

                                [$startDate, $endDate] = explode(" - ", $val);
                                $startDate = Carbon::createFromFormat(
                                    "d-m-Y",
                                    $startDate,
                                )->startOfDay();
                                $endDate = Carbon::createFromFormat(
                                    "d-m-Y",
                                    $endDate,
                                )->endOfDay();
                                if ($startDate && $endDate) {
                                    $colName &&
                                    ($filters[] = [
                                        $colName,
                                        $startDate,
                                        $endDate,
                                        "whereBetween",
                                    ]);
                                }
                            }
                            break;
                        case "metode_bayar":
                            if ($val === "NULL") {
                                $colName && ($filters[] = [$colName, "=", null]);
                            } else if ($val === "empty") {
                                $colName && ($filters[] = [$colName, "=", '']);
                            } else {
                                $colName && ($filters[] = [$colName, "like", "$val"]);
                            }
                            break;
                        case "kelas":
                            $val = explode("~", $val);
                            if (count($val) == 3) {
                                $filters[] = ["scctcust.CODE02", "=", $val[0]];
                                $filters[] = ["scctcust.DESC02", "=", $val[1]];
                                $filters[] = ["scctcust.DESC03", "=", $val[2]];
                            }
                            break;
                        case "post":
                            $array = array_filter($val, function ($value) {
                                return $value !== "all";
                            });
                            if (count($array) > 0) {
                                $colName &&
                                ($filters[] = [$colName, "in", $array]);
                            }
                            break;
                        case 'siswa':
                            $val = is_numeric($val) ? $val : "%" . $val . "%";
                            $colName = is_numeric($val)
                                ? "scctcust.nocust"
                                : $colName;
                            $colName && ($filters[] = [$colName, "like", $val]);
                            break;
                        default:
                            $colName && ($filters[] = [$colName, "=", $val]);
                            break;
                    }
                }
            }
        }

        $filter_main = [];

        foreach ($filters as $item) {
            if (str_contains($item[0], "scctbill")) {
                $filter_scctbill[] = $item;
            } else {
                $filter_main[] = $item;
            }
        }

        $whereAny = ["scctcust.nmcust", "scctcust.nocust"];

        $select = array_unique(
            array_merge($whereAny, [
                "scctcust.CODE02",
                "scctcust.DESC02",
                "scctcust.DESC03",
            ]),
        );

        try {
            $mstTagihan = mst_tagihan::select(["tagihan"])
                ->where(function ($query) use ($post) {
                    if ($post) {
                        $query->whereIn("tagihan", $post);
                    }
                })
                //                    ->orderByRaw('kode IS NULL')
                //                    ->orderBy('kode', 'asc')
                ->orderByRaw(
                    "
                        CASE
                            WHEN kode BETWEEN '07' AND '12' THEN 0
                            WHEN kode BETWEEN '01' AND '06' THEN 1
                            ELSE 2
                        END,
                        kode ASC
                    ",
                )
                ->get();

            $records = DB::table("scctcust")
                ->orderBy("scctcust.nmcust", "asc")
                ->leftJoin("scctbill", function ($join) use ($filter_scctbill) {
                    $join
                        ->on("scctbill.CUSTID", "=", "scctcust.CUSTID")
                        ->where("scctbill.PAIDST", 1)
                        ->where("scctbill.FSTSBolehBayar", 1)
                        ->whereNotNull("scctbill.PAIDDT")
                        ->where(function ($query) use ($filter_scctbill) {
                            foreach ($filter_scctbill as $filter) {
                                switch (count($filter)) {
                                    case 3:
                                        $filter[1] === "in"
                                            ? $query->whereIn(
                                            $filter[0],
                                            $filter[2],
                                        )
                                            : $query->where(
                                            $filter[0],
                                            $filter[1],
                                            $filter[2],
                                        );
                                        break;
                                    case 4:
                                        $filter[3] === "whereBetween"
                                            ? $query->whereBetween($filter[0], [
                                            $filter[1],
                                            $filter[2],
                                        ])
                                            : $query->{$filter[3]}(
                                            $filter[0],
                                            $filter[1],
                                            $filter[2],
                                        );
                                        break;
                                }
                            }
                        });
                })
                ->where(function ($query) use ($filter_main) {
                    foreach ($filter_main as $filter) {
                        switch (count($filter)) {
                            case 3:
                                $filter[1] === "in"
                                    ? $query->whereIn($filter[0], $filter[2])
                                    : $query->where(
                                    $filter[0],
                                    $filter[1],
                                    $filter[2],
                                );
                                break;

                            case 4:
                                $filter[3] === "whereBetween"
                                    ? $query->whereBetween($filter[0], [
                                    $filter[1],
                                    $filter[2],
                                ])
                                    : $query->{$filter[3]}(
                                    $filter[0],
                                    $filter[1],
                                    $filter[2],
                                );
                                break;
                        }
                    }
                })
                ->where("scctcust.STCUST", 1)
                ->select($select)
                ->groupBy("scctcust.CUSTID");

            foreach ($mstTagihan as $val) {
                $namaPost = $val["tagihan"];
                $records->addSelect(
                    DB::raw(
                        "SUM(CASE WHEN scctbill.BILLNM = '{$namaPost}' THEN scctbill.BILLAM ELSE 0 END) AS '{$namaPost}'",
                    ),
                );
            }

            //            $records = $records->get();
            $records = DB::table(DB::raw("({$records->toSql()}) as sub"))
                ->mergeBindings($records)
                ->where(function ($q) use ($mstTagihan) {
                    foreach ($mstTagihan as $val) {
                        $namaPost = $val["tagihan"];
                        $q->orWhere($namaPost, ">", 0);
                    }
                })
                ->get();

            if (!$records || !$mstTagihan) {
                throw new \Exception("Gagal mengambil data tagihan");
            }

            $zeroColumns = [];
            $filtered = $records->map(function ($item) use (
                $records,
                &$zeroColumns,
            ) {
                $zeroColumns = collect($item)
                    ->keys()
                    ->filter(function ($key) use ($records) {
                        return $records
                            ->pluck($key)
                            ->every(fn($value) => $value == 0);
                    });

                return collect($item)->except($zeroColumns);
            });

            $mstTagihan = $mstTagihan->pluck("tagihan");
            $zeroColumns = $zeroColumns->toArray();
            $filteredMstTagihan = $mstTagihan->reject(function ($value) use (
                $zeroColumns,
            ) {
                return in_array($value, $zeroColumns, true);
            });

            $customPaper = [0, 0, 935.43, 595.28];

            $pdf = Pdf::loadView("cetak.rekap-penerimaan", [
                "tagihans" => $filtered,
                "mstTagihan" => $filteredMstTagihan,
                "kelas" => $kelas,
                "unit" => $unit,
                "tanggalMulai" => $tanggalMulai,
                "tanggalSelesai" => $tanggalSelesai,
            ])
                ->setOptions([
                    "isHtml5ParserEnabled" => true,
                    "isPhpEnabled" => true,
                    //                        'dpi' => 96,
                ])
                //                    ->setPaper('a4', 'landscape');
                ->setPaper($customPaper);

            return $pdf->download("rekap-penerimaan.pdf");
        } catch (\Throwable $e) {
            return response()->json(
                [
                    "message" =>
                        "Tidak dapat mencetak rekap penerimaan!<br> *Silahkan hubungi administrator",
                    "error" => $e->getMessage(),
                ],
                422,
            );
        }
        //        $sqlWithPlaceholders = $records->toSql();
        //
        //        $bindings = $records->getBindings();
        //
        //        $fullSql = Str::replaceArray('?', array_map(function ($b) {
        //            return is_numeric($b) ? $b : "'" . addslashes($b) . "'";
        //        }, $bindings), $sqlWithPlaceholders);

        //        dd($records);
    }

    public function getDataRekap(Request $request)
    {
        if (
            $request->filter["kelas"] != null &&
            $request->filter["kelas"] != "all"
        ) {
            $filters = [];
            $filterQuery = null;
            $filter_scctbill = [];
            $post = false;
            $kelas = [];
            $tanggalMulai = null;
            $tanggalSelesai = null;
            $filter = $request->input("filter");
            if ($filter) {
                foreach ($filter as $key => $val) {
                    if (
                        is_array($val) ||
                        (strtolower($val) != "all" &&
                            $val !== null &&
                            $val !== "")
                    ) {
                        $colName = match ($key) {
                            "dari_tanggal",
                            "sampai_tanggal"
                            => "scctbill.FTGLTagihan",
                            "tanggal-transaksi" => "scctbill.PAIDDT",
                            "tahun_akademik" => "scctbill.BTA",
                            "post" => "scctbill.BILLNM",
                            "unit" => "scctcust.CODE01",
                            "kelas" => "scctcust.DESC02",
                            "siswa" => "scctcust.nmcust",
                            "custid" => "scctbill.CUSTID",
                            "periode" => "scctbill.BILLAC",
                            "metode_bayar" => "scctbill.FIDBANK",
                            default => null,
                        };

                        switch ($key) {
                            case "tanggal-transaksi":
                                if (
                                    preg_match(
                                        '/^\d{2}-\d{2}-\d{4} [-\/~] \d{2}-\d{2}-\d{4}$/',
                                        $val,
                                    )
                                ) {
                                    $val = preg_replace("/[-\/~]/", "-", $val);

                                    [$startDate, $endDate] = explode(" - ", $val);
                                    $startDate = Carbon::createFromFormat(
                                        "d-m-Y",
                                        $startDate,
                                    )->startOfDay();
                                    $endDate = Carbon::createFromFormat(
                                        "d-m-Y",
                                        $endDate,
                                    )->endOfDay();
                                    if ($startDate && $endDate) {
                                        $colName &&
                                        ($filters[] = [
                                            $colName,
                                            $startDate,
                                            $endDate,
                                            "whereBetween",
                                        ]);
                                    }
                                }
                                break;
                            case "metode_bayar":
                                if ($val === "NULL") {
                                    $colName && ($filters[] = [$colName, "=", null]);
                                } else if ($val === "empty") {
                                    $colName && ($filters[] = [$colName, "=", '']);
                                } else {
                                    $colName && ($filters[] = [$colName, "like", "$val"]);
                                }
                                break;
                            case "kelas":
                                $val = explode("~", $val);
                                $kelas = $val;
                                if (count($val) == 3) {
                                    $filters[] = ["scctcust.CODE02", "=", $val[0]];
                                    $filters[] = ["scctcust.DESC02", "=", $val[1]];
                                    $filters[] = ["scctcust.DESC03", "=", $val[2]];
                                }
                                break;
                            case "post":
                                $array = array_filter($val, function ($value) {
                                    return $value !== "all";
                                });
                                if (count($array) > 0) {
                                    $colName &&
                                    ($filters[] = [$colName, "in", $array]);
                                }
                                break;
                            case 'siswa':
                                $val = is_numeric($val) ? $val : "%" . $val . "%";
                                $colName = is_numeric($val)
                                    ? "scctcust.nocust"
                                    : $colName;
                                $colName && ($filters[] = [$colName, "like", $val]);
                                break;
                            default:
                                $colName && ($filters[] = [$colName, "=", $val]);
                                break;
                        }
                    }
                }
            }

            $filter_main = [];

            foreach ($filters as $item) {
                if (str_contains($item[0], "scctbill.")) {
                    $filter_scctbill[] = $item;
                } else {
                    $filter_main[] = $item;
                }
            }

            $whereAny = ["scctcust.nmcust", "scctcust.nocust"];

            $select = array_unique(
                array_merge($whereAny, [
                    "scctcust.CODE02",
                    "scctcust.DESC02",
                    "scctcust.DESC03",
                ]),
            );

            try {
                $mstTagihan = mst_tagihan::select(["tagihan"])
                    ->where(function ($query) use ($post) {
                        if ($post) {
                            $query->whereIn("tagihan", $post);
                        }
                    })
                    ->orderByRaw(
                        "
                        CASE
                            WHEN kode BETWEEN '07' AND '12' THEN 0
                            WHEN kode BETWEEN '01' AND '06' THEN 1
                            ELSE 2
                        END,
                        kode ASC
                    ",
                    )
                    ->get();

                $records = scctcust::leftJoin("scctbill", function ($join) use (
                    $filter_scctbill,
                ) {
                    $join
                        ->on("scctbill.CUSTID", "=", "scctcust.CUSTID")
                        ->where("scctbill.PAIDST", 1)
                        ->where("scctbill.FSTSBolehBayar", 1)
                        ->whereNotNull("scctbill.PAIDDT")
                        ->where(function ($query) use ($filter_scctbill) {
                            foreach ($filter_scctbill as $filter) {
                                switch (count($filter)) {
                                    case 3:
                                        $filter[1] === "in"
                                            ? $query->whereIn(
                                            $filter[0],
                                            $filter[2],
                                        )
                                            : $query->where(
                                            $filter[0],
                                            $filter[1],
                                            $filter[2],
                                        );
                                        break;
                                    case 4:
                                        $filter[3] === "whereBetween"
                                            ? $query->whereBetween($filter[0], [
                                            $filter[1],
                                            $filter[2],
                                        ])
                                            : $query->{$filter[3]}(
                                            $filter[0],
                                            $filter[1],
                                            $filter[2],
                                        );
                                        break;
                                }
                            }
                        });
                })
                    ->where(function ($query) use ($filter_main) {
                        foreach ($filter_main as $filter) {
                            switch (count($filter)) {
                                case 3:
                                    $filter[1] === "in"
                                        ? $query->whereIn(
                                        $filter[0],
                                        $filter[2],
                                    )
                                        : $query->where(
                                        $filter[0],
                                        $filter[1],
                                        $filter[2],
                                    );
                                    break;

                                case 4:
                                    $filter[3] === "whereBetween"
                                        ? $query->whereBetween($filter[0], [
                                        $filter[1],
                                        $filter[2],
                                    ])
                                        : $query->{$filter[3]}(
                                        $filter[0],
                                        $filter[1],
                                        $filter[2],
                                    );
                                    break;
                            }
                        }
                    })
                    ->where("scctcust.STCUST", 1)
                    ->orderBy("scctcust.nmcust", "asc")
                    ->select($select)
                    ->groupBy("scctcust.CUSTID");

                foreach ($mstTagihan as $val) {
                    $namaPost = $val["tagihan"];
                    $records->addSelect(
                        DB::raw(
                            "SUM(CASE WHEN scctbill.BILLNM = '{$namaPost}' THEN scctbill.BILLAM ELSE 0 END) AS '{$namaPost}'",
                        ),
                    );
                }


                $records = $records->get();

                $kelas = mst_kelas::where("unit", $kelas[0])
                    ->where("jenjang", $kelas[1])
                    ->where("kelas", $kelas[2])
                    ->first();
                if (!$records || !$mstTagihan) {
                    throw new \Exception("Gagal mengambil data tagihan");
                }

                $zeroColumns = [];
                $filtered = $records->map(function ($item) use (
                    $records,
                    &$zeroColumns,
                ) {
                    $zeroColumns = collect($item)
                        ->keys()
                        ->filter(function ($key) use ($records) {
                            return $records
                                ->pluck($key)
                                ->every(fn($value) => $value == 0);
                        });

                    return collect($item)->except($zeroColumns);
                });

                $mstTagihan = $mstTagihan->pluck("tagihan");
                if ($zeroColumns->count() > 0) {
                    $zeroColumns = $zeroColumns->toArray();
                }
                $filteredMstTagihan = $mstTagihan->reject(function (
                    $value,
                ) use ($zeroColumns) {
                    return in_array($value, $zeroColumns, true);
                });

                $data = [
                    "data" => $filtered,
                    "mstTagihan" => $filteredMstTagihan->toArray(),
                    "kelas" => $kelas,
                    "tanggalMulai" => $tanggalMulai,
                    "tanggalSelesai" => $tanggalSelesai,
                ];
                return response()->json($data, 200);
            } catch (\Exception $e) {
                return response()->json(
                    [
                        "message" =>
                            "Tidak dapat mencetak rekap penerimaan!<br> *Silahkan hubungi administrator",
                        "error" => $e->getMessage(),
                    ],
                    422,
                );
            }
        } else {
            return response()->json(
                [
                    "message" =>
                        "Tidak dapat mencetak rekap penerimaan!<br> *Kelas Harus Diisi, silahkan pilih salah satu kelas",
                ],
                422,
            );
        }
    }
}
