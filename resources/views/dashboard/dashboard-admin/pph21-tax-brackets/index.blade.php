@extends('layouts.master')

@section('title', 'Bracket Tarif PPh21')

@section('content')
<!--begin::Card-->
<div class="card">
    <!--begin::Card header-->
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h2>Bracket Tarif PPh21</h2>
        </div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" id="btn-add">
                <i class="ki-duotone ki-plus fs-2"></i>
                Tambah Data
            </button>
        </div>
    </div>
    <!--end::Card header-->
    
    <!--begin::Card body-->
    <div class="card-body pt-0">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="tax_bracket_table">
            <thead>
                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                    <th width="50">No</th>
                    <th>Urutan</th>
                    <th>Range PKP</th>
                    <th>Tarif</th>
                    <th>Periode Berlaku</th>
                    <th>Status</th>
                    <th>Deskripsi</th>
                    <th width="150" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
            </tbody>
        </table>
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->

<!--begin::Modal-->
<div class="modal fade" id="modal_form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="modal_title">Tambah Data</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            
            <form id="form_tax_bracket">
                @csrf
                <input type="hidden" id="id" name="id">
                <div class="modal-body py-10 px-lg-17">
                    <div class="scroll-y me-n7 pe-7">
                       <!--begin::Row-->
                        <div class="row mb-7">
                            <!--begin::Col-->
                            <div class="col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">PKP Minimal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control currency-input" placeholder="60.000.000" name="min_pkp_display" id="min_pkp_display" />
                                </div>
                                <input type="hidden" name="min_pkp" id="min_pkp" />
                                <div class="invalid-feedback" id="error-min_pkp"></div>
                                <div class="form-text">Dalam Rupiah (gunakan titik sebagai pemisah ribuan)</div>
                            </div>
                            <!--end::Col-->
                            
                            <!--begin::Col-->
                            <div class="col-md-6">
                                <label class="fs-6 fw-semibold mb-2">PKP Maksimal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control currency-input" placeholder="250.000.000" name="max_pkp_display" id="max_pkp_display" />
                                </div>
                                <input type="hidden" name="max_pkp" id="max_pkp" />
                                <div class="invalid-feedback" id="error-max_pkp"></div>
                                <div class="form-text">Kosongkan jika tidak terbatas</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                        
                        <!--begin::Row-->
                        <div class="row mb-7">
                            <!--begin::Col-->
                            <div class="col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">Tarif Pajak (%)</label>
                                <input type="number" class="form-control" placeholder="Contoh: 5" name="rate_percent" id="rate_percent" min="0" max="100" step="0.01" />
                                <div class="invalid-feedback" id="error-rate_percent"></div>
                                <div class="form-text">Dalam persen (0-100)</div>
                            </div>
                            <!--end::Col-->
                            
                            <!--begin::Col-->
                            <div class="col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">Urutan</label>
                                <input type="number" class="form-control" placeholder="Contoh: 1" name="order_index" id="order_index" min="1" />
                                <div class="invalid-feedback" id="error-order_index"></div>
                                <div class="form-text">Urutan bracket dari terkecil</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                        
                        <!--begin::Row-->
                        <div class="row mb-7">
                            <!--begin::Col-->
                            <div class="col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">Tanggal Mulai Berlaku</label>
                                <input type="date" class="form-control" name="effective_start_date" id="effective_start_date" />
                                <div class="invalid-feedback" id="error-effective_start_date"></div>
                            </div>
                            <!--end::Col-->
                            
                            <!--begin::Col-->
                            <div class="col-md-6">
                                <label class="fs-6 fw-semibold mb-2">Tanggal Akhir Berlaku</label>
                                <input type="date" class="form-control" name="effective_end_date" id="effective_end_date" />
                                <div class="invalid-feedback" id="error-effective_end_date"></div>
                                <div class="form-text">Kosongkan jika berlaku hingga sekarang</div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                        
                        <!--begin::Input group-->
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Deskripsi</label>
                            <textarea class="form-control" name="description" id="description" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                            <div class="invalid-feedback" id="error-description"></div>
                        </div>
                        <!--end::Input group-->
                        
                        <!--begin::Info Alert-->
                        <div class="alert alert-primary d-flex align-items-center p-5">
                            <i class="ki-duotone ki-information-5 fs-2hx text-primary me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h4 class="mb-1 text-dark">Informasi</h4>
                                <span>PKP (Penghasilan Kena Pajak) adalah penghasilan bruto dikurangi PTKP. Bracket ini digunakan untuk menghitung PPh21 secara progresif.</span>
                            </div>
                        </div>
                        <!--end::Info Alert-->
                    </div>
                </div>
                
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <span class="indicator-label">Simpan</span>
                        <span class="indicator-progress" style="display: none;">
                            Mohon tunggu...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal-->
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Setup CSRF Token untuk semua AJAX request
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        let table;
        let isEdit = false;
        let editId = null;
        
        // ========== CURRENCY FORMATTING FUNCTIONS ==========
        
        // Format number to Rupiah (with dots)
        function formatRupiah(number) {
            if (!number) return '';
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Remove formatting (dots) and convert to number
        function unformatRupiah(formatted) {
            if (!formatted) return '';
            return formatted.replace(/\./g, '');
        }
        
        // Auto format input as user types
        function initCurrencyInput() {
            $('.currency-input').on('input', function() {
                let cursorPosition = this.selectionStart;
                let value = $(this).val();
                let unformatted = unformatRupiah(value);
                
                // Only allow numbers
                unformatted = unformatted.replace(/\D/g, '');
                
                // Format with dots
                let formatted = formatRupiah(unformatted);
                $(this).val(formatted);
                
                // Update hidden input with raw number
                let fieldName = $(this).attr('id').replace('_display', '');
                $('#' + fieldName).val(unformatted);
                
                // Restore cursor position (adjust for added dots)
                let dotsBeforeCursor = (value.substring(0, cursorPosition).match(/\./g) || []).length;
                let dotsAfterFormat = (formatted.substring(0, cursorPosition).match(/\./g) || []).length;
                let newPosition = cursorPosition + (dotsAfterFormat - dotsBeforeCursor);
                this.setSelectionRange(newPosition, newPosition);
            });
            
            // Prevent non-numeric input
            $('.currency-input').on('keypress', function(e) {
                let charCode = e.which ? e.which : e.keyCode;
                if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
                    return false;
                }
                return true;
            });
        }
        
        // Initialize currency inputs
        initCurrencyInput();
        
        // ========== DATATABLE ==========
        
        // Initialize DataTable
        table = $('#tax_bracket_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('pph21taxbrackets.data') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'order_index', name: 'order_index'},
                {data: 'pkp_range', name: 'pkp_range', orderable: false},
                {data: 'rate_display', name: 'rate_percent'},
                {data: 'effective_period', name: 'effective_period', orderable: false},
                {data: 'status', name: 'status', orderable: false, searchable: false},
                {data: 'description', name: 'description'},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
            ],
            order: [[1, 'asc']], // Sort by order_index
            language: {
                processing: '<span class="spinner-border spinner-border-sm align-middle ms-2"></span> Memuat...',
                emptyTable: 'Tidak ada data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(disaring dari _MAX_ total data)',
                lengthMenu: 'Tampilkan _MENU_ data',
                loadingRecords: 'Memuat...',
                search: 'Cari:',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                }
            }
        });
        
        // ========== MODAL ACTIONS ==========
        
        // Button Add
        $('#btn-add').click(function() {
            isEdit = false;
            editId = null;
            $('#modal_title').text('Tambah Data');
            $('#form_tax_bracket')[0].reset();
            
            // Clear display inputs
            $('#min_pkp_display').val('');
            $('#max_pkp_display').val('');
            
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#modal_form').modal('show');
        });
        
        // Button Edit
        $(document).on('click', '.btn-edit', function() {
            let id = $(this).data('id');
            isEdit = true;
            editId = id;
            
            $.ajax({
                url: "{{ url('pph21taxbrackets') }}/" + id + "/edit",
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        $('#modal_title').text('Edit Data');
                        $('#id').val(response.data.id);
                        
                        // Set hidden inputs
                        $('#min_pkp').val(response.data.min_pkp);
                        $('#max_pkp').val(response.data.max_pkp || '');
                        
                        // Set display inputs with formatting
                        $('#min_pkp_display').val(formatRupiah(response.data.min_pkp));
                        $('#max_pkp_display').val(response.data.max_pkp ? formatRupiah(response.data.max_pkp) : '');
                        
                        $('#rate_percent').val(response.data.rate_percent);
                        $('#order_index').val(response.data.order_index);
                        $('#description').val(response.data.description);
                        $('#effective_start_date').val(response.data.effective_start_date);
                        $('#effective_end_date').val(response.data.effective_end_date);
                        
                        $('.form-control, .form-select').removeClass('is-invalid');
                        $('.invalid-feedback').text('');
                        $('#modal_form').modal('show');
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal mengambil data'
                    });
                }
            });
        });
        
        // Form Submit
        $('#form_tax_bracket').submit(function(e) {
            e.preventDefault();
            
            let url = isEdit 
                ? "{{ url('pph21taxbrackets') }}/" + editId 
                : "{{ route('pph21taxbrackets.store') }}";
            
            let formData = new FormData(this);
            
            // Add method for Laravel
            if (isEdit) {
                formData.append('_method', 'PUT');
            }
            
            // Disable button and show spinner
            let btnSubmit = $('#btn-submit');
            btnSubmit.prop('disabled', true);
            btnSubmit.find('.indicator-label').hide();
            btnSubmit.find('.indicator-progress').show();
            
            // Clear previous errors
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#modal_form').modal('hide');
                        table.ajax.reload();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            // Handle both hidden and display inputs
                            let displayKey = key + '_display';
                            if ($('#' + displayKey).length) {
                                $('#' + displayKey).addClass('is-invalid');
                            } else {
                                $('#' + key).addClass('is-invalid');
                            }
                            $('#error-' + key).text(value[0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON.message || 'Terjadi kesalahan'
                        });
                    }
                },
                complete: function() {
                    // Enable button and hide spinner
                    btnSubmit.prop('disabled', false);
                    btnSubmit.find('.indicator-label').show();
                    btnSubmit.find('.indicator-progress').hide();
                }
            });
        });
        
        // Button Delete
        $(document).on('click', '.btn-delete', function() {
            let id = $(this).data('id');
            
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data bracket tarif PPh21 akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('pph21taxbrackets') }}/" + id,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON.message || 'Terjadi kesalahan'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush