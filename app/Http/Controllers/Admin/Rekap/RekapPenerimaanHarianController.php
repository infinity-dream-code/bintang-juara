<?php

namespace App\Http\Controllers\Admin\Rekap;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctcust;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RekapPenerimaanHarianController extends Controller
{
    public function __construct()
    {
        $this->title = 'Rekap Penerimaan Harian';

        $this->datasUrl = route('admin.rekap-penerimaan-harian.get-data');
        $this->detailDatasUrl = '';
        $this->columnsUrl = route('admin.rekap-penerimaan-harian.get-column');
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['columnsUrl'] = $this->columnsUrl;
        $data['datasUrl'] = $this->datasUrl;
        $data['post'] = mst_tagihan::select(['tagihan'])->orderByRaw("
                        CASE
                            WHEN kode BETWEEN '07' AND '12' THEN 0
                            WHEN kode BETWEEN '01' AND '06' THEN 1
                            ELSE 2
                        END,
                        kode ASC
                    ")->get();
        $data['thn_aka'] = mst_thn_aka::select(['thn_aka'])->where('thn_aka', '!=', null)->get();
        $data['kelas'] = mst_kelas::get();
        $data['unit'] = mst_sekolah::get();

        return view('admin.rekap_penerimaan_harian', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => 'CUSTID', 'name' => 'no', 'columnType' => 'row'],
//            ['data' => 'item_id', 'name' => 'ITEM ID', 'visible' => false],
            ['data' => 'nocust', 'name' => 'NIS', 'searchable' => true, 'orderable' => true],
            ['data' => 'nmcust', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true],
            ['data' => 'CODE02', 'name' => 'Unit', 'searchable' => true, 'orderable' => true],
            ['data' => 'DESC02', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true],
            ['data' => 'DESC03', 'name' => 'Jenjang', 'searchable' => true, 'orderable' => true],
            ['data' => 'transaksi', 'name' => 'Penerimaan CASH', 'searchable' => false, 'orderable' => false, 'columnType' => 'currency', 'className' => 'text-end'],
            ['data' => 'transaksi_va', 'name' => 'Penerimaan VA', 'searchable' => false, 'orderable' => false, 'columnType' => 'currency', 'className' => 'text-end'],
            ['data' => 'total_transaksi_siswa', 'name' => 'Total', 'searchable' => false, 'orderable' => false, 'columnType' => 'currency', 'className' => 'text-end'],
        ];
    }

    public function cetakRekapPenerimaanHarian(Request $request)
    {
        if (!isset($request->filter['dari_tanggal']) ||
            $request->filter['dari_tanggal'] == null
            || !preg_match('/^\d{2}-\d{2}-\d{4}$/', $request->filter['dari_tanggal'])
        ) {
            return response()->json(['message' => 'Tidak dapat mencetak rekap penerimaan!<br> <span class="text-danger">*</span>Tanggal transaksi pembayaran tidak boleh kosong'], 422);
        } else if ((
                !isset($request->filter['unit']) ||
                $request->filter['unit'] == null ||
                $request->filter['unit'] == 'all') &&
            (!isset($request->filter['kelas']) ||
                $request->filter['kelas'] == null ||
                $request->filter['kelas'] == 'all')) {
            return response()->json(['message' => 'Tidak dapat mencetak rekap penerimaan!<br> * Tingkat atau Kelas Harus Diisi, silahkan pilih salah satu tingkat atau kelas' . $request->filter['unit']], 422);
        }

        $request['draw'] = 2;
        $request['start'] = 0;
        $request['length'] = "poll";

        $filter = $request;
        $results = $this->getData($filter);
        $results = json_decode(json_encode($results), true);

        return response()->json($results['original'], 200);
    }

    public function getData(Request $request)
    {
        $draw = $request->get('draw');
        if (isset($request->filter['dari_tanggal']) && $request->filter['dari_tanggal'] != null && preg_match('/^\d{2}-\d{2}-\d{4}$/', $request->filter['dari_tanggal'])) {

            $start = $request->get("start");
            $rowperpage = $request->get("length");

            $columnIndex_arr = $request->get('order', []);
            $columnName_arr = $request->get('columns', []);
            $order_arr = $request->get('order', []);
            $search_arr = $request->get('search', []);
            $searchValue = $search_arr['value'] ?? '';

            $columnName = 'scctcust.nmcust';
            $columnSortOrder = 'DESC';

            if (!empty($order_arr)) {
                $columnIndex = $columnIndex_arr[0]['column'] ?? null;
                if ($columnIndex !== null && !empty($columnName_arr[$columnIndex]['data']) && $columnName_arr[$columnIndex]['data'] !== 'no') {
                    $columnName = $columnName_arr[$columnIndex]['data'];
                    $columnSortOrder = $order_arr[0]['dir'] ?? 'desc';
                }
            }

            $filters = [];
            $filterQuery = null;

            $filter = $request->input('filter');
            if ($filter) {
                foreach ($filter as $key => $val) {
                    if (is_array($val) || strtolower($val) != 'all' && $val !== null && $val !== '') {
                        $colName = match ($key) {
                            'sampai_tanggal' => 'scctbill.FTGLTagihan',
                            'dari_tanggal', 'tanggal-transaksi' => 'scctbill.PAIDDT',
                            'tahun_akademik' => 'scctbill.BTA',
                            'post' => 'scctbill.BILLNM',
                            'unit' => 'scctcust.CODE01',
                            'kelas' => 'scctcust.DESC02',
                            'siswa' => 'scctcust.nmcust',
                            'custid' => 'scctbill.CUSTID',
                            default => null
                        };
                        if ($key == 'tanggal-transaksi') {
                            if (preg_match('/^\d{2}-\d{2}-\d{4} [-\/~] \d{2}-\d{2}-\d{4}$/', $val)) {
                                $val = preg_replace('/[-\/~]/', '-', $val);

                                list($startDate, $endDate) = explode(' - ', $val);
                                $startDate = Carbon::createFromFormat('d-m-Y', $startDate)->startOfDay();
                                $endDate = Carbon::createFromFormat('d-m-Y', $endDate)->endOfDay();
                                if ($startDate && $endDate) {
                                    ($colName) && $filters[] = [$colName, $startDate, $endDate, 'whereBetween'];
                                }
                            }
                        } else if ($key == 'dari_tanggal') {
                            $val = preg_replace('/[-\/~]/', '-', $val);
                            $startDate = Carbon::createFromFormat('d-m-Y', $val)->toDateString();
                            ($colName) && $filters[] = [$colName, 'date', $startDate];
                            ($colName) && $filters[] = ['sccttran.TRXDATE', 'date', $startDate];
                        } else if ($key == 'kelas') {
                            $val = explode("~", $val);
                            if (count($val) == 3) {
                                $filters[] = ['scctcust.CODE02', '=', $val[0]];
                                $filters[] = ['scctcust.DESC02', '=', $val[1]];
                                $filters[] = ['scctcust.DESC03', '=', $val[2]];
                            }
                        } else if ($key == 'post') {
                            $array = array_filter($val, function ($value) {
                                return $value !== 'all';
                            });
                            if (count($array) > 0) {
                                ($colName) && $filters[] = [$colName, 'in', $array];
                            }
                        } elseif ($key == 'siswa') {
                            $val = is_numeric($val) ? $val : '%' . $val . '%';
                            $colName = is_numeric($val) ? 'scctcust.nocust' : $colName;
                            ($colName) && $filters[] = [$colName, 'like', $val];
                        } else {
                            ($colName) && $filters[] = [$colName, '=', $val];
                        }
                    }
                };

                if (!empty($filters)) {
                    $filterQuery = function ($query) use ($filters) {
                        foreach ($filters as $filter) {
                            switch (count($filter)) {
                                case 3:
                                    if ($filter[1] === 'in') {
                                        $query->whereIn($filter[0], $filter[2]);
                                    } else if ($filter[1] === 'date') {
                                        $query->whereDate($filter[0], '=', $filter[2]);
                                    } else {
                                        $query->where($filter[0], $filter[1], $filter[2]);
                                    }
                                    break;

                                case 4:
                                    $filter[3] === 'whereBetween'
                                        ? $query->whereBetween($filter[0], [$filter[1], $filter[2]])
                                        : $query->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                                    break;
                            }
                        }
                    };
                }
            }

            $filter_main = [];
            $filter_scctbill = [];
            $filter_sccttran = [];

            foreach ($filters as $item) {
                if (str_contains($item[0], "scctbill")) {
                    $filter_scctbill[] = $item;
                } else if (str_contains($item[0], "scctcust")) {
                    $filter_main[] = $item;
                } else {
                    $filter_sccttran[] = $item;
                }
            }

            $whereAny = [
                'scctcust.nmcust',
                'scctcust.nocust',
            ];

            $select = array_unique(array_merge($whereAny, [
                'scctcust.CODE02',
                'scctcust.DESC02',
                'scctcust.DESC03',
                'scctcust.CUSTID',
            ]));

            $query = DB::connection('DATA_MYSQL')->table('scctcust')->where(function ($query) use ($filter_main) {
                foreach ($filter_main as $filter) {
                    switch (count($filter)) {
                        case 3:
                            if ($filter[1] === 'in') {
                                $query->whereIn($filter[0], $filter[2]);
                            } else if ($filter[1] === 'date') {
                                $query->whereDate($filter[0], '=', $filter[2]);
                            } else {
                                $query->where($filter[0], $filter[1], $filter[2]);
                            }
                            break;

                        case 4:
                            $filter[3] === 'whereBetween'
                                ? $query->whereBetween($filter[0], [$filter[1], $filter[2]])
                                : $query->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                            break;
                    }
                }
            })->where('scctcust.STCUST', 1);
//                ->groupBy('scctcust.CUSTID');

            // Total records
            $totalRecords = scctcust::select('count(*) as allcount')
                ->where('scctcust.STCUST', 1)
                ->count();

//            $totalTagihan = (clone $query)->sum('BILLAM');

            $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;

            $billAgg = DB::connection('DATA_MYSQL')->table('scctbill')
                ->select('scctbill.CUSTID', DB::raw('SUM(scctbill.BILLAM) AS transaksi'))
                ->where('scctbill.PAIDST', 1)
                ->where('scctbill.FSTSBolehBayar', 1)
                ->whereNotNull('scctbill.PAIDDT')
                ->whereIn('scctbill.FIDBANK', ['1140000', '1140001', '1140003'])
                ->when(!empty($filter_scctbill), function ($q) use ($filter_scctbill) {
                    foreach ($filter_scctbill as $filter) {
                        switch (count($filter)) {
                            case 3:
                                if ($filter[1] === 'in') {
                                    $q->whereIn($filter[0], $filter[2]);
                                } elseif ($filter[1] === 'date') {
                                    $q->whereDate($filter[0], '=', $filter[2]);
                                } else {
                                    $q->where($filter[0], $filter[1], $filter[2]);
                                }
                                break;

                            case 4:
                                if ($filter[3] === 'whereBetween') {
                                    $q->whereBetween($filter[0], [$filter[1], $filter[2]]);
                                } else {
                                    $q->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                                }
                                break;
                        }
                    }
                })
                ->groupBy('scctbill.CUSTID');

            $tranAgg = DB::connection('DATA_MYSQL')->table('sccttran')
                ->select('sccttran.CUSTID', DB::raw('SUM(sccttran.KREDIT) AS transaksi_va'))
                ->whereNull('sccttran.FIDBANK')
                ->whereIn('sccttran.REFFBANK', ['23','63'])
                ->when(!empty($filter_sccttran), function ($q) use ($filter_sccttran) {
                    foreach ($filter_sccttran as $filter) {
                        switch (count($filter)) {
                            case 3:
                                if ($filter[1] === 'in') {
                                    $q->whereIn($filter[0], $filter[2]);
                                } elseif ($filter[1] === 'date') {
                                    $q->whereDate($filter[0], '=', $filter[2]);
                                } else {
                                    $q->where($filter[0], $filter[1], $filter[2]);
                                }
                                break;

                            case 4:
                                if ($filter[3] === 'whereBetween') {
                                    $q->whereBetween($filter[0], [$filter[1], $filter[2]]);
                                } else {
                                    $q->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                                }
                                break;
                        }
                    }
                })
                ->groupBy('sccttran.CUSTID');

            $query->select(array_merge($select, [
                DB::raw('COALESCE(bill.transaksi, 0) AS transaksi'),
                DB::raw('COALESCE(tran.transaksi_va, 0) AS transaksi_va'),
                DB::raw('COALESCE(bill.transaksi, 0) + COALESCE(tran.transaksi_va, 0) AS total_transaksi_siswa'),
            ]))->leftJoinSub($billAgg, 'bill', function ($join) {
                $join->on('bill.CUSTID', '=', 'scctcust.CUSTID');
            })->leftJoinSub($tranAgg, 'tran', function ($join) {
                $join->on('tran.CUSTID', '=', 'scctcust.CUSTID');
            })->where(function ($q) {
                $q->where('bill.transaksi', '>', 0)
                    ->orWhere('tran.transaksi_va', '>', 0);
            });

            $sub = $query;
            $totals = DB::connection('DATA_MYSQL')->table(DB::raw("({$sub->toSql()}) as sub"))
                ->mergeBindings($sub)
                ->selectRaw('SUM(transaksi) as total_transaksi, SUM(transaksi_va) as total_transaksi_va')
                ->first();

            $totalTransaksi = $totals->total_transaksi;
            $totalTransaksiVa = $totals->total_transaksi_va;

            $totalRecordswithFilter = (clone $query)
                ->select('count(*) as allcount')
                ->whereAny($whereAny, 'like', '%' . $searchValue . '%')
                ->count();

            $records = (clone $query)
                ->orderBy($columnName, $columnSortOrder)
                ->whereAny($whereAny, 'like', '%' . $searchValue . '%')
                ->skip($start)
                ->take($rowperpage)
                ->get();

            if ($request->get("length") != "poll") {
                $records = $records->map(function ($item, $index) {
//                    $item->item_id = Crypt::encrypt($item['AA']);
                    $item->CUSTID = Crypt::encrypt($item->CUSTID);
                    return $item;
                });
            }

            $records->toArray();
        }

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
//            "recordsFiltered" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
            'totals' => [
                'transaksi' => ['location' => 6, 'value' => $totalTransaksi ?? 0, 'columnType' => 'currency'],
                'transaksi_va' => ['location' => 7, 'value' => $totalTransaksiVa ?? 0, 'columnType' => 'currency'],
                'total_transaksi_siswa' => ['location' => 8, 'value' => ($totalTransaksiVa ?? 0) + ($totalTransaksi ?? 0), 'columnType' => 'currency'],
            ]
        );
        return response()->json($response);
    }
}
