@extends('layouts.master')

@section('title', $title)

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex flex-wrap flex-stack mb-5">
        <div>
            <h1 class="fw-bold my-2">
                <i class="fas fa-user-times text-danger"></i> Karyawan Belum Diinput Payroll
            </h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Karyawan Belum Diinput</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-3">
            <select id="periodeFilter" class="form-select form-select-sm" style="width: 150px;">
                <option value="">Loading...</option>
            </select>
            <a href="{{ route('dashboard.admin') }}" class="btn btn-sm btn-light-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="alert alert-danger d-flex align-items-center mb-5 py-3" id="infoBanner">
        <i class="fas fa-exclamation-triangle fs-3 me-3"></i>
        <div>
            Menampilkan karyawan aktif yang <strong>belum memiliki data payroll</strong> pada periode
            <strong id="periodeLabel">-</strong>.
            Total: <strong id="totalBelumInput">-</strong> karyawan.
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-5 mb-1">Daftar Karyawan</span>
            </h3>
        </div>
        <div class="card-body py-3">
            <div class="table-responsive">
                <table id="karyawanTable" class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4 w-100">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>NIK</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Join Date</th>
                            <th class="text-center">Jenis Kelamin</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
let table = null;
let currentPeriode = '{{ $periode ?? "" }}';

$(document).ready(function () {
    loadPeriodes();

    $('#periodeFilter').change(function () {
        currentPeriode = $(this).val();
        updateLabel();
        if (table) {
            table.ajax.reload();
        }
    });
});

function loadPeriodes() {
    $.ajax({
        url: '{{ route("dashboard.periodes") }}',
        method: 'GET',
        success: function (response) {
            if (response.success && response.data.length > 0) {
                let options = '';
                response.data.forEach(function (periode) {
                    let selected = periode === currentPeriode ? 'selected' : '';
                    options += `<option value="${periode}" ${selected}>${formatPeriode(periode)}</option>`;
                });
                $('#periodeFilter').html(options);

                // Kalau belum ada periode dari server, pakai yang pertama
                if (!currentPeriode) {
                    currentPeriode = response.data[0];
                    $('#periodeFilter').val(currentPeriode);
                }

                updateLabel();
                initDatatable();
            } else {
                $('#periodeFilter').html('<option value="">Tidak ada data</option>');
            }
        },
        error: function () {
            $('#periodeFilter').html('<option value="">Error loading</option>');
        }
    });
}

function initDatatable() {
    table = $('#karyawanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("dashboard.karyawan-belum-input.data") }}',
            data: function (d) {
                d.periode = currentPeriode;
            },
        },
        columns: [
            { data: 'no',            className: 'text-center', orderable: false },
            { data: 'nik' },
            { data: 'nama_lengkap' },
            { data: 'email_pribadi' },
            { data: 'telp_pribadi' },
            { data: 'join_date' },
            { data: 'jenis_kelamin', className: 'text-center' },
        ],
        order: [[2, 'asc']], // default sort by nama
        pageLength: 25,
        dom: '<"row align-items-center mb-3"<"col-sm-6"l><"col-sm-6"f>>rt<"row align-items-center mt-3"<"col-sm-6"i><"col-sm-6"p>>',
        language: {
            processing:    '<span class="spinner-border spinner-border-sm me-2"></span> Memuat data...',
            search:        'Cari:',
            lengthMenu:    'Tampilkan _MENU_ data',
            info:          'Menampilkan _START_ - _END_ dari _TOTAL_ karyawan',
            infoEmpty:     'Tidak ada data',
            infoFiltered:  '(difilter dari _MAX_ total)',
            zeroRecords:   '<div class="text-center py-5 text-success"><i class="fas fa-check-circle fs-2x mb-3"></i><br>Semua karyawan sudah diinput payroll untuk periode ini 🎉</div>',
            paginate: {
                previous: 'Sebelumnya',
                next:     'Berikutnya',
            },
        },
        drawCallback: function (settings) {
            // Update total count dari info datatables
            let total = settings.json?.recordsFiltered ?? 0;
            $('#totalBelumInput').text(total);
        },
    });
}

function updateLabel() {
    $('#periodeLabel').text(formatPeriode(currentPeriode));
}

function formatPeriode(periode) {
    if (!periode) return '-';
    let [year, month] = periode.split('-');
    let monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${monthNames[parseInt(month) - 1]} ${year}`;
}
</script>
@endpush