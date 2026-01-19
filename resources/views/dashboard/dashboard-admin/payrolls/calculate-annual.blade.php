@extends('layouts.master')

@section('title', 'Hitung PPh 21 Tahunan')

@push('css')
<style>
    .summary-card {
        transition: all 0.3s ease;
    }
    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    /* ✅ TAMBAHAN: Bracket styling */
    .bracket-header {
        background-color: #f3f6f9;
        font-weight: 600;
        text-align: center;
        vertical-align: middle !important;
        border: 1px solid #e4e6ef;
    }
    .pkp-section {
        background-color: #fff8e1;
    }
    .tax-section {
        background-color: #e8f5e9;
    }
    .section-label {
        background-color: #e3f2fd;
        font-weight: 700;
        font-size: 0.9rem;
        text-align: center;
        vertical-align: middle !important;
    }
    .bracket-column {
        text-align: center;
        font-size: 0.8rem;
        border: 1px solid #e4e6ef;
    }
    .bracket-date {
        font-size: 0.7rem;
        color: #7e8299;
        font-weight: normal;
    }
    .total-column {
        text-align: right;
        background-color: #e3f2fd;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Hitung PPh 21 Tahunan
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('payrolls.index') }}" class="text-muted text-hover-primary">Payroll</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Hitung PPh 21 Tahunan</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('payrolls.index') }}" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left"></i> Kembali ke Payroll
                </a>
            </div>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            
            <!--begin::Info Alert-->
            <div class="alert alert-info d-flex align-items-center mb-5">
                <i class="ki-duotone ki-information-5 fs-2tx text-info me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1">Perhitungan PPh 21 Akhir Tahun</h4>
                    <span>Data di bawah ini adalah periode akhir tahun (<code>is_last_period = 1</code>) yang belum dihitung PPh21 tahunannya. Klik <strong>"Hitung Semua"</strong> untuk memproses perhitungan.</span>
                </div>
            </div>
            <!--end::Info Alert-->

            <!--begin::Summary Cards-->
            <div class="row g-5 mb-5">
                <div class="col-md-4">
                    <div class="card summary-card border border-primary">
                        <div class="card-body text-center">
                            <i class="ki-duotone ki-calendar fs-3tx text-primary mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="fs-2x fw-bold text-primary" id="pendingCount">{{ $pendingCount }}</div>
                            <div class="text-gray-600 mt-2">Data Pending Perhitungan</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card border border-success">
                        <div class="card-body text-center">
                            <i class="ki-duotone ki-check-circle fs-3tx text-success mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="fs-2x fw-bold text-success" id="calculatedCount">0</div>
                            <div class="text-gray-600 mt-2">Berhasil Dihitung</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card border border-danger">
                        <div class="card-body text-center">
                            <i class="ki-duotone ki-cross-circle fs-3tx text-danger mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="fs-2x fw-bold text-danger" id="failedCount">0</div>
                            <div class="text-gray-600 mt-2">Gagal Dihitung</div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Summary Cards-->

            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3>Data Pending Perhitungan</h3>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex gap-2">
                            <!--begin::Filter Tahun-->
                            <select id="filter-year" class="form-select form-select-solid w-150px">
                                <option value="">Semua Tahun</option>
                                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                            <!--end::Filter Tahun-->
                            
                            <button type="button" class="btn btn-primary" id="btnCalculateAll" onclick="calculateAll()">
                                <i class="fas fa-calculator"></i> Hitung Semua
                            </button>
                        </div>
                    </div>
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="annualTable">
                            <thead id="table-header">
                                <!-- Header akan di-generate dynamic -->
                            </thead>
                            <tbody class="text-gray-600 fw-semibold"></tbody>
                        </table>
                    </div>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
    </div>
    <!--end::Content-->
</div>

<!--begin::Modal Detail-->
<div class="modal fade" id="modal-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Detail Perhitungan PPh 21 Tahunan</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <div id="detail-content"></div>
            </div>
        </div>
    </div>
</div>
<!--end::Modal Detail-->

<!--begin::Modal Progress-->
<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Menghitung PPh21 Tahunan</h5>
            </div>
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="fs-5 mb-2" id="progressText">Memproses data...</div>
                <div class="text-muted" id="progressDetail">Mohon tunggu</div>
                
                <div class="progress mt-4" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         id="progressBar" 
                         role="progressbar" 
                         style="width: 0%">
                        <span id="progressPercent">0%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Modal Progress-->
@endsection

@push('scripts')
<script>
"use strict";

let table;
let currentBrackets = @json($bracketHeaders);

// ✅ Generate header berdasarkan bracket (sama seperti di laporan)
function generateTableHeader(brackets) {
    let html = `
        <!-- Row 1: Main grouping -->
        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th rowspan="3" class="min-w-125px">Karyawan</th>
            <th rowspan="3" class="min-w-100px">Company</th>
            <th rowspan="3" class="min-w-100px">Periode</th>
            <th rowspan="3" class="min-w-100px">Salary Type</th>
            <th rowspan="3" class="min-w-100px">Salary</th>
            <th rowspan="3" class="min-w-100px">Overtime</th>
            <th rowspan="3" class="min-w-100px">Tunjangan</th>
            <th rowspan="3" class="min-w-100px">Tunj PPh21 Masa</th>
            <th rowspan="3" class="min-w-100px">Tunj PPh21 Akhir</th>
            <th rowspan="3" class="min-w-100px">Tunj Asuransi</th>
            <th rowspan="3" class="min-w-100px">Natura</th>
            <th rowspan="3" class="min-w-100px">BPJS Asuransi</th>
            <th rowspan="3" class="min-w-100px">THR & Bonus</th>
            <th rowspan="3" class="min-w-125px">Total Bruto</th>
            <th rowspan="3" class="min-w-100px">Masa Jabatan</th>
            <th rowspan="3" class="min-w-100px">Premi Asuransi</th>
            <th rowspan="3" class="min-w-100px">Biaya Jabatan</th>
            <th rowspan="3" class="min-w-100px">Iuran JHT</th>
            <th rowspan="3" class="min-w-100px">Status PTKP</th>
            <th rowspan="3" class="min-w-100px">PTKP</th>
            <th rowspan="3" class="min-w-100px">PKP</th>
            <th colspan="${brackets.length}" class="section-label pkp-section">PKP per Bracket</th>
            <th colspan="${brackets.length}" class="section-label tax-section">Pajak per Bracket</th>
            <th rowspan="3" class="min-w-125px total-column">PPh 21 Tahunan</th>
            <th rowspan="3" class="min-w-125px">PPh 21 Masa</th>
            <th rowspan="3" class="min-w-125px bg-light">PPh 21 Akhir</th>
            <th rowspan="3" class="min-w-100px">Status</th>
            <th rowspan="3" class="min-w-100px">Aksi</th>
        </tr>
        
        <!-- Row 2: Bracket labels with dates -->
        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
    `;
    
    // PKP Columns
    brackets.forEach(bracket => {
        html += `
            <th class="bracket-column pkp-section">
                ${bracket.rate_percent}%<br/>
                <small>${bracket.max_label}</small><br/>
                <span class="bracket-date">${bracket.effective_start_date} - ${bracket.effective_end_date}</span>
            </th>
        `;
    });
    
    // Tax Columns
    brackets.forEach(bracket => {
        html += `
            <th class="bracket-column tax-section">
                ${bracket.rate_percent}%<br/>
                <small>${bracket.max_label}</small><br/>
                <span class="bracket-date">${bracket.effective_start_date} - ${bracket.effective_end_date}</span>
            </th>
        `;
    });
    
    html += `
        </tr>
        
        <!-- Row 3: PKP & Pajak labels -->
        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
    `;
    
    // PKP Labels
    for(let i = 0; i < brackets.length; i++) {
        html += `<th class="bracket-column pkp-section">PKP</th>`;
    }
    
    // Tax Labels
    for(let i = 0; i < brackets.length; i++) {
        html += `<th class="bracket-column tax-section">Pajak</th>`;
    }
    
    html += `</tr>`;
    
    return html;
}

// ✅ Generate columns berdasarkan bracket
function generateColumns(brackets) {
    let columns = [
        // 1. Karyawan
        { 
            data: 'karyawan_nama',
            render: function(data, type, row) {
                return `
                    <div class="d-flex flex-column">
                        <span class="text-gray-800 mb-1">${data}</span>
                        <span class="text-muted fw-semibold text-muted d-block fs-7">
                            NIK: ${row.karyawan_nik || '-'}
                        </span>
                    </div>
                `;
            }
        },
        // 2. Company
        { data: 'company_name' },
        // 3. Periode
        { 
            data: 'periode',
            className: 'text-center'
        },
        // 4. Salary Type
        { 
            data: 'salary_type',
            className: 'text-center',
            render: function(data) {
                return data === 'gross' 
                    ? '<span class="badge badge-light-primary">GROSS</span>'
                    : '<span class="badge badge-light-success">NETT</span>';
            }
        },
        // 5. Salary
        { 
            data: 'salary',
            className: 'text-end',
            render: formatRupiah
        },
        // 6. Overtime
        { 
            data: 'overtime',
            className: 'text-end',
            render: formatRupiah
        },
        // 7. Tunjangan
        { 
            data: 'tunjangan',
            className: 'text-end',
            render: formatRupiah
        },
        // 8. Tunj PPh21 Masa
        { 
            data: 'tunj_pph_21',
            className: 'text-end',
            render: formatRupiah
        },
        // 9. Tunj PPh21 Akhir
        { 
            data: 'tunj_pph21_akhir',
            className: 'text-end',
            render: formatRupiah
        },
        // 10. Tunj Asuransi
        { 
            data: 'tunjangan_asuransi',
            className: 'text-end',
            render: formatRupiah
        },
        // 11. Natura
        { 
            data: 'natura',
            className: 'text-end',
            render: formatRupiah
        },
        // 12. BPJS Asuransi
        { 
            data: 'bpjs_asuransi',
            className: 'text-end',
            render: formatRupiah
        },
        // 13. THR & Bonus
        { 
            data: 'thr_bonus',
            className: 'text-end',
            render: formatRupiah
        },
        // 14. Total Bruto
        { 
            data: 'total_bruto',
            className: 'text-end',
            render: formatRupiah
        },
        // 15. Masa Jabatan
        { 
            data: 'masa_jabatan',
            className: 'text-center',
            render: function(data) {
                return `${data} bulan`;
            }
        },
        // 16. Premi Asuransi
        { 
            data: 'premi_asuransi',
            className: 'text-end',
            render: formatRupiah
        },
        // 17. Biaya Jabatan
        { 
            data: 'biaya_jabatan',
            className: 'text-end',
            render: formatRupiah
        },
        // 18. Iuran JHT
        { 
            data: 'iuran_jht',
            className: 'text-end',
            render: formatRupiah
        },
        { 
            data: 'status',
            className: 'text-center',
            render: function(data, type, row) {
                return `${row.status || '-'}`;
            }
        },
        // 20. PTKP
        { 
            data: 'besaran_ptkp',
            className: 'text-end',
            render: formatRupiah
        },
        // 21. PKP
        { 
            data: 'pkp',
            className: 'text-end',
            render: formatRupiah
        }
    ];
    
    // 22-26. PKP Brackets (dinamis)
    brackets.forEach((bracket) => {
        columns.push({
            data: `bracket_${bracket.order_index}_pkp`,
            className: 'text-center pkp-section',
            render: function(data) {
                return data > 0 ? formatRupiah(data) : '-';
            },
            defaultContent: '-'
        });
    });
    
    // 27-31. Tax Brackets (dinamis)
    brackets.forEach((bracket) => {
        columns.push({
            data: `bracket_${bracket.order_index}_pph21`,
            className: 'text-center tax-section',
            render: function(data) {
                return data > 0 ? formatRupiah(data) : '-';
            },
            defaultContent: '-'
        });
    });
    
    // 32. PPh21 Tahunan
    columns.push({
        data: 'pph21_tahunan',
        className: 'text-center total-column',
        render: function(data) {
            return `<strong>${formatRupiah(data)}</strong>`;
        }
    });
    
    // 33. PPh21 Masa
    columns.push({
        data: 'pph21_masa',
        className: 'text-end',
        render: formatRupiah
    });
    
    // 34. PPh21 Akhir
    columns.push({
        data: 'pph21_akhir',
        className: 'text-end bg-light fw-bold',
        render: function(data) {
            return `<strong>${formatRupiah(data)}</strong>`;
        }
    });
    
    // 35. Status (Pending/Sudah Dihitung)
    columns.push({
        data: 'status',
        className: 'text-center',
        render: function(data) {
            if (data === 'calculated') {
                return '<span class="badge badge-light-success">Sudah Dihitung</span>';
            }
            return '<span class="badge badge-light-warning">Pending</span>';
        }
    });
    
    // 36. Aksi
    columns.push({
        data: 'action',
        className: 'text-center',
        orderable: false,
        searchable: false
    });
    
    return columns;
}

$(document).ready(function() {
    // Generate initial header
    $('#table-header').html(generateTableHeader(currentBrackets));
    
    // Initialize DataTable
    initializeDataTable();
    
    // ✅ Filter year handler - update header
    $('#filter-year').change(function() {
        const year = $(this).val() || new Date().getFullYear();
        
        Swal.fire({
            title: 'Memuat Bracket...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: "{{ route('payrolls.calculate-annual.bracket-headers') }}",
            data: { year: year },
            success: function(brackets) {
                currentBrackets = brackets;
                
                if (table) {
                    table.destroy();
                    table = null;
                }
                
                $('#annualTable').empty().html(`
                    <thead id="table-header"></thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                `);
                
                $('#table-header').html(generateTableHeader(brackets));
                initializeDataTable();
                
                Swal.close();
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat data bracket', 'error');
                initializeDataTable();
            }
        });
    });
    
    // View detail handler
    $(document).on('click', '.view-detail', function(e) {
        e.preventDefault();
        const karyawanId = $(this).data('karyawan-id');
        const companyId = $(this).data('company-id');
        const salaryType = $(this).data('salary-type');
        const periode = $(this).data('periode');
        
        showDetail(karyawanId, companyId, salaryType, periode);
    });
});

function initializeDataTable() {
    const columns = generateColumns(currentBrackets);
    
    table = $('#annualTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('payrolls.calculate-annual.datatable') }}",
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: function(d) {
                d.year = $('#filter-year').val();
            }
        },
        columns: columns,
        order: [[2, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        scrollX: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: "Tidak ada data pending perhitungan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            lengthMenu: "Tampilkan _MENU_ data",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });
}

function calculateAll() {
    Swal.fire({
        title: 'Konfirmasi',
        html: 'Hitung PPh21 Tahunan untuk semua data pending?<br><small class="text-muted">Proses ini akan menghitung PPh21 akhir tahun berdasarkan PKP tahunan.</small>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hitung Semua',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009ef7',
        cancelButtonColor: '#f1416c'
    }).then((result) => {
        if (result.isConfirmed) {
            executeCalculation();
        }
    });
}

function executeCalculation() {
    $('#progressModal').modal('show');
    $('#progressText').text('Menghitung PPh21 Tahunan...');
    $('#progressDetail').text('Mohon tunggu');
    $('#progressBar').css('width', '0%');
    $('#progressPercent').text('0%');
    
    let progress = 0;
    const progressInterval = setInterval(function() {
        progress += Math.random() * 15;
        if (progress > 90) {
            progress = 90;
            clearInterval(progressInterval);
        }
        updateProgress(progress);
    }, 300);
    
    $.ajax({
        url: "{{ route('payrolls.calculate-annual.process') }}",
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            clearInterval(progressInterval);
            updateProgress(100);
            
            setTimeout(function() {
                $('#progressModal').modal('hide');
                
                if (response.success) {
                    const results = response.results;
                    
                    let skipInfo = '';
                    if (results.skipped > 0) {
                        skipInfo = `
                            <tr class="table-warning">
                                <td>Di-skip (sudah dihitung)</td>
                                <td class="text-end"><strong>${results.skipped}</strong> data</td>
                            </tr>
                        `;
                    }
                    
                    Swal.fire({
                        icon: results.failed > 0 ? 'warning' : 'success',
                        title: 'Perhitungan Selesai',
                        html: `
                            <div class="text-start">
                                <table class="table table-sm">
                                    <tr class="table-success">
                                        <td>Berhasil Dihitung</td>
                                        <td class="text-end"><strong>${results.success}</strong> data</td>
                                    </tr>
                                    ${skipInfo}
                                    <tr class="table-danger">
                                        <td>Gagal</td>
                                        <td class="text-end"><strong>${results.failed}</strong> data</td>
                                    </tr>
                                    <tr class="fw-bold border-top">
                                        <td>Total Diproses</td>
                                        <td class="text-end">${results.total} data</td>
                                    </tr>
                                </table>
                                ${results.skipped > 0 ? '<small class="text-muted">*Data yang di-skip sudah dihitung oleh proses lain (race condition avoided)</small>' : ''}
                            </div>
                        `,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#calculatedCount').text(results.success);
                        $('#failedCount').text(results.failed);
                        
                        if (table) {
                            table.ajax.reload();
                        }
                        
                        if (results.success > 0) {
                            Swal.fire({
                                title: 'Perhitungan Selesai',
                                html: 'PPh21 Tahunan berhasil dihitung. Kembali ke halaman Payroll?',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Kembali ke Payroll',
                                cancelButtonText: 'Tetap di Sini'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "{{ route('payrolls.index') }}";
                                }
                            });
                        }
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }, 500);
        },
        error: function(xhr) {
            clearInterval(progressInterval);
            $('#progressModal').modal('hide');
            const error = xhr.responseJSON?.message || 'Error menghitung PPh21';
            Swal.fire('Error', error, 'error');
        }
    });
}

function updateProgress(percent) {
    percent = Math.min(100, Math.max(0, percent));
    $('#progressBar').css('width', percent + '%');
    $('#progressPercent').text(Math.round(percent) + '%');
    
    if (percent < 30) {
        $('#progressDetail').text('Mengambil data dari view tahunan');
    } else if (percent < 60) {
        $('#progressDetail').text('Menghitung PPh21 menggunakan bracket');
    } else if (percent < 90) {
        $('#progressDetail').text('Menghitung PPh21 akhir');
    } else {
        $('#progressDetail').text('Menyimpan hasil perhitungan');
    }
}
function showDetail(karyawanId, companyId, salaryType, periode) {
    $.ajax({
        url: "{{ route('payrolls.calculate-annual.detail') }}",
        data: { 
            karyawan_id: karyawanId,
            company_id: companyId,
            salary_type: salaryType,
            periode: periode
        },
        success: function(data) {
            let html = `
                <div class="table-responsive">
                    <table class="table table-row-bordered">
                        <tr>
                            <td class="fw-bold">Nama Karyawan</td>
                            <td>${data.karyawan_nama}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">NIK</td>
                            <td>${data.karyawan_nik || '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Perusahaan</td>
                            <td>${data.company_name}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Periode</td>
                            <td>${data.periode}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Salary Type</td>
                            <td class=""><span class="badge badge-light-${data.salary_type === 'gross' ? 'primary' : 'success'}">${data.salary_type.toUpperCase()}</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Salary</td>
                            <td class="">${formatRupiah(data.salary)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Overtime</td>
                            <td class="">${formatRupiah(data.overtime)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Tunjangan</td>
                            <td class="">${formatRupiah(data.tunjangan)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Tunj PPh21 Masa</td>
                            <td class="">${formatRupiah(data.tunj_pph_21)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Tunj PPh21 Akhir</td>
                            <td class="">${formatRupiah(data.tunj_pph21_akhir)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Tunjangan Asuransi</td>
                            <td class="">${formatRupiah(data.tunjangan_asuransi)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Natura</td>
                            <td class="">${formatRupiah(data.natura)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">BPJS Asuransi</td>
                            <td class="">${formatRupiah(data.bpjs_asuransi)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">THR & Bonus</td>
                            <td class="">${formatRupiah(data.thr_bonus)}</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold">Total Bruto</td>
                            <td class=" fw-bold">${formatRupiah(data.total_bruto)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Masa Jabatan</td>
                            <td class="">${data.masa_jabatan} bulan</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Premi Asuransi</td>
                            <td class="">${formatRupiah(data.premi_asuransi)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Biaya Jabatan (5%)</td>
                            <td class="">${formatRupiah(data.biaya_jabatan)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold ">Iuran JHT</td>
                            <td class="">${formatRupiah(data.iuran_jht)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Status PTKP</td>
                            <td class="">${data.status || '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">PTKP</td>
                            <td class="">${formatRupiah(data.besaran_ptkp)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">PKP</td>
                            <td class="">${formatRupiah(data.pkp)}</td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold">PPh21 Tahunan</td>
                            <td class=" fw-bold">${formatRupiah(data.pph21_tahunan)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">PPh21 Masa</td>
                            <td class="">${formatRupiah(data.pph21_masa)}</td>
                        </tr>
                        <tr class="table-primary">
                            <td class="fw-bold">PPh21 Akhir</td>
                            <td class=" fw-bold">${formatRupiah(data.pph21_akhir)}</td>
                        </tr>
                    </table>

                    <h5 class="mt-8 mb-4">Breakdown PPh 21 per Bracket:</h5>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="ki-duotone ki-information-5 fs-2tx text-info me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h5 class="mb-1">Periode Berlaku: ${data.period_date ? new Date(data.period_date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long' }) : '-'}</h5>
                        </div>
                    </div>
                    <table class="table table-row-bordered">
                        <thead>
                            <tr class="fw-bold">
                                <th>Bracket</th>
                                <th class="text-center">PKP</th>
                                <th class="text-center">Rate</th>
                                <th class="text-center">PPh 21</th>
                                <th class="text-center">Periode Berlaku</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.bracket_details.forEach((bracket, index) => {
                const startDate = bracket.effective_start_date ? new Date(bracket.effective_start_date).toLocaleDateString('id-ID', { year: 'numeric', month: 'short' }) : '-';
                const endDate = bracket.effective_end_date ? new Date(bracket.effective_end_date).toLocaleDateString('id-ID', { year: 'numeric', month: 'short' }) : 'Sekarang';
                
                html += `
                    <tr ${bracket.pkp_in_bracket > 0 ? '' : 'class="text-muted"'}>
                        <td>${bracket.description || `Bracket ${bracket.order_index}`}</td>
                        <td class="text-center">${bracket.pkp_in_bracket > 0 ? formatRupiah(bracket.pkp_in_bracket) : '-'}</td>
                        <td class="text-center">${bracket.rate_percent}%</td>
                        <td class="text-center">${bracket.pkp_in_bracket > 0 ? formatRupiah(bracket.pph21_in_bracket) : '-'}</td>
                        <td class="text-center"><small>${startDate} - ${endDate}</small></td>
                    </tr>
                `;
            });
            
            html += `
                            <tr class="table-success">
                                <td colspan="3" class="fw-bold">Total PPh 21 Tahunan</td>
                                <td class="text-center fw-bold">${formatRupiah(data.pph21_tahunan)}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            $('#detail-content').html(html);
            $('#modal-detail').modal('show');
        },
        error: function() {
            Swal.fire('Error', 'Gagal memuat detail', 'error');
        }
    });
}

function formatRupiah(amount) {
    if (amount === 0 || amount === null || amount === undefined) return 'Rp 0';
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}
</script>
@endpush