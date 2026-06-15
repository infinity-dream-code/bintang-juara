<?php

namespace App\Http\Controllers\Admin\Keuangan\PenerimaanSiswa;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_sekolah;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\u_akun;
use App\Models\scctbill;
use App\Models\scctbill_detail;
use App\Models\scctcust;
use App\Models\User;
use App\Support\CacheHandler;
use App\Support\FilterHandler;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataPenerimaanController extends Controller
{
    public ?string $sekolah = null;
    private string $title = 'Keuangan';
    private string $mainTitle = 'Data Tagihan Lunas';
    private string $cacheKey = 'data penerimaan';
    private array $allowedFilters = [
        'dari_tanggal' => 'scctbill.PAIDDT_start',
        'sampai_tanggal' => 'scctbill.PAIDDT_end',
        'tahun_akademik' => 'scctbill.BTA',
        'post' => 'scctbill.BILLNM',
        'kelas' => 'scctcust.DESC02',
        'nama' => 'scctcust.NMCUST',
        'nis' => 'scctcust.NOCUST',
        'sekolah' => 'scctcust.CODE01',
        'angkatan' => 'scctcust.DESC04',
        'periode_mulai' => 'scctbill.BILLAC_start',
        'periode_akhir' => 'scctbill.BILLAC_end',
        'bank' => 'scctbill.FIDBANK',
    ];

    public function __construct()
    {
        $key = Str::slug($this->cacheKey) . '_cache_version';

        Cache::add($key, 1);

        $this->middleware(function ($request, $next) {
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = Auth::user();
                $this->sekolah = $user->sekolah;
            }
            return $next($request);
        });
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['columnsUrl'] = $this->columnsUrl();
        $data['datasUrl'] = $this->datasUrl();
        $data['post'] = mst_tagihan::select(['tagihan'])->get();
        $data['thn_aka'] = mst_thn_aka::getMstThnAkaAttributes();
        $data['kelas'] = mst_kelas::getMstKelasAttributes();
        $data['tanda_tangan'] = User::getTandaTanganBase64();
        $data['sekolah'] = mst_sekolah::when($this->sekolah, function ($query) {
            $query->where(function ($q) {
                $q->where("CODE01", $this->sekolah)
                    ->orWhere("DESC01", $this->sekolah);
            });
        })->get();
//        dd($data['tanda_tangan']);
        $scctbillModel = new scctbill();
        $data['bank'] = $scctbillModel->metodeBayar;

        return view('admin.keuangan.penerimaan_siswa.data_penerimaan', $data);
    }

    private function columnsUrl(): string
    {
        return route('admin.keuangan.penerimaan-siswa.data-penerimaan.get-column');
    }

    private function datasUrl(): string
    {
        return route('admin.keuangan.penerimaan-siswa.data-penerimaan.get-data');
    }

    public function getColumn()
    {
        return [
            ['data' => 'AA', 'name' => 'no', 'columnType' => 'row'],
            ['data' => 'nocust', 'name' => 'NIS', 'searchable' => true, 'orderable' => true],
            ['data' => 'nmcust', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true],
            ['data' => 'CODE02', 'name' => 'Unit', 'searchable' => true, 'orderable' => true],
            ['data' => 'DESC02', 'name' => 'Kelas', 'searchable' => true, 'orderable' => true],
            ['data' => 'DESC03', 'name' => 'Kelompok', 'searchable' => true, 'orderable' => true],
            ['data' => 'BILLNM', 'name' => 'Nama Tagihan', 'searchable' => true, 'orderable' => true],
            ['data' => 'BILLAM', 'name' => 'Tagihan', 'searchable' => true, 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end'],
            ['data' => 'FIDBANK', 'name' => 'Metode', 'columnType' => 'custom_code_tagihan', 'searchable' => true, 'orderable' => true],
            ['data' => 'PAIDDT', 'name' => 'Tanggal Bayar', 'columnType' => 'timestamp', 'searchable' => true, 'orderable' => true],
            ['data' => 'BTA', 'name' => 'Tahun AKA', 'searchable' => true, 'orderable' => true],
            [
                'data' => 'delete',
                'name' => '',
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center',
                'button' => 'action',
                'buttonText' => 'Batal',
                'buttonClass' => 'btn btn-sm btn-danger btn-batal-bayar',
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

        $columnName = "scctbill.PAIDDT";
        $columnSortOrder = "desc";

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]["column"] ?? null;
            if (
                $columnIndex !== null &&
                !empty($columnName_arr[$columnIndex]["data"]) &&
                $columnName_arr[$columnIndex]["data"] !== "no" &&
                $columnName_arr[$columnIndex]["data"] !== "AA"
            ) {
                $columnName = $columnName_arr[$columnIndex]["data"];
                $columnSortOrder = $order_arr[0]["dir"] ?? "desc";
            }
        }

        $filters = [];
        $filterQuery = null;

        $filter = FilterHandler::resolveFilters($request->input('filter'), $this->allowedFilters);
        if ($this->sekolah !== null) {
            $filter = array_merge($filter, [
                'scctcust.CODE01' => $this->sekolah,
            ]);
        }

        if ($filter) {
            foreach ($filter as $key => $val) {
                switch ($key) {
                    case 'scctbill.PAIDDT_start':
                        $date = Carbon::createFromFormat('d-m-Y', $val)->startOfDay();
                        if ($date) {
                            $filters[] = ['scctbill.PAIDDT', '>=', $date];
                        }
                        break;
                    case 'scctbill.PAIDDT_end':
                        // Gunakan akhir hari agar filter tanggal yang sama tetap terbaca.
                        $date = Carbon::createFromFormat('d-m-Y', $val)->endOfDay();
                        if ($date) {
                            $filters[] = ['scctbill.PAIDDT', '<=', $date];
                        }
                        break;
                    case 'scctbill.BILLAC_start':
                        $filters[] = ['scctbill.BILLAC', '>=', $val];
                        break;
                    case 'scctbill.BILLAC_end':
                        $filters[] = ['scctbill.BILLAC', '<=', $val];
                        break;
                    case 'scctcust.DESC02':
                        $val = explode("~~", $val);
                        if (count($val) == 3) {
                            $filters[] = ['scctcust.CODE02', '=', $val[0]];
                            $filters[] = ['scctcust.DESC02', '=', $val[1]];
                            $filters[] = ['scctcust.DESC03', '=', $val[2]];
                        }
                        break;
                    case 'scctcust.nmcust':
                        $val = is_numeric($val) ? $val : '%' . $val . '%';
                        $colName = is_numeric($val) ? 'scctcust.NOCUST' : $key;
                        ($colName) && $filters[] = [$colName, 'like', $val];
                        break;
                    default:
                        ($key) && $filters[] = [$key, '=', $val];
                        break;
                }
            };

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

        $whereAny = ['scctcust.nmcust',
            'scctcust.nocust',];

        $select = array_merge($whereAny, [
            'scctbill.AA',
            'scctbill.CUSTID',
            'scctbill.BILLNM',
            'scctbill.BILLAM',
            'scctbill.PAIDST',
            'scctbill.PAIDDT',
            'scctbill.BTA',
            'scctbill.FIDBANK',
            'scctbill.FUrutan',
            'scctcust.CODE02',
            'scctcust.DESC02',
            'scctcust.DESC03',
            'scctcust.NUM2ND',
            'scctcust.GENUS',
            DB::raw('NULL as GENUS1'),
        ]);

        $query = scctbill::leftJoin('scctcust', 'scctcust.CUSTID', 'scctbill.CUSTID')
            ->where('scctbill.PAIDST', 1)
            ->where('scctbill.PAIDDT', '!=', null)
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

        $cacheKey = CacheHandler::cacheKey($this->cacheKey, 'data_penerimaan_count', $filter, $searchValue ?? '');

        $totalRecords = $this->total();

        $totalRecordswithFilter =
            Cache::remember(
                $cacheKey,
                now()->addMinutes(10),
                fn() => (clone $query)->count()
            );

        $records = (clone $query)->orderBy($columnName, $columnSortOrder)
            ->select($select)
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->map(function ($item, $index) {
                $item->item_id = $item->AA;
                $item->delete = true;
                return $item;
            })->toArray();

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecordswithFilter ?? 0,
            "data" => $records ?? [],
        );
        return response()->json($response);
    }

    public function total(): int
    {
        $key = Str::slug($this->cacheKey);
        return Cache::remember(
            "{$key}:total_all_data",
            now()->addMinutes(10),
            fn() => scctbill::where('PAIDST', 1)
                ->where('PAIDDT', '!=', null)
                ->count()
        );
    }

    public function destroy($id, Request $request)
    {
        $custId = $request->input('user_id') ?? $request->input('custid');

        $tagihan = scctbill::where('AA', $id)
            ->where('PAIDST', '=', 1)
            ->when($custId, fn ($q) => $q->where('CUSTID', $custId))
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        $custId = (string) $tagihan->CUSTID;
        $aa = (string) $tagihan->AA;
        $username = (string) (Auth::user()->username ?? Auth::id() ?? 'system');
        $fidBank = (string) ($tagihan->FIDBANK ?? '');

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();

            if ($this->isCashPaymentMethod($fidBank)) {
                $tagihan->update([
                    'PAIDST' => 0,
                    'PAIDDT' => null,
                    'PAIDDT_ACTUAL' => null,
                ]);
            } else {
                DB::connection('DATA_MYSQL')->select(
                    'CALL CancelPaymentSaldo(?, ?, ?)',
                    [$custId, $aa, $username]
                );
            }

            Cache::increment(Str::slug($this->cacheKey) . '_cache_version');
            DB::connection('DATA_MYSQL')->commit();

            return response()->json(['message' => 'Pembayaran tagihan dibatalkan!'], 200);
        } catch (\Throwable $e) {
            DB::connection('DATA_MYSQL')->rollBack();

            return response()->json([
                'message' => 'Pembatalan pembayaran tagihan gagal dilakukan!',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }

    /** Pembayaran tunai / loket — cukup reset status lunas di scctbill. */
    private function isCashPaymentMethod(?string $fidBank): bool
    {
        return in_array((string) $fidBank, ['1140000', '1200001', '1200002'], true);
    }

    public function cetak(Request $request)
    {
        ini_set('max_execution_time', 300);
//        $pdf = Pdf::loadView('cetak.data-penerimaan')->setPaper('a4', 'landscape');
//        return $pdf->download('rekap-tagihan.pdf');

        try {
            $filters = [];
            $filterQuery = null;

            $filter = $request->input('filter');
            if ($filter) {
                if (isset($request->filter['dari_tanggal']) && $request->filter['dari_tanggal'] != null
                    && preg_match('/^\d{2}-\d{2}-\d{4}$/', $request->filter['dari_tanggal']) &&
                    isset($request->filter['sampai_tanggal']) && $request->filter['sampai_tanggal'] != null
                    && preg_match('/^\d{2}-\d{2}-\d{4}$/', $request->filter['sampai_tanggal'])
                ) {
                    foreach ($filter as $key => $val) {
                        if (strtolower($val) != 'all' && $val !== null && $val !== '') {
                            $colName = match ($key) {
                                'dari_tanggal', 'sampai_tanggal' => 'scctbill.PAIDDT',
                                'tahun_akademik' => 'scctbill.BTA',
                                'post' => 'scctbill.BILLNM',
                                'kelas' => 'scctcust.DESC02',
                                'nama' => 'scctcust.NMCUST',
                                'nis' => 'scctcust.NOCUST',
                                'sekolah' => 'scctcust.CODE01',
                                'angkatan' => 'scctcust.DESC04',
                                'periode_mulai', 'periode_akhir' => 'scctbill.BILLAC',
                                default => null
                            };
                            if (in_array($key, ['dari_tanggal', 'sampai_tanggal']) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $val)) {
                                if ($key == 'dari_tanggal') {
                                    $date = Carbon::createFromFormat('d-m-Y', $val)->startOfDay();
                                } else {
                                    $date = Carbon::createFromFormat('d-m-Y', $val)->endOfDay();
                                }

                                if ($date && $colName) {
                                    $operator = $key === 'dari_tanggal' ? '>=' : '<=';
                                    $filters[] = [$colName, $operator, $date];
                                }
                            } elseif (in_array($key, ['periode_mulai', 'periode_akhir']) && preg_match('/^\d{6}$/', $val)) {
                                $operator = $key === 'periode_mulai' ? '>=' : '<=';
                                $filters[] = [$colName, $operator, $val];
                            } elseif (in_array($key, ['nama', 'nis'])) {
                                ($colName) && $filters[] = [$colName, 'like', '%' . $val . '%'];
                            } elseif ($key === 'kelas') {
                                $parts = explode('~~', (string) $val);
                                if (count($parts) === 3) {
                                    $filters[] = ['scctcust.CODE02', '=', $parts[0]];
                                    $filters[] = ['scctcust.DESC02', '=', $parts[1]];
                                    $filters[] = ['scctcust.DESC03', '=', $parts[2]];
                                }
                            } else {
                                ($colName) && $filters[] = [$colName, '=', $val];
                            }
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
                                ->where('scctbill.PAIDST', 1)
                                ->where('scctbill.PAIDDT', '!=', null)
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
                } else {
                    return response()->json(['message' => 'Tanggal transaksi tidak valid', 'error' => 'Tanggal transaksi tidak valid'], 422);
                }
            }

//            dd($posts[0]['tagihan']);
//            return  view('pdf.data_penerimaan.rekap_penerimaan', ['posts' => $posts]);

//            $view = view('cetak.data-penerimaan', compact('posts'))->render();
//            return response()->json(['html' => $view]);

            if ($posts) {
                $pdf = Pdf::loadView('cetak.data-penerimaan', ['posts' => $posts])->setPaper('a4', 'landscape');
                return $pdf->download('rekap-tagihan.pdf');
            } else {
                return response()->json(['message' => 'Data Kosong'], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Tidak dapat mencetak rekap', 'error' => $e->getMessage(), 'e' => $e], 422);
        }
    }

    public function cetakNew(Request $request)
    {
        try {
            $filter = $request->input('filter', []);
            $dariTanggal = $filter['dari_tanggal'] ?? null;
            $sampaiTanggal = $filter['sampai_tanggal'] ?? null;

            if (
                !$dariTanggal || !$sampaiTanggal ||
                !preg_match('/^\d{2}-\d{2}-\d{4}$/', $dariTanggal) ||
                !preg_match('/^\d{2}-\d{2}-\d{4}$/', $sampaiTanggal)
            ) {
                return response()->json(['message' => 'Tanggal transaksi tidak valid'], 422);
            }

            $tanggalMulai = Carbon::createFromFormat('d-m-Y', $dariTanggal)->startOfDay();
            $tanggalSelesai = Carbon::createFromFormat('d-m-Y', $sampaiTanggal)->endOfDay();

            $query = scctbill_detail::query()
                ->from('scctbill_detail as a')
                ->join('scctbill as b', function ($join) {
                    $join->on('a.BILLCD', '=', 'b.BILLCD')
                        ->on('a.CUSTID', '=', 'b.CUSTID');
                })
                ->join('scctcust as d', 'b.CUSTID', '=', 'd.CUSTID')
                ->leftJoin((new u_akun())->getTable() . ' as c', 'a.KodePost', '=', 'c.KodeAkun')
                ->where('b.PAIDST', 1)
                ->where('b.FSTSBolehBayar', 1)
                ->whereBetween('b.PAIDDT', [$tanggalMulai, $tanggalSelesai])
                ->when(!blank($filter['nis'] ?? null), function ($q) use ($filter) {
                    $nis = trim((string)$filter['nis']);
                    $q->where(function ($sub) use ($nis) {
                        $sub->where('d.NOCUST', 'like', "%{$nis}%")
                            ->orWhere('d.NUM2ND', 'like', "%{$nis}%");
                    });
                })
                ->when(!blank($filter['nama'] ?? null), function ($q) use ($filter) {
                    $q->where('d.NMCUST', 'like', '%' . trim((string)$filter['nama']) . '%');
                })
                ->when(!blank($filter['tahun_akademik'] ?? null) && strtolower((string)$filter['tahun_akademik']) !== 'all', function ($q) use ($filter) {
                    $q->where('b.BTA', trim((string)$filter['tahun_akademik']));
                })
                ->when(!blank($filter['angkatan'] ?? null) && strtolower((string)$filter['angkatan']) !== 'all', function ($q) use ($filter) {
                    $q->where('d.DESC04', trim((string)$filter['angkatan']));
                })
                ->when(!blank($filter['bank'] ?? null) && strtolower((string)$filter['bank']) !== 'all', function ($q) use ($filter) {
                    $q->where('b.FIDBANK', trim((string)$filter['bank']));
                })
                ->when(!blank($filter['post'] ?? null), function ($q) use ($filter) {
                    $post = $filter['post'];
                    if (is_array($post)) {
                        $post = collect($post)->filter(fn($item) => !blank($item) && strtolower((string)$item) !== 'all')->values()->all();
                        if (!empty($post)) {
                            $q->whereIn('b.BILLNM', $post);
                        }
                        return;
                    }
                    if (strtolower((string)$post) !== 'all') {
                        $q->where('b.BILLNM', trim((string)$post));
                    }
                })
                ->when($this->sekolah, function ($q) {
                    $q->where('d.CODE01', $this->sekolah);
                })
                ->when(!blank($filter['sekolah'] ?? null) && strtolower((string)$filter['sekolah']) !== 'all', function ($q) use ($filter) {
                    $q->where('d.CODE01', trim((string)$filter['sekolah']));
                })
                ->when(!blank($filter['kelas'] ?? null) && strtolower((string)$filter['kelas']) !== 'all', function ($q) use ($filter) {
                    $parts = explode('~~', trim((string)$filter['kelas']));
                    if (count($parts) === 3) {
                        $q->where('d.CODE02', $parts[0])
                            ->where('d.DESC02', $parts[1])
                            ->where('d.DESC03', $parts[2]);
                    }
                })
                ->when(!blank($filter['periode_mulai'] ?? null) && preg_match('/^\d{6}$/', (string)$filter['periode_mulai']), function ($q) use ($filter) {
                    $q->where('b.BILLAC', '>=', (string)$filter['periode_mulai']);
                })
                ->when(!blank($filter['periode_akhir'] ?? null) && preg_match('/^\d{6}$/', (string)$filter['periode_akhir']), function ($q) use ($filter) {
                    $q->where('b.BILLAC', '<=', (string)$filter['periode_akhir']);
                })
                ->select([
                    'a.KodePost',
                    DB::raw('SUM(a.BILLAM) as total_tagihan'),
                    DB::raw("CONCAT(d.DESC02, ' ', d.DESC03) as kelas"),
                    'd.DESC03',
                    'd.NOCUST',
                    'd.NMCUST',
                    'b.AA',
                    DB::raw('NULL as GetWisma'),
                    'b.FIDBANK',
                    'c.NamaAkun',
                    DB::raw("DATE_FORMAT(b.PAIDDT, '%Y-%m-%d %H:%i:%s') as PAIDDT"),
                    'd.DESC04 as angkatan',
                    'd.NUM2ND',
                    'b.BILLAC',
                    'b.BILLNM',
                    'b.BTA',
                ])
                ->groupBy([
                    'a.KodePost',
                    'd.DESC02',
                    'd.DESC03',
                    'd.DESC04',
                    'd.NOCUST',
                    'd.NMCUST',
                    'd.NUM2ND',
                    'b.AA',
                    'b.FIDBANK',
                    'c.NamaAkun',
                    'b.PAIDDT',
                    'b.BILLAC',
                    'b.BILLNM',
                    'b.BTA',
                ])
                ->orderBy('b.PAIDDT')
                ->orderBy('a.KodePost');

            $rows = $query->get();
            if ($rows->isEmpty()) {
                return response()->json(['message' => 'Data Kosong'], 422);
            }

            return response()->json(['data' => $rows], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Tidak dapat mencetak rekap', 'error' => $e->getMessage(), 'e' => $e], 422);
        }
    }

    public function cetakPembayaran(Request $request)
    {
        $tagihans = scctbill::where('AA', $request->id_tagihan)->get();
        $siswa = scctcust::where('CISTID', $tagihans[0]->CUSTID)->first();
        if ($siswa && $tagihans) {
            $siswa = $request->session()->get('siswa_tagihan_baru_dibayar');
            $pdf = Pdf::loadView('pdf.kuitansi', ['tagihans' => $tagihans, 'siswa' => $siswa]);
            return $pdf->download('bukti-pembayaran - ' . $siswa->nama . ' - ' . $siswa->nis . '.pdf');
        } else {
            return response()->json(['message' => 'Silakhan Lakukan pembayaran terlebih dahulu'], 422);
        }
    }

    public function cetakKartuSiswa(Request $request)
    {
        $custid = $request->input('custid');
        if (!$custid) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 422);
        }

        $siswa = scctcust::where('CUSTID', $custid)->first();
        if (!$siswa) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 422);
        }

        $tagihans = scctbill::query()
            ->leftJoin('scctcust', 'scctcust.CUSTID', '=', 'scctbill.CUSTID')
            ->where('scctbill.CUSTID', $custid)
            ->where('scctbill.PAIDST', 1)
            ->where('scctbill.FSTSBolehBayar', 1)
            ->whereNotNull('scctbill.PAIDDT')
            ->when(!blank($request->input('filter.tahun_akademik')) && strtolower((string)$request->input('filter.tahun_akademik')) !== 'all', function ($q) use ($request) {
                $q->where('scctbill.BTA', $request->input('filter.tahun_akademik'));
            })
            ->when(!blank($request->input('filter.post')), function ($q) use ($request) {
                $post = $request->input('filter.post');
                if (is_array($post)) {
                    $post = collect($post)->filter(fn($item) => !blank($item) && strtolower((string)$item) !== 'all')->values()->all();
                    if (!empty($post)) {
                        $q->whereIn('scctbill.BILLNM', $post);
                    }
                    return;
                }
                if (strtolower((string)$post) !== 'all') {
                    $q->where('scctbill.BILLNM', $post);
                }
            })
            ->when(!blank($request->input('filter.bank')) && strtolower((string)$request->input('filter.bank')) !== 'all', function ($q) use ($request) {
                $q->where('scctbill.FIDBANK', $request->input('filter.bank'));
            })
            ->when(!blank($request->input('filter.dari_tanggal')) && preg_match('/^\d{2}-\d{2}-\d{4}$/', (string)$request->input('filter.dari_tanggal')), function ($q) use ($request) {
                $startDate = Carbon::createFromFormat('d-m-Y', (string)$request->input('filter.dari_tanggal'))->startOfDay();
                $q->where('scctbill.PAIDDT', '>=', $startDate);
            })
            ->when(!blank($request->input('filter.sampai_tanggal')) && preg_match('/^\d{2}-\d{2}-\d{4}$/', (string)$request->input('filter.sampai_tanggal')), function ($q) use ($request) {
                $endDate = Carbon::createFromFormat('d-m-Y', (string)$request->input('filter.sampai_tanggal'))->endOfDay();
                $q->where('scctbill.PAIDDT', '<=', $endDate);
            })
            ->select([
                'scctbill.BILLNM',
                'scctbill.BILLAC',
                'scctbill.BILLAM',
                'scctbill.BTA',
                'scctbill.PAIDDT',
                'scctbill.PAIDST',
                'scctbill.FIDBANK',
                'scctbill.FUrutan',
            ])
            ->orderBy('scctbill.FUrutan', 'asc')
            ->orderBy('scctbill.PAIDDT', 'desc')
            ->get()
            ->values();

        if ($tagihans->isEmpty()) {
            return response()->json(['message' => 'Data pembayaran tidak ditemukan'], 422);
        }

        return response()->json([
            'siswa' => $siswa,
            'tagihans' => $tagihans,
        ]);
    }
}
