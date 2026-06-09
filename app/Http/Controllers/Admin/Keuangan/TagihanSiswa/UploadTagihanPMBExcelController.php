<?php

namespace App\Http\Controllers\Admin\Keuangan\TagihanSiswa;

use App\Http\Controllers\Controller;
use App\Imports\Keuangan\TagihanSiswa\ImportTagihanExcel;
use App\Imports\Keuangan\TagihanSiswa\ImportTagihanPMBExcel;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\u_akun;
use App\Models\scctbill;
use App\Models\scctbill_detail;
use App\Models\scctcust;
use App\Models\ValidationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Validators\ValidationException;

class UploadTagihanPMBExcelController extends Controller
{
    public string $title = 'Keuangan';
    public string $mainTitle = 'Tagihan Siswa';
    public string $dataTitle = 'Upload Tagihan PMB Excel';
    public string $cacheKey = 'import_tagihan_pmb_excel';

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = route('admin.keuangan.tagihan-siswa.upload-tagihan-pmb-excel.get-column');
        $data['datasUrl'] = route('admin.keuangan.tagihan-siswa.upload-tagihan-pmb-excel.get-data');

        $data['thn_aka'] = mst_thn_aka::getMstThnAkaAttributes();
        $data['post'] = u_akun::orderBy('KodeAkun', 'asc')->get();
        $data['tagihan'] = mst_tagihan::orderBy('urut', 'asc')->get();

        return view('admin.keuangan.tagihan_siswa.upload_tagihan_pmb_excel.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center', 'columnType' => 'row'],
            ['data' => 'NUM2ND', 'name' => 'No. Pendaftaran', 'searchable' => true, 'orderable' => true],
            ['data' => 'name', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true],
            ['data' => 'status', 'name' => 'Status', 'searchable' => true, 'orderable' => true, 'columnType' => 'importstatus'],
            ['data' => 'keterangan', 'name' => 'Keterangan', 'searchable' => true, 'orderable' => true],
            ['data' => 'kelas', 'name' => 'Sekolah', 'searchable' => true, 'orderable' => true],
            ['data' => 'kelas', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true],
            ['data' => 'kelompok', 'name' => 'Kelompok', 'searchable' => true, 'orderable' => true],
            ['data' => 'nominal', 'name' => 'Nominal', 'searchable' => true, 'orderable' => true, 'columnType' => 'currency'],
        ];
    }

    public function getData(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $rowperpage = $request->get('length');

        $columnName_arr = $request->get('columns');
        $search_arr = $request->get('search');

        $defaultColumn = 'scctcust.NUM2ND';
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

        $filters = [];
        $filterQuery = null;

        $cachedData = Cache::get($this->cacheKey, []);

//        dd($cachedData);
        $nisList = collect($cachedData)->pluck('nodaftar')->toArray();
        $nisCount = count($cachedData);


        $whereAny = [
            'scctcust.NMCUST',
            'scctcust.NOCUST',
        ];

        $select = array_unique(array_merge($whereAny, [
            'scctcust.NUM2ND',
            'scctcust.CODE02',
            'scctcust.DESC02',
            'scctcust.DESC03',
            'scctcust.DESC04',

        ]));

        $records = collect($cachedData)->map(function ($item) {
            $nis = $item['nodaftar'];
            return [
                'NUM2ND' => $nis,
                'name' => $item['nama'] ?? null,
                'ortu' => $item['ayah'] ?? null,
                'kelas' => $item['kelas'] ?? null,
                'kelompok' => $item['kelompok'] ?? null,
                'nominal' => $item['nominal'] ?? null,
            ];
        });

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $nisCount,
            'recordsFiltered' => $nisCount,
            'data' => $records,
            'nislist' => $nisList
        );
        return response()->json($response);
    }


    public function store(Request $request)
    {
        $request->validate(
            [
                'fileImport' => ['required', 'mimes:xls,xlsx', 'max:1024']
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        $file = $request->fileImport;

        try {
            $headingsData = (new HeadingRowImport)->toArray($file);
            $requiredColumns = ['nodaftar', 'nominal'];
            if (empty($headingsData) || !isset($headingsData[0][0])) throw new \Exception ('Tidak dapat membaca judul kolom dari file. Pastikan file memiliki header yang sesuai.');
            $headings = $headingsData[0][0];
            $headings = array_map('strtolower', $headings);
            $missingColumns = [];
            foreach ($requiredColumns as $column) if (!in_array($column, $headings)) $missingColumns[] = $column;

            if (!empty($missingColumns)) {
                $formattedMissingColumns = strtoupper(str_replace('_', ' ', implode(', ', $missingColumns)));
                $formattedRequiredColumns = strtoupper(str_replace('_', ' ', implode(', ', $requiredColumns)));
                throw new \Exception("Kolom $formattedMissingColumns tidak ditemukan.<br><hr> pastikan kolom berikut ada dan terisi pada file import yang akan diproses: $formattedRequiredColumns.",);
            }

            DB::beginTransaction();
            Excel::import(new ImportTagihanPMBExcel(), $file);
            DB::commit();

            $data = Cache::get($this->cacheKey);
            return response()->json(['message' => 'Sukses, data tagihan telah diimport, silahkan periksa kembali', 'data' => $data], 200);
        } catch (ValidationException $e) {
            $errorMessages = $e->errors();
            $errorMessage = $errorMessages['error'][0] ?? 'Terjadi kesalahan saat melakukan import data.';
            return response()->json(['message' => $errorMessage, 'error' => $errorMessages], 422);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            return response()->json(['message' => "Gagal!<br> tidak dapat melakukan $this->mainTitle.<hr> $error", 'error' => $error], 422);
        }
    }

    public function validateExcel(Request $request)
    {
        $request->validate([
            'tahun_pelajaran' => ['required', 'regex:/^\d{4}\/\d{4}(?:\s*-\s*(GANJIL|GENAP))?$/'],
            'fungsi' => ['required', 'integer'],
            'tagihan' => ['required'],
            'post' => ['required'],
        ], ValidationMessage::messages(), ValidationMessage::attributes());

        $data = Cache::get($this->cacheKey);
        if (empty($data))return response()->json(['message' => 'Silahkan import data tagihan terlebih dahulu'], 422);

        $tahun_akademik = mst_thn_aka::where('thn_aka', $request->tahun_pelajaran)->value('thn_aka');

        if (!$tahun_akademik || !preg_match('/\d{4}\/\d{4}/', $tahun_akademik, $matches)) {
            return response()->json(['message' => 'Tahun akademik tidak valid'], 422);
        }

        $tahun = substr($request->fungsi, 0, 4);
        $bulan = substr($request->fungsi, 4, 2) ?: date('m');

        $tagihan = mst_tagihan::where('urut', $request->tagihan)->first();
        if (!$tagihan) return response()->json(['message' => 'Tagihan tidak ditemukan, silahkan muat ulang halaman!'], 422);

        $post = u_akun::where('KodeAkun', $request->post)->first();
        if (!$post) return response()->json(['message' => 'Post tidak ditemukan, silahkan muat ulang halaman!'], 422);
        try {
            DB::beginTransaction();
            foreach ($data as $item) {
                if ($item['status'] != 1) continue;
                $siswa = scctcust::where('NUM2ND', $item['nodaftar'])->first();
                if (!$siswa) return response()->json(['message' => "siswa dengan NODAFTAR: {$item['nodaftar']} tidak ditemukan!"], 422);

                $tagihanSiswaTerbaru = scctbill::where('CUSTID', $siswa->CUSTID)
                    ->orderBy('FUrutan', 'DESC')
                    ->first();

                $urut = $tagihanSiswaTerbaru ? $tagihanSiswaTerbaru['FUrutan'] + 1 : 1;

                $bill = scctbill::create([
                    'CUSTID' => $siswa->CUSTID,
                    'BILLAC' => $tahun.$bulan,
                    'BILLNM' => $tagihan->tagihan,
                    'BILLAM' => $item['nominal'],
                    'PAIDST' => 0,
                    'FUrutan' => $urut,
                    'FTGLTagihan' => now(),
                    'FSTSBolehBayar' => 1,
                    'BTA' => $tahun_akademik,
                    'BILLCD' => date('Y') . '/i' . date('m') . '-' . ($urut + 1)
                ]);

                $billDetail = scctbill_detail::create([
                    'KodePost' => $post->KodeAkun,
                    'CUSTID' => $bill->CUSTID,
                    'BILLAM' => $bill->BILLAM,
                    'tahun' => $tahun,
                    'periode' => $bulan,
                    'BILLCD' => $bill->BILLCD,
                ]);
            }

            Cache::forget($this->cacheKey);

            DB::commit();
            return response()->json(['message' => "Data Tagihan PMB disimpan!",], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => "Terjadi kesalahan saat menyimpan data, silahkan muat ulang halaman!", 'error' => $e], 422);
        }
    }
}
