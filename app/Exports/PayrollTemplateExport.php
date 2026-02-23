<?php
// app/Exports/PayrollTemplateExport.php - REMOVED AUTO-CALCULATED COLUMNS

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayrollTemplateExport implements WithMultipleSheets
{
    protected $karyawans;
    protected $companies;
    
    public function __construct($karyawans = [], $companies = [])
    {
        $this->karyawans = $karyawans;
        $this->companies = $companies;
    }
    
    public function sheets(): array
    {
        return [
            new PayrollDataSheet(),
            new KaryawanReferenceSheet($this->karyawans),
            new CompanyReferenceSheet($this->companies),
        ];
    }
}

/**
 * SHEET 1: PAYROLL DATA
 * ✅ REMOVED: medical_reimbursement, pph_21, pph_21_deduction (auto-calculated)
 * Pattern: HIJAU (A-U) → MERAH (V-AC) → HIJAU (AD-AF)
 */
class PayrollDataSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function title(): string
    {
        return 'Payroll Data';
    }
    
    public function headings(): array
    {
        return [
            ['PAYROLL IMPORT TEMPLATE - FILL THIS SHEET ONLY'],
            ['⚠️ IMPORTANT: medical_reimbursement, pph_21, dan pph_21_deduction akan dihitung otomatis oleh sistem'],
            [],
            [
                'periode',                      // A - HIJAU
                'karyawan_id',                  // B - HIJAU
                'company_id',                   // C - HIJAU
                'salary_type',                  // D - HIJAU
                'gaji_pokok',                   // E - HIJAU
                'monthly_kpi',                  // F - HIJAU
                'overtime',                     // G - HIJAU
                // ❌ REMOVED: 'medical_reimbursement' (auto dari ReimbursementChildSum)
                'insentif_sholat',              // H - HIJAU
                'monthly_bonus',                // I - HIJAU
                'rapel',                        // J - HIJAU
                'tunjangan_pulsa',              // K - HIJAU
                'tunjangan_kehadiran',          // L - HIJAU
                'tunjangan_transport',          // M - HIJAU
                'tunjangan_lainnya',            // N - HIJAU
                'yearly_bonus',                 // O - HIJAU
                'thr',                          // P - HIJAU
                'other',                        // Q - HIJAU
                'ca_corporate',                 // R - HIJAU
                'ca_personal',                  // S - HIJAU
                'ca_kehadiran',                 // T - HIJAU
                'bpjs_tenaga_kerja',            // U - HIJAU
                'bpjs_kesehatan',               // V - MERAH
                // ❌ REMOVED: 'pph_21_deduction' (auto dari PPh21 calculator)
                'bpjs_tk_jht_3_7_percent',      // W - MERAH
                'bpjs_tk_jht_2_percent',        // X - MERAH
                'bpjs_tk_jkk_0_24_percent',     // Y - MERAH
                'bpjs_tk_jkm_0_3_percent',      // Z - MERAH
                'bpjs_tk_jp_2_percent',         // AA - MERAH
                'bpjs_tk_jp_1_percent',         // AB - MERAH
                'bpjs_kes_4_percent',           // AC - MERAH
                'bpjs_kes_1_percent',           // AD - MERAH
                // ❌ REMOVED: 'pph_21' (auto dari PPh21 calculator)
                'glh',                          // AE - HIJAU
                'lm',                           // AF - HIJAU
                'lainnya'                       // AG - HIJAU
            ]
        ];
    }
    
    public function array(): array
    {
        return [
            [], [], [],
            // Sample data (sesuaikan dengan kolom baru)
            [
                '2025-01',      // periode
                1,              // karyawan_id
                1,              // company_id
                'gross',        // salary_type
                5000000,        // gaji_pokok
                500000,         // monthly_kpi
                200000,         // overtime
                // medical_reimbursement REMOVED
                50000,          // insentif_sholat
                0,              // monthly_bonus
                0,              // rapel
                100000,         // tunjangan_pulsa
                200000,         // tunjangan_kehadiran
                150000,         // tunjangan_transport
                0,              // tunjangan_lainnya
                0,              // yearly_bonus
                0,              // thr
                0,              // other
                0,              // ca_corporate
                0,              // ca_personal
                0,              // ca_kehadiran
                0,              // bpjs_tenaga_kerja
                0,              // bpjs_kesehatan
                // pph_21_deduction REMOVED
                185000,         // bpjs_tk_jht_3_7_percent
                100000,         // bpjs_tk_jht_2_percent
                12000,          // bpjs_tk_jkk_0_24_percent
                15000,          // bpjs_tk_jkm_0_3_percent
                100000,         // bpjs_tk_jp_2_percent
                50000,          // bpjs_tk_jp_1_percent
                200000,         // bpjs_kes_4_percent
                50000,          // bpjs_kes_1_percent
                // pph_21 REMOVED
                0,              // glh
                0,              // lm
                0               // lainnya
            ]
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        // Title
        $sheet->mergeCells('A1:AG1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF5722']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Warning
        $sheet->mergeCells('A2:AG2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->getRowDimension(2)->setRowHeight(25);
        
        // ✅ HIJAU PERTAMA (A-U: 21 kolom)
        $greenCols1 = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V'];
        foreach ($greenCols1 as $col) {
            $sheet->getStyle($col.'4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }
        
        // ✅ MERAH (V-AD: 9 kolom BPJS)
        $redCols = ['W','X','Y','Z','AA','AB','AC','AD'];
        foreach ($redCols as $col) {
            $sheet->getStyle($col.'4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F44336']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }
        
        // ✅ HIJAU KEDUA (AE-AG: 3 kolom terakhir)
        $greenCols2 = ['AE','AF','AG'];
        foreach ($greenCols2 as $col) {
            $sheet->getStyle($col.'4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }
        
        return $sheet;
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // periode
            'B' => 15,  // karyawan_id
            'C' => 15,  // company_id
            'D' => 15,  // salary_type
            'E' => 15,  // gaji_pokok
            'F' => 15,  // monthly_kpi
            'G' => 15,  // overtime
            'H' => 15,  // insentif_sholat
            'I' => 15,  // monthly_bonus
            'J' => 12,  // rapel
            'K' => 15,  // tunjangan_pulsa
            'L' => 18,  // tunjangan_kehadiran
            'M' => 18,  // tunjangan_transport
            'N' => 18,  // tunjangan_lainnya
            'O' => 15,  // yearly_bonus
            'P' => 12,  // thr
            'Q' => 12,  // other
            'R' => 15,  // ca_corporate
            'S' => 15,  // ca_personal
            'T' => 15,  // ca_kehadiran
            'U' => 20,  // bpjs_tenaga_kerja
            'V' => 18,  // bpjs_kesehatan
            'W' => 22,  // bpjs_tk_jht_3_7_percent
            'X' => 20,  // bpjs_tk_jht_2_percent
            'Y' => 23,  // bpjs_tk_jkk_0_24_percent
            'Z' => 22,  // bpjs_tk_jkm_0_3_percent
            'AA' => 20, // bpjs_tk_jp_2_percent
            'AB' => 20, // bpjs_tk_jp_1_percent
            'AC' => 18, // bpjs_kes_4_percent
            'AD' => 18, // bpjs_kes_1_percent
            'AE' => 18, // glh
            'AF' => 18, // lm
            'AG' => 18  // lainnya
        ];
    }
}

/**
 * SHEET 2: KARYAWAN REFERENCE (NO CHANGES)
 */
class KaryawanReferenceSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $karyawans;
    
    public function __construct($karyawans)
    {
        $this->karyawans = $karyawans;
    }
    
    public function title(): string
    {
        return 'Karyawans';
    }
    
    public function headings(): array
    {
        return [
            ['KARYAWAN REFERENCE - READ ONLY'],
            ['✅ Use "Karyawan ID" column for karyawan_id in Payroll Data sheet'],
            [],
            ['Karyawan ID', 'NIK', 'Nama Lengkap', 'Email', 'Telepon', 'Join Date', 'Status']
        ];
    }
    
    public function array(): array
    {
        $data = [[], [], []];
        
        foreach ($this->karyawans as $k) {
            $data[] = [
                $k['absen_karyawan_id'] ?? $k['id'] ?? '-',
                $k['nik'] ?? '-',
                $k['nama_lengkap'] ?? 'Unknown',
                $k['email_pribadi'] ?? '-',
                $k['telp_pribadi'] ?? '-',
                $k['join_date'] ?? '-',
                ($k['status_resign'] ?? false) ? 'Resign' : 'Active'
            ];
        }
        
        return $data;
    }
    
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '43A047']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('karyawan123');
        
        return $sheet;
    }
    
    public function columnWidths(): array
    {
        return ['A' => 15, 'B' => 15, 'C' => 30, 'D' => 25, 'E' => 15, 'F' => 15, 'G' => 10];
    }
}

/**
 * SHEET 3: COMPANY REFERENCE (NO CHANGES)
 */
class CompanyReferenceSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $companies;
    
    public function __construct($companies)
    {
        $this->companies = $companies;
    }
    
    public function title(): string
    {
        return 'Companies';
    }
    
    public function headings(): array
    {
        return [
            ['COMPANY REFERENCE - READ ONLY'],
            ['✅ Use "Company ID" column for company_id in Payroll Data sheet'],
            [],
            ['Company ID', 'Code', 'Company Name', 'Created At']
        ];
    }
    
    public function array(): array
    {
        $data = [[], [], []];
        
        foreach ($this->companies as $c) {
            $data[] = [
                $c['absen_company_id'] ?? $c['id'] ?? '-',
                $c['code'] ?? '-',
                $c['company_name'] ?? 'Unknown',
                isset($c['created_at']) ? date('Y-m-d', strtotime($c['created_at'])) : '-'
            ];
        }
        
        return $data;
    }
    
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BBDEFB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $sheet->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('company123');
        
        return $sheet;
    }
    
    public function columnWidths(): array
    {
        return ['A' => 15, 'B' => 15, 'C' => 40, 'D' => 20];
    }
}