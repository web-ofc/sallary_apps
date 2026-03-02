@extends('layouts.master')

@section('title', $title)

@section('content')
<div class="card p-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Karyawan Belum Punya Akun</h3>
            <span class="text-muted fs-7">
                Karyawan di apps absen yang belum memiliki akun user.
                @if($lastSync)
                    · Terakhir sync: <strong>{{ \Carbon\Carbon::parse($lastSync)->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</strong>
                @else
                    · Belum pernah disync
                @endif
            </span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-light-primary btn-sm" id="btnSync">
                <i class="fas fa-sync-alt me-1"></i> Sync Sekarang
            </button>
            <a href="{{ route('dashboard.admin') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- Sync result alert --}}
    <div id="syncAlert" class="d-none mb-4"></div>

    {{-- Table --}}
    <div class="table-responsive">
        <table id="sync-karyawan-table"
               class="table table-sm table-row-dashed table-row-gray-200 align-middle gs-1 gy-1">
            <thead>
                <tr class="fw-bold fs-9 text-uppercase text-gray-600 border-bottom border-gray-300">
                    <th class="ps-2 w-35px">No</th>
                    <th>Absen ID</th>
                    <th>NIK</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>Tgl Masuk</th>
                    <th>L/P</th>
                    <th>Status Nikah</th>
                    <th>Terakhir Sync</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-700 fs-8"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
let syncTable;

$(document).ready(function () {
    syncTable = $('#sync-karyawan-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("sync-karyawan-without-user.data") }}',
            type: 'GET',
        },
        columns: [
            { data: 'no',                orderable: false, searchable: false, className: 'ps-2' },
            { data: 'absen_karyawan_id', className: 'text-muted fs-8' },
            { data: 'nik' },
            { data: 'nama_lengkap' },
            { data: 'email_pribadi' },
            { data: 'telp_pribadi' },
            { data: 'join_date' },
            { data: 'jenis_kelamin' },
            { data: 'status_pernikahan' },
            { data: 'last_synced_at',    orderable: false },
        ],
        order: [[3, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    });

    // Tombol sync manual
    $('#btnSync').on('click', function () {
        doSync();
    });
});

function doSync() {
    const btn = $('#btnSync');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Syncing...');
    $('#syncAlert').addClass('d-none');

    $.ajax({
        url: '{{ route("sync-karyawan-without-user.sync") }}',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function (response) {
            const s = response.stats ?? {};
            const msg = response.message ?? 'Sync selesai';
            showAlert('success', `<i class="fas fa-check-circle me-2"></i>${msg}`);
            syncTable.ajax.reload();
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message ?? 'Sync gagal';
            showAlert('danger', `<i class="fas fa-exclamation-circle me-2"></i>${msg}`);
        },
        complete: function () {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Sync Sekarang');
        }
    });
}

function showAlert(type, html) {
    $('#syncAlert')
        .removeClass('d-none alert-success alert-danger')
        .addClass(`alert alert-${type}`)
        .html(html);
}
</script>
@endpush