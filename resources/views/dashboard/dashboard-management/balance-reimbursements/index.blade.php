@extends('layouts.master')

@section('content')

<div class="card p-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Saldo Reimbursement Karyawan</h2>
            <p class="text-muted mb-0">Data saldo reimbursement berdasarkan periode aktif</p>
        </div>
        <div>
            <button type="button" class="btn btn-light-primary" onclick="table.ajax.reload()">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
    </div>

    <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <input type="text" data-kt-balance-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari karyawan atau tahun..." />
        </div>
    </div>

    <div class="table-responsive">
        <table id="balance-reimbursements-table" class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Karyawan</th>
                    <th>Tahun Budget</th>
                    <th>Budget Claim</th>
                    <th>Total Terpakai</th>
                    <th>Sisa Budget</th>
                    <th>Persentase</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
let table;

$(document).ready(function() {
    // Initialize DataTables
    table = $('#balance-reimbursements-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('balancereimbursements.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nama_karyawan', name: 'karyawan.nama_lengkap' },
            { data: 'year', name: 'year' },
            { data: 'budget_claim', name: 'budget_claim' },
            { data: 'total_used', name: 'total_used' },
            { data: 'sisa_budget', name: 'sisa_budget' },
            { data: 'persentase', name: 'persentase', orderable: false, searchable: false },
        ],
        order: [[2, 'desc'], [1, 'asc']], // Order by year desc, then name asc
        pageLength: 25
    });

    // Search functionality
    $('[data-kt-balance-table-filter="search"]').on('keyup', function() {
        table.search(this.value).draw();
    });
});
</script>
@endpush