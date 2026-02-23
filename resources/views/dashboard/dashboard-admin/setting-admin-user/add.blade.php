@extends('layouts.master')

@section('title', 'Add Setting Admin User - ' . $title)

@section('content')
<!--begin::Toolbar-->
<div class="toolbar" id="kt_toolbar">
    <div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
        <!--begin::Info-->
        <div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
            <!--begin::Title-->
            <h1 class="text-dark fw-bold my-1 fs-2">Add Setting Admin User</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb fw-semibold fs-base my-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Dashboard</a>
                </li>
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('manage-setting-admin-user.index') }}" class="text-muted text-hover-primary">Setting Admin User</a>
                </li>
                <li class="breadcrumb-item text-dark">Add</li>
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
                <h3 class="card-title">Add New Setting Admin User</h3>
            </div>
            <!--end::Card header-->
            
            <!--begin::Form-->
            <form action="{{ route('manage-setting-admin-user.store') }}" method="POST" id="kt_form">
                @csrf
                <!--begin::Card body-->
                <div class="card-body">
                    <!--begin::Input group-->
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Admin User</label>
                        <div class="col-lg-8 fv-row">
                            <select name="user_id" id="user_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Select Admin User">
                                <option value="">Select Admin User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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
                        <label class="col-lg-4 col-form-label required fw-semibold fs-6">Companies</label>
                        <div class="col-lg-8 fv-row">
                            <select name="absen_company_id[]" id="absen_company_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Select Companies" multiple>
                                @foreach($companies as $company)
                                    <option value="{{ $company->absen_company_id }}" 
                                        {{ (is_array(old('absen_company_id')) && in_array($company->absen_company_id, old('absen_company_id'))) ? 'selected' : '' }}>
                                        {{ $company->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">You can select multiple companies</div>
                            @error('absen_company_id')
                                <div class="fv-plugins-message-container invalid-feedback">
                                    <div>{{ $message }}</div>
                                </div>
                            @enderror
                            @if($errors->any())
                                @foreach($errors->all() as $error)
                                    <div class="fv-plugins-message-container invalid-feedback d-block">
                                        <div>{{ $error }}</div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <!--end::Card body-->

                <!--begin::Card footer-->
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <a href="{{ route('manage-setting-admin-user.index') }}" class="btn btn-light btn-active-light-primary me-2">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="kt_submit">
                        <span class="indicator-label">Save</span>
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
            placeholder: "Select Companies",
            allowClear: true,
            closeOnSelect: false
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