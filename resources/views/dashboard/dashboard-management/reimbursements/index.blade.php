@extends('layouts.master')

@section('content')

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manage Reimbursements</h3>
        <button type="button" class="btn btn-primary btn-sm" onclick="showPreCreateModal()">
            <i class="fas fa-plus"></i> Tambah Reimbursement
        </button>
    </div>

    <!-- Filter Section - Compact Layout -->
    <div class="card-title">
        <div class="row g-2 mb-3">
            <div class="col-md-2">
                <label class="form-label fs-8 fw-semibold mb-1">Filter Status</label>
                <select class="form-select form-control form-control-solid form-select-sm" id="filter-status" data-control="select2" data-placeholder="Semua Status">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 fw-semibold mb-1">Filter Tahun</label>
                <select class="form-select form-control form-control-solid form-select-sm" id="filter-year" data-control="select2" data-placeholder="Semua Tahun">
                    <option value="">Semua Tahun</option>
                    @for($i = date('Y'); $i >= 2020; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-8 fw-semibold mb-1">Cari Data</label>
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-4 position-absolute ms-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-kt-reimbursement-table-filter="search" 
                           class="form-control form-control-sm form-control-solid ps-10" 
                           placeholder="Cari..." />
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Table with ALL Columns -->
    <div class="table-responsive">
        <table id="manage-reimbursements-table" class="table table-sm table-row-dashed table-row-gray-200 align-middle gs-1 gy-1">
            <thead>
                <tr class="fw-bold fs-9 text-uppercase text-gray-600 border-bottom border-gray-300">
                    <th class="ps-2 pe-1 min-w-25px">No</th>
                    <th class="px-1 min-w-90px">ID Recap</th>
                    <th class="px-1 min-w-120px">Karyawan</th>
                    <th class="px-1 min-w-100px">Company</th>
                    <th class="px-1 min-w-65px">Periode</th>
                    <th class="px-1 min-w-60px">Tahun</th>
                    <th class="px-1 min-w-80px">Total</th>
                    <th class="px-1 min-w-90px">Approver</th>
                    <th class="px-1 min-w-80px">Dibuat Oleh</th>
                    <th class="px-1 min-w-80px">Tgl Approve</th>
                    <th class="px-1 min-w-90px">Dibuat</th>
                    <th class="px-1 min-w-70px">Status</th>
                    <th class="px-1 pe-2 text-end min-w-80px">Aksi</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-700 fs-8">
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL STEP 1: Pre-Create (Pilih Karyawan + Periode) -->
<div class="modal fade" id="preCreateModal" tabindex="-1" aria-labelledby="preCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="preCreateModalLabel">Buat Reimbursement Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="preCreateForm">
                @csrf
                
                <div class="modal-body">
                    <!-- Karyawan Select -->
                    <div class="mb-4">
                        <label for="pre_karyawan_id" class="form-label required fs-7">Karyawan</label>
                        <select class="form-select form-select-sm" id="pre_karyawan_id" name="karyawan_id" 
                                data-control="select2" 
                                data-dropdown-parent="#preCreateModal"
                                data-placeholder="Pilih karyawan..."
                                data-allow-clear="true" required>
                            <option></option>
                        </select>
                        <div class="invalid-feedback" id="pre_karyawan_id-error"></div>
                        <div class="form-text fs-8">Pilih karyawan untuk reimbursement</div>
                    </div>

                    <div class="mb-4">
                        <label for="pre_company_id" class="form-label required fs-7">Company</label>
                        <select class="form-select form-select-sm" id="pre_company_id" name="company_id" 
                                data-control="select2" 
                                data-dropdown-parent="#preCreateModal"
                                data-placeholder="Pilih company..."
                                data-allow-clear="true" required>
                            <option></option>
                        </select>
                        <div class="invalid-feedback" id="pre_company_id-error"></div>
                        <div class="form-text fs-8">Pilih company karyawan</div>
                    </div>

                    <!-- Periode Slip -->
                    <div class="mb-4">
                        <label for="pre_periode_slip" class="form-label required fs-7">Periode Slip</label>
                        <input type="month" class="form-control form-control-sm" id="pre_periode_slip" name="periode_slip" 
                               value="{{ date('Y-m') }}" required>
                        <div class="invalid-feedback" id="pre_periode_slip-error"></div>
                        <div class="form-text fs-8">Format: YYYY-MM (contoh: 2025-01)</div>
                    </div>

                    <!-- Info Alert -->
                    <div class="alert alert-info d-flex align-items-center p-4 mb-0">
                        <i class="ki-duotone ki-information-5 fs-2x text-info me-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h5 class="mb-1 text-dark fs-7">Informasi</h5>
                            <span class="fs-8">Setelah klik Lanjut, Anda akan diarahkan ke halaman form reimbursement.</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="preCreateSubmitBtn">
                        <span class="indicator-label">Lanjut</span>
                        <span class="indicator-progress" style="display: none;">
                            Mohon tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: View Detail -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Detail Reimbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be loaded here -->
                <div class="text-center py-5">
                    <span class="spinner-border spinner-border-lg"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let table;
let preCreateModal;
let viewModal;
let preKaryawanSelect2;
let preCompanySelect2;

$(document).ready(function() {
    // Initialize modals
    preCreateModal = new bootstrap.Modal(document.getElementById('preCreateModal'));
    viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

    // Initialize Select2 for filters
    $('#filter-status, #filter-year').select2({
        placeholder: function() {
            return $(this).data('placeholder');
        },
        allowClear: true
    });

    // Initialize DataTables with ALL COLUMNS
    table = $('#manage-reimbursements-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('reimbursements.data') }}",
            data: function(d) {
                d.status = $('#filter-status').val();
                d.year = $('#filter-year').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'ps-2 pe-1' },
            { data: 'id_recapan', name: 'id_recapan', className: 'px-1' },
            { data: 'karyawan_info', name: 'karyawan.nama_lengkap', className: 'px-1' },
            { data: 'company_info', name: 'company.company_name', className: 'px-1' },
            { data: 'periode_slip', name: 'periode_slip', className: 'px-1' },
            { data: 'year_budget', name: 'year_budget', className: 'px-1' },
            { data: 'total_amount', name: 'total_amount', orderable: false, searchable: false, className: 'px-1' },
            { data: 'approver_info', name: 'approver.nama_lengkap', className: 'px-1' },
            { data: 'created_by', name: 'userBy.name', className: 'px-1' },
            { data: 'approved_at', name: 'approved_at', className: 'px-1' },
            { data: 'created_at', name: 'created_at', className: 'px-1' },
            { data: 'status', name: 'status', className: 'px-1' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'px-1 pe-2 text-end' },
        ],
        order: [[10, 'desc']], // Order by created_at descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'all']],
        scrollX: true, // Enable horizontal scroll for many columns
        autoWidth: false
    });

    // Filter functionality
    $('#filter-status, #filter-year').on('change', function() {
        table.ajax.reload();
    });

    // Search functionality
    $('[data-kt-reimbursement-table-filter="search"]').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Pre-Create Form submit handler
    $('#preCreateForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#preCreateSubmitBtn');
        const indicatorLabel = submitBtn.find('.indicator-label');
        const indicatorProgress = submitBtn.find('.indicator-progress');
        
        // Show loading
        submitBtn.prop('disabled', true);
        indicatorLabel.hide();
        indicatorProgress.show();
        
        // Clear previous errors
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        $.ajax({
            url: "{{ route('manage-reimbursements.validate-pre-create') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success && response.redirect_url) {
                    // Redirect to create page
                    window.location.href = response.redirect_url;
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON;
                    if (errors.message) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: errors.message
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan.'
                    });
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                indicatorLabel.show();
                indicatorProgress.hide();
            }
        });
    });
});

function showPreCreateModal() {
    // Reset form
    $('#preCreateForm')[0].reset();
    $('.form-control, .form-select').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    // Initialize Select2 for karyawan
    if (preKaryawanSelect2) {
        $('#pre_karyawan_id').select2('destroy');
    }
    
    preKaryawanSelect2 = $('#pre_karyawan_id').select2({
        ajax: {
            url: "{{ route('manage-reimbursements.karyawan.list') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data) {
                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        },
        dropdownParent: $('#preCreateModal'),
        placeholder: 'Pilih karyawan...',
        allowClear: true,
        minimumInputLength: 0
    });
    
    // Initialize Select2 for company
    if (preCompanySelect2) {
        $('#pre_company_id').select2('destroy');
    }
    
    preCompanySelect2 = $('#pre_company_id').select2({
        ajax: {
            url: "{{ route('manage-reimbursements.company.list') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data) {
                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        },
        dropdownParent: $('#preCreateModal'),
        placeholder: 'Pilih company...',
        allowClear: true,
        minimumInputLength: 0
    });
    
    preCreateModal.show();
}

function viewReimbursement(id) {
    $('#viewModalBody').html('<div class="text-center py-5"><span class="spinner-border spinner-border-lg"></span></div>');
    viewModal.show();
    
    $.ajax({
        url: "{{ url('manage-reimbursements') }}/" + id,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                let html = '<div class="row">';
                
                // Header Info
                html += '<div class="col-md-6 mb-4">';
                html += '<h5 class="fs-6">Informasi Reimbursement</h5>';
                html += '<table class="table table-sm table-row-bordered fs-7">';
                html += '<tr><td class="fw-bold">ID Recap</td><td>' + data.id_recapan + '</td></tr>';
                html += '<tr><td class="fw-bold">Karyawan</td><td>' + (data.karyawan?.nama_lengkap || '-') + '</td></tr>';
                html += '<tr><td class="fw-bold">NIK</td><td>' + (data.karyawan?.nik || '-') + '</td></tr>';
                html += '<tr><td class="fw-bold">Periode</td><td>' + data.periode_slip + '</td></tr>';
                html += '<tr><td class="fw-bold">Tahun Budget</td><td>' + data.year_budget + '</td></tr>';
                html += '<tr><td class="fw-bold">Approver</td><td>' + (data.approver?.nama_lengkap || '-') + '</td></tr>';
                html += '<tr><td class="fw-bold">Status</td><td>';
                if (data.status) {
                    html += '<span class="badge badge-success fs-8">Approved</span>';
                    if (data.approved_at) {
                        html += '<br><span class="text-muted fs-9">' + formatDate(data.approved_at) + '</span>';
                    }
                } else {
                    html += '<span class="badge badge-warning fs-8">Pending</span>';
                }
                html += '</td></tr>';
                html += '</table>';
                html += '</div>';
                
                // Items
                html += '<div class="col-md-12">';
                html += '<h5 class="fs-6">Detail Items</h5>';
                html += '<table class="table table-sm table-striped table-row-bordered fs-7">';
                html += '<thead><tr class="fw-bold fs-8 text-uppercase text-gray-600"><th>No</th><th>Tipe</th><th>Group</th><th>Harga</th><th>Penyakit</th><th>Status Keluarga</th><th>Note</th></tr></thead>';
                html += '<tbody>';
                
                let total = 0;
                if (data.childs && data.childs.length > 0) {
                    data.childs.forEach((child, index) => {
                        total += child.harga;
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td>' + (child.reimbursement_type?.medical_type || '-') + '</td>';
                        html += '<td><span class="badge badge-light-info fs-8">' + (child.reimbursement_type?.group_medical || '-') + '</span></td>';
                        html += '<td class="fw-bold text-success">Rp ' + formatRupiah(child.harga) + '</td>';
                        html += '<td>' + (child.jenis_penyakit || '-') + '</td>';
                        html += '<td>' + (child.status_keluarga || '-') + '</td>';
                        html += '<td>' + (child.note || '-') + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
                }
                
                html += '</tbody>';
                html += '<tfoot><tr><th colspan="3" class="text-end">TOTAL:</th><th class="fw-bold text-success">Rp ' + formatRupiah(total) + '</th><th colspan="3"></th></tr></tfoot>';
                html += '</table>';
                html += '</div>';
                
                html += '</div>';
                
                $('#viewModalBody').html(html);
            }
        },
        error: function() {
            $('#viewModalBody').html('<div class="alert alert-danger">Gagal memuat data</div>');
        }
    });
}

function deleteReimbursement(id, nama) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Anda akan menghapus reimbursement untuk " + nama + "!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('manage-reimbursements') }}/" + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Dihapus!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus data.'
                    });
                }
            });
        }
    });
}

function editReimbursement(id) {
    // Redirect to edit page
    window.location.href = "{{ route('manage-reimbursements.index') }}/" + id + "/edit";
}

function formatRupiah(angka) {
    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Reset form when modal is hidden
$('#preCreateModal').on('hidden.bs.modal', function() {
    $('#preCreateForm')[0].reset();
    if (preKaryawanSelect2) {
        $('#pre_karyawan_id').val(null).trigger('change');
    }
    if (preCompanySelect2) {
        $('#pre_company_id').val(null).trigger('change');
    }
});
</script>
@endpush