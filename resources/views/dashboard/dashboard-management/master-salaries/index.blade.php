@extends('layouts.master')

@section('content')
<div class="card p-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Master Salaries</h2>
        <button type="button" class="btn btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus"></i> Tambah Salary
        </button>
    </div>

    <!-- Filter Section -->
    <div class="card-title">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Filter Tahun</label>
                <select class="form-select" id="filter-year" data-control="select2" data-placeholder="Semua Tahun">
                    <option value="">Semua Tahun</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter Karyawan</label>
                <select class="form-select" id="filter-karyawan" data-control="select2" data-placeholder="Semua Karyawan">
                    <option value="">Semua Karyawan</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Cari</label>
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" data-kt-salary-table-filter="search" 
                           class="form-control form-control-solid ps-13" 
                           placeholder="Cari data..." />
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="master-salaries-table" class="table table-sm table-row-dashed table-row-gray-200 align-middle gs-1 gy-1">
            <thead>
                <tr class="fw-bold fs-9 text-uppercase text-gray-600 border-bottom border-gray-300">
                    <th>No</th>
                    <th>Karyawan</th>
                    <th>Gaji</th>
                    <th>Tanggal Update</th>
                    <th>Tahun</th>
                    <th>Medical</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-700 fs-8">
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="salaryModal" tabindex="-1" aria-labelledby="salaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salaryModalLabel">Tambah Master Salary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="salaryForm">
                @csrf
                <input type="hidden" id="salary_id" name="id">
                <input type="hidden" id="form_method" name="_method" value="POST">
                
                <div class="modal-body">
                    <!-- Karyawan Select -->
                    <div class="mb-5">
                        <label for="karyawan_id" class="form-label required">Karyawan</label>
                        <select class="form-select" id="karyawan_id" name="karyawan_id" 
                                data-control="select2" 
                                data-dropdown-parent="#salaryModal"
                                data-placeholder="Pilih karyawan..."
                                data-allow-clear="true" required>
                            <option></option>
                        </select>
                        <div class="invalid-feedback" id="karyawan_id-error"></div>
                        <div class="form-text">Pilih karyawan berdasarkan nama, NIK, atau ID Absen</div>
                    </div>

                    <!-- Karyawan Info Display -->
                    <div id="karyawan-info" class="alert alert-secondary d-none mb-5">
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-information-5 fs-2hx text-primary me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1">Informasi Karyawan</h5>
                                <span class="text-primary"><strong>NIK:</strong> <span id="info-nik">-</span></span>
                                <span class="text-primary"><strong>ID Absen:</strong> <span id="info-absen-id">-</span></span>
                                <span class="mt-2" id="latest-salary-info"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Salary -->
                        <div class="col-md-6 mb-5">
                            <label for="salary" class="form-label required">Gaji (Rp)</label>
                            <input type="text" class="form-control" id="salary" name="salary" 
                                   placeholder="0" required>
                            <div class="invalid-feedback" id="salary-error"></div>
                        </div>

                        <!-- Update Date -->
                        <div class="col-md-6 mb-5">
                            <label for="update_date" class="form-label required">Tanggal Update</label>
                            <input type="date" class="form-control" id="update_date" name="update_date" 
                                   value="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback" id="update_date-error"></div>
                            <div class="form-text">Tahun akan diambil dari tanggal ini</div>
                        </div>
                    </div>

                    <!-- Status Medical -->
                    <div class="mb-5">
                        <label for="status_medical" class="form-label">Status Medical</label>
                        <select class="form-select" id="status_medical" name="status_medical" data-control="select2" data-dropdown-parent="#salaryModal" data-placeholder="Pilih status medical">
                            <option value="">-- Pilih --</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                        <div class="invalid-feedback" id="status_medical-error"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
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
let karyawanSelect2;
let filterKaryawanSelect2;

$(document).ready(function() {
    // Initialize modal
    modal = new bootstrap.Modal(document.getElementById('salaryModal'));

    // Initialize Select2 for Filter Karyawan
    filterKaryawanSelect2 = $('#filter-karyawan').select2({
        ajax: {
            url: "{{ route('master-salaries.karyawan.list') }}",
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
        placeholder: 'Semua Karyawan',
        allowClear: true,
        minimumInputLength: 0
    });

    // Initialize Select2 for Filter Year
    $('#filter-year').select2({
        placeholder: 'Semua Tahun',
        allowClear: true
    });

    // Initialize DataTables
    table = $('#master-salaries-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('salaries.data') }}",
            data: function(d) {
                d.year = $('#filter-year').val();
                d.karyawan_id = $('#filter-karyawan').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'karyawan_info', name: 'karyawan.nama_lengkap' },
            { data: 'salary', name: 'salary' },
            { data: 'update_date', name: 'update_date' },
            { data: 'year', name: 'year' },
            { data: 'status_medical', name: 'status_medical', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
        ],
        order: [[3, 'desc']]
    });

    // Filter functionality
    $('#filter-year, #filter-karyawan').on('change', function() {
        table.ajax.reload();
    });

    // Search functionality
    $('[data-kt-salary-table-filter="search"]').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Format number input for salary
    $('#salary').on('keyup', function() {
        let value = $(this).val().replace(/[^\d]/g, '');
        $(this).val(formatRupiah(value));
    });

    // Form submit handler
    $('#salaryForm').on('submit', function(e) {
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
        
        const id = $('#salary_id').val();
        const method = $('#form_method').val();
        let url = "{{ route('master-salaries.store') }}";
        
        if (id && method === 'PUT') {
            url = "{{ url('master-salaries') }}/" + id;
        }
        
        // Prepare form data
        let formData = new FormData(this);
        // Clean salary value (remove formatting)
        let salaryValue = $('#salary').val().replace(/[^\d]/g, '');
        formData.set('salary', salaryValue);
        
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
                submitBtn.prop('disabled', false);
                indicatorLabel.show();
                indicatorProgress.hide();
            }
        });
    });

    // Karyawan selection handler
    $('#karyawan_id').on('change', function() {
        const karyawanId = $(this).val();
        
        if (karyawanId) {
            // Get karyawan detail
            $.ajax({
                url: "{{ url('master-salaries/karyawan') }}/" + karyawanId,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Show karyawan info
                        $('#info-nik').text(data.nik || '-');
                        $('#info-absen-id').text(data.absen_karyawan_id);
                        
                        // Show latest salary if exists
                        if (data.latest_salary) {
                            $('#latest-salary-info').html(
                                '<div class="alert alert-warning mt-2 mb-0 p-3">' +
                                '<strong>Salary Terakhir:</strong> Rp ' + formatRupiah(data.latest_salary.salary) +
                                ' (Tahun ' + data.latest_salary.year + ')' +
                                '</div>'
                            );
                        } else {
                            $('#latest-salary-info').html(
                                '<div class="alert alert-info mt-2 mb-0 p-3">' +
                                'Belum ada data salary sebelumnya' +
                                '</div>'
                            );
                        }
                        
                        $('#karyawan-info').removeClass('d-none');
                    }
                }
            });
        } else {
            $('#karyawan-info').addClass('d-none');
        }
    });
});

function showCreateModal() {
    resetForm();
    $('#salaryModalLabel').text('Tambah Master Salary');
    $('#form_method').val('POST');
    $('#salary_id').val('');
    
    // Initialize Select2 for karyawan in modal
    initKaryawanSelect2();
    
    // Initialize Select2 for status medical
    $('#status_medical').select2({
        dropdownParent: $('#salaryModal'),
        placeholder: 'Pilih status medical'
    });
    
    modal.show();
}

function initKaryawanSelect2() {
    // Destroy existing Select2 if any
    if (karyawanSelect2) {
        $('#karyawan_id').select2('destroy');
    }
    
    // Initialize new Select2
    karyawanSelect2 = $('#karyawan_id').select2({
        ajax: {
            url: "{{ route('master-salaries.karyawan.list') }}",
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
        dropdownParent: $('#salaryModal'),
        placeholder: 'Pilih karyawan...',
        allowClear: true,
        minimumInputLength: 0
    });
}

function editSalary(id) {
    resetForm();
    
    Swal.fire({
        title: 'Loading...',
        text: 'Mengambil data salary',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: "{{ url('master-salaries') }}/" + id + "/edit",
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success && response.data) {
                const data = response.data;
                
                $('#salaryModalLabel').text('Edit Master Salary');
                $('#salary_id').val(data.id);
                $('#form_method').val('PUT');
                
                // Initialize Select2 first
                initKaryawanSelect2();
                
                // Set karyawan with pre-loaded option
                const option = new Option(data.karyawan.text, data.karyawan.id, true, true);
                $('#karyawan_id').append(option).trigger('change');
                
                // Set other fields
                $('#salary').val(formatRupiah(data.salary));
                $('#update_date').val(data.update_date);
                
                // Initialize and set status medical
                $('#status_medical').select2({
                    dropdownParent: $('#salaryModal'),
                    placeholder: 'Pilih status medical'
                });
                $('#status_medical').val(data.status_medical || '').trigger('change');
                
                // Show karyawan info
                $('#info-nik').text(data.karyawan.nik || '-');
                $('#info-absen-id').text(data.karyawan.absen_karyawan_id);
                $('#karyawan-info').removeClass('d-none');
                
                modal.show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Data tidak ditemukan'
                });
            }
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: xhr.responseJSON?.message || 'Terjadi kesalahan saat mengambil data.'
            });
        }
    });
}

function deleteSalary(id, nama) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Anda akan menghapus data salary untuk " + nama + "!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('master-salaries') }}/" + id,
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

function resetForm() {
    $('#salaryForm')[0].reset();
    $('#salary_id').val('');
    $('#form_method').val('POST');
    $('.form-control, .form-select').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    $('#karyawan-info').addClass('d-none');
    
    // Reset Select2
    if (karyawanSelect2) {
        $('#karyawan_id').val(null).trigger('change');
    }
    
    // Reset status medical
    $('#status_medical').val('').trigger('change');
}

function formatRupiah(angka) {
    let number_string = angka.toString().replace(/[^,\d]/g, ''),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    return rupiah;
}

// Reset form when modal is hidden
$('#salaryModal').on('hidden.bs.modal', function() {
    resetForm();
});
</script>
@endpush