@extends('layouts.master')

@section('title', 'Import Payroll Fake')

@section('content')
<div class="d-flex flex-column flex-root">
    <div class="page d-flex flex-row flex-column-fluid">
        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            
            <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                <!-- Toolbar -->
                <div class="toolbar" id="kt_toolbar">
                    <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
                        <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
                            <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Import Payroll Fake
                            </h1>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            <a href="{{ route('payrolls-fake.index') }}" class="btn btn-sm btn-light">
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
                                                <li><strong>PPh21: isi manual (tidak auto-calculate)</strong></li>
                                            </ul>
                                            <div class="alert alert-warning mt-3 mb-0" style="font-size: 0.85rem;">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Semua kolom termasuk PPh21 diisi MANUAL dari Excel
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
                                                    <th class="bg-warning">PPh 21</th>
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
                                    <button type="button" class="btn btn-success" id="btnImport" style="display: none;" onclick="processImport()">
                                        <i class="fas fa-check"></i> Import ke Database (<span id="validCount">0</span> baris)
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
        url: '{{ route("payrollsfake.import.validate") }}',
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

function displayValidationResults(response) {
    validationErrors = response.errors || [];
    
    $('#resultsCard').show();
    
    $('#totalRows').text(response.summary.total_rows);
    $('#validRows').text(response.summary.valid_rows);
    $('#errorRows').text(response.summary.error_rows);
    $('#validCount').text(response.summary.valid_rows);
    
    if (response.summary.error_rows > 0) {
        showErrorData();
        
        if (response.summary.valid_rows > 0) {
            $('#btnImport').show();
        } else {
            $('#btnImport').hide();
        }
    } else {
        showValidData();
        $('#btnImport').show();
    }
    
    $('html, body').animate({
        scrollTop: $('#resultsCard').offset().top - 100
    }, 500);
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
            url: '{{ route("payrollsfake.import.datatable.valid") }}',
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
            { data: 'pph_21', render: formatRupiah, className: 'text-end bg-warning' },
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
            url: '{{ route("payrollsfake.import.datatable.errors") }}',
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
        html: `Import ${$('#validCount').text()} data payroll fake ke database?<br><small class="text-muted">Data akan disimpan dengan nilai PPh21 yang sudah diisi manual di Excel.</small>`,
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
        url: '{{ route("payrollsfake.import.process") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Import Berhasil',
                html: `${response.imported_count} data payroll fake berhasil diimport`,
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '{{ route("payrolls-fake.index") }}';
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
    window.location.href = '{{ route("payrollsfake.import.template") }}';
}

function downloadErrors() {
    if (validationErrors.length === 0) {
        Swal.fire('Info', 'Tidak ada error untuk didownload', 'info');
        return;
    }
    
    $.ajax({
        url: '{{ route("payrollsfake.import.download-errors") }}',
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
            a.download = 'payroll_fake_import_errors_' + new Date().getTime() + '.xlsx';
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
    $('#btnImport').hide();
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

.bg-warning {
    background-color: #fff3cd !important;
}
</style>
@endpush

@endsection