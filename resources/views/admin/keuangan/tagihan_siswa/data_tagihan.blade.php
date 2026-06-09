@extends('layouts.admin_new')
@section('title',$dataTitle??$mainTitle??$title??'')
@section('style')
    <link rel="stylesheet" href="{{asset('main/libs/select2/select2.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-bs5/datatables.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.css')}}">
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
            <div class="row px-5 mb-2">
                <ul class="list-group list-group-timeline">
                    <li class="list-group-item list-group-timeline-danger">
                        <strong>Pastikan browser anda tidak memblokir <i>POP-UP</i>!</strong>
                    </li>
                </ul>
            </div>
            <form id="filter-form">
                <fieldset class="form-fieldset">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-5">
                                <label class="form-label" for="tanggal-pembuatan">Tanggal Buat Tagihan<span
                                        class="text-warning">*</span>(tanggal-bulan-tahun - tanggal-bulan-tahun)</label>
                                <input type="text" id="tanggal-pembuatan" name="filter[tanggal-pembuatan]"
                                       placeholder="tanggal/bulan/tahun"
                                       class="form-control" autocomplete="false" inputmode="numeric"/>
                            </div>
                            <div class="mb-5">
                                <label class="form-label" for="tahun_akademik">
                                    Tahun Akademik
                                </label>
                                <select class="form-select" id="tahun_akademik"
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
                            <div class="mb-5">
                                <label class="form-label" for="post">
                                    Nama Tagihan
                                </label>
                                <select class="form-select" id="post"
                                        name="filter[post][]"
                                        multiple
                                        data-control="select2"
                                        data-placeholder="Pilih Nama Tagihan">
                                    @isset($post)
                                        @foreach($post as $item)
                                            <option
                                                value="{{$item->tagihan}}">{{$item->tagihan}}</option>
                                        @endforeach
                                    @else
                                        <option>data kosong</option>
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="col mb-5">
                                <label class="form-label" for="filter[angkatan]]">
                                    Angkatan Siswa
                                </label>
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
                            <div class="col mb-5">
                                <label class="form-label" for="filter[kelas]">
                                    Kelas
                                </label>
                                <select class="form-select" id="filter[kelas]" name="filter[kelas]"
                                        data-control="select2" data-placeholder="Pilih Kelas">
                                    <option value="all">Semua</option>
                                    @isset($kelas)
                                        @foreach($kelas as $item)
                                            <option
                                                value="{{$item->unit}}~~{{$item->jenjang}}~~{{$item->kelas}}">{{$item->unit}}
                                                - {{$item->jenjang}} {{$item->kelas}}</option>
                                        @endforeach
                                    @else
                                        <option>data kosong</option>
                                    @endisset
                                </select>
                            </div>
                            <div class="col mb-5">
                                <label class="form-label" for="filter[siswa]">
                                    Siswa
                                </label>
                                <input class="form-control" id="filter[siswa]" name="filter[siswa]"
                                       placeholder="Masukkan NIS/NAMA Siswa" data-placeholder="Pilih siswa">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="d-flex justify-content-center flex-column flex-md-row justify-content-md-end gap-4">
                            <button type="button" class="btn btn-facebook" id="cetak-kartu-siswa">
                                <span class="ri-info-card-line me-2"></span>
                                Cetak Kartu Siswa
                            </button>
                            <button type="button" class="btn btn-google-plus btn-print-rekap">
                                <span class="ri-file-pdf-2-line me-2"></span>
                                Cetak Cekap
                            </button>
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

@section('script')
    <form id="form-delete" class="mainForm">
        <div class="modal modal-blur fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-header ">
                        <div class="modal-title">
                            Hapus Data
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-capitalize text-center py-4">
                        <span class="ri-delete-bin-line ri-3x"></span>
                        <h4>Hapus Tagihan Siswa?</h4>
                        <div class="">
                            anda yakin akan menghapus data tagihan Siswa?
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

    <form id="form-ubah-urutan" class="mainForm">
        <div class="modal modal-blur fade" id="modal-ubah-urutan" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-header ">
                        <div class="modal-title">
                            Ubah Urutan
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-capitalize text-center py-4">
                        <span id="logo-urutan" class="ri-delete-bin-line ri-5x"></span>
                        <h4 id="caption-urutan">Ubah Urutan Tagihan Siswa?</h4>
                        <span id="sub-caption-urutan"></span>
                    </div>
                    <div class="modal-body py-4">
                        <fieldset class="form-fieldset">
                            <div class="mb-3 row">
                                <label for="nocust" class="col-sm-4 col-form-label form-label-sm">NIS</label>
                                <div class="col">
                                    <input type="text" readonly class="form-control  form-control-sm" id="urutan-nocust"
                                           name="nocust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="nmcust" class="col-sm-4 col-form-label form-label-sm">Nama Siswa</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="urutan-nmcust"
                                           name="nmcust">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="billnm" class="col-sm-4 col-form-label form-label-sm">Nama Tagihan</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="urutan-billnm"
                                           name="billnm">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="billam" class="col-sm-4 col-form-label form-label-sm">Nominal</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="urutan-billam"
                                           name="billam">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="furutan" class="col-sm-4 col-form-label form-label-sm">Urutan
                                    Tagihan</label>
                                <div class="col-sm-8">
                                    <input type="text" readonly class="form-control form-control-sm" id="urutan-furutan"
                                           name="furutan">
                                </div>
                            </div>
                        </fieldset>
                        <input type="hidden" id="urutan_tagihan_id" name="item_id" value="">
                        <input type="hidden" id="user_urutan_tagihan_id" name="custid" value="">
                        <input type="hidden" id="urutan_tagihan" name="urutan_tagihan" value="">
                    </div>
                    <div class="modal-footer ">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <input type="reset" class="btn btn-outline-secondary w-100" value="Batal"
                                           data-bs-dismiss="modal">
                                </div>
                                <div class="col">
                                    <input id="submit-urutan-tagihan" type="submit" value="Naikkan"
                                           class="btn btn-secondary w-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script src="{{asset('main/libs/select2/select2.js')}}"></script>
    <script src="{{asset('main/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{asset('js/datatableCustom/Datatable-0-4.js')}}?v=20260602"></script>
    <script src="{{asset('main/libs/moment/moment.js')}}"></script>
    <script src="{{asset('main/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js')}}"></script>

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
            scrollX: true,
            order: [[11, 'asc']],
            paging: true,
            searching: true,
            fixedHeader: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
            select: true,
            rowId: 'AA',
            buttons: ["excel", "pdf", "print"],
            pdfOrientation: 'landscape',
            pdfPageSize: 'A3',
            pdfMargins: [10, 14, 10, 14],
            pdfFontSize: 6,
            pdfHeaderFontSize: 7,
        };

        const modalDeleteElement = document.getElementById('modal-delete');
        const modalDelete = new bootstrap.Modal(document.getElementById('modal-delete'));

        const modalUrutElement = document.getElementById('modal-ubah-urutan');
        const modalUrut = new bootstrap.Modal(document.getElementById('modal-ubah-urutan'));

        modalDeleteElement.addEventListener('hide.bs.modal', function () {
            const form = document.getElementById('form-delete');
            form.reset();
        });

        function fillFormValue(id, rowEl) {
            const rowData = DT[`${dtOptions.tableId}`].row(rowEl).data();
            Object.entries(rowData).forEach(([key, value]) => {
                let input = document.querySelector(`#${id} [name="${key.toLowerCase()}"]`);
                if (input) {
                    input.value = value ?? '';
                }
            });
            if (id === 'form-ubah-urutan') {
                const urutanId = document.getElementById('urutan_tagihan_id');
                const custId = document.getElementById('user_urutan_tagihan_id');
                const furutan = document.getElementById('urutan-furutan');
                if (urutanId) urutanId.value = rowData.item_id ?? rowData.AA ?? '';
                if (custId) custId.value = rowData.CUSTID ?? '';
                if (furutan) furutan.value = rowData.FUrutan ?? rowData.furutan ?? '0';
            }
        }

        function canChangeUrutan(rowEl) {
            const rowData = DT[`${dtOptions.tableId}`].row(rowEl).data();
            const urut = parseInt(rowData?.FUrutan ?? rowData?.furutan ?? '0', 10);
            if (!Number.isFinite(urut) || urut <= 0) {
                warningAlert('Tagihan dengan urutan 0 tidak dapat dinaikkan atau diturunkan.');
                return false;
            }
            return true;
        }

        document.querySelector('#main_table tbody').addEventListener('click', function (e) {
            if (e.target.closest('.btn-hapus')) {
                const rowEl = e.target.closest('tr');

                if (rowEl) {
                    fillFormValue('form-delete', rowEl);
                    modalDelete.show();
                }
            } else if (e.target.closest('.btn-naik-urut')) {
                const rowEl = e.target.closest('tr');
                if (rowEl && canChangeUrutan(rowEl)) {
                    document.getElementById("logo-urutan").className = "ri-arrow-up-line ri-5x me-2";
                    document.getElementById("caption-urutan").textContent = "Naikkan Urutan Tagihan?";
                    document.getElementById("sub-caption-urutan").textContent = "Anda yakin akan menaikkan urutan tagihan?";
                    document.getElementById("submit-urutan-tagihan").value = "Naikkan";
                    document.getElementById("urutan_tagihan").value = "naik";
                    fillFormValue('form-ubah-urutan', rowEl);
                    modalUrut.show();
                }
            } else if (e.target.closest('.btn-turun-urut')) {
                const rowEl = e.target.closest('tr');
                if (rowEl && canChangeUrutan(rowEl)) {
                    document.getElementById("logo-urutan").className = "ri-arrow-down-line ri-5x me-2";
                    document.getElementById("caption-urutan").textContent = "Turunkan Urutan Tagihan?";
                    document.getElementById("sub-caption-urutan").textContent = "Anda yakin akan menurunkan urutan tagihan?";
                    document.getElementById("submit-urutan-tagihan").value = "Turunkan";
                    document.getElementById("urutan_tagihan").value = "turun";
                    fillFormValue('form-ubah-urutan', rowEl);
                    modalUrut.show();
                }
            }
        });

        document.getElementById('form-delete').addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm('delete');
        })

        document.getElementById('form-ubah-urutan').addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm('ubah-urutan');
        })


        function submitForm(form) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let request, item_id, user_id, url = null;
            switch (form) {
                case 'delete':
                    loadingAlert('Menghapus tagihan....');
                    item_id = document.getElementById('delete_id').value;
                    user_id = document.getElementById('user_delete_id').value;
                    url = '{{route('admin.keuangan.hapus-tagihan.destroy',':id')}}'
                    url = url.replace(':id', item_id)

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
                case'ubah-urutan':
                    loadingAlert('Mengubah Urutan....');
                    item_id = document.getElementById('urutan_tagihan_id').value;
                    user_id = document.getElementById('user_urutan_tagihan_id').value;
                    url = '{{route('admin.keuangan.tagihan-siswa.data-tagihan.ubah-urutan',':id')}}';
                    url = url.replace(':id', item_id);
                    const urutForm = document.getElementById('form-ubah-urutan');
                    const form = new FormData(urutForm)
                    request = new Request(
                        url, {
                            method: "POST",
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                            }, body: form
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
                    modalUrut.hide();
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

            $(document).on('click', '.btn-print-rekap', function (e) {
                loadingAlert(`Membuat Rekap ... <br> Proses ini membutuhkan waktu beberapa saat<br><hr>
                    <p><span class="badge badge-dot bg-danger me-1"></span> Pastikan browser anda tidak memblokir <i>POP-UP</i>! </p>
                `);
                let data = $(`#${dtOptions.formId}`).serialize();
                if (data) {
                    const csrfToken = $('meta[name="csrf-token"]').attr('content')
                    let ajaxOptions = {
                        url: '{{route('admin.keuangan.tagihan-siswa.data-tagihan.cetak-rekap')}}',
                        type: 'get',
                        data: data,
                        datatype: 'json',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        contentType: false,
                        processData: true,
                        cache: false,
                        xhrFields: {
                            responseType: 'blob'
                        },
                        timeout: 50000
                    }
                    $.ajax(ajaxOptions).done(function (response, status, xhr) {
                        try {
                            let blob = new Blob([response], {type: 'application/pdf'});
                            if (typeof window.navigator.msSaveBlob !== 'undefined') {
                                window.navigator.msSaveBlob(blob, filename);
                            } else {
                                let URL = window.URL || window.webkitURL;
                                let previewUrl = URL.createObjectURL(blob);
                                window.open(previewUrl, '_blank');
                            }

                        } catch (ex) {
                            console.log(ex);
                        }
                        successAlert('File tagihan terbuka pada tab baru');
                    }).fail(function (xhr) {
                        if (xhr.status === 422) {
                            const errMessage = response.message || xhr.responseJSON.message;
                            errorAlert(errMessage)
                        } else {
                            const errMessages = {
                                401: 'Anda tidak memiliki izin untuk mengakses halaman ini 😖',
                                403: 'Anda tidak memiliki izin untuk mengakses halaman ini 😖',
                                404: 'Halaman yang dituju tidak ditemukan 🧐',
                                405: 'Metode tidak valid 🧐 <br>silahkan muat ulang halaman dan coba lagi!',
                                419: 'token anda sudah tidak valid 🙏 <br>Silahkan muat ulang halaman untuk mendapat token baru!',
                                429: 'Terlalu banyak permintaan akses <br>silahkan tunggu beberapa saat 🙏',
                                '5xx': 'Terjadi kesalahan saat memproses permintaan 😵‍💫. <br> silahkan muat ulang halaman'
                            };
                            const errMessage =
                                errMessages[xhr.status] ||
                                (xhr.status >= 500 && xhr.status <= 504 ? errMessages['5xx'] :
                                    'Tidak dapat terhubung ke server <br> Silahkan coba muat ulang halaman atau periksa koneksi internet anda.');
                            errorAlert(errMessage);
                        }
                    })
                } else {
                    warningAlert('Isikan form')
                }
            });

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

            let date = $('#tanggal-pembuatan');
            date.daterangepicker({
                autoUpdateInput: false,
                todayHighlight: true,
                autoclose: true,
                locale: {
                    format: 'DD-MM-YYYY',
                    separator: " - ",
                    applyLabel: "Terapkan",
                    cancelLabel: "Batal",
                    fromLabel: "Dari",
                    toLabel: "Ke",
                    customRangeLabel: "Kustom",
                    daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                    monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                    firstDay: 0,
                },
                maxDate: moment()
            }, function (start, end) {
                let duration = end.diff(start, 'days');
                if (duration > 100) {
                    warningAlert("Maksimal 100 hari.");
                    date.data('daterangepicker').setStartDate(start);
                    date.data('daterangepicker').setEndDate(start.clone().add(6, 'days'));
                }
            });

            date.on('apply.daterangepicker hide.daterangepicker', function (ev, picker) {
                if (picker.startDate && picker.endDate) {
                    $(this).val(picker.startDate.format('DD-MM-YYYY') + ' ~ ' + picker.endDate.format('DD-MM-YYYY'));
                }
            });

            date.on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
            });

            date.on('apply.daterangepicker', function (ev, picker) {
                let duration = picker.endDate.diff(picker.startDate, 'days');
                if (duration > 6) {
                    picker.setEndDate(picker.startDate.clone().add(2, 'days'));
                }
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

                    // Header (shared)
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
                        {text: title.toUpperCase(), style: 'title', margin: [0, 5, 0, 5]},
                        ...bodyContent,
                        footer
                    ];

                    // PDF definition
                    const docDefinition = {
                        info: {
                            title: String(title || 'KARTU TAGIHAN SISWA').toUpperCase(),
                            subject: 'KARTU TAGIHAN SISWA'
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

            document.getElementById('cetak-kartu-siswa').addEventListener('click', async function (e) {
                e.preventDefault();
                loadingAlert('Membuat Kartu Siswa');
                let url = '{{route('admin.keuangan.tagihan-siswa.data-tagihan.cetak-kartu-siswa')}}';
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
                    const pdf = await generatePdf('KARTU TAGIHAN SISWA', data, unit)
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

            async function getLogoUnit(unit = false) {
                const fallbackLogo = 'data:image/png;base64,' + "{{ base64_encode(file_get_contents(public_path('logo.png'))) }}";
                try {
                    if (!unit) {
                        throw 'error';
                    }
                    const cacheKey = `logo_unit_${unit}`;
                    const cachedLogo = localStorage.getItem(cacheKey);
                    if (cachedLogo) {
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
                        const tanggalBayar = item.PAIDDT
                            ? new Date(item.PAIDDT).toLocaleString('id-ID', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })
                            : '-';
                        tableBody.push([
                            {text: String(index + 1), alignment: 'center', border: [true, true, true, true]},
                            {text: tanggalBayar, border: [true, true, true, true]},
                            {text: item.BTA || '-', border: [true, true, true, true]},
                            {text: item.BILLNM || '-', border: [true, true, true, true]},
                            {text: formatRupiah(item.BILLAM), alignment: 'right', border: [true, true, true, true]},
                            {
                                text: Number(item.PAIDST) === 1 ? 'LUNAS' : 'BELUM LUNAS',
                                alignment: 'center',
                                border: [true, true, true, true]
                            }
                        ]);

                        total += item.BILLAM;
                    });

                    tableBody.push([
                        {text: 'Total', colSpan: 4, style: 'tableHeader', border: [true, true, true, true]},
                        '',
                        '',
                        '',
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
                            widths: ['8%', '18%', '16%', '24%', '18%', '16%'],
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
        });
    </script>

    {!! ($modalLink??'') !!}
@endsection
