@extends('layouts.master')

@section('title', 'Periode Karyawan & Masa Jabatan')

@section('content')
<!--begin::Toolbar-->
<div class="toolbar" id="kt_toolbar">
    <div class="container-fluid d-flex flex-stack">
        <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
            <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Periode Karyawan & Masa Jabatan</h1>
            <span class="h-20px border-gray-300 border-start mx-4"></span>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-300 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-dark">Periode Karyawan</li>
            </ul>
        </div>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Post-->
<div class="post " id="kt_post">
    <div class="">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <span class="svg-icon svg-icon-1 position-absolute ms-6">
                            <i class="bi bi-search fs-3"></i>
                        </span>
                        <input type="text" id="search" class="form-control form-control-solid w-250px ps-15" placeholder="Search..." />
                    </div>
                </div>
                
                <div class="card-toolbar">
                    <div class="d-flex justify-content-end gap-2" data-kt-customer-table-toolbar="base">
                        <!--begin::Filter-->
                        <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            <i class="bi bi-funnel fs-3"></i>
                            Filter
                        </button>
                        
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-400px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-dark fw-bold">Filter Options</div>
                            </div>
                            
                            <div class="separator border-gray-200"></div>
                            
                            <div class="px-7 py-5">
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Periode (Tahun):</label>
                                    <select class="form-select form-select-solid" id="filter_periode">
                                        <option value="">Semua Periode</option>
                                        @foreach($periodes as $periode)
                                            <option value="{{ $periode }}">{{ $periode }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Karyawan:</label>
                                    <select class="form-select form-select-solid" id="filter_karyawan">
                                        <option value="">Semua Karyawan</option>
                                        @foreach($karyawans as $karyawan)
                                            <option value="{{ $karyawan->absen_karyawan_id }}">{{ $karyawan->nik }} - {{ $karyawan->nama_lengkap }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Company:</label>
                                    <select class="form-select form-select-solid" id="filter_company">
                                        <option value="">Semua Company</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->absen_company_id }}">{{ $company->code }} - {{ $company->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-5">
                                    <label class="form-label fw-semibold">Salary Type:</label>
                                    <select class="form-select form-select-solid" id="filter_salary_type">
                                        <option value="">Semua Type</option>
                                        <option value="gross">GROSS</option>
                                        <option value="nett">NETT</option>
                                    </select>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light btn-active-light-primary me-2" id="kt_modal_filter_reset">Reset</button>
                                    <button type="button" class="btn btn-primary" id="kt_modal_filter_apply">Apply</button>
                                </div>
                            </div>
                        </div>
                        <!--end::Filter-->
                        
                        <!--begin::Export-->
                       <button type="button" class="btn btn-light-primary" id="btn_export">
                            <i class="bi bi-download fs-3"></i>
                            Export Excel
                        </button>
                        <!--end::Export-->
                    </div>
                </div>
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_datatable">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">No</th>
                            <th class="min-w-100px">Periode</th>
                            <th class="min-w-150px">Karyawan</th>
                            <th class="min-w-150px">Company</th>
                            <th class="min-w-80px">Type</th>
                            <th class="min-w-100px text-end">Salary</th>
                            <th class="min-w-100px text-end">Overtime</th>
                            <th class="min-w-100px text-end">Tunjangan</th>
                            <th class="min-w-100px text-end">Natura</th>
                            <th class="min-w-100px text-end">Tunj.PPH 21</th>
                            <th class="min-w-100px text-end">Tunj.Asuransi</th>
                            <th class="min-w-100px text-end">BPJS Asuransi</th>
                            <th class="min-w-100px text-end">THR Bonus</th>
                            <th class="min-w-100px text-end">Total Bruto</th>
                            <th class="min-w-80px text-center">Masa Jabatan</th>
                            <th class="min-w-100px text-end">Premi Asuransi</th>
                            <th class="min-w-100px text-end">Biaya Jabatan</th>
                            <th class="min-w-100px text-end">Kriteria</th>
                            <th class="min-w-100px text-end">Besaran PTKP</th>
                            <th class="min-w-100px text-end">PKP</th>
                            <th class="text-end min-w-70px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
</div>
<!--end::Post-->
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 untuk semua filter dengan dropdownParent yang benar
    $('#filter_karyawan').select2({
        placeholder: "Pilih Karyawan",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_karyawan').parent() // Fix: gunakan parent langsung
    });

    $('#filter_company').select2({
        placeholder: "Pilih Company",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_company').parent() // Fix: gunakan parent langsung
    });

    $('#filter_periode').select2({
        placeholder: "Pilih Periode",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_periode').parent() // Fix: gunakan parent langsung
    });

    $('#filter_salary_type').select2({
        placeholder: "Pilih Salary Type",
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_salary_type').parent() // Fix: gunakan parent langsung
    });

    // Initialize DataTable
    var table = $('#kt_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("periode-karyawan.datatables") }}',
            data: function(d) {
                d.periode = $('#filter_periode').val();
                d.karyawan_id = $('#filter_karyawan').val();
                d.company_id = $('#filter_company').val();
                d.salary_type = $('#filter_salary_type').val();
                d.search = $('#search').val();
            }
        },
        drawCallback: function() {
            // Initialize tooltip setelah table di-render
            $('[data-bs-toggle="tooltip"]').tooltip({
                html: true,
                trigger: 'hover',
                boundary: 'window'
            });
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'periode_with_tooltip', name: 'periode', orderable: true },
            { data: 'karyawan_info', name: 'karyawan_info', orderable: false },
            { data: 'company_info', name: 'company_info', orderable: false },
            { data: 'salary_type_badge', name: 'salary_type' },
            { data: 'formatted_salary', name: 'salary', className: 'text-end' },
            { data: 'formatted_overtime', name: 'overtime', className: 'text-end' },
            { data: 'formatted_tunjangan', name: 'tunjangan', className: 'text-end' },
            { data: 'formatted_natura', name: 'natura', className: 'text-end' },
            { data: 'formatted_tunj_pph_21', name: 'tunj_pph_21', className: 'text-end' },
            { data: 'formatted_tunjangan_asuransi', name: 'tunjangan_asuransi', className: 'text-end' },
            { data: 'formatted_bpjs_asuransi', name: 'bpjs_asuransi', className: 'text-end' },
            { data: 'formatted_thr_bonus', name: 'thr_bonus', className: 'text-end' },
            { data: 'formatted_total_bruto', name: 'total_bruto', className: 'text-end' },
            { data: 'masa_jabatan', name: 'masa_jabatan', className: 'text-center' },
            { data: 'formatted_premi_asuransi', name: 'premi_asuransi', className: 'text-end' },
            { data: 'formatted_biaya_jabatan', name: 'biaya_jabatan', className: 'text-end' },
            { data: 'kriteria', name: 'kriteria', className: 'text-end' },
            { data: 'formatted_besaran_ptkp', name: 'besaran_ptkp', className: 'text-end' },
            { data: 'formatted_pkp', name: 'pkp', className: 'text-end' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        searching: false
    });
    
    // Custom Search dengan debounce
    var searchTimeout;
    $('#search').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            table.draw();
        }, 500);
    });
    
    // Apply filter
    $('#kt_modal_filter_apply').on('click', function() {
        table.draw();
    });
    
    // Reset filter
    $('#kt_modal_filter_reset').on('click', function() {
        $('#filter_periode').val('').trigger('change');
        $('#filter_karyawan').val('').trigger('change');
        $('#filter_company').val('').trigger('change');
        $('#filter_salary_type').val('').trigger('change');
        table.draw();
    });

    // Export Excel dengan filter yang sama
    $('#btn_export').on('click', function(e) {
        e.preventDefault();
        
        // Show loading
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
            periode: $('#filter_periode').val() || '',
            karyawan_id: $('#filter_karyawan').val() || '',
            company_id: $('#filter_company').val() || '',
            salary_type: $('#filter_salary_type').val() || '',
            search: $('#search').val() || ''
        });

        var exportUrl = '{{ route("periode-karyawan.export") }}?' + params.toString();

        // Create temporary link untuk download
        var link = document.createElement('a');
        link.href = exportUrl;
        link.download = 'periode_karyawan_masa_jabatan.xlsx';
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
});
</script>
@endpush