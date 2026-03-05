@extends('layouts.master')

@section('content')

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manage Reimbursements</h3>
        <button type="button" class="btn btn-primary btn-sm" onclick="showPreCreateModal()">
            <i class="fas fa-plus"></i> Tambah Reimbursement
        </button>
    </div>

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
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" data-kt-reimbursement-table-filter="search"
                           class="form-control form-control-sm form-control-solid ps-10"
                           placeholder="Cari..." />
                </div>
            </div>
        </div>
    </div>

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
            <tbody class="fw-semibold text-gray-700 fs-8"></tbody>
        </table>
    </div>
</div>

{{-- MODAL: Pre-Create --}}
<div class="modal fade" id="preCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header px-7 py-5" style="border-bottom:1px solid var(--bs-gray-200);">
                <div class="d-flex align-items-center gap-3">
                    <div class="symbol symbol-40px">
                        <div class="symbol-label bg-gray-100">
                            <i class="ki-duotone ki-add-files fs-2 text-gray-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-gray-800 fs-5">Buat Reimbursement Baru</h5>
                        <span class="text-muted fs-7">Isi data awal untuk melanjutkan</span>
                    </div>
                </div>
                <div class="btn btn-icon btn-sm btn-light ms-auto" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <form id="preCreateForm">
                @csrf
                <div class="modal-body px-7 py-6">
                    <div class="mb-5">
                        <label class="form-label required fs-7 fw-semibold text-gray-700">Karyawan</label>
                        <select class="form-select form-select-sm" id="pre_karyawan_id" name="karyawan_id"
                                data-dropdown-parent="#preCreateModal" required><option></option></select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label required fs-7 fw-semibold text-gray-700">Company</label>
                        <select class="form-select form-select-sm" id="pre_company_id" name="company_id"
                                data-dropdown-parent="#preCreateModal" required><option></option></select>
                    </div>
                    <div class="row g-0" style="border:1px solid var(--bs-gray-200);border-radius:8px;overflow:hidden;">
                        <div class="col-12 px-4 py-3" style="border-bottom:1px solid var(--bs-gray-200);background:var(--bs-gray-50);">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ki-duotone ki-calendar fs-4 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                                <span class="fs-7 text-gray-600">Periode: <strong class="text-gray-800">{{ date('F Y') }}</strong> (otomatis)</span>
                            </div>
                        </div>
                        <div class="col-12 px-4 py-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="ki-duotone ki-information-5 fs-4 text-gray-400 mt-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <span class="fs-7 text-gray-500">Setelah klik <strong>Lanjut</strong>, Anda akan diarahkan ke halaman form reimbursement.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-7 py-4" style="border-top:1px solid var(--bs-gray-200);">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark btn-sm px-6" id="preCreateSubmitBtn">
                        <span class="indicator-label">Lanjut</span>
                        <span class="indicator-progress" style="display:none;">Mohon tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: View Detail --}}
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:1100px;">
        <div class="modal-content">
            <div class="modal-header px-7 py-5" style="border-bottom:1px solid var(--bs-gray-200);">
                <div class="d-flex align-items-center gap-3">
                    <div class="symbol symbol-40px">
                        <div class="symbol-label bg-gray-100">
                            <i class="ki-duotone ki-bill fs-2 text-gray-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-gray-800 fs-4">Detail Reimbursement</h4>
                        <span class="text-muted fs-7" id="view-modal-subtitle">Medical Reimbursement</span>
                    </div>
                </div>
                <div class="btn btn-icon btn-sm btn-light ms-auto" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body px-7 py-6" id="viewModalBody">
                <div class="text-center py-15">
                    <span class="spinner-border spinner-border-lg text-primary"></span>
                    <p class="text-gray-500 mt-4 fs-6">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer px-7 py-4" style="border-top:1px solid var(--bs-gray-200);">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let table, preCreateModal, viewModal, preKaryawanSelect2, preCompanySelect2;

$(document).ready(function() {
    preCreateModal = new bootstrap.Modal(document.getElementById('preCreateModal'));
    viewModal      = new bootstrap.Modal(document.getElementById('viewModal'));

    $('#filter-status, #filter-year').select2({ placeholder: function() { return $(this).data('placeholder'); }, allowClear: true });

    table = $('#manage-reimbursements-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('reimbursements.data') }}",
            data: function(d) { d.status = $('#filter-status').val(); d.year = $('#filter-year').val(); }
        },
        columns: [
            { data: 'DT_RowIndex',   name: 'DT_RowIndex',           orderable: false, searchable: false, className: 'ps-2 pe-1' },
            { data: 'id_recapan',    name: 'id_recapan',            className: 'px-1' },
            { data: 'karyawan_info', name: 'karyawan.nama_lengkap', className: 'px-1' },
            { data: 'company_info',  name: 'company.company_name',  className: 'px-1' },
            { data: 'periode_slip',  name: 'periode_slip',          className: 'px-1' },
            { data: 'year_budget',   name: 'year_budget',           className: 'px-1' },
            { data: 'total_amount',  name: 'total_amount',          orderable: false, searchable: false, className: 'px-1' },
            { data: 'approver_info', name: 'approver.nama_lengkap', className: 'px-1' },
            { data: 'created_by',    name: 'userBy.name',           className: 'px-1' },
            { data: 'approved_at',   name: 'approved_at',           className: 'px-1' },
            { data: 'created_at',    name: 'created_at',            className: 'px-1' },
            { data: 'status',        name: 'status',                className: 'px-1' },
            { data: 'action',        name: 'action',                orderable: false, searchable: false, className: 'px-1 pe-2 text-end' },
        ],
        order: [[10, 'desc']], pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'all']],
        scrollX: true, autoWidth: false
    });

    $('#filter-status, #filter-year').on('change', function() { table.ajax.reload(); });
    $('[data-kt-reimbursement-table-filter="search"]').on('keyup', function() { table.search(this.value).draw(); });

    $('#preCreateForm').on('submit', function(e) {
        e.preventDefault();
        $('#pre_company_id').prop('disabled', false);

        const btn = $('#preCreateSubmitBtn');
        btn.prop('disabled', true).find('.indicator-label').hide();
        btn.find('.indicator-progress').show();

        $.ajax({
            url: "{{ route('manage-reimbursements.validate-pre-create') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(r) { if (r.success && r.redirect_url) window.location.href = r.redirect_url; },
            error: function(xhr) {
                $('#pre_company_id').prop('disabled', true);
                Swal.fire({ icon: 'error', title: 'Gagal!', text: xhr.responseJSON?.message || 'Terjadi kesalahan.' });
            },
            complete: function() {
                btn.prop('disabled', false).find('.indicator-label').show();
                btn.find('.indicator-progress').hide();
            }
        });
    });
});

function showPreCreateModal() {
    openPreCreateModal();
    $.ajax({
        url: "{{ route('manage-reimbursements.sync-karyawan') }}",
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(r) { console.log('[Sync]', r.message); },
        error: function() { console.warn('[Sync] Gagal'); }
    });
}

function openPreCreateModal() {
    $('#preCreateForm')[0].reset();
    clearCompanySuggestionBadge();
    $('#pre_company_id').prop('disabled', false);

    if (preKaryawanSelect2) $('#pre_karyawan_id').select2('destroy');
    preKaryawanSelect2 = $('#pre_karyawan_id').select2({
        ajax: {
            url: "{{ route('manage-reimbursements.karyawan.list') }}",
            dataType: 'json', delay: 250,
            data: p => ({ q: p.term || '', page: p.page || 1 }),
            processResults: d => ({ results: d.results, pagination: d.pagination }),
            cache: true
        },
        dropdownParent: $('#preCreateModal'),
        placeholder: 'Cari nama / NIK karyawan...',
        allowClear: true, minimumInputLength: 0,
        templateResult: formatKaryawanOption,
    });

    $('#pre_karyawan_id').off('select2:select').on('select2:select', function(e) {
        const suggestion = e.params.data.company_suggestion;
        $('#pre_company_id').val(null).trigger('change').prop('disabled', false);
        clearCompanySuggestionBadge();

        if (!suggestion?.absen_company_id) {
            $('#pre_company_id').prop('disabled', true);
            showSuggestionWarning('Karyawan belum memiliki data perusahaan. Lakukan sinkronasi terlebih dahulu.');
            return;
        }
        $('#pre_company_id').append(new Option(suggestion.company_name + ' (ID: ' + suggestion.absen_company_id + ')', suggestion.absen_company_id, true, true)).trigger('change').prop('disabled', true);
        showSuggestionBadge(suggestion.company_name, suggestion.tgl_mutasi);
    });

    $('#pre_karyawan_id').off('select2:clear').on('select2:clear', function() {
        clearCompanySuggestionBadge();
        $('#pre_company_id').prop('disabled', false).val(null).trigger('change');
    });

    if (preCompanySelect2) $('#pre_company_id').select2('destroy');
    preCompanySelect2 = $('#pre_company_id').select2({
        ajax: {
            url: "{{ route('manage-reimbursements.company.list') }}",
            dataType: 'json', delay: 250,
            data: p => ({ q: p.term || '', page: p.page || 1 }),
            processResults: d => ({ results: d.results, pagination: d.pagination }),
            cache: true
        },
        dropdownParent: $('#preCreateModal'),
        placeholder: 'Pilih company...', allowClear: true, minimumInputLength: 0
    });

    preCreateModal.show();
}

function showSuggestionBadge(name, tgl) {
    clearCompanySuggestionBadge();
    const tglLabel = tgl ? ' <span class="text-muted">(sejak ' + tgl + ')</span>' : '';
    $('#pre_company_id').closest('.mb-5').append(`
        <div id="company-suggestion-badge" class="d-flex align-items-center gap-2 mt-2 px-3 py-2" style="border-radius:6px;background:#f0f9ff;border:1px solid #bae6fd;">
            <i class="ki-duotone ki-information-5 fs-5 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <span class="fs-8 text-gray-600">Otomatis dari mutasi terbaru: <strong class="text-gray-800">${name}</strong>${tglLabel}</span>
        </div>`);
}

function showSuggestionWarning(msg) {
    clearCompanySuggestionBadge();
    $('#pre_company_id').closest('.mb-5').append(`
        <div id="company-suggestion-badge" class="d-flex align-items-center gap-2 mt-2 px-3 py-2" style="border-radius:6px;background:#fffbeb;border:1px solid #fcd34d;">
            <i class="ki-duotone ki-information-5 fs-5 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <span class="fs-8 text-gray-600">${msg}</span>
        </div>`);
}

function clearCompanySuggestionBadge() { $('#company-suggestion-badge').remove(); }

function formatKaryawanOption(option) {
    if (!option.id) return option.text;
    const jabatan = option.jabatan?.nama_jabatan || '';
    return $(`<div class="d-flex flex-column py-1"><span class="fw-semibold text-gray-800 fs-7">${option.text}</span>${jabatan ? `<span class="text-muted fs-8">${jabatan}</span>` : ''}</div>`);
}

// ── View Detail ───────────────────────────────────────────────────
function viewReimbursement(id) {
    resetViewModal();
    viewModal.show();
    $.ajax({
        url: "{{ url('manage-reimbursements') }}/" + id,
        type: 'GET',
        success: r => r.success ? renderViewContent(r.data) : showViewModalError('Data tidak ditemukan'),
        error: () => showViewModalError('Gagal memuat detail reimbursement')
    });
}

function renderViewContent(data) {
    $('#view-modal-subtitle').text((data.karyawan?.nama_lengkap || '-') + ' · ' + data.periode_slip);

    let grandTotal = 0;
    let html = `
        <div class="row g-0 mb-6" style="border:1px solid var(--bs-gray-200);border-radius:10px;overflow:hidden;">
            <div class="col-md-3 px-5 py-4" style="border-right:1px solid var(--bs-gray-200);">
                <div class="text-gray-500 fs-8 fw-semibold text-uppercase ls-1 mb-2">Karyawan</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="symbol symbol-circle symbol-38px">
                        <div class="symbol-label fs-5 fw-bold bg-gray-100 text-gray-700">${(data.karyawan?.nama_lengkap||'U').charAt(0).toUpperCase()}</div>
                    </div>
                    <div>
                        <div class="fw-bold text-gray-800 fs-6 lh-1 mb-1">${data.karyawan?.nama_lengkap||'-'}</div>
                        <div class="text-muted fs-7">NIK: ${data.karyawan?.nik||'-'}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 px-5 py-4" style="border-right:1px solid var(--bs-gray-200);">
                <div class="text-gray-500 fs-8 fw-semibold text-uppercase ls-1 mb-2">Periode & Tahun Budget</div>
                <div class="fw-semibold text-gray-800 fs-6">${data.periode_slip||'-'}</div>
                <div class="text-muted fs-7">Tahun: ${data.year_budget||'-'}</div>
            </div>
            <div class="col-md-3 px-5 py-4" style="border-right:1px solid var(--bs-gray-200);">
                <div class="text-gray-500 fs-8 fw-semibold text-uppercase ls-1 mb-2">Approver</div>
                <div class="fw-semibold text-gray-800 fs-6">${data.approver?.nama_lengkap||'-'}</div>
                ${data.approved_at ? `<div class="text-muted fs-7">${formatDate(data.approved_at)}</div>` : ''}
            </div>
            <div class="col-md-3 px-5 py-4">
                <div class="text-gray-500 fs-8 fw-semibold text-uppercase ls-1 mb-2">Status</div>
                ${data.status ? '<span class="badge badge-light-success px-3 py-2">Approved</span>' : '<span class="badge badge-light-warning px-3 py-2">Pending Approval</span>'}
                <div class="text-muted fs-7 mt-1">ID: ${data.id_recapan||'-'}</div>
            </div>
        </div>
        <div style="border:1px solid var(--bs-gray-200);border-radius:10px;overflow:hidden;">
            <div class="px-5 py-4 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--bs-gray-200);background:var(--bs-gray-50);">
                <span class="fw-bold text-gray-700 fs-6">Detail Items</span>
                <span class="badge badge-light fs-7">${data.childs?.length||0} item</span>
            </div>
            <div class="table-responsive" style="max-height:380px;overflow-y:auto;">
                <table class="table table-row-gray-200 align-middle mb-0" style="min-width:950px;">
                    <thead style="position:sticky;top:0;z-index:1;background:#fff;">
                        <tr class="text-gray-500 fs-8 fw-semibold text-uppercase" style="border-bottom:2px solid var(--bs-gray-200);">
                            <th class="ps-5 py-3 w-30px">#</th>
                            <th class="py-3 min-w-90px">Tanggal</th>
                            <th class="py-3 min-w-130px">Nama Pasien</th>
                            <th class="py-3 min-w-100px">Status Kel.</th>
                            <th class="py-3 min-w-130px">Jenis Penyakit</th>
                            <th class="py-3 text-end min-w-110px">Tagihan Dokter</th>
                            <th class="py-3 text-end min-w-100px">Tagihan Obat</th>
                            <th class="py-3 text-end min-w-110px">Kacamata</th>
                            <th class="py-3 text-end min-w-90px">Gigi</th>
                            <th class="py-3 text-end pe-5 min-w-110px">Subtotal</th>
                            <th class="py-3 min-w-100px">Note</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fs-7">`;

    if (data.childs?.length > 0) {
        data.childs.forEach((child, i) => {
            const dokter   = child.tagihan_dokter   ?? 0;
            const obat     = child.tagihan_obat     ?? 0;
            const kacamata = child.tagihan_kacamata ?? 0;
            const gigi     = child.tagihan_gigi     ?? 0;
            const sub      = dokter + obat + kacamata + gigi;
            grandTotal    += sub;

            const jp = child.jenis_penyakit
                ? (child.jenis_penyakit.kode ? '[' + child.jenis_penyakit.kode + '] ' : '') + child.jenis_penyakit.nama_penyakit
                : '-';

            html += `
                <tr>
                    <td class="ps-5 text-muted">${i+1}</td>
                    <td class="fw-semibold">${child.tanggal||'-'}</td>
                    <td class="fw-semibold text-gray-800">${child.nama_reimbursement||'-'}</td>
                    <td>${child.status_keluarga||'-'}</td>
                    <td>${jp}</td>
                    <td class="text-end">${dokter   > 0 ? 'Rp&nbsp;'+formatRupiah(dokter)   : '<span class="text-muted">—</span>'}</td>
                    <td class="text-end">${obat     > 0 ? 'Rp&nbsp;'+formatRupiah(obat)     : '<span class="text-muted">—</span>'}</td>
                    <td class="text-end">${kacamata > 0 ? 'Rp&nbsp;'+formatRupiah(kacamata) : '<span class="text-muted">—</span>'}</td>
                    <td class="text-end">${gigi     > 0 ? 'Rp&nbsp;'+formatRupiah(gigi)     : '<span class="text-muted">—</span>'}</td>
                    <td class="text-end pe-5 fw-bold text-gray-800">Rp&nbsp;${formatRupiah(sub)}</td>
                    <td class="text-muted">${child.note||'—'}</td>
                </tr>`;
        });
    } else {
        html += `<tr><td colspan="11" class="text-center text-muted py-10">Tidak ada item</td></tr>`;
    }

    html += `
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid var(--bs-gray-200);background:var(--bs-gray-50);">
                            <td colspan="9" class="ps-5 py-4 text-end text-gray-600 fs-7 fw-bold text-uppercase">Grand Total</td>
                            <td class="text-end pe-5 py-4 fw-bold text-gray-800 fs-6">Rp&nbsp;${formatRupiah(grandTotal)}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>`;

    $('#viewModalBody').html(html);
}

// ── Delete & Edit ─────────────────────────────────────────────────
function deleteReimbursement(id, nama) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: 'Anda akan menghapus reimbursement untuk ' + nama + '!',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal'
    }).then(result => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('manage-reimbursements') }}/" + id,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
            success: r => {
                if (r.success) {
                    Swal.fire({ icon: 'success', title: 'Dihapus!', text: r.message, timer: 2000, showConfirmButton: false });
                    table.ajax.reload();
                }
            },
            error: xhr => Swal.fire({ icon: 'error', title: 'Gagal!', text: xhr.responseJSON?.message || 'Terjadi kesalahan.' })
        });
    });
}

function editReimbursement(id) {
    window.location.href = "{{ route('manage-reimbursements.index') }}/" + id + "/edit";
}

// ── Helpers ───────────────────────────────────────────────────────
function resetViewModal() {
    $('#view-modal-subtitle').text('Medical Reimbursement');
    $('#viewModalBody').html(`<div class="text-center py-15"><span class="spinner-border spinner-border-lg text-primary"></span><p class="text-gray-500 mt-4 fs-6">Memuat data...</p></div>`);
}

function showViewModalError(msg) {
    $('#viewModalBody').html(`
        <div class="d-flex align-items-center gap-3 p-5" style="border:1px solid var(--bs-danger-light);border-radius:10px;background:#fff5f8;">
            <i class="ki-duotone ki-cross-circle fs-2x text-danger"><span class="path1"></span><span class="path2"></span></i>
            <div><div class="fw-bold text-danger fs-6 mb-1">Gagal Memuat Data</div><div class="text-gray-600 fs-7">${msg}</div></div>
        </div>`);
}

function formatRupiah(angka) { return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
function formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }

$('#preCreateModal').on('hidden.bs.modal', function() {
    $('#preCreateForm')[0].reset();
    clearCompanySuggestionBadge();
    if (preKaryawanSelect2) $('#pre_karyawan_id').val(null).trigger('change');
    if (preCompanySelect2)  $('#pre_company_id').val(null).trigger('change');
});
$('#viewModal').on('hidden.bs.modal', resetViewModal);
</script>
@endpush