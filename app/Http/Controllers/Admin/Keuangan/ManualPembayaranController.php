<?php

namespace App\Http\Controllers\Admin\Keuangan;

use App\Http\Controllers\Admin\Keuangan\Saldo\SaldoVirtualAccountController;
use App\Http\Controllers\Controller;
use App\Models\mst_tagihan;
use App\Models\scctbill;
use App\Models\scctbill_detail;
use App\Models\scctcust;
use App\Models\sccttran;
use App\Models\User;
use App\Models\ValidationMessage;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;
use function Laravel\Prompts\error;
use function PHPUnit\Framework\throwException;

class ManualPembayaranController extends Controller
{
    public string $title = 'Keuangan';
    public string $mainTitle = 'Manual Pembayaran';
    public string $dataTitle = 'Manual Pembayaran';
    public string $showTitle = 'Detail Manual Pembayaran';

    public string $cacheKey = 'pembayaran_manual';

    public function __construct()
    {
        $this->datasUrl = route('admin.keuangan.manual-pembayaran.get-data');
        $this->columnsUrl = route('admin.keuangan.manual-pembayaran.get-column');
    }

    public function getColumn()
    {
        return [
            [
                'data' => 'item_id',
                'name' => 'no',
                'className' => 'text-center',
                'columnType' => 'checkbox',
                'selectName' => 'tagihan[post]',
                'selectClass' => 'scctbill',
            ],
            ['data' => 'nocust', 'name' => 'NIS', 'searchable' => true, 'orderable' => true],
            ['data' => 'NUM2ND', 'name' => 'NO. DAFTAR', 'searchable' => true, 'orderable' => true],
            ['data' => 'kelas_label', 'name' => 'Kelas', 'searchable' => true, 'orderable' => false],
            ['data' => 'NOVA', 'name' => 'NO. VA', 'searchable' => true, 'orderable' => false, 'columnType' => 'nova_edit'],
            ['data' => 'nmcust', 'name' => 'NAMA', 'searchable' => true, 'orderable' => true],
            ['data' => 'BILLNM', 'name' => 'Nama Tagihan', 'searchable' => true, 'orderable' => true],
            ['data' => 'BILLAC', 'name' => 'Periode', 'searchable' => true, 'orderable' => true, 'columnType' => 'periode'],
            ['data' => 'BILLAM', 'name' => 'Tagihan', 'searchable' => true, 'orderable' => true, 'columnType' => 'currency', 'className' => 'text-end'],
            [
                'data' => null,
                'name' => 'Nominal Bayar',
                'columnType' => 'input',
                'inputType' => 'text',
                'inputClass' => 'form-control bg-body formattedNumber nominal-bayar-input',
                'inputName' => 'tagihan[nominal_bayar][]',
                'inputPlaceholder' => 'nominal bayar',
                'excludeFromSelection' => true,
            ],
        ];
    }

    public function getData(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length");

        if ($request->siswa) {
            $columnName_arr = $request->get('columns');
            $search_arr = $request->get('search');

            $defaultColumn = 'scctbill.created_at';
            $defaultOrder = 'desc';

            $order = $request->get('order', []);
            if (!empty($order)) {
                $columnIndex = (int)($order[0]['column'] ?? -1);
                $columns = $request->get('columns', []);

                if ($columnIndex >= 0 && isset($columns[$columnIndex])) {
                    $columnData = $columns[$columnIndex]['data'] ?? '';
                    if (!in_array($columnData, ['no', 'item_id'], true) && $columnData !== '') {
                        $columnName = $columnData;
                        $columnSortOrder = strtolower($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                    }
                }
            }

            $tahun_pelajaran = data_get($request->input('filter', []), 'tahun_pelajaran');

            $whereAny = [
                'scctcust.nmcust',
                'scctcust.nocust',
            ];

            $select = array_unique(array_merge($whereAny, [
                'scctbill.AA',
                'scctbill.BILLNM',
                'scctbill.BILLAM',
                'scctbill.BILLAC',
                'scctbill.PAIDST',
                'scctbill.PAIDDT',
                'scctbill.BTA',
                'scctbill.FIDBANK',
                'scctbill.FUrutan',
                'scctcust.CUSTID',
                'scctcust.CODE02',
                'scctcust.DESC02',
                'scctcust.DESC03',
                'scctcust.NOCUST',
                'scctcust.NUM2ND',
                'scctcust.NMCUST',
            ]));

            $query = scctbill::leftJoin('scctcust', 'scctcust.CUSTID', 'scctbill.CUSTID')
                ->where('scctbill.CUSTID', $request->siswa)
                ->where('scctbill.PAIDST', '=', 0)
                ->where('scctbill.FSTSBolehBayar', '=', 1)
                ->when($tahun_pelajaran && $tahun_pelajaran != 'all', function ($query) use ($tahun_pelajaran) {
                    return $query->where('scctbill.BTA', '=', $tahun_pelajaran);
                })
            ->groupBy('scctbill.AA');

            $totalRecords = Cache::remember('total_tagihan_manual_bayar', 600, function () use ($query) {
                return $query->select('count(*) as allcount')->count();
            });

            $records = $query
                ->select($select)
                ->orderBy('scctbill.FUrutan', 'asc')
                ->get()
                ->map(function ($item) {
                    $item->item_id = $item->AA;
                    $item->nocust = $item->NOCUST;
                    $item->nmcust = $item->NMCUST;
                    $item->kelas_label = trim(($item->DESC02 ?? '') . ' ' . ($item->DESC03 ?? ''));
                    $nis = $item->NOCUST;
                    if ($nis && $nis !== '-') {
                        $item->NOVA = scctcust::showVA($nis);
                    } elseif ($item->NUM2ND && $item->NUM2ND !== '-') {
                        $item->NOVA = scctcust::showVA($item->NUM2ND);
                    } else {
                        $item->NOVA = '-';
                    }
                    $item->can_cicil = mst_tagihan::canInstallment($item->BILLNM) ? 1 : 0;
                    unset($item->AA);
                    return $item;
                })->toArray();
        }

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords ?? 0,
            "recordsFiltered" => $totalRecords ?? 0,
            "data" => $records ?? [],
        );
        return response()->json($response);
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['showTitle'] = $this->showTitle;
        $data['columnsUrl'] = $this->columnsUrl;
        $data['thn_aka'] = \App\Models\mst_thn_aka::select(['thn_aka'])
            ->whereNotNull('thn_aka')
            ->distinct()
            ->orderBy('thn_aka', 'desc')
            ->get();

        $data['datasUrl'] = $this->datasUrl;
//        $data['thn_aka'] = mst_thn_aka::where('thn_aka', '!=', null)->get();
//        $data['kelas'] = mst_kelas::get();
        $data['tanda_tangan'] = User::getTandaTanganBase64();

        return view('admin.keuangan.manual_pembayaran', $data);
    }

    public function getTagihan(Request $request)
    {
        if (!$request->siswa) {
            return response()->json(['message' => 'Silahkan periksa form anda'], 422);
        }

        $whereAny = [
            'scctcust.nmcust',
            'scctcust.nocust',
        ];

        $select = array_unique([...$whereAny,
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
        ]);

        $tagihan = scctbill::leftJoin('scctbill_detail', function ($join) {
            $join->on('scctbill.BILLCD', '=', 'scctbill_detail.BILLCD')
                ->on('scctbill.CUSTID', '=', 'scctbill_detail.CUSTID');
        })
            ->leftJoin('u_akun', 'u_akun.KodeAkun', 'scctbill_detail.KodePost')
            ->leftJoin('scctcust', 'scctcust.CUSTID', 'scctbill.CUSTID')
            ->where('scctbill.PAIDST', 0)
            ->select($select)
            ->addSelect(
                DB::raw("GROUP_CONCAT(
                            DISTINCT CASE
                                WHEN scctbill.PAIDST = 0 THEN u_akun.NamaAkun
                                ELSE NULL
                            END
                        SEPARATOR ', ') as 'nama_akun'")
            )
            ->where('scctbill.CUSTID', $request->siswa)
            ->orderBy('scctbill.PAIDST', 'asc')
            ->orderBy('scctbill.FUrutan', 'asc')
            ->get();
        return response()->json($tagihan);
    }

    public function store(Request $request)
    {
        Log::info('manual-pembayaran.store.start', [
            'route' => $request->path(),
            'user_id' => optional(Auth::user())->id,
            'siswa' => $request->input('siswa'),
            'bank' => $request->input('bank'),
            'tanggal' => $request->input('tanggal'),
            'post_count' => count((array)data_get($request->all(), 'tagihan.post', [])),
            'nominal_count' => count((array)data_get($request->all(), 'tagihan.nominal_bayar', [])),
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'tanggal' => ['required', 'regex:/^\d{2}-\d{2}-\d{4}$/'],
                'siswa' => ['required'],
                'bank' => ['required', 'in:1140000,1140001,1140002,1140003,1140004,1140005,1200001,1200002'],
                'tagihan.post' => ['required', 'array', 'min:1'],
                'tagihan.post.*' => ['required'],
                'tagihan.nominal_bayar' => ['required', 'array', 'min:1'],
                'tagihan.nominal_bayar.*' => ['required', 'regex:/^[0-9]+(\.[0-9]{3})*$/']

            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) {
            Log::warning('manual-pembayaran.store.validation_failed', [
                'siswa' => $request->input('siswa'),
                'bank' => $request->input('bank'),
                'errors' => $validator->errors()->toArray(),
            ]);
            if ($validator->errors()->has('tagihan.nominal_bayar.*') || $validator->errors()->has('tagihan.post.*')) {
                return response()->json(['message' => 'Silahkan cek tagihan yang anda pilih,<br> pastikan telah mengisi nominal pembayaran'], 422);
            } else {
                return response()->json(['message' => $validator->errors()->first(), 'error' => $validator->errors()], 422);
            }
        }

        $posts = collect((array)data_get($request->all(), 'tagihan.post', []))
            ->filter(fn($item) => !is_null($item) && $item !== '')
            ->map(fn($item) => is_numeric($item) ? (int)$item : $item)
            ->values()
            ->all();

        if (empty($posts)) {
            return response()->json(['message' => 'Silahkan pilih tagihan yang akan dibayar'], 422);
        }

        $nominalBayar = [];
        $totalBayar = 0;
        foreach ($request->tagihan['nominal_bayar'] as $key => $value) {
            $nominalBayar[$key] = str_replace('.', '', $value);
            $totalBayar += $nominalBayar[$key];
        }

        $siswa = scctcust::where('CUSTID', $request->siswa)->first();
        if (!$siswa) {
            Log::warning('manual-pembayaran.store.siswa_not_found', [
                'siswa' => $request->input('siswa'),
            ]);
            return response()->json(['message' => 'Siswa tidak ditemukan'], 422);
        }

        $tagihans = scctbill::whereIn('AA', $posts)
            ->where('PAIDST', '=', 0)
            ->where('FSTSBolehBayar', '=', 1)
            ->orderBy('FUrutan', 'asc')
            ->get();

        $queriedIds = $tagihans->pluck('AA')->toArray();
        $missingIds = array_diff($posts, $queriedIds);
        if (!empty($missingIds)) {
            Log::warning('manual-pembayaran.store.tagihan_missing', [
                'siswa' => $request->input('siswa'),
                'missing_count' => count($missingIds),
                'missing_ids' => array_values($missingIds),
            ]);
            return response()->json([
                'error' => 'Tagihan tidak ditemukan, silahkan coba tekan tombol cari untuk memuat ulang tagihan yang ada',
            ], 422);
        }
        $tagihanForPrint = [];
        $message = 'Tagihan sukses dibayar. <br> Total Bayar : Rp. ' . number_format($totalBayar, 0, ',', '.') . '.<br> Apakah anda ingin mencetak pembayaran tagihan?';

        $dateInput = $request->input('tanggal');
        $datetime = Carbon::createFromFormat('d-m-Y', $dateInput)
            ->setTimeFrom(Carbon::now());
        $formattedDate = $datetime->toDateTimeString();
        try {
            DB::beginTransaction();

            if ($request->bank == '1140002') {
                $newRequest = new Request(['siswa' => $request->siswa]);
                $saldoController = new SaldoVirtualAccountController();
                $saldo = $saldoController->getSaldo($newRequest);
                if ($saldo < $totalBayar) {
                    DB::rollBack();
                    return response()->json(['message' => 'Saldo siswa kurang.<br> saldo: Rp.' . $saldo], 422);
                }
                $sisaSaldo = $saldo - $totalBayar;
                $message = 'Tagihan sukses dibayar.
                                <br> Total Bayar: Rp. ' . number_format($totalBayar, 0, ',', '.') . '.
                                <br> Sisa saldo: Rp. ' . number_format($sisaSaldo, 0, ',', '.') . '.
                                <br> apakah anda ingin mencetak pembayaran tagihan?';
            }

            $transno = null;
            if (\in_array($request->input('bank'), ['1140000', '1140001', '1140003', '1200001', '1200002'])) {
                $last_date = DB::table('ref_invoicedate')->select(['last_date', 'last_number'])->first();
                if (!$last_date) {
                    DB::table('ref_invoicedate')->insert([
                        'last_date' => date('Y-m-d'),
                        'last_number' => 0,
                        'idx_static' => 1,
                    ]);
                    $last_date = DB::table('ref_invoicedate')
                        ->select(['last_date', 'last_number'])
                        ->where('idx_static', '=', 1)
                        ->first();
                } else {
                    if (!Carbon::parse($last_date->last_date)->isSameDay(Carbon::now())) {
                        DB::table('ref_invoicedate')
                            ->where('idx_static', '=', 1)
                            ->update([
                                'last_date' => date('Y-m-d'),
                                'last_number' => 0
                            ]);
                        $last_date = DB::table('ref_invoicedate')
                            ->select(['last_date', 'last_number'])
                            ->where('idx_static', '=', 1)
                            ->first();
                    }
                }

                $lastNumber = (int)($last_date->last_number ?? 0);
                $transno = date('Ym') . substr(config('app.nova'), 5, 2) . substr($request->input('bank'), -2) . date('d') . $lastNumber;
            }

            foreach ($tagihans as $item) {
                $tagihanForPrint[] = $item->AA;
                $keyForSearch = array_search($item->AA, $posts);
                $nominal = intval($nominalBayar[$keyForSearch]);
//                dd($nominal, $item->BILLAM, $item);
                $oldBill = $item->BILLAM;

                if ($nominal <= 0 && $oldBill != 0) {
                    DB::rollBack();
                    return response()->json(['message' => 'Nominal Pembayaran terlalu kecil'], 422);
                }
                if ($item->BILLAM > $nominal && !mst_tagihan::canInstallment($item->BILLNM)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Tagihan {$item->BILLNM} tidak dapat dicicil. Pembayaran harus lunas (Rp. " . number_format($oldBill, 0, ',', '.') . ').',
                    ], 422);
                }

                if ($item->BILLAM > $nominal) {
                    $detailPost = scctbill_detail::where('CUSTID', $item->CUSTID)
                        ->where('BILLCD', $item->BILLCD)->count();

                    if ($detailPost > 1) {
                        DB::rollBack();
                        return response()->json(['message' => "Tagihan {$item->BILLNM} memiliki beberapa POST dan tidak dapat dicicil!"], 422);
                    }
                }else if($item->BILLAM < $nominal){
                    DB::rollBack();
                    $nominalTagihan = 'Rp. '. number_format($item->BILLAM,0,',','.');
                    $nominalPembayaran = 'Rp. '. number_format($nominal,0,',','.');
                    return response()->json(['message' => "Nominal Pembayaran untuk tagihan terlalu besar! <br>
                            Tagihan: {$nominalTagihan} <br>
                            Nominal Pembayaran: {$nominalPembayaran}
                    "], 422);
                }

                if ($oldBill == $nominal) {
                    $item->update([
                        'PAIDST' => 1,
                        'PAIDDT' => $formattedDate,
                        'PAIDDT_ACTUAL' => date('Y-m-d H:i:s'),
                        'FIDBANK' => $request->input('bank'),
                        'PAIDAM' => $item->BILLAM
                    ]);
                } else {
                    $sisa = $oldBill - $nominal;
                    $tagihanSiswaTerbaru = scctbill::where('CUSTID', $item->CUSTID)
                        ->select('CUSTID', 'FUrutan', 'BILLAC', 'BILLCD')
                        ->orderBy('FUrutan', 'DESC')
                        ->first();

                    $scctbillDetail = scctbill_detail::where('CUSTID', $item->CUSTID)->where('BILLCD', $item->BILLCD)->first();
                    scctbill_detail::where('CUSTID', $item->CUSTID)->where('BILLCD', $item->BILLCD)->update([
                        'BILLAM' => $sisa
                    ]);

                    $item->update([
//                        'PAIDST' => 1,
//                        'PAIDDT' => date('Y-m-d H:i:s'),
//                        'PAIDDT_ACTUAL' => date('Y-m-d H:i:s'),
//                        'FIDBANK' => $request->input('bank'),
                        'BILLAM' => $sisa
                    ]);

                    $urut = $tagihanSiswaTerbaru ? $tagihanSiswaTerbaru['FUrutan'] + 1 : 1;
                    $billCD = date('Y') . '/i' . date('m') . '-' . ($urut + 1);

                    $bill = scctbill::create([
                        'CUSTID' => $item->CUSTID,
                        'BILLAC' => $item->BILLAC,
                        'BILLCD' => $billCD,
                        'BILLNM' => $item->BILLNM,
                        'BILLAM' => $nominal,
                        'PAIDAM' => $nominal,
                        'FUrutan' => $urut,
                        'FTGLTagihan' => now(),
                        'FSTSBolehBayar' => 1,
                        'BTA' => $item->BTA,
                        'PAIDST' => 1,
                        'PAIDDT' => $formattedDate,
                        'PAIDDT_ACTUAL' => date('Y-m-d H:i:s'),
                        'FIDBANK' => $request->input('bank'),
                        'TRANSNO' => $transno
                    ]);

                    $tahun = substr($item->BILLAC, 0, 4);
                    $bulan = substr($item->BILLAC, 4, 2);
                    $billDetail = scctbill_detail::create([
                        'KodePost' => $scctbillDetail->KodePost,
                        'CUSTID' => $bill->CUSTID,
                        'BILLAM' => $bill->BILLAM,
                        'tahun' => $tahun,
                        'periode' => $bulan,
                        'BILLCD' => $billCD,
                    ]);
                }

                $metode = 'FROM SALDO';
                if ($request->bank == '1140002') {
                    sccttran::create([
                        'CUSTID' => $siswa->CUSTID,
                        'NOREFF' => $item->BILLCD,
                        'METODE' => $metode,
                        'FIDBANK' => '1140002',
                        'TRXDATE' => now(),
                        'DEBET' => $nominal,
                        'KREDIT' => 0,
                        'TRANSNO' => Auth::user()->username
                    ]);
                }
            }
            if ($transno) {
                DB::table('ref_invoicedate')
                    ->where('idx_static', '=', 1)
                    ->increment('last_number');
            }
            $request->session()->put('key', 'value');

            $request->session()->forget('siswa_tagihan_baru_dibayar');
            $request->session()->forget('tagihan_baru_dibayar');
            session(['siswa_tagihan_baru_dibayar' => $siswa]);
            session(['tagihan_baru_dibayar' => $tagihanForPrint]);
            DB::commit();
            Log::info('manual-pembayaran.store.success', [
                'siswa' => $request->input('siswa'),
                'bank' => $request->input('bank'),
                'total_bayar' => $totalBayar,
                'tagihan_terbayar' => count($tagihanForPrint),
            ]);
            return response()->json(['message' => $message], 200);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('manual-pembayaran.store.query_exception', [
                'siswa' => $request->input('siswa'),
                'bank' => $request->input('bank'),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return response()->json(['message' => 'Tagihan gagal dibayar', 'error' => $e], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('manual-pembayaran.store.unhandled_exception', [
                'siswa' => $request->input('siswa'),
                'bank' => $request->input('bank'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => config('app.debug') ? ('Tagihan gagal dibayar: ' . $e->getMessage()) : 'Tagihan gagal dibayar'
            ], 422);
        }
    }

    public function cetakPembayaran(Request $request)
    {
        if ($request->session()->has('tagihan_baru_dibayar')) {
            $tagihanForPrint = $request->session()->get('tagihan_baru_dibayar');
            $cust = $request->session()->get('siswa_tagihan_baru_dibayar')->CUSTID;
            $whereAny = [
                'scctcust.NMCUST',
                'scctcust.NOCUST',
                'scctcust.NUM2ND',
            ];

            $select = array_merge($whereAny, [
                'scctcust.CODE02',
                'scctcust.DESC02',
                'scctcust.DESC03',
                'scctcust.DESC04',
                'scctcust.GENUS',
            ]);

            $siswa = scctcust::where('CUSTID', $cust)->select($select)->first();

            $tagihans = scctbill::whereIn('AA', $tagihanForPrint)->where('CUSTID', $cust)->get();
            if ($tagihans->isEmpty()) {
                return response()->json(['message' => 'Tagihan Tidak Ditemukan'], 422);
            }

            if ($request->boolean('pdf')) {
                $fIdBank = $tagihans->first()->FIDBANK ?? null;
//                $biayaLayanan = ($fIdBank === '1140002') ? 0 : 2000;
                $receiptMode = strtolower((string)$request->query('receipt_mode', 'manual'));
                $isNisLikeReceipt = $receiptMode === 'nis';

                $viewName = $isNisLikeReceipt ? 'pdf.kuitansi_with_2000' : 'pdf.kuitansi';
                $viewData = [
                    'tagihans' => $tagihans,
                    'siswa' => $siswa,
                    'nis' => $request->boolean('no_daftar'),
                    'biayaLayanan' => 0,
                ];

                $pdf = Pdf::loadView($viewName, $viewData);

                return $pdf->stream('bukti-pembayaran.pdf');
            }

            $fIdBank = $tagihans->first()->FIDBANK ?? null;
            $biayaLayanan = 0;

            return response()->json([
                'tagihans' => $tagihans,
                'siswa' => $siswa,
                'biaya_layanan' => $biayaLayanan,
            ], 200);
        } else {
            return response()->json(['message' => 'Silakhan Lakukan pembayaran terlebih dahulu'], 422);
        }
    }

    public function cetakTagihan(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'tanggal' => ['required'],
                'siswa' => ['required'],
//                'bank' => ['required', 'in:1140000,1140001,1140002,1140003,1140004,1140005,1200001,1200002'],
                'tagihan.post' => ['required', 'array', 'min:1'],
                'tagihan.post.*' => ['required'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );
        if ($validator->fails()) return response()->json(['message' => 'Silahkan periksa form anda', 'error' => $validator->errors()], 422);


        $whereAny = [
            'scctcust.NMCUST',
            'scctcust.NOCUST',
            'scctcust.NUM2ND',
        ];

        $select = array_unique(array_merge($whereAny, [
            'scctcust.CODE02',
            'scctcust.DESC02',
            'scctcust.DESC03',
            'scctcust.DESC04',

        ]));

        $posts = collect((array)data_get($request->all(), 'tagihan.post', []))
            ->filter(fn($item) => !is_null($item) && $item !== '')
            ->map(fn($item) => is_numeric($item) ? (int)$item : $item)
            ->values()
            ->all();

        if (empty($posts)) {
            return response()->json(['message' => 'Silahkan pilih tagihan yang akan dipratinjau'], 422);
        }

        $siswa = scctcust::where('scctcust.CUSTID', $request->siswa)
            ->select($select)
            ->first();

        if (!$siswa) return response()->json(['message' => 'Siswa tidak ditemukan'], 422);
        try {
            $tagihans = scctbill::leftJoin('scctcust', 'scctcust.CUSTID', 'scctbill.CUSTID')
                ->where('scctbill.CUSTID', $request->siswa)
                ->whereIn('scctbill.AA', $posts)
                ->select(['scctbill.AA',
                    'scctbill.BILLNM',
                    'scctbill.BILLAM',
                    'scctbill.BILLAC',
                    'scctbill.PAIDST',
                    'scctbill.PAIDDT',
                    'scctbill.BTA',
                    'scctbill.FIDBANK',
                    'scctbill.FUrutan',])
                ->get();

            if (!$tagihans) return response()->json(['message' => 'Tagihan Tidak Ditemukan'], 422);
            $isPreviewNoDaftar = filter_var($request->input('preview_by_nodaftar', false), FILTER_VALIDATE_BOOLEAN);
            $pdf = Pdf::loadView('export.tagihan_manual', [
                'tagihans' => $tagihans,
                'siswa' => $siswa,
                'nis' => $isPreviewNoDaftar,
            ]);
            return $pdf->download('tagihan-siswa.pdf');
        } catch (Throwable $e) {
            report($e);
            $message = config('app.debug')
                ? ('Gagal membuat pratinjau: ' . $e->getMessage())
                : 'Tagihan Tidak Ditemukan';

            return response()->json(['message' => $message], 422);
        }
    }

    public function updateNocust(Request $request)
    {
        $request->validate([
            'custid' => ['required'],
            'nocust' => ['required', 'regex:/^[0-9]+$/'],
        ], ValidationMessage::messages(), ValidationMessage::attributes());

        $siswa = scctcust::where('CUSTID', $request->custid)->first();
        if (!$siswa) {
            return response()->json(['message' => 'Siswa tidak ditemukan'], 422);
        }

        $nocust = trim((string) $request->nocust);
        $exists = scctcust::where('NOCUST', $nocust)
            ->where('CUSTID', '!=', $siswa->CUSTID)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'NIS / nomor VA sudah digunakan siswa lain'], 422);
        }

        try {
            $siswa->NOCUST = $nocust;
            $siswa->save();

            return response()->json([
                'message' => 'Nomor VA berhasil diperbarui',
                'nocust' => $nocust,
                'nova' => scctcust::showVA($nocust),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal memperbarui nomor VA', 'error' => $e->getMessage()], 422);
        }
    }
}
