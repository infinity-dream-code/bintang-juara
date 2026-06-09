@extends('layouts.export.kop_file')
@php use Carbon\Carbon; @endphp
@section('title', 'Rekap Pembayaran')
@section('content')
    <table width="100%">
        <tr>
            <td colspan="2" align="center">
                <h4>Rekap Tagihan</h4>
            </td>
        </tr>
    </table>
    <table width="100%" class="main-table">
        <tr>
            <td style="width: auto"
                class="border-right-0">Unit
            </td>
            <td class="border-left-0">
                : <strong>{{$kelas->unit}}</strong>
            </td>
        </tr>
        <tr>
            <td style="width: auto"
                class="border-right-0">Kelas
            </td>
            <td class="border-left-0">
                : <strong>{{$kelas->jenjang}} - {{$kelas->kelas}}</strong>
            </td>
        </tr>
    </table>
    <table width="100%" class="table-border main-table">
        <thead style="background-color: #e5e6e8;">
        <tr>
            <th>#</th>
            <th>Nis</th>
            <th>Nama</th>
            @foreach($mstTagihan as $item)
                <th>{{$item}}</th>
            @endforeach
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @php 
            $totalTagihanSiswa = 0;
            $rowNumb = 1;
        @endphp
        @foreach($tagihans as $tagihan)
            @php
                $totalTagihanSiswaIni = 0;
                $row = '';

                foreach ($mstTagihan as $item) {
                    $value = $tagihan[$item] ?? 0;
                    $row .= "<td class='text-end'>Rp. " . number_format($value, 0, ',', '.') . "</td>";
                    $totalTagihanSiswaIni += $value;
                }
            @endphp

            @if($totalTagihanSiswaIni > 0)
                <tr>
                    <td>{{ $rowNumb}}</td>
                    <td>{{ $tagihan['nocust'] }}</td>
                    <td>{{ $tagihan['nmcust'] }}</td>
                    {!! $row !!}
                    <td>@rupiah($totalTagihanSiswaIni)</td>
                </tr>
                @php
                $rowNumb++;
                @endphp
            @endif
        @endforeach
        </tbody>
        <tfoot style="background-color: #e5e6e8;">
        <tr>
            <td colspan="3">Total</td>
            @foreach($mstTagihan as $item)
                <td class="text-end">@rupiah($tagihans->sum($item))</td>
                @php $totalTagihanSiswa += $tagihans->sum($item); @endphp
            @endforeach
            <td class="text-end">@rupiah($totalTagihanSiswa)</td>
        </tr>
        </tfoot>
    </table>
@endsection
