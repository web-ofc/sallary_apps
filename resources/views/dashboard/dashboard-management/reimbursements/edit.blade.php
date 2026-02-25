@extends('layouts.master')

@section('content')

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h1 class="fs-2x fw-bold text-gray-900 mb-2">Edit Reimbursement Medical</h1>
            <div class="text-gray-600 fs-6">
                <span class="me-4"><i class="ki-outline ki-user fs-5 me-1"></i>{{ $karyawan->nama_lengkap }}</span>
                <span class="me-4"><i class="ki-outline ki-badge fs-5 me-1"></i>{{ $karyawan->absen_karyawan_id }}</span>
                <span class="me-4"><i class="ki-outline ki-building fs-5 me-1"></i>{{ $reimbursement->company->company_name ?? '-' }}</span>
                <span class="me-4"><i class="ki-outline ki-calendar fs-5 me-1"></i>{{ \Carbon\Carbon::parse($reimbursement->periode_slip . '-01')->format('F Y') }}</span>
                <span class="badge badge-light-primary">{{ $reimbursement->id_recapan }}</span>
            </div>
        </div>
        <a href="{{ route('manage-reimbursements.index') }}" class="btn btn-light btn-sm">
            <i class="ki-outline ki-arrow-left fs-3"></i>
            Kembali
        </a>
    </div>

    <form id="reimbursementForm">
        @csrf
        <input type="hidden" name="reimbursement_id" value="{{ $reimbursement->id }}">
        <input type="hidden" name="karyawan_id" value="{{ $reimbursement->karyawan_id }}">
        <input type="hidden" name="company_id" value="{{ $reimbursement->company_id }}">

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
                                        data-total="{{ $yearData->total_budget }}"
                                        {{ $yearData->year == $reimbursement->year_budget ? 'selected' : '' }}>
                                    {{ $yearData->year }} - Sisa: Rp {{ number_format($yearData->sisa_budget, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-8" id="budget-info">
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

        <!-- Items Card -->
        <div class="card mb-7">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Item Reimbursement</span>
                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Edit data kunjungan/tagihan medis</span>
                </h3>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-light-primary" onclick="addItemForm()">
                        <i class="ki-outline ki-plus fs-2"></i>
                        Tambah Item
                    </button>
                </div>
            </div>
            <div class="card-body pt-3">
                <div id="items-container"></div>
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
                        <button type="button" class="btn btn-light" onclick="window.location.href='{{ route('manage-reimbursements.index') }}'">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-check fs-2"></i>
                                Update
                            </span>
                            <span class="indicator-progress d-none">
                                Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.form-item { transition: all 0.3s ease; }
.form-item:hover { background-color: #f9fafb; }
</style>

@endsection

@push('scripts')
<script>
let itemCount = 0;
const MAX_ITEMS = 10;

let currentBudget = {
    total: 0,
    sisa: 0,
    year: null
};

// Data existing dari server
const existingChilds = @json($reimbursement->childs);

$(document).ready(function() {
    $('#year_budget').select2({
        placeholder: 'Pilih tahun budget...',
        allowClear: false
    });

    // Load budget info untuk tahun yang sudah dipilih
    loadBudgetInfo();

    // Load existing children
    loadExistingData();

    $('#year_budget').on('change', function() {
        loadBudgetInfo();
    });

    $('#reimbursementForm').on('submit', function(e) {
        e.preventDefault();
        submitReimbursement();
    });
});

function loadBudgetInfo() {
    const selectedOption = $('#year_budget').find('option:selected');
    const sisaBudget = parseInt(selectedOption.data('sisa')) || 0;
    const totalBudget = parseInt(selectedOption.data('total')) || 0;
    const selectedYear = $('#year_budget').val();

    if (selectedYear) {
        currentBudget = {
            total: totalBudget,
            sisa: sisaBudget,
            year: selectedYear
        };
        updateBudgetDisplay();
    }
}

function loadExistingData() {
    if (!existingChilds || existingChilds.length === 0) {
        $('#empty-hint').show();
        return;
    }

    existingChilds.forEach(child => {
        addItemForm(child);
    });

    calculateLivePreview();
}

function updateBudgetDisplay() {
    $('#total-budget').text('Rp ' + formatRupiah(currentBudget.total));
    $('#sisa-budget').text('Rp ' + formatRupiah(currentBudget.sisa));
    calculateLivePreview();
}

function addItemForm(existing = null) {
    if (itemCount >= MAX_ITEMS) {
        Swal.fire({ icon: 'warning', title: 'Maksimal Tercapai', text: 'Maksimal ' + MAX_ITEMS + ' item' });
        return;
    }

    const index = itemCount;
    itemCount++;

    const val = (field) => existing ? (existing[field] ?? '') : '';
    const tagihan = (field) => existing && existing[field] > 0 ? formatRupiah(existing[field]) : '';

    const formHtml = `
        <div class="form-item border border-gray-300 border-dashed rounded p-6 mb-5 reimbursement-item" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="d-flex align-items-center">
                    <span class="badge badge-circle badge-light-primary fs-6 fw-bold me-3 item-number">${itemCount}</span>
                    <span class="text-gray-800 fw-semibold fs-6">Item Reimbursement</span>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger" onclick="removeItemForm(${index})">
                    <i class="ki-outline ki-trash fs-2"></i>
                </button>
            </div>

            <div class="row g-5">
                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Tanggal</label>
                    <input type="date" class="form-control form-control-solid"
                           name="children[${index}][tanggal]"
                           value="${val('tanggal')}"
                           max="{{ date('Y-m-d') }}">
                </div>

                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Nama Pasien</label>
                    <input type="text" class="form-control form-control-solid"
                           name="children[${index}][nama_reimbursement]"
                           value="${val('nama_reimbursement')}"
                           placeholder="Nama pasien...">
                </div>

                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Status Keluarga</label>
                    <select class="form-select form-select-solid" name="children[${index}][status_keluarga]">
                        <option value="">Pilih status</option>
                        ${['Karyawan','Istri','Suami','Anak','Orang Tua'].map(s =>
                            `<option value="${s}" ${val('status_keluarga') == s ? 'selected' : ''}>${s}</option>`
                        ).join('')}
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Jenis Penyakit</label>
                    <input type="text" class="form-control form-control-solid"
                           name="children[${index}][jenis_penyakit]"
                           value="${val('jenis_penyakit')}"
                           placeholder="Flu, Demam...">
                </div>

                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Catatan</label>
                    <input type="text" class="form-control form-control-solid"
                           name="children[${index}][note]"
                           value="${val('note')}"
                           placeholder="Catatan tambahan...">
                </div>

                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Tagihan Dokter</label>
                    <input type="text" class="form-control form-control-solid tagihan-input"
                           name="children[${index}][tagihan_dokter]"
                           value="${tagihan('tagihan_dokter')}"
                           placeholder="0"
                           data-index="${index}"
                           onkeyup="formatAndCalculate(this)">
                </div>

                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Tagihan Obat</label>
                    <input type="text" class="form-control form-control-solid tagihan-input"
                           name="children[${index}][tagihan_obat]"
                           value="${tagihan('tagihan_obat')}"
                           placeholder="0"
                           data-index="${index}"
                           onkeyup="formatAndCalculate(this)">
                </div>

                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Tagihan Kacamata</label>
                    <input type="text" class="form-control form-control-solid tagihan-input"
                           name="children[${index}][tagihan_kacamata]"
                           value="${tagihan('tagihan_kacamata')}"
                           placeholder="0"
                           data-index="${index}"
                           onkeyup="formatAndCalculate(this)">
                </div>

                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Tagihan Gigi</label>
                    <input type="text" class="form-control form-control-solid tagihan-input"
                           name="children[${index}][tagihan_gigi]"
                           value="${tagihan('tagihan_gigi')}"
                           placeholder="0"
                           data-index="${index}"
                           onkeyup="formatAndCalculate(this)">
                </div>

                <div class="col-12">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-gray-600 fs-8 fw-semibold">Subtotal item ini:</span>
                        <span class="text-primary fw-bold fs-7 item-subtotal" data-index="${index}">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#items-container').append(formHtml);
    $('#empty-hint').hide();

    // Hitung subtotal existing jika ada
    if (existing) {
        const subtotal = (existing.tagihan_dokter ?? 0) + (existing.tagihan_obat ?? 0)
                       + (existing.tagihan_kacamata ?? 0) + (existing.tagihan_gigi ?? 0);
        $(`.item-subtotal[data-index="${index}"]`).text('Rp ' + formatRupiah(subtotal));
    }
}

function removeItemForm(index) {
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
        const val = $(this).val().replace(/[^\d]/g, '');
        subtotal += val ? parseInt(val) : 0;
    });
    $(`.item-subtotal[data-index="${index}"]`).text('Rp ' + formatRupiah(subtotal));

    calculateLivePreview();
}

function calculateLivePreview() {
    let total = 0;

    $('.tagihan-input').each(function() {
        const value = $(this).val().replace(/[^\d]/g, '');
        if (value) total += parseInt(value);
    });

    $('#akan-digunakan').text('Rp ' + formatRupiah(total));
    $('#grand-total').text('Rp ' + formatRupiah(total));

    const sisaSetelahInput = currentBudget.sisa - total;
    $('#sisa-setelah-input').text('Rp ' + formatRupiah(Math.abs(sisaSetelahInput)));

    if (sisaSetelahInput < 0) {
        $('#sisa-setelah-input').removeClass('text-success').addClass('text-danger');
    } else {
        $('#sisa-setelah-input').removeClass('text-danger').addClass('text-success');
    }
}

function submitReimbursement() {
    const submitBtn = $('#submitBtn');
    const indicatorLabel = submitBtn.find('.indicator-label');
    const indicatorProgress = submitBtn.find('.indicator-progress');

    if (!$('#year_budget').val()) {
        Swal.fire({ icon: 'warning', title: 'Perhatian!', text: 'Pilih tahun budget terlebih dahulu' });
        return;
    }

    const children = [];

    $('.reimbursement-item').each(function() {
        const index = $(this).data('index');

        const tagihan_dokter   = parseInt($(`[name="children[${index}][tagihan_dokter]"]`).val().replace(/[^\d]/g, '') || 0);
        const tagihan_obat     = parseInt($(`[name="children[${index}][tagihan_obat]"]`).val().replace(/[^\d]/g, '') || 0);
        const tagihan_kacamata = parseInt($(`[name="children[${index}][tagihan_kacamata]"]`).val().replace(/[^\d]/g, '') || 0);
        const tagihan_gigi     = parseInt($(`[name="children[${index}][tagihan_gigi]"]`).val().replace(/[^\d]/g, '') || 0);

        const subtotal = tagihan_dokter + tagihan_obat + tagihan_kacamata + tagihan_gigi;
        if (subtotal === 0) return;

        children.push({
            tanggal:            $(`[name="children[${index}][tanggal]"]`).val() || null,
            nama_reimbursement: $(`[name="children[${index}][nama_reimbursement]"]`).val() || null,
            status_keluarga:    $(`[name="children[${index}][status_keluarga]"]`).val() || null,
            jenis_penyakit:     $(`[name="children[${index}][jenis_penyakit]"]`).val() || null,
            note:               $(`[name="children[${index}][note]"]`).val() || null,
            tagihan_dokter,
            tagihan_obat,
            tagihan_kacamata,
            tagihan_gigi,
        });
    });

    if (children.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Perhatian!', text: 'Minimal harus ada 1 item dengan tagihan terisi' });
        return;
    }

    const formData = {
        _token:      $('input[name="_token"]').val(),
        _method:     'PUT',
        karyawan_id: $('input[name="karyawan_id"]').val(),
        company_id:  $('input[name="company_id"]').val(),
        year_budget: $('#year_budget').val(),
        children,
    };

    submitBtn.prop('disabled', true);
    indicatorLabel.addClass('d-none');
    indicatorProgress.removeClass('d-none');

    const reimbursementId = $('input[name="reimbursement_id"]').val();

    $.ajax({
        url: "{{ url('manage-reimbursements') }}/" + reimbursementId,
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.href = "{{ route('manage-reimbursements.index') }}";
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: xhr.responseJSON?.message || 'Terjadi kesalahan.'
            });
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