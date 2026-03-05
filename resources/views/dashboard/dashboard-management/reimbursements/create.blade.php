@extends('layouts.master')

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h1 class="fs-2x fw-bold text-gray-900 mb-2">Buat Reimbursement Medical</h1>
            <div class="text-gray-600 fs-6">
                <span class="me-4"><i class="ki-outline ki-user fs-5 me-1"></i>{{ $karyawan->nama_lengkap }}</span>
                <span class="me-4"><i class="ki-outline ki-badge fs-5 me-1"></i>{{ $karyawan->absen_karyawan_id }}</span>
                <span><i class="ki-outline ki-calendar fs-5 me-1"></i>{{ \Carbon\Carbon::parse($periodeSlip . '-01')->format('F Y') }}</span>
            </div>
        </div>
        <a href="{{ route('manage-reimbursements.index') }}" class="btn btn-light btn-sm">
            <i class="ki-outline ki-arrow-left fs-3"></i>
            Kembali
        </a>
    </div>

    <form id="reimbursementForm">
        @csrf
        <input type="hidden" name="karyawan_id" value="{{ $karyawan->absen_karyawan_id }}">
        <input type="hidden" name="company_id" value="{{ $company->absen_company_id }}">
        <input type="hidden" name="periode_slip" value="{{ $periodeSlip }}">

        <!-- Budget Selection Card -->
        <div class="card mb-7">
            <div class="card-body p-9">
                <div class="row g-6">
                    <div class="col-lg-4">
                        <label class="form-label fs-6 fw-semibold text-gray-800 mb-3">
                            Tahun Budget <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-solid" id="year_budget" name="year_budget"
                                data-control="select2" data-placeholder="Pilih tahun budget..." required>
                            <option></option>
                            @foreach($availableYears as $yearData)
                                <option value="{{ $yearData->year }}"
                                        data-sisa="{{ $yearData->sisa_budget }}"
                                        data-total="{{ $yearData->total_budget }}">
                                    {{ $yearData->year }} - Sisa: Rp {{ number_format($yearData->sisa_budget, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-8" id="budget-info" style="display: none;">
                        <div class="row g-5">
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <span class="text-gray-600 fs-7 fw-semibold mb-2">Total Budget</span>
                                    <span class="text-gray-900 fs-3 fw-bold" id="total-budget">Rp 0</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <span class="text-gray-600 fs-7 fw-semibold mb-2">Sisa Budget</span>
                                    <span class="text-gray-900 fs-3 fw-bold" id="sisa-budget">Rp 0</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <span class="text-gray-600 fs-7 fw-semibold mb-2">Akan Digunakan</span>
                                    <span class="text-primary fs-3 fw-bold" id="akan-digunakan">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert if year not selected -->
        <div id="year-alert" class="alert bg-light-warning border border-warning border-dashed d-flex flex-column flex-sm-row p-5 mb-7">
            <i class="ki-outline ki-information-5 fs-2hx text-warning me-4 mb-5 mb-sm-0"></i>
            <div class="d-flex flex-column text-warning pe-0 pe-sm-10">
                <span class="fs-6 fw-semibold">Silakan pilih tahun budget terlebih dahulu untuk melanjutkan</span>
            </div>
        </div>

        <!-- Forms Container -->
        <div id="forms-container" style="display: none;">

            <div class="card mb-7">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Item Reimbursement</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-7">Tambahkan data kunjungan/tagihan medis</span>
                    </h3>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light-primary" onclick="addItemForm()">
                            <i class="ki-outline ki-plus fs-2"></i>
                            Tambah Item
                        </button>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div style="overflow-x: auto;">
                        <div style="min-width: 1180px;">

                            <!-- Table Header -->
                            <div class="d-flex align-items-center bg-light rounded px-3 py-2 mb-1">
                                <div style="width:28px; flex-shrink:0;"></div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:150px; flex-shrink:0;">Tanggal</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:200px; flex-shrink:0;">Nama Pasien</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:150px; flex-shrink:0;">Status</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:220px; flex-shrink:0;">Jenis Penyakit</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:120px; flex-shrink:0;">Dokter (Rp)</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:120px; flex-shrink:0;">Obat (Rp)</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:120px; flex-shrink:0;">Kacamata (Rp)</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:120px; flex-shrink:0;">Gigi (Rp)</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:115px; flex-shrink:0;">Catatan</div>
                                <div class="text-gray-500 fs-8 fw-bold px-1" style="width:85px; flex-shrink:0;">Subtotal</div>
                                <div style="width:32px; flex-shrink:0;"></div>
                            </div>

                            <div id="items-container"></div>

                        </div>
                    </div>

                    <div class="text-center text-gray-500 fs-7 mt-3" id="empty-hint" style="display:none;">
                        Belum ada item. Klik "Tambah Item" untuk menambahkan.
                    </div>
                </div>
            </div>

            <!-- Summary & Submit -->
            <div class="card">
                <div class="card-body p-9">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
                        <div class="d-flex flex-column flex-md-row gap-7 mb-5 mb-md-0">
                            <div class="d-flex flex-column">
                                <span class="text-gray-600 fs-7 fw-semibold mb-2">Total Reimbursement</span>
                                <span class="text-gray-900 fs-2x fw-bold" id="grand-total">Rp 0</span>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="text-gray-600 fs-7 fw-semibold mb-2">Sisa Budget Akhir</span>
                                <span class="fs-2x fw-bold text-success" id="sisa-setelah-input">Rp 0</span>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-light" onclick="window.location.href='{{ route('manage-reimbursements.index') }}'">Batal</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="indicator-label"><i class="ki-outline ki-check fs-2"></i> Simpan</span>
                                <span class="indicator-progress d-none">Menyimpan... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<style>
.form-item { transition: background 0.2s ease; }
.form-item:hover { background-color: #f9fafb; }
.form-item .form-control,
.form-item .form-select { font-size: 12px; padding: 6px 8px; height: 34px; min-height: 34px; }
.form-item .form-select { padding-right: 28px; background-size: 14px; }
/* Select2 compact inside row */
.form-item .select2-container .select2-selection--single { height: 34px; }
.form-item .select2-container--bootstrap5 .select2-selection--single .select2-selection__rendered { line-height: 34px; font-size: 12px; }
.form-item .select2-container--bootstrap5 .select2-selection--single .select2-selection__arrow { height: 34px; }
</style>

@endsection

@push('scripts')
<script>
let itemCount = 0;
const MAX_ITEMS = 10;
const JENIS_PENYAKIT_URL = "{{ route('manage-reimbursements.jenis-penyakit.list') }}";

let currentBudget = { total: 0, sisa: 0, year: null };

$(document).ready(function() {
    $('#year_budget').select2({ placeholder: 'Pilih tahun budget...', allowClear: false });

    $('#year_budget').on('change', function() {
        const selectedYear = $(this).val();
        if (!selectedYear) {
            $('#year-alert').show();
            $('#budget-info').hide();
            $('#forms-container').hide();
            return;
        }

        const opt = $(this).find('option:selected');
        currentBudget = {
            total: parseInt(opt.data('total')),
            sisa:  parseInt(opt.data('sisa')),
            year:  selectedYear
        };

        updateBudgetDisplay();
        $('#year-alert').hide();
        $('#budget-info').show();
        $('#forms-container').show();

        if (itemCount === 0) addItemForm();
    });

    $('#reimbursementForm').on('submit', function(e) {
        e.preventDefault();
        submitReimbursement();
    });
});

function updateBudgetDisplay() {
    $('#total-budget').text('Rp ' + formatRupiah(currentBudget.total));
    $('#sisa-budget').text('Rp ' + formatRupiah(currentBudget.sisa));
    calculateLivePreview();
}

function addItemForm(existing = null) {
    if (itemCount >= MAX_ITEMS) {
        Swal.fire({ icon: 'warning', title: 'Maksimal Tercapai', text: 'Maksimal ' + MAX_ITEMS + ' item reimbursement' });
        return;
    }

    const index = itemCount;
    itemCount++;

    const val     = (field) => existing ? (existing[field] ?? '') : '';
    const tagihan = (field) => existing && existing[field] > 0 ? formatRupiah(existing[field]) : '';

    const formHtml = `
        <div class="form-item d-flex align-items-center border-bottom px-3 py-2 reimbursement-item" data-index="${index}">

            <div style="width:28px; flex-shrink:0;">
                <span class="badge badge-circle badge-light-primary fs-8 fw-bold item-number">${itemCount}</span>
            </div>

            <!-- Tanggal -->
            <div style="width:150px; flex-shrink:0;" class="px-1">
                <input type="date" class="form-control form-control-solid form-control-sm"
                       name="children[${index}][tanggal]" value="${val('tanggal')}" max="{{ date('Y-m-d') }}">
            </div>

            <!-- Nama Pasien -->
            <div style="width:200px; flex-shrink:0;" class="px-1">
                <input type="text" class="form-control form-control-solid form-control-sm"
                       name="children[${index}][nama_reimbursement]" value="${val('nama_reimbursement')}" placeholder="Nama pasien...">
            </div>

            <!-- Status Keluarga -->
            <div style="width:150px; flex-shrink:0;" class="px-1">
                <select class="form-select form-select-solid form-select-sm" name="children[${index}][status_keluarga]" required>
                    <option value="">-</option>
                    ${['Karyawan','Istri','Suami','Anak'].map(s =>
                        `<option value="${s}" ${val('status_keluarga') == s ? 'selected' : ''}>${s}</option>`
                    ).join('')}
                </select>
            </div>

            <!-- Jenis Penyakit — Select2 AJAX -->
            <div style="width:220px; flex-shrink:0;" class="px-1">
                <select class="form-select form-select-solid form-select-sm jenispenyakit-select"
                        name="children[${index}][jenispenyakit_id]"
                        data-index="${index}" required>
                    <option value="">Pilih penyakit...</option>
                </select>
            </div>

            <!-- Tagihan Dokter -->
            <div style="width:120px; flex-shrink:0;" class="px-1">
                <input type="text" class="form-control form-control-solid form-control-sm tagihan-input"
                       name="children[${index}][tagihan_dokter]" value="${tagihan('tagihan_dokter')}"
                       placeholder="0" data-index="${index}" onkeyup="formatAndCalculate(this)">
            </div>

            <!-- Tagihan Obat -->
            <div style="width:120px; flex-shrink:0;" class="px-1">
                <input type="text" class="form-control form-control-solid form-control-sm tagihan-input"
                       name="children[${index}][tagihan_obat]" value="${tagihan('tagihan_obat')}"
                       placeholder="0" data-index="${index}" onkeyup="formatAndCalculate(this)">
            </div>

            <!-- Tagihan Kacamata -->
            <div style="width:120px; flex-shrink:0;" class="px-1">
                <input type="text" class="form-control form-control-solid form-control-sm tagihan-input"
                       name="children[${index}][tagihan_kacamata]" value="${tagihan('tagihan_kacamata')}"
                       placeholder="0" data-index="${index}" onkeyup="formatAndCalculate(this)">
            </div>

            <!-- Tagihan Gigi -->
            <div style="width:120px; flex-shrink:0;" class="px-1">
                <input type="text" class="form-control form-control-solid form-control-sm tagihan-input"
                       name="children[${index}][tagihan_gigi]" value="${tagihan('tagihan_gigi')}"
                       placeholder="0" data-index="${index}" onkeyup="formatAndCalculate(this)">
            </div>

            <!-- Catatan -->
            <div style="width:115px; flex-shrink:0;" class="px-1">
                <input type="text" class="form-control form-control-solid form-control-sm"
                       name="children[${index}][note]" value="${val('note')}" placeholder="Catatan...">
            </div>

            <!-- Subtotal -->
            <div style="width:85px; flex-shrink:0;" class="px-1">
                <span class="text-primary fw-bold fs-8 item-subtotal" data-index="${index}">Rp 0</span>
            </div>

            <!-- Hapus -->
            <div style="width:32px; flex-shrink:0;" class="text-end">
                <button type="button" class="btn btn-icon btn-sm btn-light-danger" style="width:26px;height:26px;" onclick="removeItemForm(${index})">
                    <i class="ki-outline ki-trash fs-4"></i>
                </button>
            </div>
        </div>
    `;

    $('#items-container').append(formHtml);
    $('#empty-hint').hide();

    // Init Select2 untuk baris baru
    initJenisPenyakitSelect2(index, existing);

    // Hitung subtotal jika existing
    if (existing) {
        const sub = (existing.tagihan_dokter ?? 0) + (existing.tagihan_obat ?? 0)
                  + (existing.tagihan_kacamata ?? 0) + (existing.tagihan_gigi ?? 0);
        $(`.item-subtotal[data-index="${index}"]`).text('Rp ' + formatRupiah(sub));
    }
}

function initJenisPenyakitSelect2(index, existing = null) {
    const $select = $(`.jenispenyakit-select[data-index="${index}"]`);

    // Pre-load existing value jika ada
    if (existing && existing.jenispenyakit_id && existing.jenis_penyakit) {
        const jp = existing.jenis_penyakit;
        const label = (jp.kode ? '[' + jp.kode + '] ' : '') + jp.nama_penyakit;
        $select.append(new Option(label, jp.id, true, true));
    }

    $select.select2({
        ajax: {
            url: JENIS_PENYAKIT_URL,
            dataType: 'json',
            delay: 250,
            data: p => ({ q: p.term || '', page: p.page || 1 }),
            processResults: d => ({ results: d.results, pagination: d.pagination }),
            cache: true
        },
        placeholder: 'Pilih penyakit...',
        allowClear: true,
        minimumInputLength: 0,
        width: '100%',
    });
}

function removeItemForm(index) {
    // Destroy select2 dulu sebelum remove
    const $select = $(`.jenispenyakit-select[data-index="${index}"]`);
    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');

    $(`.reimbursement-item[data-index="${index}"]`).remove();
    itemCount--;

    $('.reimbursement-item').each(function(i) {
        $(this).find('.item-number').first().text(i + 1);
    });

    if (itemCount === 0) $('#empty-hint').show();
    calculateLivePreview();
}

function formatAndCalculate(input) {
    let value = input.value.replace(/[^\d]/g, '');
    input.value = value ? formatRupiah(value) : '';

    const index = $(input).data('index');
    let subtotal = 0;
    $(`.tagihan-input[data-index="${index}"]`).each(function() {
        const v = $(this).val().replace(/[^\d]/g, '');
        subtotal += v ? parseInt(v) : 0;
    });
    $(`.item-subtotal[data-index="${index}"]`).text('Rp ' + formatRupiah(subtotal));
    calculateLivePreview();
}

function calculateLivePreview() {
    let total = 0;
    $('.tagihan-input').each(function() {
        const v = $(this).val().replace(/[^\d]/g, '');
        if (v) total += parseInt(v);
    });

    $('#akan-digunakan').text('Rp ' + formatRupiah(total));
    $('#grand-total').text('Rp ' + formatRupiah(total));

    const sisa = currentBudget.sisa - total;
    $('#sisa-setelah-input').text('Rp ' + formatRupiah(Math.abs(sisa)));
    if (sisa < 0) {
        $('#sisa-setelah-input').removeClass('text-success').addClass('text-danger');
    } else {
        $('#sisa-setelah-input').removeClass('text-danger').addClass('text-success');
    }
}

function submitReimbursement() {
    const submitBtn         = $('#submitBtn');
    const indicatorLabel    = submitBtn.find('.indicator-label');
    const indicatorProgress = submitBtn.find('.indicator-progress');

    if (!$('#year_budget').val()) {
        Swal.fire({ icon: 'warning', title: 'Perhatian!', text: 'Pilih tahun budget terlebih dahulu' });
        return;
    }

    const children = [];

    $('.reimbursement-item').each(function() {
        const index = $(this).data('index');

        const jenispenyakit_id = $(`.jenispenyakit-select[data-index="${index}"]`).val();
        const tagihan_dokter   = parseInt($(`[name="children[${index}][tagihan_dokter]"]`).val().replace(/[^\d]/g, '') || 0);
        const tagihan_obat     = parseInt($(`[name="children[${index}][tagihan_obat]"]`).val().replace(/[^\d]/g, '') || 0);
        const tagihan_kacamata = parseInt($(`[name="children[${index}][tagihan_kacamata]"]`).val().replace(/[^\d]/g, '') || 0);
        const tagihan_gigi     = parseInt($(`[name="children[${index}][tagihan_gigi]"]`).val().replace(/[^\d]/g, '') || 0);

        const subtotal = tagihan_dokter + tagihan_obat + tagihan_kacamata + tagihan_gigi;
        if (subtotal === 0) return;

        if (!jenispenyakit_id) {
            Swal.fire({ icon: 'warning', title: 'Perhatian!', text: 'Jenis penyakit wajib dipilih untuk setiap item' });
            return;
        }

        children.push({
            tanggal:            $(`[name="children[${index}][tanggal]"]`).val() || null,
            nama_reimbursement: $(`[name="children[${index}][nama_reimbursement]"]`).val() || null,
            status_keluarga:    $(`[name="children[${index}][status_keluarga]"]`).val() || null,
            jenispenyakit_id:   parseInt(jenispenyakit_id),
            note:               $(`[name="children[${index}][note]"]`).val() || null,
            tagihan_dokter, tagihan_obat, tagihan_kacamata, tagihan_gigi,
        });
    });

    if (children.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Perhatian!', text: 'Minimal harus ada 1 item dengan tagihan terisi' });
        return;
    }

    const formData = {
        _token:      $('input[name="_token"]').val(),
        karyawan_id: $('input[name="karyawan_id"]').val(),
        company_id:  $('input[name="company_id"]').val(),
        year_budget: $('#year_budget').val(),
        children,
    };

    submitBtn.prop('disabled', true);
    indicatorLabel.addClass('d-none');
    indicatorProgress.removeClass('d-none');

    $.ajax({
        url: "{{ route('manage-reimbursements.store') }}",
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(r) {
            if (r.success) {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: r.message, showConfirmButton: false, timer: 2000 })
                    .then(() => window.location.href = "{{ route('manage-reimbursements.index') }}");
            }
        },
        error: function(xhr) {
            Swal.fire({ icon: 'error', title: 'Gagal!', text: xhr.responseJSON?.message || 'Terjadi kesalahan.' });
        },
        complete: function() {
            submitBtn.prop('disabled', false);
            indicatorLabel.removeClass('d-none');
            indicatorProgress.addClass('d-none');
        }
    });
}

function formatRupiah(angka) {
    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
</script>
@endpush