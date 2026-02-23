@extends('layouts.master')

@section('content')

<div class="container-fluid">
    <!-- Header -->
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
                    <!-- Year Selection -->
                    <div class="col-lg-4">
                        <label class="form-label fs-6 fw-semibold text-gray-800 mb-3">
                            Tahun Budget
                            <span class="text-danger">*</span>
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
                    
                    <!-- Budget Info -->
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
        <div id="year-alert" class="alert alert-dismissible bg-light-warning border border-warning border-dashed d-flex flex-column flex-sm-row p-5 mb-7">
            <i class="ki-outline ki-information-5 fs-2hx text-warning me-4 mb-5 mb-sm-0"></i>
            <div class="d-flex flex-column text-warning pe-0 pe-sm-10">
                <span class="fs-6 fw-semibold">Silakan pilih tahun budget terlebih dahulu untuk melanjutkan</span>
            </div>
        </div>

        <!-- Forms Container -->
        <div id="forms-container" style="display: none;">
            
            <!-- Action Buttons -->
            <div class="mb-7" id="action-buttons">
                <button type="button" class="btn btn-light-primary me-3" id="show-general-btn" onclick="showGeneralSection()">
                    <i class="ki-outline ki-plus-square fs-2"></i>
                    Tambah General Medical
                </button>
                <button type="button" class="btn btn-light-warning" id="show-other-btn" onclick="showOtherSection()">
                    <i class="ki-outline ki-plus-square fs-2"></i>
                    Tambah Other Medical
                </button>
            </div>

            <!-- GENERAL Section -->
            <div id="general-section" style="display: none;">
                <div class="card mb-7">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">General Medical</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Rawat jalan, obat, dll</span>
                        </h3>
                        <div class="card-toolbar">
                            <button type="button" class="btn btn-sm btn-light-danger" onclick="hideGeneralSection()">
                                <i class="ki-outline ki-cross fs-2"></i>
                                Tutup
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        <div id="general-forms-container"></div>
                        
                        <div class="mt-5" id="general-load-more">
                            <button type="button" class="btn btn-sm btn-light-primary" onclick="addGeneralForm()">
                                <i class="ki-outline ki-plus fs-2"></i>
                                Tambah Item
                            </button>
                            <span class="text-gray-600 fs-7 ms-3">Maksimal 6 item</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OTHER Section -->
            <div id="other-section" style="display: none;">
                <div class="card mb-7">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Other Medical</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Rawat inap, operasi, dll</span>
                        </h3>
                        <div class="card-toolbar">
                            <button type="button" class="btn btn-sm btn-light-danger" onclick="hideOtherSection()">
                                <i class="ki-outline ki-cross fs-2"></i>
                                Tutup
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        <div id="other-forms-container"></div>
                        
                        <div class="mt-5" id="other-load-more">
                            <button type="button" class="btn btn-sm btn-light-primary" onclick="addOtherForm()">
                                <i class="ki-outline ki-plus fs-2"></i>
                                Tambah Item
                            </button>
                            <span class="text-gray-600 fs-7 ms-3">Maksimal 6 item</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary & Submit -->
            <div class="card">
                <div class="card-body p-9">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
                        
                        <div class="d-flex flex-column flex-md-row gap-7 mb-5 mb-md-0">
                            <!-- Total -->
                            <div class="d-flex flex-column">
                                <span class="text-gray-600 fs-7 fw-semibold mb-2">Total Reimbursement</span>
                                <span class="text-gray-900 fs-2x fw-bold" id="grand-total">Rp 0</span>
                            </div>

                            <!-- Sisa -->
                            <div class="d-flex flex-column">
                                <span class="text-gray-600 fs-7 fw-semibold mb-2">Sisa Budget Akhir</span>
                                <span class="fs-2x fw-bold" id="sisa-setelah-input">Rp 0</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-light" onclick="window.location.href='{{ route('manage-reimbursements.index') }}'">
                                Batal
                            </button>

                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-check fs-2"></i>
                                    Simpan
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

        </div>
    </form>
</div>

<!-- Custom minimal style for form items -->
<style>
.form-item {
    transition: all 0.3s ease;
}
.form-item:hover {
    background-color: #f9fafb;
}
</style>

@endsection

@push('scripts')
<script>
let generalCount = 0;
let otherCount = 0;
const MAX_GENERAL = 6;
const MAX_OTHER = 6;

let currentBudget = {
    total: 0,
    sisa: 0,
    year: null
};

const generalTypes = @json($generalTypes);
const otherTypes = @json($otherTypes);

$(document).ready(function() {
    $('#year_budget').select2({
        placeholder: 'Pilih tahun budget...',
        allowClear: false
    });

    $('#year_budget').on('change', function() {
        const selectedYear = $(this).val();
        
        if (!selectedYear) {
            $('#year-alert').show();
            $('#budget-info').hide();
            $('#forms-container').hide();
            return;
        }

        const selectedOption = $(this).find('option:selected');
        const sisaBudget = parseInt(selectedOption.data('sisa'));
        const totalBudget = parseInt(selectedOption.data('total'));

        currentBudget = {
            total: totalBudget,
            sisa: sisaBudget,
            year: selectedYear
        };

        updateBudgetDisplay();
        $('#year-alert').hide();
        $('#budget-info').show();
        $('#forms-container').show();
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

function addGeneralForm() {
    if (generalCount >= MAX_GENERAL) {
        Swal.fire({
            icon: 'warning',
            title: 'Maksimal Tercapai',
            text: 'Maksimal 6 item General Medical'
        });
        return;
    }

    generalCount++;
    const index = generalCount - 1;

    const formHtml = `
        <div class="form-item border border-gray-300 border-dashed rounded p-6 mb-5 general-form-item" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="d-flex align-items-center">
                    <span class="badge badge-circle badge-light-primary fs-6 fw-bold me-3">${generalCount}</span>
                    <span class="text-gray-800 fw-semibold fs-6">Item General Medical</span>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger" onclick="removeGeneralForm(${index})">
                    <i class="ki-outline ki-trash fs-2"></i>
                </button>
            </div>
            
            <div class="row g-5">
                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Tipe Medical <span class="text-danger">*</span></label>
                    <select class="form-select form-select-solid" name="children[general_${index}][reimbursement_type_id]" 
                            data-control="select2" data-placeholder="Pilih tipe..." required>
                        <option></option>
                        ${generalTypes.map(type => `<option value="${type.id}">${type.medical_type}</option>`).join('')}
                    </select>
                </div>
                
                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Harga <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-solid harga-input" 
                           name="children[general_${index}][harga]" 
                           placeholder="0" 
                           onkeyup="formatAndCalculate(this)" required>
                </div>
                
                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Jenis Penyakit</label>
                    <input type="text" class="form-control form-control-solid" 
                           name="children[general_${index}][jenis_penyakit]" 
                           placeholder="Flu, Demam...">
                </div>
                
                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Status Keluarga</label>
                    <select class="form-select form-select-solid" name="children[general_${index}][status_keluarga]">
                        <option value="">Pilih status</option>
                        <option value="Diri Sendiri">Diri Sendiri</option>
                        <option value="Istri">Istri</option>
                        <option value="Suami">Suami</option>
                        <option value="Anak">Anak</option>
                        <option value="Orang Tua">Orang Tua</option>
                    </select>
                </div>
                
                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Catatan</label>
                    <input type="text" class="form-control form-control-solid" 
                           name="children[general_${index}][note]" 
                           placeholder="Catatan tambahan...">
                </div>
            </div>
        </div>
    `;

    $('#general-forms-container').append(formHtml);
    $(`[name="children[general_${index}][reimbursement_type_id]"]`).select2({ 
        placeholder: 'Pilih tipe medical...',
        dropdownParent: $(`.general-form-item[data-index="${index}"]`)
    });

    if (generalCount >= MAX_GENERAL) {
        $('#general-load-more').hide();
    }
}

function removeGeneralForm(index) {
    $(`.general-form-item[data-index="${index}"]`).remove();
    generalCount--;
    
    // Renumber items
    $('.general-form-item').each(function(i) {
        $(this).find('.badge-circle').first().text(i + 1);
    });
    
    $('#general-load-more').show();
    calculateLivePreview();
}

function showGeneralSection() {
    $('#general-section').show();
    $('#show-general-btn').hide();
    if (generalCount === 0) {
        addGeneralForm();
    }
}

function hideGeneralSection() {
    if (generalCount > 0) {
        Swal.fire({
            title: 'Yakin ingin menutup?',
            text: "Semua item General Medical akan dihapus",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, tutup',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#general-forms-container').empty();
                generalCount = 0;
                $('#general-section').hide();
                $('#show-general-btn').show();
                $('#general-load-more').show();
                calculateLivePreview();
            }
        });
    } else {
        $('#general-section').hide();
        $('#show-general-btn').show();
    }
}

function showOtherSection() {
    $('#other-section').show();
    $('#show-other-btn').hide();
    if (otherCount === 0) {
        addOtherForm();
    }
}

function hideOtherSection() {
    if (otherCount > 0) {
        Swal.fire({
            title: 'Yakin ingin menutup?',
            text: "Semua item Other Medical akan dihapus",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, tutup',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#other-forms-container').empty();
                otherCount = 0;
                $('#other-section').hide();
                $('#show-other-btn').show();
                $('#other-load-more').show();
                calculateLivePreview();
            }
        });
    } else {
        $('#other-section').hide();
        $('#show-other-btn').show();
    }
}

function addOtherForm() {
    if (otherCount >= MAX_OTHER) {
        Swal.fire({
            icon: 'warning',
            title: 'Maksimal Tercapai',
            text: 'Maksimal 6 item Other Medical'
        });
        return;
    }

    otherCount++;
    const index = otherCount - 1;

    const formHtml = `
        <div class="form-item border border-gray-300 border-dashed rounded p-6 mb-5 other-form-item" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="d-flex align-items-center">
                    <span class="badge badge-circle badge-light-warning fs-6 fw-bold me-3">${otherCount}</span>
                    <span class="text-gray-800 fw-semibold fs-6">Item Other Medical</span>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger" onclick="removeOtherForm(${index})">
                    <i class="ki-outline ki-trash fs-2"></i>
                </button>
            </div>
            
            <div class="row g-5">
                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Tipe Medical <span class="text-danger">*</span></label>
                    <select class="form-select form-select-solid" name="children[other_${index}][reimbursement_type_id]" 
                            data-control="select2" data-placeholder="Pilih tipe..." required>
                        <option></option>
                        ${otherTypes.map(type => `<option value="${type.id}">${type.medical_type}</option>`).join('')}
                    </select>
                </div>
                
                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Harga <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-solid harga-input" 
                           name="children[other_${index}][harga]" 
                           placeholder="0" 
                           onkeyup="formatAndCalculate(this)" required>
                </div>
                
                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Jenis Penyakit</label>
                    <input type="text" class="form-control form-control-solid" 
                           name="children[other_${index}][jenis_penyakit]" 
                           placeholder="Contoh...">
                </div>
                
                <div class="col-lg-2">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Status Keluarga</label>
                    <select class="form-select form-select-solid" name="children[other_${index}][status_keluarga]">
                        <option value="">Pilih status</option>
                        <option value="Diri Sendiri">Diri Sendiri</option>
                        <option value="Istri">Istri</option>
                        <option value="Suami">Suami</option>
                        <option value="Anak">Anak</option>
                        <option value="Orang Tua">Orang Tua</option>
                    </select>
                </div>
                
                <div class="col-lg-3">
                    <label class="form-label fs-7 fw-semibold text-gray-700">Catatan</label>
                    <input type="text" class="form-control form-control-solid" 
                           name="children[other_${index}][note]" 
                           placeholder="Catatan tambahan...">
                </div>
            </div>
        </div>
    `;

    $('#other-forms-container').append(formHtml);
    $(`[name="children[other_${index}][reimbursement_type_id]"]`).select2({ 
        placeholder: 'Pilih tipe medical...',
        dropdownParent: $(`.other-form-item[data-index="${index}"]`)
    });

    if (otherCount >= MAX_OTHER) {
        $('#other-load-more').hide();
    }
}

function removeOtherForm(index) {
    $(`.other-form-item[data-index="${index}"]`).remove();
    otherCount--;
    
    // Renumber items
    $('.other-form-item').each(function(i) {
        $(this).find('.badge-circle').first().text(i + 1);
    });
    
    $('#other-load-more').show();
    calculateLivePreview();
}

function formatAndCalculate(input) {
    let value = input.value.replace(/[^\d]/g, '');
    input.value = formatRupiah(value);
    calculateLivePreview();
}

function calculateLivePreview() {
    let total = 0;
    
    $('.harga-input').each(function() {
        const value = $(this).val().replace(/[^\d]/g, '');
        if (value) {
            total += parseInt(value);
        }
    });

    $('#akan-digunakan').text('Rp ' + formatRupiah(total));
    $('#grand-total').text('Rp ' + formatRupiah(total));
    
    const sisaSetelahInput = currentBudget.sisa - total;
    $('#sisa-setelah-input').text('Rp ' + formatRupiah(sisaSetelahInput));
    
    // Update text color based on budget
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
        Swal.fire({ 
            icon: 'warning', 
            title: 'Perhatian!', 
            text: 'Pilih tahun budget terlebih dahulu' 
        });
        return;
    }
    
    const formData = {
        _token: $('input[name="_token"]').val(),
        karyawan_id: $('input[name="karyawan_id"]').val(),
        company_id: $('input[name="company_id"]').val(),
        periode_slip: $('input[name="periode_slip"]').val(),
        year_budget: $('#year_budget').val(),
        children: []
    };
    
    // Collect all inputs
    $('input[name^="children["], select[name^="children["]').each(function() {
        const name = $(this).attr('name');
        const match = name.match(/children\[(general|other)_(\d+)\]\[(\w+)\]/);
        
        if (match) {
            const [, group, index, field] = match;
            const key = `${group}_${index}`;
            
            let childObj = formData.children.find(c => c._key === key);
            if (!childObj) {
                childObj = { _key: key };
                formData.children.push(childObj);
            }
            
            if (field === 'harga') {
                const value = $(this).val().replace(/[^\d]/g, '');
                childObj[field] = parseInt(value) || 0;
            } else {
                childObj[field] = $(this).val();
            }
        }
    });
    
    formData.children = formData.children
        .filter(child => child.harga && child.harga > 0)
        .map(child => {
            delete child._key;
            return child;
        });
    
    if (formData.children.length === 0) {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Perhatian!', 
            text: 'Minimal harus ada 1 item dengan harga terisi' 
        });
        return;
    }
    
    submitBtn.prop('disabled', true);
    indicatorLabel.addClass('d-none');
    indicatorProgress.removeClass('d-none');
    
    $.ajax({
        url: "{{ route('manage-reimbursements.store') }}",
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