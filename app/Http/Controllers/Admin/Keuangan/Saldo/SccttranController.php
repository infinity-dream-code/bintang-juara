<?php

namespace App\Http\Controllers\Admin\Keuangan\Saldo;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_thn_aka;
use App\Models\scctcust;
use App\Models\sccttran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SccttranController extends Controller
{
    public function __construct()
    {
//        $this->middleware('CheckUserRoleOrPermission:pimpinan');

        $this->title = 'Keuangan';
        $this->mainTitle = 'Data Transfer VA';
        $this->dataTitle = 'Data Transfer VA';
        $this->datasUrl = route('admin.keuangan.data-transfer-va.get-data');
        $this->columnsUrl = route('admin.keuangan.data-transfer-va.get-column');
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = $this->columnsUrl;
        $data['datasUrl'] = $this->datasUrl;
        $data['thn_aka'] = mst_thn_aka::select(['thn_aka'])
            ->whereNotNull('thn_aka')
            ->distinct()
            ->orderBy('thn_aka', 'desc')
            ->get();
        $data['sekolah'] = mst_sekolah::select(['CODE01', 'DESC01'])->orderBy('DESC01')->get();
        $data['kelas'] = mst_kelas::get();

        return view('admin.keuangan.saldo.sccttran.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'columnType' => 'row', 'name' => 'No', 'exportable' => true],
            ['data' => 'NOCUST', 'name' => 'NIS', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'NOVA', 'name' => 'NO VA', 'exportable' => true],
            ['data' => 'NMCUST', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'METODE', 'name' => 'Metode', 'orderable' => true, 'exportable' => true],
            ['data' => 'TRXDATE', 'name' => 'Tanggal Transaksi', 'orderable' => true, 'columnType' => 'timestamp', 'exportable' => true],
            ['data' => 'DEBET', 'name' => 'Debet', 'orderable' => true, "className" => "dt-right", 'columnType' => 'currency', 'exportable' => true],
            ['data' => 'KREDIT', 'name' => 'Kredit', 'orderable' => true, "className" => "dt-right", 'columnType' => 'currency', 'exportable' => true],
        ];
    }

    public function getData(Request $request)
    {
        $custid = $request->CUSTID;
        $filters = [];
        $filterQuery = null;

        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length");

        $columnName_arr = $request->get('columns');
        $search_arr = $request->get('search');

        $defaultColumn =  'sccttran.TRXDATE';
        $defaultOrder = 'asc';

        if ($request->has('order')) {
            $columnIndex_arr = $request->get('order');
            $columnIndex = $columnIndex_arr[0]['column'];
            $columnSortOrder = $columnIndex_arr[0]['dir'];

        } else {
            $columnIndex = $defaultColumn;
            $columnSortOrder = $defaultOrder;
        }

        $columnName = $columnName_arr[$columnIndex]['data'];
        $searchValue = $search_arr['value'];

        if (!$columnName || $columnName == 'no') {
            $columnName = $defaultColumn;
            $columnSortOrder = $defaultOrder;
        }

        $filter = $request->input('filter');
        if ($filter) {
            foreach ($filter as $key => $val) {
                if (strtolower($val) != 'all' && $val !== null && $val !== '') {
                    $colName = match ($key) {
                        'dari_tanggal', 'sampai_tanggal' => 'sccttran.TRXDATE',
                        'kelas' => 'scctcust.CODE03',
                        'nama' => 'scctcust.NMCUST',
                        'nis' => 'scctcust.NOCUST',
                        'sekolah' => 'scctcust.CODE02',
                        'angkatan' => 'scctcust.DESC04',
                        default => null
                    };
                    if (in_array($key, ['dari_tanggal', 'sampai_tanggal']) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $val)) {
                        if ($key == 'dari_tanggal'){
                            $date = Carbon::createFromFormat('d-m-Y', $val)->startOfDay();
                        }else{
                            $date = Carbon::createFromFormat('d-m-Y', $val)->endOfDay();
                        }

                        if ($date && $colName) {
                            $operator = $key === 'dari_tanggal' ? '>=' : '<=';
                            $filters[] = [$colName, $operator, $date];
                        }
                    } elseif (in_array($key, ['nama', 'nis'])) {
                        ($colName) && $filters[] = [$colName, 'like', '%' . $val . '%'];
                    } else if ($key === 'sekolah') {
                        ($colName) && $filters[] = [$colName, '=', $val];
                    } else {
                        ($colName) && $filters[] = [$colName, '=', $val];
                    }
                }
            };
        }

        ($custid) && $filters[] = ['sccttran.CUSTID', '=', $custid];
        if (!empty($filters)) {
            $filterQuery = function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    if (count($filter) === 3) {
                        $query->where($filter[0], $filter[1], $filter[2]);
                    } elseif (count($filter) === 4) {
                        $query->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                    }
                }
            };
        }

        $whereAny = [
            'scctcust.NMCUST',
            'scctcust.NOCUST',
            'scctcust.NUM2ND',
            'sccttran.METODE',
        ];

        $select = array_merge($whereAny, [
            'sccttran.METODE',
            'sccttran.TRXDATE',
            'sccttran.NOREFF',
            'sccttran.FIDBANK',
            'sccttran.KDCHANNEL',
            'sccttran.DEBET',
            'sccttran.KREDIT',
            'sccttran.REFFBANK',
            'sccttran.TRANSNO',
        ]);

        $query = sccttran::whereAny($whereAny, 'like', '%' . $searchValue . '%')
            ->leftJoin('scctcust', 'scctcust.CUSTID', 'sccttran.CUSTID')
            ->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            });

//        dd($query);

        if ($custid) {
            $totalKredit = sccttran::where('CUSTID', $custid)->sum('KREDIT');
            $totalDebet = sccttran::where('CUSTID', $custid)->sum('DEBET');
        }

        // Total records
//        $totalRecords = sccttran::select('count(sccttran.*) as allcount')->count();
        $totalRecords = DB::table('sccttran')->count('urut');
        $totalRecordswithFilter = (clone $query)->count();

        $records = (clone $query)->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->map(function ($item, $index) {
                if ($item->NOCUST && $item->NOCUST != '-') {
                    $NOVA = scctcust::showVA($item->NOCUST);
                } else {
                    $NOVA = scctcust::showVA($item->NUM2ND);
                }
                $item->NOVA = $NOVA;
//                unset($item->id);
                return $item;
            })->toArray();

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordswithFilter,
            "data" => $records,
        );

        if ($custid) {
            $response['totals'] = [
                'kredit' => ['location' => 5, 'value' => $totalKredit, 'columnType' => 'currency'],
                'debet' => ['location' => 4, 'value' => $totalDebet, 'columnType' => 'currency'],
            ];
        }

        return response()->json($response);
    }
}
