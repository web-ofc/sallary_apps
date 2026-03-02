@extends('layouts.master')

@section('title', 'Dashboard Management - Reimbursement')

@section('content')

<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
    <div class="d-flex flex-column flex-column-fluid">

        {{-- ── Page Header ──────────────────────────────────────────────── --}}
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                        Dashboard Reimbursement
                    </h1>
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('dashboard.management') }}" class="text-muted text-hover-primary">Home</a>
                        </li>
                        <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                        <li class="breadcrumb-item text-muted">Reimbursement</li>
                    </ul>
                </div>

                {{-- Filter Tahun --}}
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <span class="badge badge-light-primary fs-7 fw-bold py-2 px-3">
                        <i class="ki-duotone ki-calendar fs-7 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Periode: <span id="label-periode-aktif" class="ms-1">—</span>
                    </span>
                    <select class="form-select form-select-sm form-select-solid w-auto" id="filter-year">
                        @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>
        {{-- ── End Page Header ─────────────────────────────────────────── --}}

        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- ══════════════════════════════════════════════════════
                     ROW 1 — 4 STAT CARDS
                     Sumber: reimbursements + reimbursement_childs
                     ══════════════════════════════════════════════════════ --}}
                <div class="row g-5 g-xl-10 mb-5 mb-xl-10">

                    {{-- Card 1: Total Pengajuan --}}
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end mb-5 mb-xl-10"
                             style="background-color:#F1416C;background-image:url('{{ asset('assets/media/patterns/vector-1.png') }}')">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="stat-total-pengajuan">
                                        <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Total Pengajuan</span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <div class="d-flex align-items-center flex-column mt-3 w-100">
                                    <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                                        <span id="stat-approved-label">0 Approved</span>
                                        <span id="stat-pending-label">0 Pending</span>
                                    </div>
                                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-20 rounded">
                                        <div class="bg-white rounded h-8px" id="stat-progress-approved" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Total Nilai Klaim Approved --}}
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end mb-5 mb-xl-10"
                             style="background-color:#7239EA;background-image:url('{{ asset('assets/media/patterns/vector-1.png') }}')">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="stat-nilai-approved">
                                        <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Total Nilai Disetujui</span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <div class="d-flex align-items-center flex-column mt-3 w-100">
                                    <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                                        <span>Dari klaim approved</span>
                                        <span id="stat-nilai-pct">—</span>
                                    </div>
                                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-20 rounded">
                                        <div class="bg-white rounded h-8px" id="stat-progress-nilai" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 3: Karyawan Pengaju Unik --}}
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end mb-5 mb-xl-10"
                             style="background-color:#17C653;background-image:url('{{ asset('assets/media/patterns/vector-1.png') }}')">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="stat-karyawan-pengaju">
                                        <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Karyawan Pengaju</span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <div class="d-flex align-items-center flex-column mt-3 w-100">
                                    <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                                        <span>Karyawan unik tahun ini</span>
                                        <span id="stat-karyawan-pengaju-sub">—</span>
                                    </div>
                                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-20 rounded">
                                        <div class="bg-white rounded h-8px" style="width:100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 4: Pending Approval --}}
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end mb-5 mb-xl-10"
                             style="background-color:#F6C000;background-image:url('{{ asset('assets/media/patterns/vector-1.png') }}')">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="stat-pending">
                                        <span class="spinner-border spinner-border-sm text-white" role="status"></span>
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Menunggu Approval</span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <div class="d-flex align-items-center flex-column mt-3 w-100">
                                    <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                                        <span>Bulan ini</span>
                                        <span id="stat-pending-bulan">0</span>
                                    </div>
                                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-20 rounded">
                                        <div class="bg-white rounded h-8px" id="stat-progress-pending" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                {{-- ── End Row 1 ───────────────────────────────────────── --}}


                {{-- ══════════════════════════════════════════════════════
                     ROW 2 — Chart Tren + Chart Breakdown Tagihan
                     ══════════════════════════════════════════════════════ --}}
                <div class="row g-5 g-xl-10 mb-5 mb-xl-10">

                    {{-- Chart: Tren Klaim Bulanan (area — nominal) --}}
                    <div class="col-xl-8">
                        <div class="card card-flush overflow-hidden h-md-100">
                            <div class="card-header py-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Tren Klaim Reimbursement</span>
                                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Nilai & jumlah klaim approved per bulan</span>
                                </h3>
                                <div class="card-toolbar">
                                    <ul class="nav">
                                        <li class="nav-item">
                                            <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1 active"
                                               data-bs-toggle="tab" href="#tab-tren-nominal">Nominal</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4"
                                               data-bs-toggle="tab" href="#tab-tren-count">Jumlah</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body pb-1 px-0">
                                <div class="tab-content px-9">
                                    <div class="tab-pane fade show active" id="tab-tren-nominal">
                                        <div id="chart-tren-nominal" style="height:300px;"></div>
                                    </div>
                                    <div class="tab-pane fade" id="tab-tren-count">
                                        <div id="chart-tren-count" style="height:300px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Chart: Breakdown Jenis Tagihan (donut) --}}
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Breakdown Tagihan</span>
                                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Komposisi jenis klaim (approved)</span>
                                </h3>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div id="chart-breakdown" style="height:220px;"></div>
                                <div class="mt-5" id="breakdown-legend"></div>
                            </div>
                        </div>
                    </div>

                </div>
                {{-- ── End Row 2 ───────────────────────────────────────── --}}


                {{-- ══════════════════════════════════════════════════════
                     ROW 3 — Top 10 Karyawan + Chart Approved vs Pending
                     ══════════════════════════════════════════════════════ --}}
                <div class="row g-5 g-xl-10 mb-5 mb-xl-10">

                    {{-- Top 10 Karyawan --}}
                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Top 10 Karyawan</span>
                                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Nilai klaim approved tertinggi</span>
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-success fw-bold" id="label-top-year">{{ now()->year }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-5 overflow-auto" style="max-height:420px;">
                                <div id="list-top-karyawan">
                                    @for($i = 0; $i < 5; $i++)
                                    <div class="d-flex align-items-center mb-7 placeholder-glow">
                                        <div class="symbol symbol-50px me-5">
                                            <span class="symbol-label bg-light placeholder rounded"></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="placeholder col-6 d-block mb-2"></span>
                                            <span class="placeholder col-4"></span>
                                        </div>
                                        <span class="placeholder col-2"></span>
                                    </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Chart: Approved vs Pending per bulan (stacked bar) --}}
                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Status Pengajuan per Bulan</span>
                                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Perbandingan approved vs pending</span>
                                </h3>
                            </div>
                            <div class="card-body pt-3">
                                <div id="chart-status-perbulan" style="height:380px;"></div>
                            </div>
                        </div>
                    </div>

                </div>
                {{-- ── End Row 3 ───────────────────────────────────────── --}}


                {{-- ══════════════════════════════════════════════════════
                     ROW 4 — Tabel Pengajuan + Periode Aktif
                     ══════════════════════════════════════════════════════ --}}
                <div class="row g-5 g-xl-10 mb-10">

                    {{-- Tabel Pengajuan --}}
                    <div class="col-xl-8">
                        <div class="card card-flush">
                            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1">
                                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <input type="text" id="search-pengajuan"
                                               class="form-control form-control-solid w-250px ps-14"
                                               placeholder="Cari nama / ID / NIK..." />
                                    </div>
                                </div>
                                <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                                    <select class="form-select form-select-solid form-select-sm w-auto" id="filter-status-tabel">
                                        <option value="">Semua Status</option>
                                        <option value="1">Approved</option>
                                        <option value="0">Pending</option>
                                    </select>
                                    <a href="{{ route('manage-reimbursements.index') }}" class="btn btn-primary btn-sm">
                                        <i class="ki-duotone ki-exit-right-corner fs-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        Kelola Semua
                                    </a>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                                        <thead>
                                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-120px">ID Recapan</th>
                                                <th class="min-w-150px">Karyawan</th>
                                                <th class="min-w-90px">Periode</th>
                                                <th class="min-w-100px">Total Klaim</th>
                                                <th class="min-w-80px">Status</th>
                                                <th class="min-w-90px">Tgl Approve</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fw-semibold text-gray-600" id="tabel-pengajuan-body">
                                            <tr>
                                                <td colspan="6" class="text-center py-10">
                                                    <div class="spinner-border text-primary" role="status"></div>
                                                    <div class="text-gray-500 mt-2">Memuat data...</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-5">
                                    <div class="text-gray-600 fs-7" id="tabel-info"></div>
                                    <div id="tabel-pagination" class="d-flex"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar: Periode Aktif --}}
                    <div class="col-xl-4">

                        {{-- Info Periode Aktif --}}
                        <div class="card card-flush mb-5">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Periode Aktif</span>
                                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Jadwal reimbursement berjalan</span>
                                </h3>
                            </div>
                            <div class="card-body pt-5" id="info-periode-aktif">
                                <div class="placeholder-glow">
                                    <span class="placeholder col-8 d-block mb-3"></span>
                                    <span class="placeholder col-6 d-block mb-3"></span>
                                    <span class="placeholder col-7 d-block"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Ringkasan Breakdown (text list, no chart duplication) --}}
                        <div class="card card-flush">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Rincian Tagihan</span>
                                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Nominal per jenis (approved)</span>
                                </h3>
                            </div>
                            <div class="card-body pt-5" id="rincian-tagihan">
                                <div class="placeholder-glow">
                                    @for($i=0;$i<4;$i++)
                                    <div class="d-flex align-items-center justify-content-between mb-4 placeholder-glow">
                                        <span class="placeholder col-4"></span>
                                        <span class="placeholder col-3"></span>
                                    </div>
                                    @endfor
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                {{-- ── End Row 4 ───────────────────────────────────────── --}}

            </div>
        </div>
    </div>
</div>

@endsection


@push('scripts')
<script>
// =============================================================
// HELPERS
// =============================================================
const YEAR = () => document.getElementById('filter-year').value;

const rupiah = (val) => {
    if (!val && val !== 0) return '-';
    if (val >= 1_000_000_000) return 'Rp ' + (val / 1_000_000_000).toFixed(1) + ' M';
    if (val >= 1_000_000)     return 'Rp ' + (val / 1_000_000).toFixed(1) + ' Jt';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
};

const rupiahFull = (val) =>
    'Rp ' + new Intl.NumberFormat('id-ID').format(val ?? 0);

const pct = (part, total) =>
    total > 0 ? Math.min(Math.round((part / total) * 100), 100) : 0;

const MONTHS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];

const COLORS = {
    dokter   : '#F1416C',
    obat     : '#7239EA',
    kacamata : '#009EF7',
    gigi     : '#50CD89',
};

// =============================================================
// APEX CHART INSTANCES
// =============================================================
let chartTrenNominal    = null;
let chartTrenCount      = null;
let chartBreakdown      = null;
let chartStatusPerBulan = null;

// =============================================================
// FETCH — semua hit web routes (session auth, bukan API)
// =============================================================
const BASE = '/dashboard-management/reimbursement';

const apiFetch = async (path, params = {}) => {
    const qs  = new URLSearchParams(params).toString();
    const res = await fetch(`${BASE}/${path}${qs ? '?' + qs : ''}`);
    if (!res.ok) throw new Error(`HTTP ${res.status} di /${path}`);
    return res.json();
};

// =============================================================
// RENDER — Stat Cards
// =============================================================
function renderStatCards(d) {
    const total    = d.total_pengajuan       ?? 0;
    const approved = d.total_approved        ?? 0;
    const pending  = d.total_pending         ?? 0;
    const nilai    = d.total_nilai_approved  ?? 0;
    const pengaju  = d.total_karyawan_pengaju ?? 0;

    // Card 1
    document.getElementById('stat-total-pengajuan').textContent = total;
    document.getElementById('stat-approved-label').textContent  = approved + ' Approved';
    document.getElementById('stat-pending-label').textContent   = pending  + ' Pending';
    document.getElementById('stat-progress-approved').style.width = pct(approved, total) + '%';

    // Card 2
    document.getElementById('stat-nilai-approved').textContent = rupiah(nilai);
    document.getElementById('stat-nilai-pct').textContent      = approved + ' klaim';
    document.getElementById('stat-progress-nilai').style.width = pct(approved, total) + '%';

    // Card 3
    document.getElementById('stat-karyawan-pengaju').textContent     = pengaju;
    document.getElementById('stat-karyawan-pengaju-sub').textContent = pengaju + ' orang';

    // Card 4
    document.getElementById('stat-pending').textContent      = pending;
    document.getElementById('stat-pending-bulan').textContent = (d.pending_bulan_ini ?? 0) + ' bulan ini';
    document.getElementById('stat-progress-pending').style.width = pct(pending, total) + '%';
}

// =============================================================
// RENDER — Chart Tren (area)
// =============================================================
function renderChartTren(data) {
    const months   = data.map(d => MONTHS[d.month - 1]);
    const nominals = data.map(d => d.total_nominal);
    const counts   = data.map(d => d.total_count);

    const base = {
        chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0, stops: [0, 90, 100] } },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        xaxis: { categories: months, axisBorder: { show: false }, axisTicks: { show: false },
                 labels: { style: { colors: '#9e9e9e', fontSize: '12px' } } },
        markers: { size: 4, hover: { size: 6 } },
    };

    if (chartTrenNominal) chartTrenNominal.destroy();
    chartTrenNominal = new ApexCharts(document.getElementById('chart-tren-nominal'), {
        ...base,
        series: [{ name: 'Total Klaim', data: nominals }],
        colors: ['#7239EA'],
        yaxis: { labels: { formatter: v => rupiah(v), style: { colors: '#9e9e9e', fontSize: '11px' } } },
        tooltip: { y: { formatter: v => rupiahFull(v) } },
    });
    chartTrenNominal.render();

    if (chartTrenCount) chartTrenCount.destroy();
    chartTrenCount = new ApexCharts(document.getElementById('chart-tren-count'), {
        ...base,
        series: [{ name: 'Jumlah Klaim', data: counts }],
        colors: ['#009EF7'],
        yaxis: { labels: { formatter: v => v + ' klaim', style: { colors: '#9e9e9e', fontSize: '11px' } } },
        tooltip: { y: { formatter: v => v + ' klaim' } },
    });
    chartTrenCount.render();
}

// =============================================================
// RENDER — Chart Breakdown Donut
// =============================================================
function renderChartBreakdown(bd) {
    const labels = ['Dokter', 'Obat', 'Kacamata', 'Gigi'];
    const values = [bd.dokter ?? 0, bd.obat ?? 0, bd.kacamata ?? 0, bd.gigi ?? 0];
    const colors = [COLORS.dokter, COLORS.obat, COLORS.kacamata, COLORS.gigi];
    const total  = values.reduce((a, b) => a + b, 0);

    if (chartBreakdown) chartBreakdown.destroy();
    chartBreakdown = new ApexCharts(document.getElementById('chart-breakdown'), {
        chart: { type: 'donut', height: 220, toolbar: { show: false } },
        series: values, labels, colors,
        legend: { show: false },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%',
            labels: { show: true, total: {
                show: true, label: 'Total', color: '#5e6278',
                formatter: () => rupiah(total),
            }}
        }}},
        tooltip: { y: { formatter: v => rupiahFull(v) } },
    });
    chartBreakdown.render();

    // Legend + rincian tagihan sidebar
    const legend = document.getElementById('breakdown-legend');
    legend.innerHTML = labels.map((lbl, i) => `
        <div class="d-flex align-items-center mb-3">
            <span style="width:10px;height:10px;border-radius:50%;background:${colors[i]};display:inline-block;" class="me-2 flex-shrink-0"></span>
            <span class="fs-7 text-gray-700 flex-grow-1">${lbl}</span>
            <span class="fs-7 fw-bold text-gray-900">${rupiahFull(values[i])}</span>
            <span class="fs-7 text-gray-500 ms-2">(${pct(values[i], total)}%)</span>
        </div>
    `).join('');

    // Rincian sidebar (card kanan bawah) — teks saja, tidak ada chart lagi
    const rincian = document.getElementById('rincian-tagihan');
    const RINCIAN_COLORS = ['danger','primary','info','success'];
    rincian.innerHTML = labels.map((lbl, i) => `
        <div class="d-flex align-items-center mb-5">
            <span class="bullet bullet-vertical h-40px me-4 bg-${RINCIAN_COLORS[i]}" style="width:5px;border-radius:2px;min-width:5px;"></span>
            <div class="flex-grow-1">
                <div class="text-gray-700 fw-semibold fs-7">${lbl}</div>
                <div class="text-gray-500 fs-8">${pct(values[i], total)}% dari total</div>
            </div>
            <span class="text-gray-900 fw-bold fs-7">${rupiahFull(values[i])}</span>
        </div>
    `).join('');
}

// =============================================================
// RENDER — Chart Status per Bulan (stacked bar)
// =============================================================
function renderChartStatusPerBulan(data) {
    const months   = data.map(d => MONTHS[d.month - 1]);
    const approved = data.map(d => d.approved);
    const pending  = data.map(d => d.pending);

    if (chartStatusPerBulan) chartStatusPerBulan.destroy();
    chartStatusPerBulan = new ApexCharts(document.getElementById('chart-status-perbulan'), {
        chart: { type: 'bar', height: 380, toolbar: { show: false }, stacked: true },
        plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '50%' } },
        series: [
            { name: 'Approved', data: approved },
            { name: 'Pending',  data: pending  },
        ],
        colors: ['#50CD89', '#F6C000'],
        xaxis: { categories: months, axisBorder: { show: false }, axisTicks: { show: false },
                 labels: { style: { colors: '#9e9e9e', fontSize: '12px' } } },
        yaxis: { labels: { style: { colors: '#9e9e9e', fontSize: '11px' } } },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px' },
        tooltip: { y: { formatter: v => v + ' pengajuan' } },
        dataLabels: { enabled: false },
    });
    chartStatusPerBulan.render();
}

// =============================================================
// RENDER — Top 10 Karyawan
// =============================================================
const AVATAR_COLORS = ['primary','success','danger','warning','info'];
function renderTopKaryawan(data) {
    const el = document.getElementById('list-top-karyawan');
    if (!data || !data.length) {
        el.innerHTML = `<div class="text-center text-gray-500 py-10">Tidak ada data.</div>`;
        return;
    }
    el.innerHTML = data.map((k, i) => {
        const color    = AVATAR_COLORS[i % AVATAR_COLORS.length];
        const initials = (k.nama_lengkap ?? '-').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
        return `
        <div class="d-flex align-items-center mb-6">
            <div class="symbol symbol-45px me-4">
                <span class="symbol-label bg-light-${color} text-${color} fw-bold fs-6">${initials}</span>
            </div>
            <div class="flex-grow-1 me-2">
                <span class="text-gray-900 fw-bold fs-6 d-block">${k.nama_lengkap ?? '-'}</span>
                <span class="text-gray-500 fw-semibold fs-7">${k.nik ?? '-'} &bull; ${k.company_name ?? '-'}</span>
            </div>
            <div class="text-end">
                <span class="text-gray-900 fw-bold fs-7 d-block">${rupiahFull(k.total_klaim)}</span>
                <span class="text-gray-500 fs-8">${k.jumlah_klaim} klaim</span>
            </div>
        </div>`;
    }).join('');
}

// =============================================================
// RENDER — Periode Aktif
// =============================================================
function renderPeriodeAktif(data) {
    const el = document.getElementById('info-periode-aktif');
    if (!data) {
        el.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="ki-duotone ki-information-5 fs-2 text-warning me-3">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <span class="text-gray-600 fs-7">Tidak ada periode aktif saat ini.</span>
            </div>`;
        return;
    }
    document.getElementById('label-periode-aktif').textContent = data.periode ?? '—';
    el.innerHTML = `
        <div class="d-flex align-items-center mb-5">
            <span class="bullet bullet-vertical h-50px me-4 bg-primary" style="width:5px;border-radius:2px;min-width:5px;"></span>
            <div>
                <div class="text-gray-900 fw-bold fs-5">${data.periode ?? '-'}</div>
                <div class="text-gray-500 fw-semibold fs-7">Periode Berjalan</div>
            </div>
            <span class="badge badge-light-success ms-auto">Aktif</span>
        </div>
        <div class="row g-3">
            <div class="col-6">
                <div class="border border-dashed border-gray-300 rounded p-3 text-center">
                    <div class="text-gray-900 fw-bold fs-7">${data.expired_reimburs_start ?? '-'}</div>
                    <div class="text-gray-500 fw-semibold fs-8 mt-1">Mulai Expired</div>
                </div>
            </div>
            <div class="col-6">
                <div class="border border-dashed border-gray-300 rounded p-3 text-center">
                    <div class="text-gray-900 fw-bold fs-7">${data.end_reimburs_start ?? '-'}</div>
                    <div class="text-gray-500 fw-semibold fs-8 mt-1">Akhir Periode</div>
                </div>
            </div>
        </div>`;
}

// =============================================================
// RENDER — Tabel Pengajuan
// =============================================================
function renderTabelPengajuan(result) {
    const tbody = document.getElementById('tabel-pengajuan-body');
    const data  = result.data ?? [];

    if (!data.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-10">
                    <i class="ki-duotone ki-information fs-2hx text-gray-400">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <div class="text-gray-500 mt-2">Tidak ada data.</div>
                </td>
            </tr>`;
        document.getElementById('tabel-info').textContent = '';
        document.getElementById('tabel-pagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = data.map(r => {
        // status sudah di-cast ke boolean di controller
        const badge = parseInt(r.status) === 1
    ? `<span class="badge badge-light-success">Approved</span>`
    : `<span class="badge badge-light-warning">Pending</span>`;
        return `
        <tr>
            <td><span class="text-gray-900 fw-bold fs-7 font-monospace">${r.id_recapan ?? '-'}</span></td>
            <td>
                <span class="text-gray-900 fw-bold d-block fs-6">${r.nama_lengkap ?? '-'}</span>
                <span class="text-gray-500 fw-semibold fs-7">${r.nik ?? '-'}</span>
            </td>
            <td><span class="text-gray-700 fw-semibold fs-7">${r.periode_slip ?? '-'}</span></td>
            <td><span class="text-gray-900 fw-bold fs-7">${rupiahFull(r.total_amount)}</span></td>
            <td>${badge}</td>
            <td><span class="text-gray-600 fw-semibold fs-7">${r.approved_at ?? '-'}</span></td>
        </tr>`;
    }).join('');

    // Info teks
    document.getElementById('tabel-info').textContent =
        `Menampilkan ${result.from ?? 0}–${result.to ?? 0} dari ${result.total ?? 0} data`;

    // Pagination
    const pg          = document.getElementById('tabel-pagination');
    const lastPage    = result.last_page    ?? 1;
    const currentPage = result.current_page ?? 1;

    let pages = '';
    for (let p = 1; p <= lastPage; p++) {
        if (lastPage > 7 && p > 2 && p < lastPage - 1 && Math.abs(p - currentPage) > 1) {
            if (p === 3 || p === lastPage - 2)
                pages += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            continue;
        }
        pages += `<li class="page-item ${p === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
    }

    pg.innerHTML = `
        <ul class="pagination pagination-sm">
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">«</a></li>
            ${pages}
            <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">»</a></li>
        </ul>`;

    pg.querySelectorAll('[data-page]').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            const p = parseInt(el.dataset.page);
            if (p < 1 || p > lastPage) return;
            tabelPage = p;
            loadTabelPengajuan();
        });
    });
}

// =============================================================
// LOAD FUNCTIONS
// =============================================================
async function loadSummary() {
    try {
        const d = await apiFetch('summary', { year: YEAR() });
        renderStatCards(d);
        renderChartBreakdown(d.breakdown ?? {});
    } catch (e) { console.error('summary:', e); }
}

async function loadTren() {
    try {
        const d = await apiFetch('tren', { year: YEAR() });
        renderChartTren(d);
    } catch (e) { console.error('tren:', e); }
}

async function loadTopKaryawan() {
    try {
        const d = await apiFetch('top-karyawan', { year: YEAR() });
        renderTopKaryawan(d);
        document.getElementById('label-top-year').textContent = YEAR();
    } catch (e) { console.error('top-karyawan:', e); }
}

async function loadPerBulan() {
    try {
        const d = await apiFetch('per-bulan', { year: YEAR() });
        renderChartStatusPerBulan(d);
    } catch (e) { console.error('per-bulan:', e); }
}

async function loadPeriodeAktif() {
    try {
        const d = await apiFetch('periode-aktif');
        renderPeriodeAktif(d);
    } catch (e) { console.error('periode-aktif:', e); }
}

let tabelPage   = 1;
let tabelSearch = '';
let tabelStatus = '';

async function loadTabelPengajuan() {
    try {
        const result = await apiFetch('pengajuan', {
            year  : YEAR(),
            page  : tabelPage,
            search: tabelSearch,
            status: tabelStatus,
        });
        renderTabelPengajuan(result);
    } catch (e) { console.error('pengajuan:', e); }
}

function loadAll() {
    tabelPage = 1;
    loadSummary();
    loadTren();
    loadTopKaryawan();
    loadPerBulan();
    loadTabelPengajuan();
}

// =============================================================
// EVENTS
// =============================================================
document.getElementById('filter-year').addEventListener('change', loadAll);

document.getElementById('filter-status-tabel').addEventListener('change', function () {
    tabelStatus = this.value; // '' | '0' | '1'
    tabelPage   = 1;
    loadTabelPengajuan();
});

let searchTimer;
document.getElementById('search-pengajuan').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        tabelSearch = this.value.trim();
        tabelPage   = 1;
        loadTabelPengajuan();
    }, 400);
});

// =============================================================
// INIT
// =============================================================
document.addEventListener('DOMContentLoaded', () => {
    loadPeriodeAktif();
    loadAll();
});
</script>
@endpush