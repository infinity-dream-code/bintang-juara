<?php

namespace App\Http\Controllers\Admin\ManualInput;

use App\Http\Controllers\Controller;
use App\Models\mst_kelas;
use App\Models\mst_tagihan;
use App\Models\mst_thn_aka;
use App\Models\scctbill;
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
        $data['v_dt_daftar_harga'] = mst_tagihan::query()
            ->select([
                DB::raw('tagihan AS KodeAkun'),
                DB::raw('tagihan AS NamaAkun'),
                DB::raw('0 AS nominal'),
            ])
            ->orderBy('urut', 'asc')
            ->get();

        return view('admin.manual_input.edit_manual', $data);
    }

    public function getTagihan(Request $request)
    {
        if (!$request->siswa) {
            return response()->json(['message' => 'Silahkan periksa form anda'], 422);
        }

        $tagihan = scctbill::query()
            ->select([
                'AA',
                'BILLNM',
                'PAIDST',
                'PAIDDT',
                'BTA',
                'FIDBANK',
                'BILLCD',
                'FUrutan',
                'BILLAM',
                'BILLPAID',
                'PAYMENTLEFT',
            ])
            ->where('CUSTID', $request->siswa)
            ->where('FSTSBolehBayar', 1)
            ->orderBy('FUrutan', 'asc')
            ->get();

        return response()->json($tagihan);
    }

    public function getDetailTagihan(Request $request)
    {
        if (!$request->tagihan || !$request->siswa) {
            return response()->json(['message' => 'Silahkan periksa form anda'], 422);
        }

        $tagihan = scctbill::where('BILLCD', $request->tagihan)
            ->where('CUSTID', $request->siswa)
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan'], 422);
        }

        return response()->json([[
            'KodeAkun' => $tagihan->BILLNM,
            'NamaAkun' => $tagihan->BILLNM,
            'nominal' => $tagihan->BILLAM,
        ]]);
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

        $tagihan = scctbill::where('AA', $request->tagihan)
            ->where('CUSTID', $request->siswa)
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        if ($tagihan->PAIDST === 1) {
            return response()->json(['message' => "Tagihan {$tagihan->BILLNM} sudah dibayar!"], 422);
        }

        $totalTagihan = 0;
        foreach ($request->data as $item) {
            $nominal = (int) preg_replace('/\D/', '', (string) ($item['nominal'] ?? 0));
            if ($nominal <= 0) {
                return response()->json(['message' => 'Nominal tagihan tidak valid!'], 422);
            }
            $totalTagihan += $nominal;
        }

        $billPaid = (int) ($tagihan->BILLPAID ?? 0);
        if ($totalTagihan < $billPaid) {
            return response()->json([
                'message' => "Nominal tagihan tidak boleh kurang dari jumlah yang sudah dibayar (Rp " . number_format($billPaid, 0, ',', '.') . ")!",
            ], 422);
        }

        try {
            DB::beginTransaction();

            $tagihan->update([
                'BILLAM' => $totalTagihan,
                'PAYMENTLEFT' => max(0, $totalTagihan - $billPaid),
            ]);

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

        $tagihan = scctbill::where('AA', $request->tagihan)
            ->where('CUSTID', $request->siswa)
            ->first();

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan!'], 422);
        }

        $totalTagihan = 0;
        foreach ($request->data as $item) {
            $nominal = (int) preg_replace('/\D/', '', (string) ($item['nominal'] ?? 0));
            if ($nominal <= 0) {
                return response()->json(['message' => 'Nominal tagihan tidak valid!'], 422);
            }
            $totalTagihan += $nominal;
        }

        try {
            DB::beginTransaction();

            $tagihanSiswaTerbaru = scctbill::where('CUSTID', $request->siswa)
                ->select('CUSTID', 'FUrutan', 'BILLAC', 'BILLCD')
                ->orderBy('FUrutan', 'DESC')
                ->first();

            $urut = $tagihanSiswaTerbaru ? $tagihanSiswaTerbaru['FUrutan'] + 1 : 1;
            $billCD = date('Y') . '/i' . date('m') . '-' . ($urut + 1);

            $mstTagihan = mst_tagihan::where('tagihan', $tagihan->BILLNM)->first();

            scctbill::create([
                'CUSTID' => $request->siswa,
                'BILLAC' => $tagihan->BILLAC,
                'BILLCD' => $billCD,
                'BILLNM' => $tagihan->BILLNM,
                'BILLAM' => $totalTagihan,
                'BILLPAID' => 0,
                'PAYMENTLEFT' => $totalTagihan,
                'PAIDST' => 0,
                'FUrutan' => $urut,
                'FTGLTagihan' => now(),
                'FSTSBolehBayar' => 1,
                'BTA' => $tagihan->BTA,
                'INSTALLMENT' => 0,
                'isINSTALLABLE' => (int) ($mstTagihan->isINSTALLMENT ?? $tagihan->isINSTALLABLE ?? 0),
            ]);

            DB::commit();
            return response()->json(['message' => 'Tagihan Berhasil Disalin dan disimpan!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan salinan Tagihan!<br>Silahkan hubungi administrator',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
