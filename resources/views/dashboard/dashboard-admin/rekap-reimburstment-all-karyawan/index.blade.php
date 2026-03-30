@extends('layouts.master')

@section('title', 'Pivot Rekap Reimburstment Karyawan')

@push('styles')
{{-- Flatpickr --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* ===== FILTER BAR ===== */
    .filter-range-btn {
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1.5px solid #e4e6ef;
        border-radius: 8px;
        background: #fff;
        padding: 7px 16px;
        font-weight: 600;
        font-size: 0.8rem;
        color: #7e8299;
        white-space: nowrap;
    }
    .filter-range-btn:hover {
        border-color: #009ef7;
        color: #009ef7;
    }
    .filter-range-btn.active {
        background: #009ef7;
        border-color: #009ef7;
        color: #fff !important;
    }
  

    /* ===== DASHBOARD CARDS ===== */
    .stat-mini-card {
        border-radius: 12px;
        border: 1px solid #f1f1f2;
        transition: box-shadow 0.2s, transform 0.2s;
        overflow: hidden;
    }
    .stat-mini-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .stat-mini-card .card-accent {
        height: 4px;
        width: 100%;
    }
    .stat-mini-card .stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.5px;
    }
    .stat-mini-card .stat-label {
        font-size: 0.78rem;
        color: #a1a5b7;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-mini-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-mini-card .stat-change {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 20px;
    }

    /* ===== TOP PENYAKIT BAR ===== */
    .penyakit-bar-item {
        margin-bottom: 10px;
    }
    .penyakit-bar-item:last-child {
        margin-bottom: 0;
    }
    .penyakit-bar-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #3f4254;
        margin-bottom: 4px;
        display: flex;
        justify-content: space-between;
    }
    .penyakit-bar-track {
        height: 8px;
        background: #f1f1f2;
        border-radius: 4px;
        overflow: hidden;
    }
    .penyakit-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.6s ease;
    }

    .pivot-cell {
        padding: 6px 10px;
        text-align: center;
        height: 100%;
    }
    .pivot-cell.has-value {
        background: #f0f9ff;
        border-left: 1px solid #e0f0ff;
        border-right: 1px solid #e0f0ff;
    }
    .pivot-cell.empty {
        color: #c5c5c5;
        font-size: 1.1rem;
        letter-spacing: 2px;
    }
    .pivot-kali {
        font-weight: 700;
        font-size: 0.82rem;
        color: #009ef7;
        margin-bottom: 2px;
    }
    .pivot-kali .badge-kali {
        background: #e8f5ff;
        color: #009ef7;
        border-radius: 20px;
        padding: 2px 8px;
        font-size: 0.75rem;
    }
    .pivot-tagihan {
        font-size: 0.75rem;
        color: #3f4254;
        font-weight: 500;
        white-space: nowrap;
    }
    td.col-total {
        background: #f9f9f9;
        border-left: 2px solid #e4e6ef;
        padding: 10px 12px;
        text-align: right;
        min-width: 140px;
    }
    .total-kali {
        font-size: 0.78rem;
        color: #7e8299;
        font-weight: 600;
    }
    .total-tagihan {
        font-size: 0.85rem;
        font-weight: 700;
        color: #181c32;
    }
    .th-penyakit-label {
        max-width: 110px;
        white-space: normal;
        word-break: break-word;
        line-height: 1.3;
        font-size: 0.78rem;
        text-align: center;
    }
    .karyawan-nik {
        font-size: 0.75rem;
        color: #a1a5b7;
        font-weight: 400;
    }

    /* Skeleton loading cards */
    .skeleton {
        background: linear-gradient(90deg, #f1f1f2 25%, #e4e6ef 50%, #f1f1f2 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.4s infinite;
        border-radius: 6px;
        display: inline-block;
    }
    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-column-fluid">

    {{-- ===== TOOLBAR ===== --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Pivot Rekap Reimburstment Karyawan
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Report</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Reimbursement</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-dark">Pivot Penyakit</li>
                </ul>
            </div>
            
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="">

            {{-- ===== FILTER CARD ===== --}}
            <div class="card mb-6">
                <div class="card-body py-4 px-6">
                    <div class="d-flex flex-wrap align-items-center gap-2">

                        {{-- Label --}}
                        <span class="fw-bold text-gray-700 fs-7 me-2">Periode:</span>

                        {{-- Range buttons --}}
                        <div class="d-flex flex-wrap gap-1" id="range-btn-group">
                            <button type="button"
                                class="btn btn-sm btn-light-primary active filter-range-btn"
                                data-range="this_year">
                                Tahun Ini
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-light filter-range-btn"
                                data-range="this_month">
                                Bulan Ini
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-light filter-range-btn"
                                data-range="last_month">
                                Bulan Lalu
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-light filter-range-btn"
                                data-range="last_7_days">
                                7 Hari Terakhir
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-light filter-range-btn d-flex align-items-center gap-1"
                                data-range="custom">
                                <i class="ki-duotone ki-calendar fs-7">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                Custom
                            </button>
                        </div>

                        {{-- Custom range picker --}}
                        <div id="custom-range-wrapper" class="d-none d-flex align-items-center gap-2 ms-1">
                            <input type="text" id="date-from"
                                class="form-control form-control-sm form-control-solid"
                                placeholder="Dari"
                                autocomplete="off"
                                style="width: 130px">
                            <span class="text-muted">—</span>
                            <input type="text" id="date-to"
                                class="form-control form-control-sm form-control-solid"
                                placeholder="Sampai"
                                autocomplete="off"
                                style="width: 130px">
                            <button type="button" id="btn-apply-custom"
                                class="btn btn-sm btn-primary px-4">
                                Terapkan
                            </button>
                        </div>

                        {{-- Label periode aktif --}}
                        <span id="label-periode"
                            class="badge badge-light-primary fw-semibold fs-8 ms-auto px-3 py-2 d-flex align-items-center gap-1">
                            <i class="ki-duotone ki-calendar-2 fs-7">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                            </i>
                            <span id="label-periode-text">Tahun Ini</span>
                        </span>

                    </div>
                </div>
            </div>

           {{-- ===== DASHBOARD CARDS ===== --}}
            <div class="row g-4 mb-6" id="dashboard-cards">

                {{-- Card 1: Karyawan Terdampak --}}
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:16px; overflow:hidden;">
                        <div class="card-body p-6">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <span class="text-muted fw-semibold fs-7 text-uppercase ls-1">Karyawan Terdampak</span>
                                <div class="w-40px h-40px rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="ki-duotone ki-people fs-3 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                        <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="fs-2hx fw-bold text-gray-900 mb-1" id="ds-total-karyawan">
                                <span class="skeleton" style="width:50px;height:32px;border-radius:8px;display:inline-block;">&nbsp;</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="badge badge-light-primary fs-8">Aktif periode ini</span>
                            </div>
                        </div>
                        <div style="height:4px; background: linear-gradient(90deg, #3E97FF, #a0c4ff);"></div>
                    </div>
                </div>

                {{-- Card 2: Total Kasus Sakit --}}
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:16px; overflow:hidden;">
                        <div class="card-body p-6">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <span class="text-muted fw-semibold fs-7 text-uppercase ls-1">Total Kasus Sakit</span>
                                <div class="w-40px h-40px rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="ki-duotone ki-pulse fs-3 text-warning">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="fs-2hx fw-bold text-gray-900 mb-1" id="ds-total-kasus">
                                <span class="skeleton" style="width:50px;height:32px;border-radius:8px;display:inline-block;">&nbsp;</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="badge badge-light-warning fs-8">Semua status</span>
                            </div>
                        </div>
                        <div style="height:4px; background: linear-gradient(90deg, #F6C000, #ffe680);"></div>
                    </div>
                </div>

                {{-- Card 3: Total Tagihan --}}
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:16px; overflow:hidden;">
                        <div class="card-body p-6">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <span class="text-muted fw-semibold fs-7 text-uppercase ls-1">Total Tagihan</span>
                                <div class="w-40px h-40px rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="ki-duotone ki-dollar fs-3 text-danger">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="fs-2x fw-bold text-gray-900 mb-1" id="ds-total-tagihan">
                                <span class="skeleton" style="width:90px;height:32px;border-radius:8px;display:inline-block;">&nbsp;</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="badge badge-light-danger fs-8">Akumulasi biaya</span>
                            </div>
                        </div>
                        <div style="height:4px; background: linear-gradient(90deg, #F1416C, #ffb3c6);"></div>
                    </div>
                </div>

                {{-- Card 4: Jenis Penyakit Aktif --}}
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:16px; overflow:hidden;">
                        <div class="card-body p-6">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <span class="text-muted fw-semibold fs-7 text-uppercase ls-1">Jenis Penyakit Aktif</span>
                                <div class="w-40px h-40px rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="ki-duotone ki-medicine fs-3 text-success">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="fs-2hx fw-bold text-gray-900 mb-1" id="ds-total-penyakit">
                                <span class="skeleton" style="width:50px;height:32px;border-radius:8px;display:inline-block;">&nbsp;</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="badge badge-light-success fs-8">Terdiagnosis</span>
                            </div>
                        </div>
                        <div style="height:4px; background: linear-gradient(90deg, #50CD89, #b3f0d2);"></div>
                    </div>
                </div>

            </div>

           
            {{-- ===== PIVOT TABLE CARD ===== --}}
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="card-label fw-bold fs-5 mb-0">
                            Rekap Semua Karyawan
                            <span class="text-muted fw-semibold fs-7 d-block mt-1">
                                Kolom penyakit otomatis · filter sesuai periode dipilih
                            </span>
                        </h3>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex align-items-center gap-3">
                            <div class="position-relative">
                                <i class="ki-duotone ki-magnifier fs-4 position-absolute ms-3" style="top:50%;transform:translateY(-50%)">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                <input type="text" id="search-karyawan-pivot" class="form-control form-control-sm form-control-solid ps-10" placeholder="Cari nama / NIK...">
                            </div>
                            <span id="badge-total-karyawan" class="badge badge-light-primary fw-bold fs-7 px-3 py-2">
                                <span class="spinner-border spinner-border-sm me-1"></span> memuat...
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">

                    {{-- Loading state kolom --}}
                    <div id="pivot-loading" class="d-flex flex-column align-items-center justify-content-center py-15">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div class="text-muted fw-semibold">Memuat kolom penyakit...</div>
                    </div>

                    {{-- Wrapper tabel --}}
                    <div id="tabel-pivot-wrapper" class="d-none">
                        <div id="pivot-dt-controls" class="d-flex justify-content-between align-items-center mb-4">
                            <div id="pivot-info" class="text-muted fs-7 fw-semibold"></div>
                            <div id="pivot-pagination"></div>
                        </div>
                        <div id="tabel-pivot-scroll" style="overflow-x:auto">
                            <table id="tabel-pivot" class="table table-row-dashed table-row-gray-300 table-hover align-middle gs-3 gy-2 fs-7">
                                <thead id="pivot-thead"></thead>
                                <tbody id="pivot-tbody">
                                    <tr>
                                        <td colspan="99" class="text-center py-10 text-muted">Memuat data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted fs-7">Baris per halaman:</span>
                                <select id="pivot-length" class="form-select form-select-sm form-select-solid" style="width:auto">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div id="pivot-pagination-bottom"></div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Flatpickr --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(document).ready(function () {

    // =============================================
    // STATE
    // =============================================
    let pivotColumns  = [];
    let currentPage   = 1;
    let pageLength    = 10;
    let totalFiltered = 0;
    let totalRecords  = 0;
    let drawCount     = 0;
    let searchTimer   = null;
    let searchValue   = '';
    let orderCol      = 0;
    let orderDir      = 'asc';
    let isLoading     = false;

    // Filter state
    let activeRange = 'this_year';
    let customFrom  = null;
    let customTo    = null;

    const CSRF = $('meta[name="csrf-token"]').attr('content');

    // =============================================
    // FLATPICKR
    // =============================================
    const fpFrom = flatpickr('#date-from', { dateFormat: 'Y-m-d', allowInput: true });
    const fpTo   = flatpickr('#date-to',   { dateFormat: 'Y-m-d', allowInput: true });

    // =============================================
    // FILTER RANGE BUTTONS
    // =============================================
    $('.filter-range-btn').on('click', function () {
        const range = $(this).data('range');

        // Reset semua tombol
        $('#range-btn-group .filter-range-btn')
            .removeClass('btn-light-primary active')
            .addClass('btn-light');
        // Aktifkan tombol dipilih
        $(this)
            .removeClass('btn-light')
            .addClass('btn-light-primary active');

        activeRange = range;

        if (range === 'custom') {
            $('#custom-range-wrapper').removeClass('d-none').addClass('d-flex');
        } else {
            $('#custom-range-wrapper').addClass('d-none').removeClass('d-flex');
            customFrom = null;
            customTo   = null;
            updatePeriodeLabel();
            reloadAll();
        }
    });

    // Apply custom range
    $('#btn-apply-custom').on('click', function () {
        customFrom = $('#date-from').val();
        customTo   = $('#date-to').val();
        if (!customFrom || !customTo) {
            alert('Isi tanggal dari dan sampai terlebih dahulu.');
            return;
        }
        updatePeriodeLabel();
        reloadAll();
    });

    function updatePeriodeLabel() {
        const labels = {
            'this_year':   'Tahun Ini',
            'this_month':  'Bulan Ini',
            'last_month':  'Bulan Lalu',
            'last_7_days': '7 Hari Terakhir',
            'custom':      (customFrom || '-') + ' s/d ' + (customTo || '-'),
        };
        $('#label-periode-text').text(labels[activeRange] || '-');
        $('#top-periode-label').text(labels[activeRange] || 'periode ini');
    }

    function getFilterPayload() {
        return {
            filter_range: activeRange,
            date_from:    customFrom || null,
            date_to:      customTo   || null,
        };
    }

    // =============================================
    // RELOAD ALL — dipanggil saat filter berubah
    // =============================================
    function reloadAll() {
        currentPage = 1;
        loadDashboardStats();
        loadTopPenyakit();
        loadData();
    }

    // =============================================
    // STEP 1: FETCH KOLOM DINAMIS (sekali saja)
    // =============================================
    $.get('{{ route("reportreimbursements.pivot-columns") }}', function (res) {
        pivotColumns = res.columns;
        buildTableHeader();
        $('#pivot-loading').addClass('d-none');
        $('#tabel-pivot-wrapper').removeClass('d-none');
        setTimeout(function () {
            reloadAll();
        }, 50);
    }).fail(function () {
        $('#pivot-loading').html('<div class="text-danger fw-semibold">Gagal memuat kolom. Coba refresh halaman.</div>');
    });

    // =============================================
    // STEP 2: BUILD HEADER DINAMIS
    // =============================================
   function buildTableHeader() {
        let html = '<tr class="fw-bold text-muted bg-light">';
        html += '<th class="col-sticky-no text-center rounded-start">#</th>';
        html += '<th class="col-sticky-nama min-w-200px">Karyawan</th>';

        pivotColumns.forEach(function (p) {
            html += `<th class="text-center min-w-120px">
                <div class="th-penyakit-label">${escHtml(p.nama_penyakit)}</div>
                ${p.kode ? `<span class="badge badge-light text-muted fw-normal mt-1" style="font-size:0.7rem">${escHtml(p.kode)}</span>` : ''}
            </th>`;
        });

        html += '<th class="text-center min-w-150px rounded-end bg-dark text-white">Total</th>';
        html += '</tr>';
        $('#pivot-thead').html(html);
    }

    // =============================================
    // DASHBOARD STATS
    // =============================================
    function loadDashboardStats() {
        $('#ds-total-karyawan, #ds-total-kasus, #ds-total-tagihan, #ds-total-penyakit').html(
            '<span class="skeleton" style="width:50px;height:28px">&nbsp;</span>'
        );

        $.ajax({
            url:    '{{ route("reportreimbursements.pivot-stats") }}',
            method: 'POST',
            data:   { _token: CSRF, ...getFilterPayload() },
            success: function (res) {
                $('#ds-total-karyawan').text(res.total_karyawan.toLocaleString('id-ID'));
                $('#ds-total-kasus').text(res.total_kasus.toLocaleString('id-ID'));
                $('#ds-total-tagihan').text('Rp ' + res.total_tagihan.toLocaleString('id-ID'));
                $('#ds-total-penyakit').text(res.total_penyakit.toLocaleString('id-ID'));
            },
        });
    }

    // =============================================
    // TOP PENYAKIT BAR CHART
    // =============================================
    const barColors = ['#009ef7','#50cd89','#ffc700','#f1416c','#7239ea'];

    function loadTopPenyakit() {
        $('#top-penyakit-container').html(
            Array(5).fill(`
                <div class="penyakit-bar-item">
                    <div class="penyakit-bar-label">
                        <span class="skeleton" style="width:100px;height:13px">&nbsp;</span>
                        <span class="skeleton" style="width:35px;height:13px">&nbsp;</span>
                    </div>
                    <div class="penyakit-bar-track"><div class="penyakit-bar-fill bg-primary" style="width:0%"></div></div>
                </div>
            `).join('')
        );

        $.ajax({
            url:    '{{ route("reportreimbursements.pivot-top-penyakit") }}',
            method: 'POST',
            data:   { _token: CSRF, ...getFilterPayload() },
            success: function (res) {
                if (!res.data || res.data.length === 0) {
                    $('#top-penyakit-container').html('<div class="text-center text-muted py-8 fs-7">Tidak ada data pada periode ini</div>');
                    return;
                }
                const maxVal = res.data[0].total_kasus;
                let html = '';
                res.data.forEach(function (item, i) {
                    const pct   = maxVal > 0 ? Math.round((item.total_kasus / maxVal) * 100) : 0;
                    const color = barColors[i % barColors.length];
                    html += `
                        <div class="penyakit-bar-item">
                            <div class="penyakit-bar-label">
                                <span>${escHtml(item.nama_penyakit)}</span>
                                <span class="fw-bold" style="color:${color}">${item.total_kasus}x</span>
                            </div>
                            <div class="penyakit-bar-track">
                                <div class="penyakit-bar-fill" style="width:${pct}%;background:${color}"></div>
                            </div>
                            <div class="text-muted fs-8 mt-1">Rp ${parseInt(item.total_tagihan).toLocaleString('id-ID')}</div>
                        </div>
                    `;
                });
                $('#top-penyakit-container').html(html);
            },
        });
    }

    // =============================================
    // STEP 3: LOAD PIVOT DATA
    // =============================================
    function loadData() {
        if (isLoading) return;
        isLoading = true;

        const start = (currentPage - 1) * pageLength;
        drawCount++;

        const payload = {
            draw:   drawCount,
            start:  start,
            length: pageLength,
            search: { value: searchValue },
            order:  [{ column: orderCol, dir: orderDir }],
            _token: CSRF,
            ...getFilterPayload(),
        };

        $('#pivot-tbody').html(`
            <tr>
                <td colspan="99" class="text-center py-10">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span class="text-muted fw-semibold">Memuat data...</span>
                </td>
            </tr>
        `);

        $.ajax({
            url:    '{{ route("reportreimbursements.pivot-data") }}',
            method: 'POST',
            data:   payload,
            success: function (res) {
                if (res.draw < drawCount) return;
                isLoading     = false;
                totalRecords  = res.recordsTotal;
                totalFiltered = res.recordsFiltered;

                renderRows(res.data);
                renderPagination();
                renderInfo(start, res.data.length);

                $('#badge-total-karyawan').html(
                    `<i class="ki-duotone ki-people fs-6 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i> ${totalRecords.toLocaleString('id-ID')} karyawan`
                );
            },
            error: function () {
                isLoading = false;
                $('#pivot-tbody').html(`<tr><td colspan="99" class="text-center py-10 text-danger fw-semibold">Gagal memuat data. Coba lagi.</td></tr>`);
            },
        });
    }

    // =============================================
    // STEP 4: RENDER ROWS
    // =============================================
    function renderRows(data) {
        if (!data || data.length === 0) {
            $('#pivot-tbody').html(`<tr><td colspan="99" class="text-center py-12 text-muted fw-semibold fs-6">Tidak ada data ditemukan</td></tr>`);
            return;
        }

        let html = '';
        data.forEach(function (row) {
            html += '<tr>';
            html += `<td class="col-sticky-no text-center text-muted fw-semibold fs-7">${row.no}</td>`;

            const initial = (row.nama_lengkap || '?').charAt(0).toUpperCase();
            const colors  = ['primary','success','warning','danger','info'];
            const color   = colors[simpleHash(row.nama_lengkap) % colors.length];
            html += `
                <td class="col-sticky-nama">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <div class="fw-bold text-dark lh-1">${escHtml(row.nama_lengkap)}</div>
                        </div>
                    </div>
                </td>
            `;

            pivotColumns.forEach(function (p) {
                const cell = row['penyakit_' + p.id];
                if (cell) {
                    html += `<td class="text-center">
                                <span class="badge badge-light-primary fw-bold">${cell.kali}x sakit</span>
                                <div class="text-muted fs-8 mt-1">Rp ${cell.tagihan.toLocaleString('id-ID')}</div>
                            </td>`;
                } else {
                    html += `<td class="text-center text-muted">—</td>`;
                }
            });

            html += `<td class="text-end border-start border-gray-200 ps-4">
                        <span class="badge badge-light-dark fw-bold mb-1">${row.grand_total_kali}x sakit</span>
                        <div class="fw-bold text-dark fs-7">Rp ${row.grand_total_tagihan.toLocaleString('id-ID')}</div>
                    </td>`;
            html += '</tr>';
        });

        $('#pivot-tbody').html(html);
    }

    // =============================================
    // STEP 5: PAGINATION
    // =============================================
    function renderPagination() {
        const totalPages = Math.ceil(totalFiltered / pageLength);
        if (totalPages <= 1) { $('#pivot-pagination-bottom').html(''); return; }

        let html = '<ul class="pagination pagination-sm">';
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}"><i class="ki-duotone ki-left fs-6"><span class="path1"></span><span class="path2"></span></i></a></li>`;

        const maxShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxShow / 2));
        let endPage   = Math.min(totalPages, startPage + maxShow - 1);
        if (endPage - startPage < maxShow - 1) startPage = Math.max(1, endPage - maxShow + 1);

        if (startPage > 1) { html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`; if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`; }
        for (let i = startPage; i <= endPage; i++) { html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`; }
        if (endPage < totalPages) { if (endPage < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`; html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`; }

        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}"><i class="ki-duotone ki-right fs-6"><span class="path1"></span><span class="path2"></span></i></a></li>`;
        html += '</ul>';
        $('#pivot-pagination-bottom').html(html);
    }

    function renderInfo(start, count) {
        const from = totalFiltered === 0 ? 0 : start + 1;
        $('#pivot-info').text(`Menampilkan ${from} - ${start + count} dari ${totalFiltered.toLocaleString('id-ID')} karyawan`);
    }

    // =============================================
    // EVENT HANDLERS
    // =============================================
    $(document).on('click', '#pivot-pagination-bottom .page-link', function (e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (!page || page === currentPage) return;
        currentPage = page;
        loadData();
    });

    $('#pivot-length').on('change', function () {
        pageLength = parseInt($(this).val());
        currentPage = 1;
        loadData();
    });

    $('#search-karyawan-pivot').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            searchValue = $('#search-karyawan-pivot').val().trim();
            currentPage = 1;
            loadData();
        }, 400);
    });

    $(document).on('click', '#tabel-pivot thead th.col-sticky-nama', function () {
        orderDir = orderDir === 'asc' ? 'desc' : 'asc';
        currentPage = 1;
        loadData();
    });

    // =============================================
    // HELPERS
    // =============================================
    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < (str || '').length; i++) hash = (hash * 31 + str.charCodeAt(i)) >>> 0;
        return hash;
    }

});
</script>
@endpush