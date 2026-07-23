<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\mst_tagihan;
use App\Models\ValidationMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MasterTagihanController extends Controller
{
    public string $title = 'Master Data';
    public string $mainTitle = 'Master Tagihan';
    public string $dataTitle = 'Master Tagihan';

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = route('admin.master-data.master-tagihan.get-column');
        $data['datasUrl'] = route('admin.master-data.master-tagihan.get-data');

        return view('admin.master_data.master_tagihan.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center', 'columnType' => 'no'],
            ['data' => 'tagihan', 'name' => 'Nama Tagihan', 'searchable' => true, 'orderable' => true],
            [
                'data' => 'isINSTALLMENT_toggle',
                'name' => 'Status Dapat Di Cicil',
                'searchable' => false,
                'orderable' => true,
                'className' => 'text-center',
            ],
        ];
    }

    public function getData(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $rowperpage = $request->get('length');

        $columnIndex_arr = $request->get('order', []);
        $columnName_arr = $request->get('columns', []);
        $order_arr = $request->get('order', []);
        $search_arr = $request->get('search', []);
        $searchValue = $search_arr['value'] ?? '';

        $columnName = 'urut';
        $columnSortOrder = 'asc';

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'] ?? null;
            if ($columnIndex !== null && !empty($columnName_arr[$columnIndex]['data']) && $columnName_arr[$columnIndex]['data'] !== 'no') {
                $sortColumn = $columnName_arr[$columnIndex]['data'];
                $columnName = in_array($sortColumn, ['isINSTALLMENT_label', 'isINSTALLMENT_toggle'], true)
                    ? 'isINSTALLMENT'
                    : $sortColumn;
                $columnSortOrder = $order_arr[0]['dir'] ?? 'asc';
            }
        }

        $totalRecords = mst_tagihan::count();
        $totalRecordswithFilter = mst_tagihan::where('tagihan', 'like', '%' . $searchValue . '%')->count();

        $records = mst_tagihan::orderBy($columnName, $columnSortOrder)
            ->where('tagihan', 'like', '%' . $searchValue . '%')
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->map(function ($item) {
                $checked = (int) $item->isINSTALLMENT === 1 ? 'checked' : '';
                $label = (int) $item->isINSTALLMENT === 1 ? 'Bisa di cicil' : 'Tidak bisa di cicil';
                $item->isINSTALLMENT_toggle = '
                    <div class="d-inline-flex align-items-center gap-2 justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input toggle-installment"
                                type="checkbox"
                                role="switch"
                                data-id="' . (int) $item->urut . '"
                                ' . $checked . '
                                title="' . e($label) . '">
                        </div>
                        <span class="small text-muted installment-label">' . e($label) . '</span>
                    </div>';

                return $item;
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordswithFilter,
            'data' => $records,
        ]);
    }

    public function toggleInstallment($id, Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'isINSTALLMENT' => ['required', 'in:0,1'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $tagihan = mst_tagihan::where('urut', $id)->first();
        if (!$tagihan) {
            return response()->json(['message' => 'Data tagihan tidak ditemukan'], 422);
        }

        try {
            $tagihan->isINSTALLMENT = (int) $request->isINSTALLMENT;
            $tagihan->save();

            return response()->json([
                'message' => (int) $tagihan->isINSTALLMENT === 1
                    ? 'Tagihan "' . $tagihan->tagihan . '" sekarang bisa di cicil'
                    : 'Tagihan "' . $tagihan->tagihan . '" sekarang tidak bisa di cicil',
                'isINSTALLMENT' => (int) $tagihan->isINSTALLMENT,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mengubah status cicil',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'tagihan' => ['required', 'string', 'max:100'],
                'isINSTALLMENT' => ['required', 'in:0,1'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $exists = mst_tagihan::where('tagihan', $request->tagihan)->first();
        if ($exists) {
            return response()->json(['message' => 'Nama tagihan sudah ada'], 422);
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();

            $nextUrut = (int) mst_tagihan::max('urut') + 1;

            mst_tagihan::create([
                'urut' => $nextUrut,
                'tagihan' => strtoupper(trim($request->tagihan)),
                'kode' => null,
                'isINSTALLMENT' => (int) $request->isINSTALLMENT,
            ]);

            DB::connection('DATA_MYSQL')->commit();
            mst_tagihan::flushInstallmentCache();

            return response()->json(['message' => 'Data ' . $this->mainTitle . ' telah disimpan']);
        } catch (Exception $e) {
            DB::connection('DATA_MYSQL')->rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal disimpan', 'error' => $e->getMessage()], 422);
        }
    }
}
