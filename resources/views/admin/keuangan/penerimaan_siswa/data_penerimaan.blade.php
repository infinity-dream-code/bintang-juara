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
    <link rel="stylesheet" href="{{asset('main/libs/select2/select2.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/select2/select2-bootstrap.css')}}">
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
            <form id="filter-form">
                <fieldset class="form-fieldset">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="mb-3 row">
                                <label for="filter[tahun_akademik]" class="col-sm-4 col-form-label form-label">Tahun
                                    Akademik</label>
                                <div class="col">
                                    <select class="form-select select2" id="filter[tahun_akademik]"
                                            name="filter[tahun_akademik]"
                                            data-control="select2"
                                            data-placeholder="Pilih Tahun Akademik">
                                        <option value="all">Semua</option>
                                        @isset($thn_aka)
                                            @foreach($thn_aka as $item)
                                                <option
                                                    value="{{$item->thn_aka}}">{{$item->thn_aka}}</option>
                                            @endforeach
                                        @else
                                            <option>data kosong</option>
                                        @endisset
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="filter[nis]" class="col-sm-4 col-form-label form-label">Nis</label>
                                <div class="col">
                                    <input type="text" class="form-control form-control"
                                           placeholder="Masukkan nis siswa" id="filter[nis]" name="filter[nis]">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="filter[nama]" class="col-sm-4 col-form-label text-capitalize form-label">nama</label>
                                <div class="col">
                                    <input type="text" class="form-control form-control"
                                           placeholder="Masukkan nama siswa" id="filter[nama]" name="filter[nama]">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="filter_dari_tanggal"
                                       class="col-sm-4 col-form-label text-capitalize form-label">dari
                                    tanggal</label>
                                <div class="col">
                                    <input type="text" class="form-control form-control"
                                           placeholder="dari tanggal" id="filter_dari_tanggal"
                                           name="filter[dari_tanggal]">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="filter_sampai_tanggal"
                                       class="col-sm-4 col-form-label text-capitalize form-label">sampai
                                    tanggal</label>
                                <div class="col">
                                    <input type="text" class="form-control form-control"
                                           placeholder="sampai tanggal" id="filter_sampai_tanggal"
                                           name="filter[sampai_tanggal]">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="filter[sekolah]"
                                       class="col-sm-4 col-form-label text-capitalize form-label">sekolah</label>
                                <div class="col">
                                    <select class="form-select" id="filter[sekolah]" name="filter[sekolah]"
                                            data-control="select2" data-placeholder="Pilih Sekolah">
                                        <option value="all">Semua</option>
                                        @isset($sekolah)
                                            @foreach($sekolah as $item)
                                                <option
                                                    value="{{$item->CODE01}}">{{$item->DESC01}}</option>
                                            @endforeach
                                        @else
                                            <option>data kosong</option>
                                        @endisset
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="filter[periode_mulai]"
                                       class="col-sm-4 col-form-label text-capitalize form-label">periode
                                    mulai</label>
                                <div class="col">
                                    <input type="text" class="form-control form-control"
                                           placeholder="periode mulai" id="filter[periode_mulai]"
                                           name="filter[periode_mulai]">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="filter[periode_akhir]"
                                       class="col-sm-4 col-form-label text-capitalize form-label">periode
                                    akhir</label>
                                <div class="col">
                                    <input type="text" class="form-control form-control"
                                           placeholder="periode akhir" id="filter[periode_akhir]"
                                           name="filter[periode_akhir]">
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label form-label" for="filter[angkatan]">
                                    Angkatan Siswa
                                </label>
                                <div class="col">
                                    <select class="form-select" id="filter[angkatan]"
                                            name="filter[angkatan]"
                                            data-control="select2"
                                            data-placeholder="Pilih Angkatan Siswa">
                                        <option value="all">Semua</option>
                                        @isset($thn_aka)
                                            @foreach($thn_aka as $item)
                                                <option
                                                    value="{{$item->thn_aka}}">{{$item->thn_aka}}</option>
                                            @endforeach
                                        @else
                                            <option>data kosong</option>
                                        @endisset
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label form-label" for="filter[kelas]">
                                    Kelas
                                </label>
                                <div class="col">
                                    <select class="form-select" id="filter[kelas]" name="filter[kelas]"
                                            data-control="select2" data-placeholder="Pilih Kelas">
                                        <option value="all">Semua</option>
                                        @isset($kelas)
                                            @foreach($kelas as $item)
                                                <option
                                                    value="{{$item->jenjang}}">{{$item->unit}}
                                                    - {{$item->kelas}} {{$item->jenjang}}</option>
                                            @endforeach
                                        @else
                                            <option>data kosong</option>
                                        @endisset
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label text-capitalize form-label" for="filter[bank]">
                                    bank
                                </label>
                                <div class="col">
                                    <select class="form-select" id="filter[bank]" name="filter[bank]"
                                            data-control="select2" data-placeholder="Pilih bank">
                                        <option value="all">Semua</option>
                                        @isset($bank)
                                            @foreach($bank as $key => $item)
                                                <option
                                                    value="{{$key}}">{{$item}}</option>
                                            @endforeach
                                        @else
                                            <option>data kosong</option>
                                        @endisset
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-facebook w-100 text-nowrap mb-3" id="cetak-kartu-siswa">
                                <span class="ri-info-card-line me-2"></span>
                                Cetak Kartu Siswa
                            </button>
                            <button type="button" class="btn btn-facebook w-100 text-nowrap mb-3" id="cetak-kuitansi">
                                <span class="ri-info-card-line me-2"></span>
                                Cetak Kuitansi
                            </button>
                            <button type="button" class="btn btn-facebook w-100 text-nowrap  mb-3" id="cetak-kuitansi-2000">
                                <span class="ri-info-card-line me-2"></span>
                                Cetak Kuitansi Dengan 2000
                            </button>
                            <button type="button" class="btn btn-google-plus w-100 text-nowrap download-pdf-new  mb-3"
                                    id="download-pdf">
                                <span class="ri-file-pdf-2-line me-2"></span>
                                Cetak PDF
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="d-flex justify-content-center justify-content-md-end gap-4">
                            <button type="reset" class="btn btn-secondary">
                                <span class="ri-reset-left-line me-2"></span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="ri-search-line me-2"></span>
                                Cari
                            </button>
                        </div>
                    </div>
                </fieldset>
            </form>
            <div class="row px-5 mb-2">
                <ul class="list-group list-group-timeline">
                    <li class="list-group-item list-group-timeline-danger">
                        <strong>Pastikan telah mengisi Tanggal Transaksi!</strong>
                    </li>
                    <li class="list-group-item list-group-timeline-danger">
                        <strong>Pastikan browser anda tidak memblokir <i>POP-UP</i>!</strong>
                    </li>
                </ul>
            </div>
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

@section('script')
    <script type="module">
        import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.min.mjs';

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.min.mjs';
    </script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/pdfmake.min.js"
            integrity="sha512-axXaF5grZBaYl7qiM6OMHgsgVXdSLxqq0w7F4CQxuFyrcPmn0JfnqsOtYHUun80g6mRRdvJDrTCyL8LQqBOt/Q=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/vfs_fonts.min.js"
            integrity="sha512-EFlschXPq/G5zunGPRSYqazR1CMKj0cQc8v6eMrQwybxgIbhsfoO5NAMQX3xFDQIbFlViv53o7Hy+yCWw6iZxA=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    {{--    <script src="{{asset('main/libs/select2/select2.full.min.js')}}"></script>--}}
    <script src="{{asset('main/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{asset('js/datatableCustom/Datatable-0-4.min.js')}}"></script>
    <script src="{{asset('main/libs/moment/moment.js')}}"></script>
    <script src="{{asset('main/libs/bootstrap-datepicker/bootstrap-datepicker.js')}}"></script>

    <form id="form-delete" class="mainForm">
        <div class="modal modal-blur fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-header ">
                        <div class="modal-title">
                            Batalkan Pembayaran
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-capitalize text-center py-4">
                        <span class="ri-delete-bin-line ri-3x"></span>
                        <h4>Batalkan pembayaran tagihan?</h4>
                        <div class="">
                            anda yakin akan Batalkan pembayaran tagihan?
                        </div>
                    </div>
                    <div class="modal-body py-4">
                        <fieldset class="form-fieldset">
                            <div class="mb-3 row">
                                <label for="nocust" class="col-sm-4 col-form-label form-label-sm">NIS</label>
                                <div class="col">
                                    <input type="text" readonly class="form-control  form-control-sm" id="nocust"
                                           name="nocust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="nmcust" class="col-sm-4 col-form-label form-label-sm">Nama Siswa</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="nmcust"
                                           name="nmcust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="billnm" class="col-sm-4 col-form-label form-label-sm">Nama Tagihan</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="billnm"
                                           name="billnm">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="billam" class="col-sm-4 col-form-label form-label-sm">Nominal</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="billam"
                                           name="billam">
                                </div>
                            </div>
                        </fieldset>
                        <input type="hidden" id="delete_id" name="item_id" value="">
                        <input type="hidden" id="user_delete_id" name="custid" value="">
                    </div>
                    <div class="modal-footer ">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <input type="reset" class="btn btn-outline-secondary w-100" value="Batal"
                                           data-bs-dismiss="modal">
                                </div>
                                <div class="col">
                                    <input type="submit" value="Hapus" class="btn btn-danger w-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script type="text/javascript">
        const select2 = $(`[data-control='select2']`);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let dtOptions = {
            tableId: 'main_table',
            formId: 'filter-form',
            columnUrl: '{{($columnsUrl??null)}}',
            dataUrl: '{{($datasUrl??null)}}',
            dataColumns: [],
            thead: true,
            tfoot: true,
            paging: true,
            searching: true,
            fixedHeader: false,
            select: 'multi',
            pageLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
        };

        const modalDeleteElement = document.getElementById('modal-delete');
        const modalDelete = new bootstrap.Modal(document.getElementById('modal-delete'));

        modalDeleteElement.addEventListener('hide.bs.modal', function () {
            const form = document.getElementById('form-delete');
            form.reset();
        });

        document.querySelector('#main_table tbody').addEventListener('click', function (e) {
            if (e.target.closest('.btn-batal-bayar')) {
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

        document.getElementById('form-delete').addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm('delete');
        })


        function submitForm(form) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let request = null;
            switch (form) {
                case 'delete':
                    loadingAlert('Menghapus tagihan....');
                    const item_id = document.getElementById('delete_id').value;
                    const user_id = document.getElementById('user_delete_id').value;
                    let url = '{{route('admin.keuangan.penerimaan-siswa.data-penerimaan.destroy',':id')}}'
                    url = url.replace(':id', item_id)

                    if (!item_id || !user_id) {
                        errorAlert('Tagihan tidak ditemukan!');
                        return;
                    }
                    request = new Request(
                        url, {
                            method: "DELETE",
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            }, body: JSON.stringify({
                                user_id: user_id
                            })
                        });
                    break;
                default:
                    errorAlert('Data tidak valid!');
                    return;
            }

            fetch(request)
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw {status: response.status, message: data.message || response.statusText};
                    }
                    return data;
                })
                .then(data => {
                    dataReload(dtOptions.tableId);
                    successAlert(data.message);
                    modalDelete.hide();
                })
                .catch(error => {
                    if (error.status === 422) {
                        const errors = error.error || error.errors;
                        errorAlert(error.message);
                        if (errors) {
                            processErrors(errors)
                        }
                    } else {
                        const errorMessages = {
                            401: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan! <br> jika masalah masih terjadi silahkan login kembali!',
                            403: 'Anda tidak memiliki izin untuk mengakses halaman ini 😖',
                            404: 'Halaman yang dituju tidak ditemukan 🧐',
                            405: 'Metode tidak valid 🧐 <br>silahkan muat ulang halaman dan coba lagi!',
                            419: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan! <br> jika masalah masih terjadi silahkan login kembali!',
                            429: 'Terlalu banyak permintaan akses <br>silahkan tunggu beberapa saat 🙏',
                        };
                        errorAlert(errorMessages[error.status] || "Terjadi kesalahan, silahkan coba memuat ulang halaman");
                    }
                });
        }

        document.addEventListener("DOMContentLoaded", function () {
            if (dtOptions.dataUrl && dtOptions.columnUrl) {
                getDT(dtOptions);
                if (dtOptions.formId) {
                    let filterForm = $(`#${dtOptions.formId}`);
                    filterForm.on('submit', function (e) {
                        e.preventDefault();
                        dataReFilter(dtOptions.tableId);
                    });
                    filterForm.on('reset', function (e) {
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
                        dropdownParent: $this.parent(),
                    });
                });
            }

            const dariTanggal = $('#filter_dari_tanggal');
            const sampaiTanggal = $('#filter_sampai_tanggal');
            const periodeMulai = $('input[name="filter[periode_mulai]"]');
            const periodeAkhir = $('input[name="filter[periode_akhir]"]');

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

            periodeMulai.datepicker({
                format: "yyyymm",
                startView: "months",
                minViewMode: "months",
                autoclose: true
            }).on('changeDate', function (e) {
                periodeAkhir.datepicker('setStartDate', e.date);
            });

            periodeAkhir.datepicker({
                format: "yyyymm",
                startView: "months",
                minViewMode: "months",
                autoclose: true
            }).on('changeDate', function (e) {
                periodeMulai.datepicker('setEndDate', e.date);
            });

            const instansi = {
                nama_instansi: "{{ config('app.nama_instansi') }}",
                nama_sub_1: "{{ config('app.nama_sub_instansi_1') }}",
                nama_sub_2: "{{ config('app.nama_sub_instansi_2') }}",
                akreditasi: "{{ config('app.akreditasi') }}",
                alamat: "{{ config('app.alamat') }}",
                kontak: {
                    telepon: "{{ config('app.telepon') }}",
                    email: "{{ config('app.email') }}",
                    website: "{{ config('app.website') }}"
                }
            };
            const headerLogo = "{{ base64_encode(file_get_contents(public_path('logo.png'))) }}";
            const tandaTangan = @json($tanda_tangan);
            const userName = "{{ Auth::user()->name }}";
            const domisili = "{{ config('app.domisili') }}";
            const tanggalSekarang = "{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM YYYY') }}";
            const APP_NOVA = {{config('app.nova')}};
            const showVA = (nis) => APP_NOVA + String(nis).padStart(10, '0');

            async function generatePdf(title, bodyContent, unit_logo = false) {
                try {
                    let logo = 'data:image/png;base64,' + headerLogo;

                    if (unit_logo) {
                        logo = await getLogoUnit(unit_logo);
                    }

                    const orientation = 'portrait';
                    const pageMargins = [20, 20, 20, 20];
                    const tanggalSekarang = new Date().toLocaleDateString('id-ID', {
                        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
                    });
                    const availableWidth = getContentWidth('A4', orientation, pageMargins);

                    const headerTable = {
                        alignment: 'center',
                        table: {
                            widths: [60, '*'],
                            body: [[
                                logo ? {
                                    image: logo,
                                    width: 60,
                                    alignment: 'center'
                                } : '',
                                {
                                    stack: [
                                        instansi.nama_sub_1 ? {
                                            text: instansi.nama_sub_1.toUpperCase(),
                                            style: 'headerSmall'
                                        } : '',
                                        instansi.nama_sub_2 ? {
                                            text: instansi.nama_sub_2.toUpperCase(),
                                            style: 'headerSmall'
                                        } : '',
                                        {text: instansi.nama_instansi.toUpperCase(), style: 'headerBig'},
                                        instansi.akreditasi ? {text: instansi.akreditasi, style: 'headerSmall'} : '',
                                        instansi.alamat ? {text: instansi.alamat, style: 'headerSmall'} : '',
                                        {
                                            text: `Telp: ${instansi.kontak.telepon || '-'} | Email: ${instansi.kontak.email || '-'} | Web: ${instansi.kontak.website || '-'}`,
                                            style: 'headerSmall'
                                        }
                                    ],
                                    alignment: 'center'
                                }
                            ]]
                        },
                        layout: 'noBorders'
                    };

                    // Footer (shared)
                    const footer = {
                        columns: [
                            {text: '', width: '*'},
                            {
                                stack: [
                                    {
                                        text: `${domisili}, ${tanggalSekarang}`,
                                        margin: [0, 10, 0, 0],
                                        alignment: 'center'
                                    },
                                    tandaTangan ? {
                                        image: tandaTangan,
                                        width: 100,
                                        alignment: 'center'
                                    } : {},
                                    {text: userName, alignment: 'center'}
                                ],
                                width: 'auto'
                            }
                        ]
                    };

                    // Combine all content
                    const content = [
                        headerTable,
                        {
                            margin: [0, 5, 0, 5],
                            canvas: [
                                {type: 'line', x1: 0, y1: 0, x2: availableWidth, y2: 0, lineWidth: 2},
                                {
                                    type: 'line',
                                    x1: 0,
                                    y1: 3,
                                    x2: availableWidth,
                                    y2: 3,
                                    lineWidth: 0.5,
                                    lineColor: '#888'
                                }
                            ]
                        },
                        {text: String(title || '').toUpperCase(), style: 'title', margin: [0, 5, 0, 5]},
                        ...bodyContent,
                        footer
                    ];

                    // PDF definition
                    const docDefinition = {
                        info: {
                            title: String(title || 'KARTU PEMBAYARAN SISWA').toUpperCase(),
                            subject: 'KARTU PEMBAYARAN SISWA'
                        },
                        pageSize: 'A4',
                        pageOrientation: orientation,
                        pageMargins: pageMargins,
                        content: content,
                        styles: {
                            headerBig: {fontSize: 16, bold: true, alignment: 'center'},
                            headerSmall: {fontSize: 12, alignment: 'center'},
                            title: {fontSize: 14, bold: true, alignment: 'center'},
                            subTitle: {fontSize: 12, bold: true},
                            tableHeader: {bold: true, fillColor: '#ededed', alignment: 'center'},
                            small: {fontSize: 9, alignment: 'center'},
                            tableFont: {fontSize: 5}
                        },
                        defaultStyle: {font: 'Times'}
                    };

                    pdfMake.createPdf(docDefinition).open();

                    successAlert('File telah didownload <br>' +
                        '<p><span class="badge badge-dot bg-danger me-1"></span> Cek pada menu unduhan browser anda untuk memeriksa!</p>');
                } catch (e) {
                    console.error('Error generating PDF:', e);
                    errorAlert(e.message);
                }
            }

            document.getElementById('download-pdf').addEventListener('click', function (e) {
                e.preventDefault();
                const form = new FormData(document.getElementById('filter-form'));
                const params = new URLSearchParams();
                for (const [key, value] of form.entries()) {
                    params.append(key, value);
                }
                const mulaiTanggalValue = params.get('filter[dari_tanggal]');
                const akhirTanggalValue = params.get('filter[sampai_tanggal]');
                const invalidValues = [null, '', 'undefined', 'all'];

                if (invalidValues.includes(mulaiTanggalValue) || invalidValues.includes(akhirTanggalValue)) {
                    warningAlert('Silahkan isikan tanggal terlebih dahulu!');
                    return;
                }

                loadingAlert(`Membuat Rekap ... <br> Proses ini membutuhkan waktu beberapa saat<br><hr>
                    <p><span class="badge badge-dot bg-danger me-1"></span> Pastikan telah mengisi Tanggal Transaksi! </p>
                    <p><span class="badge badge-dot bg-danger me-1"></span> Pastikan browser anda tidak memblokir <i>POP-UP</i>! </p>
                `);

                let url = '{{route('admin.keuangan.penerimaan-siswa.data-penerimaan.get-data')}}';
                params.append('draw', '2');
                params.append('start', '0');
                params.append('length', '1500');

                const fullUrl = `${url}?${params.toString()}`;
                const request = new Request(
                    fullUrl, {
                        method: "GET",
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        }
                    });

                fetch(request)
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw {status: response.status, message: data.message || response.statusText};
                        }
                        return data;
                    })
                    .then(data => {
                        generatePdfRekapTagihanPdfMake(data.data || [])
                    })
                    .catch(error => {
                        if (error.status === 422) {
                            const errors = error.error || error.errors;
                            errorAlert(error.message);
                            if (errors) {
                                processErrors(errors)
                            }
                        } else {
                            const errorMessages = {
                                401: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan! <br> jika masalah masih terjadi silahkan login kembali!',
                                403: 'Anda tidak memiliki izin untuk mengakses halaman ini 😖',
                                404: 'Halaman yang dituju tidak ditemukan 🧐',
                                405: 'Metode tidak valid 🧐 <br>silahkan muat ulang halaman dan coba lagi!',
                                419: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan! <br> jika masalah masih terjadi silahkan login kembali!',
                                429: 'Terlalu banyak permintaan akses <br>silahkan tunggu beberapa saat 🙏',
                            };
                            errorAlert(errorMessages[error.status] || "Terjadi kesalahan, silahkan coba memuat ulang halaman");
                        }
                    });
            });

            pdfMake.fonts = {
                Times: {
                    normal: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman@1.0.4/Times New Roman.ttf',
                    bold: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman-bold@1.0.4/Times New Roman Bold.ttf',
                    italics: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman-italic@1.0.4/Times New Roman Italic.ttf',
                    bolditalics: 'https://cdn.jsdelivr.net/npm/@canvas-fonts/times-new-roman-bold@1.0.4/Times New Roman Bold.ttf'
                }, Roboto: {
                    normal: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Regular.ttf',
                    bold: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Medium.ttf',
                    italics: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-Italic.ttf',
                    bolditalics: 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.66/fonts/Roboto/Roboto-MediumItalic.ttf'
                },
            };

            function formatMetodePembayaran(data) {
                const descriptions = {
                    '1140000': 'Manual Cash',
                    '1140001': 'Manual BMI',
                    '1140002': 'Manual SALDO',
                    '1140003': 'Transfer Bank Lain',
                    '1140004': 'INFAQ',
                    '1140005': 'Transfer Bank BRI',
                    '1200001': 'Loket Manual - Beasiswa',
                    '1200002': 'Loket Manual - Potongan',
                    '1': 'H2H VA BMI - ATM',
                    '2': 'H2H VA BMI - Teller',
                    '3': 'H2H VA BMI - IBANK',
                    '4': 'H2H VA BMI - EDC',
                    '5': 'H2H VA BMI - MOBILE',
                    '6': 'ANDROID',
                    null: 'Nomor VA',
                    '': 'Nomor VA'
                };
                return descriptions[data] || data;
            }

            function formatRupiah(amount) {
                if (!amount) return 'Rp 0';
                return 'Rp. ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            function getContentWidth(pageSize = 'A4', orientation = 'portrait', margins = [30, 30, 30, 30]) {
                const sizes = {
                    A4: [595.28, 841.89],
                    A3: [841.89, 1190.55],
                    LETTER: [612, 792],
                    LEGAL: [612, 1008]
                };
                const key = String(pageSize).toUpperCase();
                const size = sizes[key] || sizes.A4;

                // swap width/height for landscape
                const pageW = orientation === 'landscape' ? size[1] : size[0];
                const [ml, , mr] = margins;
                return pageW - ml - mr;
            }

            async function getLogoUnit(unit = false) {
                const fallbackLogo = 'data:image/png;base64,' + "{{ base64_encode(file_get_contents(public_path('logo.png'))) }}";
                try {
                    if (!unit) {
                        throw 'error';
                    }
                    const cacheKey = `logo_unit_${unit}`;
                    const cachedLogo = localStorage.getItem(cacheKey);
                    if (cachedLogo && cachedLogo.startsWith('data:image')) {
                        return cachedLogo;
                    }
                    const params = new URLSearchParams();
                    params.append('unit', unit);
                    const request = new Request(
                        `{{ route('admin.master-data.get-logo') }}?${params.toString()}`,
                        {
                            method: "GET",
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        }
                    );
                    const response = await fetch(request);
                    if (!response.ok) {
                        throw 'error';
                    }
                    const result = await response.json();
                    if (!result.data) {
                        throw 'error';
                    }
                    localStorage.setItem(cacheKey, result.data);
                    return result.data;
                } catch {
                    return fallbackLogo;
                }
            }

            function generatePdfRekapTagihanPdfMake(rows) {
                if (!Array.isArray(rows) || rows.length === 0) {
                    generatePdf('REKAP DATA PENERIMAAN', [
                        {text: 'Tidak ada data', alignment: 'center', margin: [0, 20, 0, 0]}
                    ]);
                    return;
                }

                const tableBody = [
                    ['No', 'NIS', 'NAMA', 'Unit', 'Kelas', 'Nama Tagihan', 'Tagihan', 'Metode', 'Tanggal Bayar', 'Tahun AKA']
                        .map(h => ({text: h, style: 'tableHeader'}))
                ];

                rows.forEach((item, index) => {
                    const tanggalBayar = item.PAIDDT
                        ? new Date(item.PAIDDT).toLocaleString('id-ID', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        })
                        : '-';

                    tableBody.push([
                        {text: index + 1, alignment: 'center', border: [true, true, true, true]},
                        {text: item.nocust ?? '-', border: [true, true, true, true]},
                        {text: item.nmcust ?? '-', border: [true, true, true, true]},
                        {text: item.CODE02 ?? '-', border: [true, true, true, true]},
                        {text: `${item.DESC02 ?? ''} ${item.DESC03 ?? ''}`.trim() || '-', border: [true, true, true, true]},
                        {text: item.BILLNM ?? '-', border: [true, true, true, true]},
                        {text: formatRupiah(item.BILLAM ?? 0), alignment: 'right', border: [true, true, true, true]},
                        {text: formatMetodePembayaran(item.FIDBANK ?? ''), border: [true, true, true, true]},
                        {text: tanggalBayar, border: [true, true, true, true]},
                        {text: item.BTA ?? '-', border: [true, true, true, true]},
                    ]);
                });

                generatePdf('REKAP PEMBAYARAN SISWA', [
                    {
                        table: {
                            headerRows: 1,
                            widths: ['4%', '9%', '13%', '10%', '12%', '15%', '9%', '10%', '11%', '7%'],
                            body: tableBody
                        },
                        layout: {
                            fillColor: (rowIndex) => rowIndex === 0 ? '#ededed' : null,
                            hLineWidth: () => 0.5,
                            vLineWidth: () => 0.5
                        },
                        margin: [0, 0, 0, 10],
                        fontSize: 8
                    }
                ]);
            }

            function generateKuitansi(biayaLayanan = false) {
                let data = DT[`${dtOptions.tableId}`].rows({selected: true}).data().toArray();
                data = data.filter(item => Number(item.PAIDST) === 1 && item.PAIDDT);

                if (!data[0]) {
                    warningAlert('silahkan pilih data pembayaran yang sudah lunas!');
                    return;
                }

                let firstValue = data[0]?.nocust;
                let allSame = data.every(row => row.nocust === firstValue);

                if (!allSame) {
                    warningAlert('silahkan pilih siswa yang sama!');
                    return;
                }

                let siswa = data[0];
                let nocust = siswa.nocust === null || siswa.nocust === '' || siswa.nocust === '-' || !siswa.nocust ? false : siswa.nocust;
                const uniqueMetode = [...new Set(data.map(item => String(item.FIDBANK ?? '')))].filter(Boolean);
                const metodeLabel = uniqueMetode.length > 1
                    ? 'Beragam'
                    : formatMetodePembayaran(siswa.FIDBANK ?? '');

                const mainTable = [
                    [(nocust ? 'NIS ' : 'No. Pendaftaran'), ': ' + (nocust ? nocust : siswa.NUM2ND), 'Unit', ': ' + siswa.CODE02],
                    [(nocust ? 'No. VA ' : '-'), ': ' + (nocust ? showVA(nocust) : ''), 'Kelas', ': ' + (siswa.DESC02 ?? '') + ' ' + (siswa.DESC03 ?? '')],
                    ['Nama ', ': ' + siswa.nmcust, 'Ayah', ': ' + (siswa.GENUS ?? '-')],
                    ['Metode Bayar', ': ' + metodeLabel, 'Ibu', ': ' + (siswa.GENUS1 ?? '')],
                ]

                let content = [];
                const headerContent = [
                    {
                        table: {
                            widths: ['15%', '35%', '15%', '35%'],
                            body: mainTable
                        },
                        layout: 'noBorders'
                    },
                    {
                        text: '',
                        margin: [0, 5, 0, 5]
                    },
                ]

                content.push(headerContent);

                const tableBody = [];
                tableBody.push(
                    ['#', 'Nama Tagihan', 'Periode', 'Tagihan', 'Bayar', 'Metode', 'Tanggal Bayar']
                        .map(h => ({text: h, style: 'tableHeader'})),
                );

                let totalTagihan = 0;

                data.forEach((item, index) => {
                    let tanggalBayar = item.PAIDDT;
                    if (tanggalBayar && tanggalBayar !== '' && tanggalBayar !== '0000-00-00 00:00:00') {
                        tanggalBayar = new Date(tanggalBayar).toLocaleDateString('id-ID', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                    }

                    totalTagihan += item.BILLAM;

                    tableBody.push([
                        {text: index + 1, alignment: 'center'},
                        {text: item.BILLNM, alignment: 'left'},
                        {text: item.BTA, alignment: 'left'},
                        {text: formatRupiah(item.BILLAM), alignment: 'right'},
                        {text: formatRupiah(item.BILLAM), alignment: 'right'},
                        {text: formatMetodePembayaran(item.FIDBANK ?? ''), alignment: 'left'},
                        {text: tanggalBayar, alignment: 'left'},
                    ]);
                })

                if (biayaLayanan) {
                    tableBody.push([
                        {colSpan: 4, text: 'Biaya Layanan', alignment: 'right', style: 'tableHeader'},
                        {}, {}, {},
                        {text: formatRupiah(2000), alignment: 'right'},
                        {}, {}
                    ])
                }


                tableBody.push([
                    {colSpan: 4, text: 'Total', alignment: 'right', style: 'tableHeader'},
                    {}, {}, {},
                    {text: formatRupiah(totalTagihan + (biayaLayanan ? 2000 : 0)), alignment: 'right'},
                    {}, {}
                ])

                content.push({
                    table: {
                        widths: ['3%', '22%', '11%', '14%', '14%', '16%', '20%'],
                        body: tableBody,
                    },
                    layout: {
                        fillColor: (rowIndex) => rowIndex === 0 ? '#ededed' : null,
                        hLineWidth: () => 0.5,
                        vLineWidth: () => 0.5
                    },
                    margin: [0, 0, 0, 10],
                    fontSize: 12
                });

                generatePdf('KUITANSI', content, siswa.CODE02);
            }

            document.getElementById('cetak-kuitansi').addEventListener('click', function (e) {
                e.preventDefault();
                generateKuitansi();
            });

            document.getElementById('cetak-kuitansi-2000').addEventListener('click', function (e) {
                e.preventDefault();
                generateKuitansi(true);
            });

            document.getElementById('cetak-kartu-siswa').addEventListener('click', async function (e) {
                e.preventDefault();
                loadingAlert('Membuat Kartu Siswa');
                let url = '{{route('admin.keuangan.penerimaan-siswa.data-penerimaan.cetak-kartu-siswa')}}';
                const form = new FormData(document.getElementById('filter-form'));
                const params = new URLSearchParams();
                for (const [key, value] of form.entries()) {
                    params.append(key, value);
                }
                let data = DT[`${dtOptions.tableId}`].rows({selected: true}).data();
                if (!data[0]) {
                    warningAlert('silahkan pilih siswa!')
                    return;
                }
                params.append('custid', data[0].CUSTID)
                const unit = data[0].CODE02;
                const fullUrl = `${url}?${params.toString()}`;
                const request = new Request(
                    fullUrl, {
                        method: "GET",
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                try {
                    const response = await fetch(request);

                    if (!response.ok) {
                        throw await buildHttpError(response);
                    }

                    const result = await response.json();

                    if (!result?.tagihans?.length) {
                        throw createError("Data Tagihan Kosong", 422);
                    }
                    const data = await generateKartuSiswa(result);
                    const pdf = await generatePdf('KARTU PEMBAYARAN SISWA', data, unit)
                    // if (!result['tagihans'] || result['tagihans'].length === 0) {
                    //     console.log('kosong');
                    //     const error = new Error("Data Tagihan Kosong");
                    //     error.status = 422;
                    //     throw error;
                    // }

                    if (pdf) {
                        successAlert('Sukses, Rekap telah dicetak');
                    }
                } catch (error) {
                    if (error.status === 422) {
                        const errors = error.error || error.errors;
                        errorAlert(error.message);
                        if (errors) {
                            processErrors(errors)
                        }
                    } else {
                        const errorMessages = {
                            401: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan! <br> jika masalah masih terjadi silahkan login kembali!',
                            403: 'Anda tidak memiliki izin untuk mengakses halaman ini 😖',
                            404: 'Halaman yang dituju tidak ditemukan 🧐',
                            405: 'Metode tidak valid 🧐 <br>silahkan muat ulang halaman dan coba lagi!',
                            419: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman untuk melanjutkan! <br> jika masalah masih terjadi silahkan login kembali!',
                            429: 'Terlalu banyak permintaan akses <br>silahkan tunggu beberapa saat 🙏',
                        };
                        errorAlert(errorMessages[error.status] || "Terjadi kesalahan, silahkan coba memuat ulang halaman");
                    }
                }
            });

            async function generateKartuSiswa(data) {
                try {
                    const bodyContent = [];

                    let siswa = data.siswa;
                    let nocust = siswa.NOCUST === null || siswa.NOCUST === '' || siswa.NOCUST === '-' || !siswa.NOCUST ? false : siswa.NOCUST;

                    const mainTable = [
                        [(nocust ? 'NIS ' : 'No. Pendaftaran'), ': ' + (nocust ? nocust : siswa.NUM2ND), 'Unit', ': ' + siswa.CODE02].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                        [(nocust ? 'No. VA ' : '-'), ': ' + (nocust ? showVA(nocust) : ''), 'Kelas', ': ' + siswa.DESC02 + ' '+ siswa.DESC03].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                        ['Nama ', ': ' + siswa.NMCUST,'Ayah', ': ' + (siswa.GENUS ?? '-')].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                        ['', ' ',  'Ibu', ': ' + (siswa.GENUS1 ?? '')].map(h => ({
                            text: h,
                            border: [false, false, false, false]
                        })),
                    ]

                    bodyContent.push({
                        table: {
                            widths: ['15%', '35%', '15%', '35%'],
                            body: mainTable
                        },
                        layout: {
                            fillColor: null,
                            hLineWidth: () => 0.5,
                            vLineWidth: () => 0.5
                        },
                        margin: [0, 0, 0, 5],
                        fontSize: 9
                    });

                    const tableBody = [
                        ['#', 'Tanggal Bayar', 'Tahun Akademik', 'Nama Tagihan', 'Tagihan', 'Status']
                            .map(h => ({text: h, style: 'tableHeader'}))
                    ];

                    let total = 0;
                    const sortedTagihans = [...(data.tagihans || [])].sort((a, b) => {
                        const urutA = Number(a?.FUrutan ?? 0);
                        const urutB = Number(b?.FUrutan ?? 0);
                        if (urutA !== urutB) return urutA - urutB;
                        return String(a?.BILLNM ?? '').localeCompare(String(b?.BILLNM ?? ''));
                    });

                    sortedTagihans.forEach((item, index) => {
                        const formatDateTime = (val) => {
                            if (!val) return '-';
                            const dt = new Date(val);
                            if (Number.isNaN(dt.getTime())) return '-';
                            const dd = String(dt.getDate()).padStart(2, '0');
                            const mm = String(dt.getMonth() + 1).padStart(2, '0');
                            const yyyy = dt.getFullYear();
                            const hh = String(dt.getHours()).padStart(2, '0');
                            const mi = String(dt.getMinutes()).padStart(2, '0');
                            const ss = String(dt.getSeconds()).padStart(2, '0');
                            return `${dd}/${mm}/${yyyy} ${hh}:${mi}:${ss}`;
                        };
                        const tanggalBayar = item.PAIDDT
                            ? formatDateTime(item.PAIDDT)
                            : '-';
                        tableBody.push([
                            {text: String(index + 1), alignment: 'center', border: [true, true, true, true]},
                            {text: tanggalBayar, border: [true, true, true, true]},
                            {text: item.BTA || '-', border: [true, true, true, true]},
                            {text: item.BILLNM || '-', border: [true, true, true, true]},
                            {text: formatRupiah(item.BILLAM), alignment: 'right', border: [true, true, true, true]},
                            {text: 'LUNAS', alignment: 'center', border: [true, true, true, true]}
                        ]);

                        total += item.BILLAM;
                    });

                    tableBody.push([
                        {text: 'Total', colSpan: 4, style: 'tableHeader', border: [true, true, true, true]},
                        '', '', '',
                        {
                            text: formatRupiah(total),
                            style: 'tableHeader',
                            alignment: 'right',
                            border: [true, true, true, true]
                        },
                        {
                            text: '',
                            border: [true, true, true, true]
                        }
                    ])

                    bodyContent.push({
                        table: {
                            widths: ['8%', '20%', '18%', '27%', '15%', '12%'],
                            body: tableBody
                        },
                        layout: {
                            fillColor: rowIndex => rowIndex === 0 ? '#ededed' : null,
                            hLineWidth: () => 0.5,
                            vLineWidth: () => 0.5
                        },
                        margin: [0, 0, 0, 0],
                        fontSize: 9
                    });

                    return bodyContent;
                } catch (e) {
                    console.log(e)
                }
            }

            function createError(message, status, extra = {}) {
                const err = new Error(message);
                err.status = status;
                Object.assign(err, extra);
                return err;
            }

            async function buildHttpError(response) {
                const status = response.status;
                const contentType = response.headers.get('content-type');

                let message = `Request failed with status ${status}`;
                let extra = {};

                try {
                    if (contentType?.includes('application/json')) {
                        const data = await response.json();
                        message = data.message ?? message;
                        extra = data;
                    } else {
                        const text = await response.text();
                        message = text || message;
                    }
                } catch {
                }

                return createError(message, status, extra);
            }
        });
    </script>

    {!! ($modalLink??'') !!}
@endsection
