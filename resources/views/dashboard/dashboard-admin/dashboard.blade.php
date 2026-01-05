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

    <!-- Stats Cards Row 1 -->
    <div class="row g-5 g-xl-8 mb-5">
        <!-- Total Payroll -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-xl-8">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-primary">
                                <i class="fas fa-users fs-2x text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-gray-700 fw-semibold d-block fs-6">Total Payroll</span>
                            <span class="text-gray-900 fw-bold d-block fs-2" id="totalPayroll">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Gaji Dibayarkan -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-xl-8">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-success">
                                <i class="fas fa-money-bill-wave fs-2x text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-gray-700 fw-semibold d-block fs-6">Total Dibayarkan</span>
                            <span class="text-gray-900 fw-bold d-block fs-2" id="totalDisbursed">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Released Payrolls -->
        <div class="col-xl-3">
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
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Draft Payrolls -->
        <div class="col-xl-3">
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
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 2 - Breakdown -->
    <div class="row g-5 g-xl-8 mb-5">
        <!-- Total Pendapatan -->
        <div class="col-xl-4">
            <div class="card card-xl-stretch mb-xl-8 bg-light-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-success fw-semibold d-block fs-6 mb-1">
                                <i class="fas fa-arrow-up me-2"></i>Total Salary
                            </span>
                            <span class="text-success fw-bold d-block fs-2x" id="totalIncome">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Potongan -->
        <div class="col-xl-4">
            <div class="card card-xl-stretch mb-xl-8 bg-light-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-danger fw-semibold d-block fs-6 mb-1">
                                <i class="fas fa-arrow-down me-2"></i>Total Potongan
                            </span>
                            <span class="text-danger fw-bold d-block fs-2x" id="totalDeduction">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total BPJS -->
        <div class="col-xl-4">
            <div class="card card-xl-stretch mb-xl-8 bg-light-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-primary fw-semibold d-block fs-6 mb-1">
                                <i class="fas fa-shield-alt me-2"></i>Total BPJS
                            </span>
                            <span class="text-primary fw-bold d-block fs-2x" id="totalBPJS">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-5 g-xl-8 mb-5">
        <!-- Payroll by Company -->
        <div class="col-xl-6">
            <div class="card card-xl-stretch mb-5 mb-xl-8">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Payroll by Company</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Distribusi gaji per perusahaan</span>
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="companyChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Top 10 Highest Salary -->
        <div class="col-xl-6">
            <div class="card card-xl-stretch mb-5 mb-xl-8">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Top 10 Gaji Tertinggi</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Karyawan dengan gaji tertinggi bulan ini</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <div id="topSalaryList" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Components -->
    <div class="row g-5 g-xl-8 mb-5">
        <!-- Income Breakdown -->
        <div class="col-xl-6">
            <div class="card card-xl-stretch mb-5 mb-xl-8">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Breakdown Pendapatan</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Komponen pendapatan karyawan</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gs-0 gy-3">
                            <tbody id="incomeBreakdown">
                                <tr><td colspan="2" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deduction Breakdown -->
        <div class="col-xl-6">
            <div class="card card-xl-stretch mb-5 mb-xl-8">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Breakdown Potongan & BPJS</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Komponen potongan karyawan</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gs-0 gy-3">
                            <tbody id="deductionBreakdown">
                                <tr><td colspan="2" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payrolls -->
    <div class="row g-5 g-xl-8">
        <div class="col-xl-12">
            <div class="card card-xl-stretch mb-5 mb-xl-8">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Payroll Terbaru</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">10 payroll terakhir yang diinput</span>
                    </h3>
                    <div class="card-toolbar">
                        <a href="{{ route('payrolls.index') }}" class="btn btn-sm btn-light-primary">
                            <i class="fas fa-eye"></i> Lihat Semua
                        </a>
                    </div>
                </div>
                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="min-w-100px">Periode</th>
                                    <th class="min-w-150px">Karyawan</th>
                                    <th class="min-w-120px">Company</th>
                                    <th class="min-w-120px text-end">Gaji Pokok</th>
                                    <th class="min-w-120px text-end">Take Home Pay</th>
                                    <th class="min-w-80px">Status</th>
                                    <th class="min-w-100px text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recentPayrolls">
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let currentPeriode = '';
let companyChart = null;

$(document).ready(function() {
    loadPeriodes();
    
    $('#periodeFilter').change(function() {
        currentPeriode = $(this).val();
        loadDashboardData();
    });
});

function loadPeriodes() {
    $.ajax({
        url: '{{ route("dashboard.periodes") }}',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                let options = '';
                response.data.forEach(function(periode, index) {
                    let selected = index === 0 ? 'selected' : '';
                    options += `<option value="${periode}" ${selected}>${formatPeriode(periode)}</option>`;
                });
                $('#periodeFilter').html(options);
                
                currentPeriode = response.data[0];
                loadDashboardData();
            } else {
                $('#periodeFilter').html('<option value="">Tidak ada data</option>');
            }
        },
        error: function(xhr) {
            console.error('Error loading periodes:', xhr);
            $('#periodeFilter').html('<option value="">Error loading</option>');
        }
    });
}

function loadDashboardData() {
    if (!currentPeriode) return;
    
    $.ajax({
        url: '{{ route("dashboard.data") }}',
        method: 'GET',
        data: {
            periode: currentPeriode
        },
        success: function(response) {
            if (response.success) {
                calculateStats(response.data);
                renderCompanyChart(response.data);
                renderTopSalaries(response.data);
                renderIncomeBreakdown(response.data);
                renderDeductionBreakdown(response.data);
                renderRecentPayrolls(response.data);
            }
        },
        error: function(xhr) {
            console.error('Error loading dashboard data:', xhr);
        }
    });
}

function calculateStats(data) {
    let totalPayroll = data.length;
    let releasedCount = data.filter(p => p.is_released).length;
    let draftCount = totalPayroll - releasedCount;
    
    let totalIncome = 0;
    let totalDeduction = 0;
    let totalBPJS = 0;
    let totalDisbursed = 0;
    
    data.forEach(function(p) {
        // Total Income
        let income = (p.gaji_pokok || 0) +
                    (p.monthly_kpi || 0) +
                    (p.overtime || 0) +
                    (p.medical_reimbursement || 0) +
                    (p.insentif_sholat || 0) +
                    (p.monthly_bonus || 0) +
                    (p.rapel || 0) +
                    (p.tunjangan_pulsa || 0) +
                    (p.tunjangan_kehadiran || 0) +
                    (p.tunjangan_transport || 0) +
                    (p.tunjangan_lainnya || 0) +
                    (p.yearly_bonus || 0) +
                    (p.thr || 0) +
                    (p.other || 0);
        
        // Total Deduction
        let deduction = (p.ca_corporate || 0) +
                       (p.ca_personal || 0) +
                       (p.ca_kehadiran || 0) +
                       (p.pph_21 || 0);
        
        // Total BPJS
        let bpjs = (p.bpjs_tenaga_kerja || 0) +
                  (p.bpjs_kesehatan || 0) +
                  (p.pph_21_deduction || 0);
        
        totalIncome += income;
        totalDeduction += deduction;
        totalBPJS += bpjs;
        totalDisbursed += (income - deduction - bpjs);
    });
    
    $('#totalPayroll').html(`<span class="counter">${totalPayroll}</span>`);
    $('#totalDisbursed').html(formatRupiahShort(totalDisbursed));
    $('#releasedCount').html(`<span class="counter">${releasedCount}</span>`);
    $('#draftCount').html(`<span class="counter">${draftCount}</span>`);
    $('#totalIncome').html(formatRupiahShort(totalIncome));
    $('#totalDeduction').html(formatRupiahShort(totalDeduction));
    $('#totalBPJS').html(formatRupiahShort(totalBPJS));
}

function renderCompanyChart(data) {
    let companyData = {};
    
    data.forEach(function(p) {
        let companyName = p.company?.company_name || 'No Company';
        let takeHomePay = calculateTakeHomePay(p);
        
        if (!companyData[companyName]) {
            companyData[companyName] = 0;
        }
        companyData[companyName] += takeHomePay;
    });
    
    let labels = Object.keys(companyData);
    let values = Object.values(companyData);
    
    if (companyChart) {
        companyChart.destroy();
    }
    
    let ctx = document.getElementById('companyChart').getContext('2d');
    companyChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    '#009ef7',
                    '#50cd89',
                    '#ffc700',
                    '#f1416c',
                    '#7239ea',
                    '#50cd89',
                    '#f1416c'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + formatRupiah(context.raw);
                        }
                    }
                }
            }
        }
    });
}

function renderTopSalaries(data) {
    let sorted = data.map(p => ({
        ...p,
        takeHomePay: calculateTakeHomePay(p)
    })).sort((a, b) => b.takeHomePay - a.takeHomePay).slice(0, 10);
    
    let html = '';
    sorted.forEach(function(p, index) {
        let badgeClass = index < 3 ? 'badge-success' : 'badge-light';
        html += `
            <div class="d-flex align-items-center mb-4">
                <div class="symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-4">
                        ${index + 1}
                    </div>
                </div>
                <div class="flex-grow-1">
                    <a href="/payrolls/${p.id}" class="text-gray-800 text-hover-primary fw-bold fs-6">
                        ${p.karyawan?.nama_lengkap || 'Unknown'}
                    </a>
                    <span class="text-muted fw-semibold d-block fs-7">
                        ${p.karyawan?.nik || '-'}
                    </span>
                </div>
                <span class="badge ${badgeClass} fs-7">
                    ${formatRupiah(p.takeHomePay)}
                </span>
            </div>
        `;
    });
    
    $('#topSalaryList').html(html);
}

function renderIncomeBreakdown(data) {
    let breakdown = {
        'Gaji Pokok': 0,
        'Monthly KPI': 0,
        'Overtime': 0,
        'Medical': 0,
        'Insentif Sholat': 0,
        'Monthly Bonus': 0,
        'Rapel': 0,
        'Tunjangan Pulsa': 0,
        'Tunjangan Kehadiran': 0,
        'Tunjangan Transport': 0,
        'Tunjangan Lainnya': 0,
        'Yearly Bonus': 0,
        'THR': 0,
        'Other': 0
    };
    
    data.forEach(function(p) {
        breakdown['Gaji Pokok'] += p.gaji_pokok || 0;
        breakdown['Monthly KPI'] += p.monthly_kpi || 0;
        breakdown['Overtime'] += p.overtime || 0;
        breakdown['Medical'] += p.medical_reimbursement || 0;
        breakdown['Insentif Sholat'] += p.insentif_sholat || 0;
        breakdown['Monthly Bonus'] += p.monthly_bonus || 0;
        breakdown['Rapel'] += p.rapel || 0;
        breakdown['Tunjangan Pulsa'] += p.tunjangan_pulsa || 0;
        breakdown['Tunjangan Kehadiran'] += p.tunjangan_kehadiran || 0;
        breakdown['Tunjangan Transport'] += p.tunjangan_transport || 0;
        breakdown['Tunjangan Lainnya'] += p.tunjangan_lainnya || 0;
        breakdown['Yearly Bonus'] += p.yearly_bonus || 0;
        breakdown['THR'] += p.thr || 0;
        breakdown['Other'] += p.other || 0;
    });
    
    let html = '';
    Object.keys(breakdown).forEach(function(key) {
        if (breakdown[key] > 0) {
            html += `
                <tr>
                    <td class="fw-semibold text-gray-700">${key}</td>
                    <td class="text-end fw-bold text-gray-900">${formatRupiah(breakdown[key])}</td>
                </tr>
            `;
        }
    });
    
    $('#incomeBreakdown').html(html);
}

function renderDeductionBreakdown(data) {
    let breakdown = {
        'CA Corporate': 0,
        'CA Personal': 0,
        'CA Kehadiran': 0,
        'PPh 21': 0,
        'BPJS TK': 0,
        'BPJS Kes': 0,
        'PPh 21 Deduction': 0
    };
    
    data.forEach(function(p) {
        breakdown['CA Corporate'] += p.ca_corporate || 0;
        breakdown['CA Personal'] += p.ca_personal || 0;
        breakdown['CA Kehadiran'] += p.ca_kehadiran || 0;
        breakdown['PPh 21'] += p.pph_21 || 0;
        breakdown['BPJS TK'] += p.bpjs_tenaga_kerja || 0;
        breakdown['BPJS Kes'] += p.bpjs_kesehatan || 0;
        breakdown['PPh 21 Deduction'] += p.pph_21_deduction || 0;
    });
    
    let html = '';
    Object.keys(breakdown).forEach(function(key) {
        if (breakdown[key] > 0) {
            html += `
                <tr>
                    <td class="fw-semibold text-gray-700">${key}</td>
                    <td class="text-end fw-bold text-gray-900">${formatRupiah(breakdown[key])}</td>
                </tr>
            `;
        }
    });
    
    $('#deductionBreakdown').html(html);
}

function renderRecentPayrolls(data) {
    let recent = data.slice(0, 10);
    
    let html = '';
    recent.forEach(function(p) {
        let statusBadge = p.is_released 
            ? '<span class="badge badge-success">Released</span>'
            : '<span class="badge badge-warning">Draft</span>';
        
        let takeHomePay = calculateTakeHomePay(p);
        
        html += `
            <tr>
                <td class="fw-bold">${p.periode}</td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="text-gray-800 fw-bold">${p.karyawan?.nama_lengkap || 'Unknown'}</span>
                        <span class="text-muted fs-7">${p.karyawan?.nik || '-'}</span>
                    </div>
                </td>
                <td>${p.company?.company_name || '-'}</td>
                <td class="text-end fw-bold">${formatRupiah(p.gaji_pokok)}</td>
                <td class="text-end fw-bold text-success">${formatRupiah(takeHomePay)}</td>
                <td>${statusBadge}</td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="/payrolls/${p.id}" class="btn btn-sm btn-light-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/payrolls/${p.id}/edit" class="btn btn-sm btn-light-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `;
    });
    
    $('#recentPayrolls').html(html);
}

function calculateTakeHomePay(p) {
    let income = (p.gaji_pokok || 0) +
                (p.monthly_kpi || 0) +
                (p.overtime || 0) +
                (p.medical_reimbursement || 0) +
                (p.insentif_sholat || 0) +
                (p.monthly_bonus || 0) +
                (p.rapel || 0) +
                (p.tunjangan_pulsa || 0) +
                (p.tunjangan_kehadiran || 0) +
                (p.tunjangan_transport || 0) +
                (p.tunjangan_lainnya || 0) +
                (p.yearly_bonus || 0) +
                (p.thr || 0) +
                (p.other || 0);
    
    let deduction = (p.ca_corporate || 0) +
                   (p.ca_personal || 0) +
                   (p.ca_kehadiran || 0) +
                   (p.pph_21 || 0) +
                   (p.bpjs_tenaga_kerja || 0) +
                   (p.bpjs_kesehatan || 0) +
                   (p.pph_21_deduction || 0);
    
    return income - deduction;
}

function formatPeriode(periode) {
    let [year, month] = periode.split('-');
    let monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${monthNames[parseInt(month) - 1]} ${year}`;
}

function formatRupiah(number) {
    if (number === 0 || number === null || number === undefined) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}

function formatRupiahShort(number) {
    if (number >= 1000000000) {
        return 'Rp ' + (number / 1000000000).toFixed(1) + 'M';
    } else if (number >= 1000000) {
        return 'Rp ' + (number / 1000000).toFixed(1) + 'Jt';
    } else if (number >= 1000) {
        return 'Rp ' + (number / 1000).toFixed(0) + 'K';
    }
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}
</script>
@endpush
@endsection