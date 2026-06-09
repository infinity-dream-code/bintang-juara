<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\scctcust;
use App\Models\ValidationMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MasterKelasController extends Controller
{
    public string $title;
    public string $mainTitle;
    public string $dataTitle;
    public string $showTitle;
    public ?string $unit = null;

    public function __construct()
    {
        $this->title = 'Master Data';
        $this->mainTitle = 'Master Kelas';
        $this->dataTitle = 'Master Kelas';
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $user = Auth::user();
                $this->unit = $user->unit;
            }
            return $next($request);
        });
    }

    public function index()
    {
        $data['sekolah'] = mst_sekolah::select('CODE01', 'DESC01')->get();
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        //        $data['modalLink'] = view('admin.master_data.data_siswa.modal', compact('kelas', 'angkatan'));
        $data['columnsUrl'] = route('admin.master-data.master-kelas.get-column');
        $data['datasUrl'] = route('admin.master-data.master-kelas.get-data');

        return view('admin.master_data.master_kelas.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center', 'columnType' => 'row'],
            ['data' => 'sekolah', 'name' => 'Sekolah', 'searchable' => true, 'orderable' => true],
            ['data' => 'unit', 'name' => 'Unit', 'searchable' => true, 'orderable' => true],
            ['data' => 'jenjang', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true],
            ['data' => 'kelas', 'name' => 'Kelompok', 'searchable' => true, 'orderable' => true],
            [
                'data' => 'delete',
                'name' => '',
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center',
                'button' => 'modal',
                'buttonText' => 'Hapus',
                'buttonClass' => 'btn btn-sm btn-danger btn-hapus',
                'buttonLink' => '#modal-delete',
                'buttonIcon' => 'ri-delete-bin-line me-2'
            ],

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

        $columnName = 'mst_kelas.unit';
        $columnSortOrder = 'asc';

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'] ?? null;
            if ($columnIndex !== null && !empty($columnName_arr[$columnIndex]['data']) && $columnName_arr[$columnIndex]['data'] !== 'no') {
                $columnName = $columnName_arr[$columnIndex]['data'];
                $columnSortOrder = $order_arr[0]['dir'] ?? 'desc';
            }
        }

        $totalRecords = mst_kelas::select('count(*) as allcount')->count();
        $baseQuery = mst_kelas::query()
            ->leftJoin('mst_sekolah', 'mst_sekolah.CODE01', '=', 'mst_kelas.kelompok');

        $searchable = [
            'mst_kelas.kelas',
            'mst_kelas.jenjang',
            'mst_kelas.unit',
            'mst_kelas.kelompok',
            'mst_sekolah.DESC01',
        ];

        $totalRecordswithFilter = (clone $baseQuery)
            ->whereAny($searchable, 'like', '%' . $searchValue . '%')
            ->count();

        if ($columnName === 'sekolah') {
            $columnName = 'mst_sekolah.DESC01';
        } elseif (!str_contains($columnName, '.')) {
            $columnName = "mst_kelas.{$columnName}";
        }

        $records = (clone $baseQuery)
            ->orderBy($columnName, $columnSortOrder)
            ->orderBy('mst_sekolah.DESC01', 'asc')
            ->orderBy('mst_kelas.unit', 'asc')
            ->orderBy('mst_kelas.jenjang', 'asc')
            ->orderBy('mst_kelas.kelas', 'asc')
            ->whereAny($searchable, 'like', '%' . $searchValue . '%')
            ->when($this->unit, function ($query) {
                $query->where("mst_kelas.unit", $this->unit);
            })
            ->select([
                'mst_kelas.id',
                'mst_kelas.unit',
                'mst_kelas.jenjang',
                'mst_kelas.kelas',
                'mst_kelas.kelompok',
                DB::raw('COALESCE(mst_sekolah.DESC01, mst_kelas.kelompok) as sekolah')
            ])
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->map(function ($item, $index) {
                if (strcasecmp((string) $item->unit, 'CADANGAN') === 0) {
                    $item->unit = 'ICT TESTING';
                }
                if (strcasecmp((string) $item->kelas, 'KELAS') === 0) {
                    $item->kelas = 'TESTING 3';
                }
                $item->item_id = $item->id;
                $item->delete = true;
                unset($item->id);
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
            $request->all(),
            [
                'sekolah' => ['required',],
                'kelas' => ['required',],
                'kelompok' => ['required'],
                'unit_kelas' => ['required'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);

        $sekolah = trim((string) $request->sekolah);
        $unit = trim((string) $request->unit_kelas);
        $kelas = trim((string) $request->kelas);
        $kelompok = trim((string) $request->kelompok);

        // Normalisasi sesuai kebutuhan user.
        if (strcasecmp($sekolah, 'CADANGAN') === 0) {
            $sekolah = 'ICT TESTING';
        }
        if (strcasecmp($kelompok, 'KELAS') === 0) {
            $kelompok = 'TESTING 3';
        }

        $sekolahCode = $sekolah;
        $refSekolah = mst_sekolah::query()
            ->where('CODE01', $sekolah)
            ->orWhere('DESC01', $sekolah)
            ->first();
        if ($refSekolah) {
            $sekolahCode = (string) $refSekolah->CODE01;
        }

        $kelasExist = mst_kelas::where('unit', $unit)
            ->where('jenjang', $kelas)
            ->where('kelas', $kelompok)
            ->where('kelompok', $sekolahCode)
            ->first();
        if ($kelasExist) return response()->json(['message' => 'Kelas sudah ada'], 422);

        try {
            DB::beginTransaction();
            mst_kelas::create(
                [
                    // Mapping sesuai kebutuhan terbaru:
                    // unit=unit, kelas=jenjang, kelompok=kelas input, dan kolom kelompok DB berisi CODE01 sekolah.
                    'unit' => $unit,
                    'jenjang' => $kelas,
                    'kelas' => $kelompok,
                    'kelompok' => $sekolahCode,
                ]
            );
            DB::commit();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' telah disimpan']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal disimpan', 'error' => $e->getMessage()], 422);
        }
    }

    public function destroy($id, Request $request)
    {
        $kelas = mst_kelas::where('id', $id)->first();
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan!'], 422);

        $usageSiswa = scctcust::where([
            'DESC02' => $kelas->jenjang,
            'CODE03' => $kelas->id,
            'DESC03' => $kelas->kelas,
        ])->first();

        if ($usageSiswa) return response()->json(['message' => 'Kelas digunakan oleh data siswa!!'], 422);

        try {
            DB::beginTransaction();
            $kelas->delete();
            DB::commit();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' telah dihapus']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal dihapus', 'error' => $e->getMessage()], 422);
        }
    }
}
