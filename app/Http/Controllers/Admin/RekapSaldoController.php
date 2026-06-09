<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use App\Models\sccttran;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapSaldoController extends Controller
{
    private string $title;
    private string $datasUrl;
    private string $columnsUrl;
    private string $mainTitle;

    public function __construct()
    {
        $this->title = "Saldo Per Periode";
        $this->mainTitle = "Saldo Per Periode";
        $this->datasUrl = route("admin.rekap-saldo.get-data");
        $this->columnsUrl = route("admin.rekap-saldo.get-column");
    }

    public function index()
    {
        $data["title"] = $this->title;
        $data["mainTitle"] = $this->mainTitle;
        $data["columnsUrl"] = $this->columnsUrl;
        $data["datasUrl"] = $this->datasUrl;
        $data["thn_aka"] = mst_thn_aka::select(["thn_aka"])
            ->where("thn_aka", "!=", null)
            ->orderBy("thn_aka", "desc")
            ->get();
        $data["kelas"] = mst_kelas::get();
        $data['unit'] = mst_sekolah::get();

        return view("admin.rekap_saldo.index", $data);
    }

    public function getColumn()
    {
        return [
            [
                "data" => null,
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
                "data" => "opening_balance",
                "name" => "Saldo Awal",
                "exportable" => true,
                "columnType" => "currency",
            ],
            [
                "data" => "current_net",
                "name" => "Saldo Periode Dipilih",
                "exportable" => true,
                "columnType" => "currency",
            ],
            [
                "data" => "closing_balance",
                "name" => "Saldo AKhir",
                "exportable" => true,
                "columnType" => "currency",
            ],
        ];
    }

    public function getDataRekap(Request $request)
    {
        $periode = $request->filter["periode"] ?? null;
        $unit = $request->filter["unit"] ?? null;
        $kelas = $request->filter["kelas"] ?? null;

        if ($periode == null ||
            !preg_match('/^\d{6}$/', $periode) ||
            !checkdate(substr($periode, 4, 2), 1, substr($periode, 0, 4))
        ) {
            return response()->json([
                "error" => "Periode is not valid or defined!",
                "message" => "Silahkan pilih periode terlebih dahulu"
            ], 422);
        }

        $kelas = strtolower(trim($kelas ?? ''));
        $unit  = strtolower(trim($unit ?? ''));

        if (($kelas === '' || $kelas === 'all') && ($unit === '' || $unit === 'all')) {
            return response()->json([
                "error" => "Filter tidak valid!",
                "message" => "Silahkan pilih satu Unit atau satu kelas!"
            ], 422);
        }

        $request["draw"] = 2;
        $request["start"] = 0;
        $request["length"] = "poll";

        try {
            $filter = $request;
            $saldos = $this->getData($filter);

            $saldos = json_decode(json_encode($saldos), true);
            $saldos = $saldos["original"]["data"];
            if (!$saldos) {
                return response()->json(
                    ["message" => "Data saldo kosong"],
                    422,
                );
            }

            return response()->json([
                "data" => $saldos,
            ], 200);
        } catch
        (\Throwable $e) {
            return response()->json(
                [
                    "message" => "Data Saldo Tidak Ditemukan",
                    "error" => $e->getMessage(),
                ],
                422,
            );
        }
    }

    public function getData(Request $request)
    {
        $draw = $request->get("draw");
        if (
            $request->filter["periode"] != null &&
            preg_match(
                '/^\d{6}$/',
                $request->filter["periode"],
            )
        ) {
            $start = $request->get("start");
            $rowperpage = $request->get("length");

            $columnIndex_arr = $request->get("order", []);
            $columnName_arr = $request->get("columns", []);
            $order_arr = $request->get("order", []);
            $search_arr = $request->get("search", []);
            $searchValue = $search_arr["value"] ?? "";

            $columnName = "scctcust.nmcust";
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
                            "unit" => "scctcust.CODE01",
                            "kelas" => "scctcust.DESC02",
                            "siswa" => "scctcust.nmcust",
                            default => null,
                        };
                        if ($key == "kelas") {
                            $val = explode("~", $val);
                            if (count($val) == 3) {
                                $filters[] = ["scctcust.CODE02", "=", $val[0]];
                                $filters[] = ["scctcust.DESC02", "=", $val[1]];
                                $filters[] = ["scctcust.DESC03", "=", $val[2]];
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
                    "scctcust.CODE02",
                    "scctcust.DESC02",
                    "scctcust.CUSTID",
                ]),
            );

            $periode = $request->filter["periode"];
            $monthStart = Carbon::createFromFormat('Ym', $periode)->startOfMonth();
            $monthEnd = Carbon::createFromFormat('Ym', $periode)->endOfMonth();

            $query = scctcust::where("scctcust.STCUST", 1
            )->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            });

            // Total records
            $totalRecords = scctcust::select("count(*) as allcount")
                ->where("scctcust.STCUST", 1)
                ->count();

            $totalRecordswithFilter = (clone $query)->select("count(*) as allcount")->count();

            $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
            $records = (clone $query)
                ->orderBy($columnName, $columnSortOrder)
                ->select($select)
                ->addSelect([
                    'OPENING_KREDIT' => sccttran::whereColumn('sccttran.CUSTID', 'scctcust.CUSTID')
                        ->where('sccttran.TRXDATE', '<', $monthStart)
                        ->selectRaw('COALESCE(SUM(sccttran.KREDIT), 0) as OPENING_KREDIT'),
                    'OPENING_DEBET' => sccttran::whereColumn('sccttran.CUSTID', 'scctcust.CUSTID')
                        ->where('sccttran.TRXDATE', '<', $monthStart)
                        ->selectRaw('COALESCE(SUM(sccttran.DEBET), 0) as OPENING_DEBET'),
                    'KREDIT_BULAN' => sccttran::whereColumn('sccttran.CUSTID', 'scctcust.CUSTID')
                        ->whereBetween('TRXDATE', [$monthStart, $monthEnd])
                        ->selectRaw('COALESCE(SUM(sccttran.KREDIT), 0) as KREDIT_BULAN'),
                    'DEBET_BULAN' => sccttran::whereColumn('sccttran.CUSTID', 'scctcust.CUSTID')
                        ->whereBetween('TRXDATE', [$monthStart, $monthEnd])
                        ->selectRaw('COALESCE(SUM(sccttran.DEBET), 0) as DEBET_BULAN')
                ])
                ->when(!blank($searchValue), function ($query) use ($whereAny, $searchValue) {
                    $query->where(function ($q) use ($whereAny, $searchValue) {
                        $sanitizeSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchValue);
                        foreach ($whereAny as $column) {
                            $q->orWhere($column, 'like', '%' . $sanitizeSearch . '%');
                        }
                    });
                })
                ->skip($start)
                ->take($rowperpage)
                ->get();

            $records = $records->map(function ($item, $index) use ($request, $monthStart, $monthEnd) {
                $item->NOVA = match (strtolower($item->CODE02)) {
                    "mts" => scctcust::showVAMTS($item->nocust),
                    "ma" => scctcust::showVAMA($item->nocust),
                    default => "",
                };

                $item['opening_balance'] = $item['OPENING_KREDIT'] - $item['OPENING_DEBET'];
                $item['current_net'] = $item['KREDIT_BULAN'] - $item['DEBET_BULAN'];
                $item['closing_balance'] = $item['opening_balance'] + $item['current_net'];

                $item->item_id = $item["CUSTID"];

                return $item;
            });

            $records->toArray();
        }

//        $tran = sccttran::where('TRXDATE', '>=', $monthStart)->where('TRXDATE', '<=', $monthEnd)->limit(10)->get();

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
            "tran" => $tran ?? [],
        ];
        return response()->json($response);
    }
}
