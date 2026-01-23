@extends('layouts.master')

@section('title', 'Laporan PPh 21 Tahunan')

@push('css')
<style>
    /* Compact Table Styling */
    #pph21-table tbody td, th,
    #pph21-table thead th {
        padding: 6px 6px !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
    }
    
    #pph21-table thead th {
        font-weight: 600 !important;
    }
    
    /* Badge compact */
    #pph21-table .badge {
        font-size: 10px !important;
        padding: 2px 6px !important;
    }
    
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
        font-size: 0.8rem !important;
        text-align: center;
        vertical-align: middle !important;
    }
    .bracket-column {
        text-align: center;
        font-size: 0.7rem !important;
        border: 1px solid #e4e6ef;
    }
    .bracket-date {
        font-size: 0.65rem !important;
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
                    Laporan PPh 21 Tahunan
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Laporan PPh 21 Tahunan</li>
                </ul>
            </div>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" id="search-input" class="form-control form-control-solid w-250px ps-13" placeholder="Cari karyawan..." />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <!--begin::Filter Tahun-->
                            <select id="filter-year" class="form-select form-select-solid w-150px">
                                <option value="">Semua Tahun</option>
                                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                            <!--end::Filter Tahun-->
                            
                            <!--begin::Filter Company-->
                            <select id="filter-company" class="form-select form-select-solid w-200px">
                                <option value="">Semua Perusahaan</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                @endforeach
                            </select>
                            <!--end::Filter Company-->

                            <!--begin::Export-->
                            <button type="button" class="btn btn-light-primary" id="export-excel">
                                <i class="ki-duotone ki-exit-up fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Export Excel
                            </button>
                            <!--end::Export-->
                        </div>
                    </div>
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed table-striped gs-0 gy-3" id="pph21-table">
                            <thead id="table-header">
                                <!-- Header akan di-generate dynamic -->
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                            </tbody>
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
@endsection

@push('scripts')
<script>
"use strict";

let table;
let currentBrackets = @json($bracketHeaders);

// Generate header berdasarkan bracket (COMPACT)
function generateTableHeader(brackets) {
    let html = `
        <!-- Row 1: Main grouping -->
        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th rowspan="3" class="min-w-100px">Karyawan</th>
            <th rowspan="3" class="min-w-80px">Company</th>
            <th rowspan="3" class="min-w-70px">Periode</th>
            <th rowspan="3" class="min-w-70px">Salary Type</th>
            <th rowspan="3" class="min-w-80px">Salary</th>
            <th rowspan="3" class="min-w-80px">Overtime</th>
            <th rowspan="3" class="min-w-80px">Tunjangan</th>
            <th rowspan="3" class="min-w-80px">Tunj PPh21 Masa</th>
            <th rowspan="3" class="min-w-80px">Tunj PPh21 Akhir</th>
            <th rowspan="3" class="min-w-80px">Tunj Asuransi</th>
            <th rowspan="3" class="min-w-80px">Natura</th>
            <th rowspan="3" class="min-w-80px">BPJS Asuransi</th>
            <th rowspan="3" class="min-w-80px">THR & Bonus</th>
            <th rowspan="3" class="min-w-100px">Total Bruto</th>
            <th rowspan="3" class="min-w-70px">Masa Jabatan</th>
            <th rowspan="3" class="min-w-80px">Premi Asuransi</th>
            <th rowspan="3" class="min-w-80px">Biaya Jabatan</th>
            <th rowspan="3" class="min-w-80px">Iuran JHT</th>
            <th rowspan="3" class="min-w-70px">Status PTKP</th>
            <th rowspan="3" class="min-w-80px">PTKP</th>
            <th rowspan="3" class="min-w-80px">PKP</th>
            <th colspan="${brackets.length}" class="section-label pkp-section">PKP per Bracket</th>
            <th colspan="${brackets.length}" class="section-label tax-section">Pajak per Bracket</th>
            <th rowspan="3" class="min-w-100px total-column">PPh 21 Tahunan</th>
            <th rowspan="3" class="min-w-100px">PPh 21 Masa</th>
            <th rowspan="3" class="min-w-100px bg-light">PPh 21 Akhir</th>
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

// Generate columns berdasarkan bracket (COMPACT - NO DETAIL)
function generateColumns(brackets) {
    let columns = [
        // 1. Karyawan (simplified - no link)
        { 
            data: 'karyawan_nama',
            name: 'karyawan_nama', // ✅ FIX: ganti dari 'k.nama_lengkap'
            render: function(data, type, row) {
                return `
                    <div class="d-flex flex-column">
                        <span class="text-gray-800">${data}</span>
                        <span class="text-muted fs-8">NIK: ${row.karyawan_nik || '-'}</span>
                    </div>
                `;
            }
        },
        // 2. Company
        { 
            data: 'company_name',
            name: 'company_name' // ✅ FIX: ganti dari 'c.company_name'
        },
        // 3. Periode
        { 
            data: 'periode',
            name: 'periode',
            className: 'text-center'
        },
        // 4. Salary Type
        { 
            data: 'salary_type',
            name: 'salary_type',
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
            name: 'salary',
            className: 'text-center',
            render: formatRupiah
        },
        // 6. Overtime
        { 
            data: 'overtime',
            name: 'overtime',
            className: 'text-center',
            render: formatRupiah
        },
        // 7. Tunjangan
        { 
            data: 'tunjangan',
            name: 'tunjangan',
            className: 'text-center',
            render: formatRupiah
        },
        // 8. Tunj PPh21 Masa
        { 
            data: 'tunj_pph_21',
            name: 'tunj_pph_21',
            className: 'text-center',
            render: formatRupiah
        },
        // 9. Tunj PPh21 Akhir
        { 
            data: 'tunj_pph21_akhir',
            name: 'tunj_pph21_akhir',
            className: 'text-center',
            render: formatRupiah
        },
        // 10. Tunj Asuransi
        { 
            data: 'tunjangan_asuransi',
            name: 'tunjangan_asuransi',
            className: 'text-center',
            render: formatRupiah
        },
        // 11. Natura
        { 
            data: 'natura',
            name: 'natura',
            className: 'text-center',
            render: formatRupiah
        },
        // 12. BPJS Asuransi
        { 
            data: 'bpjs_asuransi',
            name: 'bpjs_asuransi',
            className: 'text-center',
            render: formatRupiah
        },
        // 13. THR & Bonus
        { 
            data: 'thr_bonus',
            name: 'thr_bonus',
            className: 'text-center',
            render: formatRupiah
        },
        // 14. Total Bruto
        { 
            data: 'total_bruto',
            name: 'total_bruto',
            className: 'text-center fw-bold bg-light',
            render: formatRupiah
        },
        // 15. Masa Jabatan
        { 
            data: 'masa_jabatan',
            name: 'masa_jabatan',
            className: 'text-center',
            render: function(data) {
                return `${data} bln`;
            }
        },
        // 16. Premi Asuransi
        { 
            data: 'premi_asuransi',
            name: 'premi_asuransi',
            className: 'text-center',
            render: formatRupiah
        },
        // 17. Biaya Jabatan
        { 
            data: 'biaya_jabatan',
            name: 'biaya_jabatan',
            className: 'text-center',
            render: formatRupiah
        },
        // 18. Iuran JHT
        { 
            data: 'iuran_jht',
            name: 'iuran_jht',
            className: 'text-center',
            render: formatRupiah
        },
        // 19. Status PTKP
        { 
            data: 'status',
            name: 'status',
            className: 'text-center',
            render: function(data, type, row) {
                return `${row.status || '-'}`;
            }
        },
        // 20. PTKP
        { 
            data: 'besaran_ptkp',
            name: 'besaran_ptkp',
            className: 'text-center',
            render: formatRupiah
        },
        // 21. PKP
        { 
            data: 'pkp',
            name: 'pkp',
            className: 'text-center fw-bold',
            render: formatRupiah
        }
    ];
    
    // 22-26. PKP Brackets (dinamis)
    brackets.forEach((bracket) => {
        columns.push({
            data: `bracket_${bracket.order_index}_pkp`,
            name: `bracket_${bracket.order_index}_pkp`,
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
            name: `bracket_${bracket.order_index}_pph21`,
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
        name: 'pph21_tahunan',
        className: 'text-center total-column',
        render: function(data) {
            return `<strong>${formatRupiah(data)}</strong>`;
        }
    });
    
    // 33. PPh21 Masa
    columns.push({
        data: 'tunj_pph_21',
        name: 'tunj_pph_21',
        className: 'text-center',
        render: formatRupiah
    });
    
    // 34. PPh21 Akhir
    columns.push({
        data: 'tunj_pph21_akhir',
        name: 'tunj_pph21_akhir',
        className: 'text-center bg-light fw-bold',
        render: function(data) {
            return `<strong>${formatRupiah(data)}</strong>`;
        }
    });
    
    return columns;
}

$(document).ready(function() {
    // Generate initial header
    $('#table-header').html(generateTableHeader(currentBrackets));
    
    // Initialize DataTable
    initializeDataTable();
    
    // Filter year handler - update header
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
            url: "{{ route('pph21.tahunan.bracket-headers') }}",
            data: { year: year },
            success: function(brackets) {
                currentBrackets = brackets;
                
                if (table) {
                    table.destroy();
                    table = null;
                }
                
                $('#pph21-table').empty().html(`
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
    
    // Filter company handler
    $('#filter-company').change(function() {
        if (table) {
            table.ajax.reload();
        }
    });
    
    // Search handler with debounce
    let searchTimer;
    $('#search-input').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (table) {
                table.ajax.reload();
            }
        }, 500);
    });
});

// Initialize DataTable with full columns
// Initialize DataTable with full columns
function initializeDataTable() {
    const columns = generateColumns(currentBrackets);
    
    table = $('#pph21-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('pph21.tahunan.data') }}",
            type: 'POST', // ⬅️ TAMBAHKAN INI
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // ⬅️ TAMBAHKAN INI
            },
            data: function(d) {
                d.year = $('#filter-year').val() || new Date().getFullYear();
                d.company_id = $('#filter-company').val();
                d.search = $('#search-input').val();
            }
        },
        columns: columns,
        order: [[2, 'desc'], [0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        scrollX: true,
        scrollCollapse: true,
        fixedColumns: {
            leftColumns: 4
        },
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: "Tidak ada data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(difilter dari _MAX_ total data)",
            lengthMenu: "Tampilkan _MENU_ data",
            loadingRecords: "Loading...",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            },
            search: "Cari:",
            zeroRecords: "Data tidak ditemukan"
        }
    });
}

function formatRupiah(amount) {
    if (amount === 0 || amount === null || amount === undefined) return 'Rp 0';
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

// Export Excel handler
$('#export-excel').on('click', function(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Exporting...',
        html: 'Mohon tunggu, sedang memproses export data',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Build export URL dengan filter
    var params = new URLSearchParams({
        year: $('#filter-year').val() || new Date().getFullYear(),
        company_id: $('#filter-company').val() || '',
        search: $('#search-input').val() || ''
    });

    var exportUrl = '{{ route("pph21.tahunan.export") }}?' + params.toString();

    // Create temporary link untuk download
    var link = document.createElement('a');
    link.href = exportUrl;
    link.download = 'laporan_pph21_tahunan.xlsx';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Close loading setelah 2 detik
    setTimeout(function() {
        Swal.fire({
            icon: 'success',
            title: 'Export Berhasil!',
            text: 'File Excel berhasil didownload',
            timer: 2000,
            showConfirmButton: false
        });
    }, 2000);
});
</script>
@endpush