<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reimbursement {{ $reimbursement->id_recapan ?? '-' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #1a1a1a;
            position: relative;
            min-height: 100vh;
        }

        /* Content wrapper with bottom padding for fixed signature */
        .content-wrapper {
            padding: 15px 15px 140px 15px;
        }

        /* Header Section */
        .header {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #0066cc;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 70px;
            vertical-align: middle;
        }

        .company-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 12px;
        }

        .company-logo {
            max-width: 65px;
            max-height: 65px;
        }

        .company-name {
            font-size: 13px;
            font-weight: 700;
            color: #0066cc;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }

        .doc-subtitle {
            font-size: 8px;
            color: #666;
            font-weight: 500;
        }

        .doc-title {
            font-size: 11px;
            font-weight: 700;
            color: #0066cc;
            text-align: center;
            margin: 10px 0 12px 0;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        /* Info Cards - Side by Side */
        .info-cards {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .info-card {
            display: table-cell;
            width: 50%;
            padding: 8px;
            background: #f8f9fc;
            border: 1px solid #e1e4e8;
            vertical-align: top;
        }

        .info-card:first-child {
            border-right: none;
        }

        .card-header {
            font-size: 9px;
            font-weight: 700;
            color: #0066cc;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #d1d5db;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row {
            margin-bottom: 3px;
            display: table;
            width: 100%;
        }

        .info-label {
            display: table-cell;
            width: 40%;
            font-size: 8px;
            color: #666;
            padding: 2px 0;
        }

        .info-value {
            display: table-cell;
            font-size: 8px;
            color: #1a1a1a;
            font-weight: 600;
            padding: 2px 0;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7px;
            font-weight: 700;
            border-radius: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-success {
            background: #10b981;
            color: white;
        }

        .badge-warning {
            background: #f59e0b;
            color: white;
        }

        /* Table Styles */
        .section {
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 9px;
            font-weight: 700;
            color: #0066cc;
            margin-bottom: 5px;
            padding: 4px 6px;
            background: #f0f4ff;
            border-left: 3px solid #0066cc;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table th {
            background: #0066cc;
            color: white;
            padding: 5px 6px;
            text-align: left;
            font-size: 8px;
            font-weight: 600;
            border: 1px solid #0052a3;
        }

        table td {
            padding: 4px 6px;
            border: 1px solid #e1e4e8;
            font-size: 8px;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fc;
        }

        table tbody tr:hover {
            background: #f0f4ff;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: 700;
        }

        .subtotal-row {
            background: #e5edff !important;
            font-weight: 700;
            color: #0066cc;
        }

        .total-row {
            background: #0066cc !important;
            color: white !important;
            font-weight: 700;
        }

        .total-row td {
            border-color: #0052a3 !important;
        }

        /* Balance Section - Bottom */
        .balance-section {
            margin-top: 12px;
            padding: 8px;
            background: #f8f9fc;
            border: 1px solid #e1e4e8;
        }

        .balance-title {
            font-size: 9px;
            font-weight: 700;
            color: #0066cc;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .balance-grid {
            display: table;
            width: 100%;
        }

        .balance-row {
            display: table-row;
        }

        .balance-cell {
            display: table-cell;
            width: 25%;
            padding: 6px 4px;
            text-align: center;
            border-right: 1px solid #d1d5db;
        }

        .balance-cell:last-child {
            border-right: none;
        }

        .balance-label {
            font-size: 7px;
            color: #666;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .balance-value {
            font-size: 9px;
            font-weight: 700;
            color: #0066cc;
        }

        /* Fixed Signature Section at Bottom */
        .signature-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 2px solid #0066cc;
            padding: 10px 15px 8px 15px;
            page-break-inside: avoid;
        }

        .signature-grid {
            display: table;
            width: 100%;
        }

        .signature-cell {
            display: table-cell;
            width: 50%;
            padding: 6px;
            vertical-align: top;
        }

        .signature-box {
            text-align: center;
            border: 1px solid #e1e4e8;
            padding: 8px;
            min-height: 90px;
            background: #f8f9fc;
        }

        .signature-title {
            font-size: 8px;
            font-weight: 700;
            margin-bottom: 35px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signature-name {
            font-size: 9px;
            font-weight: 700;
            border-top: 1px solid #1a1a1a;
            padding-top: 3px;
            display: inline-block;
            min-width: 120px;
            color: #1a1a1a;
        }

        .signature-position {
            font-size: 7px;
            color: #666;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 7px;
            color: #999;
            margin-top: 6px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Amount highlight */
        .amount-highlight {
            color: #0066cc;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <!-- HEADER -->
        <div class="header">
            <div class="header-content">
                @if(isset($company) && isset($company->logo) && $company->logo)
                <div class="logo-section">
                    <img src="{{ $company->logo }}" alt="Logo" class="company-logo">
                </div>
                @endif
                <div class="company-info">
                    <div class="company-name">{{ $company->company_name ?? '-' }}</div>
                    <div class="doc-subtitle">Medical Reimbursement Form</div>
                </div>
            </div>
        </div>

        <!-- DOCUMENT TITLE -->
        <div class="doc-title">Medical Reimbursement Form</div>

        <!-- INFO CARDS - SIDE BY SIDE -->
        <div class="info-cards">
            <!-- Reimbursement Info Card -->
            <div class="info-card">
                <div class="card-header">Reimbursement Details</div>
                <div class="info-row">
                    <div class="info-label">ID Recap</div>
                    <div class="info-value">{{ $reimbursement->id_recapan ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Period</div>
                    <div class="info-value">{{ isset($reimbursement->periode_slip) ? \Carbon\Carbon::parse($reimbursement->periode_slip . '-01')->format('F Y') : '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Budget Year</div>
                    <div class="info-value">{{ $reimbursement->year_budget ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        @if(isset($reimbursement->status) && $reimbursement->status)
                            <span class="badge badge-success">Approved</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </div>
                </div>
                @if(isset($reimbursement->approved_at) && $reimbursement->approved_at)
                <div class="info-row">
                    <div class="info-label">Approved Date</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($reimbursement->approved_at)->format('d M Y') }}</div>
                </div>
                @endif
            </div>

            <!-- Employee Info Card -->
            <div class="info-card">
                <div class="card-header">Employee Information</div>
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">{{ $karyawan->nama_lengkap ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">NIK</div>
                    <div class="info-value">{{ $karyawan->nik ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $karyawan->email_pribadi ?? '-' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $karyawan->telp_pribadi ?? '-' }}</div>
                </div>
            </div>
        </div>

        <!-- GENERAL MEDICAL ITEMS -->
        @if(isset($generalChilds) && $generalChilds->count() > 0)
        <div class="section">
            <div class="section-title">General Medical Expenses</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th style="width: 24%;">Medical Type</th>
                        <th style="width: 18%;">Disease Type</th>
                        <th style="width: 14%;">Family Status</th>
                        <th style="width: 26%;">Notes</th>
                        <th style="width: 14%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php $generalTotal = 0; @endphp
                    @foreach($generalChilds as $index => $child)
                    @php $generalTotal += $child->harga ?? 0; @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ isset($child->reimbursementType) ? ($child->reimbursementType->medical_type ?? '-') : '-' }}</td>
                        <td>{{ $child->jenis_penyakit ?? '-' }}</td>
                        <td>{{ $child->status_keluarga ?? '-' }}</td>
                        <td>{{ $child->note ?? '-' }}</td>
                        <td class="text-right amount-highlight">Rp {{ number_format($child->harga ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="5" class="text-right">Subtotal General:</td>
                        <td class="text-right">Rp {{ number_format($generalTotal, 0, ',', '.') }}</td>
                        <td colspan="6"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- OTHER MEDICAL ITEMS -->
        @if(isset($otherChilds) && $otherChilds->count() > 0)
        <div class="section">
            <div class="section-title">Other Medical Expenses</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th style="width: 24%;">Medical Type</th>
                        <th style="width: 18%;">Disease Type</th>
                        <th style="width: 14%;">Family Status</th>
                        <th style="width: 26%;">Notes</th>
                        <th style="width: 14%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php $otherTotal = 0; @endphp
                    @foreach($otherChilds as $index => $child)
                    @php $otherTotal += $child->harga ?? 0; @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ isset($child->reimbursementType) ? ($child->reimbursementType->medical_type ?? '-') : '-' }}</td>
                        <td>{{ $child->jenis_penyakit ?? '-' }}</td>
                        <td>{{ $child->status_keluarga ?? '-' }}</td>
                        <td>{{ $child->note ?? '-' }}</td>
                        <td class="text-right amount-highlight">Rp {{ number_format($child->harga ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="5" class="text-right">Subtotal Other:</td>
                        <td class="text-right">Rp {{ number_format($otherTotal, 0, ',', '.') }}</td>
                        <td colspan="6"></td>
                    </tr>
                    <br>
                    <br>
                    <tr class="total-row">
                        <td colspan="5" class="text-right" style="font-size: 10px;">TOTAL REIMBURSEMENT:</td>
                        <td class="text-right" style="font-size: 10px;">Rp {{ number_format($totalAmount ?? 0, 0, ',', '.') }}</td>
                        <td colspan="6"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif


        <!-- BALANCE INFORMATION (MOVED TO BOTTOM) -->
        @if(isset($balance) && $balance)
        <div class="balance-section">
            <div class="balance-title">Budget Balance - Year {{ $balance->year ?? '-' }}</div>
            <div class="balance-grid">
                <div class="balance-row">
                    <div class="balance-cell">
                        <div class="balance-label">Budget Claim</div>
                        <div class="balance-value">Rp {{ number_format($balance->budget_claim ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="balance-cell">
                        <div class="balance-label">Total Used</div>
                        <div class="balance-value">Rp {{ number_format($balance->total_used ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="balance-cell">
                        <div class="balance-label">This Claim</div>
                        <div class="balance-value">Rp {{ number_format($totalAmount ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="balance-cell">
                        <div class="balance-label">Remaining</div>
                        <div class="balance-value">Rp {{ number_format($balance->sisa_budget ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- FIXED SIGNATURE SECTION AT BOTTOM -->
    <div class="signature-section">
        <div class="signature-grid">
            <div class="signature-cell">
                <div class="signature-box">
                    <div class="signature-title">Prepared By</div>
                    <div style="margin-top: 35px;">
                        <div class="signature-name">{{ isset($preparedBy) ? ($preparedBy->name ?? '-') : '-' }}</div>
                        <div class="signature-position">Staff</div>
                    </div>
                </div>
            </div>
            <div class="signature-cell">
                <div class="signature-box">
                    <div class="signature-title">Approved By</div>
                    <div style="margin-top: 35px;">
                        <div class="signature-name">{{ isset($approver) ? ($approver->nama_lengkap ?? '-') : '-' }}</div>
                        <div class="signature-position">Manager</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer">
            <div>Generated on {{ $printDate ?? now()->format('d F Y H:i') }} | {{ $company->company_name ?? '-' }} - Medical Reimbursement System</div>
        </div>
    </div>
</body>
</html>