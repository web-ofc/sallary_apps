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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
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
                            <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Payroll Management
                                <span class="h-20px border-gray-300 border-start ms-3 mx-2"></span>
                                <small class="text-muted fs-7 fw-semibold my-1 ms-1">Kelola data payroll karyawan</small>
                            </h1>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('payrolls.import') }}" class="btn btn-sm btn-light-success">
                                <i class="ki-outline ki-file-up fs-3"></i>
                                Import Excel
                            </a>
                            <a href="{{ route('payrolls.create') }}" class="btn btn-sm btn-primary">
                                <i class="ki-outline ki-plus fs-3"></i>
                                Tambah Payroll
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Post -->
                <div class="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" class="container-fluid">

                        <!--begin::Statistics Cards-->
                        <div class="row g-5 mb-5">
                            <!-- Filter Periode untuk Cards -->
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body py-4">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <label class="fs-6 fw-semibold text-gray-700 text-nowrap">Filter Statistik:</label>
                                            <input type="text" id="filterStatisticsPeriode" class="form-control form-control-sm form-control-solid w-200px" placeholder="Pilih Bulan" readonly />
                                            <select id="filterStatisticsYear" class="form-select form-select-sm form-select-solid w-150px">
                                                <option value="">Pilih Tahun</option>
                                                @for ($year = date('Y'); $year >= 2020; $year--)
                                                    <option value="{{ $year }}">{{ $year }}</option>
                                                @endfor
                                            </select>
                                            <button type="button" class="btn btn-sm btn-light-primary" id="btnResetStatistics">
                                                <i class="ki-outline ki-arrows-circle fs-6"></i>
                                                Reset
                                            </button>
                                            <span class="text-muted fs-7 ms-auto">
                                                <i class="ki-outline ki-information-5 fs-6 me-1"></i>
                                                Pilih bulan atau tahun untuk melihat statistik
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 1: Pending -->
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
                            
                            <!-- Card 2: Released (tanpa slip) -->
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
                            
                            <!-- Card 3: Released with Slip -->
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
                        <!--end::Statistics Cards-->
                        <!--begin::Tab Card-->
                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <!-- 🔥 NAV TABS - Hanya tampil jika ada data -->
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
                                    <!-- 🔥 TAB PENDING -->
                                    <div class="tab-pane fade show active" id="tab_pending" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-5">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative">
                                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5" style="top: 50%; transform: translateY(-50%);"></i>
                                                    <input type="text" id="searchPending" class="form-control form-control-solid w-250px ps-13" placeholder="Cari..." />
                                                </div>
                                                <input type="text" id="filterPeriodePending" class="form-control form-control-solid w-150px" placeholder="Periode" readonly />
                                                <button type="button" class="btn btn-sm btn-light-primary" id="btnResetFilterPending">
                                                    <i class="ki-outline ki-arrows-circle fs-5"></i>
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-light-success" id="btnExportPending">
                                                    <i class="ki-outline ki-file-down fs-3"></i>
                                                    Export Pending
                                                </button>
                                                <button type="button" class="btn btn-success" id="btnReleaseSelected" style="display: none;">
                                                    <i class="ki-outline ki-check-circle fs-2"></i>
                                                    Release (<span id="selectedCount">0</span>)
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table id="payrollTablePending" class="table table-hover table-striped table-bordered table-sm align-middle gs-0 gy-4">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th rowspan="2" class="text-center align-middle">
                                                            <input class="form-check-input" type="checkbox" id="checkAllPending" />
                                                        </th>
                                                        <th rowspan="2" class="text-center align-middle">#</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-100px">Periode</th>
                                                        <th rowspan="2" class="text-center align-middle">NIK</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-200px">Nama Karyawan</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-150px">Company</th>
                                                        <th rowspan="2" class="text-center align-middle">Salary Type</th>
                                                        <th rowspan="2" class="text-center align-middle">Gaji Pokok</th>
                                                        <th colspan="6" class="text-center bg-warning bg-opacity-10">Monthly Insentif</th>
                                                        <th colspan="4" class="text-center bg-info bg-opacity-10">Monthly Allowance</th>
                                                        <th colspan="3" class="text-center bg-success bg-opacity-10">Yearly Benefit</th>
                                                        <th colspan="6" class="text-center bg-danger bg-opacity-10">Potongan</th>
                                                        <th colspan="6" class="text-center bg-primary bg-opacity-10">BPJS TK</th>
                                                        <th colspan="2" class="text-center bg-secondary bg-opacity-10">BPJS KES</th>
                                                        <th colspan="5" class="text-center bg-secondary bg-opacity-10">Lainnya</th>
                                                        <th colspan="4" class="text-center bg-dark bg-opacity-10">Summary</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-120px">Actions</th>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Monthly KPI</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Overtime</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Medical</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Insentif Sholat</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Monthly Bonus</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Rapel</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Pulsa</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Kehadiran</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Transport</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Lainnya</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">Yearly Bonus</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">THR</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">Other</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Corporate</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Personal</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Kehadiran</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">BPJS TK</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">BPJS Kes</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">PPh 21 Deduction</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JHT 3.7%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JHT 2%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JKK 0.24%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JKM 0.3%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JP 2%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JP 1%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Kes 4%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Kes 1%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">PPh 21</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">GLH</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">LM</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Lainnya</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Tunjangan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Salary</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Total Penerimaan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Total Potongan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Gaji Bersih</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                    @if($releasedCount > 0)
                                    <!-- 🔥 TAB RELEASED -->
                                    <div class="tab-pane fade {{ $pendingCount == 0 ? 'show active' : '' }}" id="tab_released" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-5">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative">
                                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5" style="top: 50%; transform: translateY(-50%);"></i>
                                                    <input type="text" id="searchReleased" class="form-control form-control-solid w-250px ps-13" placeholder="Cari..." />
                                                </div>
                                                <input type="text" id="filterPeriodeReleased" class="form-control form-control-solid w-150px" placeholder="Periode" readonly />
                                                <button type="button" class="btn btn-sm btn-light-primary" id="btnResetFilterReleased">
                                                    <i class="ki-outline ki-arrows-circle fs-5"></i>
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-light-success" id="btnExportReleased">
                                                    <i class="ki-outline ki-file-down fs-3"></i>
                                                    Export Released
                                                </button>
                                                <button type="button" class="btn btn-primary" id="btnReleaseSlipSelected" style="display: none;">
                                                    <i class="ki-outline ki-double-check fs-2"></i>
                                                    Release Slip (<span id="selectedReleasedCount">0</span>)
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table id="payrollTableReleased" class="table table-hover table-striped table-bordered table-sm align-middle gs-0 gy-4">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th rowspan="2" class="text-center align-middle">
                                                            <input class="form-check-input" type="checkbox" id="checkAllReleased" />
                                                        </th>
                                                        <th rowspan="2" class="text-center align-middle">#</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-100px">Periode</th>
                                                        <th rowspan="2" class="text-center align-middle">NIK</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-200px">Nama Karyawan</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-150px">Company</th>
                                                        <th rowspan="2" class="text-center align-middle">Salary Type</th>
                                                        <th rowspan="2" class="text-center align-middle">Gaji Pokok</th>
                                                        <th colspan="6" class="text-center bg-warning bg-opacity-10">Monthly Insentif</th>
                                                        <th colspan="4" class="text-center bg-info bg-opacity-10">Monthly Allowance</th>
                                                        <th colspan="3" class="text-center bg-success bg-opacity-10">Yearly Benefit</th>
                                                        <th colspan="6" class="text-center bg-danger bg-opacity-10">Potongan</th>
                                                        <th colspan="6" class="text-center bg-primary bg-opacity-10">BPJS TK</th>
                                                        <th colspan="2" class="text-center bg-secondary bg-opacity-10">BPJS KES</th>
                                                        <th colspan="5" class="text-center bg-secondary bg-opacity-10">Lainnya</th>
                                                        <th colspan="4" class="text-center bg-dark bg-opacity-10">Summary</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-120px">Actions</th>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Monthly KPI</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Overtime</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Medical</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Insentif Sholat</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Monthly Bonus</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Rapel</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Pulsa</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Kehadiran</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Transport</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Lainnya</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">Yearly Bonus</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">THR</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">Other</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Corporate</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Personal</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Kehadiran</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">BPJS TK</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">BPJS Kes</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">PPh 21 Deduction</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JHT 3.7%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JHT 2%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JKK 0.24%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JKM 0.3%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JP 2%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JP 1%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Kes 4%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Kes 1%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">PPh 21</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">GLH</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">LM</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Lainnya</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Tunjangan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Salary</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Total Penerimaan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Total Potongan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Gaji Bersih</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    @if($releasedSlipCount > 0)
                                    <!-- 🔥 TAB RELEASED SLIP -->
                                    <div class="tab-pane fade {{ $pendingCount == 0 && $releasedCount == 0 ? 'show active' : '' }}" id="tab_released_slip" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-5">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative">
                                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5" style="top: 50%; transform: translateY(-50%);"></i>
                                                    <input type="text" id="searchReleasedSlip" class="form-control form-control-solid w-250px ps-13" placeholder="Cari..." />
                                                </div>
                                                <input type="text" id="filterPeriodeReleasedSlip" class="form-control form-control-solid w-150px" placeholder="Periode" readonly />
                                            </div>
                                            <button type="button" class="btn btn-sm btn-light-success" id="btnExportReleasedSlip">
                                                <i class="ki-outline ki-file-down fs-3"></i>
                                                Export
                                            </button>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table id="payrollTableReleasedSlip" class="table table-hover table-striped table-bordered table-sm align-middle gs-0 gy-4">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th rowspan="2" class="text-center align-middle">#</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-100px">Periode</th>
                                                        <th rowspan="2" class="text-center align-middle">NIK</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-200px">Nama Karyawan</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-150px">Company</th>
                                                        <th rowspan="2" class="text-center align-middle">Salary Type</th>
                                                        <th rowspan="2" class="text-center align-middle">Gaji Pokok</th>
                                                        <th colspan="6" class="text-center bg-warning bg-opacity-10">Monthly Insentif</th>
                                                        <th colspan="4" class="text-center bg-info bg-opacity-10">Monthly Allowance</th>
                                                        <th colspan="3" class="text-center bg-success bg-opacity-10">Yearly Benefit</th>
                                                        <th colspan="6" class="text-center bg-danger bg-opacity-10">Potongan</th>
                                                        <th colspan="6" class="text-center bg-primary bg-opacity-10">BPJS TK</th>
                                                        <th colspan="2" class="text-center bg-secondary bg-opacity-10">BPJS KES</th>
                                                        <th colspan="5" class="text-center bg-secondary bg-opacity-10">Lainnya</th>
                                                        <th colspan="4" class="text-center bg-dark bg-opacity-10">Summary</th>
                                                        <th rowspan="2" class="text-center align-middle min-w-120px">Actions</th>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Monthly KPI</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Overtime</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Medical</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Insentif Sholat</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Monthly Bonus</th>
                                                        <th class="text-center min-w-120px bg-warning bg-opacity-10">Rapel</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Pulsa</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Kehadiran</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Transport</th>
                                                        <th class="text-center min-w-120px bg-info bg-opacity-10">Tunj. Lainnya</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">Yearly Bonus</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">THR</th>
                                                        <th class="text-center min-w-120px bg-success bg-opacity-10">Other</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Corporate</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Personal</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">CA Kehadiran</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">BPJS TK</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">BPJS Kes</th>
                                                        <th class="text-center min-w-120px bg-danger bg-opacity-10">PPh 21 Deduction</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JHT 3.7%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JHT 2%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JKK 0.24%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JKM 0.3%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JP 2%</th>
                                                        <th class="text-center min-w-120px bg-primary bg-opacity-10">JP 1%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Kes 4%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Kes 1%</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">PPh 21</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">GLH</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">LM</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Lainnya</th>
                                                        <th class="text-center min-w-120px bg-secondary bg-opacity-10">Tunjangan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Salary</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Total Penerimaan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Total Potongan</th>
                                                        <th class="text-center min-w-150px bg-dark bg-opacity-10 fw-bold">Gaji Bersih</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                    
                                </div>
                            </div>
                        </div>
                        <!--end::Tab Card-->
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 🔥 MODAL: Release Confirmation -->
<div class="modal fade" id="modalReleaseConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">
                    <i class="ki-outline ki-information-5 fs-2 me-2"></i>
                    Konfirmasi Release Payroll
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center mb-4">
                    <i class="ki-outline ki-shield-tick fs-2x text-warning me-3"></i>
                    <div>
                        <strong>Peringatan!</strong><br>
                        Payroll yang sudah dirilis tidak dapat diedit lagi.
                    </div>
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
                    <i class="ki-outline ki-check-circle fs-2"></i>
                    Ya, Release Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 🔥 MODAL: Release Slip Confirmation -->
<div class="modal fade" id="modalReleaseSlipConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="ki-outline ki-double-check fs-2 me-2"></i>
                    Konfirmasi Release Slip Gaji
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary d-flex align-items-center mb-4">
                    <i class="ki-outline ki-notification-on fs-2x text-primary me-3"></i>
                    <div>
                        <strong>Informasi</strong><br>
                        Slip gaji akan tersedia untuk karyawan setelah dirilis.
                    </div>
                </div>
                <p class="fs-6 mb-3">Anda akan merilis slip untuk <strong><span id="releaseSlipCountText">0</span> payroll</strong>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnConfirmReleaseSlip">
                    <i class="ki-outline ki-double-check fs-2"></i>
                    Ya, Release Slip Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 🔥 MODAL: Delete Confirmation -->
<div class="modal fade" id="modalDeleteConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">
                    <i class="ki-outline ki-trash fs-2 me-2"></i>
                    Konfirmasi Hapus Payroll
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="ki-outline ki-information-5 fs-2x text-danger me-3"></i>
                    <div>
                        <strong>Peringatan!</strong><br>
                        Data yang dihapus tidak dapat dikembalikan.
                    </div>
                </div>
                <p class="fs-6 mb-0">Anda yakin ingin menghapus payroll periode <strong id="deletePeriodeText">-</strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="ki-outline ki-trash fs-2"></i>
                    Ya, Hapus Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
$(document).ready(function() {
    'use strict';

    // 🔥 KONFIGURASI
    const CONFIG = {
        routes: {
            pending: '{{ route("payrollsdatatablepending") }}',
            released: '{{ route("payrollsdatatablereleased") }}',
            releasedSlip: '{{ route("payrollsdatatablereleasedslip") }}',
            release: '{{ route("payrolls.release") }}',
            destroy: '{{ route("payrolls.destroy", ":id") }}',
            statistics: '{{ route("payrolls.statistics") }}',
            export: '{{ route("payrolls.export") }}'
        },
        csrfToken: '{{ csrf_token() }}'
    };
      // 🔥 INIT FLATPICKR - Filter Statistik Bulan
    $("#filterStatisticsPeriode").flatpickr({
        plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m", altFormat: "F Y" })],
        locale: "id",
        altInput: true,
        altFormat: "F Y",
        dateFormat: "Y-m",
        onChange: function(selectedDates, dateStr) {
            updateStatistics(dateStr);
        }
    });

    // 🔥 INIT SELECT - Filter Statistik Tahun
    $('#filterStatisticsYear').on('change', function() {
        const year = $(this).val();
        if (year) {
            updateStatistics(year);
        }
    });

    // 🔥 INIT FLATPICKR - Filter Periode Pending
   // 🔥 INIT FLATPICKR - Filter Periode Pending
if ($("#filterPeriodePending").length) {
    $("#filterPeriodePending").flatpickr({
        plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m", altFormat: "F Y" })],
        locale: "id",
        altInput: true,
        altFormat: "F Y",
        dateFormat: "Y-m",
        onChange: function(selectedDates, dateStr) {
            if (tablePending) tablePending.ajax.reload();
        }
    });
}

// 🔥 INIT FLATPICKR - Filter Periode Released
if ($("#filterPeriodeReleased").length) {
    $("#filterPeriodeReleased").flatpickr({
        plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m", altFormat: "F Y" })],
        locale: "id",
        altInput: true,
        altFormat: "F Y",
        dateFormat: "Y-m",
        onChange: function(selectedDates, dateStr) {
            if (tableReleased) tableReleased.ajax.reload();
        }
    });
}

// 🔥 INIT FLATPICKR - Filter Periode Released Slip
if ($("#filterPeriodeReleasedSlip").length) {
    $("#filterPeriodeReleasedSlip").flatpickr({
        plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m", altFormat: "F Y" })],
        locale: "id",
        altInput: true,
        altFormat: "F Y",
        dateFormat: "Y-m",
        onChange: function(selectedDates, dateStr) {
            if (tableReleasedSlip) tableReleasedSlip.ajax.reload();
        }
    });
}

    // 🔥 STATE MANAGEMENT
    let selectedIds = [];
    let selectedReleasedIds = [];
    let deletePayrollId = null;
    let tablePending, tableReleased, tableReleasedSlip;

    // 🔥 UTILITY: Format Rupiah
    function formatRupiah(value) {
        if (!value || value === 0 || value === '0') return '-';
        const number = typeof value === 'string' ? parseFloat(value) : value;
        return 'Rp ' + number.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    // 🔥 UTILITY: Show Toast
    function showToast(message, type = 'success') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 3000
        };
        toastr[type](message);
    }

    // 🔥 UTILITY: Update Statistics
    function updateStatistics(periode = null) {
        const params = periode ? `?periode=${periode}` : '';
        
        $.ajax({
            url: CONFIG.routes.statistics + params,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken },
            success: function(response) {
                if (response.success) {
                    $('#statPendingCount').text(response.data.pending.count);
                    $('#statReleasedCount').text(response.data.released.count);
                    $('#statReleasedSlipCount').text(response.data.released_slip.count);
                    
                    $('#tabPendingCount').text(response.data.pending.count);
                    $('#tabReleasedCount').text(response.data.released.count);
                    $('#tabReleasedSlipCount').text(response.data.released_slip.count);
                }
            }
        });
    }

    // 🔥 DEFINE COLUMNS (sama untuk semua tab)
    const columns = [
        { 
            data: 'checkbox', 
            name: 'checkbox', 
            orderable: false, 
            searchable: false,
            className: 'text-center'
        },
        { 
            data: 'DT_RowIndex', 
            name: 'DT_RowIndex', 
            orderable: false, 
            searchable: false,
            className: 'text-center'
        },
        { data: 'periode', name: 'periode', className: 'text-center' },
        { data: 'karyawan_nik', name: 'karyawan.nik', className: 'text-center' },
        { data: 'karyawan_nama', name: 'karyawan.nama_lengkap' },
        { data: 'company_nama', name: 'company.company_name' },
        { data: 'salary_type', name: 'salary_type', className: 'text-center' },
        { 
            data: 'gaji_pokok_formatted', 
            name: 'gaji_pokok',
            className: 'text-end',
            render: (data) => formatRupiah(data)
        },
        // Monthly Insentif
        { data: 'monthly_kpi', name: 'monthly_kpi', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'overtime', name: 'overtime', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'medical_reimbursement', name: 'medical_reimbursement', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'insentif_sholat', name: 'insentif_sholat', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'monthly_bonus', name: 'monthly_bonus', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'rapel', name: 'rapel', className: 'text-end', render: (data) => formatRupiah(data) },
        // Monthly Allowance
        { data: 'tunjangan_pulsa', name: 'tunjangan_pulsa', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'tunjangan_kehadiran', name: 'tunjangan_kehadiran', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'tunjangan_transport', name: 'tunjangan_transport', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'tunjangan_lainnya', name: 'tunjangan_lainnya', className: 'text-end', render: (data) => formatRupiah(data) },
        // Yearly Benefit
        { data: 'yearly_bonus', name: 'yearly_bonus', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'thr', name: 'thr', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'other', name: 'other', className: 'text-end', render: (data) => formatRupiah(data) },
        // Potongan
        { data: 'ca_corporate', name: 'ca_corporate', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'ca_personal', name: 'ca_personal', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'ca_kehadiran', name: 'ca_kehadiran', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_tenaga_kerja', name: 'bpjs_tenaga_kerja', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_kesehatan', name: 'bpjs_kesehatan', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'pph_21_deduction', name: 'pph_21_deduction', className: 'text-end', render: (data) => formatRupiah(data) },
        // BPJS TK
        { data: 'bpjs_tk_jht_3_7_percent', name: 'bpjs_tk_jht_3_7_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_tk_jht_2_percent', name: 'bpjs_tk_jht_2_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_tk_jkk_0_24_percent', name: 'bpjs_tk_jkk_0_24_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_tk_jkm_0_3_percent', name: 'bpjs_tk_jkm_0_3_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_tk_jp_2_percent', name: 'bpjs_tk_jp_2_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_tk_jp_1_percent', name: 'bpjs_tk_jp_1_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        // BPJS KES
        { data: 'bpjs_kes_4_percent', name: 'bpjs_kes_4_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'bpjs_kes_1_percent', name: 'bpjs_kes_1_percent', className: 'text-end', render: (data) => formatRupiah(data) },
        // Lainnya
        { data: 'pph_21', name: 'pph_21', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'glh', name: 'glh', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'lm', name: 'lm', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'lainnya', name: 'lainnya', className: 'text-end', render: (data) => formatRupiah(data) },
        { data: 'tunjangan', name: 'tunjangan', className: 'text-end', render: (data) => formatRupiah(data) },
        // Summary
        { data: 'salary_formatted', name: 'salary', className: 'text-end fw-bold', render: (data) => formatRupiah(data) },
        { data: 'total_penerimaan_formatted', name: 'total_penerimaan', className: 'text-end fw-bold', render: (data) => formatRupiah(data) },
        { data: 'total_potongan_formatted', name: 'total_potongan', className: 'text-end fw-bold', render: (data) => formatRupiah(data) },
        { data: 'gaji_bersih_formatted', name: 'gaji_bersih', className: 'text-end fw-bold', render: (data) => formatRupiah(data) },
        { 
            data: 'action', 
            name: 'action', 
            orderable: false, 
            searchable: false,
            className: 'text-center'
        }
    ];

    // 🔥 COLUMNS RELEASED SLIP (tanpa checkbox)
    const columnsReleasedSlip = columns.filter(col => col.data !== 'checkbox');

    // 🔥 INIT DATATABLE PENDING
    if ($('#payrollTablePending').length) {
        tablePending = $('#payrollTablePending').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            ajax: {
                url: CONFIG.routes.pending,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken },
                data: function(d) {
                    d.periode = $('#filterPeriodePending').val();
                }
            },
            columns: columns,
            order: [[2, 'desc']],
            scrollCollapse: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
            scrollX: true,
            fixedColumns: {
                leftColumns: 5
            }
        });
    }

    // 🔥 INIT DATATABLE RELEASED
    if ($('#payrollTableReleased').length) {
        tableReleased = $('#payrollTableReleased').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: CONFIG.routes.released,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken },
                data: function(d) {
                    d.periode = $('#filterPeriodeReleased').val();
                }
            },
            columns: columns,
            order: [[2, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
            scrollX: true,
            fixedColumns: {
                leftColumns: 5
            }
        });
    }

    // 🔥 INIT DATATABLE RELEASED SLIP
    if ($('#payrollTableReleasedSlip').length) {
        tableReleasedSlip = $('#payrollTableReleasedSlip').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: CONFIG.routes.releasedSlip,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken },
                data: function(d) {
                    d.periode = $('#filterPeriodeReleasedSlip').val();
                }
            },
            columns: columnsReleasedSlip,
            order: [[1, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
            scrollX: true,
            fixedColumns: {
                leftColumns: 5
            }
        });
    }

    // 🔥 SEARCH PENDING
    $('#searchPending').on('keyup', function() {
        if (tablePending) {
            tablePending.search(this.value).draw();
        }
    });

   

    $('#btnResetFilterPending').on('click', function() {
    if ($('#filterPeriodePending').length && $('#filterPeriodePending')[0]._flatpickr) {
        $('#filterPeriodePending')[0]._flatpickr.clear();
    }
    $('#searchPending').val('');
    if (tablePending) {
        tablePending.search('').ajax.reload();
    }
});

    // 🔥 SEARCH RELEASED
    $('#searchReleased').on('keyup', function() {
        if (tableReleased) {
            tableReleased.search(this.value).draw();
        }
    });

   

   $('#btnResetFilterReleased').on('click', function() {
    if ($('#filterPeriodeReleased').length && $('#filterPeriodeReleased')[0]._flatpickr) {
        $('#filterPeriodeReleased')[0]._flatpickr.clear();
    }
    $('#searchReleased').val('');
    if (tableReleased) {
        tableReleased.search('').ajax.reload();
    }
});

    // 🔥 SEARCH RELEASED SLIP
    $('#searchReleasedSlip').on('keyup', function() {
        if (tableReleasedSlip) {
            tableReleasedSlip.search(this.value).draw();
        }
    });

 

    $('#btnResetFilterReleasedSlip').on('click', function() {
    if ($('#filterPeriodeReleasedSlip').length && $('#filterPeriodeReleasedSlip')[0]._flatpickr) {
        $('#filterPeriodeReleasedSlip')[0]._flatpickr.clear();
    }
    $('#searchReleasedSlip').val('');
    if (tableReleasedSlip) {
        tableReleasedSlip.search('').ajax.reload();
    }
});


    // 🔥 CHECKBOX: Check All Pending
    $('#checkAllPending').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateSelectedIds();
    });

    // 🔥 CHECKBOX: Individual Pending
    $(document).on('change', '.row-checkbox', function() {
        updateSelectedIds();
    });

    function updateSelectedIds() {
        selectedIds = $('.row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        $('#selectedCount').text(selectedIds.length);
        $('#btnReleaseSelected').toggle(selectedIds.length > 0);
        
        const allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
        $('#checkAllPending').prop('checked', allChecked);
    }

    // 🔥 CHECKBOX: Check All Released
    $('#checkAllReleased').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.row-checkbox-released').prop('checked', isChecked);
        updateSelectedReleasedIds();
    });

    // 🔥 CHECKBOX: Individual Released
    $(document).on('change', '.row-checkbox-released', function() {
        updateSelectedReleasedIds();
    });

    function updateSelectedReleasedIds() {
        selectedReleasedIds = $('.row-checkbox-released:checked').map(function() {
            return $(this).val();
        }).get();

        $('#selectedReleasedCount').text(selectedReleasedIds.length);
        $('#btnReleaseSlipSelected').toggle(selectedReleasedIds.length > 0);
        
        const allChecked = $('.row-checkbox-released').length === $('.row-checkbox-released:checked').length;
        $('#checkAllReleased').prop('checked', allChecked);
    }

    // 🔥 BTN: Release Selected (Pending → Released or Released Slip)
    $('#btnReleaseSelected').on('click', function() {
        $('#releaseCountText').text(selectedIds.length);
        $('#releaseSlipCheck').prop('checked', false);
        $('#modalReleaseConfirm').modal('show');
    });

    // 🔥 BTN: Release Slip Selected (Released → Released Slip)
    $('#btnReleaseSlipSelected').on('click', function() {
        $('#releaseSlipCountText').text(selectedReleasedIds.length);
        $('#modalReleaseSlipConfirm').modal('show');
    });

    // 🔥 CONFIRM RELEASE (from Pending)
    $('#btnConfirmRelease').on('click', function() {
        const releaseSlip = $('#releaseSlipCheck').is(':checked');
        
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        
        $.ajax({
            url: CONFIG.routes.release,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken },
            data: {
                ids: selectedIds,
                release_slip: releaseSlip ? '1' : '0'
            },
            success: function(response) {
                $('#modalReleaseConfirm').modal('hide');
                showToast(response.message, 'success');
                
                if (tablePending) tablePending.ajax.reload();
                if (tableReleased) tableReleased.ajax.reload();
                if (tableReleasedSlip) tableReleasedSlip.ajax.reload();
                
                updateStatistics();
                
                selectedIds = [];
                $('#selectedCount').text(0);
                $('#btnReleaseSelected').hide();
                $('#checkAllPending').prop('checked', false);
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal merilis payroll';
                showToast(message, 'error');
            },
            complete: function() {
                $('#btnConfirmRelease').prop('disabled', false).html('<i class="ki-outline ki-check-circle fs-2"></i> Ya, Release Sekarang');
            }
        });
    });

    // 🔥 CONFIRM RELEASE SLIP (from Released)
    $('#btnConfirmReleaseSlip').on('click', function() {
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        
        $.ajax({
            url: CONFIG.routes.release,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken },
            data: {
                ids: selectedReleasedIds,
                release_slip: '1'
            },
            success: function(response) {
                $('#modalReleaseSlipConfirm').modal('hide');
                showToast(response.message, 'success');
                
                if (tableReleased) tableReleased.ajax.reload();
                if (tableReleasedSlip) tableReleasedSlip.ajax.reload();
                
                updateStatistics();
                
                selectedReleasedIds = [];
                $('#selectedReleasedCount').text(0);
                $('#btnReleaseSlipSelected').hide();
                $('#checkAllReleased').prop('checked', false);
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal merilis slip gaji';
                showToast(message, 'error');
            },
            complete: function() {
                $('#btnConfirmReleaseSlip').prop('disabled', false).html('<i class="ki-outline ki-double-check fs-2"></i> Ya, Release Slip Sekarang');
            }
        });
    });

    // 🔥 BTN: Delete
    $(document).on('click', '.btn-delete', function() {
        deletePayrollId = $(this).data('id');
        const periode = $(this).data('periode');
        
        $('#deletePeriodeText').text(periode);
        $('#modalDeleteConfirm').modal('show');
    });

    // 🔥 CONFIRM DELETE
    $('#btnConfirmDelete').on('click', function() {
        if (!deletePayrollId) return;
        
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...');
        
        $.ajax({
            url: CONFIG.routes.destroy.replace(':id', deletePayrollId),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CONFIG.csrfToken },
            success: function(response) {
                $('#modalDeleteConfirm').modal('hide');
                showToast(response.message, 'success');
                
                if (tablePending) tablePending.ajax.reload();
                
                updateStatistics();
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal menghapus payroll';
                showToast(message, 'error');
            },
            complete: function() {
                $('#btnConfirmDelete').prop('disabled', false).html('<i class="ki-outline ki-trash fs-2"></i> Ya, Hapus Sekarang');
                deletePayrollId = null;
            }
        });
    });


    // Ganti bagian FILTER STATISTICS
    $('#btnResetStatistics').on('click', function() {
    if ($('#filterStatisticsPeriode').length && $('#filterStatisticsPeriode')[0]._flatpickr) {
        $('#filterStatisticsPeriode')[0]._flatpickr.clear();
    }
    $('#filterStatisticsYear').val('');
    updateStatistics();
});

    // 🔥 EXPORT PENDING
    $('#btnExportPending').on('click', function() {
        const periode = $('#filterPeriodePending').val();
        let url = CONFIG.routes.export + '?status=pending';
        
        if (periode) {
            url += '&periode=' + periode;
        }
        
        window.location.href = url;
    });

    // 🔥 EXPORT RELEASED (tanpa slip)
    $('#btnExportReleased').on('click', function() {
        const periode = $('#filterPeriodeReleased').val();
        let url = CONFIG.routes.export + '?status=released';
        
        if (periode) {
            url += '&periode=' + periode;
        }
        
        window.location.href = url;
    });

    // 🔥 EXPORT RELEASED SLIP
    $('#btnExportReleasedSlip').on('click', function() {
        const periode = $('#filterPeriodeReleasedSlip').val();
        let url = CONFIG.routes.export + '?status=released_slip';
        
        if (periode) {
            url += '&periode=' + periode;
        }
        
        window.location.href = url;
    });

    // 🔥 TAB SWITCH: Reload table when tab is shown
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e)
    {
        const target = $(e.target).attr('href');
        
        if (target === '#tab_pending' && tablePending) {
            tablePending.columns.adjust().draw();
        } else if (target === '#tab_released' && tableReleased) {
            tableReleased.columns.adjust().draw();
        } else if (target === '#tab_released_slip' && tableReleasedSlip) {
            tableReleasedSlip.columns.adjust().draw();
        }
    });

    // 🔥 RESPONSIVE: Adjust table on window resize
    $(window).on('resize', function() {
        if (tablePending) tablePending.columns.adjust();
        if (tableReleased) tableReleased.columns.adjust();
        if (tableReleasedSlip) tableReleasedSlip.columns.adjust();
    });
});
</script>
@endpush