<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reimbursement {{ $reimbursement->id_recapan ?? '-' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            line-height: 1.5;
            color: #1e293b;
            background: #fff;
        }

        /* ── TOP ACCENT ── */
        .top-bar {
            height: 4px;
            background: #64748b;
            width: 100%;
        }

        /* ── HEADER ── */
        .header {
            display: table;
            width: 100%;
            padding: 12px 22px 11px 22px;
            border-bottom: 1px solid #e2e8f0;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 55%;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 45%;
        }

        .company-logo {
            max-height: 32px;
            max-width: 95px;
            display: block;
            margin-bottom: 4px;
        }

        .company-name {
            font-size: 10.5px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.2px;
        }

        .company-sub {
            font-size: 7px;
            color: #94a3b8;
            margin-top: 1px;
        }

        /* Document title block (right side) */
        .doc-title-wrap {
            display: inline-block;
            text-align: right;
        }

        .doc-main-title {
            font-size: 10px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.2;
        }

        .doc-main-subtitle {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 400;
        }

        .doc-id-row {
            margin-top: 5px;
        }

        .doc-label {
            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            font-weight: 600;
        }

        .doc-id {
            font-size: 8.5px;
            font-weight: 700;
            color: #475569;
        }

        /* ── BODY ── */
        .body-wrap {
            padding: 14px 22px 170px 22px;
        }

        /* ── INFO BAND ── */
        .info-band {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .band-header-row {
            display: table;
            width: 100%;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .band-header-cell {
            display: table-cell;
            width: 50%;
            padding: 5px 11px;
            border-right: 1px solid #e2e8f0;
        }

        .band-header-cell:last-child { border-right: none; }

        .band-title {
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
        }

        .band-body-row {
            display: table;
            width: 100%;
        }

        .band-col {
            display: table-cell;
            width: 50%;
            padding: 9px 11px;
            vertical-align: top;
            border-right: 1px solid #e2e8f0;
        }

        .band-col:last-child { border-right: none; }

        .field-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .field-row:last-child { margin-bottom: 0; }

        .field-label {
            display: table-cell;
            width: 38%;
            font-size: 7.5px;
            color: #94a3b8;
            font-weight: 400;
            vertical-align: top;
        }

        .field-sep {
            display: table-cell;
            width: 10px;
            font-size: 7.5px;
            color: #cbd5e1;
            vertical-align: top;
            text-align: center;
        }

        .field-value {
            display: table-cell;
            font-size: 8px;
            color: #1e293b;
            font-weight: 600;
            vertical-align: top;
        }

        .badge {
            display: inline-block;
            padding: 1.5px 7px;
            font-size: 6.5px;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending  { background: #fef9c3; color: #a16207; }

        /* ── SECTION LABEL ── */
        .section-label {
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            margin-bottom: 5px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* ── TABLE ── */
        .table-wrap {
            border: 1px solid #e2e8f0;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* rowspan header */
        table thead tr.th-top th,
        table thead tr.th-sub th {
            padding: 4px 5px;
            text-align: left;
            font-size: 7px;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.2px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        table thead tr.th-top th:last-child,
        table thead tr.th-sub th:last-child {
            border-right: none;
        }

        table thead tr.th-sub th {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .th-group {
            text-align: center !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        table th.ar { text-align: right; }
        table th.ac { text-align: center; }

        table td {
            padding: 4px 5px;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            font-size: 7.5px;
            color: #334155;
            vertical-align: middle;
        }

        table td:last-child { border-right: none; }
        table tbody tr:last-child td { border-bottom: none; }
        table tbody tr:nth-child(even) td { background: #fafafa; }

        .ar  { text-align: right; }
        .ac  { text-align: center; }
        .fw  { font-weight: 700; }
        .tm  { color: #94a3b8; }
        .num { font-variant-numeric: tabular-nums; }

        /* total row */
        .tr-total td {
            background: #475569 !important;
            color: #f8fafc !important;
            font-weight: 700;
            font-size: 7.5px;
            border: none !important;
            padding: 5px 5px;
        }

        /* ── SUMMARY ── */
        .summary {
            display: table;
            width: 100%;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 5px 5px;
            overflow: hidden;
        }

        .summary-cell {
            display: table-cell;
            padding: 6px 10px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .summary-cell:last-child { border-right: none; }

        .summary-label {
            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 1px;
        }

        .summary-value {
            font-size: 9px;
            font-weight: 700;
            color: #1e293b;
            font-variant-numeric: tabular-nums;
        }

        /* ── NOTES ── */
        .notes-wrap {
            margin-top: 10px;
            width: 50%;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .notes-table {
            width: 100%;
            border-collapse: collapse;
        }

        .notes-table thead tr th {
            background: #f8fafc;
            padding: 4px 8px;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .notes-table tbody td {
            padding: 3px 8px;
            font-size: 7.5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            color: #475569;
        }

        .notes-table tbody tr:last-child td { border-bottom: none; }

        .notes-table tbody td.note-term {
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            width: 1%;
        }

        .notes-table tbody td.note-sep {
            color: #cbd5e1;
            width: 10px;
            text-align: center;
            padding-left: 4px;
            padding-right: 4px;
        }

        /* ── SIGNATURE ── */
        .sig-wrap {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            padding: 40px 22px 12px 22px;
        }

        .sig-grid {
            display: table;
            width: 100%;
            margin-bottom: 7px;
        }

        .sig-col {
            display: table-cell;
            width: 50%;
            padding-right: 18px;
            vertical-align: top;
        }

        .sig-col:last-child {
            padding-right: 0;
            padding-left: 18px;
            border-left: 1px solid #e2e8f0;
        }

        .sig-role {
            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            font-weight: 600;
            margin-bottom: 44px;
        }

        .sig-line {
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }

        .sig-name {
            font-size: 8px;
            font-weight: 700;
            color: #1e293b;
        }

        .sig-pos {
            font-size: 7px;
            color: #94a3b8;
            margin-top: 1px;
        }

        .sig-footer {
            text-align: center;
            font-size: 6.5px;
            color: #cbd5e1;
            margin-top: 5px;
        }

        /* ── BOTTOM ACCENT ── */
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #64748b;
        }
    </style>
</head>
<body>

<div class="top-bar"></div>

{{-- ── HEADER ── --}}
<div class="header">
    <div class="header-left">
        @if(isset($company) && isset($company->logo) && $company->logo)
            <img src="{{ $company->logo }}" class="company-logo" alt="Logo">
        @endif
        <div class="company-name">{{ $company->company_name ?? '-' }}</div>
        <div class="company-sub">Human Resources Department</div>
    </div>
    <div class="header-right">
        <div class="doc-title-wrap">
            <div class="doc-main-title">Medical Reimbursement</div>
            <div class="doc-main-subtitle">Approval Document</div>
            <div class="doc-id-row">
                <span class="doc-label">No. &nbsp;</span>
                <span class="doc-id">{{ $reimbursement->id_recapan ?? '-' }}</span>
            </div>
             <div class="doc-id-row" style="margin-top: 3px;">
                <span class="doc-label">Dibuat Tanggal &nbsp;</span>
                <span class="doc-id">
                    {{ isset($reimbursement->created_at)
                        ? \Carbon\Carbon::parse($reimbursement->created_at)->setTimezone('Asia/Jakarta')->translatedFormat('d F Y')
                        : '-' }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- ── BODY ── --}}
<div class="body-wrap">

    {{-- ── INFO BAND ── --}}
    <div class="info-band">
        <div class="band-header-row">
            <div class="band-header-cell">
                <div class="band-title">Employee Information</div>
            </div>
            <div class="band-header-cell">
                <div class="band-title">Reimbursement Details</div>
            </div>
        </div>
        <div class="band-body-row">
            <div class="band-col">
                <div class="field-row">
                    <div class="field-label">Full Name</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">{{ $karyawan->nama_lengkap ?? '-' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">NIK</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">{{ $karyawan->nik ?? '-' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Email</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">{{ $karyawan->email_pribadi ?? '-' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Phone</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">{{ $karyawan->telp_pribadi ?? '-' }}</div>
                </div>
            </div>
            <div class="band-col">
                <div class="field-row">
                    <div class="field-label">ID Recap</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">{{ $reimbursement->id_recapan ?? '-' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Period</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">
                        {{ isset($reimbursement->periode_slip)
                            ? \Carbon\Carbon::parse($reimbursement->periode_slip . '-01')->format('F Y')
                            : '-' }}
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Budget Year</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">{{ $reimbursement->year_budget ?? '-' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Status</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">
                        @if(isset($reimbursement->status) && $reimbursement->status)
                            <span class="badge badge-approved">Approved</span>
                        @else
                            <span class="badge badge-pending">Pending</span>
                        @endif
                    </div>
                </div>
                @if(isset($reimbursement->approved_at) && $reimbursement->approved_at)
                <div class="field-row">
                    <div class="field-label">Approved Date</div>
                    <div class="field-sep">:</div>
                    <div class="field-value">
                        {{ \Carbon\Carbon::parse($reimbursement->approved_at)->format('d M Y') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── ITEMS TABLE ── --}}
    @if(isset($childs) && $childs->count() > 0)
    @php $grandTotal = 0; @endphp

    <div class="section-label">Detail Reimbursement Items</div>

    <div class="table-wrap">
        <table>
            <thead>
                {{-- Row 1: base columns + "Keterangan" group --}}
                <tr class="th-top">
                    <th rowspan="2" class="ac" style="width: 3%; vertical-align: middle;">#</th>
                    <th rowspan="2" style="width: 8%; vertical-align: middle;">Tanggal</th>
                    <th rowspan="2" style="width: 15%; vertical-align: middle;">Reimburse Untuk</th>
                    <th rowspan="2" style="width: 12%; vertical-align: middle;">Hubungan dengan Karyawan</th>
                    <th rowspan="2" style="width: 13%; vertical-align: middle;">Analisa Penyakit</th>
                    <th colspan="4" class="th-group" style="width: 37%;">Keterangan</th>
                    <th rowspan="2" class="ar" style="width: 12%; vertical-align: middle;">TOTAL</th>
                </tr>
                {{-- Row 2: sub-columns under Keterangan --}}
                <tr class="th-sub">
                    <th class="ar" style="width: 10%;">Tagihan Dokter</th>
                    <th class="ar" style="width: 9%;">Tagihan Obat</th>
                    <th class="ar" style="width: 10%;">Tagihan Kacamata</th>
                    <th class="ar" style="width: 8%;">Tagihan Gigi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($childs as $index => $child)
                    @php
                        $dokter   = $child->tagihan_dokter   ?? 0;
                        $obat     = $child->tagihan_obat     ?? 0;
                        $kacamata = $child->tagihan_kacamata ?? 0;
                        $gigi     = $child->tagihan_gigi     ?? 0;
                        $subtotal = $dokter + $obat + $kacamata + $gigi;
                        $grandTotal += $subtotal;
                    @endphp
                    <tr>
                        <td class="ac tm">{{ $index + 1 }}</td>
                        <td>{{ $child->tanggal ? \Carbon\Carbon::parse($child->tanggal)->format('d/m/Y') : '-' }}</td>
                        <td class="fw">{{ $child->nama_reimbursement ?? '-' }}</td>
                        <td class="tm">{{ $child->status_keluarga ?? '-' }}</td>
                        <td>{{ $child->jenis_penyakit ?? '-' }}</td>
                        <td class="ar num">@if($dokter > 0){{ number_format($dokter, 0, ',', '.') }}@else<span class="tm">—</span>@endif</td>
                        <td class="ar num">@if($obat > 0){{ number_format($obat, 0, ',', '.') }}@else<span class="tm">—</span>@endif</td>
                        <td class="ar num">@if($kacamata > 0){{ number_format($kacamata, 0, ',', '.') }}@else<span class="tm">—</span>@endif</td>
                        <td class="ar num">@if($gigi > 0){{ number_format($gigi, 0, ',', '.') }}@else<span class="tm">—</span>@endif</td>
                        <td class="ar num fw">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tr-total">
                    <td colspan="9" class="ar">Total Reimbursement</td>
                    <td class="ar num">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── SUMMARY ── --}}
    <div class="summary">
        <div class="summary-cell" style="width: 50%;">
            <div class="summary-label">Total Items</div>
            <div class="summary-value">{{ $childs->count() }} item</div>
        </div>
        <div class="summary-cell" style="width: 50%;">
            <div class="summary-label">Total Klaim</div>
            <div class="summary-value">Rp {{ number_format($totalAmount ?? $grandTotal, 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- ── NOTES ── --}}
    <div class="notes-wrap">
        <table class="notes-table">
            <thead>
                <tr><th colspan="3">Notes</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td class="note-term">Reimburse Untuk</td>
                    <td class="note-sep">:</td>
                    <td>Nama yang berobat</td>
                </tr>
                <tr>
                    <td class="note-term">Hubungan dengan Karyawan</td>
                    <td class="note-sep">:</td>
                    <td>Hubungan dengan karyawan</td>
                </tr>
                <tr>
                    <td class="note-term">Analisa Penyakit</td>
                    <td class="note-sep">:</td>
                    <td>Analisis penyakit yang diderita</td>
                </tr>
                <tr>
                    <td class="note-term">Tagihan Dokter</td>
                    <td class="note-sep">:</td>
                    <td>Keterangan biaya tindakan / penanganan dokter</td>
                </tr>
                <tr>
                    <td class="note-term">Tagihan Obat</td>
                    <td class="note-sep">:</td>
                    <td>Keterangan biaya jenis obat</td>
                </tr>
            </tbody>
        </table>
    </div>

    @endif

</div>

{{-- ── SIGNATURE ── --}}
<div class="sig-wrap">
    <div class="sig-grid">
        <div class="sig-col">
            <div class="sig-role">Prepared By</div>
            <div class="sig-line">
                <div class="sig-name">{{ isset($preparedBy) ? ($preparedBy->name ?? '-') : '-' }}</div>
                <div class="sig-pos">Staff &nbsp;·&nbsp; {{ $printDate ?? now()->format('d F Y') }}</div>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-role">Approved By</div>
            <div class="sig-line">
                <div class="sig-name">{{ isset($approver) ? ($approver->nama_lengkap ?? '-') : '-' }}</div>
                <div class="sig-pos">{{ $company->jabatan_ttd ?? 'Manager' }}</div>
            </div>
        </div>
    </div>
    <div class="sig-footer">
        Generated {{ $printDate ?? now()->format('d F Y, H:i') }}
        &nbsp;·&nbsp; {{ $company->company_name ?? '-' }}
        &nbsp;·&nbsp; Medical Reimbursement System
    </div>
</div>

<div class="bottom-bar"></div>

</body>
</html>