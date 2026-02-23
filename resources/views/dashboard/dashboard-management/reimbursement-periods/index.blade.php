@extends('layouts.master')

@section('content')

<div class="card p-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Periode Reimbursement</h2>
        <button type="button" class="btn btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus"></i> Tambah Periode
        </button>
    </div>

    <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <input type="text" data-kt-period-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari periode..." />
        </div>
    </div>

    <div class="table-responsive">
        <table id="manage-reimbursementperiods-table" class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Periode</th>
                    <th>Tanggal Mulai</th>
                    <th>Tanggal Akhir</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="periodModal" tabindex="-1" aria-labelledby="periodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="periodModalLabel">Tambah Periode Reimbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="periodForm">
                @csrf
                <input type="hidden" id="period_id" name="id">
                <input type="hidden" id="form_method" name="_method" value="POST">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="periode" class="form-label required">Periode</label>
                        <input type="text" class="form-control" id="periode" name="periode" placeholder="Contoh: 2025 - 2026" required>
                        <div class="invalid-feedback" id="periode-error"></div>
                        <div class="form-text">Format: YYYY - YYYY</div>
                    </div>

                    <div class="mb-3">
                        <label for="expired_reimburs_start" class="form-label required">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="expired_reimburs_start" name="expired_reimburs_start" required>
                        <div class="invalid-feedback" id="expired_reimburs_start-error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="end_reimburs_start" class="form-label required">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_reimburs_start" name="end_reimburs_start" required>
                        <div class="invalid-feedback" id="end_reimburs_start-error"></div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center p-5 mb-0">
                        <i class="ki-duotone ki-shield-tick fs-2hx text-info me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-dark">Informasi</h4>
                            <span>Tanggal akhir harus lebih besar dari tanggal mulai dan tidak boleh overlap dengan periode lain.</span>
                        </div>
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
    modal = new bootstrap.Modal(document.getElementById('periodModal'));

    // Initialize DataTables
    table = $('#manage-reimbursementperiods-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('reimbursementperiods.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'periode', name: 'periode' },
            { data: 'expired_reimburs_start', name: 'expired_reimburs_start' },
            { data: 'end_reimburs_start', name: 'end_reimburs_start' },
            { data: 'duration', name: 'duration', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']]
    });

    // Search functionality
    $('[data-kt-period-table-filter="search"]').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Auto generate periode from dates
    $('#expired_reimburs_start, #end_reimburs_start').on('change', function() {
        const startDate = $('#expired_reimburs_start').val();
        const endDate = $('#end_reimburs_start').val();
        
        if (startDate && endDate) {
            const startYear = new Date(startDate).getFullYear();
            const endYear = new Date(endDate).getFullYear();
            
            if (startYear && endYear) {
                $('#periode').val(startYear + ' - ' + endYear);
            }
        }
    });

    // Validate end date is after start date
    $('#end_reimburs_start').on('change', function() {
        const startDate = $('#expired_reimburs_start').val();
        const endDate = $(this).val();
        
        if (startDate && endDate && new Date(endDate) <= new Date(startDate)) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian!',
                text: 'Tanggal akhir harus setelah tanggal mulai!'
            });
            $(this).val('');
        }
    });

    // Form submit handler
    $('#periodForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submitBtn');
        const indicatorLabel = submitBtn.find('.indicator-label');
        const indicatorProgress = submitBtn.find('.indicator-progress');
        
        // Show loading
        submitBtn.prop('disabled', true);
        indicatorLabel.hide();
        indicatorProgress.show();
        
        // Clear previous errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        const id = $('#period_id').val();
        const method = $('#form_method').val();
        let url = "{{ route('manage-reimbursementperiods.store') }}";
        
        if (id && method === 'PUT') {
            url = "{{ url('manage-reimbursementperiods') }}/" + id;
        }
        
        const formData = new FormData(this);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data.'
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
    $('#periodModalLabel').text('Tambah Periode Reimbursement');
    $('#form_method').val('POST');
    $('#period_id').val('');
    modal.show();
}

function editPeriod(id) {
    resetForm();
    
    // Show loading indicator
    Swal.fire({
        title: 'Loading...',
        text: 'Mengambil data periode',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: "{{ url('manage-reimbursementperiods') }}/" + id + "/edit",
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success && response.data) {
                const data = response.data;
                
                console.log('Data received:', data); // Debug
                
                $('#periodModalLabel').text('Edit Periode Reimbursement');
                $('#period_id').val(data.id);
                $('#form_method').val('PUT');
                $('#periode').val(data.periode);
                $('#expired_reimburs_start').val(data.expired_reimburs_start);
                $('#end_reimburs_start').val(data.end_reimburs_start);
                
                modal.show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Data tidak ditemukan'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('Error:', xhr.responseText); // Debug
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: xhr.responseJSON?.message || 'Terjadi kesalahan saat mengambil data.'
            });
        }
    });
}

function deletePeriod(id, periode) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Anda tidak akan bisa mengembalikan data periode " + periode + " ini!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('manage-reimbursementperiods') }}/" + id,
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
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus data.'
                    });
                }
            });
        }
    });
}

function resetForm() {
    $('#periodForm')[0].reset();
    $('#period_id').val('');
    $('#form_method').val('POST');
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').text('');
}

// Reset form when modal is hidden
$('#periodModal').on('hidden.bs.modal', function() {
    resetForm();
});
</script>
@endpush