{{-- resources/views/dashboard/dashboard-admin/reimbursement-files/index.blade.php --}}

@extends('layouts.master')

@section('content')
<div class="container-xxl">
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold m-0">A1 Files</h3>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" onclick="showPreCreateModal()">
                    <i class="ki-duotone ki-plus fs-2"></i>
                    Upload Files
                </button>
            </div>
        </div>

        {{-- ✅ FILTER SECTION --}}
        <div class="card-body border-top pt-6">
            <div class="row g-3 mb-5">
                {{-- Filter Year --}}
                <div class="col-md-3">
                    <label class="form-label fw-bold fs-6">Filter Tahun</label>
                    <select class="form-select form-select-solid" id="filter_year" data-control="select2" data-placeholder="Semua Tahun" data-allow-clear="true">
                        <option></option>
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Search Karyawan --}}
                <div class="col-md-6">
                    <label class="form-label fw-bold fs-6">Search Karyawan</label>
                    <input type="text" class="form-control form-control-solid" id="search_karyawan" placeholder="Cari nama atau NIK karyawan...">
                </div>

                {{-- Reset Button --}}
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-light-primary w-100" id="btn_reset_filter">
                        <i class="ki-duotone ki-arrows-circle fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_files">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-50px">No</th>
                        <th class="min-w-200px">Karyawan</th>
                        <th class="min-w-100px">Tahun</th>
                        <th class="min-w-250px">File</th>
                        <th class="min-w-100px">Upload Date</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL: Pre-Create (Select Karyawan + Year) --}}
<div class="modal fade" id="modal_pre_create" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="modal_pre_create_header">
                <h2 class="fw-bold">Select Karyawan & Year</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <form id="form_pre_create" class="form">
                <div class="modal-body py-10 px-lg-17">
                    {{-- Select Karyawan --}}
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Karyawan (Max 20)</label>
                        <select class="form-select" 
                                id="karyawan_ids" 
                                name="karyawan_ids[]" 
                                data-control="select2" 
                                data-dropdown-parent="#modal_pre_create"
                                data-placeholder="Select Karyawan" 
                                data-allow-clear="true"
                                multiple>
                        </select>
                        <div class="form-text">Maksimal 20 karyawan per transaksi</div>
                    </div>

                    {{-- Select Year --}}
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Tahun</label>
                        <select class="form-select" id="year" name="year" data-control="select2" data-dropdown-parent="#modal_pre_create" data-placeholder="Pilih Tahun">
                            <option></option>
                            @for ($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn_pre_create_submit">
                        <span class="indicator-label">Continue</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
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
"use strict";

let table;
let modal_pre_create;
let form_pre_create;
let btn_pre_create_submit;

$(document).ready(function() {
    // Initialize modal
    modal_pre_create = new bootstrap.Modal(document.getElementById('modal_pre_create'));
    
    // ✅ Initialize DataTable with proper search config
    table = $('#kt_table_files').DataTable({
        processing: true,
        serverSide: true,
        searching: true, // ✅ Enable searching
        ajax: {
            url: "{{ route('reimbursement-files.get-data') }}",
            type: 'GET',
            data: function(d) {
                // ✅ Add filter parameters
                d.year = $('#filter_year').val();
                // ✅ Manual search from custom input
                d.karyawan_search = $('#search_karyawan').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'karyawan_info', name: 'karyawan.nama_lengkap', orderable: true, searchable: true }, // ✅ Enable search
            { data: 'year', name: 'year', orderable: true, searchable: false },
            { data: 'file_info', name: 'file', orderable: false, searchable: true }, // ✅ Enable search
            { data: 'created_at', name: 'created_at', orderable: true, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[4, 'desc']],
        dom: "<'row'<'col-sm-12'tr>>" + // ✅ Hide default search box
              "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            emptyTable: "No matching records found",
            zeroRecords: "No matching records found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // ✅ Filter Year - on change reload table
    $('#filter_year').on('change', function() {
        console.log('Filter year changed:', $(this).val());
        table.ajax.reload();
    });

    // ✅ Search Karyawan - debounce untuk performance
    let searchTimeout;
    $('#search_karyawan').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchValue = $(this).val();
        
        searchTimeout = setTimeout(function() {
            console.log('Searching karyawan:', searchValue);
            table.ajax.reload(); // ✅ Reload with new search param
        }, 500); // Wait 500ms after user stops typing
    });

    // ✅ Reset Filter
    $('#btn_reset_filter').on('click', function() {
        $('#filter_year').val(null).trigger('change');
        $('#search_karyawan').val('');
        table.ajax.reload();
    });

    // Initialize Select2 for Filter Year
    $('#filter_year').select2({
        placeholder: 'Semua Tahun',
        allowClear: true
    });

    // Initialize Select2 for Karyawan
    $('#karyawan_ids').select2({
        ajax: {
            url: "{{ route('reimbursement-files.get-karyawan-list') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        },
        placeholder: 'Select Karyawan',
        minimumInputLength: 0,
        maximumSelectionLength: 20,
        dropdownParent: $('#modal_pre_create')
    });

    // Form submission
    form_pre_create = document.getElementById('form_pre_create');
    btn_pre_create_submit = document.getElementById('btn_pre_create_submit');

    $(form_pre_create).on('submit', function(e) {
        e.preventDefault();

        // Disable button & show loading
        btn_pre_create_submit.setAttribute('data-kt-indicator', 'on');
        btn_pre_create_submit.disabled = true;

        const formData = new FormData(form_pre_create);
        const karyawanIds = $('#karyawan_ids').val();

        $.ajax({
            url: "{{ route('reimbursement-files.validate-pre-create') }}",
            type: 'POST',
            data: {
                karyawan_ids: karyawanIds,
                year: formData.get('year'),
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect_url;
                }
            },
            error: function(xhr) {
                btn_pre_create_submit.removeAttribute('data-kt-indicator');
                btn_pre_create_submit.disabled = false;

                const error = xhr.responseJSON?.message || 'Terjadi kesalahan';
                Swal.fire({
                    text: error,
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
            }
        });
    });
});

function showPreCreateModal() {
    form_pre_create.reset();
    $('#karyawan_ids').val(null).trigger('change');
    $('#year').val(null).trigger('change');
    modal_pre_create.show();
}

function deleteFile(id, filename) {
    Swal.fire({
        text: `Yakin ingin menghapus file "${filename}"?`,
        icon: "warning",
        showCancelButton: true,
        buttonsStyling: false,
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
        customClass: {
            confirmButton: "btn btn-danger",
            cancelButton: "btn btn-light"
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/reimbursement-files/${id}`,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            text: response.message,
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                        table.ajax.reload();
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    Swal.fire({
                        text: error,
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            });
        }
    });
}
</script>
@endpush