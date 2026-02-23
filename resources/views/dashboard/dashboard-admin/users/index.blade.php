@extends('layouts.master')

@section('content')
<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                User Management
            </h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Users</li>
            </ul>
        </div>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid">
    <div id="kt_app_content_container" class="app-container ">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search users..."/>
                    </div>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary" onclick="createUser()">
                        <i class="ki-duotone ki-plus fs-2"></i>
                        Add User
                    </button>
                </div>
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-0">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="usersTable">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th>No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
</div>
<!--end::Content-->

<!--begin::Modal - Create/Edit User-->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_user_header">
                <h2 class="fw-bold" id="modalTitle">Add User</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <form id="userForm" class="form">
                    <input type="hidden" id="userId" name="user_id">
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Full name" required/>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Email</label>
                        <input type="email" name="email" id="email" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="example@domain.com" required/>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">
                            <span id="passwordLabel">Password</span>
                            <span class="text-muted" id="passwordHint" style="display:none;">(Leave blank to keep current password)</span>
                        </label>
                        <input type="password" name="password" id="password" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Password"/>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Role</label>
                        <select name="role" id="role" class="form-select form-select-solid" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="management">Management</option>
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked/>
                            <span class="form-check-label fw-semibold text-muted">Active Status</span>
                        </label>
                    </div>

                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress" style="display: none;">
                                Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Create/Edit User-->
@endsection

@push('scripts')
<script>
let table;
const modal = new bootstrap.Modal(document.getElementById('userModal'));
const userForm = document.getElementById('userForm');
const submitBtn = document.getElementById('submitBtn');

$(document).ready(function() {
    // Initialize DataTable
    table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('users.data') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'role', name: 'role' },
            { data: 'status', name: 'is_active' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        language: {
            processing: '<span class="spinner-border spinner-border-sm"></span> Loading...'
        }
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Form submit handler
    userForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const userId = document.getElementById('userId').value;
        const url = userId ? 
            "{{ route('manage-user.update', ':id') }}".replace(':id', userId) : 
            "{{ route('manage-user.store') }}";
        
        const method = userId ? 'PUT' : 'POST';
        
        // Show loading
        submitBtn.disabled = true;
        submitBtn.querySelector('.indicator-label').style.display = 'none';
        submitBtn.querySelector('.indicator-progress').style.display = 'inline-block';

        const formData = new FormData(userForm);
        formData.append('_method', method);
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                modal.hide();
                table.ajax.reload();
                userForm.reset();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message || 'Something went wrong'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Network error. Please try again.'
            });
        } finally {
            submitBtn.disabled = false;
            submitBtn.querySelector('.indicator-label').style.display = 'inline-block';
            submitBtn.querySelector('.indicator-progress').style.display = 'none';
        }
    });
});

function createUser() {
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('passwordLabel').textContent = 'Password';
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('password').setAttribute('required', 'required');
    userForm.reset();
    document.getElementById('userId').value = '';
    document.getElementById('is_active').checked = true;
    modal.show();
}

async function editUser(id) {
    try {
        const response = await fetch("{{ route('manage-user.edit', ':id') }}".replace(':id', id), {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const user = result.data;
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('passwordLabel').textContent = 'Password';
            document.getElementById('passwordHint').style.display = 'inline';
            document.getElementById('password').removeAttribute('required');
            document.getElementById('userId').value = user.id;
            document.getElementById('name').value = user.name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('is_active').checked = user.is_active;
            document.getElementById('password').value = '';
            modal.show();
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to load user data'
        });
    }
}

async function deleteUser(id, name) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete user: <strong>${name}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch("{{ route('manage-user.destroy', ':id') }}".replace(':id', id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                table.ajax.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to delete user'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Network error. Please try again.'
            });
        }
    }
}
</script>
@endpush