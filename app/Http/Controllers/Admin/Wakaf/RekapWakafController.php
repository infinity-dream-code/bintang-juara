<?php

namespace App\Http\Controllers\Admin\Wakaf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RekapWakafController extends Controller
{
    public string $title = 'Wakaf';
    public string $mainTitle = 'Wakaf';
    public string $dataTitle = 'Rekap Wakaf';

    public function index()
    {
        $wakafList = DB::connection('DATA_MYSQL')
            ->table('mst_sumbangan')
            ->select(['idincrement', 'namaSumbangan'])
            ->orderBy('namaSumbangan')
            ->get();

        return view('admin.wakaf.rekap_wakaf.index', [
            'title' => $this->title,
            'mainTitle' => $this->mainTitle,
            'dataTitle' => $this->dataTitle,
            'columnsUrl' => route('admin.wakaf.rekap-wakaf.get-column'),
            'datasUrl' => route('admin.wakaf.rekap-wakaf.get-data'),
            'wakafList' => $wakafList,
        ]);
    }

    public function getColumn()
    {
        return [
            ['data' => null, 'name' => 'no', 'columnType' => 'row', 'className' => 'text-center', 'duplicate' => false, 'exportable' => true],
            ['data' => 'nmsumbangan', 'name' => 'Nama Wakaf', 'searchable' => true, 'orderable' => true, 'duplicate' => false, 'exportable' => true],
            ['data' => 'trxdate', 'name' => 'Tanggal', 'columnType' => 'timestamp', 'searchable' => false, 'orderable' => true, 'duplicate' => false, 'exportable' => true],
            [
                'data' => 'stcust',
                'name' => 'Status Wakaf',
                'columnType' => 'boolean',
                'searchable' => false,
                'orderable' => true,
                'className' => 'text-center',
                'duplicate' => false,
                'exportable' => true,
                'trueVal' => 'Aktif',
                'falseVal' => 'Nonaktif',
            ],
            ['data' => 'metode', 'name' => 'Metode', 'searchable' => true, 'orderable' => true, 'duplicate' => false, 'exportable' => true],
            ['data' => 'nominal', 'name' => 'Nominal', 'columnType' => 'currency', 'className' => 'text-end', 'searchable' => false, 'orderable' => true, 'duplicate' => false, 'exportable' => true],
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
            $filterWakafId = trim((string) ($filter['wakaf_id'] ?? 'all'));
            $filterStatus = strtolower(trim((string) ($filter['status'] ?? 'all')));
            $filterDariTanggal = trim((string) ($filter['dari_tanggal'] ?? ''));
            $filterSampaiTanggal = trim((string) ($filter['sampai_tanggal'] ?? ''));

            $defaultColumn = 't.TRXDATE';
            $defaultOrder = 'desc';

            $columnName = 'trxdate';
            $columnSortOrder = $defaultOrder;

            if (is_array($columnIndexArr) && !empty($columnIndexArr)) {
                $columnIndex = (int) ($columnIndexArr[0]['column'] ?? 0);
                $columnSortOrder = strtolower((string) ($columnIndexArr[0]['dir'] ?? $defaultOrder));
                $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'], true) ? $columnSortOrder : $defaultOrder;

                if (is_array($columnNameArr) && isset($columnNameArr[$columnIndex])) {
                    $columnName = (string) ($columnNameArr[$columnIndex]['data'] ?? 'trxdate');
                }
            }

            if (!$columnName || $columnName === 'no') {
                $columnName = 'trxdate';
                $columnSortOrder = $defaultOrder;
            }

            $allowedSortMap = [
                'nmsumbangan' => 'm.namaSumbangan',
                'trxdate' => 't.TRXDATE',
                'stcust' => 'm.STCUST',
                'metode' => 't.METODE',
            ];
            $sortColumn = $allowedSortMap[$columnName] ?? $defaultColumn;
            $sortByNominal = $columnName === 'nominal';
            $nominalExpr = 'ABS(COALESCE(t.KREDIT, 0) - COALESCE(t.DEBET, 0))';

            $baseQuery = DB::connection('DATA_MYSQL')
                ->table('sccttran_sumbangan as t')
                ->join('mst_sumbangan as m', 'm.idincrement', '=', 't.CUSTID')
                ->select([
                    DB::raw('m.namaSumbangan as nmsumbangan'),
                    DB::raw('m.STCUST as stcust'),
                    DB::raw('t.TRXDATE as trxdate'),
                    DB::raw('t.METODE as metode'),
                    DB::raw($nominalExpr . ' as nominal'),
                ]);

            $applyFilters = function ($query) use (
                $searchValue,
                $filterNama,
                $filterWakafId,
                $filterStatus,
                $filterDariTanggal,
                $filterSampaiTanggal
            ) {
                if ($searchValue !== '') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('m.namaSumbangan', 'like', '%' . $searchValue . '%')
                            ->orWhere('t.METODE', 'like', '%' . $searchValue . '%');
                    });
                }

                if ($filterNama !== '') {
                    $query->where('m.namaSumbangan', 'like', '%' . $filterNama . '%');
                }

                if ($filterWakafId !== '' && strtolower($filterWakafId) !== 'all') {
                    $query->where('m.idincrement', (int) $filterWakafId);
                }

                if (in_array($filterStatus, ['0', '1'], true)) {
                    $query->where('m.STCUST', (int) $filterStatus);
                }

                if ($filterDariTanggal !== '' && preg_match('/^\d{2}-\d{2}-\d{4}$/', $filterDariTanggal)) {
                    $query->where('t.TRXDATE', '>=', Carbon::createFromFormat('d-m-Y', $filterDariTanggal)->startOfDay());
                }

                if ($filterSampaiTanggal !== '' && preg_match('/^\d{2}-\d{2}-\d{4}$/', $filterSampaiTanggal)) {
                    $query->where('t.TRXDATE', '<=', Carbon::createFromFormat('d-m-Y', $filterSampaiTanggal)->endOfDay());
                }
            };

            $totalRecords = (clone $baseQuery)->count();
            $filteredQuery = clone $baseQuery;
            $applyFilters($filteredQuery);
            $totalRecordswithFilter = (clone $filteredQuery)->count();

            $totalNominal = (clone $filteredQuery)
                ->reorder()
                ->selectRaw('COALESCE(SUM(' . $nominalExpr . '), 0) as total_nominal')
                ->value('total_nominal');

            $recordsQuery = clone $filteredQuery;
            if ($sortByNominal) {
                $recordsQuery->orderByRaw($nominalExpr . ' ' . $columnSortOrder);
            } else {
                $recordsQuery->orderBy($sortColumn, $columnSortOrder);
            }

            $records = $recordsQuery
                ->skip($start)
                ->take($rowperpage)
                ->get()
                ->map(function ($item) {
                    $item->stcust = (int) ($item->stcust ?? 0);
                    $item->nominal = (float) ($item->nominal ?? 0);
                    $item->metode = $item->metode ?: '-';
                    return $item;
                })
                ->toArray();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecordswithFilter,
                'data' => $records,
                'totals' => [
                    'nominal' => ['location' => 5, 'value' => (float) $totalNominal, 'columnType' => 'currency'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('RekapWakaf getData failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'draw' => (int) $request->get('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'message' => 'Gagal memuat data rekap wakaf',
            ], 500);
        }
    }
}
