@extends('layouts.admin_new')
@section('style')
    <link rel="stylesheet" href="{{asset('main/libs/datatables-bs5/datatables.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/select2/select2.min.css')}}">
    <style>
        .cicil-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            justify-content: center;
            min-width: 210px;
        }

        .cicil-switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
            flex-shrink: 0;
        }

        .cicil-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .cicil-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #cfd3dc;
            border-radius: 999px;
            transition: background-color .2s ease;
        }

        .cicil-slider::before {
            content: "";
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
            transition: transform .2s ease;
        }

        .cicil-switch input:checked + .cicil-slider {
            background: #696cff;
        }

        .cicil-switch input:checked + .cicil-slider::before {
            transform: translateX(22px);
        }

        .cicil-switch input:disabled + .cicil-slider {
            opacity: .6;
            cursor: wait;
        }

        .cicil-label {
            font-size: .8125rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }

        .cicil-label.is-on {
            color: #696cff;
        }

        .cicil-label.is-off {
            color: #8592a3;
        }
    </style>
@endsection
@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">
        {{($dataTitle??($mainTitle??($title??'')))}}
    </h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item">
            <a href="{{ route('admin.index') }}" class="text-hover-primary">Beranda</a>
        </li>
        @isset($title)
            <li class="breadcrumb-item">{{ $title }}</li>
        @endisset
        @isset($mainTitle)
            <li class="breadcrumb-item active">{{ $mainTitle }}</li>
        @endisset
    </ul>

    <div class="card">
        <div class="card-header header-elements">
            <h5 class="mb-0 me-2">{{($dataTitle??$mainTitle)}}</h5>
            <div class="card-header-elements ms-auto">
                <div class="w-100">
                    <div class="row">
                        <div class="d-flex justify-content-center justify-content-md-end gap-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="dataReload('main_table')" title="Refresh">
                                <span class="ri-refresh-line me-2"></span>
                                Refresh
                            </button>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modal-create" title="Tambah Tagihan">
                                <span class="ri-add-line me-2"></span>
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive text-nowrap">
            <table class="table table-sm table-bordered table-hover" id="main_table">
                <thead class="table-light"></thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card-footer">
            <small class="text-muted">
                Geser toggle untuk mengaktifkan / menonaktifkan cicilan per nama tagihan.
            </small>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{asset('main/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{asset('js/datatableCustom/Datatable-0-4.min.js')}}"></script>
    <script src="{{asset('js/helper/errorInputHelper.min.js')}}"></script>
    <script src="{{asset('main/libs/select2/select2.min.js')}}"></script>

    <script type="text/javascript">
        const dtOptions = {
            tableId: 'main_table',
            formId: false,
            columnUrl: '{{($columnsUrl??null)}}',
            dataUrl: '{{($datasUrl??null)}}',
            dataColumns: [],
            thead: true,
            tfoot: false,
            paging: true,
            searching: true,
            fixedHeader: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 75, 100],
        };

        const toggleInstallmentUrl = '{{ route('admin.master-data.master-tagihan.toggle-installment', ['id' => '__ID__']) }}';

        document.addEventListener("DOMContentLoaded", function () {
            if (dtOptions.dataUrl && dtOptions.columnUrl) {
                getDT(dtOptions);
            }

            document.querySelectorAll("[data-control='select2']").forEach(select => {
                let wrapper = document.createElement("div");
                wrapper.classList.add("position-relative");
                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(select);
                $(select).select2({
                    placeholder: "Pilih satu",
                    language: "id",
                    dropdownParent: $(wrapper)
                });
            });

            $(document).on('change', '#main_table .toggle-installment', function () {
                const $toggle = $(this);
                const id = $toggle.data('id');
                const nextValue = $toggle.is(':checked') ? 1 : 0;
                const previousChecked = !$toggle.is(':checked');
                const $wrap = $toggle.closest('.cicil-toggle');
                const $label = $wrap.find('.cicil-label');
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const url = toggleInstallmentUrl.replace('__ID__', id);

                $toggle.prop('disabled', true);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        isINSTALLMENT: nextValue,
                        _token: csrfToken,
                    }),
                })
                    .then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok) {
                                throw payload;
                            }
                            return payload;
                        });
                    })
                    .then(function (data) {
                        const active = Number(data.isINSTALLMENT) === 1;
                        $toggle.prop('checked', active);
                        if ($label.length) {
                            $label
                                .text(active ? 'Bisa di cicil' : 'Tidak bisa di cicil')
                                .toggleClass('is-on', active)
                                .toggleClass('is-off', !active);
                        }
                        $toggle.attr('title', active ? 'Bisa di cicil' : 'Tidak bisa di cicil');
                        if (typeof successAlert === 'function') {
                            successAlert(data.message || 'Status cicil diperbarui');
                        }
                    })
                    .catch(function (error) {
                        $toggle.prop('checked', previousChecked);
                        if (typeof errorAlert === 'function') {
                            errorAlert((error && error.message) ? error.message : 'Gagal mengubah status cicil');
                        }
                    })
                    .finally(function () {
                        $toggle.prop('disabled', false);
                    });
            });
        });
    </script>

    <form id="addForm" class="mainForm">
        <div class="modal modal-blur fade" id="modal-create" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Master Tagihan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <fieldset class="form-fieldset">
                            <div class="mb-3">
                                <label class="form-label required" for="tagihan">Nama Tagihan</label>
                                <input type="text" class="form-control" name="tagihan" id="tagihan" autocomplete="off"
                                       placeholder="Contoh: SPP" required>
                                <div class="invalid-feedback" role="alert"><strong></strong></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required" for="isINSTALLMENT">Status Dapat Di Cicil</label>
                                <select class="form-select" name="isINSTALLMENT" id="isINSTALLMENT" data-control="select2" required>
                                    <option value="0">TIDAK BISA DI CICIL</option>
                                    <option value="1">BISA DI CICIL</option>
                                </select>
                                <div class="invalid-feedback" role="alert"><strong></strong></div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <input type="reset" value="Batal" class="btn btn-outline-secondary w-100"
                                           data-bs-dismiss="modal">
                                </div>
                                <div class="col">
                                    <input type="submit" value="Simpan Data" class="btn btn-primary w-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll(".mainForm").forEach(form => {
                form.addEventListener("submit", function (e) {
                    e.preventDefault();
                    loadingAlert();

                    const formData = new FormData(this);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    formData.append("_token", csrfToken);

                    clearErrorMessages(form.id);
                    fetch("{{ route('admin.master-data.master-tagihan.store') }}", {
                        method: "POST",
                        headers: {'X-CSRF-TOKEN': csrfToken},
                        body: formData
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw {status: response.status, error: err};
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            document.getElementById(form.id).reset();
                            successAlert(data.message);
                            dataReload("main_table");
                            document.querySelector(`#${form.id} [data-bs-dismiss="modal"]`)?.click();
                        })
                        .catch(error => {
                            if (error.status === 422) {
                                const errors = error.error.error || error.error.errors;
                                errorAlert(error.error.message);
                                if (errors) processErrors(errors);
                            } else {
                                errorAlert("Terjadi kesalahan, silahkan coba memuat ulang halaman");
                            }
                        });
                });
            });
        });
    </script>
@endsection
