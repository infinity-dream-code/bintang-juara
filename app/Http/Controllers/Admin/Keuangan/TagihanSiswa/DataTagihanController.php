<?php

namespace App\Http\Controllers\Admin\Keuangan\TagihanSiswa;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use App\Models\sccttran;
use App\Models\User;
use App\Models\ValidationMessage;
use App\Support\CacheHandler;
use App\Support\FilterHandler;
use App\Support\TagihanPaymentReversal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DataTagihanController extends Controller
{
    public ?string $sekolah = null;
    private string $title = "Keuangan";
    private string $mainTitle = 'Tagihan Siswa';
    private string $dataTitle = 'Data Tagihan Siswa';
    private string $cacheKey = 'data_tagihan';
    private array $allowedFilters = [
        'tanggal-pembuatan' => 'scctbill.FTGLTagihan',
        'periode' => 'scctbill.BILLAC',
        'post' => 'scctbill.BILLNM',
        'kelas' => 'scctcust.DESC02',
        'sekolah' => 'scctcust.CODE02',
        'angkatan' => 'scctcust.DESC04',
        'siswa' => 'scctcust.nmcust',
        'custid' => 'scctcust.CUSTID',
    ];

    public function __construct()
    {
        $key = Str::slug($this->cacheKey) . '_cache_version';

        Cache::add($key, 1);
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $user = Auth::user();
                $this->sekolah = $user->sekolah;
            }
            return $next($request);
        });
    }

    private function applyUnitScope($query, string $table = 'scctcust'): void
    {
        if (blank($this->sekolah)) {
            return;
        }

        $unit = trim((string) $this->sekolah);
        $query->where(function ($q) use ($table, $unit) {
            $q->where($table . '.CODE01', $unit)
                ->orWhere($table . '.CODE02', $unit)
                ->orWhereRaw('UPPER(TRIM(' . $table . '.DESC01)) = UPPER(?)', [$unit]);
        });
    }

    public function getColumn()
    {
        return [
            [
                'data' => 'detail_trx',
                'name' => '+',
                'orderable' => false,
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center exclude-selection',
                'excludeFromSelection' => true,
                'button' => 'action',
                'buttonText' => '+',
                'buttonClass' => 'btn btn-sm btn-primary btn-detail-trx',
                'buttonLink' => '#',
                'noCaption' => false,
                'exportable' => false,
                'duplicate' => false,
            ],
            ['data' => null, 'name' => 'no', 'columnType' => 'row', 'exportable' => true],
            ['data' => 'NOCUST', 'name' => 'NIS', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'NUM2ND', 'name' => 'NO DAFT', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'NOVA', 'name' => 'NO VA', 'exportable' => true],
            ['data' => 'NMCUST', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'CODE02', 'name' => 'Unit', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'DESC02', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'DESC03', 'name' => 'Kelompok', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'BILLNM', 'name' => 'Nama Tagihan', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'BILLAM_TOTAL', 'name' => 'Jumlah Tagihan', 'searchable' => true, 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end', 'exportable' => true],
            ['data' => 'BILLAM', 'name' => 'Sisa Tagihan', 'searchable' => true, 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end', 'exportable' => true],
            ['data' => 'BILLPAID', 'name' => 'Jumlah Terbayar', 'searchable' => true, 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end', 'exportable' => true],
            ['data' => 'PAIDDT', 'name' => 'Tanggal Bayar', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'BILLAC', 'name' => 'Periode', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            [
                'data' => 'FUrutan',
                'name' => 'Urutan',
                'searchable' => true,
                'orderable' => true,
                'exportable' => true,
                'duplicate' => false,
            ],
            [
                'data' => 'delete',
                'name' => '',
                'orderable' => false,
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center exclude-selection',
                'excludeFromSelection' => true,
                'button' => 'action',
                'buttonText' => 'Reversal',
                'buttonTextField' => 'delete_label',
                'buttonClass' => 'btn btn-sm btn-warning btn-reversal',
                'buttonLink' => '#modal-delete',
                'buttonIcon' => 'ri-arrow-go-back-line me-2',
                'exportable' => false,
                'duplicate' => false,
            ],
        ];
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = $this->columnsUrl();
        $data['datasUrl'] = $this->datasUrl();
        $data['post'] = mst_tagihan::select(['tagihan'])->get();
        $data['thn_aka'] = mst_thn_aka::select(['thn_aka'])
            ->where('thn_aka', '!=', null)
            ->orderBy('thn_aka', 'desc')->get();
        $data['periode'] = scctbill::query()
            ->whereNotNull('BILLAC')
            ->where('BILLAC', '!=', '')
            ->distinct()
            ->orderBy('BILLAC', 'desc')
            ->pluck('BILLAC');
        $data['sekolah'] = mst_sekolah::when($this->sekolah, function ($query) {
            $query->where(function ($q) {
                $q->where("CODE01", $this->sekolah)
                    ->orWhere("DESC01", $this->sekolah);
            });
        })->get();
        $data['kelas'] = mst_kelas::when($this->sekolah, function ($query) {
            $query->where("unit", $this->sekolah);
        })->orderByRaw("CASE WHEN kelas REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, kelas")->get();
        $data['tanda_tangan'] = User::getTandaTanganBase64();

        return view('admin.keuangan.tagihan_siswa.data_tagihan', $data);
    }

    private function columnsUrl(): string
    {
        return route('admin.keuangan.tagihan-siswa.data-tagihan.get-column');
    }

    private function datasUrl(): string
    {
        return route('admin.keuangan.tagihan-siswa.data-tagihan.get-data');
    }

    public function ubahUrutan($id, Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'urutan_tagihan' => ['required', 'in:naik,turun'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) {
            if ($validator->errors()->has('tagihan.nominal_bayar.*') || $validator->errors()->has('tagihan.post.*')) {
                return response()->json(['message' => 'Silahkan cek tagihan yang anda pilih,<br> pastikan telah mengisi nominal pembayaran'], 422);
            }

            return response()->json(['message' => $validator->errors()->first(), 'error' => $validator->errors()], 422);
        }

        $tagihan = scctbill::where('AA', $id)
            ->where('FSTSBolehBayar', '=', 1)
            ->where('PAIDST', '=', 0)
            ->first();
        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        $custId = (string) $tagihan->CUSTID;
        $aa = (string) $tagihan->AA;
        $currentUrut = (int) ($tagihan->FUrutan ?? 0);

        if ($currentUrut <= 0) {
            return response()->json([
                'message' => 'Tagihan dengan urutan 0 tidak dapat dinaikkan atau diturunkan.',
            ], 422);
        }

        if ($request->urutan_tagihan === 'naik' && $currentUrut <= 1) {
            return response()->json(['message' => 'Urutan sudah paling atas.'], 422);
        }

        if ($request->urutan_tagihan === 'turun') {
            $maxUrut = (int) scctbill::where('CUSTID', $custId)->max('FUrutan');
            if ($currentUrut >= $maxUrut) {
                return response()->json(['message' => 'Urutan sudah paling bawah.'], 422);
            }
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();

            if ($request->urutan_tagihan === 'naik') {
                DB::connection('DATA_MYSQL')->select('CALL UpdateUrutUP(?, ?)', [$custId, $aa]);
            } else {
                DB::connection('DATA_MYSQL')->select('CALL UpdateUrutDOWN(?, ?)', [$custId, $aa]);
            }

            Cache::increment(Str::slug($this->cacheKey) . '_cache_version');
            DB::connection('DATA_MYSQL')->commit();

            $label = $request->urutan_tagihan === 'naik' ? 'dinaikkan' : 'diturunkan';

            return response()->json([
                'message' => "Urutan tagihan berhasil {$label}.",
            ], 200);
        } catch (\Throwable $e) {
            DB::connection('DATA_MYSQL')->rollBack();

            return response()->json([
                'message' => 'Gagal mengubah urutan tagihan: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id, Request $request)
    {
        $custId = $request->input('user_id') ?? $request->input('custid');

        $tagihan = scctbill::where('AA', $id)
            ->where('FSTSBolehBayar', '=', 1)
            ->where('PAIDST', '=', 0)
            ->when($custId, fn ($q) => $q->where('CUSTID', $custId))
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        $siswa = scctcust::where('CUSTID', $tagihan->CUSTID)->first();
        if (!$siswa) {
            return response()->json(['message' => 'Siswa tidak ditemukan!'], 422);
        }

        try {
            $reversal = app(TagihanPaymentReversal::class);
            $hasPayments = $reversal->hasBillPayments($tagihan);

            if ($hasPayments) {
                $reversal->reverseLastPayment($tagihan, $request);
                $message = 'Reversal berhasil!';
            } else {
                $tagihan->update(['FSTSBolehBayar' => 0]);
                $message = 'Tagihan dihapus!';
            }

            Cache::increment(Str::slug($this->cacheKey) . '_cache_version');

            return response()->json(['message' => $message], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal memproses tagihan!',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function cetak(Request $request)
    {
        ini_set('max_execution_time', 300);

        try {
            $filterQuery = $this->resolveTagihanFilterQuery($request);
            $selectedBillNames = $this->resolveSelectedBillNames($request);

            $mstTagihanQuery = mst_tagihan::query()
                ->select('urut', 'tagihan', 'kode')
                ->orderBy('urut');

            if (!empty($selectedBillNames)) {
                $mstTagihanQuery->where(function ($q) use ($selectedBillNames) {
                    foreach ($selectedBillNames as $name) {
                        $sanitized = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], (string) $name);
                        $q->orWhere('tagihan', 'like', '%' . $sanitized . '%');
                    }
                });
            }

            $mstTagihan = $mstTagihanQuery->get();
            if ($mstTagihan->isEmpty()) {
                return response()->json(['message' => 'Data Kosong'], 422);
            }

            $records = scctbill::query()
                ->join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
                ->select([
                    'scctcust.nmcust',
                    'scctcust.nocust',
                    'scctbill.AA',
                    'scctbill.BILLNM',
                    'scctbill.BILLAM',
                    'scctbill.PAIDST',
                    'scctbill.PAIDDT',
                    'scctbill.BTA',
                    'scctbill.FIDBANK',
                    'scctbill.FUrutan',
                    'scctcust.CODE02',
                    'scctcust.DESC02',
                ])
                ->whereIn('scctbill.BILLNM', $mstTagihan->pluck('tagihan'))
                ->where(function ($q) {
                    $q->whereNull('scctbill.PAIDST')
                        ->orWhere('scctbill.PAIDST', '<>', 1);
                })
                ->where('scctbill.FSTSBolehBayar', 1)
                ->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1')
                ->when($filterQuery, function ($query) use ($filterQuery) {
                    $filterQuery($query);
                });

            $this->applyUnitScope($records);

            $records = $records
                ->orderBy('scctbill.CUSTID', 'desc')
                ->orderBy('scctbill.BILLAC', 'desc')
                ->orderBy('scctbill.PAIDDT', 'desc')
                ->get();

            $groupedByBill = $records->groupBy('BILLNM');

            $posts = $mstTagihan->map(function ($item) use ($groupedByBill) {
                $item->tagihans = ($groupedByBill->get($item->tagihan) ?? collect())
                    ->map(fn ($row) => $row->toArray())
                    ->values()
                    ->all();

                return $item;
            });

            if ($posts->every(fn ($post) => empty($post->tagihans))) {
                return response()->json(['message' => 'Data Kosong'], 422);
            }

            $pdf = Pdf::loadView('cetak.data-tagihan', ['posts' => $posts])->setPaper('a4', 'landscape');

            return $pdf->download('rekap-tagihan.pdf');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Tidak dapat mencetak rekap', 'error' => $e->getMessage(), 'e' => $e], 422);
        }
    }

    public function cetakKartuSiswa(Request $request)
    {
        $filter = $request;
        if (!$filter['custid']) return response()->json(['error' => 'Siswa tidak ditemukan']);
        $filter['draw'] = 2;
        $filter['start'] = 0;
        $filter['length'] = "poll";

        $siswa = scctcust::where('custid', $filter['custid'])->first();
        if (!$siswa) return response()->json(['error' => 'Siswa tidak ditemukan']);

        $request->merge([
            'filter' => array_merge($request->input('filter', []), [
                'custid' => $filter['custid']
            ])
        ]);

        $filter = $request;
        $tagihans = $this->getData($filter);

        try {
            $tagihans = json_decode(json_encode($tagihans), true);
            $tagihans = $tagihans['original']['data'];
            if (!$tagihans) return response()->json(['message' => 'Tagihan Tidak Ditemukan'], 422);
            return response()->json(['tagihans' => $tagihans, 'siswa' => $siswa], 200);
//            $pdf = Pdf::loadView('pdf.data_tagihan.kartu-siswa', ['tagihans' => $tagihans, 'siswa' => $siswa, 'tanda_tangan' => $tanda_tangan]);
//            return $pdf->download('kartu-siswa.pdf');
        } catch (\Dompdf\Exception $e) {
            return response()->json(['message' => 'Tagihan Tidak Ditemukan', 'error' => $e], 422);
        }
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

        $columnName = 'scctbill.FUrutan';
        $columnSortOrder = 'asc';
        $userOrdered = false;
        $nonSortableData = ['AA', 'naik', 'turun', 'delete', 'print', 'NOVA', 'detail_trx'];

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'] ?? null;
            $requestedData = ($columnIndex !== null && isset($columnName_arr[$columnIndex]['data']))
                ? $columnName_arr[$columnIndex]['data']
                : null;

            if (
                $requestedData
                && !in_array($requestedData, $nonSortableData, true)
                && $requestedData !== 'no'
            ) {
                $userOrdered = true;
                $columnName = $requestedData;
                $columnSortOrder = strtolower($order_arr[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            }
        }

        $sortableColumns = [
            'BILLNM' => 'scctbill.BILLNM',
            'BILLAM_TOTAL' => 'scctbill.BILLAM',
            'BILLAM' => 'scctbill.PAYMENTLEFT',
            'BILLPAID' => 'scctbill.BILLPAID',
            'BILLAC' => 'scctbill.BILLAC',
            'FUrutan' => 'scctbill.FUrutan',
            'PAIDDT' => 'scctbill.PAIDDT',
            'NOCUST' => 'scctcust.NOCUST',
            'NUM2ND' => 'scctcust.NUM2ND',
            'NMCUST' => 'scctcust.NMCUST',
            'CODE02' => 'scctcust.CODE02',
            'DESC02' => 'scctcust.DESC02',
            'DESC03' => 'scctcust.DESC03',
        ];
        if (isset($sortableColumns[$columnName])) {
            $columnName = $sortableColumns[$columnName];
        } elseif ($columnName && !str_contains($columnName, '.')) {
            $columnName = 'scctbill.' . $columnName;
        }

        $filterQuery = $this->resolveTagihanFilterQuery($request);
        $filter = $request->input('filter', []);

        $whereAny = [
            'scctcust.NMCUST',
            'scctcust.NOCUST',
            'scctcust.NUM2ND',
            'scctcust.DESC02',
            'scctcust.DESC03',
            'scctbill.BILLNM',
        ];

        $select = array_unique(array_merge($whereAny, [
            'scctbill.AA',
            'scctbill.BILLNM',
            'scctbill.BILLAC',
            'scctbill.BILLAM',
            'scctbill.BILLPAID',
            'scctbill.PAYMENTLEFT',
            'scctbill.PAIDST',
            'scctbill.PAIDDT',
            'scctbill.TRANSNO as BILL_TRANSNO',
            'scctbill.BTA',
            'scctbill.FIDBANK',
            'scctcust.CODE02',
            'scctcust.NUM2ND',
            'scctbill.CUSTID',

        ]));

        $query = scctbill::join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
            ->select($select)
            ->selectRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) AS FUrutan')
            ->where(function ($q) {
                $q->whereNull('scctbill.PAIDST')
                    ->orWhere('scctbill.PAIDST', '<>', 1);
            })
            ->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1')
            ->where('scctbill.FSTSBolehBayar', 1)
            ->when(!blank($searchValue), function ($query) use ($whereAny, $searchValue) {
                $query->where(function ($q) use ($whereAny, $searchValue) {
                    $sanitizeSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchValue);
                    foreach ($whereAny as $column) {
                        $q->orWhere($column, 'like', '%' . $sanitizeSearch . '%');
                    }
                });
            })
            ->where(function ($query) use ($filterQuery) {
                if ($filterQuery) {
                    $filterQuery($query);
                }
            });

        $this->applyUnitScope($query);

        $totalRecords = $this->total();

        $totalRecordswithFilter = Cache::remember(
            CacheHandler::cacheKey($this->cacheKey, 'total_records_with_filter', $filter, $searchValue),
            now()->addMinutes(10),
            fn() => (clone $query)->count()
        );

        $cacheKey = CacheHandler::cacheKey($this->cacheKey, 'sum_tagihan', $filter, $searchValue);

        $totalTagihan =
            Cache::remember(
                $cacheKey,
                now()->addMinutes(10),
                fn() => (clone $query)->sum('PAYMENTLEFT')
            );

        $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
        $recordsQuery = clone $query;

        if ($userOrdered) {
            $dir = $columnSortOrder === 'desc' ? 'DESC' : 'ASC';
            if ($columnName === 'scctbill.FUrutan') {
                $recordsQuery->orderByRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) ' . $dir);
            } elseif ($columnName === 'scctbill.BILLAM') {
                $recordsQuery->orderByRaw('CAST(COALESCE(scctbill.BILLAM, 0) AS DECIMAL(18,2)) ' . $dir);
            } elseif ($columnName === 'scctbill.PAYMENTLEFT') {
                $recordsQuery->orderByRaw('CAST(COALESCE(scctbill.PAYMENTLEFT, 0) AS DECIMAL(18,2)) ' . $dir);
            } elseif ($columnName === 'scctbill.BILLPAID') {
                $recordsQuery->orderByRaw('CAST(COALESCE(scctbill.BILLPAID, 0) AS DECIMAL(18,2)) ' . $dir);
            } else {
                $recordsQuery->orderBy($columnName, $columnSortOrder);
            }
            $recordsQuery
                ->orderBy('scctcust.NOCUST', 'asc')
                ->orderBy('scctbill.AA', 'asc');
        } else {
            $recordsQuery
                ->orderBy('scctbill.BILLAC')
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
                ->orderByRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) ASC')
                ->orderBy('scctcust.NOCUST', 'asc');
        }

        $records = $recordsQuery
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->map(function ($item, $index) {
                $row = $item->toArray();
                $get = static fn (string $key) => $row[$key] ?? $row[strtolower($key)] ?? null;

                $nocust = $get('NOCUST');
                $num2nd = $get('NUM2ND');
                $furutan = $get('FUrutan');

                return [
                    'AA' => $get('AA'),
                    'item_id' => $get('AA'),
                    'CUSTID' => $get('CUSTID'),
                    'NOCUST' => ($nocust && $nocust !== '-') ? $nocust : null,
                    'NUM2ND' => ($num2nd && $num2nd !== '-') ? $num2nd : null,
                    'NOVA' => ($nocust && $nocust !== '-') ? scctcust::showVA($nocust) : null,
                    'NMCUST' => $get('NMCUST'),
                    'CODE02' => $get('CODE02'),
                    'DESC02' => $get('DESC02'),
                    'DESC03' => $get('DESC03'),
                    'BILLNM' => $get('BILLNM'),
                    'BILLAM_TOTAL' => $get('BILLAM'),
                    'BILLAM' => $get('PAYMENTLEFT'),
                    'BILLPAID' => $get('BILLPAID'),
                    'PAYMENTLEFT' => $get('PAYMENTLEFT'),
                    'BILLAC' => $get('BILLAC'),
                    'BTA' => $get('BTA'),
                    'PAIDST' => $get('PAIDST'),
                    'PAIDDT' => $get('PAIDDT')
                        ? Carbon::parse($get('PAIDDT'))->format('d-m-Y H:i:s')
                        : null,
                    'FIDBANK' => $get('FIDBANK'),
                    'FUrutan' => ($furutan === null || $furutan === '')
                        ? '0'
                        : (string) (int) $furutan,
                    'detail_trx' => true,
                    'TRX_LOGS' => [],
                    'BILL_TRANSNO' => $get('BILL_TRANSNO'),
                    'print' => true,
                    'delete' => true,
                    'delete_label' => ((int) ($get('BILLPAID') ?? 0)) > 0 ? 'Reversal' : 'Hapus',
                ];
            })
            ->values()
            ->all();
        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
            'totals' => [
                'tagihan' => ['location' => 11, 'value' => $totalTagihan, 'columnType' => 'currency'],
            ]
        );
        return response()->json($response);
    }

    private function getTransactionLogsForBill($custId, $aa, $billTransNo = null, $billName = null): array
    {
        if (blank($aa)) {
            return [];
        }

        try {
            // Relasi utama: sccttran.BILLID = scctbill.AA (sesuai struktur DB).
            $primaryLogs = sccttran::query()
                ->where('BILLID', $aa)
                ->orderBy('TRXDATE', 'desc')
                ->get(['TRXDATE', 'METODE', 'DEBET', 'KREDIT', 'FIDBANK', 'NOREFF', 'TRANSNO']);

            $logsCollection = $primaryLogs;
            if ($logsCollection->isEmpty()) {
                // Fallback untuk data lama yang tidak konsisten pengisian BILLID.
                $logsCollection = sccttran::query()
                    ->where(function ($q) use ($billTransNo, $billName) {
                        if (!blank($billTransNo) && (string) $billTransNo !== '-') {
                            $q->orWhere('TRANSNO', $billTransNo);
                        }
                        if (!blank($billName)) {
                            $q->orWhereRaw('UPPER(TRIM(BILLTARGET)) = UPPER(TRIM(?))', [$billName]);
                        }
                    })
                    ->when(!blank($custId), function ($q) use ($custId) {
                        $q->where('CUSTID', $custId);
                    })
                    ->orderBy('TRXDATE', 'desc')
                    ->get(['TRXDATE', 'METODE', 'DEBET', 'KREDIT', 'FIDBANK', 'NOREFF', 'TRANSNO']);
            }

            return $logsCollection
                ->map(function ($trx) {
                    return [
                        'trxdate' => $trx->TRXDATE
                            ? Carbon::parse($trx->TRXDATE)->format('d-m-Y H:i:s')
                            : null,
                        'metode' => $trx->METODE,
                        'debet' => (int) ($trx->DEBET ?? 0),
                        'kredit' => (int) ($trx->KREDIT ?? 0),
                        'fidbank' => $trx->FIDBANK,
                        'noreff' => $trx->NOREFF,
                        'transno' => $trx->TRANSNO,
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getTransLog($id, Request $request)
    {
        $custId = $request->input('custid');
        $billTransNo = $request->input('bill_transno');
        $billName = $request->input('billnm');

        if (blank($custId)) {
            $bill = scctbill::query()
                ->where('AA', $id)
                ->select(['CUSTID', 'TRANSNO', 'BILLNM'])
                ->first();
            if ($bill) {
                $custId = $bill->CUSTID;
                $billTransNo = $billTransNo ?: $bill->TRANSNO;
                $billName = $billName ?: $bill->BILLNM;
            }
        }

        // Paksa relasi utama berdasarkan AA/BILLID; custid hanya pembantu jika ada.
        $logs = $this->getTransactionLogsForBill($custId, $id, $billTransNo, $billName);

        if (empty($logs)) {
            $logs = sccttran::query()
                ->where('BILLID', $id)
                ->orderBy('TRXDATE', 'desc')
                ->get(['TRXDATE', 'METODE', 'DEBET', 'KREDIT', 'FIDBANK', 'NOREFF', 'TRANSNO'])
                ->map(function ($trx) {
                    return [
                        'trxdate' => $trx->TRXDATE
                            ? Carbon::parse($trx->TRXDATE)->format('d-m-Y H:i:s')
                            : null,
                        'metode' => $trx->METODE,
                        'debet' => (int) ($trx->DEBET ?? 0),
                        'kredit' => (int) ($trx->KREDIT ?? 0),
                        'fidbank' => $trx->FIDBANK,
                        'noreff' => $trx->NOREFF,
                        'transno' => $trx->TRANSNO,
                    ];
                })
                ->values()
                ->all();
        }

        return response()->json(['logs' => $logs], 200);
    }

    public function total(): int
    {
        return Cache::remember(
            "{$this->cacheKey}:total_all_data",
            now()->addMinutes(10),
            function () {
                $query = scctbill::join('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
                    ->where(function ($q) {
                        $q->whereNull('scctbill.PAIDST')
                            ->orWhere('scctbill.PAIDST', '<>', 1);
                    })
                    ->where('scctbill.FSTSBolehBayar', 1)
                    ->whereRaw('CAST(COALESCE(scctcust.STCUST, 0) AS SIGNED) = 1');

                $this->applyUnitScope($query);

                return $query->count();
            }
        );
    }

    private function resolveSelectedBillNames(Request $request): array
    {
        $rawPosts = $request->input('filter.post', []);
        if (!is_array($rawPosts) && !is_null($rawPosts) && $rawPosts !== '') {
            $rawPosts = [$rawPosts];
        }

        if (!is_array($rawPosts)) {
            return [];
        }

        return array_values(array_filter(
            $rawPosts,
            fn ($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'
        ));
    }

    private function resolveTagihanFilterQuery(Request $request): ?\Closure
    {
        $filters = [];

        $filter = FilterHandler::resolveFilters($request->input('filter'), $this->allowedFilters);
        if (!is_array($filter)) {
            $filter = [];
        }

        $postValues = $this->resolveSelectedBillNames($request);
        if (!empty($postValues)) {
            $filter['scctbill.BILLNM'] = $postValues;
        }

        if (!$filter) {
            return null;
        }

        foreach ($filter as $key => $val) {
            switch ($key) {
                case 'scctbill.FTGLTagihan':
                    if (preg_match('/^\d{2}-\d{2}-\d{4} [-\/~] \d{2}-\d{2}-\d{4}$/', $val)) {
                        $val = preg_replace('/[-\/~]/', '-', $val);

                        list($startDate, $endDate) = explode(' - ', $val);
                        $startDate = Carbon::createFromFormat('d-m-Y', $startDate)->startOfDay();
                        $endDate = Carbon::createFromFormat('d-m-Y', $endDate)->endOfDay();
                        if ($startDate && $endDate) {
                            ($key) && $filters[] = [$key, $startDate, $endDate, 'whereBetween'];
                        }
                    }
                    break;
                case 'scctcust.nmcust':
                    $rawVal = trim((string) $val);
                    if ($rawVal === '') {
                        break;
                    }
                    if (is_numeric($rawVal)) {
                        $filters[] = ['scctcust.nocust', 'like', '%' . $rawVal . '%'];
                    } else {
                        $sanitized = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $rawVal);
                        $filters[] = ['scctcust.nmcust', 'like', '%' . $sanitized . '%'];
                    }
                    break;
                case 'scctbill.BILLNM':
                    if (is_array($val)) {
                        $billNames = array_values(array_filter($val, fn($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'));
                        if (!empty($billNames)) {
                            $filters[] = ['scctbill.BILLNM', 'like_any', $billNames];
                        }
                    } else {
                        $name = trim((string) $val);
                        if ($name !== '') {
                            $filters[] = ['scctbill.BILLNM', 'like', '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $name) . '%'];
                        }
                    }
                    break;
                case 'scctbill.BILLAC':
                    $filters[] = ['scctbill.BILLAC', '=', trim((string) $val)];
                    break;
                case 'scctcust.DESC02':
                    $delimiter = str_contains((string) $val, '~~') ? '~~' : '~~';
                    $parts = explode($delimiter, (string) $val);
                    if (count($parts) == 3) {
                        if (!$this->sekolah) {
                            $filters[] = ['scctcust.CODE02', '=', $parts[0]];
                        }
                        $filters[] = ['scctcust.DESC02', '=', $parts[1]];
                        $filters[] = ['scctcust.DESC03', '=', $parts[2]];
                    }
                    break;
                case 'scctcust.CODE02':
                    $unit = trim((string) $val);
                    $filters[] = ['scctcust.CODE02', 'unit_any', $unit];
                    break;
                case 'scctcust.DESC04':
                    $filters[] = ['scctcust.DESC04', '=', $val];
                    break;
                default:
                    ($key) && $filters[] = [$key, '=', $val];
                    break;
            }
        }

        if (empty($filters)) {
            return null;
        }

        return function ($query) use ($filters) {
            foreach ($filters as $filter) {
                if (($filter[0] ?? null) === 'whereRaw') {
                    $query->whereRaw($filter[1], $filter[2] ?? []);
                    continue;
                }
                if (count($filter) === 3 && ($filter[1] ?? null) === 'like_any' && is_array($filter[2] ?? null)) {
                    $query->where(function ($q) use ($filter) {
                        foreach ($filter[2] as $name) {
                            $sanitized = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], (string) $name);
                            $q->orWhere($filter[0], 'like', '%' . $sanitized . '%');
                        }
                    });
                    continue;
                }
                if (count($filter) === 3 && ($filter[1] ?? null) === 'unit_any') {
                    $unit = trim((string) ($filter[2] ?? ''));
                    if ($unit !== '') {
                        $query->where(function ($q) use ($unit) {
                            $q->where('scctcust.CODE01', $unit)
                                ->orWhere('scctcust.CODE02', $unit)
                                ->orWhereRaw('UPPER(TRIM(scctcust.DESC01)) = UPPER(?)', [$unit]);
                        });
                    }
                    continue;
                }
                if (count($filter) === 3) {
                    if (($filter[1] ?? null) === 'in' && is_array($filter[2] ?? null)) {
                        $query->whereIn($filter[0], $filter[2]);
                    } else {
                        $query->where($filter[0], $filter[1], $filter[2]);
                    }
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
