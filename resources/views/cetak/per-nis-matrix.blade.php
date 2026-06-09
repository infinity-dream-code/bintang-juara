@extends('layouts.export.kop_file')
@php
    use App\Support\PerNisMatrixPdf;

    $postColCount = PerNisMatrixPdf::countPostColumns($tagihans);
    $layout = PerNisMatrixPdf::layoutForColumns($postColCount);
    $useNamaAkunHeader = $useNamaAkunHeader ?? true;

    $columns = [];
    $periods = collect($tagihans)
        ->sortBy([
            ['BILLAC', 'asc'],
            ['FUrutan', 'asc'],
        ])
        ->groupBy(fn ($row) => $row['BILLAC'] ?? '-');

    foreach ($periods as $billac => $items) {
        $seen = [];
        foreach ($items as $item) {
            $kode = $item['KodePost'] ?? '-';
            $nama = $useNamaAkunHeader
                ? ($item['NamaAkun'] ?? $item['BILLNM'] ?? '-')
                : ($item['BILLNM'] ?? $item['NamaAkun'] ?? '-');
            $key = $kode . '|' . $nama;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $columns[] = [
                'billac' => $billac,
                'kode' => $kode,
                'nama' => $nama,
                'key' => $key,
            ];
        }
    }

    $students = collect($tagihans)->groupBy(function ($row) {
        $nis = trim((string) ($row['NOCUST'] ?? ''));
        if ($nis !== '' && $nis !== '-') {
            return 'nis:' . $nis;
        }
        $daftar = trim((string) ($row['NUM2ND'] ?? ''));
        if ($daftar !== '' && $daftar !== '-') {
            return 'daftar:' . $daftar;
        }
        return 'cust:' . ($row['CUSTID'] ?? uniqid());
    });

    $amountIndex = [];
    foreach ($tagihans as $row) {
        $nis = trim((string) ($row['NOCUST'] ?? ''));
        $groupKey = ($nis !== '' && $nis !== '-')
            ? 'nis:' . $nis
            : ('daftar:' . trim((string) ($row['NUM2ND'] ?? '')));
        $kode = $row['KodePost'] ?? '-';
        $nama = $useNamaAkunHeader
            ? ($row['NamaAkun'] ?? $row['BILLNM'] ?? '-')
            : ($row['BILLNM'] ?? $row['NamaAkun'] ?? '-');
        $colKey = ($row['BILLAC'] ?? '-') . '|' . $kode . '|' . $nama;
        $amountIndex[$groupKey][$colKey] = ($amountIndex[$groupKey][$colKey] ?? 0) + (float) ($row['BILLAM'] ?? 0);
    }
@endphp
@section('title', $reportTitle ?? 'REKAP TAGIHAN - CETAK PER NIS')
@section('content')
    <table width="100%">
        <tr>
            <td colspan="{{ 4 + count($columns) }}" align="center">
                <h4>{{ $reportTitle ?? 'REKAP TAGIHAN - CETAK PER NIS' }}</h4>
            </td>
        </tr>
    </table>

    <table width="100%" class="table-border main-table" style="font-size: {{ $layout['fontSize'] }}pt;">
        <thead style="background-color: #ededed; font-size: {{ $layout['headerFontSize'] }}pt;">
        <tr>
            <th style="padding: {{ $layout['cellPadding'] }};">No</th>
            <th style="padding: {{ $layout['cellPadding'] }};">NIS</th>
            <th style="padding: {{ $layout['cellPadding'] }};">Nama Siswa</th>
            <th style="padding: {{ $layout['cellPadding'] }};">Kelas</th>
            @foreach ($columns as $column)
                <th style="padding: {{ $layout['cellPadding'] }}; max-width: {{ $layout['postHeaderMaxWidth'] }};">
                    <div>{{ $column['billac'] }}</div>
                    <div>{{ $column['kode'] }}</div>
                    <div>{{ $column['nama'] }}</div>
                </th>
            @endforeach
            <th style="padding: {{ $layout['cellPadding'] }};">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($students as $groupKey => $rows)
            @php
                $first = $rows->first();
                $nis = $first['NOCUST'] ?? ($first['NUM2ND'] ?? '-');
                $nama = $first['NMCUST'] ?? '-';
                $kelas = trim(($first['DESC02'] ?? '') . ' ' . ($first['DESC03'] ?? ''));
                $rowTotal = 0;
            @endphp
            <tr>
                <td align="center" style="padding: {{ $layout['cellPadding'] }};">{{ $loop->iteration }}</td>
                <td style="padding: {{ $layout['cellPadding'] }};">{{ $nis }}</td>
                <td style="padding: {{ $layout['cellPadding'] }};">{{ $nama }}</td>
                <td style="padding: {{ $layout['cellPadding'] }};">{{ $kelas }}</td>
                @foreach ($columns as $column)
                    @php
                        $colKey = $column['billac'] . '|' . $column['kode'] . '|' . $column['nama'];
                        $val = $amountIndex[$groupKey][$colKey] ?? 0;
                        $rowTotal += $val;
                    @endphp
                    <td align="right" style="padding: {{ $layout['cellPadding'] }};">
                        {{ $val > 0 ? number_format($val, 0, ',', '.') : '-' }}
                    </td>
                @endforeach
                <td align="right" style="padding: {{ $layout['cellPadding'] }}; font-weight: bold;">
                    {{ number_format($rowTotal, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
