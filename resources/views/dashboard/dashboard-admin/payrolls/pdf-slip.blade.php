{{-- resources/views/dashboard/dashboard-user/payroll/slip-pdf.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $payroll['periode'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #000;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-box {
            border: 1px solid #000;
            padding: 10px;
            width: 120px;
            height: 50px;
            text-align: center;
            vertical-align: middle;
        }
        .company-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #666;
            padding: 5px 0;
        }
        .slip-header {
            background-color: #ff4444;
            color: white;
            text-align: center;
            padding: 8px;
            font-weight: bold;
            font-size: 11pt;
        }
        .info-label {
            font-weight: bold;
            width: 90px;
        }
        .separator-line {
            border-top: 1px solid #000;
            height: 1px;
        }
        .separator-header {
            border-top: 1px solid #000;
            height: 1px;
        }
        .column-title {
            font-weight: bold;
            font-size: 8pt;
            border-bottom: 1px solid #000;
            padding: 0px 0;
        }
        .line-item {
            font-size: 7.5pt;
            padding: 2px 0;
        }
        .item-label {
            text-align: left;
        }
        .item-value {
            text-align: right;
            width: 100px;
        }
        .section-title {
            font-weight: bold;
            padding-top: 5px;
            font-size: 7.5pt;
        }
        .sub-item {
            padding-left: 15px;
        }
        .total-box {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 6px 0;
            font-weight: bold;
            font-size: 8pt;
            background-color: #f5f5f5;
        }
        .take-home-box {
            background-color: #fccd8c;
            padding: 2px;
            font-weight: bold;
            text-align: center;
        }
        
        
        .bank-box {
            border: 1px solid #000;
            padding: 8px;
            font-size: 7.5pt;
        }
        .bank-title {
            font-weight: bold;
            padding-bottom: 5px;
        }
       
        .box-title {
            font-weight: bold;
            font-size: 7.5pt;
            padding-bottom: 5px;
        }
        .attendance-table {
            width: 100%;
            font-size: 7pt;
        }
        .attendance-table td {
            border: 1px solid #000;
            padding: 1px;
            text-align: center;
        }
        .keterangan-box {
            border: 1px solid #000;
            padding: 10px;
            font-size: 7pt;
        }
        .ket-title {
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            padding-bottom: 5px;
        }
        .signature-area {
            text-align: center;
        }
        .company-name-sign {
            font-weight: bold;
            font-size: 9pt;
            padding-bottom: 0px;
        }
        .employee-name-sign {
            font-weight: bold;
            text-decoration: underline;
            font-size: 8.5pt;
        }
        .position-sign {
            font-size: 7.5pt;
        }
        .footer-code {
            text-align: center;
            font-weight: bold;
            font-size: 7.5pt;
            padding-top: 15px;
        }
        /* === TOTAL SEPARATOR (BOLD) === */
        .total-separator {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            height: 0;
            margin: 3px 0;
        }

        /* === KUNCI KOLOM TOTAL AGAR SAMA === */
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-label {
            font-weight: bold;
        }

        .total-rp {
            width: 25px;
            text-align: right;
            font-weight: bold;
        }

        .total-value {
            width: 100px;
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }

    </style>
</head>
<body>
    
    {{-- Header --}}
    <table width="100%" cellspacing="0" cellpadding="8" style="border-collapse: collapse;">
        <tr>
            <!-- LOGO -->
             {{-- Logo --}}
<td width="20%" style="vertical-align: middle;">
    @if(!empty($payroll['company']['logo']))
        <img src="{{ $payroll['company']['logo'] }}" 
             style="max-height: 80px; max-width: 150px;" 
             alt="Company Logo">
    @else
        <div style="border: 1px solid #ccc; padding: 15px 10px; text-align: center; font-size: 8pt; color: #999;">
            Company Logo
        </div>
    @endif
</td>

            <!-- NAMA PERUSAHAAN -->
            <td width="60%" style="text-align:center; vertical-align: middle;">
                <strong style="font-size:16px;">
                    {{ strtoupper($payroll['company']['company_name'] ?? '-') }}
                </strong>
            </td>

            <!-- SLIP GAJI -->
            <td width="20%" style="text-align:right; vertical-align: middle;">
                <span style="
                    background:#e53935;
                    color:#fff;
                    padding:6px 12px;
                    font-weight:bold;
                    font-size:12px;
                    border-radius:3px;
                ">
                    SLIP GAJI
                </span>
            </td>
        </tr>
    </table>

    

    {{-- Separator --}}
    <table>
        <tr>
            <td class="separator-header"></td>
        </tr>
    </table>
    <br>

    {{-- Employee Information --}}
     <table style="font-size: 7.5pt;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <table>
                    <tr>
                        <td class="info-label">NIK</td>
                        <td>:{{ $payroll['karyawan']['nik'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Nama</td>
                        <td>: {{ strtoupper($payroll['karyawan']['nama_lengkap'] ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Position</td>
                        <td>: {{ $jabatanPerusahaan->nama_jabatan_terbaru ?? '-' }}</td>
                    </tr>
                   <tr>
                        <td class="info-label">Join Date</td>
                        <td>: {{ $karyawan->join_date ? \Carbon\Carbon::parse($karyawan->join_date)->format('Y-m-d') : '-' }}</td>
                    </tr>

                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <table>
                    <tr>
                        <td class="info-label">Area</td>
                        <td>: {{ $payroll['karyawan']['area'] ?? 'HO' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Dept</td>
                        <td>: {{ $payroll['karyawan']['department'] ?? 'OFFICE' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Month</td>
                        <td>: {{ strtoupper(\Carbon\Carbon::createFromFormat('Y-m', $payroll['periode'])->format('F Y')) }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">PTKP</td>
                        <td>: {{ $ptkp ? $ptkp->status : '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
     </table>
    

    <br>

    {{-- Separator --}}
    <table>
        <tr>
            <td class="separator-line"></td>
        </tr>
    </table>

    {{-- Income & Deduction Columns --}}
    <table>
        <tr>
            {{-- INCOME COLUMN --}}
            <td style="width: 50%; vertical-align: top;">
                <table>
                    <tr>
                        <td colspan="3" class="column-title">INCOME</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="text-end">&nbsp; </td>
                        <td class="text-end">&nbsp; </td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">Gaji Pokok</td>
                        <td style="text-align: right; width: 25px;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['gaji_pokok'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">Monthly KPI Bonus</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['monthly_kpi'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">Overtime</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['overtime'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">Tunjangan</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['tunjangan'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">Medical Reimbursement</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['medical_reimbursement'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr><td colspan="3" style="height: 5px;"></td></tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Tenaga Kerja ( Perusahaan )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['bpjs_tenaga_kerja_perusahaan_income'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Tenaga Kerja ( Pegawai )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['bpjs_tenaga_kerja_pegawai_income'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Kesehatan ( Perusahaan )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['bpjs_kesehatan_perusahaan_income'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Kesehatan ( Pegawai )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['bpjs_kesehatan_pegawai_income'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr>
                        <td colspan="3" class="section-title">Monthly Insentif</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Insentif Berjamaah</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['insentif_sholat'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Monthly Bonus</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ $payroll['monthly_bonus'] > 0 ? number_format($payroll['monthly_bonus'], 0, ',', '.') : '-' }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Rapel</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ $payroll['rapel'] > 0 ? number_format($payroll['rapel'], 0, ',', '.') : '-' }}</td>
                    </tr>
                    
                    <tr>
                        <td colspan="3" class="section-title">Monthly Allowance</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Tunjangan Pulsa</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['tunjangan_pulsa'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Tunjangan Kehadiran</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['tunjangan_kehadiran'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Tunjangan Transport</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['tunjangan_transport'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Tunjangan Lainnya</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ number_format($payroll['tunjangan_lainnya'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    
                    <tr>
                        <td colspan="3" class="section-title">Yearly Benefit</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Yearly Bonus</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ $payroll['yearly_bonus'] > 0 ? number_format($payroll['yearly_bonus'], 0, ',', '.') : '-' }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* THR</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ $payroll['thr'] > 0 ? number_format($payroll['thr'], 0, ',', '.') : '-' }}</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label sub-item">* Other</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">{{ $payroll['other'] > 0 ? number_format($payroll['other'], 0, ',', '.') : '-' }}</td>
                    </tr>
                </table>
            </td>
            
            {{-- DEDUCTION COLUMN --}}
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <table>
                    <tr>
                        <td colspan="3" class="column-title">DEDUCTION</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="text-end">&nbsp; </td>
                        <td class="text-end">&nbsp; </td>
                    </tr>
                    <tr class="line-item">
                        <td class="item-label">Pinjaman Corporate</td>
                        <td style="text-align: right; width: 25px;">Rp</td>
                        <td class="item-value">
                            ({{ $payroll['ca_corporate'] != 0 
                                ? number_format(abs($payroll['ca_corporate']), 0, ',', '.') 
                                : '0' }})
                        </td>
                    </tr>
                    <tr class="line-item">
                        <td class="item-label">Pinjaman Personal</td>
                        <td style="text-align: right; width: 25px;">Rp</td>
                        <td class="item-value">
                            ({{ $payroll['ca_personal'] != 0 
                                ? number_format(abs($payroll['ca_personal']), 0, ',', '.') 
                                : '0' }})
                        </td>
                    </tr>
                    <tr class="line-item">
                        <td class="item-label">Potongan / Absen / Over Cuti</td>
                        <td style="text-align: right; width: 25px;">Rp</td>
                        <td class="item-value">
                            ({{ $payroll['ca_kehadiran'] != 0 
                                ? number_format(abs($payroll['ca_kehadiran']), 0, ',', '.') 
                                : '0' }})
                        </td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">PPh 21</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">({{ number_format($payroll['pph_21_deduction'] ?? 0, 0, ',', '.') }})</td>
                    </tr>

                    <tr>
                        <td>&nbsp;</td>
                        <td class="text-end">&nbsp;</td>
                        <td class="text-end">&nbsp;</td>
                    </tr>
                    
                    <tr><td colspan="3" style="height: 5px;"></td></tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Tenaga Kerja ( Perusahaan )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">({{ number_format(abs($payroll['bpjs_tenaga_kerja_perusahaan_deduction'] ?? 0), 0, ',', '.') }})</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Tenaga Kerja ( Pegawai )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">({{ number_format(abs($payroll['bpjs_tenaga_kerja_pegawai_deduction'] ?? 0), 0, ',', '.') }})</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Kesehatan ( Perusahaan )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">({{ number_format(abs($payroll['bpjs_kesehatan_perusahaan_deduction'] ?? 0), 0, ',', '.') }})</td>
                    </tr>
                    
                    <tr class="line-item">
                        <td class="item-label">BPJS Kesehatan ( Pegawai )</td>
                        <td style="text-align: right;">Rp</td>
                        <td class="item-value">({{ number_format(abs($payroll['bpjs_kesehatan_pegawai_deduction'] ?? 0), 0, ',', '.') }})</td>
                    </tr>
                    
                </table>
            </td>
        </tr>
        <!-- TOTAL (SEJAJAR) -->
        <tr>
            <!-- TOTAL PENERIMAAN -->
            <td width="50%" style="padding-top:6px;">
                <table width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td colspan="3"
                            style="border-top:1px solid #000;"></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;">TOTAL PENERIMAAN</td>
                        <td style="width:25px; text-align:right; font-weight:bold;">Rp</td>
                        <td style="width:100px; text-align:right; font-weight:bold;">
                            {{ number_format($payroll['total_penerimaan'],0,',','.') }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3"
                            style="border-top:1px solid #000;"></td>
                    </tr>
                </table>
            </td>

            <!-- TOTAL POTONGAN -->
            <td width="50%" style="padding-top:6px;">
                <table width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td colspan="3"
                            style="border-top:1px solid #000;"></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;padding-left: 10px;">TOTAL POTONGAN</td>
                        <td style="width:25px; text-align:right; font-weight:bold;">Rp</td>
                        <td style="width:100px; text-align:right; font-weight:bold;">
                            ({{ number_format(abs($payroll['total_potongan']),0,',','.') }})
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3"
                            style="border-top:1px solid #000;"></td>
                    </tr>
                </table>
            </td>
        </tr>


    </table>

    <br>

    {{-- Take Home Pay --}}
    <table class="take-home-box" style="width: 50%;">
        <tr>
            <td style="text-align: left;">TAKE HOME PAY</td>
            <td style="text-align: right; width: 30px;">Rp</td>
            <td style="text-align: right;" class="take-home-value">{{ number_format($payroll['gaji_bersih'] ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>

    <br>

    {{-- Bank Transfer Info --}}
    <table>
        <tr>
            {{-- INCOME COLUMN --}}
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                 <table class="">
                        <tr>
                            <td colspan="2" class="bank-title">Ditransfer ke:</td>
                        </tr>
                        <tr>
                            <td style="width: 80px;">Nama</td>
                            <td>: {{ strtoupper($karyawan->nama_lengkap ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td>No. NPWP</td>
                            <td>: {{ $karyawan->no_npwp ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Bank</td>
                            <td>: {{ strtoupper($karyawan->nama_bank ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td>Account</td>
                            <td>: {{ $karyawan->no_rek ?? '-' }}</td>
                        </tr>
                    </table>
                     <table style="margin-top: 15px; border: 1px dashed #999; width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 7px 9px; font-size: 7.5pt; line-height: 1.5; font-style: italic;">
                                Medical reimbursement serta insentif berjamaah telah dilakukan pembayaran secara terpisah dan tidak termasuk dalam komponen transfer gaji.
                            </td>
                        </tr>
                    </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                {{-- Attendance Info --}}
                <table class="attendance-box">
                    <tr>
                        <td class="box-title" colspan="4">Keterangan : {{ strtoupper(\Carbon\Carbon::createFromFormat('Y-m', $payroll['periode'])->format('F Y')) }}</td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <table class="attendance-table">
                                <tr>
                                    <td style="width: 25px;">1.</td>
                                    <td style="text-align: left;">HDR</td>
                                    <td style="width: 30px;">0</td>
                                    <td style="width: 40px;">Hari</td>
                                </tr>
                                <tr>
                                    <td>2.</td>
                                    <td style="text-align: left;">SKT</td>
                                    <td>0</td>
                                    <td>Hari</td>
                                </tr>
                                <tr>
                                    <td>3.</td>
                                    <td style="text-align: left;">CTI</td>
                                    <td>0</td>
                                    <td>Hari</td>
                                </tr>
                                <tr>
                                    <td>4.</td>
                                    <td style="text-align: left;">UNP</td>
                                    <td>0</td>
                                    <td>Hari</td>
                                </tr>
                                <tr>
                                    <td>5.</td>
                                    <td style="text-align: left;">LTE</td>
                                    <td>0</td>
                                    <td>Hari</td>
                                </tr>
                                <tr>
                                    <td>6.</td>
                                    <td style="text-align: left;">IPC</td>
                                    <td>0</td>
                                    <td>Hari</td>
                                </tr>
                                <tr>
                                    <td>7.</td>
                                    <td style="text-align: left;">LPA</td>
                                    <td>0</td>
                                    <td>Hari</td>
                                </tr>
                                <tr>
                                    <td>8.</td>
                                    <td style="text-align: left;">ALP</td>
                                    <td>0</td>
                                    <td>Hari</td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
   

    <br>

     <table>
        <tr>
            {{-- INCOME COLUMN --}}
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                 
                {{-- Signature --}}
                <table class="signature-area">
                    <tr>
                        <td class="company-name-sign">{{ strtoupper($payroll['company']['company_name'] ?? '-') }}</td>
                    </tr>
                    <tr>
                        {{-- TTD --}}
<td style="text-align: center; padding: 10px 0;">
    @if(!empty($payroll['company']['ttd']))
        <img src="{{ $payroll['company']['ttd'] }}" style="width: 100px;">
    @else
        <div style="border: 1px solid #ccc; padding: 15px 10px; text-align: center; font-size: 8pt; color: #999;">
            TTD
        </div>
    @endif
</td>
                    </tr>
                    <tr>
                        <td class="employee-name-sign">{{ strtoupper($payroll['company']['nama_ttd'] ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="position-sign">( {{ strtoupper($payroll['company']['jabatan_ttd'] ?? '-') }} )</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                {{-- Keterangan --}}
                <table class="keterangan-box">
                    <tr>
                        <td class="ket-title" colspan="2">KETERANGAN</td>
                    </tr>
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            HDR : Hadir<br>
                            SKT : Sakit<br>
                            CTI : Cuti
                        </td>
                        <td style="width: 50%; vertical-align: top;">
                            UNP : Unpaid Leave<br>
                            LTE : Late<br>
                            IPC : Izin Pulang Cepat<br>
                            LPA : Lupa Checkout <br>
                            ALP : Alpha <!-- ✅ TAMBAH INI -->
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <br>
    <br>

    {{-- Footer --}}
    <table>
        <tr>
            <td class="footer-code">PAYROLL SLIP</td>
        </tr>
    </table>
    
</body>
</html>