@extends('layouts.master')

@section('title', 'Analisa Jenis Penyakit Karyawan')

@push('css')
{{-- Select2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
{{-- Flatpickr --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    .filter-card {
        border: 1px solid #e4e6ef;
        border-radius: 12px;
    }
    .filter-range-btn {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .filter-range-btn.active {
        background-color: #009ef7 !important;
        color: #ffffff !important;
        border-color: #009ef7 !important;
    }
    #custom-range-wrapper {
        display: none;
    }
    #custom-range-wrapper.show {
        display: flex;
    }
    .stat-card {
        border-left: 4px solid #009ef7;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    /* Sembunyikan tabel sebelum search */
    #section-result {
        display: none;
    }
</style>
@endpush

@section('content')
{{-- Page Header --}}
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Analisa Jenis Penyakit Karyawan
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Report</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Reimbursement</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-dark">Analisa Penyakit</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Filter Card --}}
            <div class="card filter-card mb-6">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <span class="svg-icon svg-icon-1 me-2">
                            <i class="ki-duotone ki-filter fs-2 text-primary">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-5 mb-1">Filter Pencarian</span>
                            <span class="text-muted fw-semibold fs-7">Pilih karyawan dan periode untuk analisa</span>
                        </h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-5">
                        {{-- Select Karyawan --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Karyawan</label>
                            <select id="select-karyawan" class="form-select form-select-solid" style="width:100%">
                                <option value="">-- Cari nama / NIK karyawan --</option>
                            </select>
                            <div class="form-text text-muted">Ketik minimal 2 karakter untuk mencari</div>
                        </div>

                        {{-- Filter Range --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Periode</label>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-sm btn-light-primary filter-range-btn active" data-range="this_year">
                                    Tahun Ini
                                </button>
                                <button type="button" class="btn btn-sm btn-light-primary filter-range-btn" data-range="this_month">
                                    Bulan Ini
                                </button>
                                <button type="button" class="btn btn-sm btn-light-primary filter-range-btn" data-range="last_month">
                                    Bulan Lalu
                                </button>
                                <button type="button" class="btn btn-sm btn-light-primary filter-range-btn" data-range="last_7_days">
                                    7 Hari Terakhir
                                </button>
                                <button type="button" class="btn btn-sm btn-light-primary filter-range-btn" data-range="custom">
                                    Custom Range
                                </button>
                            </div>

                            {{-- Custom Range Picker --}}
                            <div id="custom-range-wrapper" class="gap-3 align-items-center">
                                <div class="flex-fill">
                                    <input type="text" id="date-from" class="form-control form-control-solid" placeholder="Dari tanggal">
                                </div>
                                <span class="fw-bold text-muted">s/d</span>
                                <div class="flex-fill">
                                    <input type="text" id="date-to" class="form-control form-control-solid" placeholder="Sampai tanggal">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Search --}}
                    <div class="d-flex justify-content-end mt-5">
                        <button type="button" id="btn-reset" class="btn btn-light me-3">
                            <i class="ki-duotone ki-arrows-circle fs-4"><span class="path1"></span><span class="path2"></span></i>
                            Reset
                        </button>
                        <button type="button" id="btn-search" class="btn btn-primary">
                            <i class="ki-duotone ki-magnifier fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Tampilkan Analisa
                        </button>
                    </div>
                </div>
            </div>

            {{-- Hasil Section (hidden sampai search) --}}
            <div id="section-result">

                {{-- Info Karyawan + Summary --}}
                <div class="row g-5 mb-6" id="summary-cards">
                    <div class="col-md-4">
                        <div class="card stat-card h-100">
                            <div class="card-body d-flex align-items-center gap-4">
                                <div class="symbol symbol-50px">
                                    <span class="symbol-label bg-light-primary">
                                        <i class="ki-duotone ki-profile-circle fs-2x text-primary">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        </i>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-muted fs-7 fw-semibold">Karyawan</div>
                                    <div class="fw-bold fs-5 text-dark" id="summary-nama">-</div>
                                    <div class="text-muted fs-8" id="summary-nik">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100" style="border-left-color: #50cd89">
                            <div class="card-body d-flex align-items-center gap-4">
                                <div class="symbol symbol-50px">
                                    <span class="symbol-label bg-light-success">
                                        <i class="ki-duotone ki-pulse fs-2x text-success">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-muted fs-7 fw-semibold">Total Sakit</div>
                                    <div class="fw-bold fs-3 text-dark" id="summary-total-sakit">0</div>
                                    <div class="text-muted fs-8">kali dalam periode</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100" style="border-left-color: #f1416c">
                            <div class="card-body d-flex align-items-center gap-4">
                                <div class="symbol symbol-50px">
                                    <span class="symbol-label bg-light-danger">
                                        <i class="ki-duotone ki-dollar fs-2x text-danger">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        </i>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-muted fs-7 fw-semibold">Total Tagihan</div>
                                    <div class="fw-bold fs-5 text-dark" id="summary-total-tagihan">Rp 0</div>
                                    <div class="text-muted fs-8">dalam periode ini</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabel DataTable --}}
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="card-label fw-bold fs-5">
                                Detail Jenis Penyakit
                                <span class="text-muted fw-semibold fs-7 d-block mt-1" id="label-periode-aktif">-</span>
                            </h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <table id="tabel-penyakit" class="table table-striped table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4 rounded-start" style="width: 50px">#</th>
                                    <th>Nama Penyakit</th>
                                    <th class="text-center" style="width: 150px">Jumlah Sakit</th>
                                    <th class="text-end pe-4 rounded-end" style="width: 180px">Total Tagihan</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
            {{-- end section-result --}}

        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Select2 --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
{{-- Flatpickr --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function () {

    // =============================================
    // 1. SELECT2 — Karyawan
    // =============================================
    $('#select-karyawan').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari nama / NIK karyawan --',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("reportreimbursements.search-karyawan") }}',
            type: 'GET',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true,
        },
    });

    // =============================================
    // 2. FILTER RANGE BUTTON
    // =============================================
    let activeRange = 'this_year';

    $('.filter-range-btn').on('click', function () {
        $('.filter-range-btn').removeClass('active');
        $(this).addClass('active');
        activeRange = $(this).data('range');

        if (activeRange === 'custom') {
            $('#custom-range-wrapper').addClass('show');
        } else {
            $('#custom-range-wrapper').removeClass('show');
        }
    });

    // =============================================
    // 3. FLATPICKR — Custom Range
    // =============================================
    flatpickr('#date-from', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    flatpickr('#date-to', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    // =============================================
    // 4. DATATABLE — Inisialisasi (POST, serverSide)
    // =============================================
    let dtTable = null;

    function initOrReloadDatatable(karyawanId, filterRange, dateFrom, dateTo) {
        if (dtTable) {
            dtTable.destroy();
            $('#tabel-penyakit tbody').empty();
        }

        dtTable = $('#tabel-penyakit').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("reportreimbursements.data") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                data: {
                    karyawan_id:  karyawanId,
                    filter_range: filterRange,
                    date_from:    dateFrom,
                    date_to:      dateTo,
                },
                dataSrc: function (json) {
                    updateSummary(json.data);
                    return json.data;
                },
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false,
                  className: 'text-center ps-4 text-muted fw-semibold fs-7' },
                { data: 'nama_penyakit_display', name: 'jp.nama_penyakit' },
                { data: 'jumlah_sakit_display',  name: 'jumlah_sakit', className: 'text-center' },
                { data: 'total_tagihan_display', name: 'total_tagihan',  className: 'text-end pe-4' },
            ],
            order: [[2, 'desc']],
            language: {
                processing:  '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable:  'Tidak ada data penyakit pada periode ini',
                zeroRecords: 'Tidak ditemukan data yang sesuai',
                info:        'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty:   'Tidak ada data',
                search:      'Cari:',
                paginate: {
                    first:    'Pertama',
                    last:     'Terakhir',
                    next:     'Berikutnya',
                    previous: 'Sebelumnya',
                },
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        });
    }

    // =============================================
    // 5. UPDATE SUMMARY CARDS
    // =============================================
    function updateSummary(data) {
        let totalSakit    = 0;
        let totalTagihan  = 0;

        data.forEach(function (row) {
            totalSakit   += parseInt(row.jumlah_sakit)   || 0;
            totalTagihan += parseInt(row.total_tagihan)  || 0;
        });

        $('#summary-total-sakit').text(totalSakit);
        $('#summary-total-tagihan').text('Rp ' + totalTagihan.toLocaleString('id-ID'));

        // Label periode aktif
        const rangeLabel = {
            'this_year':   'Tahun ini',
            'this_month':  'Bulan ini',
            'last_month':  'Bulan lalu',
            'last_7_days': '7 hari terakhir',
            'custom':      ($('#date-from').val() || '-') + ' s/d ' + ($('#date-to').val() || '-'),
        };
        $('#label-periode-aktif').text(rangeLabel[activeRange] || '-');
    }

    // =============================================
    // 6. TOMBOL SEARCH
    // =============================================
    $('#btn-search').on('click', function () {
        const karyawanId = $('#select-karyawan').val();
        const selectedText = $('#select-karyawan option:selected').text();

        if (!karyawanId) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops!',
                text: 'Pilih karyawan terlebih dahulu.',
                confirmButtonText: 'Oke',
                confirmButtonColor: '#009ef7',
            });
            return;
        }

        if (activeRange === 'custom') {
            const df = $('#date-from').val();
            const dt = $('#date-to').val();
            if (!df || !dt) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops!',
                    text: 'Isi tanggal dari dan sampai untuk custom range.',
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#009ef7',
                });
                return;
            }
        }

        // Update nama karyawan di summary
        const parts = selectedText.split(' — ');
        $('#summary-nama').text(parts[0] ?? '-');
        $('#summary-nik').text(parts[1] ?? '');

        // Tampilkan section result
        $('#section-result').show();

        // Load datatable
        initOrReloadDatatable(
            karyawanId,
            activeRange,
            $('#date-from').val() || null,
            $('#date-to').val()   || null
        );
    });

    // =============================================
    // 7. TOMBOL RESET
    // =============================================
    $('#btn-reset').on('click', function () {
        $('#select-karyawan').val(null).trigger('change');
        $('.filter-range-btn').removeClass('active');
        $('[data-range="this_year"]').addClass('active');
        activeRange = 'this_year';
        $('#custom-range-wrapper').removeClass('show');
        $('#date-from').val('');
        $('#date-to').val('');
        $('#section-result').hide();

        if (dtTable) {
            dtTable.destroy();
            dtTable = null;
        }
    });

});
</script>
@endpush