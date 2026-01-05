@extends('layouts.master')

@section('title', 'Detail Payroll')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap flex-stack mb-6">
        <h1 class="fw-bold my-2">
            <i class="fas fa-file-invoice-dollar text-primary"></i> Detail Payroll
        </h1>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('payrolls.edit', $payroll->id) }}" class="btn btn-sm btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('payrolls.index') }}" class="btn btn-sm btn-light-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Status Card -->
    <div class="card mb-5 mb-xl-10">
        <div class="card-body pt-9 pb-0">
            <div class="d-flex flex-wrap flex-sm-nowrap mb-3">
                <!-- Company Logo -->
                @php
                    $companyLogo = $company->logo ?? null;
                    $companyName = $company->company_name ?? 'No Company';
                    $companyCode = $company->code ?? '-';
                    $companyInitial = !empty($companyName) ? strtoupper(substr($companyName, 0, 1)) : '?';
                    $absenUrl = env('ABSEN_URL', 'http://127.0.0.1:8000');
                @endphp
                
                <div class="me-7 mb-4">
                    <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                        @if($companyLogo)
                            <img src="{{ $absenUrl }}/storage/{{ $companyLogo }}" 
                                 alt="{{ $companyName }}"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'symbol-label fs-2hx fw-semibold bg-light-primary text-primary\'>{{ $companyInitial }}</div>'"/>
                        @else
                            <div class="symbol-label fs-2hx fw-semibold bg-light-primary text-primary">
                                {{ $companyInitial }}
                            </div>
                        @endif
                        @if($payroll->is_released)
                            <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px"></div>
                        @endif
                    </div>
                </div>
                
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-gray-900 fs-2 fw-bold me-1">
                                    {{ $karyawan->nama_lengkap ?? 'Unknown Employee' }}
                                </span>
                                @if($payroll->is_released)
                                    <span class="badge badge-success">Released</span>
                                @else
                                    <span class="badge badge-warning">Draft</span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                <span class="d-flex align-items-center text-gray-400 me-5 mb-2">
                                    <i class="fas fa-id-card me-1"></i>
                                    NIK: {{ $karyawan->nik ?? '-' }}
                                </span>
                                <span class="d-flex align-items-center text-gray-400 me-5 mb-2">
                                    <i class="fas fa-calendar me-1"></i>
                                    Periode: {{ date('F Y', strtotime($payroll->periode . '-01')) }}
                                </span>
                                @if($company)
                                    <span class="d-flex align-items-center text-gray-400 mb-2">
                                        <i class="fas fa-building me-1"></i>
                                        {{ $companyName }} 
                                        <span class="badge badge-light-primary ms-2">{{ $companyCode }}</span>
                                    </span>
                                @else
                                    <span class="d-flex align-items-center text-gray-400 mb-2">
                                        <i class="fas fa-building me-1"></i>
                                        No Company Assigned
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-5 mb-5">
        <div class="col-md-3">
            <div class="card bg-light-success">
                <div class="card-body">
                    <div class="fw-bold text-success mb-1">Salary</div>
                    <div class="fs-2 fw-bolder">Rp {{ number_format($payroll->salary ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light-primary">
                <div class="card-body">
                    <div class="fw-bold text-primary mb-1">Total Penerimaan</div>
                    <div class="fs-2 fw-bolder">Rp {{ number_format($payroll->total_penerimaan ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light-danger">
                <div class="card-body">
                    <div class="fw-bold text-danger mb-1">Total Potongan</div>
                    <div class="fs-2 fw-bolder">Rp {{ number_format($payroll->total_potongan ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light-info">
                <div class="card-body">
                    <div class="fw-bold text-info mb-1">Gaji Bersih</div>
                    <div class="fs-2 fw-bolder">Rp {{ number_format($payroll->gaji_bersih ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Sections in 2 Columns -->
    <div class="row g-5">
        <!-- Left Column -->
        <div class="col-md-6">
            <!-- Gaji Pokok -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-money-bill-wave text-success me-2"></i>
                        Gaji Pokok
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold text-gray-800">Gaji Pokok</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->gaji_pokok ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Insentif -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-award text-warning me-2"></i>
                        Monthly Insentif
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Monthly KPI</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->monthly_kpi ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Overtime</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->overtime ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Medical Reimbursement</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->medical_reimbursement ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Insentif Sholat</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->insentif_sholat ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Monthly Bonus</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->monthly_bonus ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-gray-700">Rapel</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->rapel ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Allowance -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-hand-holding-usd text-info me-2"></i>
                        Monthly Allowance
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Tunjangan Pulsa</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->tunjangan_pulsa ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Tunjangan Kehadiran</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->tunjangan_kehadiran ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Tunjangan Transport</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->tunjangan_transport ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-gray-700">Tunjangan Lainnya</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->tunjangan_lainnya ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Yearly Benefit -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-gift text-success me-2"></i>
                        Yearly Benefit
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">Yearly Bonus</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->yearly_bonus ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">THR</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->thr ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-gray-700">Other</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->other ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

             <!-- Informasi Tambahan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-info-circle text-secondary me-2"></i>
                        Informasi Tambahan
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="fw-bold text-gray-700 mb-2">Salary Type</label>
                                <div>
                                    @if($payroll->salary_type == 'gross')
                                        <span class="badge badge-light-primary">Gross</span>
                                    @elseif($payroll->salary_type == 'nett')
                                        <span class="badge badge-light-success">Nett</span>
                                    @else
                                        <span class="badge badge-light">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="fw-bold text-gray-700 mb-2">Status Release</label>
                                <div>
                                    @if($payroll->is_released)
                                        <span class="badge badge-success fs-6">
                                            <i class="fas fa-check-circle me-1"></i> Released
                                        </span>
                                    @else
                                        <span class="badge badge-warning fs-6">
                                            <i class="fas fa-clock me-1"></i> Draft
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="separator my-4"></div>
                    <div class="row text-muted">
                        <div class="col-md-6">
                            <small>Dibuat: {{ date('d M Y H:i', strtotime($payroll->created_at)) }}</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small>Diupdate: {{ date('d M Y H:i', strtotime($payroll->updated_at)) }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
            <!-- Potongan -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-cut text-danger me-2"></i>
                        Potongan
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">CA Corporate</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->ca_corporate ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">CA Personal</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->ca_personal ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">CA Kehadiran</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->ca_kehadiran ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-gray-700">PPh 21</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->pph_21 ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- BPJS Tenaga Kerja -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-shield-alt text-primary me-2"></i>
                        BPJS Tenaga Kerja
                    </h3>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Perusahaan (Income)</div>
                        <div class="fs-4 fw-bolder text-success">
                            Rp {{ number_format($payroll->bpjs_tenaga_kerja_perusahaan_income ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Perusahaan (Deduction)</div>
                        <div class="fs-4 fw-bolder text-danger">
                            Rp {{ number_format($payroll->bpjs_tenaga_kerja_perusahaan_deduction ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Pegawai (Income)</div>
                        <div class="fs-4 fw-bolder text-success">
                            Rp {{ number_format($payroll->bpjs_tenaga_kerja_pegawai_income ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Pegawai (Deduction)</div>
                        <div class="fs-4 fw-bolder text-danger">
                            Rp {{ number_format($payroll->bpjs_tenaga_kerja_pegawai_deduction ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="separator my-3"></div>
                    <div class="fw-bold text-gray-600 mb-2">Detail Komponen:</div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">JHT 3.7%</span>
                        <span>Rp {{ number_format($payroll->bpjs_tk_jht_3_7_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">JHT 2%</span>
                        <span>Rp {{ number_format($payroll->bpjs_tk_jht_2_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">JKK 0.24%</span>
                        <span>Rp {{ number_format($payroll->bpjs_tk_jkk_0_24_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">JKM 0.3%</span>
                        <span>Rp {{ number_format($payroll->bpjs_tk_jkm_0_3_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">JP 2%</span>
                        <span>Rp {{ number_format($payroll->bpjs_tk_jp_2_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">JP 1%</span>
                        <span>Rp {{ number_format($payroll->bpjs_tk_jp_1_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-gray-700">BPJS TK (Lainnya)</span>
                        <span>Rp {{ number_format($payroll->bpjs_tenaga_kerja ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- BPJS Kesehatan -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-heartbeat text-danger me-2"></i>
                        BPJS Kesehatan
                    </h3>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Perusahaan (Income)</div>
                        <div class="fs-4 fw-bolder text-success">
                            Rp {{ number_format($payroll->bpjs_kesehatan_perusahaan_income ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Perusahaan (Deduction)</div>
                        <div class="fs-4 fw-bolder text-danger">
                            Rp {{ number_format($payroll->bpjs_kesehatan_perusahaan_deduction ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Pegawai (Income)</div>
                        <div class="fs-4 fw-bolder text-success">
                            Rp {{ number_format($payroll->bpjs_kesehatan_pegawai_income ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold text-muted mb-2">Pegawai (Deduction)</div>
                        <div class="fs-4 fw-bolder text-danger">
                            Rp {{ number_format($payroll->bpjs_kesehatan_pegawai_deduction ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="separator my-3"></div>
                    <div class="fw-bold text-gray-600 mb-2">Detail Komponen:</div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">BPJS Kes 4%</span>
                        <span>Rp {{ number_format($payroll->bpjs_kes_4_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-gray-700">BPJS Kes 1%</span>
                        <span>Rp {{ number_format($payroll->bpjs_kes_1_percent ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-gray-700">BPJS Kesehatan (Lainnya)</span>
                        <span>Rp {{ number_format($payroll->bpjs_kesehatan ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- PPh 21 -->
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title fw-bold">
                        <i class="fas fa-receipt text-warning me-2"></i>
                        PPh 21
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span class="text-gray-700">PPh 21 Deduction</span>
                        <span class="fw-bold">Rp {{ number_format($payroll->pph_21_deduction ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    console.log('Payroll detail loaded');
});
</script>
@endpush
@endsection