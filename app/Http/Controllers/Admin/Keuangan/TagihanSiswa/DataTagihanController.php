<?php

namespace App\Http\Controllers\Admin\Keuangan\TagihanSiswa;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctcust;
use App\Models\User;
use App\Models\ValidationMessage;
use App\Support\CacheHandler;
use App\Support\FilterHandler;
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
        'tahun_akademik' => 'scctbill.BTA',
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

    public function getColumn()
    {
        return [
            ['data' => 'AA', 'name' => 'no', 'columnType' => 'row', 'exportable' => true],
            ['data' => 'NOCUST', 'name' => 'NIS', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'NUM2ND', 'name' => 'NO DAFT', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'NOVA', 'name' => 'NO VA', 'exportable' => true],
            ['data' => 'NMCUST', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'CODE02', 'name' => 'Unit', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'DESC02', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'DESC03', 'name' => 'Kelompok', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'BILLNM', 'name' => 'Nama Tagihan', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            ['data' => 'BILLAM', 'name' => 'Tagihan', 'searchable' => true, 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end', 'exportable' => true],
            ['data' => 'BTA', 'name' => 'Tahun AKA', 'searchable' => true, 'orderable' => true, 'exportable' => true],
            [
                'data' => 'FUrutan',
                'name' => 'Urutan',
                'searchable' => true,
                'orderable' => true,
                'exportable' => true,
                'duplicate' => false,
            ],
            [
                'data' => 'naik',
                'name' => 'Naik',
                'orderable' => false,
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center',
                'button' => 'action',
                'buttonText' => 'Naikkan',
                'buttonClass' => 'btn btn-sm btn-secondary btn-naik-urut',
                'buttonLink' => '#modal-delete',
                'buttonIcon' => 'ri-arrow-up-line me-2',
                'exportable' => false,
                'duplicate' => false,
            ],
            [
                'data' => 'turun',
                'name' => 'Turun',
                'orderable' => false,
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center',
                'button' => 'action',
                'buttonText' => 'Turunkan',
                'buttonClass' => 'btn btn-sm btn-secondary btn-turun-urut',
                'buttonLink' => '#modal-delete',
                'buttonIcon' => 'ri-arrow-down-line me-2',
                'exportable' => false,
                'duplicate' => false,
            ],
            [
                'data' => 'delete',
                'name' => '',
                'orderable' => false,
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center',
                'button' => 'action',
                'buttonText' => 'Hapus',
                'buttonClass' => 'btn btn-sm btn-danger btn-hapus',
                'buttonLink' => '#modal-delete',
                'buttonIcon' => 'ri-delete-bin-line me-2',
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
            } else {
                return response()->json(['message' => $validator->errors()->first(), 'error' => $validator->errors()], 422);
            }
        }

        $tagihan = scctbill::where('AA', $id)
            ->where('FSTSBolehBayar', '=', 1)
            ->where('PAIDST', '=', 0)
            ->first();
        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        $currentUrut = (int) ($tagihan->FUrutan ?? 0);
        if ($currentUrut <= 0) {
            return response()->json([
                'message' => 'Tagihan dengan urutan 0 tidak dapat dinaikkan atau diturunkan.',
            ], 422);
        }

        if ($request->urutan_tagihan === 'naik') {
            $targetUrut = $currentUrut + 1;
        } else {
            $targetUrut = $currentUrut - 1;
            if ($targetUrut <= 0) {
                return response()->json([
                    'message' => 'Urutan 1 tidak dapat ditukar ke urutan 0.',
                ], 422);
            }
        }

        if (!$this->furutanSlotExists($targetUrut)) {
            $pesan = $request->urutan_tagihan === 'naik'
                ? "Tidak ada tagihan dengan urutan {$targetUrut}. Urutan sudah paling atas."
                : "Tidak ada tagihan dengan urutan {$targetUrut}.";

            return response()->json(['message' => $pesan], 422);
        }

        try {
            DB::beginTransaction();
            $this->swapFUrutanSlots($currentUrut, $targetUrut);
            Cache::increment(Str::slug($this->cacheKey) . '_cache_version');
            DB::commit();

            return response()->json([
                'message' => "Urutan {$currentUrut} dan {$targetUrut} berhasil ditukar.",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal mengubah urutan tagihan: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /** Apakah ada tagihan aktif (belum lunas) dengan nilai FUrutan tertentu. */
    private function furutanSlotExists(int $urut): bool
    {
        return scctbill::query()
            ->where('PAIDST', 0)
            ->where('FSTSBolehBayar', 1)
            ->whereRaw('CAST(COALESCE(FUrutan, 0) AS SIGNED) = ?', [$urut])
            ->exists();
    }

    /**
     * Tukar semua tagihan pada slot FUrutan $fromUrut dengan slot $toUrut (global, bukan per baris).
     */
    private function swapFUrutanSlots(int $fromUrut, int $toUrut): void
    {
        $tempUrut = (int) scctbill::query()
            ->where('PAIDST', 0)
            ->where('FSTSBolehBayar', 1)
            ->max('FUrutan') + 10000;

        $applySlot = static function (int $from, int $to) {
            scctbill::query()
                ->where('PAIDST', 0)
                ->where('FSTSBolehBayar', 1)
                ->whereRaw('CAST(COALESCE(FUrutan, 0) AS SIGNED) = ?', [$from])
                ->update(['FUrutan' => $to]);
        };

        $applySlot($fromUrut, $tempUrut);
        $applySlot($toUrut, $fromUrut);
        $applySlot($tempUrut, $toUrut);
    }

    public function cetak(Request $request)
    {
        ini_set('max_execution_time', 300);
//        $pdf = Pdf::loadView('cetak.data-penerimaan')->setPaper('a4', 'landscape');
//        return $pdf->download('rekap-tagihan.pdf');

        try {
            $filters = [];
            $filterQuery = null;

            $filter = FilterHandler::resolveFilters($request->input('filter'), $this->allowedFilters);
            if (!is_array($filter)) {
                $filter = [];
            }

            // Multi-select Nama Tagihan dikirim sebagai filter[post][].
            $rawPosts = $request->input('filter.post', []);
            if (!is_array($rawPosts) && !is_null($rawPosts) && $rawPosts !== '') {
                $rawPosts = [$rawPosts];
            }
            if (is_array($rawPosts)) {
                $postValues = array_values(array_filter($rawPosts, fn($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'));
                if (!empty($postValues)) {
                    $filter['scctbill.BILLNM'] = $postValues;
                }
            }
            if ($filter) {
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
                            $val = is_numeric($val) ? $val : '%' . $val . '%';
                            $colName = is_numeric($val) ? 'scctcust.nocust' : $key;
                            ($colName) && $filters[] = [$colName, 'like', $val];
                            break;
                        case 'scctbill.BILLNM':
                            if (is_array($val)) {
                                $postValues = array_values(array_filter($val, fn($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'));
                                if (!empty($postValues)) {
                                    $filters[] = ['scctbill.BILLNM', 'in', $postValues];
                                }
                            } else {
                                ($key) && $filters[] = [$key, '=', $val];
                            }
                            break;
                        case 'scctbill.BTA':
                            $normalizedVal = str_replace([' ', '-'], ['', '/'], trim((string) $val));
                            $filters[] = [
                                'whereRaw',
                                '(REPLACE(REPLACE(TRIM(scctbill.BTA), " ", ""), "-", "/") = ? OR REPLACE(REPLACE(TRIM(scctcust.DESC04), " ", ""), "-", "/") = ?)',
                                [$normalizedVal, $normalizedVal]
                            ];
                            break;
                        default:
                            ($key) && $filters[] = [$key, '=', $val];
                            break;
                        case 'scctcust.DESC02':
                            $delimiter = str_contains($val, '~~') ? '~~' : ',';
                            $val = explode($delimiter, $val);
                            if (count($val) == 3) {
                                if (!$this->sekolah) {
                                    $filters[] = [
                                        "scctcust.CODE02",
                                        "=",
                                        $val[0],
                                    ];
                                }
                                $filters[] = ['scctcust.DESC02', '=', $val[1]];
                                $filters[] = ['scctcust.DESC03', '=', $val[2]];
                            }
                            break;
                        case 'scctcust.CODE02':
                            $filters[] = ["scctcust.CODE02", "=", $val];
                            break;
                    }
                };

                if ($this->sekolah !== null && $this->sekolah !== '') {
                    $filters[] = ['scctcust.CODE02', '=', $this->sekolah];
                }

                if (!empty($filters)) {
                    $filterQuery = function ($query) use ($filters) {
                        foreach ($filters as $filter) {
                            if (($filter[0] ?? null) === 'whereRaw') {
                                $query->whereRaw($filter[1], $filter[2] ?? []);
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


                $posts = mst_tagihan::select('urut', 'tagihan', 'kode')->get()
                    ->map(function ($item) use ($filterQuery) {
                        $item->tagihans = scctbill::leftJoin('scctcust', 'scctcust.CUSTID', 'scctbill.CUSTID')
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
                            ->where('scctbill.BILLNM', $item->tagihan)
                            ->where('scctbill.PAIDST', 0)
                            ->where(function ($query) use ($filterQuery) {
                                if ($filterQuery) {
                                    $filterQuery($query);
                                }
                            })
                            ->orderBy('scctbill.CUSTID', 'desc')
                            ->orderBy('scctbill.BTA', 'desc')
                            ->orderBy('scctbill.PAIDDT', 'desc')
                            ->get()
                            ->toArray();;

                        return $item;
                    });

            }
            if ($posts) {
                $pdf = Pdf::loadView('cetak.data-tagihan', ['posts' => $posts])->setPaper('a4', 'landscape');
                return $pdf->download('rekap-tagihan.pdf');
            } else {
                return response()->json(['message' => 'Data Kosong'], 422);
            }
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
        $nonSortableData = ['AA', 'naik', 'turun', 'delete', 'print', 'NOVA'];

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
            'BILLAM' => 'scctbill.BILLAM',
            'BILLAC' => 'scctbill.BILLAC',
            'BTA' => 'scctbill.BTA',
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

        $filters = [];
        $filterQuery = null;

        $filter = FilterHandler::resolveFilters($request->input('filter'), $this->allowedFilters);
        if (!is_array($filter)) {
            $filter = [];
        }

        // Multi-select Nama Tagihan dikirim sebagai filter[post][].
        $rawPosts = $request->input('filter.post', []);
        if (!is_array($rawPosts) && !is_null($rawPosts) && $rawPosts !== '') {
            $rawPosts = [$rawPosts];
        }
        if (is_array($rawPosts)) {
            $postValues = array_values(array_filter($rawPosts, fn($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'));
            if (!empty($postValues)) {
                $filter['scctbill.BILLNM'] = $postValues;
            }
        }

        if ($this->sekolah !== null) {
            $filter = array_merge($filter, [
                'scctcust.CODE02' => $this->sekolah,
            ]);
        }

        if ($filter) {
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
                        $val = is_numeric($val) ? $val : '%' . $val . '%';
                        $colName = is_numeric($val) ? 'scctcust.nocust' : $key;
                        ($colName) && $filters[] = [$colName, 'like', $val];
                        break;
                    case 'scctbill.BILLNM':
                        if (is_array($val)) {
                            $postValues = array_values(array_filter($val, fn($item) => !is_null($item) && $item !== '' && strtolower((string) $item) !== 'all'));
                            if (!empty($postValues)) {
                                $filters[] = ['scctbill.BILLNM', 'in', $postValues];
                            }
                        } else {
                            ($key) && $filters[] = [$key, '=', $val];
                        }
                        break;
                    case 'scctbill.BTA':
                        $normalizedVal = str_replace([' ', '-'], ['', '/'], trim((string) $val));
                        $filters[] = [
                            'whereRaw',
                            '(REPLACE(REPLACE(TRIM(scctbill.BTA), " ", ""), "-", "/") = ? OR REPLACE(REPLACE(TRIM(scctcust.DESC04), " ", ""), "-", "/") = ?)',
                            [$normalizedVal, $normalizedVal]
                        ];
                        break;
                    case 'scctcust.DESC02':
                        $val = explode("~~", $val);
                        if (count($val) == 3) {
                            $filters[] = ['scctcust.CODE02', '=', $val[0]];
                            $filters[] = ['scctcust.DESC02', '=', $val[1]];
                            $filters[] = ['scctcust.DESC03', '=', $val[2]];
                        }
                        break;
                    case 'scctcust.CODE02':
                        $filters[] = ["scctcust.CODE02", "=", $val];
                        break;
                    case 'scctcust.DESC04':
                        $filters[] = ['scctcust.DESC04', '=', $val];
                        break;
                    default:
                        ($key) && $filters[] = [$key, '=', $val];
                        break;
                }
            };

            if (!empty($filters)) {
                $filterQuery = function ($query) use ($filters) {
                    foreach ($filters as $filter) {
                        if (($filter[0] ?? null) === 'whereRaw') {
                            $query->whereRaw($filter[1], $filter[2] ?? []);
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

        $whereAny = [
            'scctcust.NMCUST',
            'scctcust.NOCUST',
            'scctcust.DESC02',
            'scctcust.DESC03',
        ];

        $select = array_unique(array_merge($whereAny, [
            'scctbill.AA',
            'scctbill.BILLNM',
            'scctbill.BILLAC',
            'scctbill.BILLAM',
            'scctbill.PAIDST',
            'scctbill.PAIDDT',
            'scctbill.BTA',
            'scctbill.FIDBANK',
            'scctcust.CODE02',
            'scctcust.NUM2ND',
            'scctbill.CUSTID',

        ]));

        $query = scctbill::leftJoin('scctcust', 'scctcust.CUSTID', 'scctbill.CUSTID')
            ->select($select)
            ->selectRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) AS FUrutan')
            ->where('scctbill.PAIDST', 0)
            ->where('scctcust.STCUST', 1)
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
                fn() => (clone $query)->sum('BILLAM')
            );

        $rowperpage = $rowperpage == "poll" ? $totalRecords : $rowperpage;
        $recordsQuery = clone $query;

        if ($userOrdered) {
            $dir = $columnSortOrder === 'desc' ? 'DESC' : 'ASC';
            if ($columnName === 'scctbill.FUrutan') {
                $recordsQuery->orderByRaw('CAST(COALESCE(scctbill.FUrutan, 0) AS SIGNED) ' . $dir);
            } elseif ($columnName === 'scctbill.BILLAM') {
                $recordsQuery->orderByRaw('CAST(COALESCE(scctbill.BILLAM, 0) AS DECIMAL(18,2)) ' . $dir);
            } else {
                $recordsQuery->orderBy($columnName, $columnSortOrder);
            }
            $recordsQuery
                ->orderBy('scctcust.NOCUST', 'asc')
                ->orderBy('scctbill.AA', 'asc');
        } else {
            $recordsQuery
                ->orderBy('scctbill.BTA')
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
                    'BILLAM' => $get('BILLAM'),
                    'BILLAC' => $get('BILLAC'),
                    'BTA' => $get('BTA'),
                    'PAIDST' => $get('PAIDST'),
                    'PAIDDT' => $get('PAIDDT'),
                    'FIDBANK' => $get('FIDBANK'),
                    'FUrutan' => ($furutan === null || $furutan === '')
                        ? '0'
                        : (string) (int) $furutan,
                    'print' => true,
                    'naik' => true,
                    'turun' => true,
                    'delete' => true,
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
                'tagihan' => ['location' => 9, 'value' => $totalTagihan, 'columnType' => 'currency'],
            ]
        );
        return response()->json($response);
    }

    public function total(): int
    {
        return Cache::remember(
            "{$this->cacheKey}:total_all_data",
            now()->addMinutes(10),
            fn() => scctbill::where('scctbill.PAIDST', 0)
                ->where('scctbill.FSTSBolehBayar', 1)
                ->count()
        );
    }
}
