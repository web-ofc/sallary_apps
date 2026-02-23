@extends('layouts.master')

@section('title', 'Payroll Management')

@push('css')
<style>
    #payrollTablePending tbody td, th,
    #payrollTableReleased tbody td,
    #payrollTableReleasedSlip tbody td {
        padding: 6px 6px !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
    }
    
    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }
    
    .nav-tabs .nav-link.active {
        color: #fff;
        background-color: #009ef7;
        border-color: #009ef7;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-root">
    <div class="page d-flex flex-row flex-column-fluid">
        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

                <!-- Toolbar -->
                <div class="toolbar" id="kt_toolbar">
                    <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack mb-5">
                        <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
                            <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">
                                Payroll Management
                                <span class="h-20px border-gray-300 border-start ms-3 mx-2"></span>
                                <small class="text-muted fs-7 fw-semibold my-1 ms-1">Kelola data payroll karyawan</small>
                            </h1>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('payrolls.import') }}" class="btn btn-sm btn-light-success">
                                <i class="ki-outline ki-file-up fs-3"></i> Import Excel
                            </a>
                            <a href="{{ route('payrolls.create') }}" class="btn btn-sm btn-primary">
                                <i class="ki-outline ki-plus fs-3"></i> Tambah Payroll
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" class="container-fluid">

                        <!-- Statistics Cards -->
                        <div class="row g-5 mb-5">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body py-4">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <label class="fs-6 fw-semibold text-gray-700 text-nowrap">Filter Statistik:</label>
                                            <input type="month" id="filterStatisticsPeriode" class="form-control form-control-sm form-control-solid w-200px" />
                                            <select id="filterStatisticsYear" class="form-select form-select-sm form-select-solid w-150px">
                                                <option value="">Pilih Tahun</option>
                                                @for ($year = date('Y'); $year >= 2020; $year--)
                                                    <option value="{{ $year }}">{{ $year }}</option>
                                                @endfor
                                            </select>
                                            <button type="button" class="btn btn-sm btn-light-primary" id="btnResetStatistics">
                                                <i class="ki-outline ki-arrows-circle fs-6"></i> Reset
                                            </button>
                                            <span class="text-muted fs-7 ms-auto">
                                                <i class="ki-outline ki-information-5 fs-6 me-1"></i>
                                                Pilih bulan atau tahun untuk melihat statistik
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="symbol symbol-50px me-4">
                                            <div class="symbol-label bg-light-warning">
                                                <i class="ki-outline ki-time text-warning fs-2x"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="text-gray-600 fs-7 fw-semibold d-block mb-1">Payroll Pending</span>
                                            <div class="d-flex align-items-baseline">
                                                <span class="fs-2x fw-bold text-gray-900 me-2" id="statPendingCount">{{ $pendingCount }}</span>
                                                <span class="text-gray-500 fs-6 fw-semibold">Payroll</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="symbol symbol-50px me-4">
                                            <div class="symbol-label bg-light-info">
                                                <i class="ki-outline ki-check text-info fs-2x"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="text-gray-600 fs-7 fw-semibold d-block mb-1">Payroll Released</span>
                                            <div class="d-flex align-items-baseline">
                                                <span class="fs-2x fw-bold text-gray-900 me-2" id="statReleasedCount">{{ $releasedCount }}</span>
                                                <span class="text-gray-500 fs-6 fw-semibold">Payroll</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="symbol symbol-50px me-4">
                                            <div class="symbol-label bg-light-success">
                                                <i class="ki-outline ki-double-check text-success fs-2x"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="text-gray-600 fs-7 fw-semibold d-block mb-1">Released + Slip</span>
                                            <div class="d-flex align-items-baseline">
                                                <span class="fs-2x fw-bold text-gray-900 me-2" id="statReleasedSlipCount">{{ $releasedSlipCount }}</span>
                                                <span class="text-gray-500 fs-6 fw-semibold">Payroll</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Card -->
                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                                    @if($pendingCount > 0)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#tab_pending" role="tab">
                                            <i class="ki-outline ki-time fs-2 me-2"></i>
                                            Pending (<span id="tabPendingCount">{{ $pendingCount }}</span>)
                                        </a>
                                    </li>
                                    @endif

                                    @if($releasedCount > 0)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ $pendingCount == 0 ? 'active' : '' }}" data-bs-toggle="tab" href="#tab_released" role="tab">
                                            <i class="ki-outline ki-check fs-2 me-2"></i>
                                            Released (<span id="tabReleasedCount">{{ $releasedCount }}</span>)
                                        </a>
                                    </li>
                                    @endif

                                    @if($releasedSlipCount > 0)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ $pendingCount == 0 && $releasedCount == 0 ? 'active' : '' }}" data-bs-toggle="tab" href="#tab_released_slip" role="tab">
                                            <i class="ki-outline ki-double-check fs-2 me-2"></i>
                                            Released Slip (<span id="tabReleasedSlipCount">{{ $releasedSlipCount }}</span>)
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </div>

                            <div class="card-body pt-0 mt-3">
                                <div class="tab-content" id="payrollTabContent">

                                    @if($pendingCount > 0)
                                    <div class="tab-pane fade show active" id="tab_pending" role="tabpanel">
                                        @include('dashboard.dashboard-admin.payrolls._table_pending')
                                    </div>
                                    @endif

                                    @if($releasedCount > 0)
                                    <div class="tab-pane fade {{ $pendingCount == 0 ? 'show active' : '' }}" id="tab_released" role="tabpanel">
                                        @include('dashboard.dashboard-admin.payrolls._table_released')
                                    </div>
                                    @endif

                                    @if($releasedSlipCount > 0)
                                    <div class="tab-pane fade {{ $pendingCount == 0 && $releasedCount == 0 ? 'show active' : '' }}" id="tab_released_slip" role="tabpanel">
                                        @include('dashboard.dashboard-admin.payrolls._table_released_slip')
                                    </div>
                                    @endif

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Release Confirm -->
<div class="modal fade" id="modalReleaseConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white"><i class="ki-outline ki-information-5 fs-2 me-2"></i>Konfirmasi Release Payroll</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center mb-4">
                    <i class="ki-outline ki-shield-tick fs-2x text-warning me-3"></i>
                    <div><strong>Peringatan!</strong><br>Payroll yang sudah dirilis tidak dapat diedit lagi.</div>
                </div>
                <p class="fs-6 mb-3">Anda akan merilis <strong><span id="releaseCountText">0</span> payroll</strong>.</p>
                <div class="form-check form-check-custom form-check-solid mb-3">
                    <input class="form-check-input" type="checkbox" id="releaseSlipCheck" />
                    <label class="form-check-label fw-semibold" for="releaseSlipCheck">
                        <i class="ki-outline ki-double-check text-primary me-2"></i>
                        Langsung release slip gaji
                        <span class="text-muted fs-7 d-block mt-1">Jika tidak dicentang, hanya akan release payroll</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="btnConfirmRelease">
                    <i class="ki-outline ki-check-circle fs-2"></i> Ya, Release Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Release Slip Confirm -->
<div class="modal fade" id="modalReleaseSlipConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="ki-outline ki-double-check fs-2 me-2"></i>Konfirmasi Release Slip Gaji</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary d-flex align-items-center mb-4">
                    <i class="ki-outline ki-notification-on fs-2x text-primary me-3"></i>
                    <div><strong>Informasi</strong><br>Slip gaji akan tersedia untuk karyawan setelah dirilis.</div>
                </div>
                <p class="fs-6 mb-3">Anda akan merilis slip untuk <strong><span id="releaseSlipCountText">0</span> payroll</strong>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnConfirmReleaseSlip">
                    <i class="ki-outline ki-double-check fs-2"></i> Ya, Release Slip Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delete Confirm -->
<div class="modal fade" id="modalDeleteConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="ki-outline ki-trash fs-2 me-2"></i>Konfirmasi Hapus Payroll</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="ki-outline ki-information-5 fs-2x text-danger me-3"></i>
                    <div><strong>Peringatan!</strong><br>Data yang dihapus tidak dapat dikembalikan.</div>
                </div>
                <p class="fs-6 mb-0">Anda yakin ingin menghapus payroll periode <strong id="deletePeriodeText">-</strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="ki-outline ki-trash fs-2"></i> Ya, Hapus Sekarang
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/payrolls.js') }}"></script>
<script>
// Pass Laravel routes ke JS (minimal, cukup di sini)
window.PAYROLL_CONFIG = {
    routes: {
        pending:      '{{ route("payrollsdatatablepending") }}',
        released:     '{{ route("payrollsdatatablereleased") }}',
        releasedSlip: '{{ route("payrollsdatatablereleasedslip") }}',
        release:      '{{ route("payrolls.release") }}',
        destroy:      '{{ route("payrolls.destroy", ":id") }}',
        statistics:   '{{ route("payrolls.statistics") }}',
        export:       '{{ route("payrolls.export") }}',
        downloadZip: '{{ route("payrolls.download-pdf-zip") }}',

    },
    csrf: '{{ csrf_token() }}'
};
</script>
@endpush