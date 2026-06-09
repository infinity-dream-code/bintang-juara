<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_thn_aka;
use App\Models\u_akun;
use App\Models\ValidationMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MasterPostController extends Controller
{
    public string $title = 'Master Data';
    public string $mainTitle = 'Master Post';
    public string $dataTitle = 'Master Post';

    public function index()
    {
        //        dd($angkatan);
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
//        $data['modalLink'] = view('admin.master_data.data_siswa.modal', compact('kelas', 'angkatan'));
        $data['columnsUrl'] = route('admin.master-data.master-post.get-column');
        $data['datasUrl'] = route('admin.master-data.master-post.get-data');

        return view('admin.master_data.master_post.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center','columnType' => 'row'],
            ['data' => 'KodeAkun', 'name' => 'Kode', 'searchable' => true, 'orderable' => true],
            ['data' => 'NamaAkun', 'name' => 'Nama Post', 'searchable' => true, 'orderable' => true],
            ['data' => 'NoRek', 'name' => 'Nomor Rekening', 'searchable' => true, 'orderable' => true],
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

        $columnName = 'KodeAkun';
        $columnSortOrder = 'asc';

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'] ?? null;
            if ($columnIndex !== null && !empty($columnName_arr[$columnIndex]['data']) && $columnName_arr[$columnIndex]['data'] !== 'no') {
                $columnName = $columnName_arr[$columnIndex]['data'];
                $columnSortOrder = $order_arr[0]['dir'] ?? 'desc';
            }
        }

        // Total records
        $totalRecords = u_akun::select('count(*) as allcount')->count();
        $totalRecordswithFilter = u_akun::select('count(*) as allcount')
            ->whereAny(['KodeAkun','NamaAkun','NoRek'], 'like', '%' . $searchValue . '%')
            ->count();

        // Fetch records
        $records = u_akun::orderBy($columnName, $columnSortOrder)
            ->whereAny(['KodeAkun','NamaAkun','NoRek'], 'like', '%' . $searchValue . '%')
            ->select('*')
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->toArray();

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
        $kodeAkun = trim((string) $request->kode_akun);
        $namaAkun = trim((string) $request->nama_akun);
        $noRek = $request->no_rek;

        $validator = Validator::make(
            [
                'kode_akun' => $kodeAkun,
                'nama_akun' => $namaAkun,
                'no_rek' => $noRek,
            ],
            [
                'kode_akun' => ['required', 'max:5'],
                'nama_akun' => ['required',],
                'no_rek' => [],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);

        $postExistByKode = u_akun::where('KodeAkun', $kodeAkun)->first();
        if ($postExistByKode) {
            return response()->json(['message' => 'Kode master post sudah ada'], 422);
        }

        $postExistByNama = u_akun::where('NamaAkun', $namaAkun)->first();
        if ($postExistByNama) {
            return response()->json(['message' => 'Nama master post sudah ada'], 422);
        }

        $postExist = u_akun::where([
            ['KodeAkun', '=', $kodeAkun],
            ['NamaAkun', '=', $namaAkun],
            ['NoRek', '=', $noRek],
        ])->first();
        if ($postExist) return response()->json(['message' => 'Master post sudah ada'], 422);

        try {
            DB::beginTransaction();
            u_akun::create(
                [
                    'KodeAkun' => $kodeAkun,
                    'NamaAkun' => $namaAkun,
                    'NoRek' => $noRek,
                ]
            );
            DB::commit();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' telah disimpan']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal disimpan', 'error' => $e->getMessage()], 422);
        }
    }
}
