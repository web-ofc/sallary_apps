@extends('layouts.master')

@section('content')

<div class="card p-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Master Jenis Penyakit</h2>
        <button type="button" class="btn btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus"></i> Tambah Jenis Penyakit
        </button>
    </div>

    <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <input type="text" data-kt-jenispenyakit-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari jenis penyakit..." />
        </div>
    </div>

    <div class="table-responsive">
        <table id="jenis-penyakit-table" class="table table-sm table-row-dashed table-row-gray-200 align-middle gs-1 gy-1">
            <thead>
                <tr class="fw-bold fs-9 text-uppercase text-gray-600 border-bottom border-gray-300">
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Penyakit</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-700 fs-8">
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="jenisPenyakitModal" tabindex="-1" aria-labelledby="jenisPenyakitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jenisPenyakitModalLabel">Tambah Jenis Penyakit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="jenisPenyakitForm">
                @csrf
                <input type="hidden" id="jenis_penyakit_id" name="id">
                <input type="hidden" id="form_method" name="_method" value="POST">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="kode" class="form-label">Kode <span class="text-muted fs-8">(opsional)</span></label>
                        <input type="text" class="form-control" id="kode" name="kode" placeholder="Contoh: FLU, DBD, TBC">
                        <div class="invalid-feedback" id="kode-error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="nama_penyakit" class="form-label required">Nama Penyakit</label>
                        <input type="text" class="form-control" id="nama_penyakit" name="nama_penyakit" placeholder="Contoh: Influenza, Demam Berdarah" required>
                        <div class="invalid-feedback" id="nama_penyakit-error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Aktif</label>
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
    modal = new bootstrap.Modal(document.getElementById('jenisPenyakitModal'));

    table = $('#jenis-penyakit-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('jenispenyakits.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'kode', name: 'kode' },
            { data: 'nama_penyakit', name: 'nama_penyakit' },
            { data: 'is_active_badge', name: 'is_active', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[3, 'asc']]
    });

    $('[data-kt-jenispenyakit-table-filter="search"]').on('keyup', function() {
        table.search(this.value).draw();
    });

    $('#jenisPenyakitForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $('#submitBtn');
        const indicatorLabel = submitBtn.find('.indicator-label');
        const indicatorProgress = submitBtn.find('.indicator-progress');

        submitBtn.prop('disabled', true);
        indicatorLabel.hide();
        indicatorProgress.show();

        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        const id = $('#jenis_penyakit_id').val();
        const url = id
            ? "{{ url('jenis-penyakits') }}/" + id
            : "{{ route('jenis-penyakits.store') }}";

        // Build data manually to handle checkbox
        const formData = {
            _token: $('input[name="_token"]').val(),
            _method: $('#form_method').val(),
            kode: $('#kode').val(),
            nama_penyakit: $('#nama_penyakit').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0,
        };

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
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: xhr.responseJSON?.message || 'Data tidak valid.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat menyimpan data.'
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

function showCreateModal() {
    resetForm();
    $('#jenisPenyakitModalLabel').text('Tambah Jenis Penyakit');
    $('#form_method').val('POST');
    modal.show();
}

function editJenisPenyakit(id) {
    resetForm();

    $.ajax({
        url: "{{ url('jenis-penyakits') }}/" + id + "/edit",
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const data = response.data;

                $('#jenisPenyakitModalLabel').text('Edit Jenis Penyakit');
                $('#jenis_penyakit_id').val(data.id);
                $('#form_method').val('PUT');
                $('#kode').val(data.kode);
                $('#nama_penyakit').val(data.nama_penyakit);
                $('#is_active').prop('checked', data.is_active == 1);

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

function deleteJenisPenyakit(id, name) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: 'Data "' + name + '" akan dihapus dan tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('jenis-penyakits') }}/" + id,
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
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message });
                    }
                },
                error: function() {
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
    $('#jenisPenyakitForm')[0].reset();
    $('#jenis_penyakit_id').val('');
    $('#form_method').val('POST');
    $('#is_active').prop('checked', true);
    $('.form-control, .form-select').removeClass('is-invalid');
    $('.invalid-feedback').text('');
}

$('#jenisPenyakitModal').on('hidden.bs.modal', function() {
    resetForm();
});
</script>
@endpush