@extends('layouts.export.kop_file')
@php use Carbon\Carbon; @endphp
@section('title', 'Bukti Pembayaran  ' . ($siswa->NOCUST ?? '') . ' - ' . ($siswa->NMCUST ?? ''))
@section('content')
    <table width="100%">
        <tr>
            <td colspan="2" align="center">
                <h4>KARTU TAGIHAN SISWA</h4>
            </td>
        </tr>
    </table>
    @php
        $nis = !($siswa->NOCUST === '' || is_null($siswa->NOCUST) || !is_numeric($siswa->NOCUST));
    @endphp
    <table width="100%" class="main-table">
        <tr>
            <td style="width: auto"
                class="border-right-0">{{$nis ?  'NIS' : 'No Daft' }}</td>
            <td class="border-left-0">
                :<strong> {{$nis ? $siswa->NOCUST : $siswa->NUM2ND }}</strong>
            </td>
            <td style="width: auto" class="border-right-0">Kelas</td>
            <td class="border-left-0">:<strong> {{$siswa->DESC02??''}} - {{$siswa->DESC03??''}}</strong></td>
        </tr>
        <tr>
            <td style="width: auto"
                class="border-right-0">NOVA
            </td>
            <td class="border-left-0">
                :<strong> {{$nova ?? ''}}</strong>
            </td>
            <td style="width: auto" class="border-right-0">Unit</td>
            <td class="border-left-0">:<strong> {{$siswa->CODE02??''}}</strong></td>
        </tr>
        <tr>
            <td class="border-right-0">Nama Siswa</td>
            <td class="border-left-0">:<strong> {{$siswa->NMCUST??''}} </strong></td>
            <td class="border-right-0">Angkatan</td>
            <td class="border-left-0">:<strong> {{$siswa->DESC04??''}}</strong></td>
        </tr>
    </table>
    <table width="100%" class="table-border main-table">
        <thead class="table-border" style="background-color: #ededed;">
        <tr>
            <th>#</th>
            <th>Tahun Akademik</th>
            <th>Nama Tagihan</th>
            <th>Jumlah</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        @php
            $paid = 0;
            $unpaid = 0;
        @endphp
        @foreach($tagihans as $tagihan)
            @php
                $BILLNM = $tagihan['BILLNM'];
                $nextBILLNM =  false;
                $beforeBILLNM =  false;
                $BILLNMClass = '';
                if (count($tagihans) > 1){
                    if ($loop->index > 0 && $loop->index < (count($tagihans) - 1)){
                        if($tagihans[$loop->index + 1]['BILLNM'] == $tagihan['BILLNM']) $nextBILLNM = true;
                        if($tagihans[$loop->index - 1]['BILLNM'] == $tagihan['BILLNM']) $beforeBILLNM = true;
                    }elseif ($loop->index ==0){
                        if($tagihans[$loop->index + 1]['BILLNM'] == $tagihan['BILLNM']) $nextBILLNM = true;
                    }elseif ($loop->index == (count($tagihans) - 1)){
                        if($tagihans[$loop->index - 1]['BILLNM'] == $tagihan['BILLNM']) $beforeBILLNM = true;
                    }
                }
//
                $BILLNMClass = !$nextBILLNM ? '' : ' border-bottom-0';
                $BILLNMClass .= !$beforeBILLNM?'':' border-top-0';

                if($nextBILLNM && $beforeBILLNM) $BILLNM = '';
                if($beforeBILLNM) $BILLNM = '';

                if ($tagihan['PAIDST'] == 0) {
                    $unpaid += $tagihan['BILLAM'];
                } else{
                    $paid += $tagihan['BILLAM'];
                }
            @endphp
            <tr>
                <th scope="row">{{$loop->index + 1}}</th>
                <td>{{$tagihan['BTA']}}</td>
                <td class="{{$BILLNMClass}}">{{$BILLNM}}</td>
                <td align="right">@rupiah($tagihan['BILLAM'])</td>
                <td align="center">{!! $tagihan['PAIDST'] == 0 ? '<span style="color:red;">BELUM LUNAS</span>': '<span style="color:green;">LUNAS</span>'!!}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot style="background-color: #ededed; font-weight: bold;">
        <tr>
            <td colspan="3">Total Tagihan</td>
            <td align="right">@rupiah($paid + $unpaid)</td>
            <td rowspan="3"></td>
        </tr>
        <tr>
            <td colspan="3">Total Tagihan Terbayar</td>
            <td align="right">@rupiah($paid)</td>
        </tr>
        <tr>
            <td colspan="3">Total Sisa Tagihan</td>
            <td align="right">@rupiah($unpaid)</td>
        </tr>
        </tfoot>
    </table>
@endsection
