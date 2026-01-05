<?php
// app/Exports/PayrollErrorsExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayrollErrorsExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $errors;
    
    public function __construct($errors)
    {
        $this->errors = $errors;
    }
    
    public function headings(): array
    {
        return [
            ['PAYROLL IMPORT ERROR REPORT'],
            ['Generated at: ' . now()->format('Y-m-d H:i:s')],
            [],
            ['Row Number', 'Periode', 'Karyawan ID', 'Company ID', 'Error Messages']
        ];
    }
    
    public function array(): array
    {
        $data = [[], [], []]; // Empty rows for title and info
        
        foreach ($this->errors as $error) {
            $errorMessages = implode('; ', $error['errors']);
            
            $data[] = [
                $error['row'],
                $error['data']['periode'] ?? '-',
                $error['data']['karyawan_id'] ?? '-',
                $error['data']['company_id'] ?? '-',
                $errorMessages
            ];
        }
        
        return $data;
    }
    
    public function styles(Worksheet $sheet)
    {
        // Title row
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Info row
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8D7DA']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Header row
        $sheet->getStyle('A4:E4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C82333']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
        $sheet->getRowDimension(4)->setRowHeight(25);
        
        // Data rows styling
        $lastRow = count($this->errors) + 4;
        $sheet->getStyle("A5:E{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);
        
        // Error message column - wrap text
        $sheet->getStyle("E5:E{$lastRow}")->getAlignment()->setWrapText(true);
        
        return $sheet;
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Row Number
            'B' => 15,  // Periode
            'C' => 15,  // Karyawan ID
            'D' => 15,  // Company ID
            'E' => 60,  // Error Messages
        ];
    }
}