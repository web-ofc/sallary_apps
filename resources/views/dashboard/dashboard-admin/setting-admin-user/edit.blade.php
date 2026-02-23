@extends('layouts.master')

@section('title', 'Edit Setting Admin User')

@section('content')
<!--begin::Toolbar-->
<div class="toolbar" id="kt_toolbar">
    <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
        <!--begin::Info-->
        <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
            <!--begin::Title-->
            <h1 class="text-dark fw-bold my-1 fs-2">Edit Setting Admin User</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb fw-semibold fs-base my-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('manage-setting-admin-user.index') }}" class="text-muted text-hover-primary">Setting Admin User</a>
                </li>
                <li class="breadcrumb-item text-dark">Edit</li>
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Info-->
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Post-->
<div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div class="container-xxl">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header">
                <h3 class="card-title">Edit Setting Admin User</h3>
            </div>
            <!--end::Card header-->
            
            <!--begin::Form-->
            <form action="{{ route('manage-setting-admin-user.update', $settingCompanyUser->id) }}" method="POST" id="kt_form">
                @csrf
                @method('PUT')
                <!--begin::Card body-->
                <div class="card-body">
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Admin User</label>
                        <div class="col-lg-8 fv-row">
                            <select name="user_id" id="user_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Select Admin User">
                                <option value="">Select Admin User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" 
                                        {{ (old('user_id', $settingCompanyUser->user_id) == $user->id) ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="fv-plugins-message-container invalid-feedback">
                                    <div>{{ $message }}</div>
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Company</label>
                        <div class="col-lg-8 fv-row">
                            <select name="absen_company_id" id="absen_company_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Select Company">
                                <option value="">Select Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->absen_company_id }}" 
                                        {{ (old('absen_company_id', $settingCompanyUser->absen_company_id) == $company->absen_company_id) ? 'selected' : '' }}>
                                        {{ $company->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('absen_company_id')
                                <div class="fv-plugins-message-container invalid-feedback">
                                    <div>{{ $message }}</div>
                                </div>
                            @enderror
                            @error('error')
                                <div class="fv-plugins-message-container invalid-feedback d-block">
                                    <div>{{ $message }}</div>
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!--end::Input group-->

                    <!--begin::Current Info-->
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">Current Information</label>
                        <div class="col-lg-8">
                            <div class="card bg-light-primary">
                                <div class="card-body py-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ki-duotone ki-user fs-2 text-primary me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <span class="fw-bold">User:</span>
                                        <span class="ms-2">{{ $settingCompanyUser->user->name ?? '-' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-office-bag fs-2 text-primary me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                        <span class="fw-bold">Company:</span>
                                        <span class="ms-2">{{ $settingCompanyUser->company->company_name ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Current Info-->
                </div>
                <!--end::Card body-->

                <!--begin::Card footer-->
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <a href="{{ route('manage-setting-admin-user.index') }}" class="btn btn-light btn-active-light-primary me-2">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="kt_submit">
                        <span class="indicator-label">Update</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
                <!--end::Card footer-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Container-->
</div>
<!--end::Post-->

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#user_id').select2({
            placeholder: "Select Admin User",
            allowClear: true
        });

        $('#absen_company_id').select2({
            placeholder: "Select Company",
            allowClear: true
        });

        // Form validation
        var form = document.getElementById('kt_form');
        var submitButton = document.getElementById('kt_submit');

        // Handle form submit
        form.addEventListener('submit', function(e) {
            // Show loading indicator
            submitButton.setAttribute('data-kt-indicator', 'on');
            submitButton.disabled = true;
        });

        // Error messages
        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '<ul class="text-start">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
            });
        @endif
    });
</script>
@endpush