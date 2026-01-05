@extends('layouts.master')

@section('title', 'Tambah Payroll')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap flex-stack mb-6">
        <h1 class="fw-bold my-2">
            <i class="fas fa-plus-circle text-primary"></i> Tambah Payroll Baru
        </h1>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('payrolls.index') }}" class="btn btn-sm btn-light-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <form id="payrollForm">
        @csrf
        
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
                        <input type="month" class="form-control form-control-solid" id="periode" name="periode" required>
                        <div class="form-text">Format: YYYY-MM (contoh: 2025-01)</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label required">Karyawan</label>
                        <select class="form-select form-select-solid" id="karyawan_id" name="karyawan_id" required data-control="select2" data-placeholder="Pilih Karyawan">
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($karyawans as $k)
                                <option value="{{ $k['id'] }}" {{ isset($karyawan) && $karyawan->absen_karyawan_id == $k['id'] ? 'selected' : '' }}>
                                    {{ $k['nik'] }} - {{ $k['nama_lengkap'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <select class="form-select form-select-solid" id="company_id" name="company_id" data-control="select2" data-placeholder="Pilih Company (Opsional)">
                            <option value="">-- Pilih Company (Opsional) --</option>
                            @foreach($companies as $c)
                                <option value="{{ $c['id'] }}">
                                    {{ $c['company_name'] }} ({{ $c['code'] }})
                                </option>
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
                        <input type="number" class="form-control form-control-solid" id="gaji_pokok" name="gaji_pokok"  value="0">
                    </div>
                    
                    <!-- Monthly Insentif -->
                    <div class="col-12 mt-8">
                        <div class="separator separator-content my-5">
                            <span class="w-250px text-gray-500 fw-bold fs-5">Monthly Insentif</span>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Monthly KPI</label>
                        <input type="number" class="form-control form-control-solid" id="monthly_kpi" name="monthly_kpi"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Overtime</label>
                        <input type="number" class="form-control form-control-solid" id="overtime" name="overtime"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Medical Reimbursement</label>
                        <input type="number" class="form-control form-control-solid" id="medical_reimbursement" name="medical_reimbursement"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Insentif Sholat</label>
                        <input type="number" class="form-control form-control-solid" id="insentif_sholat" name="insentif_sholat"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Monthly Bonus</label>
                        <input type="number" class="form-control form-control-solid" id="monthly_bonus" name="monthly_bonus"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Rapel</label>
                        <input type="number" class="form-control form-control-solid" id="rapel" name="rapel"  value="0">
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
                        <input type="number" class="form-control form-control-solid" id="tunjangan_pulsa" name="tunjangan_pulsa"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tunjangan Kehadiran</label>
                        <input type="number" class="form-control form-control-solid" id="tunjangan_kehadiran" name="tunjangan_kehadiran"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tunjangan Transport</label>
                        <input type="number" class="form-control form-control-solid" id="tunjangan_transport" name="tunjangan_transport"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tunjangan Lainnya</label>
                        <input type="number" class="form-control form-control-solid" id="tunjangan_lainnya" name="tunjangan_lainnya"  value="0">
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
                        <input type="number" class="form-control form-control-solid" id="yearly_bonus" name="yearly_bonus"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">THR</label>
                        <input type="number" class="form-control form-control-solid" id="thr" name="thr"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Other</label>
                        <input type="number" class="form-control form-control-solid" id="other" name="other"  value="0">
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
                        <input type="number" class="form-control form-control-solid" id="ca_corporate" name="ca_corporate"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">CA Personal</label>
                        <input type="number" class="form-control form-control-solid" id="ca_personal" name="ca_personal"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">CA Kehadiran</label>
                        <input type="number" class="form-control form-control-solid" id="ca_kehadiran" name="ca_kehadiran"  value="0">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">PPh 21</label>
                        <input type="number" class="form-control form-control-solid" id="pph_21" name="pph_21"  value="0">
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
                        <input type="number" class="form-control form-control-solid" id="bpjs_tenaga_kerja" name="bpjs_tenaga_kerja"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS Kesehatan</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_kesehatan" name="bpjs_kesehatan"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">PPh 21 Deduction</label>
                        <input type="number" class="form-control form-control-solid" id="pph_21_deduction" name="pph_21_deduction"  value="0">
                    </div>
                    
                    <!-- BPJS TK Detail -->
                    <div class="col-12 mt-8">
                        <div class="separator separator-content my-5">
                            <span class="w-250px text-gray-500 fw-bold fs-6">BPJS Tenaga Kerja (Detail)</span>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JHT 3.7%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jht_3_7_percent" name="bpjs_tk_jht_3_7_percent"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JHT 2%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jht_2_percent" name="bpjs_tk_jht_2_percent"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JKK 0.24%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jkk_0_24_percent" name="bpjs_tk_jkk_0_24_percent"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JKM 0.3%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jkm_0_3_percent" name="bpjs_tk_jkm_0_3_percent"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JP 2%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jp_2_percent" name="bpjs_tk_jp_2_percent"  value="0">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">BPJS TK - JP 1%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_tk_jp_1_percent" name="bpjs_tk_jp_1_percent"  value="0">
                    </div>
                    
                    <!-- BPJS Kesehatan Detail -->
                    <div class="col-12 mt-8">
                        <div class="separator separator-content my-5">
                            <span class="w-250px text-gray-500 fw-bold fs-6">BPJS Kesehatan (Detail)</span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">BPJS Kes - 4%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_kes_4_percent" name="bpjs_kes_4_percent"  value="0">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">BPJS Kes - 1%</label>
                        <input type="number" class="form-control form-control-solid" id="bpjs_kes_1_percent" name="bpjs_kes_1_percent"  value="0">
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
                    <!-- Lainnya -->
                    <div class="col-md-4">
                        <label class="form-label">GLH</label>
                        <input type="number" class="form-control form-control-solid" id="glh" name="glh"  value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">LM</label>
                        <input type="number" class="form-control form-control-solid" id="lm" name="lm"  value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lainnya</label>
                        <input type="number" class="form-control form-control-solid" id="lainnya" name="lainnya"  value="0">
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
                            <option value="gross">Gross</option>
                            <option value="nett">Nett</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Status Release</label>
                        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                            <input class="form-check-input" type="checkbox" id="is_released" name="is_released" value="1">
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
                <a href="{{ route('payrolls.index') }}" class="btn btn-light btn-active-light-primary">
                    <i class="fas fa-times"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="fas fa-save"></i> Simpan Payroll
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
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
        
        if (!confirm('Apakah Anda yakin ingin menyimpan payroll ini?')) {
            return;
        }
        
        $('#btnSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
        
        // ✅ Collect form data
        let formData = {};
        $(this).serializeArray().forEach(function(item) {
            if (item.name !== '_token') {
                if (item.name === 'is_released') {
                    formData[item.name] = true;
                } else if (item.name === 'periode') {
                    // Convert YYYY-MM to proper format
                    formData[item.name] = item.value;
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
        
        console.log('Sending data:', formData);
        
        $.ajax({
            url: '{{ route("payrolls.store") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Payroll berhasil disimpan',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '{{ route("payrolls.index") }}';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message
                    });
                    $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Payroll');
                }
            },
            error: function(xhr) {
                console.error('Error response:', xhr);
                
                let errorMsg = 'Error menyimpan payroll';
                if (xhr.responseJSON?.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                if (xhr.responseJSON?.errors) {
                    let errors = '<ul class="text-start">';
                    Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                        errors += '<li>' + xhr.responseJSON.errors[key][0] + '</li>';
                    });
                    errors += '</ul>';
                    errorMsg = errors;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    html: errorMsg
                });
                
                $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Payroll');
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.badge-circle {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.form-control-solid, .form-select-solid {
    background-color: #f5f8fa;
    border-color: #f5f8fa;
}

.form-control-solid:focus, .form-select-solid:focus {
    background-color: #eef3f7;
    border-color: #009ef7;
}

.card {
    box-shadow: 0 0 20px 0 rgba(76, 87, 125, 0.02);
    border: 1px solid #eff2f5;
}

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

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}

.form-label.required::after {
    content: '*';
    color: #f1416c;
    margin-left: 0.25rem;
}
</style>
@endpush
@endsection