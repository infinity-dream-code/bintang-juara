<?php

namespace App\Http\Controllers\Admin\CekPelunasan;

use App\Http\Controllers\Controller;
use App\Models\MetodeBayar;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapCekPelunasanController extends Controller
{
    private string $title;
    private string $datasUrl;
    private string $columnsUrl;
    private string $mainTitle;

    public function __construct()
    {
        $this->title = "Rekap Data";
        $this->mainTitle = "Rekap Cek Pelunasan";
        $this->datasUrl = route("admin.rekap-cek-pelunasan.get-data");
        $this->columnsUrl = route("admin.rekap-cek-pelunasan.get-column");
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
        $data["unit"] = mst_sekolah::get();
        $data["metode_bayar"] = MetodeBayar::attributes();
        $data["kelas"] = mst_kelas::get();

        return view("admin.rekap_cek_pelunasan.index", $data);
    }

    public function getColumn()
    {
        return [
            ['data' => 'CUSTID', 'name' => 'no', 'columnType' => 'row'],
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
                "data" => "status_kelunasan",
                "name" => "Status Kelunasan",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                'columnType' => 'boolean',
                'trueVal' => 'LUNAS',
                'falseVal' => 'BELUM LUNAS'
            ],
            [
                "data" => "BILL_TOTAL",
                "name" => "SISA TAGIHAN",
                'columnType' => 'currency'
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
        $filterQuery = [];
        $filtersiswa = [];
        $filtersiswaQuery = [];

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
                                $startDate = \Illuminate\Support\Carbon::createFromFormat(
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

                $filtersiswa = array_values(array_filter($filters, function ($item) {
                    return isset($item[0]) && is_string($item[0]) && str_contains($item[0], 'scctcust.');
                }));

                $filtersiswaQuery = null;
                if (!empty($filtersiswa)) {
                    $filtersiswaQuery = function ($query) use ($filtersiswa) {
                        foreach ($filtersiswa as $filter) {
                            switch (count($filter)) {
                                case 3:
                                    $filter[1] === "in"
                                        ? $query->whereIn($filter[0], $filter[2])
                                        : $query->where($filter[0], $filter[1], $filter[2]);
                                    break;

                                case 4:
                                    $filter[3] === "whereBetween"
                                        ? $query->whereBetween($filter[0], [$filter[1], $filter[2]])
                                        : $query->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                                    break;
                            }
                        }
                    };
                }
            }
        }

        $whereAny = ["scctcust.nmcust", "scctcust.nocust"];

        $select = array_unique(
            array_merge($whereAny, [
                "scctcust.CUSTID",
                "scctcust.CODE02",
                "scctcust.DESC02",
                "scctcust.DESC03",
            ]),
        );

        $totalRecords = scctcust::select("count(*) as allcount")
            ->where("scctcust.STCUST", 1)
            ->count();

        $totalRecordswithFilter = scctcust::where("scctcust.STCUST", 1)
            ->where(function ($query) use ($filtersiswaQuery) {
                if ($filtersiswaQuery){
                    $filtersiswaQuery($query);
                }
            })
            ->count();

        $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
        $records = scctcust::leftJoin(
            "scctbill",
            "scctcust.CUSTID",
            "scctbill.CUSTID",
        )
            ->where("scctbill.FSTSBolehBayar", 1)
            ->where("scctcust.STCUST", 1)
            ->whereAny($whereAny, "like", "%" . $searchValue . "%")
            ->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            })
            ->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->addSelect(DB::raw('COALESCE(MIN(scctbill.PAIDST), 0) as status_kelunasan'))
            ->addSelect(
                DB::raw(
                    "SUM(CASE WHEN scctbill.PAIDST = 0 THEN scctbill.BILLAM ELSE 0 END) AS 'BILL_TOTAL'",
                ),
            )
            ->groupBy("scctcust.CUSTID")
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $records = $records->map(function ($item, $index) use ($request) {
            $item->NOVA = match (strtolower($item->CODE02)) {
                "mts" => scctcust::showVAMTS($item->nocust),
                "ma" => scctcust::showVAMA($item->nocust),
                default => "",
            };

            $item->item_id = $item["CUSTID"];

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
}
