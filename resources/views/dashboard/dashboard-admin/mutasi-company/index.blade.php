@extends('layouts.master')

@section('content')
<!--begin::Card-->
<div class="card">
    <!--begin::Card header-->
    <div class="card-header border-0 pt-6">
        <!--begin::Card title-->
        <div class="card-title">
            <h2 class="fw-bold">Matrix Mutasi Company {{ $tahun }}</h2>
            <span class="text-muted fs-7 ms-2">Karyawan dengan perpindahan company</span>
        </div>
        <!--end::Card title-->
        
        <!--begin::Card toolbar-->
        <div class="card-toolbar">
            <!--begin::Toolbar-->
            <div class="d-flex justify-content-end align-items-center gap-2">
                <!--begin::Filter tahun-->
                <select class="form-select form-select-sm w-150px" id="filterTahun">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ $year == $tahun ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
                <!--end::Filter tahun-->
                
                {{-- <!--begin::Export-->
                <button type="button" class="btn btn-sm btn-light-primary" id="btnExport">
                    <i class="ki-outline ki-exit-up fs-2"></i>
                    Export Excel
                </button>
                <!--end::Export--> --}}
            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Card toolbar-->
    </div>
    <!--end::Card header-->
    
    <!--begin::Card body-->
    <div class="card-body pt-0">
        <!--begin::Table-->
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3" id="kt_mutasi_table">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="min-w-50px ps-4">NIK</th>
                        <th class="min-w-200px">Nama Karyawan</th>
                        <th class="min-w-80px text-center">Total<br>Mutasi</th>
                        @foreach($months as $month)
                            <th class="min-w-120px text-center">{{ $month['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                </tbody>
            </table>
        </div>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->

<!--begin::Modal Detail-->
<div class="modal fade" id="kt_modal_detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="modalTitle">Detail Mutasi</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7" id="modalContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>
<!--end::Modal Detail-->
@endsection

@push('styles')
<style>
    /* Make table scrollable horizontally */
    #kt_mutasi_table {
        white-space: nowrap;
    }
    
    /* Sticky first columns */
    #kt_mutasi_table thead th:nth-child(1),
    #kt_mutasi_table thead th:nth-child(2),
    #kt_mutasi_table tbody td:nth-child(1),
    #kt_mutasi_table tbody td:nth-child(2) {
        position: sticky;
        background-color: #fff;
        z-index: 10;
    }
    
    #kt_mutasi_table thead th:nth-child(1),
    #kt_mutasi_table tbody td:nth-child(1) {
        left: 0;
    }
    
    #kt_mutasi_table thead th:nth-child(2),
    #kt_mutasi_table tbody td:nth-child(2) {
        left: 80px;
    }
    
    #kt_mutasi_table thead th {
        background-color: #f5f8fa !important;
    }
    
    /* Badge styling */
    .badge {
        font-weight: 500;
        padding: 0.5rem 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script>
"use strict";

var KTMutasiCompany = function () {
    var table;
    var datatable;
    var currentTahun = {{ $tahun }};
    var monthsConfig = @json($months);
    
    // Generate consistent color for company
    var getCompanyColor = function(companyName) {
        var colors = ['primary', 'success', 'info', 'warning', 'danger', 'dark'];
        var hash = 0;
        for (var i = 0; i < companyName.length; i++) {
            hash = companyName.charCodeAt(i) + ((hash << 5) - hash);
        }
        var index = Math.abs(hash) % colors.length;
        return colors[index];
    };

    // Init datatable
    var initDatatable = function () {
        // Build columns dynamically
        var columns = [
            { data: 'nik', name: 'nik', className: 'ps-4' },
            { data: 'nama_lengkap', name: 'nama_lengkap' },
            { 
                data: 'total_mutasi_badge', 
                name: 'total_mutasi',
                className: 'text-center',
                orderable: true
            }
        ];
        
        // Add month columns dynamically
        monthsConfig.forEach(function(month) {
            columns.push({
                data: 'months_data',
                name: 'month_' + month.num,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    if (data && data[month.num]) {
                        var companyCode = data[month.num].company_code || '-';
                        var color = getCompanyColor(companyCode);
                        return '<span class="badge badge-light-' + color + ' fs-8">' + companyCode + '</span>';
                    }
                    return '<span class="text-muted fs-9">-</span>';
                }
            });
        });
        
        datatable = $('#kt_mutasi_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('mutasicompanies.data') }}",
                data: function (d) {
                    d.tahun = currentTahun;
                }
            },
            columns: columns,
            order: [[2, 'desc']], // Sort by total mutasi DESC
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            scrollX: true,
            scrollCollapse: true,
            fixedColumns: {
                leftColumns: 2
            },
            language: {
                processing: '<div class="d-flex justify-content-center"><span class="spinner-border spinner-border-sm align-middle ms-2"></span> Loading data...</div>',
                emptyTable: "Tidak ada data mutasi company untuk tahun " + currentTahun,
                zeroRecords: "Tidak ditemukan data yang sesuai",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ karyawan",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 karyawan",
                infoFiltered: "(difilter dari _MAX_ total karyawan)",
                lengthMenu: "Tampilkan _MENU_ karyawan",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            drawCallback: function() {
                KTMenu.createInstances();
            }
        });

        table = datatable.$;
    }

    // Handle filter tahun
    var handleFilterTahun = function() {
        $('#filterTahun').on('change', function() {
            currentTahun = $(this).val();
            
            // Reload page untuk update month columns
            window.location.href = '{{ route("manage-mutasicompany.index") }}?tahun=' + currentTahun;
        });
    }

    // Handle export
    var handleExport = function() {
        $('#btnExport').on('click', function() {
            Swal.fire({
                text: "Export functionality will be implemented",
                icon: "info",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });
        });
    }

    // Public methods
    return {
        init: function () {
            initDatatable();
            handleFilterTahun();
            handleExport();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTMutasiCompany.init();
});

// Global function untuk show detail (dipanggil dari button)
function showDetail(karyawanId, namaKaryawan) {
    var modal = new bootstrap.Modal(document.getElementById('kt_modal_detail'));
    
    $('#modalTitle').text('Detail Mutasi: ' + namaKaryawan);
    $('#modalContent').html('<div class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</div>');
    
    $.ajax({
        url: '/manage-mutasicompany/' + karyawanId,
        data: { tahun: currentTahun },
        success: function(response) {
            var html = '<div class="timeline">';
            
            $.each(response.mutasi_history, function(index, item) {
                var month = moment(item.periode + '-01').format('MMMM YYYY');
                var companyDisplay = item.company_code ? item.company_code + ' - ' + item.company_name : item.company_name;
                html += `
                    <div class="timeline-item mb-5">
                        <div class="timeline-line w-40px"></div>
                        <div class="timeline-icon symbol symbol-circle symbol-40px">
                            <div class="symbol-label bg-light">
                                <i class="ki-outline ki-abstract-26 fs-2 text-gray-500"></i>
                            </div>
                        </div>
                        <div class="timeline-content mb-10 mt-n1">
                            <div class="pe-3 mb-5">
                                <div class="fs-5 fw-semibold mb-2">${companyDisplay}</div>
                                <div class="d-flex align-items-center mt-1 fs-6">
                                    <div class="text-muted me-2 fs-7">${month}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $('#modalContent').html(html);
        }
    });
    
    modal.show();
}
</script>
@endpush