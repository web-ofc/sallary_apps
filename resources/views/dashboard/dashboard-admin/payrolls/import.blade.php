@extends('layouts.master')

@section('title', 'Import Payroll')

@section('content')
<div class="d-flex flex-column flex-root">
    <div class="page d-flex flex-row flex-column-fluid">
        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            
            <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                <!-- Toolbar -->
                <div class="toolbar" id="kt_toolbar">
                    <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
                        <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
                            <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Import Payroll
                                <span class="h-20px border-gray-300 border-start ms-3 mx-2"></span>
                                <small class="text-muted fs-7 fw-semibold my-1 ms-1">Upload Excel untuk import data payroll</small>
                            </h1>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            <a href="{{ route('payrolls.index') }}" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Post -->
                <div class="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" class="container-fluid">
                        
                        <!-- Upload Card -->
                        <div class="card mb-5" id="uploadCard">
                            <div class="card-header">
                                <h3 class="card-title">Upload File Excel</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <form id="uploadForm" enctype="multipart/form-data">
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">Pilih File Excel</label>
                                                <input type="file" id="excelFile" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                                <div class="form-text">Format: .xlsx, .xls, .csv (Max 10MB)</div>
                                            </div>
                                            
                                            <div class="d-flex gap-3">
                                                <button type="submit" class="btn btn-primary" id="btnValidate">
                                                    <i class="fas fa-check-circle"></i> Validasi File
                                                </button>
                                                <button type="button" class="btn btn-light-success" onclick="downloadTemplate()">
                                                    <i class="fas fa-download"></i> Download Template
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="border border-gray-300 p-4 rounded">
                                            <h5 class="fw-bold mb-3">Format Excel</h5>
                                            <ul class="mb-0" style="font-size: 0.9rem;">
                                                <li>periode: YYYY-MM (Required)</li>
                                                <li>karyawan_id: ID dari sheet Karyawans (Required)</li>
                                                <li>company_id: ID dari sheet Companies (Optional)</li>
                                                <li>Field numerik: angka positif</li>
                                                <li>salary_type: gross/nett</li>
                                            </ul>
                                            <div class="alert alert-warning mt-3 mb-0" style="font-size: 0.85rem;">
                                                Template memiliki 3 sheets - gunakan sheet "Karyawans" dan "Companies" untuk referensi ID
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Validation Results Card -->
                        <div class="card" id="resultsCard" style="display: none;">
                            <div class="card-header">
                                <h3 class="card-title">Hasil Validasi</h3>
                            </div>
                            <div class="card-body">
                                <!-- Summary -->
                                <div class="row mb-5" id="summarySection">
                                    <div class="col-md-4">
                                        <div class="card border border-primary">
                                            <div class="card-body text-center">
                                                <div class="fs-2x fw-bold text-primary" id="totalRows">0</div>
                                                <div class="text-gray-600 mt-1">Total Baris</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border border-success clickable" onclick="showValidData()" style="cursor: pointer;">
                                            <div class="card-body text-center">
                                                <div class="fs-2x fw-bold text-success" id="validRows">0</div>
                                                <div class="text-gray-600 mt-1">Baris Valid</div>
                                                <small class="text-muted">Klik untuk lihat detail</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border border-danger clickable" onclick="showErrorData()" style="cursor: pointer;">
                                            <div class="card-body text-center">
                                                <div class="fs-2x fw-bold text-danger" id="errorRows">0</div>
                                                <div class="text-gray-600 mt-1">Baris Error</div>
                                                <small class="text-muted">Klik untuk lihat detail</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Error Section -->
                                <div id="errorSection" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="mb-0">Data dengan Error</h4>
                                        <button type="button" class="btn btn-sm btn-light-danger" onclick="downloadErrors()">
                                            <i class="fas fa-download"></i> Download Error Report
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm table-hover" id="errorTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 80px;">Baris</th>
                                                    <th style="width: 120px;">Periode</th>
                                                    <th style="width: 120px;">Karyawan ID</th>
                                                    <th style="width: 120px;">Company ID</th>
                                                    <th>Error Messages</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Success Section -->
                                <div id="successSection" style="display: none;">
                                    <h4 class="mb-3">Data Valid</h4>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm table-hover nowrap" id="validTable" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Periode</th>
                                                    <th>Karyawan</th>
                                                    <th>Company</th>
                                                    <th>Salary Type</th>
                                                    <th>Gaji Pokok</th>
                                                    <th>Monthly KPI</th>
                                                    <th>Overtime</th>
                                                    <th>Medical Reimb</th>
                                                    <th>Insentif Sholat</th>
                                                    <th>Monthly Bonus</th>
                                                    <th>Rapel</th>
                                                    <th>Tunj Pulsa</th>
                                                    <th>Tunj Kehadiran</th>
                                                    <th>Tunj Transport</th>
                                                    <th>Tunj Lainnya</th>
                                                    <th>Yearly Bonus</th>
                                                    <th>THR</th>
                                                    <th>Other</th>
                                                    <th>CA Corporate</th>
                                                    <th>CA Personal</th>
                                                    <th>CA Kehadiran</th>
                                                    <th>BPJS TK</th>
                                                    <th>BPJS Kesehatan</th>
                                                    <th>PPh 21 Deduction</th>
                                                    <th>BPJS TK JHT 3.7%</th>
                                                    <th>BPJS TK JHT 2%</th>
                                                    <th>BPJS TK JKK 0.24%</th>
                                                    <th>BPJS TK JKM 0.3%</th>
                                                    <th>BPJS TK JP 2%</th>
                                                    <th>BPJS TK JP 1%</th>
                                                    <th>BPJS Kes 4%</th>
                                                    <th>BPJS Kes 1%</th>
                                                    <th class="bg-light">PPh 21</th>
                                                    <th>GLH</th>
                                                    <th>LM</th>
                                                    <th>Lainnya</th>
                                                    <th>Tunjangan</th>
                                                    <th class="bg-light">Total Penerimaan</th>
                                                    <th class="bg-light">Total Potongan</th>
                                                    <th class="bg-light">Gaji Bersih</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-flex gap-3 mt-5">
                                    <!-- Tombol Hitung PPh21 Bulanan (existing) -->
                                    <button type="button" class="btn btn-warning" id="btnCalculatePph21" style="display: none;" onclick="calculatePph21()">
                                        <i class="fas fa-calculator"></i> Hitung PPh21 Bulanan (<span id="validCountCalc">0</span> data)
                                    </button>
                                    
                                    <!-- Tombol Import Biasa (existing) -->
                                    <button type="button" class="btn btn-success" id="btnImport" style="display: none;" onclick="processImport()">
                                        <i class="fas fa-check"></i> Import ke Database (<span id="validCount">0</span> baris)
                                    </button>
                                    
                                    <!-- 🆕 TOMBOL BARU: Import untuk PPh21 Tahunan -->
                                    <button type="button" class="btn btn-info" id="btnImportAnnual" style="display: none;" onclick="processImportAnnual()">
                                        <i class="fas fa-calendar-check"></i> Import PPh21 Tahunan (<span id="validCountAnnual">0</span> baris)
                                    </button>
                                    
                                    <button type="button" class="btn btn-light" onclick="resetForm()">
                                        <i class="fas fa-redo"></i> Upload File Baru
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Progress PPh21 -->
<div class="modal fade" id="pph21ProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Menghitung PPh21</h5>
            </div>
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="fs-5 mb-2" id="pph21ProgressText">Memproses data...</div>
                <div class="text-muted" id="pph21ProgressDetail">Mohon tunggu</div>
                
                <!-- Progress Bar -->
                <div class="progress mt-4" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         id="pph21ProgressBar" 
                         role="progressbar" 
                         style="width: 0%">
                        <span id="pph21ProgressPercent">0%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let validTable;
let errorTable;
let validationErrors = [];

$(document).ready(function() {
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        validateFile();
    });
});

function validateFile() {
    const fileInput = $('#excelFile')[0];
    
    if (!fileInput.files.length) {
        Swal.fire('Error', 'Pilih file Excel terlebih dahulu', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    
    Swal.fire({
        title: 'Memvalidasi File',
        html: 'Mohon tunggu, sedang memproses file Excel',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '{{ route("payrolls.import.validate") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            Swal.close();
            displayValidationResults(response);
        },
        error: function(xhr) {
            Swal.close();
            const error = xhr.responseJSON?.message || 'Error memvalidasi file';
            Swal.fire('Error', error, 'error');
        }
    });
}

// Function baru untuk import annual
function processImportAnnual() {
    Swal.fire({
        title: 'Konfirmasi Import PPh21 Tahunan',
        html: `
            <div class="text-start">
                <p>Import <strong>${$('#validCountAnnual').text()}</strong> data sebagai <strong>periode akhir tahun</strong>?</p>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Catatan:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Data akan di-set sebagai <code>is_last_period = 1</code></li>
                        <li>Anda akan diarahkan ke halaman perhitungan PPh21 Tahunan</li>
                        <li>PPh21 akan dihitung berdasarkan PKP setahun penuh</li>
                    </ul>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Import untuk Tahunan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        width: '600px'
    }).then((result) => {
        if (result.isConfirmed) {
            executeImportAnnual();
        }
    });
}
function executeImportAnnual() {
    Swal.fire({
        title: 'Mengimport Data',
        html: 'Mohon tunggu, sedang memvalidasi dan menyimpan data...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '{{ route("payrolls.import.process-annual") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Import Berhasil',
                html: `
                    <div class="text-start">
                        <p>${response.imported_count} data berhasil diimport sebagai periode akhir tahun</p>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-arrow-right me-2"></i>
                            Anda akan diarahkan ke halaman perhitungan PPh21 Tahunan
                        </div>
                    </div>
                `,
                confirmButtonText: 'Lanjut ke Perhitungan',
                confirmButtonColor: '#0dcaf0'
            }).then(() => {
                window.location.href = '{{ route("payrolls.calculate-annual.index") }}';
            });
        },
        error: function(xhr) {
            Swal.close();
            
            // ✅ HANDLE VALIDATION ERRORS (422)
            if (xhr.status === 422) {
                const response = xhr.responseJSON;
                
                // ✅ CEK APAKAH ADA KOMBINASI ERROR
                const hasPtkpErrors = response.has_ptkp_errors;
                const hasBracketErrors = response.has_bracket_errors;
                
                if (hasPtkpErrors && hasBracketErrors) {
                    // ✅ TAMPILKAN KEDUA ERROR SEKALIGUS
                    showCombinedValidationErrors(response);
                } else if (hasPtkpErrors) {
                    // ✅ HANYA ERROR PTKP
                    showPtkpValidationErrors(response.ptkp_errors, response.ptkp_summary);
                } else if (hasBracketErrors) {
                    // ✅ HANYA ERROR BRACKET
                    showBracketValidationErrors(response.bracket_errors, response.bracket_summary);
                }
            } else {
                const error = xhr.responseJSON?.message || 'Error saat import data';
                Swal.fire('Error', error, 'error');
            }
        }
    });
}


// ✅ BARU: Function untuk show GABUNGAN PTKP & BRACKET validation errors
function showCombinedValidationErrors(response) {
    const ptkpErrors = response.ptkp_errors || [];
    const bracketErrors = response.bracket_errors || [];
    const ptkpSummary = response.ptkp_summary || {};
    const bracketSummary = response.bracket_summary || {};
    
    let html = `
        <div class="text-start">
            <!-- HEADER -->
            <div class="alert alert-danger mb-4">
                <h5 class="mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Validasi Import Gagal</h5>
                <p class="mb-0">Ditemukan beberapa masalah yang harus diperbaiki sebelum import dapat dilanjutkan:</p>
                <ul class="mb-0 mt-2">
                    ${ptkpErrors.length > 0 ? '<li><strong>Data PTKP tidak lengkap</strong></li>' : ''}
                    ${bracketErrors.length > 0 ? '<li><strong>Bracket PPh21 belum tersedia</strong></li>' : ''}
                </ul>
            </div>
    `;
    
    // ============ SECTION 1: BRACKET ERRORS ============
    if (bracketErrors.length > 0) {
        html += `
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>1. Masalah Bracket PPh21</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        <h6 class="mb-2"><strong>Tahun yang Dicek:</strong></h6>
                        <div class="d-flex flex-wrap gap-2">
                            ${bracketSummary.years_checked.map(year => 
                                `<span class="badge ${bracketSummary.years_missing_bracket.includes(year) ? 'bg-danger' : 'bg-success'}">${year}</span>`
                            ).join('')}
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Tahun</th>
                                    <th class="text-center">Tanggal Dicek</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        bracketErrors.forEach(error => {
            html += `
                <tr>
                    <td class="text-center fw-bold">${error.year}</td>
                    <td class="text-center">${error.date_checked}</td>
                    <td class="text-danger">${error.message}</td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    // ============ SECTION 2: PTKP ERRORS ============
    if (ptkpErrors.length > 0) {
        html += `
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>2. Masalah Data PTKP Karyawan</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <tr class="table-light">
                                <td class="fw-bold">Total Data Import</td>
                                <td class="text-end">${ptkpSummary.total}</td>
                            </tr>
                            <tr class="table-success">
                                <td class="fw-bold">✓ Valid (Punya PTKP)</td>
                                <td class="text-end">${ptkpSummary.valid}</td>
                            </tr>
                            <tr class="table-danger">
                                <td class="fw-bold">✗ Invalid (Tidak Ada PTKP)</td>
                                <td class="text-end">${ptkpSummary.invalid_ptkp}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <h6 class="fw-bold mb-2">Detail Karyawan yang Bermasalah:</h6>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Baris</th>
                                    <th>Karyawan</th>
                                    <th>Periode</th>
                                    <th>Tahun</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        ptkpErrors.forEach(error => {
            html += `
                <tr>
                    <td class="text-center">${error.row_index}</td>
                    <td>
                        ${error.karyawan_nama}<br>
                        <small class="text-muted">ID: ${error.karyawan_id}</small>
                    </td>
                    <td class="text-center">${error.periode}</td>
                    <td class="text-center">${error.year}</td>
                    <td class="text-danger small">${error.message}</td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    // ============ FOOTER: SOLUSI ============
    html += `
            <div class="alert alert-info mb-0">
                <h6 class="mb-2"><i class="fas fa-lightbulb me-2"></i><strong>Langkah Perbaikan:</strong></h6>
                <ol class="mb-0">
    `;
    
    if (bracketErrors.length > 0) {
        html += `
            <li>
                <strong>Tambahkan Bracket PPh21:</strong>
                <ul>
                    <li>Pastikan bracket PPh21 sudah diinput untuk tahun <strong>${bracketSummary.years_missing_bracket.join(', ')}</strong></li>
                    <li>Bracket harus memiliki <code>effective_start_date</code> yang valid</li>
                    <li>Untuk tahun yang masih berlaku, <code>effective_end_date</code> boleh kosong (NULL)</li>
                </ul>
            </li>
        `;
    }
    
    if (ptkpErrors.length > 0) {
        html += `
            <li>
                <strong>Lengkapi Data PTKP:</strong>
                <ul>
                    <li>Pastikan setiap karyawan memiliki data PTKP untuk tahun yang relevan</li>
                    <li>Total <strong>${ptkpSummary.invalid_ptkp}</strong> karyawan memerlukan data PTKP</li>
                </ul>
            </li>
        `;
    }
    
    html += `
                    <li><strong>Ulangi proses import</strong> setelah semua data lengkap</li>
                </ol>
            </div>
        </div>
    `;
    
    Swal.fire({
        icon: 'error',
        title: 'Import Dibatalkan',
        html: html,
        width: '950px',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#f1416c',
        customClass: {
            htmlContainer: 'text-start'
        }
    });
}


// ✅ Function untuk show PTKP validation errors (individual)
function showPtkpValidationErrors(errors, summary) {
    let html = `
        <div class="text-start">
            <div class="alert alert-danger mb-4">
                <h5 class="mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Validasi PTKP Gagal</h5>
                <p class="mb-0">Beberapa karyawan tidak memiliki data PTKP untuk tahun yang diimport. Silakan lengkapi data PTKP terlebih dahulu.</p>
            </div>
            
            <div class="table-responsive mb-4">
                <table class="table table-sm">
                    <tr class="table-light">
                        <td class="fw-bold">Total Data</td>
                        <td class="text-end">${summary.total}</td>
                    </tr>
                    <tr class="table-success">
                        <td class="fw-bold">✓ Valid (Punya PTKP)</td>
                        <td class="text-end">${summary.valid}</td>
                    </tr>
                    <tr class="table-danger">
                        <td class="fw-bold">✗ Invalid (Tidak Ada PTKP)</td>
                        <td class="text-end">${summary.invalid_ptkp}</td>
                    </tr>
                </table>
            </div>
            
            <h6 class="fw-bold mb-2">Detail Data yang Bermasalah:</h6>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-bordered">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Baris</th>
                            <th>Karyawan</th>
                            <th>Periode</th>
                            <th>Tahun</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    errors.forEach(error => {
        html += `
            <tr>
                <td class="text-center">${error.row_index}</td>
                <td>
                    ${error.karyawan_nama}<br>
                    <small class="text-muted">ID: ${error.karyawan_id}</small>
                </td>
                <td class="text-center">${error.periode}</td>
                <td class="text-center">${error.year}</td>
                <td class="text-danger small">${error.message}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Solusi:</strong> Pastikan setiap karyawan memiliki data PTKP untuk tahun yang relevan sebelum melakukan import PPh21 Tahunan.
            </div>
        </div>
    `;
    
    Swal.fire({
        icon: 'error',
        title: 'Import Dibatalkan',
        html: html,
        width: '900px',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#f1416c'
    });
}

function showBracketValidationErrors(errors, summary) {
    let html = `
        <div class="text-start">
            <div class="alert alert-danger mb-4">
                <h5 class="mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Validasi Bracket PPh21 Gagal</h5>
                <p class="mb-0">Bracket PPh21 tidak ditemukan untuk beberapa tahun yang diimport. Silakan tambahkan bracket PPh21 terlebih dahulu.</p>
            </div>
            
            <div class="alert alert-info mb-4">
                <h6 class="mb-2"><strong>Tahun yang Dicek:</strong></h6>
                <div class="d-flex flex-wrap gap-2">
                    ${summary.years_checked.map(year => 
                        `<span class="badge ${summary.years_missing_bracket.includes(year) ? 'bg-danger' : 'bg-success'}">${year}</span>`
                    ).join('')}
                </div>
            </div>
            
            <h6 class="fw-bold mb-2">Detail Tahun yang Bermasalah:</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Tahun</th>
                            <th class="text-center">Tanggal Dicek</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    errors.forEach(error => {
        html += `
            <tr>
                <td class="text-center fw-bold">${error.year}</td>
                <td class="text-center">${error.date_checked}</td>
                <td class="text-danger">${error.message}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-warning mt-3 mb-0">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Solusi:</strong> 
                <ul class="mb-0 mt-2">
                    <li>Pastikan bracket PPh21 sudah diinput untuk tahun ${summary.years_missing_bracket.join(', ')}</li>
                    <li>Bracket harus memiliki <code>effective_start_date</code> yang valid</li>
                    <li>Untuk tahun yang masih berlaku, <code>effective_end_date</code> boleh kosong (NULL)</li>
                </ul>
            </div>
        </div>
    `;
    
    Swal.fire({
        icon: 'error',
        title: 'Import Dibatalkan',
        html: html,
        width: '800px',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#f1416c'
    });
}

function displayValidationResults(response) {
    validationErrors = response.errors || [];
    
    $('#resultsCard').show();
    
    $('#totalRows').text(response.summary.total_rows);
    $('#validRows').text(response.summary.valid_rows);
    $('#errorRows').text(response.summary.error_rows);
    $('#validCount').text(response.summary.valid_rows);
    $('#validCountCalc').text(response.summary.valid_rows);
    $('#validCountAnnual').text(response.summary.valid_rows); // 🆕 SET COUNT ANNUAL
    
    if (response.summary.error_rows > 0) {
        showErrorData();
        
        if (response.summary.valid_rows > 0) {
            $('#btnCalculatePph21').show();
            $('#btnImport').hide();
            $('#btnImportAnnual').show(); // 🆕 SHOW TOMBOL ANNUAL
        } else {
            $('#btnCalculatePph21').hide();
            $('#btnImport').hide();
            $('#btnImportAnnual').hide();
        }
    } else {
        showValidData();
        $('#btnCalculatePph21').show();
        $('#btnImport').hide();
        $('#btnImportAnnual').show(); // 🆕 SHOW TOMBOL ANNUAL
    }
    
    $('html, body').animate({
        scrollTop: $('#resultsCard').offset().top - 100
    }, 500);
}


function calculatePph21() {
    Swal.fire({
        title: 'Konfirmasi',
        html: `Hitung PPh21 untuk ${$('#validCountCalc').text()} data?<br><small class="text-muted">Proses ini akan menghitung PPh21 berdasarkan TER dan PTKP karyawan.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hitung',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009ef7',
        cancelButtonColor: '#f1416c'
    }).then((result) => {
        if (result.isConfirmed) {
            executePph21Calculation();
        }
    });
}

function executePph21Calculation() {
    $('#pph21ProgressModal').modal('show');
    $('#pph21ProgressText').text('Menghitung PPh21...');
    $('#pph21ProgressDetail').text('Mohon tunggu');
    $('#pph21ProgressBar').css('width', '0%');
    $('#pph21ProgressPercent').text('0%');
    
    $.ajax({
        url: '{{ route("payrolls.import.calculate-pph21") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            
            var progress = 0;
            var progressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) {
                    progress = 90;
                    clearInterval(progressInterval);
                }
                updateProgress(progress);
            }, 300);
            
            return xhr;
        },
        success: function(response) {
            updateProgress(100);
            
            setTimeout(function() {
                $('#pph21ProgressModal').modal('hide');
                
                if (response.success) {
                    let alertClass = response.results.failed > 0 ? 'warning' : 'success';
                    let alertIcon = response.results.failed > 0 ? 'warning' : 'success';
                    
                    Swal.fire({
                        icon: alertIcon,
                        title: 'Perhitungan Selesai',
                        html: `
                            <div class="text-start">
                                <table class="table table-sm">
                                    <tr>
                                        <td>Berhasil</td>
                                        <td class="text-end">${response.results.success} data</td>
                                    </tr>
                                    <tr>
                                        <td>Gagal</td>
                                        <td class="text-end">${response.results.failed} data</td>
                                    </tr>
                                    <tr class="fw-bold border-top">
                                        <td>Total</td>
                                        <td class="text-end">${response.results.total} data</td>
                                    </tr>
                                </table>
                                ${response.results.failed > 0 ? 
                                    '<div class="alert alert-warning mt-2 mb-0"><small>Beberapa data gagal dihitung. Periksa detail error.</small></div>' 
                                    : ''}
                            </div>
                        `,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (validTable) {
                            validTable.ajax.reload();
                        }
                        
                        $('#btnImport').show();
                        $('#btnCalculatePph21').hide();
                        
                        if (response.results.failed > 0) {
                            showCalculationErrors(response.results.details);
                        }
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }, 500);
        },
        error: function(xhr) {
            $('#pph21ProgressModal').modal('hide');
            const error = xhr.responseJSON?.message || 'Error menghitung PPh21';
            Swal.fire('Error', error, 'error');
        }
    });
}

function showCalculationErrors(details) {
    const failedDetails = details.filter(d => d.status === 'failed');
    
    if (failedDetails.length === 0) return;
    
    let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
    html += '<thead><tr><th>Periode</th><th>Karyawan ID</th><th>Error</th></tr></thead><tbody>';
    
    failedDetails.forEach(detail => {
        html += `<tr>
            <td>${detail.periode}</td>
            <td>${detail.karyawan_id}</td>
            <td class="text-danger">${detail.message}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    
    Swal.fire({
        title: 'Detail Error Perhitungan',
        html: html,
        width: '800px',
        confirmButtonText: 'Tutup'
    });
}

function updateProgress(percent) {
    percent = Math.min(100, Math.max(0, percent));
    $('#pph21ProgressBar').css('width', percent + '%');
    $('#pph21ProgressPercent').text(Math.round(percent) + '%');
    
    if (percent < 30) {
        $('#pph21ProgressDetail').text('Memuat data PTKP dan TER');
    } else if (percent < 60) {
        $('#pph21ProgressDetail').text('Menghitung total bruto dasar');
    } else if (percent < 90) {
        $('#pph21ProgressDetail').text('Menerapkan formula gross-up');
    } else {
        $('#pph21ProgressDetail').text('Menyimpan hasil perhitungan');
    }
}

function showValidData() {
    $('#errorSection').hide();
    $('#successSection').show();
    
    if (validTable) {
        validTable.destroy();
    }
    
    validTable = $('#validTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("payrolls.import.datatable.valid") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        },
        columns: [
            { data: 'periode', name: 'periode' },
            { 
                data: null,
                render: function(data) {
                    return `${data.karyawan_nama}<br><small class="text-muted">ID: ${data.absen_karyawan_id} | NIK: ${data.karyawan_nik}</small>`;
                },
                orderable: false
            },
            { 
                data: null,
                render: function(data) {
                    return `${data.company_name || '-'}<br><small class="text-muted">ID: ${data.absen_company_id || '-'} | Code: ${data.company_code || '-'}</small>`;
                },
                orderable: false
            },
            { data: 'salary_type', className: 'text-center' },
            { data: 'gaji_pokok', render: formatRupiah, className: 'text-end' },
            { data: 'monthly_kpi', render: formatRupiah, className: 'text-end' },
            { data: 'overtime', render: formatRupiah, className: 'text-end' },
            { data: 'medical_reimbursement', render: formatRupiah, className: 'text-end' },
            { data: 'insentif_sholat', render: formatRupiah, className: 'text-end' },
            { data: 'monthly_bonus', render: formatRupiah, className: 'text-end' },
            { data: 'rapel', render: formatRupiah, className: 'text-end' },
            { data: 'tunjangan_pulsa', render: formatRupiah, className: 'text-end' },
            { data: 'tunjangan_kehadiran', render: formatRupiah, className: 'text-end' },
            { data: 'tunjangan_transport', render: formatRupiah, className: 'text-end' },
            { data: 'tunjangan_lainnya', render: formatRupiah, className: 'text-end' },
            { data: 'yearly_bonus', render: formatRupiah, className: 'text-end' },
            { data: 'thr', render: formatRupiah, className: 'text-end' },
            { data: 'other', render: formatRupiah, className: 'text-end' },
            { data: 'ca_corporate', render: formatRupiah, className: 'text-end' },
            { data: 'ca_personal', render: formatRupiah, className: 'text-end' },
            { data: 'ca_kehadiran', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_tenaga_kerja', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_kesehatan', render: formatRupiah, className: 'text-end' },
            { data: 'pph_21_deduction', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_tk_jht_3_7_percent', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_tk_jht_2_percent', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_tk_jkk_0_24_percent', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_tk_jkm_0_3_percent', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_tk_jp_2_percent', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_tk_jp_1_percent', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_kes_4_percent', render: formatRupiah, className: 'text-end' },
            { data: 'bpjs_kes_1_percent', render: formatRupiah, className: 'text-end' },
            { 
                data: 'pph_21', 
                render: function(data, type, row) {
                    // Cek apakah belum dihitung sama sekali (null/undefined)
                    if (data === null || data === undefined) {
                        return '<span class="badge badge-light-secondary">Belum Dihitung</span>';
                    }
                    
                    // Jika sudah dihitung (bisa 0 atau lebih)
                    if (data === 0) {
                        return '<span class="badge badge-light-success">Rp 0</span>';
                    }
                    
                    return formatRupiah(data);
                }, 
                className: 'text-end bg-light' 
            },
            { data: 'glh', render: formatRupiah, className: 'text-end' },
            { data: 'lm', render: formatRupiah, className: 'text-end' },
            { data: 'lainnya', render: formatRupiah, className: 'text-end' },
            { data: 'tunjangan', render: formatRupiah, className: 'text-end' },
            { data: 'total_penerimaan', render: formatRupiah, className: 'text-end bg-light fw-bold' },
            { data: 'total_potongan', render: formatRupiah, className: 'text-end bg-light fw-bold' },
            { data: 'gaji_bersih', render: formatRupiah, className: 'text-end bg-light fw-bold' },
        ],
        scrollX: true,
        scrollCollapse: true,
        fixedColumns: {
            leftColumns: 3
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'asc']],
        language: {
            processing: 'Loading...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            search: 'Search:',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        }
    });
    
    $('html, body').animate({
        scrollTop: $('#successSection').offset().top - 100
    }, 500);
}

function showErrorData() {
    if (validationErrors.length === 0) {
        Swal.fire('Info', 'Tidak ada error ditemukan', 'info');
        return;
    }
    
    $('#successSection').hide();
    $('#errorSection').show();
    
    if (errorTable) {
        errorTable.destroy();
    }
    
    errorTable = $('#errorTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("payrolls.import.datatable.errors") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        },
        columns: [
            { data: 'row', className: 'text-center fw-bold' },
            { 
                data: null,
                render: function(data) {
                    return data.data.periode || '-';
                },
                orderable: false
            },
            { 
                data: null,
                render: function(data) {
                    return data.data.karyawan_id || '-';
                },
                orderable: false
            },
            { 
                data: null,
                render: function(data) {
                    return data.data.company_id || '-';
                },
                orderable: false
            },
            { 
                data: null,
                render: function(data) {
                    let html = '<ul class="mb-0" style="font-size: 0.85rem;">';
                    data.errors.forEach(error => {
                        html += `<li class="text-danger">${error}</li>`;
                    });
                    html += '</ul>';
                    return html;
                },
                orderable: false
            }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'asc']],
        language: {
            processing: 'Loading...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            search: 'Search:',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        }
    });
    
    $('html, body').animate({
        scrollTop: $('#errorSection').offset().top - 100
    }, 500);
}

function processImport() {
    Swal.fire({
        title: 'Konfirmasi Import',
        html: `Import ${$('#validCount').text()} data payroll ke database?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Import',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009ef7',
        cancelButtonColor: '#f1416c'
    }).then((result) => {
        if (result.isConfirmed) {
            executeImport();
        }
    });
}

function executeImport() {
    Swal.fire({
        title: 'Mengimport Data',
        html: 'Mohon tunggu, sedang menyimpan data ke database',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '{{ route("payrolls.import.process") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Import Berhasil',
                html: `${response.imported_count} data payroll berhasil diimport`,
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '{{ route("payrolls.index") }}';
            });
        },
        error: function(xhr) {
            Swal.close();
            const error = xhr.responseJSON?.message || 'Error saat import data';
            Swal.fire('Error', error, 'error');
        }
    });
}

function downloadTemplate() {
    window.location.href = '{{ route("payrolls.import.template") }}';
}

function downloadErrors() {
    if (validationErrors.length === 0) {
        Swal.fire('Info', 'Tidak ada error untuk didownload', 'info');
        return;
    }
    
    $.ajax({
        url: '{{ route("payrolls.import.download-errors") }}',
        method: 'POST',
        data: {
            errors: validationErrors
        },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(blob) {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'payroll_import_errors_' + new Date().getTime() + '.xlsx';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        },
        error: function() {
            Swal.fire('Error', 'Gagal download error report', 'error');
        }
    });
}

function resetForm() {
    $('#uploadForm')[0].reset();
    $('#resultsCard').hide();
    $('#btnCalculatePph21').hide();
    $('#btnImport').hide();
    $('#btnImportAnnual').hide(); // 🆕 HIDE TOMBOL ANNUAL
    validationErrors = [];
    
    if (validTable) {
        validTable.destroy();
        validTable = null;
    }
    
    if (errorTable) {
        errorTable.destroy();
        errorTable = null;
    }
    
    $('html, body').animate({
        scrollTop: $('#uploadCard').offset().top - 100
    }, 500);
}

function formatRupiah(data) {
    if (data === 0 || data === null || data === undefined) return '-';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(data);
}
</script>
@endpush

@push('styles')
<style>
.card-summary.clickable {
    transition: transform 0.2s ease;
}

.card-summary.clickable:hover {
    transform: translateY(-2px);
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 1rem;
}

.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    padding-top: 1rem;
}

.DTFC_LeftBodyLiner {
    background-color: white !important;
}

.dataTables_scrollBody::-webkit-scrollbar {
    height: 8px;
}

.dataTables_scrollBody::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
    background: #555;
}

#validTable tbody tr:hover,
#errorTable tbody tr:hover {
    background-color: rgba(0, 158, 247, 0.05) !important;
}

.bg-light {
    background-color: #f5f8fa !important;
}
</style>
@endpush

@endsection