@extends('layouts.master')

@section('content')

<div class="card p-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Master Jenis Reimbursement</h2>
        <button type="button" class="btn btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus"></i> Tambah Jenis Reimbursement
        </button>
    </div>

    <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <input type="text" data-kt-reimbursement-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari jenis reimbursement..." />
        </div>
    </div>

    <div class="table-responsive">
        <table id="reimbursement-types-table" class="table table-sm table-row-dashed table-row-gray-200 align-middle gs-1 gy-1">
            <thead>
                <tr class="fw-bold fs-9 text-uppercase text-gray-600 border-bottom border-gray-300">
                    <th>No</th>
                    <th>Kode</th>
                    <th>Jenis Medical</th>
                    <th>Group Medical</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-700 fs-8">
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="reimbursementTypeModal" tabindex="-1" aria-labelledby="reimbursementTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reimbursementTypeModalLabel">Tambah Jenis Reimbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reimbursementTypeForm">
                @csrf
                <input type="hidden" id="reimbursement_id" name="id">
                <input type="hidden" id="form_method" name="_method" value="POST">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="code" class="form-label required">Kode</label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="Contoh: KACAMATA" required>
                        <div class="invalid-feedback" id="code-error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="medical_type" class="form-label required">Jenis Medical</label>
                        <input type="text" class="form-control" id="medical_type" name="medical_type" placeholder="Contoh: Kacamata" required>
                        <div class="invalid-feedback" id="medical_type-error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="group_medical" class="form-label required">Group Medical</label>
                        <select class="form-select" id="group_medical" name="group_medical" required>
                            <option value="">Pilih Group Medical</option>
                            <option value="general">General</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="invalid-feedback" id="group_medical-error"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress" style="display: none;">
                            Mohon tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let table;
let modal;

$(document).ready(function() {
    // Initialize modal
    modal = new bootstrap.Modal(document.getElementById('reimbursementTypeModal'));

    // Initialize DataTables
    table = $('#reimbursement-types-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('reimbursementtypes.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'code', name: 'code' },
            { data: 'medical_type', name: 'medical_type' },
            { data: 'group_medical', name: 'group_medical' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[1, 'asc']]
    });

    // Search functionality
    $('[data-kt-reimbursement-table-filter="search"]').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Form submit handler
    $('#reimbursementTypeForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submitBtn');
        const indicatorLabel = submitBtn.find('.indicator-label');
        const indicatorProgress = submitBtn.find('.indicator-progress');
        
        // Show loading
        submitBtn.prop('disabled', true);
        indicatorLabel.hide();
        indicatorProgress.show();
        
        // Clear previous errors
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        const formData = $(this).serialize();
        const id = $('#reimbursement_id').val();
        const method = $('#form_method').val();
        const url = id ? "{{ url('master-reimbursementtypes') }}/" + id : "{{ route('master-reimbursementtypes.store') }}";
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    modal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    table.ajax.reload();
                    resetForm();
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
                        text: 'Terjadi kesalahan saat menyimpan data.'
                    });
                }
            },
            complete: function() {
                // Hide loading
                submitBtn.prop('disabled', false);
                indicatorLabel.show();
                indicatorProgress.hide();
            }
        });
    });
});

function showCreateModal() {
    resetForm();
    $('#reimbursementTypeModalLabel').text('Tambah Jenis Reimbursement');
    $('#form_method').val('POST');
    modal.show();
}

function editReimbursementType(id) {
    resetForm();
    
    $.ajax({
        url: "{{ url('master-reimbursementtypes') }}/" + id + "/edit",
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                $('#reimbursementTypeModalLabel').text('Edit Jenis Reimbursement');
                $('#reimbursement_id').val(data.id);
                $('#form_method').val('PUT');
                $('#code').val(data.code);
                $('#medical_type').val(data.medical_type);
                $('#group_medical').val(data.group_medical);
                
                modal.show();
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat mengambil data.'
            });
        }
    });
}

function deleteReimbursementType(id, name) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Anda tidak akan bisa mengembalikan data " + name + " ini!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('master-reimbursementtypes') }}/" + id,
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
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menghapus data.'
                    });
                }
            });
        }
    });
}

function resetForm() {
    $('#reimbursementTypeForm')[0].reset();
    $('#reimbursement_id').val('');
    $('#form_method').val('POST');
    $('.form-control, .form-select').removeClass('is-invalid');
    $('.invalid-feedback').text('');
}

// Reset form when modal is hidden
$('#reimbursementTypeModal').on('hidden.bs.modal', function() {
    resetForm();
});
</script>
@endpush