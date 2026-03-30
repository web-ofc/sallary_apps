@extends('layouts.master')

@section('title', 'Rekap Total Reimbursement')

@section('content')

{{-- ===================== TOOLBAR ===================== --}}
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                Rekap Total Reimbursement
            </h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ url('/') }}" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Reimbursement</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-gray-900">Rekap Total</li>
            </ul>
        </div>
    </div>
</div>

{{-- ===================== CONTENT ===================== --}}
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container container-xxl">

        {{-- ===== SUMMARY CARDS ===== --}}
        <div class="row g-5 g-xl-10 mb-5">

            {{-- Grand Total --}}
            <div class="col-xl-4">
                <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100"
                    style="background-color: #1C325E;">
                    <div class="card-header pt-5 mb-3">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="grand-total-display">
                                <span class="spinner-border spinner-border-sm text-white"></span>
                            </span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Grand Total Tagihan</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                            <span id="filter-label-display">Semua Periode</span>
                            <span class="text-white opacity-50 fs-7">Filter aktif</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Karyawan --}}
            <div class="col-xl-4">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2" id="total-karyawan-display">-</span>
                            <span class="text-gray-500 pt-1 fw-semibold fs-6">Total Karyawan</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <div class="d-flex justify-content-between mt-auto mb-2 w-100">
                            <span class="fw-semibold text-muted fs-7">Rekap per karyawan</span>
                            <span class="badge badge-light-primary">
                                <i class="ki-outline ki-people fs-7"></i> Karyawan
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Pengajuan --}}
            <div class="col-xl-4">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2" id="total-pengajuan-display">-</span>
                            <span class="text-gray-500 pt-1 fw-semibold fs-6">Total Data Terfilter</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <div class="d-flex justify-content-between mt-auto mb-2 w-100">
                            <span class="fw-semibold text-muted fs-7">Jumlah record ditemukan</span>
                            <span class="badge badge-light-success">
                                <i class="ki-outline ki-document fs-7"></i> Record
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        {{-- ===== END SUMMARY CARDS ===== --}}

        {{-- ===== MAIN TABLE CARD ===== --}}
        <div class="card card-flush">

            {{-- Card Header --}}
            <div class="card-header align-items-center py-5 gap-2 gap-md-5 flex-wrap">

                {{-- Search --}}
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" id="kt_search_input"
                            class="form-control form-control-solid w-250px ps-14"
                            placeholder="Cari nama / NIK karyawan..." />
                    </div>
                </div>

                {{-- Toolbar kanan --}}
                <div class="card-toolbar flex-row-fluid justify-content-end gap-3 flex-wrap">

                    {{-- Filter Periode --}}
                    <div class="w-200px">
                        <select class="form-select form-select-solid" id="filter_periode"
                            data-control="select2"
                            data-placeholder="Semua Periode"
                            data-allow-clear="true">
                            <option value="">Semua Periode</option>
                            @foreach ($periodeList as $periode)
                                <option value="{{ $periode }}">{{ $periode }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Reset --}}
                    <button class="btn btn-light-danger" id="btn-reset-filter">
                        <i class="ki-outline ki-arrows-circle fs-4"></i> Reset
                    </button>

                    {{-- Export Excel --}}
                    <a href="#" id="btn-export-excel" class="btn btn-light-success">
                        <i class="ki-outline ki-exit-up fs-4"></i>
                        <span id="btn-export-label">Export Excel</span>
                    </a>

                </div>
            </div>

            {{-- Card Body --}}
            <div class="card-body pt-0">
                <table class="table align-middle table-row-dashed fs-6 gy-5"
                    id="kt_table_sum_reimbursement">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-50px">No</th>
                            <th class="min-w-150px">Karyawan</th>
                            <th class="min-w-120px">Periode Slip</th>
                            <th class="min-w-100px text-center">Jumlah Pengajuan</th>
                            <th class="min-w-150px text-end">Total Tagihan</th>
                            <th class="min-w-100px text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                    <tfoot>
                        <tr class="fw-bold text-gray-800 bg-light">
                            <td colspan="4" class="text-end pe-4 text-gray-600 fs-7">
                                Grand Total (semua data filter):
                            </td>
                            <td class="text-end" id="footer-grand-total">
                                <span class="text-primary fw-bolder fs-6">-</span>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
        {{-- ===== END MAIN TABLE CARD ===== --}}

    </div>
</div>

@endsection

@push('scripts')
<script>
"use strict";

let dataTable;
let selectedPeriode = '';

// ============================================================
//  DATATABLES INIT
// ============================================================
function initDataTable() {
    dataTable = $('#kt_table_sum_reimbursement').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route('sumreimburstment.data') }}',
            type: 'GET',
            data: function (d) {
                d.periode_slip = selectedPeriode;
            },
            dataSrc: function (response) {
                updateSummaryCards(response);
                updateExportLink();
                return response.data;
            }
        },
        columns: [
            { data: 'no',                   orderable: false, searchable: false },
            { data: 'karyawan',             name: 'karyawan' },
            { data: 'periode_slip',         name: 'periode_slip' },
            { data: 'jumlah_reimbursement', name: 'jumlah_reimbursement', className: 'text-center' },
            { data: 'total_harga',          name: 'total_harga',          className: 'text-end' },
            { data: 'status',               name: 'status',               className: 'text-center' },
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        language: {
            processing: `<div class="d-flex align-items-center gap-2 text-muted">
                            <span class="spinner-border spinner-border-sm text-primary"></span>
                            Memuat data...
                         </div>`,
            emptyTable: `<div class="d-flex flex-column align-items-center py-10 text-muted">
                            <i class="ki-outline ki-document fs-2tx text-gray-300 mb-3"></i>
                            <span class="fs-5">Tidak ada data ditemukan</span>
                         </div>`,
            lengthMenu:   'Tampilkan _MENU_ data',
            info:         'Menampilkan _START_–_END_ dari _TOTAL_ data',
            infoEmpty:    'Tidak ada data',
            infoFiltered: '(dari _MAX_ total)',
            zeroRecords:  'Tidak ada data sesuai filter',
            paginate: {
                next:     '<i class="ki-outline ki-right fs-6"></i>',
                previous: '<i class="ki-outline ki-left fs-6"></i>',
            },
        },
    });
}

// ============================================================
//  UPDATE SUMMARY CARDS
// ============================================================
function updateSummaryCards(response) {
    $('#grand-total-display').html(response.grand_total ?? 'Rp 0');
    $('#total-karyawan-display').text(response.recordsFiltered ?? 0);
    $('#total-pengajuan-display').text(response.recordsFiltered ?? 0);
    $('#footer-grand-total').html(
        `<span class="text-primary fw-bolder fs-6">${response.grand_total ?? 'Rp 0'}</span>`
    );
    $('#filter-label-display').text(selectedPeriode ? `Periode: ${selectedPeriode}` : 'Semua Periode');
}

// ============================================================
//  UPDATE EXPORT LINK (ikuti filter periode aktif)
// ============================================================
function updateExportLink() {
    let exportUrl = '{{ route('sumreimburstment.export') }}';
    if (selectedPeriode) {
        exportUrl += '?periode_slip=' + encodeURIComponent(selectedPeriode);
    }
    $('#btn-export-excel').attr('href', exportUrl);

    // Label tombol
    const label = selectedPeriode
        ? `Export Excel (${selectedPeriode})`
        : 'Export Excel (Semua)';
    $('#btn-export-label').text(label);
}

// ============================================================
//  FILTER PERIODE
// ============================================================
$('#filter_periode').on('change', function () {
    selectedPeriode = $(this).val() ?? '';
    dataTable.ajax.reload();
});

// ============================================================
//  SEARCH (debounce 400ms)
// ============================================================
let searchTimeout;
$('#kt_search_input').on('keyup', function () {
    clearTimeout(searchTimeout);
    const val = $(this).val();
    searchTimeout = setTimeout(() => dataTable.search(val).draw(), 400);
});

// ============================================================
//  RESET FILTER
// ============================================================
$('#btn-reset-filter').on('click', function () {
    selectedPeriode = '';
    $('#filter_periode').val('').trigger('change');
    $('#kt_search_input').val('');
    dataTable.search('').draw();
});

// ============================================================
//  LOADING INDICATOR SAAT EXPORT
// ============================================================
$('#btn-export-excel').on('click', function () {
    const $btn = $(this);
    const originalHtml = $btn.html();

    $btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Menyiapkan...')
        .addClass('disabled');

    // Kembalikan tombol setelah 3 detik (file mulai didownload)
    setTimeout(() => {
        $btn.html(originalHtml).removeClass('disabled');
    }, 3000);
});

// ============================================================
//  INIT
// ============================================================
$(document).ready(function () {
    if (typeof KTComponents !== 'undefined') KTComponents.init();
    initDataTable();
    updateExportLink();
});
</script>
@endpush