@extends('layouts.master')

@section('title', 'Payroll Management')

@section('content')
<div class="d-flex flex-column flex-root">
    <div class="page d-flex flex-row flex-column-fluid">
        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            
            <!-- Content -->
            <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                <!-- Toolbar -->
                <div class="toolbar" id="kt_toolbar">
                    <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
                        <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
                            <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Payroll Management
                                <span class="h-20px border-gray-300 border-start ms-3 mx-2"></span>
                                <small class="text-muted fs-7 fw-semibold my-1 ms-1">Kelola data payroll karyawan</small>
                            </h1>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Export Button untuk Pending -->
                            <button type="button" class="btn btn-sm btn-light-success" id="btnExportPending">
                                <i class="ki-outline ki-file-down fs-3"></i>
                                Export Pending
                            </button>
                            
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
                        
                        <!--begin::Card PENDING-->
                        <div class="card mb-5" id="cardPending" style="display: {{ $pendingCount > 0 ? 'block' : 'none' }};">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold fs-3 mb-1">Payroll Pending</span>
                                        <span class="text-muted mt-1 fw-semibold fs-7">
                                            <span id="pendingCount">{{ $pendingCount }}</span> payroll menunggu release
                                        </span>
                                    </h3>
                                </div>
                                <div class="card-toolbar">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div class="position-relative">
                                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5" style="top: 50%; transform: translateY(-50%);"></i>
                                            <input type="text" id="searchPending" class="form-control form-control-solid w-250px ps-13" placeholder="Cari..." />
                                        </div>
                                        <input type="month" id="filterPeriodePending" class="form-control form-control-solid w-150px" placeholder="Periode">
                                        <button type="button" class="btn btn-success" id="btnReleaseSelected" style="display: none;">
                                            <i class="ki-outline ki-check-circle fs-2"></i>
                                            Release (<span id="selectedCount">0</span>)
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive" style="max-height: 70vh; overflow: auto;">
                                    <table id="payrollTablePending" class="table table-hover table-striped table-bordered table-sm align-middle gs-0 gy-4">
                                        <thead class="sticky-top bg-light">
                                            <tr>
                                                <th rowspan="2" class="text-center align-middle">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" id="checkAllPending" />
                                                    </div>
                                                </th>
                                                <th rowspan="2" class="text-center align-middle min-w-50px">#</th>
                                                <th rowspan="2" class="text-center align-middle min-w-100px">Periode</th>
                                                <th rowspan="2" class="text-center align-middle min-w-120px">NIK</th>
                                                <th rowspan="2" class="text-center align-middle min-w-200px">Nama Karyawan</th>
                                                <th rowspan="2" class="text-center align-middle min-w-150px">Company</th>
                                                <th rowspan="2" class="text-center align-middle min-w-100px">Salary Type</th>
                                                <th rowspan="2" class="text-center align-middle min-w-120px">Gaji Pokok</th>
                                                <th colspan="6" class="text-center bg-warning bg-opacity-10">Monthly Insentif</th>
                                                <th colspan="4" class="text-center bg-info bg-opacity-10">Monthly Allowance</th>
                                                <th colspan="3" class="text-center bg-success bg-opacity-10">Yearly Benefit</th>
                                                <th colspan="4" class="text-center bg-danger bg-opacity-10">Potongan</th>
                                                <th colspan="7" class="text-center bg-primary bg-opacity-10">BPJS TK</th>
                                                <th colspan="3" class="text-center bg-secondary bg-opacity-10">BPJS KES</th>
                                                <th colspan="4" class="text-center bg-secondary bg-opacity-10">Lainnya</th>
                                                <th colspan="4" class="text-center bg-dark bg-opacity-10">Summary</th>
                                                <th rowspan="2" class="text-center align-middle min-w-120px">Actions</th>
                                            </tr>
                                            <tr>
                                                <th class="text-end min-w-120px bg-warning bg-opacity-10">Monthly KPI</th>
                                                <th class="text-end min-w-120px bg-warning bg-opacity-10">Overtime</th>
                                                <th class="text-end min-w-120px bg-warning bg-opacity-10">Medical</th>
                                                <th class="text-end min-w-120px bg-warning bg-opacity-10">Insentif Sholat</th>
                                                <th class="text-end min-w-120px bg-warning bg-opacity-10">Monthly Bonus</th>
                                                <th class="text-end min-w-120px bg-warning bg-opacity-10">Rapel</th>
                                                <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Pulsa</th>
                                                <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Kehadiran</th>
                                                <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Transport</th>
                                                <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Lainnya</th>
                                                <th class="text-end min-w-120px bg-success bg-opacity-10">Yearly Bonus</th>
                                                <th class="text-end min-w-120px bg-success bg-opacity-10">THR</th>
                                                <th class="text-end min-w-120px bg-success bg-opacity-10">Other</th>
                                                <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Corporate</th>
                                                <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Personal</th>
                                                <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Kehadiran</th>
                                                
                                                <th class="text-end min-w-120px bg-danger bg-opacity-10">PPh 21 Deduction</th>
                                                <th class="text-end min-w-120px bg-primary bg-opacity-10">BPJS TK</th>
                                                <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JHT 3.7%</th>
                                                <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JHT 2%</th>
                                                <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JKK 0.24%</th>
                                                <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JKM 0.3%</th>
                                                <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JP 2%</th>
                                                <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JP 1%</th>
                                                <th class="text-end min-w-120px bg-secondary bg-opacity-10">BPJS Kes</th>
                                                <th class="text-end min-w-120px bg-secondary bg-opacity-10">Kes 4%</th>
                                                <th class="text-end min-w-120px bg-secondary bg-opacity-10">Kes 1%</th>
                                                <th class="text-end min-w-120px bg-secondary bg-opacity-10">PPh 21</th>
                                                <th class="text-end min-w-120px bg-secondary bg-opacity-10">GLH</th>
                                                <th class="text-end min-w-120px bg-secondary bg-opacity-10">LM</th>
                                                <th class="text-end min-w-120px bg-secondary bg-opacity-10">Lainnya</th>
                                                <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Salary</th>
                                                <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Total Penerimaan</th>
                                                <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Total Potongan</th>
                                                <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Gaji Bersih</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!--end::Card PENDING-->
                        
                        <!--begin::Card RELEASED-->
                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold fs-3 mb-1">Payroll Released</span>
                                        <span class="text-muted mt-1 fw-semibold fs-7">Data payroll yang sudah dirilis</span>
                                    </h3>
                                </div>
                                <div class="card-toolbar">
                                    <div class="d-flex justify-content-end align-items-center gap-3">
                                         <button type="button" class="btn btn-sm btn-light-success" id="btnExportReleased">
                                            <i class="ki-outline ki-file-down fs-3"></i>
                                            Export Released
                                        </button>
                                        <div class="position-relative">
                                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5" style="top: 50%; transform: translateY(-50%);"></i>
                                            <input type="text" id="searchReleased" class="form-control form-control-solid w-250px ps-13" placeholder="Cari..." />
                                        </div>
                                        <input type="month" id="filterPeriodeReleased" class="form-control form-control-solid w-150px" placeholder="Periode">
                                        <button type="button" class="btn btn-light-primary btn-sm" id="btnResetFilter">
                                            <i class="ki-outline ki-arrows-circle fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex align-items-center mb-5">
                                    <label class="fs-6 fw-semibold me-2">Show</label>
                                    <select id="entriesSelectReleased" class="form-select form-select-solid form-select-sm w-auto">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="all">All (Virtual Scroll)</option>
                                    </select>
                                    <span class="fs-6 fw-semibold ms-2">entries</span>
                                </div>
                                
                                <!-- 🔥 CONTAINER UNTUK VIRTUAL SCROLL -->
                                <div id="virtualScrollContainer" style="display: none;">
                                    <div class="table-responsive" style="max-height: 100vh; overflow-y: auto; position: relative;" id="virtualScrollWrapper">
                                        <table id="payrollTableReleasedVirtual" class="table table-hover table-striped table-bordered table-sm align-middle gs-0 gy-4">
                                            <thead class="sticky-top bg-light" style="position: sticky; top: 0; z-index: 100;">
                                                <tr>
                                                    <th rowspan="2" class="text-center align-middle min-w-50px">#</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-100px">Periode</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-120px">NIK</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-200px">Nama Karyawan</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-150px">Company</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-100px">Salary Type</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-120px">Gaji Pokok</th>
                                                    <th colspan="6" class="text-center bg-warning bg-opacity-10">Monthly Insentif</th>
                                                    <th colspan="4" class="text-center bg-info bg-opacity-10">Monthly Allowance</th>
                                                    <th colspan="3" class="text-center bg-success bg-opacity-10">Yearly Benefit</th>
                                                    <th colspan="4" class="text-center bg-danger bg-opacity-10">Potongan</th>
                                                    <th colspan="7" class="text-center bg-primary bg-opacity-10">BPJS TK</th>
                                                    <th colspan="3" class="text-center bg-secondary bg-opacity-10">BPJS KES</th>
                                                    <th colspan="4" class="text-center bg-secondary bg-opacity-10">Lainnya</th>
                                                    <th colspan="4" class="text-center bg-dark bg-opacity-10">Summary</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-120px">Actions</th>
                                                </tr>
                                                <tr>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Monthly KPI</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Overtime</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Medical</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Insentif Sholat</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Monthly Bonus</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Rapel</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Pulsa</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Kehadiran</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Transport</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Lainnya</th>
                                                    <th class="text-end min-w-120px bg-success bg-opacity-10">Yearly Bonus</th>
                                                    <th class="text-end min-w-120px bg-success bg-opacity-10">THR</th>
                                                    <th class="text-end min-w-120px bg-success bg-opacity-10">Other</th>
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Corporate</th>
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Personal</th>
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Kehadiran</th>
                                                    
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">PPh 21 Deduction</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">BPJS TK</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JHT 3.7%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JHT 2%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JKK 0.24%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JKM 0.3%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JP 2%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JP 1%</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">BPJS Kes</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">Kes 4%</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">Kes 1%</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">PPh 21</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">GLH</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">LM</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">Lainnya</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Salary</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Total Penerimaan</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Total Potongan</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Gaji Bersih</th>
                                                </tr>
                                            </thead>
                                            <tbody id="virtualScrollBody"></tbody>
                                        </table>
                                        <div id="virtualScrollLoader" style="display: none; text-align: center; padding: 20px;">
                                            <span class="spinner-border spinner-border-sm"></span> Loading more...
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 🔥 CONTAINER UNTUK DATATABLE BIASA -->
                                <div id="normalTableContainer">
                                    <div class="table-responsive" style="overflow: auto;">
                                        <table id="payrollTableReleased" class="table table-hover table-striped table-bordered table-sm align-middle gs-0 gy-4">
                                            <thead class="sticky-top bg-light">
                                                <tr>
                                                    <th rowspan="2" class="text-center align-middle min-w-50px">#</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-100px">Periode</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-120px">NIK</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-200px">Nama Karyawan</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-150px">Company</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-100px">Salary Type</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-120px">Gaji Pokok</th>
                                                    <th colspan="6" class="text-center bg-warning bg-opacity-10">Monthly Insentif</th>
                                                    <th colspan="4" class="text-center bg-info bg-opacity-10">Monthly Allowance</th>
                                                    <th colspan="3" class="text-center bg-success bg-opacity-10">Yearly Benefit</th>
                                                    <th colspan="4" class="text-center bg-danger bg-opacity-10">Potongan</th>
                                                    <th colspan="7" class="text-center bg-primary bg-opacity-10">BPJS TK</th>
                                                    <th colspan="3" class="text-center bg-secondary bg-opacity-10">BPJS KES</th>
                                                    <th colspan="4" class="text-center bg-secondary bg-opacity-10">Lainnya</th>
                                                    <th colspan="4" class="text-center bg-dark bg-opacity-10">Summary</th>
                                                    <th rowspan="2" class="text-center align-middle min-w-120px">Actions</th>
                                                </tr>
                                                <tr>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Monthly KPI</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Overtime</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Medical</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Insentif Sholat</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Monthly Bonus</th>
                                                    <th class="text-end min-w-120px bg-warning bg-opacity-10">Rapel</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Pulsa</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Kehadiran</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Transport</th>
                                                    <th class="text-end min-w-120px bg-info bg-opacity-10">Tunj. Lainnya</th>
                                                    <th class="text-end min-w-120px bg-success bg-opacity-10">Yearly Bonus</th>
                                                    <th class="text-end min-w-120px bg-success bg-opacity-10">THR</th>
                                                    <th class="text-end min-w-120px bg-success bg-opacity-10">Other</th>
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Corporate</th>
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Personal</th>
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">CA Kehadiran</th>
                                                    
                                                    <th class="text-end min-w-120px bg-danger bg-opacity-10">PPh 21 Deduction</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">BPJS TK</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JHT 3.7%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JHT 2%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JKK 0.24%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JKM 0.3%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JP 2%</th>
                                                    <th class="text-end min-w-120px bg-primary bg-opacity-10">TK JP 1%</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">BPJS Kes</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">Kes 4%</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">Kes 1%</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">PPh 21</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">GLH</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">LM</th>
                                                    <th class="text-end min-w-120px bg-secondary bg-opacity-10">Lainnya</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Salary</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Total Penerimaan</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Total Potongan</th>
                                                    <th class="text-end min-w-150px bg-dark bg-opacity-10 fw-bold">Gaji Bersih</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Konfirmasi Hapus</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <p>Apakah Anda yakin ingin menghapus payroll ini?</p>
                <p id="deleteInfo" class="text-muted fs-7 mt-3"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.table-responsive { border: 1px solid #dee2e6; border-radius: 0.375rem; }
.table { margin-bottom: 0; font-size: 0.875rem; }
.table thead th { background-color: #f5f8fa; color: #181c32; border: 1px solid #e4e6ef; font-weight: 600; white-space: nowrap;  }
.table tbody td { white-space: nowrap; vertical-align: middle; border: 1px solid #e4e6ef;  }
.table tbody tr:hover { background-color: #f9fafb; }
.min-w-50px { min-width: 50px; }
.min-w-100px { min-width: 100px; }
.min-w-120px { min-width: 120px; }
.min-w-150px { min-width: 150px; }
.min-w-200px { min-width: 200px; }
</style>
@endpush

@push('scripts')
<script>
"use strict";

var KTPayrollList = function() {
    var dtPending, dtReleased, selectedIds = [], deletePayrollId = null;
    
    // 🔥 VIRTUAL SCROLL VARIABLES
    var virtualScrollData = [];
    var virtualScrollPage = 1;
    var virtualScrollLoading = false;
    var virtualScrollHasMore = true;
    var virtualScrollMode = false;

    function formatCurrency(data) {
        if (!data || data === 0) return '-';
        if (typeof data === 'string' && data.includes('Rp')) {
            return data;
        }
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(data);
    }

    // 🔥 FUNGSI UNTUK BUILD ROW VIRTUAL SCROLL
    function buildVirtualRow(item, index) {
        return `
            <tr>
                <td class="text-center">${index}</td>
                <td class="text-center">${item.periode || '-'}</td>
                <td class="text-center">${item.karyawan_nik || '-'}</td>
                <td>${item.karyawan_nama || '-'}</td>
                <td>${item.company_nama || '-'}</td>
                <td class="text-center">${item.salary_type || '-'}</td>
                <td class="text-end">${formatCurrency(item.gaji_pokok)}</td>
                <td class="text-end">${formatCurrency(item.monthly_kpi)}</td>
                <td class="text-end">${formatCurrency(item.overtime)}</td>
                <td class="text-end">${formatCurrency(item.medical_reimbursement)}</td>
                <td class="text-end">${formatCurrency(item.insentif_sholat)}</td>
                <td class="text-end">${formatCurrency(item.monthly_bonus)}</td>
                <td class="text-end">${formatCurrency(item.rapel)}</td>
                <td class="text-end">${formatCurrency(item.tunjangan_pulsa)}</td>
                <td class="text-end">${formatCurrency(item.tunjangan_kehadiran)}</td>
                <td class="text-end">${formatCurrency(item.tunjangan_transport)}</td>
                <td class="text-end">${formatCurrency(item.tunjangan_lainnya)}</td>
                <td class="text-end">${formatCurrency(item.yearly_bonus)}</td>
                <td class="text-end">${formatCurrency(item.thr)}</td>
                <td class="text-end">${formatCurrency(item.other)}</td>
                <td class="text-end">${formatCurrency(item.ca_corporate)}</td>
                <td class="text-end">${formatCurrency(item.ca_personal)}</td>
                <td class="text-end">${formatCurrency(item.ca_kehadiran)}</td>
                
                <td class="text-end">${formatCurrency(item.pph_21_deduction)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_tenaga_kerja)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_tk_jht_3_7_percent)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_tk_jht_2_percent)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_tk_jkk_0_24_percent)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_tk_jkm_0_3_percent)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_tk_jp_2_percent)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_tk_jp_1_percent)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_kesehatan)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_kes_4_percent)}</td>
                <td class="text-end">${formatCurrency(item.bpjs_kes_1_percent)}</td>
                <td class="text-end">${formatCurrency(item.pph_21)}</td>
                <td class="text-end">${formatCurrency(item.glh)}</td>
                <td class="text-end">${formatCurrency(item.lm)}</td>
                <td class="text-end">${formatCurrency(item.lainnya)}</td>
                <td class="text-end fw-bold bg-light">${formatCurrency(item.salary)}</td>
                <td class="text-end fw-bold bg-light">${formatCurrency(item.total_penerimaan)}</td>
                <td class="text-end fw-bold bg-light">${formatCurrency(item.total_potongan)}</td>
                <td class="text-end fw-bold bg-success bg-opacity-10">${formatCurrency(item.gaji_bersih)}</td>
                <td class="text-center">
                    <a href="/payrolls/${item.id}/edit" class="btn btn-icon btn-light-primary btn-sm me-1">
                        <i class="ki-outline ki-pencil fs-5"></i>
                    </a>
                    <button type="button" class="btn btn-icon btn-light-danger btn-sm btn-delete" data-id="${item.id}" data-periode="${item.periode}">
                        <i class="ki-outline ki-trash fs-5"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    // 🔥 LOAD DATA UNTUK VIRTUAL SCROLL
    function loadVirtualScrollData() {
        if (virtualScrollLoading || !virtualScrollHasMore) return;
        
        virtualScrollLoading = true;
        $('#virtualScrollLoader').show();

        $.ajax({
            url: "{{ route('payrollsdatatablereleased') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                start: (virtualScrollPage - 1) * 50,
                length: 50,
                search: { value: $('#searchReleased').val() },
                periode: $('#filterPeriodeReleased').val()
            },
            success: function(response) {
                virtualScrollLoading = false;
                $('#virtualScrollLoader').hide();

                if (response.data && response.data.length > 0) {
                    virtualScrollData = virtualScrollData.concat(response.data);
                    
                    let html = '';
                    response.data.forEach((item, idx) => {
                        let rowIndex = (virtualScrollPage - 1) * 50 + idx + 1;
                        html += buildVirtualRow(item, rowIndex);
                    });
                    
                    $('#virtualScrollBody').append(html);
                    virtualScrollPage++;
                    
                    if (response.data.length < 50) {
                        virtualScrollHasMore = false;
                    }
                } else {
                    virtualScrollHasMore = false;
                }
            },
            error: function() {
                virtualScrollLoading = false;
                $('#virtualScrollLoader').hide();
            }
        });
    }

    // 🔥 INIT VIRTUAL SCROLL
    function initVirtualScroll() {
        virtualScrollMode = true;
        virtualScrollData = [];
        virtualScrollPage = 1;
        virtualScrollLoading = false;
        virtualScrollHasMore = true;
        
        $('#virtualScrollBody').empty();
        $('#normalTableContainer').hide();
        $('#virtualScrollContainer').show();
        
        loadVirtualScrollData();
        
        // Scroll event listener
        $('#virtualScrollWrapper').off('scroll').on('scroll', function() {
            let scrollTop = $(this).scrollTop();
            let scrollHeight = $(this)[0].scrollHeight;
            let clientHeight = $(this)[0].clientHeight;
            
            if (scrollTop + clientHeight >= scrollHeight - 100) {
                loadVirtualScrollData();
            }
        });
    }

    // 🔥 DESTROY VIRTUAL SCROLL
    function destroyVirtualScroll() {
        virtualScrollMode = false;
        $('#virtualScrollWrapper').off('scroll');
        $('#virtualScrollBody').empty();
        $('#virtualScrollContainer').hide();
        $('#normalTableContainer').show();
    }

    // ========== PENDING TABLE ==========
    var initPendingTable = function() {
        dtPending = $("#payrollTablePending").DataTable({
            processing: true, serverSide: true, pageLength: 25,
            ajax: {
                url: "{{ route('payrollsdatatablepending') }}", type: "POST",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function(d) { d.periode = $('#filterPeriodePending').val(); }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                { data: 'karyawan_nik', orderable: false },
                { data: 'karyawan_nama', orderable: false },
                { data: 'company_nama', orderable: false },
                { data: 'salary_type', className: 'text-center' },
                { data: 'gaji_pokok_formatted', className: 'text-end', render: formatCurrency },
                { data: 'monthly_kpi', className: 'text-end', render: formatCurrency },
                { data: 'overtime', className: 'text-end', render: formatCurrency },
                { data: 'medical_reimbursement', className: 'text-end', render: formatCurrency },
                { data: 'insentif_sholat', className: 'text-end', render: formatCurrency },
                { data: 'monthly_bonus', className: 'text-end', render: formatCurrency },
                { data: 'rapel', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_pulsa', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_kehadiran', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_transport', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_lainnya', className: 'text-end', render: formatCurrency },
                { data: 'yearly_bonus', className: 'text-end', render: formatCurrency },
                { data: 'thr', className: 'text-end', render: formatCurrency },
                { data: 'other', className: 'text-end', render: formatCurrency },
                { data: 'ca_corporate', className: 'text-end', render: formatCurrency },
                { data: 'ca_personal', className: 'text-end', render: formatCurrency },
                { data: 'ca_kehadiran', className: 'text-end', render: formatCurrency },
                
                { data: 'pph_21_deduction', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tenaga_kerja', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jht_3_7_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jht_2_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jkk_0_24_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jkm_0_3_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jp_2_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jp_1_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_kesehatan', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_kes_4_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_kes_1_percent', className: 'text-end', render: formatCurrency },
                { data: 'pph_21', className: 'text-end', render: formatCurrency },
                { data: 'glh', className: 'text-end', render: formatCurrency },
                { data: 'lm', className: 'text-end', render: formatCurrency },
                { data: 'lainnya', className: 'text-end', render: formatCurrency },
                { data: 'salary_formatted', className: 'text-end fw-bold bg-light', render: formatCurrency },
                { data: 'total_penerimaan_formatted', className: 'text-end fw-bold bg-light', render: formatCurrency },
                { data: 'total_potongan_formatted', className: 'text-end fw-bold bg-light', render: formatCurrency },
                { data: 'gaji_bersih_formatted', className: 'text-end fw-bold bg-success bg-opacity-10', render: formatCurrency },
                { data: 'action', orderable: false, searchable: false }
            ],
            scrollX: true,
            scrollCollapse: true,
            fixedColumns: {
                leftColumns: 5
            },
            order: [[2, 'desc']], 
            dom: '<"table-responsive"t><"row"<"col-sm-12 col-md-5"li><"col-sm-12 col-md-7"p>>', 
            scrollX: true,
            drawCallback: function() { updatePendingCount(); updateSelectedIds(); }
        });

        $('#searchPending').on('keyup', function() { dtPending.search(this.value).draw(); });
        $('#filterPeriodePending').on('change', function() { dtPending.ajax.reload(); });
    }

    // ========== RELEASED TABLE (NORMAL MODE) ==========
    var initReleasedTable = function() {
        if (dtReleased) {
            dtReleased.destroy();
        }

        dtReleased = $("#payrollTableReleased").DataTable({
            processing: true, serverSide: true,
            ajax: {
                url: "{{ route('payrollsdatatablereleased') }}", 
                type: "POST",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function(d) { d.periode = $('#filterPeriodeReleased').val(); }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                { data: 'karyawan_nik', orderable: false },
                { data: 'karyawan_nama', orderable: false },
                { data: 'company_nama', orderable: false },
                { data: 'salary_type', className: 'text-center' },
                { data: 'gaji_pokok_formatted', className: 'text-end', render: formatCurrency },
                { data: 'monthly_kpi', className: 'text-end', render: formatCurrency },
                { data: 'overtime', className: 'text-end', render: formatCurrency },
                { data: 'medical_reimbursement', className: 'text-end', render: formatCurrency },
                { data: 'insentif_sholat', className: 'text-end', render: formatCurrency },
                { data: 'monthly_bonus', className: 'text-end', render: formatCurrency },
                { data: 'rapel', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_pulsa', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_kehadiran', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_transport', className: 'text-end', render: formatCurrency },
                { data: 'tunjangan_lainnya', className: 'text-end', render: formatCurrency },
                { data: 'yearly_bonus', className: 'text-end', render: formatCurrency },
                { data: 'thr', className: 'text-end', render: formatCurrency },
                { data: 'other', className: 'text-end', render: formatCurrency },
                { data: 'ca_corporate', className: 'text-end', render: formatCurrency },
                { data: 'ca_personal', className: 'text-end', render: formatCurrency },
                { data: 'ca_kehadiran', className: 'text-end', render: formatCurrency },
                
                { data: 'pph_21_deduction', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tenaga_kerja', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jht_3_7_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jht_2_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jkk_0_24_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jkm_0_3_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jp_2_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_tk_jp_1_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_kesehatan', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_kes_4_percent', className: 'text-end', render: formatCurrency },
                { data: 'bpjs_kes_1_percent', className: 'text-end', render: formatCurrency },
                { data: 'pph_21', className: 'text-end', render: formatCurrency },
                { data: 'glh', className: 'text-end', render: formatCurrency },
                { data: 'lm', className: 'text-end', render: formatCurrency },
                { data: 'lainnya', className: 'text-end', render: formatCurrency },
                { data: 'salary_formatted', className: 'text-end fw-bold bg-light', render: formatCurrency },
                { data: 'total_penerimaan_formatted', className: 'text-end fw-bold bg-light', render: formatCurrency },
                { data: 'total_potongan_formatted', className: 'text-end fw-bold bg-light', render: formatCurrency },
                { data: 'gaji_bersih_formatted', className: 'text-end fw-bold bg-success bg-opacity-10', render: formatCurrency },
                { data: 'action', orderable: false, searchable: false }
            ],
            scrollX: true,
            scrollCollapse: true,
            fixedColumns: {
                leftColumns: 4
            },
            order: [[1, 'desc']],
            scrollX: true,
            pageLength: parseInt($('#entriesSelectReleased').val()) || 25,
            lengthChange: false,
            dom: '<"table-responsive"t><"row"<"col-sm-12 col-md-5"li><"col-sm-12 col-md-7"p>>',
            language: {
                emptyTable: "Tidak ada data yang tersedia",
                processing: '<span class="spinner-border spinner-border-sm align-middle ms-2"></span> Loading...',
                paginate: { previous: '<i class="previous"></i>', next: '<i class="next"></i>' }
            }
        });

        $('#searchReleased').off('keyup').on('keyup', function() { 
            if (virtualScrollMode) {
                // Re-init virtual scroll dengan search
                initVirtualScroll();
            } else {
                dtReleased.search(this.value).draw(); 
            }
        });

        $('#filterPeriodeReleased').off('change').on('change', function() { 
            if (virtualScrollMode) {
                initVirtualScroll();
            } else {
                dtReleased.ajax.reload(); 
            }
        });

        $('#btnResetFilter').off('click').on('click', function() {
            $('#searchReleased').val('');
            $('#filterPeriodeReleased').val('');
            if (virtualScrollMode) {
                initVirtualScroll();
            } else {
                dtReleased.search('').ajax.reload();
            }
        });
    }

    // ========== HANDLE ENTRIES SELECT ==========
    var handleEntriesSelect = function() {
        $('#entriesSelectReleased').on('change', function() {
            const value = $(this).val();
            
            if (value === 'all') {
                Swal.fire({
                    title: 'Mengaktifkan Virtual Scrolling',
                    text: 'Loading data...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                setTimeout(() => {
                    initVirtualScroll();
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'Virtual Scrolling Aktif!',
                        text: 'Scroll ke bawah untuk memuat data berikutnya',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }, 500);
            } else {
                if (virtualScrollMode) {
                    destroyVirtualScroll();
                    initReleasedTable();
                }
                dtReleased.page.len(parseInt(value)).draw();
            }
        });
    }

    var updateSelectedIds = function() {
        selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        $('#selectedCount').text(selectedIds.length);
        $('#btnReleaseSelected').toggle(selectedIds.length > 0);
    }

    var updatePendingCount = function() {
        $.ajax({
            url: "{{ route('payrollsdatatablepending') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { count_only: true },
            success: function(response) {
                var count = response.recordsTotal || 0;
                $('#pendingCount').text(count);
                $('#cardPending').toggle(count > 0);
            }
        });
    }

    var handleCheckboxes = function() {
        $(document).on('change', '#checkAllPending', function() {
            $('.row-checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedIds();
        });

        $(document).on('change', '.row-checkbox', function() {
            var allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
            $('#checkAllPending').prop('checked', allChecked);
            updateSelectedIds();
        });
    }

    var handleReleaseSelected = function() {
        $('#btnReleaseSelected').on('click', function() {
            if (selectedIds.length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal 1 payroll untuk dirilis', 'warning');
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Release',
                text: `Anda akan merilis ${selectedIds.length} payroll. Lanjutkan?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Release',
                cancelButtonText: 'Batal',
                customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-secondary' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('payrolls.release') }}",
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: { ids: selectedIds },
                        success: function(response) {
                            Swal.fire('Berhasil!', response.message, 'success');
                            selectedIds = [];
                            $('#checkAllPending').prop('checked', false);
                            dtPending.ajax.reload();
                            if (virtualScrollMode) {
                                initVirtualScroll();
                            } else {
                                dtReleased.ajax.reload();
                            }
                            updatePendingCount();
                        },
                        error: function(xhr) {
                            var msg = xhr.responseJSON?.message || 'Gagal merilis payroll';
                            Swal.fire('Error!', msg, 'error');
                        }
                    });
                }
            });
        });
    }

    var handleDeletePayroll = function() {
        $(document).on('click', '.btn-delete', function() {
            deletePayrollId = $(this).data('id');
            var periode = $(this).data('periode');
            $('#deleteInfo').text('Periode: ' + periode);
            $('#deleteModal').modal('show');
        });

        $('#confirmDelete').on('click', function() {
            if (!deletePayrollId) return;

            $.ajax({
                url: `/payrolls/${deletePayrollId}`,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    $('#deleteModal').modal('hide');
                    Swal.fire('Berhasil!', 'Payroll berhasil dihapus', 'success');
                    dtPending.ajax.reload();
                    updatePendingCount();
                    deletePayrollId = null;
                },
                error: function(xhr) {
                    $('#deleteModal').modal('hide');
                    var msg = xhr.responseJSON?.message || 'Gagal menghapus payroll';
                    Swal.fire('Error!', msg, 'error');
                }
            });
        });
    }

    return {
        init: function() {
            initPendingTable();
            initReleasedTable();
            handleEntriesSelect();
            handleCheckboxes();
            handleReleaseSelected();
            handleDeletePayroll();
        }
    };
}();

$(document).ready(function() {
    KTPayrollList.init();
});

// Handle Export Pending
$('#btnExportPending').on('click', function() {
    var periode = $('#filterPeriodePending').val();
    var url = "{{ route('payrolls.export') }}?is_released=0";
    
    if (periode) {
        url += '&periode=' + periode;
    }
    
    Swal.fire({
        title: 'Exporting...',
        text: 'Mohon tunggu, sedang memproses data',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    window.location.href = url;
    
    setTimeout(() => {
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'Export Berhasil!',
            text: 'File Excel sedang diunduh',
            timer: 2000,
            showConfirmButton: false
        });
    }, 2000);
});

// Handle Export Released
$('#btnExportReleased').on('click', function() {
    var periode = $('#filterPeriodeReleased').val();
    var url = "{{ route('payrolls.export') }}?is_released=1";
    
    if (periode) {
        url += '&periode=' + periode;
    }
    
    Swal.fire({
        title: 'Exporting...',
        text: 'Mohon tunggu, sedang memproses data',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    window.location.href = url;
    
    setTimeout(() => {
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'Export Berhasil!',
            text: 'File Excel sedang diunduh',
            timer: 2000,
            showConfirmButton: false
        });
    }, 2000);
});
</script>
@endpush
