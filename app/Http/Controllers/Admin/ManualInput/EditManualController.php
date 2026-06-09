<?php

namespace App\Http\Controllers\Admin\ManualInput;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
use App\Models\scctbill_detail;
use App\Models\ValidationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EditManualController extends Controller
{
    private string $title;
    private string $mainTitle;

    public function __construct()
    {
        $this->title = 'Manual Input';
        $this->mainTitle = 'Edit Detail Post Manual';
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;

        $data['thn_aka'] = mst_thn_aka::orderBy('thn_aka', 'desc')->get();
        $data['kelas'] = mst_kelas::orderByRaw("CASE WHEN kelas REGEXP '^[0-9]+$' THEN 0 ELSE 1 END, kelas")->get();
        $data['tagihan'] = mst_tagihan::orderBy('urut', 'asc')->get();
        $data['v_dt_daftar_harga'] = DB::table('u_akun')
            ->select([
                'u_daftar_harga.kode_fak AS kode_fak',
                'u_daftar_harga.kode_prod AS kode_prod',
                'u_akun.KodeAkun AS KodeAkun',
                'u_akun.NamaAkun AS NamaAkun',
                'u_daftar_harga.thn_masuk AS thn_masuk',
                'u_daftar_harga.nominal AS nominal'
            ])
            ->leftJoin('u_daftar_harga', 'u_akun.KodeAkun', '=', 'u_daftar_harga.KodeAkun')
            ->groupBy('u_akun.KodeAkun')
            ->get();

        return view('admin.manual_input.edit_manual', $data);
    }

    public function getTagihan(Request $request)
    {
        if (!$request->siswa) {
            return response()->json(['message' => 'Silahkan periksa form anda'], 422);
        }

        $whereAny = [

        ];

        $detailSum = scctbill_detail::query()
            ->select([
                'CUSTID',
                'BILLCD',
                DB::raw('COALESCE(SUM(BILLAM), 0) AS nominal_detail'),
            ])
            ->groupBy('CUSTID', 'BILLCD');

        $select = array_unique(array_merge($whereAny, [
            'scctbill.AA',
            'scctbill.BILLNM',
            'scctbill.PAIDST',
            'scctbill.PAIDDT',
            'scctbill.BTA',
            'scctbill.FIDBANK',
            'scctbill.BILLCD',
            'scctbill.FUrutan',
        ]));

        $tagihan = scctbill::query()
            ->leftJoinSub($detailSum, 'bill_detail', function ($join) {
                $join->on('bill_detail.CUSTID', '=', 'scctbill.CUSTID')
                    ->on('bill_detail.BILLCD', '=', 'scctbill.BILLCD');
            })
            ->select($select)
            ->selectRaw('COALESCE(NULLIF(bill_detail.nominal_detail, 0), scctbill.BILLAM) AS BILLAM')
            ->where('scctbill.CUSTID', $request->siswa)
            ->where('scctbill.FSTSBolehBayar', 1)
            ->orderBy('scctbill.FUrutan', 'asc')
            ->get();

        return response()->json($tagihan);
    }

    public function getDetailTagihan(Request $request)
    {
        if (!$request->tagihan || !$request->siswa) {
            return response()->json(['message' => 'Silahkan periksa form anda'], 422);
        }

        $detailTaighan = scctbill_detail::where('scctbill_detail.BILLCD', $request->tagihan)
            ->where('scctbill_detail.CUSTID', $request->siswa)
            ->leftJoin('u_akun', 'u_akun.KodeAkun', '=', 'scctbill_detail.KodePost')
//            ->leftJoin('v_dt_daftar_harga', 'v_dt_daftar_harga.KodeAkun', 'scctbill_detail.KodePost')
            ->select([
                'u_akun.KodeAkun',
                'scctbill_detail.BILLAM as nominal',
//                'v_dt_daftar_harga.KodeAkun',
                'u_akun.NamaAkun',
            ])
            ->get();

        if (!$detailTaighan) {
            return response()->json(['message' => 'Tagihan ini tidak memiliki detail'], 422);
        }
        return response()->json($detailTaighan);
    }

    public function editTagihan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'siswa' => ['required', 'string'],
            'tagihan' => ['required', 'string'],
            'data' => ['required', 'array', 'min:1'],
            'data.*.KodeAkun' => ['required'],
            'data.*.NamaAkun' => ['required'],
            'data.*.nominal' => ['required', 'numeric'],
        ], ValidationMessage::messages(),
            ValidationMessage::attributes());

        if ($validator->fails()) {
            $message = $validator->errors()->first();
            if ($validator->errors()->count() > 1) {
                $message = "{$message} Dan beberapa error lainnya";
            }

            return response()->json(
                [
                    "message" => $message,
                    "errors" => $validator->errors(),
                ],
                422
            );
        }

        $grouped = collect($request->data)->groupBy('KodeAkun');

        $duplicates = $grouped->filter(function ($items) {
            return $items->count() > 1;
        });

        if ($duplicates->isNotEmpty()) {
            $namaAkunList = collect($duplicates)
                ->flatten(1)
                ->pluck('NamaAkun')
                ->unique()
                ->values()
                ->all();

            $message = 'Terdapat duplikat post: ' . implode(', ', $namaAkunList) . '!';

            return response()->json([
                'message' => $message,
            ], 422);
        }

        $kodeAkunList = collect($request->data)
            ->pluck('KodeAkun')
            ->unique()
            ->values()
            ->all();

        $tagihan = scctbill::where('AA', $request->tagihan)
            ->where('CUSTID', $request->siswa)
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        if ($tagihan->PAIDST === 1) {
            return response()->json(['message' => "Tagihan {$tagihan->BILLNM} sudah dibayar!"], 422);
        }

        $tahun = substr($tagihan->BILLAC, 0, 4);
        $bulan = substr($tagihan->BILLAC, 4, 2);

        try {
            DB::beginTransaction();
            $totalTagihan = 0;
            foreach ($request->data as $key => $item) {
                $nominal = (int) preg_replace('/\D/', '', (string) ($item['nominal'] ?? 0));
                if ($nominal <= 0) {
                    return response()->json(['message' => 'Nominal detail post tidak valid!'], 422);
                }

                scctbill_detail::updateOrInsert([
                    'KodePost' => $item['KodeAkun'],
                    'BILLCD' => $tagihan->BILLCD,
                    'CUSTID' => $tagihan->CUSTID,
                ],[
                    'tahun' => $tahun,
                    'periode' => $bulan,
                    'BILLAM' => $nominal,
                ]);

                $totalTagihan += $nominal;
            }

            $tagihan->update([
                'BILLAM' => $totalTagihan,
            ]);

            $otherDetail = scctbill_detail::whereNotIn('KodePost', $kodeAkunList)
                ->where('CUSTID', $request->siswa)
                ->where('BILLCD', $tagihan->BILLCD)
                ->delete();

            DB::commit();
            return response()->json(['message' => 'Tagihan Berhasil Diedit!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal Mengubah Data Tagihan!<br>Silahkan hubungi administrator',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function copyTagihan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'siswa' => ['required', 'string'],
            'tagihan' => ['required', 'string'],
            'data' => ['required', 'array', 'min:1'],
            'data.*.KodeAkun' => ['required'],
            'data.*.NamaAkun' => ['required'],
            'data.*.nominal' => ['required', 'numeric'],
        ], ValidationMessage::messages(),
            ValidationMessage::attributes());

        if ($validator->fails()) {
            $message = $validator->errors()->first();
            if ($validator->errors()->count() > 1) {
                $message = "{$message} Dan beberapa error lainnya";
            }

            return response()->json(
                [
                    "message" => $message,
                    "errors" => $validator->errors(),
                ],
                422
            );
        }

        $grouped = collect($request->data)->groupBy('KodeAkun');

        $duplicates = $grouped->filter(function ($items) {
            return $items->count() > 1;
        });

        if ($duplicates->isNotEmpty()) {
            $namaAkunList = collect($duplicates)
                ->flatten(1)
                ->pluck('NamaAkun')
                ->unique()
                ->values()
                ->all();

            $message = 'Terdapat duplikat post: ' . implode(', ', $namaAkunList) . '!';

            return response()->json([
                'message' => $message,
            ], 422);
        }

        $tagihan = scctbill::where('AA', $request->tagihan)
            ->where('CUSTID', $request->siswa)
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        try {
            DB::beginTransaction();
            $tagihanSiswaTerbaru = scctbill::where('CUSTID', $request->siswa)
                ->select('CUSTID', 'FUrutan', 'BILLAC', 'BILLCD')
                ->orderBy('FUrutan', 'DESC')
                ->first();

            $urut = $tagihanSiswaTerbaru ? $tagihanSiswaTerbaru['FUrutan'] + 1 : 1;
            $billCD = date('Y') . '/i' . date('m') . '-' . ($urut + 1);

            $tahun = substr($tagihan->BILLAC, 0, 4);
            $bulan = substr($tagihan->BILLAC, 4, 2);

            $totalTagihan = 0;
            $detailRows = [];
            foreach ($request->data as $item) {
                $nominal = (int) preg_replace('/\D/', '', (string) ($item['nominal'] ?? 0));
                if ($nominal <= 0) {
                    DB::rollBack();
                    return response()->json(['message' => 'Nominal detail post tidak valid!'], 422);
                }
                $detailRows[] = [
                    'KodePost' => $item['KodeAkun'],
                    'nominal' => $nominal,
                ];
                $totalTagihan += $nominal;
            }

            $bill = scctbill::create([
                'CUSTID' => $request->siswa,
                'BILLAC' => $tagihan->BILLAC,
                'BILLCD' => $billCD,
                'BILLNM' => $tagihan->BILLNM,
                'BILLAM' => $totalTagihan,
                'PAIDST' => 0,
                'FUrutan' => $urut,
                'FTGLTagihan' => now(),
                'FSTSBolehBayar' => 1,
                'BTA' => $tagihan->BTA,
            ]);

            foreach ($detailRows as $detail) {
                scctbill_detail::create([
                    'KodePost' => $detail['KodePost'],
                    'CUSTID' => $bill->CUSTID,
                    'BILLAM' => $detail['nominal'],
                    'tahun' => $tahun,
                    'periode' => $bulan,
                    'BILLCD' => $bill->BILLCD,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Tagihan Berhasil Disalin dan disimpan!'], 200);
        }catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan salinan Tagihan!<br>Silahkan hubungi administrator',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
