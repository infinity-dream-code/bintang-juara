<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\u_akun;
use App\Models\u_daftar_harga;
use App\Models\ValidationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BebanPostController extends Controller
{
    public string $title = 'Master Data';
    public string $mainTitle = 'Beban Post';
    public string $dataTitle = 'Beban Post';

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['thn_aka'] = mst_thn_aka::orderBy('thn_aka', 'asc')->get();
        $data['tagihan'] = u_akun::whereNotNull('KodeAkun')->orderBy('KodeAkun', 'asc')->get();
        $data['jenjang'] = mst_kelas::select('jenjang')->distinct()
            ->orderByRaw("CASE WHEN jenjang REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, jenjang")->get();
        $data['kelas'] = mst_kelas::orderByRaw("CASE WHEN jenjang REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, jenjang")->get();
//        $data['modalLink'] = view('admin.master_data.data_siswa.modal', compact('kelas', 'angkatan'));
        $data['columnsUrl'] = route('admin.master-data.beban-post.get-column');
        $data['datasUrl'] = route('admin.master-data.beban-post.get-data');

        return view('admin.master_data.beban_post.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center', 'columnType' => 'row'],
            ['data' => 'thn_masuk', 'name' => 'Tahun Angkatan', 'searchable' => true, 'orderable' => true],
            ['data' => 'KodeAkun', 'name' => 'Kode', 'searchable' => true, 'orderable' => true],
            ['data' => 'NamaAkun', 'name' => 'Nama Post', 'searchable' => true, 'orderable' => true],
            ['data' => 'nominal', 'name' => 'Nominal', 'columnType' => 'currency', 'searchable' => true, 'orderable' => true],
        ];
    }

    public function getData(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length");

        $columnIndex_arr = $request->get('order', []);
        $columnName_arr = $request->get('columns', []);
        $order_arr = $request->get('order', []);
        $search_arr = $request->get('search', []);
        $searchValue = $search_arr['value'] ?? '';

        $columnName = 'u_daftar_harga.KodeAkun';
        $columnSortOrder = 'asc';

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
                if (strtolower($val) != 'all' && $val !== null && $val !== '') {
                    $colName = match ($key) {
                        'kelas' => 'u_daftar_harga.kode_prod',
                        'tahun_akademik' => 'u_daftar_harga.thn_masuk',
                        'kode_akun' => 'u_daftar_harga.KodeAkun',
                        'nominal' => 'u_daftar_harga.nominal',
                        default => null
                    };
                    if ($key == 'nominal') {
                        if (preg_match('/^[0-9]+(\.[0-9]{3})*$/', $val)) {
                            $val = str_replace('.', '', $val);
                        }
                    }

                    ($colName) && $filters[] = [$colName, '=', $val];
                }
            }

            if (!empty($filters)) {
                $filterQuery = function ($query) use ($filters) {
                    foreach ($filters as $filter) {
                        if (count($filter) === 3) {
                            $query->where($filter[0], $filter[1], $filter[2]);
                        } elseif (count($filter) === 4) {
                            if ($filter[3] == 'whereBetween') {
                                $query->whereBetween($filter[0], [$filter[1], $filter[2]]);
                            } else {
                                $query->{$filter[3]}($filter[0], $filter[1], $filter[2]);
                            }
                        }
                    }
                };
            }
        }

        $whereAny = [
            'u_daftar_harga.KodeAkun', 'u_akun.NamaAkun', 'u_daftar_harga.nominal', 'u_daftar_harga.thn_masuk'
        ];

        $select = array_unique(array_merge($whereAny, [

        ]));

        $totalRecords = Cache::remember('master_data_beban_post_total_count', 600, function () {
            return u_daftar_harga::select('count(*) as allcount')->count();
        });

        $query = u_daftar_harga::leftJoin('u_akun', 'u_akun.KodeAkun', '=', 'u_daftar_harga.KodeAkun')
            ->when(!blank($searchValue), function ($query) use ($whereAny, $searchValue) {
                $query->where(function ($q) use ($whereAny, $searchValue) {
                    $sanitizeSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchValue);
                    foreach ($whereAny as $column) {
                        $q->orWhere($column, 'like', '%' . $sanitizeSearch . '%');
                    }
                });
            });

        if ($filterQuery) {
            $query->where(function ($q) use ($filterQuery) {
                $filterQuery($q);
            });
        }

        $totalRecordswithFilter = $query->select('count(*) as allcount')->count();

        // Fetch records
        $records = $query->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->toArray();

        if ($totalRecords < $totalRecordswithFilter) {
            $totalRecords = $totalRecordswithFilter;
            Cache::put('master_data_beban_post_total_count', $totalRecordswithFilter, 600);
        }

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordswithFilter,
            'data' => $records,
        );
        return response()->json($response);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'tahun_aka' => ['required', 'regex:/^\d{4}\/\d{4}(?:\s*-\s*(GANJIL|GENAP))?$/'],
                'kelas' => ['required'],
                'kode_akun' => ['required',],
                'nominal' => ['required', 'regex:/^[0-9]+(\.[0-9]{3})*$/', 'not_in:0'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);

        $nominal = str_replace('.', '', $request->nominal);

        $query = u_daftar_harga::where([
            ['KodeAkun', '=', $request->kode_akun],
            ['thn_masuk', '=', $request->tahun_aka],
        ]);

        $kelas = $request->kelas != 'all' && !is_null($request->kelas) ? $request->kelas : null;
        if (!is_null($kelas)) {
            $kelasExist = mst_kelas::where('id', $kelas)->first();
            if (!$kelasExist) return response()->json(['message' => 'Kelas tidak ditemukan!'], 422);
            $query->where('kode_prod', '=', $request->kelas);
        } else {
            $query->whereNull('kode_prod');
        }
        $dataExsit = $query->first();
        if ($dataExsit) return response()->json(['message' => 'Beban post sudah ada!'], 422);

        try {
            DB::beginTransaction();

            u_daftar_harga::create([
                'KodeAkun' => $request->kode_akun,
                'thn_masuk' => $request->tahun_aka,
                'kode_prod' => $kelas,
                'kode_fak' => '03',
                'nominal' => $nominal,
            ]);

            DB::commit();
            return response()->json(['message' => 'Beban Post disimpan'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Tidak dapat menambahkan beban post!', 'error' => $e], 422);
        }
    }
}
