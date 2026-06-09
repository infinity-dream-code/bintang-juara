<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\tbl_notice;
use App\Models\ValidationMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PengumumanController extends Controller
{
    public string $title = 'Pengumuman';
    public string $mainTitle = 'Pengumuman';
    public string $dataTitle = 'Pengumuman';

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = route('admin.pengumuman.get-column');
        $data['datasUrl'] = route('admin.pengumuman.get-data');

        return view('admin.pengumuman.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'className' => 'text-center', 'columnType' => 'row'],
            ['data' => 'Title', 'name' => 'Judul', 'searchable' => true, 'orderable' => true],
            ['data' => 'Payload', 'name' => 'Isi', 'searchable' => true, 'orderable' => true],
            ['data' => 'Date', 'name' => 'Tanggal', 'searchable' => true, 'orderable' => true],
            [
                'data' => 'edit',
                'name' => '',
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center',
                'button' => 'modal',
                'buttonText' => 'Edit',
                'buttonClass' => 'btn btn-sm btn-info btn-edit',
                'buttonLink' => '#modal-edit',
                'buttonIcon' => 'ri-edit-line me-2',
            ],
            [
                'data' => 'delete',
                'name' => '',
                'dataVal' => false,
                'columnType' => 'button',
                'className' => 'text-center',
                'button' => 'modal',
                'buttonText' => 'Hapus',
                'buttonClass' => 'btn btn-sm btn-danger btn-hapus',
                'buttonLink' => '#modal-delete',
                'buttonIcon' => 'ri-delete-bin-line me-2',
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

        $columnName = 'Date';
        $columnSortOrder = 'desc';

        if (!empty($order_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'] ?? null;
            if ($columnIndex !== null && !empty($columnName_arr[$columnIndex]['data']) && $columnName_arr[$columnIndex]['data'] !== 'no') {
                $columnName = $columnName_arr[$columnIndex]['data'];
                $columnSortOrder = $order_arr[0]['dir'] ?? 'desc';
            }
        }

        $searchable = ['Title', 'Payload', 'Date'];
        $allowedSort = ['Title', 'Payload', 'Date'];
        if (!in_array($columnName, $allowedSort, true)) {
            $columnName = 'Date';
        }

        $totalRecords = tbl_notice::where('Status', 1)->count();
        $totalRecordswithFilter = tbl_notice::where('Status', 1)
            ->whereAny($searchable, 'like', '%' . $searchValue . '%')
            ->count();

        $records = tbl_notice::where('Status', 1)
            ->orderBy($columnName, $columnSortOrder)
            ->whereAny($searchable, 'like', '%' . $searchValue . '%')
            ->select('*')
            ->skip($start)
            ->take($rowperpage)
            ->get()
            ->map(function ($item) {
                $item->item_id = $item->idincrement;
                $item->edit = true;
                $item->delete = true;
                unset($item->idincrement);
                return $item;
            })->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordswithFilter,
            'data' => $records,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'Title' => ['required', 'string', 'max:255'],
                'Payload' => ['required', 'string'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();

            tbl_notice::create([
                'Title' => $request->Title,
                'Payload' => $request->Payload,
                'Date' => now()->format('Y-m-d H:i:s'),
                'Status' => 1,
            ]);

            DB::connection('DATA_MYSQL')->commit();

            $pushMessage = $this->pushNotice()
                ? 'Data ' . $this->mainTitle . ' telah disimpan'
                : 'Data ' . $this->mainTitle . ' telah disimpan, namun notifikasi push gagal dikirim';

            return response()->json(['message' => $pushMessage]);
        } catch (Exception $e) {
            DB::connection('DATA_MYSQL')->rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal disimpan', 'error' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $notice = tbl_notice::where('idincrement', $id)->where('Status', 1)->first();
        if (!$notice) {
            return response()->json(['message' => 'Pengumuman tidak ditemukan!'], 422);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'Title' => ['required', 'string', 'max:255'],
                'Payload' => ['required', 'string'],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes()
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();

            $notice->update([
                'Title' => $request->Title,
                'Payload' => $request->Payload,
                'Date' => now()->format('Y-m-d H:i:s'),
            ]);

            DB::connection('DATA_MYSQL')->commit();

            $pushMessage = $this->pushNotice()
                ? 'Data ' . $this->mainTitle . ' telah diubah'
                : 'Data ' . $this->mainTitle . ' telah diubah, namun notifikasi push gagal dikirim';

            return response()->json(['message' => $pushMessage]);
        } catch (Exception $e) {
            DB::connection('DATA_MYSQL')->rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal diubah', 'error' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $notice = tbl_notice::where('idincrement', $id)->where('Status', 1)->first();
        if (!$notice) {
            return response()->json(['message' => 'Pengumuman tidak ditemukan!'], 422);
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();
            $notice->update(['Status' => 0]);
            DB::connection('DATA_MYSQL')->commit();

            return response()->json(['message' => 'Data ' . $this->mainTitle . ' telah dihapus']);
        } catch (Exception $e) {
            DB::connection('DATA_MYSQL')->rollBack();
            return response()->json(['message' => 'Data ' . $this->mainTitle . ' gagal dihapus', 'error' => $e->getMessage()], 422);
        }
    }

    private function pushNotice(): bool
    {
        $url = config('services.smartpayment.notice_push_url');
        if (!$url) {
            return false;
        }

        try {
            $response = Http::timeout(10)->get($url);
            return $response->successful();
        } catch (Exception $e) {
            Log::warning('NoticePush gagal: ' . $e->getMessage());
            return false;
        }
    }
}
