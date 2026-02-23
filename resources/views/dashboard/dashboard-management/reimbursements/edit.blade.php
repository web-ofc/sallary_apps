@extends('layouts.master')

@section('content')

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Edit Reimbursement</h3>
            <div class="text-muted">
                <span class="me-3"><i class="fas fa-user me-1"></i> {{ $karyawan->nama_lengkap }}</span>
                <span class="me-3"><i class="fas fa-id-badge me-1"></i> {{ $karyawan->absen_karyawan_id }}</span>
                <span class="me-3"><i class="fas fa-building me-1"></i> {{ $reimbursement->company->company_name ?? '-' }}</span>  <!-- ✅ TAMBAH INI -->
                <span class="me-3"><i class="fas fa-calendar me-1"></i> {{ \Carbon\Carbon::parse($reimbursement->periode_slip . '-01')->format('F Y') }}</span>
                <span class="badge badge-light-primary">{{ $reimbursement->id_recapan }}</span>
            </div>
        </div>
        <a href="{{ route('manage-reimbursements.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
    <form id="reimbursementForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="reimbursement_id" value="{{ $reimbursement->id }}">

        <!-- Budget Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <label class="form-label fw-bold mb-3">Tahun Budget <span class="text-danger">*</span></label>
                        <select class="form-select" id="year_budget" name="year_budget" 
                                data-control="select2" data-placeholder="Pilih tahun..." required>
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
                </div>
            </div>
            
            <div class="col-md-8" id="budget-info">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center py-3">
                                <div class="text-muted small mb-1">Total Budget</div>
                                <div class="h5 mb-0 fw-bold" id="total-budget">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light-success">
                            <div class="card-body text-center py-3">
                                <div class="text-muted small mb-1">Sisa Budget</div>
                                <div class="h5 mb-0 fw-bold text-success" id="sisa-budget">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light-primary">
                            <div class="card-body text-center py-3">
                                <div class="text-muted small mb-1">Akan Digunakan</div>
                                <div class="h5 mb-0 fw-bold text-primary" id="akan-digunakan">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0" id="sisa-card">
                            <div class="card-body text-center py-3">
                                <div class="text-muted small mb-1">Sisa Akhir</div>
                                <div class="h5 mb-0 fw-bold" id="sisa-setelah-input">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms Container -->
        <div id="forms-container">
            
            <!-- GENERAL Section -->
            <div id="general-section" style="display: none;">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">General Medical</h5>
                    </div>
                    <div class="card-body">
                        <div id="general-forms-container"></div>
                        
                        <div class="mt-3" id="general-load-more">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addGeneralForm()">
                                <i class="fas fa-plus me-1"></i> Tambah Item General
                            </button>
                            <small class="text-muted ms-2">Maksimal 6 item</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OTHER Section -->
            <div id="other-section" style="display: none;">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Other Medical</h5>
                    </div>
                    <div class="card-body">
                        <div id="other-forms-container"></div>
                        
                        <div class="mt-3" id="other-load-more">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOtherForm()">
                                <i class="fas fa-plus me-1"></i> Tambah Item Other
                            </button>
                            <small class="text-muted ms-2">Maksimal 6 item</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary & Submit -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-1">Total Reimbursement</h4>
                            <h2 class="mb-0 text-success fw-bold" id="grand-total">Rp 0</h2>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-light me-2" onclick="window.location.href='{{ route('manage-reimbursements.index') }}'">
                                Batal
                            </button>
                            <button type="submit" class="btn btn-primary px-5" id="submitBtn">
                                <span class="indicator-label">
                                    <i class="fas fa-save me-1"></i> Update
                                </span>
                                <span class="indicator-progress" style="display: none;">
                                    Menyimpan... <span class="spinner-border spinner-border-sm ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

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
const existingData = @json($reimbursement->childs);

$(document).ready(function() {
    $('#year_budget').select2({
        placeholder: 'Pilih tahun budget...',
        allowClear: false
    });

    // Load budget info untuk tahun yang sudah dipilih
    loadBudgetInfo();

    // Load existing children data
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
    const sisaBudget = parseInt(selectedOption.data('sisa'));
    const totalBudget = parseInt(selectedOption.data('total'));
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
    const generalData = existingData.filter(child => child.reimbursement_type.group_medical === 'general');
    const otherData = existingData.filter(child => child.reimbursement_type.group_medical === 'other');

    // Load General items
    if (generalData.length > 0) {
        $('#general-section').show();
        generalData.forEach(child => {
            addGeneralForm(child);
        });
    }

    // Load Other items
    if (otherData.length > 0) {
        $('#other-section').show();
        otherData.forEach(child => {
            addOtherForm(child);
        });
    }

    calculateLivePreview();
}

function updateBudgetDisplay() {
    $('#total-budget').text('Rp ' + formatRupiah(currentBudget.total));
    $('#sisa-budget').text('Rp ' + formatRupiah(currentBudget.sisa));
    calculateLivePreview();
}

function addGeneralForm(existingData = null) {
    if (generalCount >= MAX_GENERAL) {
        Swal.fire({
            icon: 'warning',
            title: 'Maksimal Tercapai',
            text: 'Maksimal 6 item General'
        });
        return;
    }

    generalCount++;
    const index = generalCount - 1;

    const formHtml = `
        <div class="border rounded p-3 mb-3 general-form-item" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Item #${generalCount}</strong>
                <button type="button" class="btn btn-sm btn-light" onclick="removeGeneralForm(${index})"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tipe <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm general-type-${index}" name="children[general_${index}][reimbursement_type_id]" 
                            data-control="select2" required>
                        <option></option>
                        ${generalTypes.map(type => `<option value="${type.id}" ${existingData && existingData.reimbursement_type_id == type.id ? 'selected' : ''}>${type.medical_type}</option>`).join('')}
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Harga <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm harga-input" 
                           name="children[general_${index}][harga]" 
                           value="${existingData ? formatRupiah(existingData.harga) : ''}"
                           placeholder="0" 
                           onkeyup="formatAndCalculate(this)" required>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Penyakit</label>
                    <input type="text" class="form-control form-control-sm" 
                           name="children[general_${index}][jenis_penyakit]" 
                           value="${existingData ? (existingData.jenis_penyakit || '') : ''}"
                           placeholder="Flu, Demam...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status Keluarga</label>
                    <select class="form-select form-select-sm" name="children[general_${index}][status_keluarga]">
                        <option value="">-- Pilih --</option>
                        <option value="Diri Sendiri" ${existingData && existingData.status_keluarga == 'Diri Sendiri' ? 'selected' : ''}>Diri Sendiri</option>
                        <option value="Istri" ${existingData && existingData.status_keluarga == 'Istri' ? 'selected' : ''}>Istri</option>
                        <option value="Suami" ${existingData && existingData.status_keluarga == 'Suami' ? 'selected' : ''}>Suami</option>
                        <option value="Anak" ${existingData && existingData.status_keluarga == 'Anak' ? 'selected' : ''}>Anak</option>
                        <option value="Orang Tua" ${existingData && existingData.status_keluarga == 'Orang Tua' ? 'selected' : ''}>Orang Tua</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Catatan</label>
                    <input type="text" class="form-control form-control-sm" 
                           name="children[general_${index}][note]" 
                           value="${existingData ? (existingData.note || '') : ''}"
                           placeholder="Catatan...">
                </div>
            </div>
        </div>
    `;

    $('#general-forms-container').append(formHtml);
    $(`.general-type-${index}`).select2({ placeholder: 'Pilih tipe...' });

    if (generalCount >= MAX_GENERAL) {
        $('#general-load-more').hide();
    }
}

function addOtherForm(existingData = null) {
    if (otherCount >= MAX_OTHER) {
        Swal.fire({
            icon: 'warning',
            title: 'Maksimal Tercapai',
            text: 'Maksimal 6 item Other'
        });
        return;
    }

    otherCount++;
    const index = otherCount - 1;

    const formHtml = `
        <div class="border rounded p-3 mb-3 other-form-item" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Item #${otherCount}</strong>
                <button type="button" class="btn btn-sm btn-light" onclick="removeOtherForm(${index})"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tipe <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm other-type-${index}" name="children[other_${index}][reimbursement_type_id]" 
                            data-control="select2" required>
                        <option></option>
                        ${otherTypes.map(type => `<option value="${type.id}" ${existingData && existingData.reimbursement_type_id == type.id ? 'selected' : ''}>${type.medical_type}</option>`).join('')}
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Harga <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm harga-input" 
                           name="children[other_${index}][harga]" 
                           value="${existingData ? formatRupiah(existingData.harga) : ''}"
                           placeholder="0" 
                           onkeyup="formatAndCalculate(this)" required>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Penyakit</label>
                    <input type="text" class="form-control form-control-sm" 
                           name="children[other_${index}][jenis_penyakit]" 
                           value="${existingData ? (existingData.jenis_penyakit || '') : ''}"
                           placeholder="Contoh...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status Keluarga</label>
                    <select class="form-select form-select-sm" name="children[other_${index}][status_keluarga]">
                        <option value="">-- Pilih --</option>
                        <option value="Diri Sendiri" ${existingData && existingData.status_keluarga == 'Diri Sendiri' ? 'selected' : ''}>Diri Sendiri</option>
                        <option value="Istri" ${existingData && existingData.status_keluarga == 'Istri' ? 'selected' : ''}>Istri</option>
                        <option value="Suami" ${existingData && existingData.status_keluarga == 'Suami' ? 'selected' : ''}>Suami</option>
                        <option value="Anak" ${existingData && existingData.status_keluarga == 'Anak' ? 'selected' : ''}>Anak</option>
                        <option value="Orang Tua" ${existingData && existingData.status_keluarga == 'Orang Tua' ? 'selected' : ''}>Orang Tua</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Catatan</label>
                    <input type="text" class="form-control form-control-sm" 
                           name="children[other_${index}][note]" 
                           value="${existingData ? (existingData.note || '') : ''}"
                           placeholder="Catatan...">
                </div>
            </div>
        </div>
    `;

    $('#other-forms-container').append(formHtml);
    $(`.other-type-${index}`).select2({ placeholder: 'Pilih tipe...' });

    if (otherCount >= MAX_OTHER) {
        $('#other-load-more').hide();
    }
}

function removeGeneralForm(index) {
    $(`.general-form-item[data-index="${index}"]`).remove();
    generalCount--;
    
    $('.general-form-item').each(function(i) {
        $(this).find('strong').first().text('Item #' + (i + 1));
    });
    
    $('#general-load-more').show();
    
    if (generalCount === 0) {
        $('#general-section').hide();
    }
    
    calculateLivePreview();
}

function removeOtherForm(index) {
    $(`.other-form-item[data-index="${index}"]`).remove();
    otherCount--;
    
    $('.other-form-item').each(function(i) {
        $(this).find('strong').first().text('Item #' + (i + 1));
    });
    
    $('#other-load-more').show();
    
    if (otherCount === 0) {
        $('#other-section').hide();
    }
    
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
    
    $('#sisa-card').removeClass('bg-light-success bg-light-danger');
    if (sisaSetelahInput < 0) {
        $('#sisa-setelah-input').removeClass('text-success').addClass('text-danger');
        $('#sisa-card').addClass('bg-light-danger');
    } else {
        $('#sisa-setelah-input').removeClass('text-danger').addClass('text-success');
        $('#sisa-card').addClass('bg-light-success');
    }
}

function submitReimbursement() {
    const submitBtn = $('#submitBtn');
    const indicatorLabel = submitBtn.find('.indicator-label');
    const indicatorProgress = submitBtn.find('.indicator-progress');
    
    const formData = {
        _token: $('input[name="_token"]').val(),
        _method: 'PUT',
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
        Swal.fire({ icon: 'warning', title: 'Perhatian!', text: 'Minimal harus ada 1 item dengan harga terisi' });
        return;
    }
    
    submitBtn.prop('disabled', true);
    indicatorLabel.hide();
    indicatorProgress.show();
    
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
            indicatorLabel.show();
            indicatorProgress.hide();
        }
    });
}

function formatRupiah(angka) {
    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
</script>
@endpush