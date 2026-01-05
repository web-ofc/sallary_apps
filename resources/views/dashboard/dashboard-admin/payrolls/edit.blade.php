@extends('layouts.master')

@section('title', 'Edit Payroll')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap flex-stack mb-6">
        <h1 class="fw-bold my-2">
            <i class="fas fa-edit text-warning"></i> Edit Payroll - {{ $payroll->periode }}
        </h1>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('payrolls.show', $payroll->id) }}" class="btn btn-sm btn-light-info">
                <i class="fas fa-eye"></i> Detail
            </a>
            <a href="{{ route('payrolls.index') }}" class="btn btn-sm btn-light-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" style="display: none;">
        <div class="overlay-content">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
            <p class="mt-3 fw-bold">Memuat data...</p>
        </div>
    </div>

    <form id="payrollForm">
        @csrf
        @method('PUT')
        
        <!-- Step 1: Informasi Dasar -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-primary me-2">1</span>
                    Informasi Dasar
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-4">
                        <label class="form-label required">Periode</label>
                        <input type="month" class="form-control form-control-solid" id="periode" name="periode" 
                               value="{{ $payroll->periode }}" required>
                        <div class="form-text">Format: YYYY-MM (contoh: 2025-01)</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label required">Karyawan</label>
                        <select class="form-select form-select-solid" id="karyawan_id" name="karyawan_id" required data-control="select2" data-placeholder="Pilih Karyawan">
                            @if(isset($karyawan))
                                <option value="{{ $karyawan->absen_karyawan_id }}" selected>
                                    {{ $karyawan->nik ?? '-' }} - {{ $karyawan->nama_lengkap ?? 'Unknown' }}
                                </option>
                            @else
                                <option value="{{ $payroll->karyawan_id }}" selected>
                                    ID: {{ $payroll->karyawan_id }}
                                </option>
                            @endif
                            @foreach($karyawans as $k)
                                @if(!isset($karyawan) || $k['id'] != $payroll->karyawan_id)
                                <option value="{{ $k['id'] }}">
                                    {{ $k['nik'] }} - {{ $k['nama_lengkap'] }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <select class="form-select form-select-solid" id="company_id" name="company_id" data-control="select2" data-placeholder="Pilih Company (Opsional)">
                            <option value="">-- Pilih Company (Opsional) --</option>
                            @if(isset($company))
                                <option value="{{ $company->absen_company_id }}" selected>
                                    {{ $company->company_name }} ({{ $company->code ?? '-' }})
                                </option>
                            @endif
                            @foreach($companies as $c)
                                @if(!isset($company) || $c['id'] != $payroll->company_id)
                                <option value="{{ $c['id'] }}">
                                    {{ $c['company_name'] }} ({{ $c['code'] }})
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Gaji Pokok & Monthly Insentif -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-success me-2">2</span>
                    Gaji Pokok & Monthly Insentif
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <!-- Gaji Pokok -->
                    <div class="col-12">
                        <div class="alert alert-primary d-flex align-items-center p-5 mb-5">
                            <i class="fas fa-money-bill-wave fs-2x text-primary me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1">Gaji Pokok</h5>
                                <span class="text-muted">Gaji dasar karyawan sebelum insentif dan tunjangan</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Gaji Pokok</label>
                        <input type="number" class="form-control form-control-solid" id="gaji_pokok" name="gaji_pokok" 
                                value="{{ $payroll->gaji_pokok ?? 0 }}">
                    </div>
                    
                    <!-- Monthly Insentif -->
                    <div class="col-12 mt-8">
                        <div class="separator separator-content my-5">
                            <span class="w-250px text-gray-500 fw-bold fs-5">Monthly Insentif</span>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Monthly KPI</label>
                        <input type="number" class="form-control form-control-solid" id="monthly_kpi" name="monthly_kpi" 
                                value="{{ $payroll->monthly_kpi ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Overtime</label>
                        <input type="number" class="form-control form-control-solid" id="overtime" name="overtime" 
                                value="{{ $payroll->overtime ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Medical Reimbursement</label>
                        <input type="number" class="form-control form-control-solid" id="medical_reimbursement" name="medical_reimbursement" 
                                value="{{ $payroll->medical_reimbursement ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Insentif Sholat</label>
                        <input type="number" class="form-control form-control-solid" id="insentif_sholat" name="insentif_sholat" 
                                value="{{ $payroll->insentif_sholat ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Monthly Bonus</label>
                        <input type="number" class="form-control form-control-solid" id="monthly_bonus" name="monthly_bonus" 
                                value="{{ $payroll->monthly_bonus ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Rapel</label>
                        <input type="number" class="form-control form-control-solid" id="rapel" name="rapel" 
                                value="{{ $payroll->rapel ?? 0 }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Monthly Allowance -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-info me-2">3</span>
                    Monthly Allowance
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-3">
                        <label class="form-label">Tunjangan Pulsa</label>
                        <input type="number" class="form-control form-control-solid" id="tunjangan_pulsa" name="tunjangan_pulsa" 
                                value="{{ $payroll->tunjangan_pulsa ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tunjangan Kehadiran</label>
                        <input type="number" class="form-control form-control-solid" id="tunjangan_kehadiran" name="tunjangan_kehadiran" 
                                value="{{ $payroll->tunjangan_kehadiran ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tunjangan Transport</label>
                        <input type="number" class="form-control form-control-solid" id="tunjangan_transport" name="tunjangan_transport" 
                                value="{{ $payroll->tunjangan_transport ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tunjangan Lainnya</label>
                        <input type="number" class="form-control form-control-solid" id="tunjangan_lainnya" name="tunjangan_lainnya" 
                                value="{{ $payroll->tunjangan_lainnya ?? 0 }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Yearly Benefit -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-warning me-2">4</span>
                    Yearly Benefit
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-4">
                        <label class="form-label">Yearly Bonus</label>
                        <input type="number" class="form-control form-control-solid" id="yearly_bonus" name="yearly_bonus" 
                                value="{{ $payroll->yearly_bonus ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">THR</label>
                        <input type="number" class="form-control form-control-solid" id="thr" name="thr" 
                                value="{{ $payroll->thr ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Other</label>
                        <input type="number" class="form-control form-control-solid" id="other" name="other" 
                                value="{{ $payroll->other ?? 0 }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Potongan -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-danger me-2">5</span>
                    Potongan
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-3">
                        <label class="form-label">CA Corporate</label>
                        <input type="number" class="form-control form-control-solid" id="ca_corporate" name="ca_corporate" 
                                value="{{ $payroll->ca_corporate ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">CA Personal</label>
                        <input type="number" class="form-control form-control-solid" id="ca_personal" name="ca_personal" 
                                value="{{ $payroll->ca_personal ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">CA Kehadiran</label>
                        <input type="number" class="form-control form-control-solid" id="ca_kehadiran" name="ca_kehadiran" 
                                value="{{ $payroll->ca_kehadiran ?? 0 }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">PPh 21</label>
                        <input type="number" class="form-control form-control-solid" id="pph_21" name="pph_21" 
                                value="{{ $payroll->pph_21 ?? 0 }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 6: BPJS -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-dark me-2">6</span>
                    BPJS & Pajak
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <!-- BPJS Utama -->
                    <div class="col-12">
                        <h5 class="text-gray-700 mb-4">BPJS Utama</h5>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS Tenaga Kerja</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tenaga_kerja" name="bpjs_tenaga_kerja" 
                                value="{{ $payroll->bpjs_tenaga_kerja ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS Kesehatan</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_kesehatan" name="bpjs_kesehatan" 
                                value="{{ $payroll->bpjs_kesehatan ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">PPh 21 Deduction</label>
                        <input type="number" class="form-control form-control-solid" id="pph_21_deduction" name="pph_21_deduction" 
                                value="{{ $payroll->pph_21_deduction ?? 0 }}">
                    </div>
                    
                    <!-- BPJS TK Detail -->
                    <div class="col-12 mt-8">
                        <div class="separator separator-content my-5">
                            <span class="w-250px text-gray-500 fw-bold fs-6">BPJS Tenaga Kerja (Detail)</span>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JHT 3.7%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jht_3_7_percent" name="bpjs_tk_jht_3_7_percent" 
                                value="{{ $payroll->bpjs_tk_jht_3_7_percent ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JHT 2%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jht_2_percent" name="bpjs_tk_jht_2_percent" 
                                value="{{ $payroll->bpjs_tk_jht_2_percent ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JKK 0.24%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jkk_0_24_percent" name="bpjs_tk_jkk_0_24_percent" 
                                value="{{ $payroll->bpjs_tk_jkk_0_24_percent ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JKM 0.3%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jkm_0_3_percent" name="bpjs_tk_jkm_0_3_percent" 
                                value="{{ $payroll->bpjs_tk_jkm_0_3_percent ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JP 2%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jp_2_percent" name="bpjs_tk_jp_2_percent" 
                                value="{{ $payroll->bpjs_tk_jp_2_percent ?? 0 }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JP 1%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jp_1_percent" name="bpjs_tk_jp_1_percent" 
                                value="{{ $payroll->bpjs_tk_jp_1_percent ?? 0 }}">
                    </div>
                    
                    <!-- BPJS Kesehatan Detail -->
                    <div class="col-12 mt-8">
                        <div class="separator separator-content my-5">
                            <span class="w-250px text-gray-500 fw-bold fs-6">BPJS Kesehatan (Detail)</span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">BPJS Kes - 4%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_kes_4_percent" name="bpjs_kes_4_percent" 
                                value="{{ $payroll->bpjs_kes_4_percent ?? 0 }}">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">BPJS Kes - 1%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_kes_1_percent" name="bpjs_kes_1_percent" 
                                value="{{ $payroll->bpjs_kes_1_percent ?? 0 }}">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 6: BPJS -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-dark me-2">7</span>
                    Lainnya
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-4">
                        <label class="form-label">GLH</label>
                        <input type="number" class="form-control form-control-solid" id="glh" name="glh" 
                                value="{{ $payroll->glh ?? 0 }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">LM</label>
                        <input type="number" class="form-control form-control-solid" id="lm" name="lm" 
                                value="{{ $payroll->lm ?? 0 }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lainnya</label>
                        <input type="number" class="form-control form-control-solid" id="lainnya" name="lainnya" 
                                value="{{ $payroll->lainnya ?? 0 }}">
                    </div>
                    
                </div>
            </div>
        </div>

        <!-- Step 8: Additional Settings -->
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="badge badge-circle badge-secondary me-2">8</span>
                    Pengaturan Tambahan
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label">Salary Type</label>
                        <select class="form-select form-select-solid" id="salary_type" name="salary_type">
                            <option value="">-- Pilih Tipe --</option>
                            <option value="gross" {{ ($payroll->salary_type ?? '') == 'gross' ? 'selected' : '' }}>Gross</option>
                            <option value="nett" {{ ($payroll->salary_type ?? '') == 'nett' ? 'selected' : '' }}>Nett</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Status Release</label>
                        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                            <input class="form-check-input" type="checkbox" id="is_released" name="is_released" value="1" 
                                   {{ ($payroll->is_released ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_released">
                                Payroll Sudah Di-release
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body d-flex justify-content-between">
                <div class="d-flex gap-2">
                    <a href="{{ route('payrolls.index') }}" class="btn btn-light btn-active-light-primary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <a href="{{ route('payrolls.show', $payroll->id) }}" class="btn btn-light-info">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                </div>
                <button type="submit" class="btn btn-warning" id="btnSubmit">
                    <i class="fas fa-save"></i> Update Payroll
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    console.log('Initializing edit payroll form...');
    
    // Initialize Select2
    $('#karyawan_id').select2({
        placeholder: 'Pilih Karyawan',
        allowClear: true,
        width: '100%'
    });
    
    $('#company_id').select2({
        placeholder: 'Pilih Company (Opsional)',
        allowClear: true,
        width: '100%'
    });
    
    // Form submission
    $('#payrollForm').submit(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin mengupdate payroll ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Update',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                submitForm();
            }
        });
    });
});

function submitForm() {
    $('#btnSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Mengupdate...');
    $('#loadingOverlay').show();
    
    let formData = {};
    $('#payrollForm').serializeArray().forEach(function(item) {
        if (item.name !== '_token' && item.name !== '_method') {
            if (item.name === 'is_released') {
                formData[item.name] = true;
            } else if (item.value === '' || item.value === null) {
                formData[item.name] = null;
            } else {
                formData[item.name] = item.value;
            }
        }
    });
    
    // Jika checkbox is_released tidak dicentang, set false
    if (!formData.is_released) {
        formData.is_released = false;
    }
    
    console.log('Submitting update:', formData);
    
    $.ajax({
        url: '{{ route("payrolls.update", $payroll->id) }}',
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(formData),
        timeout: 15000,
        success: function(response) {
            console.log('Update success:', response);
            $('#loadingOverlay').hide();
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Payroll berhasil diupdate',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = '{{ route("payrolls.show", $payroll->id) }}';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: response.message
                });
                resetSubmitButton();
            }
        },
        error: function(xhr, status, error) {
            console.error('Update error:', xhr, status, error);
            $('#loadingOverlay').hide();
            
            let errorMsg = 'Error mengupdate payroll';
            
            if (status === 'timeout') {
                errorMsg = 'Request timeout. Silakan coba lagi.';
            } else if (xhr.responseJSON?.message) {
                errorMsg = xhr.responseJSON.message;
                
                if (xhr.responseJSON?.errors) {
                    let errors = '<ul class="text-start">';
                    Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                        errors += '<li>' + xhr.responseJSON.errors[key][0] + '</li>';
                    });
                    errors += '</ul>';
                    errorMsg = errors;
                }
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                html: errorMsg
            });
            
            resetSubmitButton();
        }
    });
}

function resetSubmitButton() {
    $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Update Payroll');
}
</script>
@endpush

@push('styles')
<style>
/* Badge Circle - Custom untuk step indicator */
.badge-circle {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

/* Separator dengan content di tengah */
.separator.separator-content {
    display: flex;
    align-items: center;
}

.separator.separator-content::before,
.separator.separator-content::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e4e6ef;
}

.separator.separator-content span {
    padding: 0 1rem;
}

/* Remove number input spinner arrows */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}

/* Required field indicator */
.form-label.required::after {
    content: '*';
    color: #f1416c;
    margin-left: 0.25rem;
}

/* Loading Overlay */
#loadingOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.overlay-content {
    background: white;
    padding: 2rem 3rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}
</style>
@endpush
@endsection