<?php
// app/Exports/PayrollFakeErrorsExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayrollFakeErrorsExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $errors;
    
    public function __construct($errors)
    {
        $this->errors = $errors;
    }
    
    public function headings(): array
    {
        return [
            ['PAYROLL FAKE IMPORT - ERROR REPORT'],
            ['Generated: ' . date('Y-m-d H:i:s')],
            [],
            ['Row', 'Periode', 'Karyawan ID', 'Company ID', 'Error Messages']
        ];
    }
    
    public function array(): array
    {
        $data = [[], [], []];
        
        foreach ($this->errors as $error) {
            $data[] = [
                $error['row'],
                $error['data']['periode'] ?? '-',
                $error['data']['karyawan_id'] ?? '-',
                $error['data']['company_id'] ?? '-',
                implode(' | ', $error['errors'])
            ];
        }
        
        return $data;
    }
    
    public function styles(Worksheet $sheet)
    {
        // Title
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F44336']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Timestamp
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Headers
        $sheet->getStyle('A4:E4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E57373']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);
        
        // Data rows borders
        $lastRow = count($this->errors) + 4;
        $sheet->getStyle('A4:E' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);
        
        return $sheet;
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 60
        ];
    }
}