<?php

namespace App\Http\Controllers\Admin\Rekap\RekapTaighan;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapTagihanPerAkunController extends Controller
{
    private string $title;
    private string $datasUrl;
    private string $columnsUrl;
    private string $mainTitle;

    public function __construct()
    {
        $this->title = "Rekap Data";
        $this->mainTitle = "Rekap Tagihan";
        $this->datasUrl = route("admin.rekap-tagihan.get-data");
        $this->columnsUrl = route("admin.rekap-tagihan.get-column");
    }

    public function index()
    {
        $data["title"] = $this->title;
        $data["mainTitle"] = $this->mainTitle;
        $data["columnsUrl"] = $this->columnsUrl;
        $data["datasUrl"] = $this->datasUrl;
        $data["post"] = mst_tagihan::select(["tagihan"])->get();
        $data["thn_aka"] = mst_thn_aka::select(["thn_aka"])
            ->where("thn_aka", "!=", null)
            ->orderBy("thn_aka", "desc")
            ->get();
        $data["kelas"] = mst_kelas::get();

        return view("admin.rekap_tagihan.index", $data);
    }

    public function getColumn()
    {
        return [
            ["data" => "AA", "name" => "no", "columnType" => "row"],
            [
                "data" => "BTA",
                "name" => "Tahun Pelajaran",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "nocust",
                "name" => "NIS",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
            ],
            [
                "data" => "nmcust",
                "name" => "NAMA",
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
                "classname" => "text-end",
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
        ];
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
                    (strtolower($val) != "all" && $val !== null && $val !== "")
                ) {
                    $colName = match ($key) {
                        "tahun_akademik" => "scctbill.BTA",
                        "post" => "scctbill.BILLNM",
                        "unit" => "scctcust.CODE01",
                        "kelas" => "scctcust.DESC02",
                        "siswa" => "scctcust.nmcust",
                        "custid" => "scctbill.CUSTID",
                        default => null,
                    };
                    if ($key == "kelas") {
                        $val = explode("~", $val);
                        if (count($val) == 3) {
                            $filters[] = ["scctcust.CODE02", "=", $val[0]];
                            $filters[] = ["scctcust.DESC02", "=", $val[1]];
                            $filters[] = ["scctcust.DESC03", "=", $val[2]];
                        }
                    } elseif ($key == "post") {
                        $array = array_filter($val, function ($value) {
                            return $value !== "all";
                        });
                        if (count($array) > 0) {
                            $colName && ($filters[] = [$colName, "in", $array]);
                        }
                    } elseif ($key == "siswa") {
                        $val = is_numeric($val) ? $val : "%" . $val . "%";
                        $colName = is_numeric($val)
                            ? "scctcust.nocust"
                            : $colName;
                        $colName && ($filters[] = [$colName, "like", $val]);
                    } else {
                        $colName && ($filters[] = [$colName, "=", $val]);
                    }
                }
            }

            if (!empty($filters)) {
                $filterQuery = function ($query) use ($filters) {
                    foreach ($filters as $filter) {
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
            ->where("scctbill.PAIDST", 0)
            // ->where("scctbill.FSTSBolehBayar", 1)
            ->where("scctcust.STCUST", 1)
            // ->where("scctbill.PAIDDT", "!=", null)
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

            $item->item_id = $item["AA"];

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

    public function getRekapDataTagihan(Request $request)
    {
        $request->validate(
            [
                "filter.tahun_akademik" => "required|not_in:all",
            ],
            [
                "filter.tahun_akademik.required" =>
                    "Silahkan pilih satu tahun akademik terlebih dahulu",
                "filter.tahun_akademik.not_in" =>
                    "silahkan pilih satu tahun akademik terlebih dahulu",
            ],
        );

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
                        default => null,
                    };

                    if ($key == "tanggal-transaksi") {
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
                    } elseif ($key == "kelas") {
                        $val = explode("~", $val);
                        $kelas = $val;
                        if (count($val) == 3) {
                            $filters[] = ["scctcust.CODE02", "=", $val[0]];
                            $filters[] = ["scctcust.DESC02", "=", $val[1]];
                            $filters[] = ["scctcust.DESC03", "=", $val[2]];
                        }
                    } elseif ($key == "post") {
                        $array = array_filter($val, function ($value) {
                            return $value !== "all";
                        });
                        if (count($array) > 0) {
                            $colName && ($filters[] = [$colName, "in", $array]);
                        }
                        $post = $array;
                    } elseif ($key === "unit") {
                        $unit = mst_sekolah::where("CODE01", $val)->first();
                        $colName && ($filters[] = [$colName, "=", $val]);
                    } elseif ($key == "siswa") {
                        $val = is_numeric($val) ? $val : "%" . $val . "%";
                        $colName = is_numeric($val)
                            ? "scctcust.nocust"
                            : $colName;
                        $colName && ($filters[] = [$colName, "like", $val]);
                    } else {
                        $colName && ($filters[] = [$colName, "=", $val]);
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

        $whereAny = [];

        $select = array_unique([
            // ...$whereAny,
            // "scctbill.*",
            "u_akun.KodeAkun",
            "u_akun.NamaAkun",
        ]);

        try {
            // $mstTagihan = mst_tagihan::select(["tagihan"])
            //     ->where(function ($query) use ($post) {
            //         if ($post) {
            //             $query->whereIn("tagihan", $post);
            //         }
            //     })
            //     //                    ->orderByRaw('kode IS NULL')
            //     //                    ->orderBy('kode', 'asc')
            //     ->orderByRaw(
            //         "
            //             CASE
            //                 WHEN kode BETWEEN '07' AND '12' THEN 0
            //                 WHEN kode BETWEEN '01' AND '06' THEN 1
            //                 ELSE 2
            //             END,
            //             kode ASC
            //         ",
            //     )
            //     ->get();

            $kelas = mst_kelas::get();

            $records = DB::connection('DATA_MYSQL')->table("u_akun")
                ->join("scctbill_detail", function ($join) {
                    $join->on(
                        "u_akun.KodeAkun",
                        "=",
                        "scctbill_detail.KodePost",
                    );
                })
                ->leftJoin("scctbill", function ($join) use ($filter_scctbill) {
                    $join
                        ->on("scctbill_detail.CUSTID", "=", "scctbill.CUSTID")
                        ->on("scctbill_detail.BILLCD", "=", "scctbill.BILLCD")
                        ->where("scctbill.PAIDST", 0)
                        ->where("scctbill.FSTSBolehBayar", 1)
                        ->whereNotNull("scctbill.PAIDDT")
                        ->where(function ($query) use ($filter_scctbill) {
                            foreach ($filter_scctbill as $filter) {
                                switch (\count($filter)) {
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
                ->leftJoin("scctcust", function ($join) {
                    $join->on("scctcust.CUSTID", "=", "scctbill_detail.CUSTID");
                })
                // ->where(function ($query) use ($filter_main) {
                //     foreach ($filter_main as $filter) {
                //         switch (count($filter)) {
                //             case 3:
                //                 $filter[1] === "in"
                //                     ? $query->whereIn($filter[0], $filter[2])
                //                     : $query->where(
                //                         $filter[0],
                //                         $filter[1],
                //                         $filter[2],
                //                     );
                //                 break;

                //             case 4:
                //                 $filter[3] === "whereBetween"
                //                     ? $query->whereBetween($filter[0], [
                //                         $filter[1],
                //                         $filter[2],
                //                     ])
                //                     : $query->{$filter[3]}(
                //                         $filter[0],
                //                         $filter[1],
                //                         $filter[2],
                //                     );
                //                 break;
                //         }
                //     }
                // })
                ->where("scctcust.STCUST", 1)
                ->select($select)
                ->orderBy("u_akun.KodeAkun", "asc")
                // ->orderBy("scctbill.BILLNM", "asc")
                ->groupBy("u_akun.KodeAkun");

            $records->addSelect(DB::raw("MAX(scctbill.BILLNM) as bill_name"));
            foreach ($kelas as $val) {
                $id_kelas = $val["id"];
                $jenjang = $val["jenjang"];
                $records->addSelect(
                    DB::raw(
                        "SUM(CASE WHEN scctcust.CODE03 = '{$id_kelas}' THEN scctbill.BILLAM ELSE 0 END) AS '{$id_kelas}'",
                    ),
                );
            }

            //            $records = $records->get();
            $records = DB::connection('DATA_MYSQL')->table(DB::raw("({$records->toSql()}) as sub"))
                ->mergeBindings($records)
                ->where(function ($q) use ($kelas) {
                    foreach ($kelas as $val) {
                        $id_kelas = $val["id"];
                        // $namaPost = $val["jenjang"];
                        $q->orWhere($id_kelas, ">", 0);
                    }
                })
                ->get();

            if (!$records || !$kelas) {
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

            $zeroColumns = collect($zeroColumns);

            $kelasClean = $kelas
                ->reject(fn($item) => $zeroColumns->contains($item->id))
                ->values();

            return response()->json(
                ["data" => $filtered, "kelas" => $kelasClean],
                200,
            );
        } catch (\Throwable $e) {
            return response()->json(
                [
                    "message" =>
                        "Tidak dapat mencetak rekap tagihan!<br> *Silahkan hubungi administrator",
                    "error" => $e->getMessage(),
                ],
                422,
            );
        }
    }
}
