@extends('layouts.master')

@section('title', 'Dashboard Admin - Payroll')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap flex-stack mb-5">
        <h1 class="fw-bold my-2">
            <i class="fas fa-chart-line text-primary"></i> Dashboard Payroll
        </h1>
        <div class="d-flex align-items-center gap-2">
            <select id="periodeFilter" class="form-select form-select-sm" style="width: 150px;">
                <option value="">Loading...</option>
            </select>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-5 g-xl-8 mb-5">

        <!-- Total Payroll -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-xl-stretch mb-xl-8">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-primary">
                                <i class="fas fa-file-invoice-dollar fs-2x text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-gray-700 fw-semibold d-block fs-6">Total Payroll</span>
                            <span class="text-gray-900 fw-bold d-block fs-2" id="totalPayroll">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Draft -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-xl-stretch mb-xl-8">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-warning">
                                <i class="fas fa-clock fs-2x text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-gray-700 fw-semibold d-block fs-6">Draft</span>
                            <span class="text-gray-900 fw-bold d-block fs-2" id="draftCount">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Released -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-xl-stretch mb-xl-8">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-info">
                                <i class="fas fa-check-circle fs-2x text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-gray-700 fw-semibold d-block fs-6">Released</span>
                            <span class="text-gray-900 fw-bold d-block fs-2" id="releasedCount">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Released Slip -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-xl-stretch mb-xl-8">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-success">
                                <i class="fas fa-file-alt fs-2x text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-gray-700 fw-semibold d-block fs-6">Released Slip</span>
                            <span class="text-gray-900 fw-bold d-block fs-2" id="releasedSlip">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Belum Diinput -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-xl-stretch mb-xl-8 border border-danger border-dashed">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-danger">
                                <i class="fas fa-user-times fs-2x text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-gray-700 fw-semibold d-block fs-6">Belum Diinput</span>
                            <span class="text-gray-900 fw-bold d-block fs-2" id="belumInput">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                            <span class="text-muted fw-semibold fs-8">karyawan aktif tanpa payroll</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
let currentPeriode = '';

$(document).ready(function () {
    loadPeriodes();

    $('#periodeFilter').change(function () {
        currentPeriode = $(this).val();
        loadStatistics();
    });
});

function loadPeriodes() {
    $.ajax({
        url: '{{ route("dashboard.periodes") }}',
        method: 'GET',
        success: function (response) {
            if (response.success && response.data.length > 0) {
                let options = '';
                response.data.forEach(function (periode, index) {
                    let selected = index === 0 ? 'selected' : '';
                    options += `<option value="${periode}" ${selected}>${formatPeriode(periode)}</option>`;
                });
                $('#periodeFilter').html(options);

                currentPeriode = response.data[0];
                loadStatistics();
            } else {
                $('#periodeFilter').html('<option value="">Tidak ada data</option>');
            }
        },
        error: function (xhr) {
            console.error('Error loading periodes:', xhr);
            $('#periodeFilter').html('<option value="">Error loading</option>');
        }
    });
}

function loadStatistics() {
    if (!currentPeriode) return;

    // Reset ke spinner dulu
    ['totalPayroll', 'draftCount', 'releasedCount', 'releasedSlip', 'belumInput'].forEach(function (id) {
        $('#' + id).html('<span class="spinner-border spinner-border-sm" role="status"></span>');
    });

    $.ajax({
        url: '{{ route("dashboard.statistics") }}',
        method: 'GET',
        data: { periode: currentPeriode },
        success: function (response) {
            if (response.success) {
                let d = response.data;
                $('#totalPayroll').text(d.total_payroll);
                $('#draftCount').text(d.draft_count);
                $('#releasedCount').text(d.released_count);
                $('#releasedSlip').text(d.released_slip);
                $('#belumInput').text(d.belum_input);
            }
        },
        error: function (xhr) {
            console.error('Error loading statistics:', xhr);
        }
    });
}

function formatPeriode(periode) {
    let [year, month] = periode.split('-');
    let monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${monthNames[parseInt(month) - 1]} ${year}`;
}
</script>
@endpush
@endsection