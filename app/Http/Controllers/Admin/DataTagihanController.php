<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DataTagihanController extends Controller
{
    public function __construct()
    {
        $this->title = "Data Tagihan Siswa";
        $this->datasUrl = route("admin.data-tagihan.get-data");
        $this->detailDatasUrl = "";
        $this->columnsUrl = route("admin.data-tagihan.get-column");
    }

    public function getColumn()
    {
        return [
            [
                "data" => "AA",
                "name" => "no",
                "columnType" => "row",
                "exportable" => true,
            ],
            [
                "data" => "nocust",
                "name" => "NIS",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => false
            ],
            [
                "data" => "nmcust",
                "name" => "NAMA",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => false
            ],
            [
                "data" => "CODE02",
                "name" => "Unit",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => false
            ],
            [
                "data" => "DESC02",
                "name" => "Kelas",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => false
            ],
            [
                "data" => "DESC03",
                "name" => "Kelompok",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => false
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
                "data" => "PAIDST",
                "name" => "Status",
                "orderable" => true,
                "columnType" => "boolean",
                "trueVal" => "Lunas",
                "falseVal" => "Belum Lunas",
                "exportable" => true,
            ],
            [
                "data" => "FUrutan",
                "name" => "urutan",
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

        return view("admin.data_tagihan", $data);
    }

    public function cetak(Request $request)
    {
        ini_set("max_execution_time", 300);
        //        $pdf = Pdf::loadView('cetak.data-penerimaan')->setPaper('a4', 'landscape');
        //        return $pdf->download('rekap-tagihan.pdf');

        try {
            $filters = [];
            $filterQuery = null;

            $filter = $request->input("filter");
            if ($filter) {
                foreach ($filter as $key => $val) {
                    if (
                        strtolower($val) != "all" &&
                        $val !== null &&
                        $val !== ""
                    ) {
                        $colName = match ($key) {
                            "tanggal-pembuatan" => "scctbill.FTGLTagihan",
                            "tahun_akademik" => "scctbill.BTA",
                            "post" => "scctbill.BILLNM",
                            "kelas" => "scctcust.DESC02",
                            "siswa" => "scctcust.nmcust",
                            default => null,
                        };
                        if ($key == "tanggal-pembuatan") {
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
                            if (count($filter) === 3) {
                                $query->where(
                                    $filter[0],
                                    $filter[1],
                                    $filter[2],
                                );
                            } elseif (count($filter) === 4) {
                                if ($filter[3] == "whereBetween") {
                                    $query->whereBetween($filter[0], [
                                        $filter[1],
                                        $filter[2],
                                    ]);
                                } else {
                                    $query->{$filter[3]}(
                                        $filter[0],
                                        $filter[1],
                                        $filter[2],
                                    );
                                }
                            }
                        }
                    };
                }

                $posts = mst_tagihan::select("urut", "tagihan", "kode")
                    ->get()
                    ->map(function ($item) use ($filterQuery) {
                        $item->tagihans = scctbill::leftJoin(
                            "scctcust",
                            "scctcust.CUSTID",
                            "scctbill.CUSTID",
                        )
                            ->select([
                                "scctcust.nmcust",
                                "scctcust.nocust",
                                "scctbill.AA",
                                "scctbill.BILLNM",
                                "scctbill.BILLAM",
                                "scctbill.PAIDST",
                                "scctbill.PAIDDT",
                                "scctbill.BTA",
                                "scctbill.FIDBANK",
                                "scctbill.FUrutan",
                                "scctcust.CODE02",
                                "scctcust.DESC02",
                            ])
                            ->where("scctbill.BILLNM", $item->tagihan)
                            ->where("scctbill.PAIDST", 0)
                            ->where("scctbill.FSTSBolehBayar", 1)
                            ->where("scctcust.STCUST", 1)
                            ->where(function ($query) use ($filterQuery) {
                                if ($filterQuery) {
                                    $filterQuery($query);
                                }
                            })
                            ->orderBy("scctbill.CUSTID", "desc")
                            ->orderBy("scctbill.BTA", "desc")
                            ->orderBy("scctbill.PAIDDT", "desc")
                            ->get()
                            ->toArray();

                        return $item;
                    });
            }

            //            dd($posts[0]['tagihan']);
            //            return  view('pdf.data_penerimaan.rekap_penerimaan', ['posts' => $posts]);

            //            $view = view('cetak.data-penerimaan', compact('posts'))->render();
            //            return response()->json(['html' => $view]);

            if ($posts) {
                $pdf = Pdf::loadView("cetak.data-tagihan", [
                    "posts" => $posts,
                ])->setPaper("a4", "landscape");
                return $pdf->download("rekap-tagihan.pdf");
            } else {
                return response()->json(["message" => "Data Kosong"], 422);
            }
        } catch (\Exception $e) {
            return response()->json(
                [
                    "message" => "Tidak dapat mencetak rekap",
                    "error" => $e->getMessage(),
                    "e" => $e,
                ],
                422,
            );
        }
    }

    public function cetakRekapTagihan(Request $request)
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
                            "tahun_akademik" => "scctbill.BTA",
                            "post" => "scctbill.BILLNM",
                            "unit" => "scctcust.CODE01",
                            "kelas" => "scctcust.DESC02",
                            "siswa" => "scctcust.nmcust",
                            "custid" => "scctbill.CUSTID",
                            "periode" => "scctbill.BILLAC",
                            default => null,
                        };

                        if ($key == "kelas") {
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
                                $colName &&
                                ($filters[] = [$colName, "in", $array]);
                            }
                            $post = $array;
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
                    //                    ->orderBy('urut', 'asc')
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
                        ->where("scctbill.PAIDST", 0)
                        ->where("scctbill.FSTSBolehBayar", 1)
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
                $zeroColumns = $zeroColumns->toArray();
                $filteredMstTagihan = $mstTagihan->reject(function (
                    $value,
                ) use ($zeroColumns) {
                    return in_array($value, $zeroColumns, true);
                });

                //                $customPaper = [0, 0, 1684, 842];
                $customPaper = [0, 0, 935.43, 595.28];

                $pdf = Pdf::loadView("cetak.rekap-tagihan", [
                    "tagihans" => $filtered,
                    "mstTagihan" => $filteredMstTagihan,
                    "kelas" => $kelas,
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
                return $pdf->download("rekap-tagihan.pdf");
            } catch (\Exception $e) {
                return response()->json(
                    [
                        "message" =>
                            "Tidak dapat mencetak rekap tagihan!<br> *Silahkan hubungi administrator",
                        "error" => $e,
                    ],
                    422,
                );
            }
        } else {
            return response()->json(
                [
                    "message" =>
                        "Tidak dapat mencetak rekap tagihan!<br> *Kelas Harus Diisi, silahkan pilih salah satu kelas",
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

    public function cetakKartuSiswa(Request $request)
    {
        if (!$request["custid"]) {
            return response()->json(["message" => "siswa tidak ditemukan! 1"]);
        }
        $request["draw"] = 2;
        $request["start"] = 0;
        $request["length"] = "poll";

        $siswa = scctcust::where("CUSTID", $request["custid"])->first();
        if (!$siswa) {
            return response()->json(["message" => "siswa tidak ditemukan! 3"]);
        }

        $request->merge([
            "filter" => array_merge($request->input("filter", []), [
                "custid" => $request["custid"],
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

            $nova = match (strtolower($siswa["CODE02"])) {
                "mts" => scctcust::showVAMTS($siswa["NOCUST"]),
                "ma" => scctcust::showVAMA($siswa["NOCUST"]),
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
                [
                    "message" => "Tagihan Tidak Ditemukan",
                    "error" => $e->getMessage(),
                ],
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

        $columnName = "scctbill.FUrutan";
        $columnSortOrder = "asc";

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
                        "tanggal-pembuatan" => "scctbill.FTGLTagihan",
                        "tahun_akademik" => "scctbill.BTA",
                        "post" => "scctbill.BILLNM",
                        "kelas" => "scctcust.DESC02",
                        "siswa" => "scctcust.nmcust",
                        "custid" => "scctbill.CUSTID",
                        "periode" => "scctbill.BILLAC",
                        default => null,
                    };
                    if ($key == "tanggal-pembuatan") {
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
                "scctbill.FUrutan",
                "scctbill.FIDBANK",
                "scctbill.FUrutan",
                "scctbill.CUSTID",
                "scctcust.CODE02",
                "scctcust.DESC02",
                "scctcust.DESC03",
            ]),
        );

        $query = scctbill::leftJoin(
            "scctcust",
            "scctcust.CUSTID",
            "scctbill.CUSTID",
        )
            ->where("scctbill.PAIDST", 0)
            ->where("scctbill.FSTSBolehBayar", 1)
            ->where("scctcust.STCUST", 1)
            ->when(!blank($searchValue), function ($query) use ($whereAny, $searchValue) {
                $query->where(function ($q) use ($whereAny, $searchValue) {
                    $sanitizeSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchValue);
                    foreach ($whereAny as $column) {
                        $q->orWhere($column, 'like', '%' . $sanitizeSearch . '%');
                    }
                });
            })
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
        $totalRecords = scctbill::where("scctbill.PAIDST", 0)
            ->where("scctbill.FSTSBolehBayar", 1)
            ->count();

        $totalRecordswithFilter = (clone $query)->count();

        $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
        $records = (clone $query)
            ->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $records = $records->map(function ($item, $index) use ($request) {
            $item->NOVA = match (strtolower($item->CODE02)) {
                "mts" => scctcust::showVAMTS($item->nocust),
                "ma" => scctcust::showVAMA($item->nocust),
                default => "",
            };

            $item->item_id = $item['AA'];
            return $item;
        });

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
        ];
        return response()->json($response);
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
                            "tahun_akademik" => "scctbill.BTA",
                            "post" => "scctbill.BILLNM",
                            "unit" => "scctcust.CODE01",
                            "kelas" => "scctcust.DESC02",
                            "siswa" => "scctcust.nmcust",
                            "custid" => "scctbill.CUSTID",
                            "periode" => "scctbill.BILLAC",
                            default => null,
                        };

                        if ($key == "kelas") {
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
                                $colName &&
                                ($filters[] = [$colName, "in", $array]);
                            }
                            $post = $array;
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
                    //                    ->orderBy('urut', 'asc')
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
                        ->where("scctbill.PAIDST", 0)
                        ->where("scctbill.FSTSBolehBayar", 1)
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

                $zeroColumns = $zeroColumns->toArray(); // <-- always convert

                $filteredMstTagihan = $mstTagihan->reject(function ($value) use ($zeroColumns) {
                    return in_array($value, $zeroColumns, true);
                });

                //                $customPaper = [0, 0, 1684, 842];
                $customPaper = [0, 0, 935.43, 595.28];

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
                            "Tidak dapat mencetak rekap tagihan!<br> *Silahkan hubungi administrator",
                        "error" => $e,
                    ],
                    422,
                );
            }
        } else {
            return response()->json(
                [
                    "message" =>
                        "Tidak dapat mencetak rekap tagihan!<br> *Kelas Harus Diisi, silahkan pilih salah satu kelas",
                ],
                422,
            );
        }
    }
}
