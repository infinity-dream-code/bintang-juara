@extends('layouts.admin_new')
@section('title', $dataTitle ?? $mainTitle ?? $title ?? '')

@section('style')
    <link rel="stylesheet" href="{{ asset('main/libs/select2/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('main/libs/select2/select2-bootstrap.css') }}">
@endsection

@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">
        @if(isset($dataTitle) && isset($mainTitle) && $mainTitle != $dataTitle)
            {{ $mainTitle . ' - ' . $dataTitle }}
        @else
            {{ $mainTitle ?? $title ?? '' }}
        @endif
    </h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item">
            <a href="{{ route('admin.index') }}" class="text-hover-primary">Beranda</a>
        </li>
        @if(isset($title))
            <li class="breadcrumb-item">{{ $title }}</li>
        @endif
        @if(isset($mainTitle))
            <li class="breadcrumb-item">{{ $mainTitle }}</li>
        @endif
        @if(isset($dataTitle) && isset($mainTitle) && $mainTitle != $dataTitle)
            <li class="breadcrumb-item active">{{ $dataTitle }}</li>
        @endif
    </ul>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ $dataTitle ?? $mainTitle ?? $title }}</h5>
        </div>
        <div class="card-body">
            <form id="copy-tagihan-form" autocomplete="off">
                <div class="row mb-3">
                    <label for="thn_aka" class="col-sm-3 col-form-label form-label">Tahun Pelajaran (Tujuan)</label>
                    <div class="col-sm-9">
                        <select class="form-select" id="thn_aka" name="thn_aka" data-control="select2"
                                data-placeholder="Pilih Tahun Pelajaran">
                            <option value="">Pilih Tahun Pelajaran</option>
                            @isset($thn_aka)
                                @foreach($thn_aka as $item)
                                    <option value="{{ $item->thn_aka }}">{{ $item->thn_aka }}</option>
                                @endforeach
                            @endisset
                        </select>
                        <small class="text-muted">
                            Dipakai untuk menghitung BILLAC baru dan mengisi BTA tagihan hasil copy.
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="kelas" class="col-sm-3 col-form-label form-label">Kelas</label>
                    <div class="col-sm-9">
                        <select class="form-select" id="kelas" name="kelas" data-control="select2"
                                data-placeholder="Pilih Kelas">
                            <option value="">Pilih Kelas</option>
                            @isset($kelas)
                                @foreach($kelas as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->unit }} - {{ $item->jenjang }} {{ $item->kelas }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="tagihan_lama" class="col-sm-3 col-form-label form-label">Tagihan Lama</label>
                    <div class="col-sm-9">
                        <select class="form-select" id="tagihan_lama" name="tagihan_lama" data-control="select2"
                                data-placeholder="Pilih Tagihan Lama">
                            <option value="">Pilih Tagihan Lama</option>
                            @isset($tagihan)
                                @foreach($tagihan as $item)
                                    <option value="{{ $item->urut }}" data-name="{{ $item->tagihan }}">
                                        {{ $item->tagihan }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="tagihan_baru" class="col-sm-3 col-form-label form-label">Tagihan Baru</label>
                    <div class="col-sm-9">
                        <select class="form-select" id="tagihan_baru" name="tagihan_baru" data-control="select2"
                                data-placeholder="Pilih Tagihan Baru">
                            <option value="">Pilih Tagihan Baru</option>
                            @isset($tagihan)
                                @foreach($tagihan as $item)
                                    <option value="{{ $item->urut }}" data-name="{{ $item->tagihan }}">
                                        {{ $item->tagihan }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                        <small class="text-muted">
                            Periode (BILLAC) baru otomatis: deteksi dari nama Tagihan Baru (mis. "DRB JUNI" → bulan 06), fallback ke bulan saat ini bila tidak terdeteksi.
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="jenis" class="col-sm-3 col-form-label form-label">Jenis Tagihan</label>
                    <div class="col-sm-9">
                        <select class="form-select" id="jenis" name="jenis" data-control="select2">
                            <option value="belum" selected>Belum Dibayar</option>
                            <option value="sudah">Sudah Dibayar</option>
                            <option value="semua">Semua</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="nis" class="col-sm-3 col-form-label form-label">NIS (Opsional)</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="nis" name="nis"
                               placeholder="Kosongkan untuk seluruh siswa di kelas">
                        <small class="text-muted">
                            Kosongkan untuk menyalin tagihan seluruh siswa di kelas. Isi NIS / No. Pendaftaran untuk membatasi ke satu siswa.
                        </small>
                    </div>
                </div>

                <div id="preview-box" class="d-none">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered table-hover align-middle" id="preview-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Nama Tagihan</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Thn Akademik</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Total</th>
                                    <th class="text-end" id="preview-total-nominal">Rp 0</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button type="reset" class="btn btn-secondary">
                        <span class="ri-reset-left-line me-2"></span>Reset
                    </button>
                    <button type="button" class="btn btn-info" id="btn-preview">
                        <span class="ri-eye-line me-2"></span>Pratinjau
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="ri-file-copy-line me-2"></span>Salin Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('main/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('main/libs/select2/id.min.js') }}"></script>
    <script>
        const previewUrl = @json(route('admin.keuangan.tagihan-siswa.copy-tagihan.preview'));
        const copyUrl = @json(route('admin.keuangan.tagihan-siswa.copy-tagihan.execute'));
        const csrfToken = '{{ csrf_token() }}';

        function formatCurrency(n) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
            }).format(n || 0);
        }

        function formatPeriode(p) {
            if (!p || p.length !== 6) return p;
            return `${p.substring(0, 4)} / ${p.substring(4, 6)}`;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const select2 = $(`[data-control='select2']`);
            select2.each(function () {
                const $this = $(this);
                $this.wrap('<div class="position-relative"></div>').select2({
                    placeholder: $this.data('placeholder') || 'Select value',
                    dropdownParent: $this.parent(),
                    minimumResultsForSearch: 0,
                });
            });

            const form = document.getElementById('copy-tagihan-form');
            const previewBox = document.getElementById('preview-box');
            const previewTbody = document.querySelector('#preview-table tbody');
            const previewTotal = document.getElementById('preview-total-nominal');

            function gatherFormData() {
                const fd = new FormData(form);
                fd.append('_token', csrfToken);
                return fd;
            }

            function escapeHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                })[c]);
            }

            function showPreview(data) {
                const rows = Array.isArray(data.rows) ? data.rows : [];
                if (rows.length === 0) {
                    previewTbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-3">Tidak ada tagihan yang cocok dengan filter.</td></tr>`;
                } else {
                    previewTbody.innerHTML = rows.map((r, i) => {
                        const statusBadge = r.paidst == 1
                            ? '<span class="badge bg-success">Lunas</span>'
                            : '<span class="badge bg-warning">Belum</span>';
                        return `
                            <tr>
                                <td class="text-center">${i + 1}</td>
                                <td>${escapeHtml(r.nis ?? '')}</td>
                                <td>${escapeHtml(r.nama ?? '')}</td>
                                <td>${escapeHtml(r.kelas ?? '')}</td>
                                <td>${escapeHtml(r.nama_tagihan ?? '')}</td>
                                <td class="text-end">${formatCurrency(r.billam)}</td>
                                <td>${escapeHtml(r.bta ?? '')}</td>
                                <td class="text-center">${statusBadge}</td>
                            </tr>
                        `;
                    }).join('');
                }
                previewTotal.textContent = formatCurrency(data.total_nominal);
                previewBox.classList.remove('d-none');
            }

            document.getElementById('btn-preview').addEventListener('click', function () {
                previewBox.classList.add('d-none');
                fetch(previewUrl, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
                    body: gatherFormData(),
                })
                    .then(async (res) => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Gagal pratinjau');
                        return data;
                    })
                    .then((data) => {
                        showPreview(data);
                    })
                    .catch((e) => {
                        if (typeof errorAlert === 'function') errorAlert(e.message);
                        else alert(e.message);
                    });
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const confirmFn = window.Swal
                    ? () => window.Swal.fire({
                        title: 'Salin Tagihan?',
                        text: 'Pastikan filter sudah benar. Tagihan baru akan dibuat untuk siswa yang cocok.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, salin',
                        cancelButtonText: 'Batal',
                    }).then(r => r.isConfirmed)
                    : () => Promise.resolve(confirm('Salin tagihan untuk siswa yang cocok?'));

                confirmFn().then((ok) => {
                    if (!ok) return;
                    if (typeof loadingAlert === 'function') {
                        loadingAlert('Menyalin tagihan, mohon tunggu...');
                    }

                    fetch(copyUrl, {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
                        body: gatherFormData(),
                    })
                        .then(async (res) => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok) throw new Error(data.message || 'Gagal menyalin tagihan');
                            return data;
                        })
                        .then((data) => {
                            if (typeof successAlert === 'function') successAlert(data.message);
                            else alert(data.message);
                        })
                        .catch((e) => {
                            if (typeof errorAlert === 'function') errorAlert(e.message);
                            else alert(e.message);
                        });
                });
            });

            form.addEventListener('reset', function () {
                previewBox.classList.add('d-none');
                setTimeout(() => {
                    $(`[data-control='select2']`).each(function () {
                        $(this).val('').trigger('change');
                    });
                }, 0);
            });
        });
    </script>
@endsection
