@extends('layouts.admin_new')
@section('title',$dataTitle??$mainTitle??$title??'')
@section('style')
    <link rel="stylesheet" href="{{asset('main/libs/datatables-bs5/datatables.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('main/libs/select2/select2.min.css')}}">
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
        <div class="card-header header-elements">
            <div class="card-title">
                <h5 class="mb-0 me-2">{{($dataTitle??$mainTitle)}}</h5>
            </div>
        </div>

        <div class="card-body">
            <fieldset class="form-fieldset">
                <div class="col-12 mb-3">
                    <label class="form-label" for="nis">Nis/No Daftar Siswa</label>
                    <div class="input-group input-group-merge">
                        <input type="text" placeholder="Masukkan Nis/No Daftar siswa" name="nis"
                               id="nis"
                               autocomplete="off"
                               class="form-control @error('password')is-invalid @enderror" required/>
                        <span class="input-group-text cursor-pointer bg-primary text-white cari-siswa"
                              data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click"
                              data-bs-placement="bottom"
                              title="Cari Siswa">
                                <i class="ri ri-search-line me-2"></i>
                                Cari
                            </span>
                    </div>
                </div>
            </fieldset>
        </div>
        <div class="card-datatable table-responsive text-nowrap px-5 card-siswa">
            <table class="table table-sm table-bordered table-hover"
                   id="table-siswa">
                <thead class="table-light">
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <div class="card-body px-0">
            <div class="row">
                <div class="col-12 col-md-7">
                    <div class="card-datatable table-responsive text-nowrap px-5">
                        <div class="card-header">
                            TAGIHAN YANG TAMPIL DI BANK
                        </div>
                        <div class="col-12">
                            <table class="table table-sm table-bordered table-hover"
                                   id="table-tagihan">
                                <thead class="table-light">
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-datatable table-responsive text-nowrap px-5">
                        <div class="card-header">
                            TAGIHAN YANG SUDAH DIBAYAR
                        </div>
                        <table class="table table-sm table-bordered table-hover"
                               id="table-tagihan-dibayar">
                            <thead class="table-light">
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12 col-md-5">
                    <div class="card-datatable table-responsive text-nowrap px-5">
                        <div class="card-header">
                            POST
                        </div>
                        <table class="table table-sm table-bordered table-hover">
                            <tbody>
                            <tr>
                                <th class="table-light">Akun</th>
                                <td>
                                    <select class="form-select" name="pilih-akun" id="pilih-akun">
                                        <option disabled selected>Pilih Akun</option>
                                        @isset($v_dt_daftar_harga)
                                            @foreach($v_dt_daftar_harga as $item)
                                                <option data-val="{{json_encode($item)}}"
                                                        value="{{$item->KodeAkun}}">{{$item->NamaAkun}}</option>
                                            @endforeach
                                        @endisset
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th class="table-light">Nominal</th>
                                <td>
                                    <input type="text"
                                           class="form-control bg-body rounded-end formattedNumber"
                                           id="nominal-pilih-akun" autocomplete="off"
                                           placeholder="" value="" readonly>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-center">
                                    <button type="button" class="btn btn-primary" id="btn-buat-detail">Buat Detail
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-datatable table-responsive text-nowrap px-5">
                        <div class="card-header">
                            DETAIL POST
                            <ul class="list-group list-group-timeline">
                                <li class="list-group-item list-group-timeline-info">
                                    <strong>Klik 2x pada Detail Post Untuk menghapus</strong>
                                </li>
                            </ul>
                        </div>
                        <table class="table table-sm table-bordered table-hover" id="table-post-baru">
                            <thead class="table-light">
                            <tr>
                                <th>Nama Post</th>
                                <th>Nominal</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="w-100">
                <div class="row">
                    <div class="d-flex flex-column flex-md-row gap-4">
                        <button type="button" class="btn btn-secondary me-md-auto w-md-auto" id="btn-reset">
                            <span class="ri-reset-left-line me-2"></span>
                            Reset
                        </button>
                        <button class="btn btn-warning w-md-auto" type="button" id="btn-edit-tagihan">
                            <span class="ri-save-line me-2"></span>
                            Edit Tagihan
                        </button>
                        <button class="btn btn-primary w-md-auto" type="button" id="btn-copy-tagihan">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round"
                                 class="icon icon-tabler icons-tabler-outline icon-tabler-copy me-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path
                                    d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666"/>
                                <path
                                    d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/>
                            </svg>
                            Copy Tagihan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{asset('main/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{asset('js/datatableCustom/Datatable-0-4.min.js')}}"></script>
    <script src="{{asset('main/libs/select2/select2.min.js')}}"></script>
    <script src="{{asset('js/helper/formattedNumber.min.js')}}"></script>

    <script type="text/javascript" defer>
        let tableSiswa;
        let tableTagihan;
        let tableTagihanDibayar;
        let tablePostBaru;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function inputSiswa() {
            const inputValue = document.getElementById('nis').value;
            if (!inputValue) {
                warningAlert('NIS/No Daftar siswa tidak boleh kosong');
            } else {
                getSiswa(inputValue);
            }
        }

        document.getElementById('nis').addEventListener('keydown', function (e) {
            if (e.key === "Enter") {
                inputSiswa();
            }
        });

        document.getElementById('btn-buat-detail').addEventListener('click', function (e) {
            const akun = document.getElementById('pilih-akun');
            const selectedOption = akun.options[akun.selectedIndex];
            const dataVal = selectedOption.getAttribute('data-val');
            // const akunValue = akun.value;
            // const nominalInput = document.getElementById('nominal-pilih-akun');
            // const nominalValue = nominalInput.value;
            const dataObj = JSON.parse(dataVal);
            const exists = tablePostBaru
                .rows()
                .data()
                .toArray()
                .some(row => row.KodeAkun === dataObj.KodeAkun);

            if (!exists) {
                tablePostBaru.row.add({
                    KodeAkun: dataObj.KodeAkun,
                    nominal: dataObj.nominal ?? 0,
                    NamaAkun: dataObj.NamaAkun
                }).draw();
            } else {
                warningAlert(`<b>${dataObj.NamaAkun}</b> sudah ada!`)
            }
        })

        document.querySelector('.cari-siswa').addEventListener('click', function () {
            inputSiswa();
        });

        document.getElementById('table-siswa').addEventListener('click', function (e) {
            if (!e.target.classList.contains('checkbox-siswa')) {
                const row = e.target.closest('tr');
                if (row) {
                    const checkbox = row.querySelector('.checkbox-siswa');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        tableTagihan.clear().draw();
                        tableTagihanDibayar.clear().draw();
                        tablePostBaru.clear().draw();
                        checkbox.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                }
            }
        });

        // Existing checkbox change listener
        document.getElementById('table-siswa').addEventListener('change', function (e) {
            if (e.target.classList.contains('checkbox-siswa')) {
                const checkbox = e.target;
                const isChecked = checkbox.checked;
                if (isChecked) {
                    const value = checkbox.value;
                    getTagihan(value);
                }
            }
        });

        // document.getElementById('table-tagihan').addEventListener('click', function (e) {
        //     if (!e.target.classList.contains('checkbox')) {
        //         const row = e.target.closest('tr');
        //         if (row) {
        //             const checkbox = row.querySelector('.checkbox');
        //             if (checkbox) {
        //                 checkbox.checked = !checkbox.checked;
        //                 checkbox.dispatchEvent(new Event('change', {bubbles: true}));
        //             }
        //         }
        //     }
        // });

        document.getElementById('btn-reset').addEventListener('click', function (e) {
            tablePostBaru.clear().draw();
            tableTagihan.clear().draw();
            tableTagihanDibayar.clear().draw();
            tableSiswa.clear().draw();
            const nisInput = document.getElementById('nis');
            if (nisInput) {
                nisInput.value = '';
                nisInput.focus();
                nisInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                window.scrollBy(0, -80)
            }
        })

        document.getElementById('table-post-baru').addEventListener('input', function (e) {
            if (e.target.classList.contains('nominal-input')) {
                const input = e.target;
                const rowEl = input.closest('tr');
                const row = tablePostBaru.row(rowEl);

                const raw = input.value.replace(/\./g, '');

                input.dataset.raw = raw;
            }
        });

        document.getElementById('table-post-baru').addEventListener('blur', function (e) {
            if (!e.target.classList.contains('nominal-input')) return;
            const input = e.target;
            const rowEl = input.closest('tr');
            const row = tablePostBaru.row(rowEl);

            const raw = input.value.replace(/\./g, '');
            const number = parseInt(raw || 0, 10);
            input.value = number.toLocaleString('id-ID');
            const data = row.data();
            data.nominal = number;
            row.data(data);
        }, true);
        // document.querySelector('#table-tagihan tbody').addEventListener('dblclick', function (e) {
        //     const rowEl = e.target.closest('tr');
        //
        //     if (rowEl) {
        //         const rowData = tableTagihan.row(rowEl).data();
        //         if (rowData) {
        //             tablePostBaru.row.add(rowData);
        //             tablePostBaru.draw();
        //         }
        //     }
        // });
        //
        // document.querySelector('#table-tagihan-dibayar tbody').addEventListener('dblclick', function (e) {
        //     const rowEl = e.target.closest('tr');
        //
        //     if (rowEl) {
        //         const rowData = tableTagihanDibayar.row(rowEl).data();
        //         if (rowData) {
        //             tablePostBaru.row.add(rowData);
        //             tablePostBaru.draw();
        //         }
        //     }
        // });

        document.querySelector('#table-post-baru tbody').addEventListener('dblclick', function (e) {
            if (e.target.tagName.toLowerCase() === 'input' || e.target.closest('input')) {
                return;
            }

            const rowEl = e.target.closest('tr');
            if (rowEl) {
                tablePostBaru.row(rowEl).remove();
                tablePostBaru.draw();
            }
        });

        document.querySelector('#pilih-akun').addEventListener('change', function (e) {
            const selectedOption = this.options[this.selectedIndex];
            const value = selectedOption.value;
            const dataVal = selectedOption.getAttribute("data-val");

            const obj = JSON.parse(dataVal);
            let val = parseInt(obj.nominal ?? 0);
            val = val.toLocaleString('id-ID');

            document.getElementById('nominal-pilih-akun').value = val;
        });

        document.getElementById('btn-copy-tagihan').addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedSiswa = tableSiswa.rows({selected: true}).data();
            if (!selectedSiswa[0]?.CUSTID) {
                warningAlert('Silahkan pilih 1 siswa!')
                return;
            }
            const selectedTagihanDibayar = tableTagihanDibayar.rows({selected: true}).data();
            const selectedTagihan = tableTagihan.rows({selected: true}).data();

            if (!selectedTagihanDibayar[0] && !selectedTagihan[0]) {
                warningAlert('Silahkan pilih tagihan yang akan disalin!')
                return;
            } else if (selectedTagihan[0] && selectedTagihanDibayar[0]) {
                warningAlert('Silahkan pilih satu tagihan antara tagihan yang belum dan sudah dibayarkan untuk disalin')
                return;
            }


            const data = tablePostBaru.data().toArray();
            if (data.length === 0) {
                warningAlert('Silahkan tambahkan paling tidak satu detail post!');
                return;
            }
            const formData = new FormData();
            formData.append(`siswa`, selectedSiswa[0].CUSTID);
            const tagihan =
                selectedTagihan?.[0]?.AA ??
                selectedTagihanDibayar?.[0]?.AA ??
                null;

            if (tagihan !== null) {
                formData.append('tagihan', tagihan);
            }
            appendDetailPostToFormData(formData);

            loadingAlert('Mengedit data...');
            const request = new Request(
                `{{route('admin.manual-input.edit-manual.copy-tagihan')}}`,
                {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData
                });

            let result = await submitForm(request);
            if (result) {
                await getTagihan(selectedSiswa[0].CUSTID, false);
                tablePostBaru.clear().draw();
                successAlert(result.message ?? "Tagihan telah disalin");
            }
        });

        document.getElementById('btn-edit-tagihan').addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedSiswa = tableSiswa.rows({selected: true}).data();
            if (!selectedSiswa[0]?.CUSTID) {
                warningAlert('Silahkan pilih 1 siswa')
                return;
            }

            const selectedTagihanDibayar = tableTagihanDibayar.rows({selected: true}).data();
            if (selectedTagihanDibayar[0]) {
                warningAlert('Tagihan yang sudah dibayarkan tidak bisa diedit!')
                return;
            }

            const selectedTagihan = tableTagihan.rows({selected: true}).data();
            if (!selectedTagihan[0]) {
                warningAlert('Silahkan pilih tagihan yang akan diedit!')
                return;
            }

            const data = tablePostBaru.data().toArray();
            if (data.length === 0) {
                warningAlert('Silahkan tambahkan paling tidak satu detail post!');
                return;
            }

            const formData = new FormData();
            formData.append(`siswa`, selectedSiswa[0]?.CUSTID);
            const tagihan = selectedTagihan?.[0]?.AA ?? null;

            if (tagihan !== null) {
                formData.append('tagihan', tagihan);
            }
            appendDetailPostToFormData(formData);

            formData.append('_method', 'PUT');
            loadingAlert('Mengedit data...');
            const request = new Request(
                `{{route('admin.manual-input.edit-manual.edit-tagihan')}}`,
                {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData
                });

            let result = await submitForm(request);
            if (result) {
                await getTagihan(selectedSiswa[0].CUSTID, false);
                tablePostBaru.clear().draw();
                successAlert(result.message);
            }
        });

        async function getSiswa(siswa) {
            let url = '{{route('admin.keuangan.tagihan-siswa.buat-tagihan.get-siswa')}}';
            let ajaxOptions = {
                url: url,
                type: 'get',
                datatype: 'json',
                data: {
                    'cari_siswa': siswa,
                    'siswa_only': true
                },
            }

            $.ajax(ajaxOptions).done(function (response) {
                refreshDataTable(response.data);
            }).fail(function (xhr) {
                if (xhr.status === 422) {
                    errorAlert('Gagal mendapat data siswa')
                } else if (xhr.status === 419) {
                    errorAlert('Sesi anda telah habis, Silahkan Login Kembali')
                } else if (xhr.status === 500) {
                    errorAlert('Tidak dapat terhubung ke server, Silahkan periksa koneksi internet anda')
                } else if (xhr.status === 403) {
                    errorAlert('Anda tidak memiliki izin untuk mengakses halaman ini')
                } else if (xhr.status === 404) {
                    errorAlert('Halaman tidak ditemukan')
                } else {
                    errorAlert('Terjadi kesalahan, silahkan coba memuat ulang halaman')
                }
            })
        }

        async function getTagihan(siswa, closeAlert = true) {
            loadingAlert('Memuat data...');
            const request = new Request(
                `{{route('admin.manual-input.edit-manual.get-tagihan')}}?siswa=${encodeURIComponent(siswa)}`,
                {
                    method: "get",
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
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
                    refreshTableTagihan(data);
                    if (closeAlert) {
                        Swal.close();
                    }
                })
                .catch(error => {
                    if (error.status === 422) {
                        const errors = error.error.error || error.error.errors;
                        errorAlert(error.error.message);
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
                        console.log(error)
                    }
                });
        }

        function appendDetailPostToFormData(formData) {
            tablePostBaru.rows().every(function (index) {
                const rowData = this.data();
                const node = this.node();
                if (!node) return;
                const input = node.querySelector('.nominal-input');
                const rawNominal = input
                    ? input.value.replace(/\./g, '')
                    : String(rowData.nominal ?? '').replace(/\./g, '');
                const nominal = parseInt(rawNominal || '0', 10);
                formData.append(`data[${index}][KodeAkun]`, rowData.KodeAkun);
                formData.append(`data[${index}][NamaAkun]`, rowData.NamaAkun);
                formData.append(`data[${index}][nominal]`, nominal);
            });
        }

        async function getDetailTagihan(siswa, tagihan) {
            loadingAlert('Memuat data...');
            const request = new Request(
                `{{route('admin.manual-input.edit-manual.get-detail-tagihan')}}?siswa=${encodeURIComponent(siswa)}&tagihan=${encodeURIComponent(tagihan)}`,
                {
                    method: "get",
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

            let data = await submitForm(request);
            if (data) {
                tablePostBaru.rows.add(data);
                tablePostBaru.draw();
                Swal.close();
            }
        }

        function refreshDataTable(newData = []) {
            tableSiswa.rows().deselect();
            tableSiswa.clear();
            tableSiswa.rows.add(newData);
            tableSiswa.draw();
        }

        function refreshTableTagihan(newData = []) {
            const splitByPaidStatus = newData.reduce((acc, item) => {
                if (item.PAIDST === 0 || item.PAIDST === "0") {
                    acc.unpaid.push(item);
                } else if (item.PAIDST === 1 || item.PAIDST === "1") {
                    acc.paid.push(item);
                }
                return acc;
            }, {paid: [], unpaid: []});

            tableTagihan.rows().deselect();
            tableTagihan.clear();
            tableTagihan.rows.add(splitByPaidStatus.unpaid);
            tableTagihan.draw();

            tableTagihanDibayar.rows().deselect();
            tableTagihanDibayar.clear();
            tableTagihanDibayar.rows.add(splitByPaidStatus.paid);
            tableTagihanDibayar.draw();
        }

        async function submitForm(request, options = {}) {
            const controller = new AbortController();
            const timeout = options.timeout || 30000;

            const timeoutId = setTimeout(() => controller.abort(), timeout);

            try {
                const response = await fetch(request, {
                    ...options,
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                const contentType = response.headers.get('content-type');
                let data = null;

                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    data = await response.text();
                }

                if (!response.ok) {
                    const error = new Error(data?.message || `Gagal memproses permintaan! (${response.status})`);
                    error.status = response.status;
                    error.data = data;
                    throw error;
                }

                return data;
            } catch (error) {
                clearTimeout(timeoutId);

                if (error.name === 'AbortError') {
                    errorAlert('Permintaan terlalu lama ⏳, silakan coba lagi.');
                    return false;
                }

                if (error.status === 422) {
                    errorAlert(error.message);

                    const errors = error.data?.errors || error.data;
                    if (errors) {
                        processErrors(errors);
                    }

                    return false;
                }

                const errorMessages = {
                    401: 'Sesi anda sudah habis 🙏 <br>Silahkan muat ulang halaman atau login kembali!',
                    403: 'Anda tidak memiliki izin untuk mengakses 😖',
                    404: 'Halaman tidak ditemukan 🧐',
                    405: 'Metode tidak valid 🧐 <br>Silakan coba lagi!',
                    419: 'Sesi anda sudah habis 🙏 <br>Silahkan login kembali!',
                    429: 'Terlalu banyak permintaan 🙏 <br>Tunggu beberapa saat!',
                };

                errorAlert(
                    errorMessages[error.status] ||
                    error.message ||
                    'Terjadi kesalahan, silakan muat ulang halaman'
                );

                return false;
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const languageKey = 'datatables_id_language';
            const languageUrl = '/js/datatableCustom/id.json';

            async function fetchLanguageFile() {
                try {
                    const response = await fetch(languageUrl);
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();
                    localStorage.setItem(languageKey, JSON.stringify(data)); // Save to localStorage
                    return data;
                } catch (error) {
                    console.error('Error fetching language file:', error);
                    return null;
                }
            }

            let languageData = localStorage.getItem(languageKey);

            async function getDTLang() {
                if (!languageData) {
                    languageData = await fetchLanguageFile();
                } else {
                    languageData = JSON.parse(languageData);
                }
            }

            getDTLang();

            tableSiswa = $('#table-siswa').DataTable({
                columns: [
                    {data: 'CUSTID'},
                    {data: 'nis', title: 'NIS'},
                    {data: 'nama', title: 'NAMA'},
                    {data: 'kelas', title: 'Kelas'},
                    {data: 'jenjang', title: 'Jenjang'},
                    {data: 'angkatan', title: 'Angkatan'},
                ],
                columnDefs: [
                    {
                        targets: 0,
                        searchable: false,
                        orderable: false,
                        render: function (data) {
                            return `<input type="checkbox" id="siswa-checkbox-${data}" class="dt-checkboxes form-check-input checkbox-siswa" value="${data}">`;
                        },
                        checkboxes: {
                            selectRow: true,
                            selectAll: false,
                        },
                        className: 'text-center',
                    },
                ],
                language: {
                    ...languageData,
                    emptyTable: "Tidak ada siswa yang sesuai kriteria pencarian"
                },

                paging: true,
                serverSide: false,
                searching: false,
                lengthChange: false,
                pageLength: 10,
                order: [[1, 'desc']],
                select: 'single',
                scrollX: true,
            });

            tableTagihan = $('#table-tagihan').DataTable({
                columns: [
                    {data: 'AA'},
                    {data: 'BILLNM', title: 'NAMA TAGIHAN'},
                    {
                        data: 'BILLAM',
                        title: 'JUMLAH',
                        className: 'text-end',
                        render: function (data, type, row) {
                            const value = Number(data);

                            if (!Number.isFinite(value)) {
                                return 'Rp. 0';
                            }

                            const formatted = $.fn.dataTable
                                .render
                                .number('.', ',', 0, 'Rp. ')
                                .display(Math.abs(value));

                            return value < 0 ? `Rp. -${formatted.replace('Rp. ', '')}` : formatted;
                        }
                    },
                    {data: 'BTA', title: 'TAHUN PELAJARAN'},
                    {data: 'FUrutan', title: 'Urutan'},
                ],
                columnDefs: [
                    {
                        targets: 0,
                        searchable: false,
                        orderable: false,
                        render: function (data) {
                            return `<input type="checkbox" id="tagihan-checkbox-${data}" class="dt-checkboxes form-check-input checkbox" name="checkbox_tagihan" value="${data}">`;
                        },
                        checkboxes: {
                            selectRow: true,
                            selectAll: false,
                        },
                        className: 'text-center',
                    }
                ],
                language: {
                    ...languageData,
                    emptyTable: "Tidak ada siswa yang sesuai kriteria pencarian"
                },

                paging: true,
                select: {style: 'single'},
                serverSide: false,
                searching: false,
                lengthChange: false,
                pageLength: 10,
                order: [[4, 'asc']],
                scrollX: true,
            });

            tableTagihanDibayar = $('#table-tagihan-dibayar').DataTable({
                columns: [
                    {data: 'AA'},
                    {data: 'BILLNM', title: 'NAMA TAGIHAN'},
                    {
                        data: 'BILLAM',
                        title: 'JUMLAH',
                        className: 'text-end',
                        render: function (data, type, row) {
                            const value = Number(data);

                            if (!Number.isFinite(value)) {
                                return 'Rp. 0';
                            }

                            const formatted = $.fn.dataTable
                                .render
                                .number('.', ',', 0, 'Rp. ')
                                .display(Math.abs(value));

                            return value < 0 ? `Rp. -${formatted.replace('Rp. ', '')}` : formatted;
                        }
                    },
                    {data: 'BTA', title: 'TAHUN PELAJARAN'},
                    {data: 'FUrutan', title: 'Urutan'},
                ],
                columnDefs: [
                    {
                        targets: 0,
                        searchable: false,
                        orderable: false,
                        render: function (data) {
                            return `<input type="checkbox" id="penerimaan-checkbox-${data}" class="dt-checkboxes form-check-input checkbox" name="checkbox_tagihan_dibayar" value="${data}">`;
                        },
                        checkboxes: {
                            selectRow: true,
                            selectAll: false,
                        },
                        className: 'text-center',
                    }
                ],
                language: {
                    ...languageData,
                    emptyTable: "Tidak ada siswa yang sesuai kriteria pencarian"
                },

                paging: true,
                select: {style: 'single'},
                serverSide: false,
                searching: false,
                lengthChange: false,
                pageLength: 10,
                order: [[4, 'asc']],
                scrollX: true,
            });

            tablePostBaru = $('#table-post-baru').DataTable({
                columns: [
                    {data: 'NamaAkun', title: 'NAMA POST'},
                    {
                        data: 'nominal',
                        title: 'NOMINAL',
                        searchable: false,
                        orderable: false,
                        render: function (data, type, row) {
                            let val = parseInt(data ?? 0);
                            val = val.toLocaleString('id-ID');
                            return `
                            <input type="text" class="form-control bg-body rounded-end nominal-input text-end formattedNumber"
                                    id="tagihan[${row.AA}][nominal]" name="nominal_post_baru" autocomplete="off" placeholder="Nominal Tagihan" value="${val}">
                                <div class="invalid-feedback" role="alert"></div>
                            `;
                        }
                    },
                ],
                language: {
                    ...languageData,
                    emptyTable: "silahkan pilih tagihan atau tambahkan detail post baru"
                },

                paging: false,
                serverSide: false,
                searching: false,
                lengthChange: false,
                pageLength: 10,
                order: [[1, 'desc']],
                scrollX: true,
            });

            tableTagihan.on('select.dt deselect.dt', async function (e, dt, type, indexes) {
                if (type === 'row') {
                    if (e.type === 'select') {
                        const rowData = tableTagihan.rows(indexes).data();
                        tableTagihanDibayar.rows().every(function () {
                            const node = this.node();
                            if (!node) return;

                            const checkbox = node.querySelector('.checkbox');
                            if (checkbox && checkbox.checked) {
                                tableTagihanDibayar.rows().deselect();
                            }
                        });

                        tablePostBaru.clear().draw();

                        const selectedData = tableSiswa.rows({selected: true}).data();
                        if (!selectedData[0].CUSTID) {
                            warningAlert('Terjadi Kesalahan pada sistem, silahkan hubungi administrator!')
                            return;
                        }
                        await getDetailTagihan(selectedData[0].CUSTID, rowData[0].BILLCD);
                        Swal.close();
                    } else {
                        tablePostBaru.clear().draw();
                    }
                }
            });

            tableTagihanDibayar.on('select.dt deselect.dt', async function (e, dt, type, indexes) {
                if (type === 'row') {
                    if (e.type === 'select') {
                        const rowData = tableTagihanDibayar.rows(indexes).data();
                        tableTagihan.rows().every(function () {
                            const node = this.node();
                            if (!node) return;

                            const checkbox = node.querySelector('.checkbox');
                            if (checkbox && checkbox.checked) {
                                tableTagihan.rows().deselect();
                            }
                        });

                        tablePostBaru.clear().draw();

                        const selectedData = tableSiswa.rows({selected: true}).data();
                        if (!selectedData[0].CUSTID) {
                            warningAlert('Terjadi Kesalahan pada sistem, silahkan hubungi administrator!')
                            return;
                        }
                        await getDetailTagihan(selectedData[0].CUSTID, rowData[0].BILLCD);
                        Swal.close();
                    } else {
                        tablePostBaru.clear().draw();
                    }
                }
            });
        });

    </script>
@endsection

