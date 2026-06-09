<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cek Lunas Siswa</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h3 { margin: 0 0 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #444; padding: 4px 6px; vertical-align: top; }
        th { background: #eee; text-align: left; }
        .text-center { text-align: center; }
        .lunas { color: #146c43; font-weight: bold; }
        .belum { color: #b02a37; font-weight: bold; }
    </style>
</head>
<body>
<h3>Laporan Cek Lunas Siswa</h3>
@if(!empty($filter))
    <div>
        Filter:
        @foreach($filter as $k => $v)
            @if($v !== null && $v !== '' && strtolower((string)$v) !== 'all')
                <strong>{{ str_replace('_', ' ', ucfirst($k)) }}</strong>: {{ $v }} &nbsp;
            @endif
        @endforeach
    </div>
@endif

<table>
    <thead>
    <tr>
        <th class="text-center" style="width: 30px;">No</th>
        <th>Thn Akademik</th>
        <th>NIS</th>
        <th>Nama Siswa</th>
        <th>Nama Tagihan</th>
        <th>Kelas</th>
        <th>Unit</th>
        <th class="text-center">Status</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $i => $row)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $row['BTA'] ?? '-' }}</td>
            <td>{{ $row['nocust'] ?? '-' }}</td>
            <td>{{ $row['nmcust'] ?? '-' }}</td>
            <td>{{ $row['BILLNM'] ?? '-' }}</td>
            <td>{{ $row['kelas_display'] ?? '-' }}</td>
            <td>{{ $row['CODE02'] ?? '-' }}</td>
            <td class="text-center">
                @if(!empty($row['status']))
                    <span class="lunas">LUNAS</span>
                @else
                    <span class="belum">BELUM LUNAS</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center">Data tidak ditemukan</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
