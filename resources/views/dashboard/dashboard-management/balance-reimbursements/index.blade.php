@extends('layouts.master')

@section('content')

<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fs-5 fw-bold">Saldo Reimbursement Karyawan</h4>
            <p class="text-muted mb-0 fs-8">Data saldo reimbursement berdasarkan periode aktif</p>
        </div>
        <button type="button" class="btn btn-light-primary btn-sm" onclick="table.ajax.reload()">
            <i class="fas fa-sync fs-8"></i> Refresh
        </button>
    </div>

    {{-- Filter + Search --}}
    <div class="card-title mb-2">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-5 position-absolute ms-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-kt-balance-table-filter="search"
                           class="form-control form-control-sm form-control-solid w-200px ps-10"
                           placeholder="Cari karyawan atau tahun..." />
                </div>
            </div>
            <div class="col-auto">
                <select id="filter-status" class="form-select form-select-sm form-control-solid w-150px"
                        data-control="select2" data-placeholder="Semua Status">
                    <option value="">Semua Status</option>
                    <option value="aman">🟢 Aman</option>
                    <option value="normal">🔵 Normal</option>
                    <option value="menipis">🟡 Menipis</option>
                    <option value="hampir_habis">🔴 Hampir Habis</option>
                    <option value="habis">⛔ Habis</option>
                </select>
            </div>
            <div class="col-auto">
                <select id="filter-year" class="form-select form-select-sm form-control-solid w-100px"
                        data-control="select2" data-placeholder="Semua Tahun">
                    <option value="">Semua Tahun</option>
                    @for($i = date('Y'); $i >= 2020; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="balance-reimbursements-table" class="table table-sm table-row-dashed table-row-gray-200 align-middle gs-1 gy-1">
            <thead>
                <tr class="fw-bold fs-9 text-uppercase text-gray-600 border-bottom border-gray-300">
                    <th class="ps-2 pe-1 min-w-25px">No</th>
                    <th class="px-1 min-w-150px">Nama Karyawan</th>
                    <th class="px-1 min-w-60px">Tahun</th>
                    <th class="px-1 min-w-100px">Budget Claim</th>
                    <th class="px-1 min-w-100px">Total Terpakai</th>
                    <th class="px-1 min-w-100px">Sisa Budget</th>
                    <th class="px-1 min-w-150px">Persentase</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-700 fs-8">
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
let table;

$(document).ready(function() {

    $('#filter-status, #filter-year').select2({
        placeholder: function() { return $(this).data('placeholder'); },
        allowClear: true
    });

    table = $('#balance-reimbursements-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('balancereimbursements.data') }}",
            data: function(d) {
                d.status = $('#filter-status').val();
                d.year   = $('#filter-year').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex',   name: 'DT_RowIndex',          orderable: false, searchable: false, className: 'ps-2 pe-1' },
            { data: 'nama_karyawan', name: 'karyawan.nama_lengkap',                                     className: 'px-1' },
            { data: 'year',          name: 'year',                                                       className: 'px-1' },
            { data: 'budget_claim',  name: 'budget_claim',                                               className: 'px-1' },
            { data: 'total_used',    name: 'total_used',                                                 className: 'px-1' },
            { data: 'sisa_budget',   name: 'sisa_budget',                                                className: 'px-1' },
            { data: 'persentase',    name: 'persentase',           orderable: false, searchable: false,  className: 'px-1' },
        ],
        order: [[2, 'desc'], [1, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'all']],
        scrollX: true,
        autoWidth: false,
        language: {
            processing: '<span class="spinner-border spinner-border-sm text-primary"></span>',
        }
    });

    $('#filter-status, #filter-year').on('change', function() {
        table.ajax.reload();
    });

    $('[data-kt-balance-table-filter="search"]').on('keyup', function() {
        table.search(this.value).draw();
    });
});
</script>
@endpush