@extends('layouts.admin_new')
@section('title',$dataTitle??$mainTitle??$title??'')
@section('style')
    <style>
        table.dataTable tr.selected {
            border-top: 2px solid var(--bs-primary);
            border-bottom: 2px solid var(--bs-primary);
            border-left: none;
            border-right: none;
        }
    </style>
{{--    <link rel="stylesheet" href="{{asset('main/libs/select2/select2.css')}}">--}}
{{--    <link rel="stylesheet" href="{{asset('main/libs/select2/select2-bootstrap.css')}}">--}}
    <link rel="stylesheet" href="{{asset('main/libs/datatables-bs5/datatables.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endsection
@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">
        @if(isset($dataTitle) && isset($mainTitle) && $mainTitle != $dataTitle)
            {{$mainTitle .' - '.$dataTitle}}
        @else
            {{$mainTitle??$title??''}}
        @endif
    </h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item">
            <a href="{{route('admin.index')}}" class="text-hover-primary">Beranda</a>
        </li>
        @if(isset($title))
            <li class="breadcrumb-item">
                {{$title}}
            </li>
        @endif
        @if(isset($mainTitle))
            <li class="breadcrumb-item">
                {{$mainTitle}}
            </li>
        @endif
        @if(isset($dataTitle) && isset($mainTitle) && $mainTitle != $dataTitle)
            <li class="breadcrumb-item active">
                {{$dataTitle}}
            </li>
        @endif
    </ul>

    <div class="card">
        <div class="card-header">
            <div class="row mb-3">
                <h5 class="mb-0 me-2">{{($dataTitle??$mainTitle??$title)}}</h5>
            </div>
        </div>
        <div class="card-body">
            <form id="rekapForm">
                <fieldset class="form-fieldset">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="col mb-5">
                                <label for="filter_dari_tanggal"
                                       class="form-label text-capitalize form-label-sm">dari
                                    tanggal</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="dari tanggal" id="filter_dari_tanggal"
                                       name="filter[dari_tanggal]">
                            </div>

                            <div class="col mb-5">
                                <label for="filter_sampai_tanggal"
                                       class="form-label text-capitalize form-label-sm">sampai
                                    tanggal</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="sampai tanggal" id="filter_sampai_tanggal"
                                       name="filter[sampai_tanggal]">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="d-flex justify-content-center flex-column flex-md-row justify-content-md-end gap-4">
                            <button type="reset" class="btn btn-secondary" disabled>
                                <span class="ri-reset-left-line me-2"></span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-primary" disabled>
                                <span class="ri-search-line me-2"></span>
                                Cari
                            </button>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
        <div class="card-datatable table-responsive text-nowrap">
            <table class="table table-sm table-bordered table-hover"
                   id="main_table">
                <thead class="table-light">

                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('momentjs', true)
@section('bootstrap-datepicker',true)

@section('script')
    <script src="{{asset('main/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{asset('js/datatableCustom/Datatable-0-4.min.js')}}"></script>
    <script type="text/javascript">
        const select2 = $(`[data-control='select2']`);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let dtOptions = {
            tableId: 'main_table',
            formId: 'rekapForm',
            columnUrl: '{{($columnsUrl??null)}}',
            dataUrl: '{{($datasUrl??null)}}',
            dataColumns: [],
            thead: true,
            tfoot: true,
            paging: true,
            searching: true,
            fixedHeader: false,
            select: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
            buttons: ['excel', 'pdf'],
        };

        document.querySelector('#main_table tbody').addEventListener('click', function (e) {
            if (e.target.closest('.btn-hapus')) {
                const rowEl = e.target.closest('tr');

                if (rowEl) {
                    const rowData = DT[`${dtOptions.tableId}`].row(rowEl).data();
                    Object.entries(rowData).forEach(([key, value]) => {
                        let input = document.querySelector(`#form-delete [name="${key.toLowerCase()}"]`);
                        if (input) {
                            input.value = value;
                        }
                    });
                    modalDelete.show();
                }
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            if (dtOptions.dataUrl && dtOptions.columnUrl) {
                getDT(dtOptions);
                if (dtOptions.formId) {
                    let filterForm = document.getElementById(dtOptions.formId);
                    ;
                    filterForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        dataReFilter(dtOptions.tableId);
                    });
                    filterForm.addEventListener('reset', function (e) {
                        setTimeout(function () {
                            dataReFilter(dtOptions.tableId);
                            const select2InForm = select2.filter(`#${dtOptions.formId} [data-control='select2']`);
                            if (select2InForm.length) {
                                select2InForm.each(function () {
                                    let $this = $(this);
                                    $this.trigger('change');
                                });
                            }
                        }, 0)
                    });
                }
            }

            if (select2.length) {
                select2.each(function () {
                    let $this = $(this);
                    // select2Focus($this);
                    $this.wrap('<div class="position-relative"></div>').select2({
                        placeholder: 'Select value',
                        dropdownParent: $this.parent()
                    });
                });
            }

            const dariTanggal = $('#filter_dari_tanggal');
            const sampaiTanggal = $('#filter_sampai_tanggal');

            dariTanggal.datepicker({
                format: "dd-mm-yyyy",
                autoclose: true
            }).on('changeDate', function (e) {
                sampaiTanggal.datepicker('setStartDate', e.date);
            });

            sampaiTanggal.datepicker({
                format: "dd-mm-yyyy",
                autoclose: true
            }).on('changeDate', function (e) {
                dariTanggal.datepicker('setEndDate', e.date);
            });
        });

    </script>
@endsection
