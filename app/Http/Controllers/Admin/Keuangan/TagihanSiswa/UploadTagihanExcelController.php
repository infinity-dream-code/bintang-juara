<?php

namespace App\Http\Controllers\Admin\Keuangan\TagihanSiswa;

use App\Http\Controllers\Controller;
use App\Imports\Keuangan\TagihanSiswa\ImportTagihanExcel;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use App\Models\ValidationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Validators\ValidationException;

class UploadTagihanExcelController extends Controller
{
    public string $title = 'Keuangan';
    public string $mainTitle = 'Tagihan Siswa';
    public string $dataTitle = 'Buat Tagihan Excel';
    public string $cacheKey = 'import_tagihan_excel';

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = route('admin.keuangan.tagihan-siswa.upload-tagihan-excel.get-column');
        $data['datasUrl'] = route('admin.keuangan.tagihan-siswa.upload-tagihan-excel.get-data');

        $data['thn_aka'] = mst_thn_aka::orderBy('thn_aka', 'desc')->get();
        $data['tagihan'] = mst_tagihan::orderBy('urut', 'asc')->get();

        return view('admin.keuangan.tagihan_siswa.upload_tagihan_excel.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center', 'columnType' => 'row'],
            ['data' => 'nis', 'name' => 'NIS', 'searchable' => true, 'orderable' => true],
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

        $defaultColumn = 'scctcust.nocust';
        $defaultOrder = 'asc';

        $columnSortOrder = $defaultOrder;
        $columnName = $defaultColumn;

        if ($request->has('order')) {
            $order = $request->get('order');
            $columnIndex = (int) ($order[0]['column'] ?? 0);
            $columnSortOrder = $order[0]['dir'] ?? $defaultOrder;
            $requestedColumn = $columnName_arr[$columnIndex]['data'] ?? null;

            if ($requestedColumn && $requestedColumn !== 'no') {
                $columnName = 'scctcust.' . $requestedColumn;
            }
        }

        $searchValue = $search_arr['value'] ?? '';

        $filters = [];
        $filterQuery = null;

        $cachedData = Cache::get($this->cacheKey, []);

        $nisList = collect($cachedData)->pluck('nis')->toArray();
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

        $records = collect($cachedData)->map(function ($item) use ($select){
            $nis = $item['nis'];
            $siswa = scctcust::select($select)->where('scctcust.NOCUST', $nis)->first();
            return [
                'nis' => $nis,
                'name' => $siswa->NMCUST ?? null,
                'ortu' => $item['ayah'] ?? null,
                'kelas' => $siswa->DESC02 ?? null,
                'kelompok' => $siswa->DESC03 ?? null,
                'nominal' => $item['nominal'] ?? null,
                'status' => $item['status'] ?? 0,
                'keterangan' => $item['keterangan'],
            ];
        });

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $nisCount,
            'recordsFiltered' => $nisCount,
            'data' => $records,
        );
        return response()->json($response);
    }


    public function store(Request $request)
    {
        $request->validate(
            [
                'fileImport' => [
                    'required',
                    'file',
                    'mimes:xls,xlsx',
                    'mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream',
                    'max:1024',
                ],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        $file = $request->file('fileImport');

        try {
            $headingsData = (new HeadingRowImport)->toArray($file);
            $requiredColumns = ['nis', 'nama', 'unit', 'kelas', 'kelompok', 'angkatan', 'nominal'];
            if (empty($headingsData) || !isset($headingsData[0][0])) {
                throw new \Exception('Tidak dapat membaca judul kolom dari file. Pastikan file memiliki header yang sesuai.');
            }
            $headings = array_map(
                static fn ($heading) => strtolower(trim((string) $heading)),
                $headingsData[0][0]
            );
            $missingColumns = [];
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $headings, true)) {
                    $missingColumns[] = $column;
                }
            }

            if (!empty($missingColumns)) {
                $formattedMissingColumns = strtoupper(str_replace('_', ' ', implode(', ', $missingColumns)));
                $formattedRequiredColumns = strtoupper(str_replace('_', ' ', implode(', ', $requiredColumns)));
                throw new \Exception("Kolom $formattedMissingColumns tidak ditemukan.<br><hr> pastikan kolom berikut ada dan terisi pada file import yang akan diproses: $formattedRequiredColumns.");
            }

            DB::beginTransaction();
            Excel::import(new ImportTagihanExcel(), $file);
            DB::commit();

            $data = Cache::get($this->cacheKey, []);
            if (empty($data)) {
                throw new \Exception('File berhasil dibaca, tetapi tidak ada baris data yang dapat diproses. Pastikan file berisi NIS dan Nominal.');
            }

            Log::info('Upload tagihan excel berhasil', [
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'row_count' => count($data),
            ]);

            return response()->json(['message' => 'Sukses, data tagihan telah diimport, silahkan periksa kembali', 'data' => $data], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            $errorMessages = $e->errors();
            $errorMessage = $errorMessages['error'][0] ?? 'Terjadi kesalahan saat melakukan import data.';

            Log::warning('Upload tagihan excel gagal validasi excel', [
                'user_id' => auth()->id(),
                'file_name' => $file?->getClientOriginalName(),
                'errors' => $errorMessages,
            ]);

            return response()->json(['message' => $errorMessage, 'error' => $errorMessages], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Upload tagihan excel gagal', [
                'user_id' => auth()->id(),
                'file_name' => $file?->getClientOriginalName(),
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            $error = $e->getMessage();

            return response()->json([
                'message' => "Gagal!<br> tidak dapat melakukan {$this->mainTitle}.<hr> {$error}",
                'error' => $error,
            ], 422);
        }
    }

    public function validateExcel(Request $request)
    {
        $request->validate([
            'tahun_pelajaran' => ['required', 'regex:/^\d{4}\/\d{4}(?:\s*-\s*(GANJIL|GENAP))?$/'],
            'fungsi' => ['required', 'integer'],
            'tagihan' => ['required'],
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

        try {
            DB::beginTransaction();
            foreach ($data as $item) {
                if ($item['status'] != 1) continue;
                $siswa = scctcust::where('NOCUST', $item['nis'])->first();
                if (!$siswa) return response()->json(['message' => "siswa dengan nis: {$item['nis']} tidak ditemukan!"], 422);

                $tagihanSiswaTerbaru = scctbill::where('CUSTID', $siswa->CUSTID)
                    ->orderBy('FUrutan', 'DESC')
                    ->first();

                $urut = $tagihanSiswaTerbaru ? $tagihanSiswaTerbaru['FUrutan'] + 1 : 1;
                $billCD = date('Y') . '/i' . date('m') . '-' . ($urut + 1);
                $nominal = (int) $item['nominal'];

                scctbill::create([
                    'CUSTID' => $siswa->CUSTID,
                    'BILLAC' => $tahun . $bulan,
                    'BILLNM' => $tagihan->tagihan,
                    'BILLAM' => $nominal,
                    'PAIDST' => 0,
                    'FUrutan' => $urut,
                    'FTGLTagihan' => now(),
                    'FSTSBolehBayar' => 1,
                    'BTA' => $matches[0],
                    'BILLCD' => $billCD,
                ]);
            }

            Cache::forget($this->cacheKey);

            DB::commit();
            return response()->json(['message' => "Data tagihan disimpan!",], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Simpan tagihan excel gagal', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data.<hr>' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
