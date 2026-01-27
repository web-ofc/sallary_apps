@extends('layouts.master')

@section('title', 'PPh21 Masa per Company & Periode')

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
@endpush

@section('content')
<!--begin::Content wrapper-->
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    PPh21 Masa per Company & Periode Sudah Released
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">PPh21 Masa Company</li>
                </ul>
            </div>
            <!--end::Page title-->
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
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" id="search-input" class="form-control form-control-solid w-250px ps-13" placeholder="Cari data..." />
                        </div>
                        <!--end::Search-->
                    </div>

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end gap-3" data-kt-customer-table-toolbar="base">
                            <!--begin::Filter Company-->
                            <select id="filter-company" class="form-select form-select-solid w-200px">
                                <option value="">Semua Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->absen_company_id }}">{{ $company->company_name }}</option>
                                @endforeach
                            </select>
                            <!--end::Filter Company-->

                            <!--begin::Filter Periode (Flatpickr Month Selector)-->
                            <div class="position-relative">
                                <input type="text" id="filter-periode" class="form-control form-control-solid w-200px" placeholder="Pilih Periode" readonly />
                                <i class="ki-duotone ki-calendar fs-2 position-absolute top-50 end-0 translate-middle-y me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Filter Periode-->

                            <!--begin::Reset Button-->
                            <button type="button" id="btn-reset" class="btn btn-light-primary">
                                <i class="ki-duotone ki-arrows-circle fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Reset Filter
                            </button>
                            <!--end::Reset Button-->
                        </div>
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <!--begin::Table-->
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="pph21_table">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        #
                                    </div>
                                </th>
                                <th class="min-w-125px">Company</th>
                                <th class="min-w-100px">Periode</th>
                                <th class="min-w-100px text-end">Total Karyawan</th>
                                <th class="min-w-125px text-end">Total PPh21</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                        </tbody>
                    </table>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
    </div>
    <!--end::Content-->
</div>
<!--end::Content wrapper-->
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Flatpickr untuk filter periode
            const periodePicker = flatpickr("#filter-periode", {
                locale: "id",
                plugins: [
                    new monthSelectPlugin({
                        shorthand: false,
                        dateFormat: "Y-m",
                        altFormat: "F Y",
                        theme: "light"
                    })
                ],
                onChange: function(selectedDates, dateStr, instance) {
                    table.ajax.reload();
                },
                allowInput: false,
                disableMobile: true
            });

            // Initialize DataTable
            var table = $('#pph21_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('pph21companyperiode.data') }}",
                    data: function(d) {
                        d.company_id = $('#filter-company').val();
                        d.periode = $('#filter-periode').val(); // Format: YYYY-MM
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'company_name', name: 'company_name' },
                    { data: 'periode', name: 'periode' },
                    { data: 'total_karyawan', name: 'total_karyawan', className: 'text-end' },
                    { data: 'total_pph21_masa', name: 'total_pph21_masa', className: 'text-end' },
                ],
                order: [[2, 'desc']], // Order by periode descending
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    processing: '<span class="spinner-border spinner-border-sm align-middle ms-2"></span> Memuat data...',
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    emptyTable: "Tidak ada data tersedia",
                    zeroRecords: "Tidak ada data yang cocok",
                    search: "Cari:",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
            });

            // Custom search
            $('#search-input').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Filter by company
            $('#filter-company').on('change', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#btn-reset').on('click', function() {
                // Reset company filter
                $('#filter-company').val('').trigger('change');
                
                // Reset flatpickr periode
                periodePicker.clear();
                
                // Reset search
                $('#search-input').val('');
                
                // Reload table
                table.search('').ajax.reload();
            });
        });
    </script>
@endpush