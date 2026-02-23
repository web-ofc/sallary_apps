{{-- resources/views/dashboard/dashboard-admin/payrolls/_table_released_slip.blade.php --}}

<div class="d-flex justify-content-between align-items-center mb-5">
    <div class="d-flex align-items-center gap-3">
        <div class="position-relative">
            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5" style="top:50%;transform:translateY(-50%)"></i>
            <input type="text" id="searchReleasedSlip" class="form-control form-control-solid w-250px ps-13" placeholder="Cari..." />
        </div>
        <input type="month" id="filterPeriodeReleasedSlip" class="form-control form-control-solid w-150px" />
        <button type="button" class="btn btn-sm btn-light-primary" id="btnResetFilterReleasedSlip">
            <i class="ki-outline ki-arrows-circle fs-5"></i>
        </button>
    </div>
    <button type="button" class="btn btn-sm btn-light-success" id="btnExportReleasedSlip">
        <i class="ki-outline ki-file-down fs-3"></i> Export
    </button>
    <button type="button" class="btn btn-sm btn-primary" id="btnDownloadZipReleasedSlip" style="display:none">
        <i class="ki-outline ki-file-down fs-3"></i>
        Download Slip ZIP (<span id="selectedReleasedSlipCount">0</span>/20)
    </button>
</div>

<div class="table-responsive">
    <table id="payrollTableReleasedSlip" class="table table-hover table-striped table-bordered table-sm align-middle gs-0 gy-4">
        <thead class="bg-light">
            <tr>
                <th rowspan="2" class="text-center align-middle">
                </th>
                <th rowspan="2" class="text-center align-middle">#</th>
                <th rowspan="2" class="text-center align-middle min-w-100px">Periode</th>
                <th rowspan="2" class="text-center align-middle">NIK</th>
                <th rowspan="2" class="text-center align-middle min-w-200px">Nama Karyawan</th>
                <th rowspan="2" class="text-center align-middle min-w-150px">Company</th>
                <th rowspan="2" class="text-center align-middle">Salary Type</th>
                <th rowspan="2" class="text-center align-middle">PTKP Status</th>
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