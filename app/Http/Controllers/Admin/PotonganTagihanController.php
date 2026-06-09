<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\ScctbillCut;
use App\Models\scctcust;
use App\Models\ValidationMessage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class
PotonganTagihanController extends Controller
{
    public string $title;
    public string $mainTitle;
    public string $datasUrl;
    public string $columnsUrl;

    public function __construct()
    {
        $this->title = "Potongan Tagihan";
        $this->mainTitle = "Potongan Tagihan";
        $this->datasUrl = route("admin.potongan-tagihan.get-data");
        $this->columnsUrl = route("admin.potongan-tagihan.get-column");
    }

    public function index()
    {
        $data["title"] = $this->title;
        $data["mainTitle"] = "Data " . $this->mainTitle;
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

        return view("admin.potongan_tagihan.index", $data);
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
                "duplicate" => true
            ],
            [
                "data" => "nmcust",
                "name" => "NAMA",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => true
            ],
            [
                "data" => "CODE02",
                "name" => "Unit",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => true
            ],
            [
                "data" => "DESC02",
                "name" => "Kelas",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => true
            ],
            [
                "data" => "DESC03",
                "name" => "Kelompok",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => true
            ],
            [
                "data" => "BILLNM",
                "name" => "Nama Tagihan",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => true
            ],
            [
                "data" => "PAIDDT",
                "name" => "Tanggal Bayar",
                "columnType" => "timestamp",
                "searchable" => true,
                "orderable" => true,
                "exportable" => true,
                "duplicate" => true
            ],
            [
                "data" => "BILLAM",
                "name" => "Tagihan",
                "searchable" => true,
                "orderable" => true,
                "columnType" => "currency",
                "className" => "text-end",
                "exportable" => true,
                "duplicate" => true
            ],
            [
                "data" => "TOTAL_BILL_CUT",
                "name" => "Total Potongan",
                "columnType" => "currency",
                "className" => "text-end",
                "exportable" => true,
            ],
            [
                "data" => "BILL_CUT_LISTS",
                "name" => "Detail",
                "columnType" => "array",
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
                        "tanggal-transaksi" => "scctbill.PAIDDT",
                        "tahun_akademik" => "scctbill.BTA",
                        "post" => "scctbill.BILLNM",
                        "tanggal-potongan" => "scctbill_cut.CUT_DATE",
                        "kelas" => "scctcust.DESC02",
                        "siswa" => "scctcust.nmcust",
                        "angkatan" => "scctcust.DESC04",
                        "custid" => "scctbill.CUSTID",
                        "periode" => "scctbill.BILLAC",
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
                    } elseif ($key == "tanggal-potongan") {
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

        $whereAny = ["scctcust.nmcust", "scctcust.nocust", "scctbill_cut.REASON"];

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
                "scctbill_cut.BILL_CUT"
            ]),
        );

        $query = ScctbillCut::
        leftJoin("scctbill", "scctbill_cut.AA", "scctbill.AA")
            ->leftJoin(
                "scctcust",
                "scctcust.CUSTID",
                "scctbill.CUSTID",
            )
            ->where("scctbill.PAIDST", 1)
            ->where("scctbill.FSTSBolehBayar", 1)
            ->where("scctcust.STCUST", 1)
            ->when(!blank($searchValue), function ($query) use ($whereAny, $searchValue) {
                $query->where(function ($q) use ($whereAny, $searchValue) {
                    $sanitizeSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchValue);
                    foreach ($whereAny as $column) {
                        $q->orWhere($column, 'like', '%' . $sanitizeSearch . '%');
                    }
                });
            })->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            });

        //other join leftJoin('scctbill', function ($join) {
        //            $join->on('scctbill_cut.AA', '=', "scctbill.AA")
        //                ->where("scctbill.PAIDST", 1)
        //                ->where("scctbill.FSTSBolehBayar", 1);
        //        })
        //            ->leftJoin(
        //                "scctcust", function ($join) {
        //                $join->on("scctbill.CUSTID", "=", "scctcust.CUSTID")
        //                    ->where("scctcust.STCUST", 1);
        //            })

        $totalRecords = ScctbillCut::distinct(['scctbill_cut.AA'])->count('scctbill_cut.AA');

        $totalRecordswithFilter = (clone $query)->distinct(['scctbill_cut.AA'])->count('scctbill_cut.AA');

        $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
        $records = (clone $query)
            ->groupBy('scctbill_cut.AA')
            ->orderBy('scctcust.nmcust', 'asc')
            ->orderByRaw("
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
            ")
            ->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->addSelect(DB::raw("COALESCE(SUM(scctbill_cut.BILL_CUT), 0) as TOTAL_BILL_CUT"))
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $records = $records->map(function ($item, $index) use ($request) {
            $item->NOVA = match (strtolower($item->CODE02)) {
                "mts" => scctcust::showVAMTS($item->nocust),
                "ma" => scctcust::showVAMA($item->nocust),
                default => "",
            };

            $billCutQuery = ScctbillCut::select(['ID', 'CUT_DATE', 'BILL_CUT', 'REASON'])
                ->where('AA', $item->AA)
                ->orderBy('CUT_DATE', 'ASC')
                ->get();

            $item->BILL_CUT_LISTS_RAW = $billCutQuery;

            $cutLists = $billCutQuery
                ->map(function ($row) {
                    $date = '';
                    if ($row->CUT_DATE) {
                        $date = \Carbon\Carbon::parse($row->CUT_DATE)
                            ->translatedFormat('d F Y');
                    }

                    $bill = 'Rp ' . number_format($row->BILL_CUT, 0, ',', '.');

                    return "{$date} | {$bill} | {$row->REASON}";
                });

            $item->BILL_CUT_LISTS = $cutLists;

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

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "item_id" => ["required", "array", "min:1"],
                "potongan" => ["required", "array", "min:1"],
                "tanggal" => ["required", "array", "min:1"],
                "potongan.*" => [
                    "nullable",
                    "regex:/^[0-9]+(\.[0-9]{3})*$/"
                ],
                "tanggal.*" => [
                    "nullable",
                    "regex:/^\d{2}-\d{2}-\d{4}$/"
                ],
                "deskripsi" => ["nullable", "array"],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes(),
        );

        if ($validator->fails()) {
            $message = $validator->errors()->first();
            if ($validator->errors()->count() > 1) {
                $message = "{$message} Dan beberapa masalah validasi lainnya, silahkan periksa form anda!";
            }
            return response()->json(
                [
                    "message" => $message,
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        $bill = scctbill::where('PAIDST', 1)
            ->whereIn("AA", $request->item_id)->get();
        if (!$bill) {
            return response()->json(["message" => "Tagihan yang dipilih tidak valid!"], 422);
        }

//        $total = array_sum(
//            array_map(function ($value) {
//                if (!$value) {
//                    return 0;
//                }
//                $clean = str_replace('.', '', $value);
//
//                return (int)$clean;
//            }, $request['potongan'])
//        );
//
//        if ($bill->BILLAM < $total) {
//            $tagihan = 'Rp. ' . number_format($bill->BILLAM, 0, ',', '.');
//            $potongan = 'Rp. ' . number_format($total, 0, ',', '.');
//            return response()->json(["message" => "Total potongan tidak boleh lebih besar dari tagihan! <br> Tagihan : $tagihan <br> Potongan: $potongan"], 422);
//        }

        foreach ($request->potongan as $id => $value) {
            $nominal = str_replace('.', '', $value);
            $tanggal = $request->tanggal[$id] ?? null;

            $hasPotongan = !empty($nominal) && $nominal > 0;
            $hasTanggal = !empty($tanggal);

            if ($hasPotongan xor $hasTanggal) {
                return response()->json([
                    "message" => "Potongan dan tanggal harus diisi, cek potongan baris ke-" . ($id + 1)
                ], 422);
            }
        }

        try {
            DB::beginTransaction();
            foreach ($bill as $item) {
                foreach ($request->potongan as $id => $value) {
                    $nominal = str_replace('.', '', $value);
                    if ($nominal > 0) {
                        $tanggal = Carbon::createFromFormat(
                            "d-m-Y",
                            $request->tanggal[$id]);
                        ScctbillCut::create([
                            'AA' => $item->AA,
                            'BILLNM' => $item->BILLNM,
                            'BTA' => $item->BTA,
                            'BILLCD' => $item->BILLCD,
                            'BILLAM' => $item->BILLAM,
                            'BILL_CUT' => $nominal,
                            'CUT_DATE' => $tanggal,
                            'REASON' => $request->deskripsi[$id] ?? null,
                            'CREATED_AT' => now(),
                            'USER_ID' => Auth::user()->id
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(["message" => "Data potongan disimpan!"], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["message" => "Gagal menyimpan potongan tagihan", "error" => $e->getMessage()], 422);
        }
    }

    public function create()
    {
        $data["title"] = $this->title;
        $data["mainTitle"] = "Buat " . $this->mainTitle;
        $data["columnsUrl"] = route("admin.data-penerimaan.get-column");
        $data["datasUrl"] = route("admin.data-penerimaan.get-data");
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

        return view("admin.potongan_tagihan.create", $data);
    }

    public function cetakKuitansi(Request $request)
    {
        $filter = $request;
        if (!$filter['AA']) return response()->json(['error' => 'Tagihan Tidak Ditemukan!']);
        $filter['draw'] = 2;
        $filter['start'] = 0;
        $filter['length'] = "poll";

        $tagihan = ScctbillCut::distinct('AA')
            ->where('AA', $filter['AA'])
            ->first();
        if (!$tagihan) return response()->json(['error' => 'Tagihan Tidak Ditemukan!']);

        try {
            $tagihans = json_decode(json_encode($tagihan), true);
            $tagihans = $tagihans['original']['data'];
            if (!$tagihans) return response()->json(['message' => 'Tagihan Tidak Ditemukan!'], 422);
            return response()->json(['tagihans' => $tagihans]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Tagihan Tidak Ditemukan!'], 422);
        }
    }

    public function destroy($id)
    {
        $bill = scctbill::where('AA', $id)->first();
        if (!$bill) {
            return response()->json(['message' => 'tagihan tidak ditemukan'], 422);
        }

        try {
            DB::beginTransaction();
            ScctBillCut::where('AA', $id)->delete();
            DB::commit();

            return response()->json(["message" => "Data potongan tagihan dihapus!"], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Potongan tagihan gagal dihapus!'], 422);
        }
    }
}
