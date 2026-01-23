<?php
// app/Exports/PayrollFakeTemplateExport.php

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

class PayrollFakeTemplateExport implements WithMultipleSheets
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
            new PayrollFakeDataSheet(),
            new KaryawanFakeReferenceSheet($this->karyawans),
            new CompanyFakeReferenceSheet($this->companies),
        ];
    }
}

/**
 * SHEET 1: PAYROLL FAKE DATA
 */
class PayrollFakeDataSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function title(): string
    {
        return 'Payroll Data';
    }
    
    public function headings(): array
    {
        return [
            ['PAYROLL FAKE IMPORT TEMPLATE - FILL THIS SHEET ONLY'],
            ['⚠️ IMPORTANT: Semua kolom diisi MANUAL dari Excel (tidak ada auto-calculate PPh21)'],
            [],
            [
                'periode',
                'karyawan_id',
                'company_id',
                'salary_type',
                'gaji_pokok',
                'monthly_kpi',
                'overtime',
                'medical_reimbursement',
                'insentif_sholat',
                'monthly_bonus',
                'rapel',
                'tunjangan_pulsa',
                'tunjangan_kehadiran',
                'tunjangan_transport',
                'tunjangan_lainnya',
                'yearly_bonus',
                'thr',
                'other',
                'ca_corporate',
                'ca_personal',
                'ca_kehadiran',
                'pph_21',
                'bpjs_tenaga_kerja',
                'bpjs_kesehatan',
                'pph_21_deduction',
                'bpjs_tk_jht_3_7_percent',
                'bpjs_tk_jht_2_percent',
                'bpjs_tk_jkk_0_24_percent',
                'bpjs_tk_jkm_0_3_percent',
                'bpjs_tk_jp_2_percent',
                'bpjs_tk_jp_1_percent',
                'bpjs_kes_4_percent',
                'bpjs_kes_1_percent',
                'glh',
                'lm',
                'lainnya'
            ]
        ];
    }
    
    public function array(): array
    {
        return [
            [], [], [],
            // Sample data
            [
                '2025-01', 1, 1, 'gross', 5000000,
                500000, 200000, 100000, 50000, 0, 0,
                100000, 200000, 150000, 0, 0, 0, 0,
                0, 0, 0, 250000,
                0, 0, 0,
                185000, 100000, 12000, 15000, 100000, 50000, 200000, 50000,
                0, 0, 0
            ]
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        // Title
        $sheet->mergeCells('A1:AJ1');
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
        $sheet->mergeCells('A2:AJ2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->getRowDimension(2)->setRowHeight(25);
        
        // Headers - ALL GREEN (karena semua manual)
        $allCols = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ'];
        foreach ($allCols as $col) {
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
            'A' => 12, 'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15,
            'F' => 15, 'G' => 15, 'H' => 20, 'I' => 15, 'J' => 15,
            'K' => 12, 'L' => 15, 'M' => 18, 'N' => 18, 'O' => 18,
            'P' => 15, 'Q' => 12, 'R' => 12, 'S' => 15, 'T' => 15,
            'U' => 15, 'V' => 15, 'W' => 20, 'X' => 18, 'Y' => 18,
            'Z' => 22, 'AA' => 20, 'AB' => 23, 'AC' => 22,
            'AD' => 20, 'AE' => 20, 'AF' => 18, 'AG' => 18,
            'AH' => 12, 'AI' => 18, 'AJ' => 18
        ];
    }
}

/**
 * SHEET 2: KARYAWAN REFERENCE
 */
class KaryawanFakeReferenceSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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
 * SHEET 3: COMPANY REFERENCE
 */
class CompanyFakeReferenceSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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