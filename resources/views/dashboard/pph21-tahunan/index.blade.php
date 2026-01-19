@extends('layouts.master')

@section('title', 'Laporan PPh 21 Tahunan')

@push('styles')
<style>
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
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="pph21-table">
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

<!--begin::Modal Detail-->
<div class="modal fade" id="modal-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Detail Perhitungan PPh 21</h2>
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
@endsection

@push('scripts')
<script>
"use strict";

let table;
let currentBrackets = @json($bracketHeaders);

// Generate header berdasarkan bracket
function generateTableHeader(brackets) {
    let html = `
        <!-- Row 1: Main grouping -->
        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th rowspan="3" class="min-w-125px">Karyawan</th>
            <th rowspan="3" class="min-w-100px">Periode</th>
            <th rowspan="3" class="min-w-100px">Masa Jabatan</th>
            <th rowspan="3" class="min-w-125px">Total Bruto</th>
            <th rowspan="3" class="min-w-100px">PKP</th>
            <th colspan="${brackets.length}" class="section-label pkp-section">PKP per Bracket</th>
            <th colspan="${brackets.length}" class="section-label tax-section">Pajak per Bracket</th>
            <th rowspan="3" class="min-w-125px total-column">PPh 21 Tahunan</th>
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

// Generate columns berdasarkan bracket
function generateColumns(brackets) {
    let columns = [
        { 
            data: 'karyawan_nama', 
            name: 'k.nama_lengkap',
            render: function(data, type, row) {
                return `
                    <div class="d-flex align-items-center">
                        <div class="d-flex flex-column">
                            <a href="#" class="text-gray-800 text-hover-primary mb-1 view-detail" data-id="${row.karyawan_id}" data-year="${row.periode}">
                                ${data}
                            </a>
                            <span class="text-muted fw-semibold text-muted d-block fs-7">
                                NIK: ${row.karyawan_nik || '-'}
                            </span>
                        </div>
                    </div>
                `;
            }
        },
        { 
            data: 'periode', 
            name: 'periode',
            className: 'text-center'
        },
        { 
            data: 'masa_jabatan', 
            name: 'masa_jabatan',
            className: 'text-center',
            render: function(data) {
                return `${data} bulan`;
            }
        },
        { 
            data: 'total_bruto', 
            name: 'total_bruto',
            className: 'text-center',
            render: function(data) {
                return formatRupiah(data);
            }
        },
        { 
            data: 'pkp', 
            name: 'pkp',
            className: 'text-center',
            render: function(data) {
                return formatRupiah(data);
            }
        }
    ];
    
    // Add PKP columns dynamically
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
    
    // Add Tax columns dynamically
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
    
    // Add total column
    columns.push({
        data: 'pph21_tahunan', 
        name: 'pph21_tahunan',
        className: 'text-center total-column',
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
        
        // ✅ Show overlay loading
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
    
    // View detail handler
    $(document).on('click', '.view-detail', function(e) {
        e.preventDefault();
        const karyawanId = $(this).data('id');
        const year = $(this).data('year');
        
        $.ajax({
            url: "{{ route('pph21.tahunan.detail') }}",
            data: { karyawan_id: karyawanId, year: year },
            success: function(response) {
                showDetailModal(response);
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat detail', 'error');
            }
        });
    });
});

function initializeDataTable() {
    // Generate columns based on current brackets
    const columns = generateColumns(currentBrackets);
    
    table = $('#pph21-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('pph21.tahunan.data') }}",
            data: function(d) {
                d.year = $('#filter-year').val() || new Date().getFullYear();
                d.company_id = $('#filter-company').val();
                d.search = $('#search-input').val();
            }
        },
        columns: columns,
        order: [[1, 'desc'], [0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
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
        },
        drawCallback: function() {
            // Optional: Add any post-draw customizations
        }
    });
}

function formatRupiah(amount) {
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

function showDetailModal(data) {
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
                    <td class="fw-bold">Periode</td>
                    <td>${data.periode}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Periode Terakhir</td>
                    <td>${data.last_period}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Status PTKP</td>
                    <td>${data.ptkp_status}</td>
                </tr>
                <tr class="table-light">
                    <td class="fw-bold">Total Bruto</td>
                    <td class="text-center">${formatRupiah(data.total_bruto)}</td>
                </tr>
                <tr>
                    <td class="fw-bold ps-8">- Salary</td>
                    <td class="text-center">${formatRupiah(data.salary)}</td>
                </tr>
                <tr>
                    <td class="fw-bold ps-8">- Overtime</td>
                    <td class="text-center">${formatRupiah(data.overtime)}</td>
                </tr>
                <tr>
                    <td class="fw-bold ps-8">- Tunjangan</td>
                    <td class="text-center">${formatRupiah(data.tunjangan)}</td>
                </tr>
                <tr>
                    <td class="fw-bold ps-8">- THR & Bonus</td>
                    <td class="text-center">${formatRupiah(data.thr_bonus)}</td>
                </tr>
                <tr class="table-light">
                    <td class="fw-bold">Biaya Jabatan (5%)</td>
                    <td class="text-center">${formatRupiah(data.biaya_jabatan)}</td>
                </tr>
                <tr class="table-light">
                    <td class="fw-bold">Iuran JHT</td>
                    <td class="text-center">${formatRupiah(data.iuran_jht)}</td>
                </tr>
                <tr class="table-light">
                    <td class="fw-bold">PTKP</td>
                    <td class="text-center">${formatRupiah(data.besaran_ptkp)}</td>
                </tr>
                <tr class="table-primary">
                    <td class="fw-bold">PKP</td>
                    <td class="text-center fw-bold">${formatRupiah(data.pkp)}</td>
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
}
</script>
@endpush