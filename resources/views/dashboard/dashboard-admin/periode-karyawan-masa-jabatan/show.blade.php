@extends('layouts.master')

@section('title', 'Detail Periode Karyawan')

@section('content')
<!--begin::Toolbar-->
<div class="toolbar" id="kt_toolbar">
    <div class="container-fluid d-flex flex-stack">
        <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
            <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Detail Periode Karyawan</h1>
            <span class="h-20px border-gray-300 border-start mx-4"></span>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard.admin') }}" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-300 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('periode-karyawan.index') }}" class="text-muted text-hover-primary">Periode Karyawan</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-300 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-dark">Detail</li>
            </ul>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('periode-karyawan.index') }}" class="btn btn-sm btn-light-primary">
                <i class="ki-outline ki-arrow-left fs-3"></i> Kembali
            </a>
        </div>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div class="container-fluid">
        
        <!--begin::Card Header Info-->
        <div class="card mb-5">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-60px me-5">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-profile-circle fs-2x text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-800 text-hover-primary fs-4 fw-bold">{{ $data->karyawan->nama_lengkap ?? '-' }}</a>
                                <div class="text-muted fw-semibold fs-6">NIK: {{ $data->karyawan->nik ?? '-' }}</div>
                                <div class="text-muted fw-semibold fs-7">{{ $data->company->company_name ?? '-' }} ({{ $data->company->code ?? '-' }})</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="mb-2">
                            <span class="badge badge-lg badge-light-primary">Periode {{ $data->periode }}</span>
                        </div>
                        <div class="mb-2">
                            <span class="badge badge-lg {{ $data->salary_type === 'nett' ? 'badge-light-success' : 'badge-light-info' }}">
                                {{ strtoupper($data->salary_type) }}
                            </span>
                        </div>
                        <div class="text-muted fs-7">Masa Jabatan: <span class="fw-bold">{{ $data->masa_jabatan }} Bulan</span></div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Card Header Info-->

        <div class="row g-5 g-xl-8">
            
            <!--begin::Col Pendapatan-->
            <div class="col-xl-8">
                <div class="card mb-5">
                    <div class="card-header">
                        <h3 class="card-title fw-bold">Komponen Pendapatan</h3>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-4 mb-0">
                                <tbody>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">Gaji Pokok & KPI</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Salary</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->salary, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">Lembur</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Overtime</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->overtime, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">Tunjangan</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Allowance</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->tunjangan, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">Natura</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Medical Reimbursement</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->natura, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    @if($data->salary_type === 'nett')
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">Tunjangan PPh 21</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Tax Allowance</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->tunj_pph_21, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">Tunjangan Asuransi</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Insurance Allowance</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->tunjangan_asuransi, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">BPJS Asuransi</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Company Insurance</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->bpjs_asuransi, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-6">THR & Bonus</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Yearly Benefit</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-6">Rp {{ number_format($data->thr_bonus, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr class="border-top border-top-dashed">
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-bold d-block fs-5">Total Bruto</span>
                                            <span class="text-muted fw-semibold d-block fs-7">Gross Income</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-primary fw-bold fs-3">Rp {{ number_format($data->total_bruto, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Col-->
            
            <!--begin::Col Pengurang & PKP-->
            <div class="col-xl-4">
                
                <!--begin::Card Pengurang-->
                <div class="card mb-5">
                    <div class="card-header">
                        <h3 class="card-title fw-bold">Pengurang Penghasilan</h3>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-4 mb-0">
                                <tbody>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-7">Biaya Jabatan</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-7">Rp {{ number_format($data->biaya_jabatan, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-7">Premi Asuransi</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-7">Rp {{ number_format($data->premi_asuransi, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-9">
                                            <span class="text-gray-800 fw-semibold d-block fs-7">PTKP</span>
                                            <span class="text-muted fw-semibold d-block fs-8">{{ $data->kriteria ?? '-' }}</span>
                                        </td>
                                        <td class="text-end pe-9">
                                            <span class="text-gray-800 fw-bold fs-7">Rp {{ number_format($data->besaran_ptkp, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!--end::Card Pengurang-->
                
                <!--begin::Card PKP-->
                <div class="card {{ $data->pkp < 0 ? 'border border-danger' : 'border border-success' }}">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-{{ $data->pkp < 0 ? 'cross-circle' : 'check-circle' }} fs-3x mb-5 {{ $data->pkp < 0 ? 'text-danger' : 'text-success' }}">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold text-gray-800 mb-3">Penghasilan Kena Pajak</h3>
                        <div class="fs-2x fw-bolder {{ $data->pkp < 0 ? 'text-danger' : 'text-success' }} mb-3">
                            Rp {{ number_format(abs($data->pkp), 0, ',', '.') }}
                        </div>
                        <span class="badge badge-light-{{ $data->pkp < 0 ? 'danger' : 'success' }} fs-7">
                            {{ $data->pkp < 0 ? 'Tidak Kena Pajak (PKP Negatif)' : 'Kena Pajak' }}
                        </span>
                    </div>
                </div>
                <!--end::Card PKP-->
                
            </div>
            <!--end::Col-->
            
        </div>
        
        <!--begin::Card Payroll IDs-->
        <div class="card mt-5">
            <div class="card-header">
                <h3 class="card-title fw-bold">Referensi Payroll</h3>
            </div>
            <div class="card-body">
                <div class="text-muted fs-7 mb-2">Payroll IDs yang digunakan dalam perhitungan:</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(explode(',', $data->payroll_ids) as $payrollId)
                    <span class="badge badge-light-primary">ID: {{ $payrollId }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        <!--end::Card Payroll IDs-->
        
    </div>
</div>
<!--end::Post-->
@endsection