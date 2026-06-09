<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\scctcust;
use App\Models\mst_thn_aka;
use App\Models\ValidationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PindahKelasController extends Controller
{
    private string $title = 'Master Data';
    private string $mainTitle = 'Pindah Kelas';
    private string $dataTitle = 'Pindah Kelas';
    private ?string $unitScope = null;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $user = Auth::user();
                $this->unitScope = $user->unit;
            }
            return $next($request);
        });
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = route('admin.master-data.pindah-kelas.get-column');
        $data['datasUrl'] = route('admin.master-data.pindah-kelas.get-data');
        $data['thn_aka'] = mst_thn_aka::where('thn_aka', '!=', null)->orderBy('thn_aka','asc')->get();
        $data['kelas'] = mst_kelas::when($this->unitScope, function ($query) {
            $query->where("unit", $this->unitScope);
        })->orderBy('kelas','asc')->get();

        return view('admin.master_data.pindah_kelas.index', $data);
    }

    public function getColumn()
    {
        return [
            //        ['data' => 'no', 'name' => 'no'],
            ['data' => 'check', 'name' => '', 'columnType' => 'checkbox', 'orderable' => false],
            ['data' => 'nis', 'name' => 'NIS', 'searchable' => true, 'orderable' => true],
            ['data' => 'nama', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true],
            ['data' => 'thn_aka', 'name' => 'Angkatan', 'searchable' => true, 'orderable' => true],
            ['data' => 'kelas', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true],
        ];
    }

    public function getData(Request $request)
    {
        $draw = (int) $request->get("draw");
        $kelasId = $request->get("kelas");

        $query = scctcust::query()->where("STCUST", 1);

        if ($this->unitScope) {
            $query->where("CODE02", $this->unitScope);
        }

        if ($kelasId) {
            $query->where("CODE03", $kelasId);
        }

        $searchValue = $request->get("search")["value"] ?? "";
        if ($searchValue !== "") {
            $query->where(function ($q) use ($searchValue) {
                $q->where("NOCUST", "like", "%{$searchValue}%")
                    ->orWhere("NMCUST", "like", "%{$searchValue}%");
            });
        }

        $totalRecords = scctcust::where("STCUST", 1)->count();
        $totalFiltered = (clone $query)->count();

        $records = $query
            ->orderBy("NMCUST", "asc")
            ->skip((int) $request->get("start", 0))
            ->take((int) $request->get("length", 10))
            ->get()
            ->map(function ($item) {
                return [
                    "id" => $item->CUSTID,
                    "nis" => $item->NOCUST,
                    "nama" => $item->NMCUST,
                    "thn_aka" => $item->DESC04,
                    "kelas" => trim(($item->DESC02 ?? "") . " " . ($item->DESC03 ?? "")),
                    "check" => true,
                ];
            });

        return response()->json([
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalFiltered,
            "data" => $records,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pindah' => ['required', 'in:all,satuan'],
            'ke_kelas' => ['required', 'different:dari_kelas'],
            'dari_kelas' => ['required', 'different:ke_kelas'],
        ], ValidationMessage::messages(), ValidationMessage::attributes());
        $validator->sometimes('siswa', 'required|array|min:1', function ($input) {
            return $input->pindah === 'satuan';
        });
        $valMsg = $request->pindah === 'satuan' ? 'silahkan pilih siswa yang akan dipindahkan kelasnya!' : $validator->errors()->first();
        if ($validator->fails()) return response()->json(['message' => $valMsg, 'error' => $validator->errors()], 422);

        $dariKelas = mst_kelas::where('id', '=', $request->dari_kelas)->first();
        $keKelas = mst_kelas::where('id', '=', $request->ke_kelas)->first();
        if (!$keKelas && $dariKelas) return response()->json(['message' => 'Kelas tidak ditemukan, silahkan muat ulang halaman!'], 422);

        switch ($request->pindah) {
            case 'all':
                $siswas = scctcust::select(['CUSTID'])
                    ->where('CODE03', $request->dari_kelas)
                    ->get();
                if ($siswas->isEmpty()) return response()->json(['message' => 'Tidak ada siswa di kelas dan angkatan ini'], 422);
                break;
            case 'satuan':
                $siswas = scctcust::select(['CUSTID'])->whereIn('CUSTID', $request->input('siswa'))->get();
                if ($siswas->isEmpty()) return response()->json(['message' => 'Siswa tidak ditemukan'], 422);
                if (count($request->input('siswa')) != $siswas->count()) return response()->json(['message' => 'Jumlah siswa yang dipilih tidak sesuai dengan jumlah data, silahkan muat ulang halaman!'], 422);
                break;
            default:
                return response()->json(['message' => 'Data tidak valid, silahkan muat ulang halaman '], 422);
        }

        try {
            DB::beginTransaction();
            scctcust::whereIn('CUSTID', $siswas->pluck('CUSTID'))
                ->update([
                    'DESC02' => $keKelas->jenjang,
                    'CODE02' => $keKelas->unit,
                    'CODE03' => $keKelas->id,
                    'DESC03' => $keKelas->kelas,
                    ]);
            DB::commit();
            return response()->json(['message' => 'Siswa telah dipindahkan dari kelas ' . $dariKelas->kelas . ' ' . $dariKelas->jenjang . ' ke kelas ' . $keKelas->kelas . ' ' . $keKelas->kelompok], 200);
        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json(['message' => 'Pindah kelas gagal dilakukan', 'error' => $e->getMessage()], 422);
        }
    }
}
