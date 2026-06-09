<?php

namespace App\Http\Controllers\Admin\Wakaf;

use App\Http\Controllers\Controller;
use App\Models\mst_sumbangan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MasterWakafController extends Controller
{
    public string $title = 'Wakaf';
    public string $mainTitle = 'Wakaf';
    public string $dataTitle = 'Master Wakaf';

    public function index()
    {
        $data['title'] = $this->title;
        $data['mainTitle'] = $this->mainTitle;
        $data['dataTitle'] = $this->dataTitle;
        $data['columnsUrl'] = route('admin.wakaf.master-wakaf.get-column');
        $data['datasUrl'] = route('admin.wakaf.master-wakaf.get-data');

        return view('admin.wakaf.master_wakaf.index', $data);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'columnType' => 'row', 'className' => 'text-center', 'duplicate' => false, 'exportable' => true],
            ['data' => 'nmsumbangan', 'name' => 'Nama Sumbangan', 'searchable' => true, 'orderable' => true, 'duplicate' => false, 'exportable' => true],
            ['data' => 'nocust', 'name' => 'No VA', 'searchable' => true, 'orderable' => true, 'duplicate' => false, 'exportable' => true],
            [
                'data' => 'stcust',
                'name' => 'Status',
                'columnType' => 'switch',
                'searchable' => false,
                'orderable' => true,
                'className' => 'text-center',
                'duplicate' => false,
                'exportable' => true,
                'trueVal' => 'Aktif',
                'falseVal' => 'Nonaktif',
            ],
        ];
    }

    public function getData(Request $request)
    {
        try {
            $draw = (int) $request->get('draw', 0);
            $start = (int) $request->get('start', 0);
            $rowperpage = (int) $request->get('length', 10);

            $columnIndexArr = $request->get('order', []);
            $columnNameArr = $request->get('columns', []);
            $searchArr = $request->get('search', []);
            $searchValue = trim((string) ($searchArr['value'] ?? ''));

            $filter = $request->input('filter', []);
            if (!is_array($filter)) {
                $filter = [];
            }
            $filterNama = trim((string) ($filter['nama'] ?? ''));
            $filterStatus = strtolower(trim((string) ($filter['status'] ?? 'all')));

            $defaultColumn = 'idincrement';
            $defaultOrder = 'desc';

            $columnName = $defaultColumn;
            $columnSortOrder = $defaultOrder;

            if (is_array($columnIndexArr) && !empty($columnIndexArr)) {
                $columnIndex = (int) ($columnIndexArr[0]['column'] ?? 0);
                $columnSortOrder = strtolower((string) ($columnIndexArr[0]['dir'] ?? $defaultOrder));
                $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'], true) ? $columnSortOrder : $defaultOrder;

                if (is_array($columnNameArr) && isset($columnNameArr[$columnIndex])) {
                    $columnName = (string) ($columnNameArr[$columnIndex]['data'] ?? $defaultColumn);
                }
            }

            $allowedSortMap = [
                'nmsumbangan' => 'namaSumbangan',
                'nocust' => 'NOCUST',
                'stcust' => 'STCUST',
            ];
            $sortColumn = $allowedSortMap[$columnName] ?? $defaultColumn;

            $baseQuery = DB::connection('DATA_MYSQL')
                ->table('mst_sumbangan')
                ->select([
                    'idincrement',
                    DB::raw('namaSumbangan as nmsumbangan'),
                    DB::raw('NOCUST as nocust'),
                    DB::raw('STCUST as stcust'),
                ]);

            $applyFilters = function ($query) use ($searchValue, $filterNama, $filterStatus) {
                if ($searchValue !== '') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('namaSumbangan', 'like', '%' . $searchValue . '%')
                            ->orWhere('NOCUST', 'like', '%' . $searchValue . '%');
                    });
                }

                if ($filterNama !== '') {
                    $query->where('namaSumbangan', 'like', '%' . $filterNama . '%');
                }

                if (in_array($filterStatus, ['0', '1'], true)) {
                    $query->where('STCUST', (int) $filterStatus);
                }
            };

            $totalRecords = (clone $baseQuery)->count();
            $filteredQuery = clone $baseQuery;
            $applyFilters($filteredQuery);
            $totalRecordswithFilter = (clone $filteredQuery)->count();

            $records = $filteredQuery
                ->orderBy($sortColumn, $columnSortOrder)
                ->skip($start)
                ->take($rowperpage)
                ->get()
                ->map(function ($item) {
                    $item->stcust = (int) ($item->stcust ?? 0);
                    $item->nocust = $item->nocust ?: '-';
                    $item->item_id = $item->idincrement;
                    return $item;
                })
                ->toArray();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecordswithFilter,
                'data' => $records,
            ]);
        } catch (\Throwable $e) {
            Log::error('MasterWakaf getData failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'draw' => (int) $request->get('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'message' => 'Gagal memuat data master wakaf',
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        $data = mst_sumbangan::query()->where('idincrement', $id)->first();
        if (!$data) {
            return response()->json(['message' => 'Data wakaf tidak ditemukan'], 422);
        }

        try {
            DB::connection('DATA_MYSQL')->beginTransaction();
            $newStatus = (int) $data->STCUST === 1 ? 0 : 1;
            $data->update(['STCUST' => $newStatus]);
            DB::connection('DATA_MYSQL')->commit();

            return response()->json([
                'message' => $newStatus === 1 ? 'Status wakaf diubah menjadi Aktif' : 'Status wakaf diubah menjadi Nonaktif',
            ]);
        } catch (Exception $e) {
            DB::connection('DATA_MYSQL')->rollBack();
            return response()->json(['message' => 'Gagal mengubah status wakaf', 'error' => $e->getMessage()], 422);
        }
    }
}
