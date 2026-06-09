<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_thn_aka;
use App\Models\ValidationMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TahunPelajaranController extends Controller
{
    public string $title = 'Master Data';
    public string $mainTitle = 'Tahun Akademik';
    public string $dataTitle = 'Tahun Akademik';

    public function index()
    {
        //        dd($angkatan);
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
//        $data['modalLink'] = view('admin.master_data.data_siswa.modal', compact('kelas', 'angkatan'));
        $data['columnsUrl'] = route('admin.master-data.tahun-pelajaran.get-column');
        $data['datasUrl'] = route('admin.master-data.tahun-pelajaran.get-data');

        return view('admin.master_data.tahun_akademik.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center', 'columnType' => 'no'],
            ['data' => 'thn_aka', 'name' => 'Tahun Pelajaran', 'searchable' => true, 'orderable' => true],
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

        $columnName = 'thn_aka';
        $columnSortOrder = 'desc';

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'] ?? null;
            if ($columnIndex !== null && !empty($columnName_arr[$columnIndex]['data']) && $columnName_arr[$columnIndex]['data'] !== 'no') {
                $columnName = $columnName_arr[$columnIndex]['data'];
                $columnSortOrder = $order_arr[0]['dir'] ?? 'desc';
            }
        }

        // Total records
        $totalRecords = mst_thn_aka::select('count(*) as allcount')->count();
        $totalRecordswithFilter = mst_thn_aka::select('count(*) as allcount')
            ->whereAny(['thn_aka'], 'like', '%' . $searchValue . '%')
            ->count();

        // Fetch records
        $records = mst_thn_aka::orderBy($columnName, $columnSortOrder)
            ->whereAny(['thn_aka'], 'like', '%' . $searchValue . '%')
            ->select('*')
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->map(function ($item) {
                return $item;
            })->toArray();

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
            $request->all(), [
            'thn_aka' => ['required', 'regex:/^\d{4}\/\d{4}(?:\s*-\s*(GANJIL|GENAP))?$/', function ($attribute, $value, $fail) {
                if (strlen($value) > 18) {
                    $fail('Tahun Pelajaran tidak boleh lebih dari 18 karakter');
                }
            }],
        ], ValidationMessage::messages(), ValidationMessage::attributes()
        );

        if ($validator->fails()) return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);


        $kelasExist = mst_thn_aka::where('thn_aka', $request->thn_aka)->first();
        if ($kelasExist) return response()->json(['message' => 'Tahun Pelajaran sudah ada'], 422);

        try {
            DB::beginTransaction();
            mst_thn_aka::create(['thn_aka' => $request->thn_aka,]);
            DB::commit();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' telah disimpan']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal disimpan', 'error' => $e->getMessage()], 422);
        }
    }
}
