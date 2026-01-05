<?php

namespace App\Exports;

use App\Models\PayrollCalculation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PayrollsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles
{
    protected $periode;
    protected $companyId;
    protected $isReleased;

    public function __construct($periode = null, $companyId = null, $isReleased = null)
    {
        $this->periode = $periode;
        $this->companyId = $companyId;
        $this->isReleased = $isReleased;
    }

    public function collection()
    {
        $query = PayrollCalculation::with(['karyawan:absen_karyawan_id,nik,nama_lengkap', 'company:absen_company_id,company_name']);

        // Filter berdasarkan parameter
        if ($this->periode) {
            $query->where('periode', $this->periode);
        }

        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        if ($this->isReleased !== null) {
            $query->where('is_released', $this->isReleased);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Periode',
            'NIK',
            'Nama Karyawan',
            'Company',
            'Gaji Pokok',
            'Salary Type',
            // Monthly Insentif
            'Monthly KPI',
            'Overtime',
            'Medical Reimbursement',
            'Insentif Sholat',
            'Monthly Bonus',
            'Rapel',
            // Monthly Allowance
            'Tunjangan Pulsa',
            'Tunjangan Kehadiran',
            'Tunjangan Transport',
            'Tunjangan Lainnya',
            // Yearly Benefit
            'Yearly Bonus',
            'THR',
            'Other',
            // Potongan
            'CA Corporate',
            'CA Personal',
            'CA Kehadiran',
            'PPh 21',
            'PPh 21 Deduction',
            // BPJS TK
            'BPJS Tenaga Kerja',
            'BPJS TK JHT 3.7%',
            'BPJS TK JHT 2%',
            'BPJS TK JKK 0.24%',
            'BPJS TK JKM 0.3%',
            'BPJS TK JP 2%',
            'BPJS TK JP 1%',
            // BPJS KES
            'BPJS Kesehatan',
            'BPJS Kes 4%',
            'BPJS Kes 1%',
            // Lainnya
            'GLH',
            'LM',
            'Lainnya',
            // Summary
            'Salary',
            'Total Penerimaan',
            'Total Potongan',
            'Gaji Bersih',
            'Status'
        ];
    }

    public function map($payroll): array
    {
        return [
            $payroll->periode,
            $payroll->karyawan?->nik ?? '-',
            $payroll->karyawan?->nama_lengkap ?? '-',
            $payroll->company?->company_name ?? '-',
            $payroll->gaji_pokok ?? 0,
            $payroll->salary_type ?? '-',
            $payroll->monthly_kpi ?? 0,
            $payroll->overtime ?? 0,
            $payroll->medical_reimbursement ?? 0,
            $payroll->insentif_sholat ?? 0,
            $payroll->monthly_bonus ?? 0,
            $payroll->rapel ?? 0,
            $payroll->tunjangan_pulsa ?? 0,
            $payroll->tunjangan_kehadiran ?? 0,
            $payroll->tunjangan_transport ?? 0,
            $payroll->tunjangan_lainnya ?? 0,
            $payroll->yearly_bonus ?? 0,
            $payroll->thr ?? 0,
            $payroll->other ?? 0,
            $payroll->ca_corporate ?? 0,
            $payroll->ca_personal ?? 0,
            $payroll->ca_kehadiran ?? 0,
            $payroll->pph_21 ?? 0,
            $payroll->pph_21_deduction ?? 0,
            $payroll->bpjs_tenaga_kerja ?? 0,
            $payroll->bpjs_tk_jht_3_7_percent ?? 0,
            $payroll->bpjs_tk_jht_2_percent ?? 0,
            $payroll->bpjs_tk_jkk_0_24_percent ?? 0,
            $payroll->bpjs_tk_jkm_0_3_percent ?? 0,
            $payroll->bpjs_tk_jp_2_percent ?? 0,
            $payroll->bpjs_tk_jp_1_percent ?? 0,
            $payroll->bpjs_kesehatan ?? 0,
            $payroll->bpjs_kes_4_percent ?? 0,
            $payroll->bpjs_kes_1_percent ?? 0,
            $payroll->glh ?? 0,
            $payroll->lm ?? 0,
            $payroll->lainnya ?? 0,
            $payroll->salary ?? 0,
            $payroll->total_penerimaan ?? 0,
            abs($payroll->total_potongan ?? 0),
            $payroll->gaji_bersih ?? 0,
            $payroll->is_released ? 'Released' : 'Pending'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Gaji Pokok
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Monthly KPI
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Overtime
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Medical
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Insentif Sholat
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Monthly Bonus
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Rapel
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Tunj Pulsa
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Tunj Kehadiran
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Tunj Transport
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Tunj Lainnya
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Yearly Bonus
            'R' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // THR
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Other
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // CA Corporate
            'U' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // CA Personal
            'V' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // CA Kehadiran
            'W' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // PPh 21
            'X' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // PPh 21 Deduction
            'Y' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK
            'Z' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK JHT 3.7%
            'AA' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK JHT 2%
            'AB' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK JKK
            'AC' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK JKM
            'AD' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK JP 2%
            'AE' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK JP 1%
            'AF' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS Kes
            'AG' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS Kes 4%
            'AH' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS Kes 1%
            'AI' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // GLH
            'AJ' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // LM
            'AK' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Lainnya
            'AL' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Salary
            'AM' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Penerimaan
            'AN' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Potongan
            'AO' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Gaji Bersih
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}