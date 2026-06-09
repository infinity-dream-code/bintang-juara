@extends('layouts.export.kop_file')
@section('title', 'Pratinjau Tagihan')
@section('content')
    @php
        $nis = $siswa->NOCUST ?? null;
        $hasNis = $nis && $nis !== '-';
        $total = collect($tagihans ?? [])->sum('BILLAM');
    @endphp
    <table width="100%">
        <tr>
            <td colspan="4" align="center"><h4>PRATINJAU TAGIHAN</h4></td>
        </tr>
    </table>
    <table width="100%" class="main-table">
        <tr>
            <td>Nama</td>
            <td>: <strong>{{ $siswa->NMCUST ?? '' }}</strong></td>
            <td>{{ $hasNis ? 'NIS' : 'No. Daftar' }}</td>
            <td>: <strong>{{ $hasNis ? $nis : ($siswa->NUM2ND ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>: <strong>{{ trim(($siswa->DESC02 ?? '') . ' ' . ($siswa->DESC03 ?? '')) }}</strong></td>
            <td>Unit</td>
            <td>: <strong>{{ $siswa->CODE02 ?? '' }}</strong></td>
        </tr>
    </table>
    <table width="100%" class="table-border main-table" style="margin-top: 12px;">
        <thead style="background-color: #ededed;">
        <tr>
            <th>#</th>
            <th>Nama Tagihan</th>
            <th>Tahun Akademik</th>
            <th align="right">Tagihan</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tagihans as $tagihan)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $tagihan->BILLNM }}</td>
                <td>{{ $tagihan->BTA }}</td>
                <td align="right">@rupiah($tagihan->BILLAM)</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3" align="right"><strong>Total</strong></td>
            <td align="right"><strong>@rupiah($total)</strong></td>
        </tr>
        </tfoot>
    </table>
@endsection
