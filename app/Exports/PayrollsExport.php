<?php

namespace App\Exports;

use App\Models\PayrollCalculation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class PayrollsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, WithEvents
{
    protected $periode;
    protected $companyId;
    protected $isReleased;
    protected $isReleasedSlip;

        public function __construct($periode = null, $companyId = null, $isReleased = null, $isReleasedSlip = null)
    {
        $this->periode = $periode;
        $this->companyId = $companyId;
        $this->isReleased = $isReleased;
        $this->isReleasedSlip = $isReleasedSlip;
    }

        public function collection()
{
    try {
        $query = PayrollCalculation::with([
            'karyawan:absen_karyawan_id,nik,nama_lengkap',
            'company:absen_company_id,company_name'
        ]);

        if ($this->periode) {
            $query->where('periode', $this->periode);
        }

        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        if ($this->isReleased !== null) {
            $query->where('is_released', $this->isReleased);
            
            if ($this->isReleasedSlip !== null) {
                $query->where('is_released_slip', $this->isReleasedSlip);
            }
        }

        $data = $query->orderBy('periode', 'desc')->orderBy('id')->get();
        
        // 🔥 TAMBAHKAN LOG
        Log::info('Export Data Count: ' . $data->count());
        
        return $data;
        
    } catch (\Exception $e) {
        Log::error('Export Collection Error: ' . $e->getMessage());
        return collect([]); // Return empty collection jika error
    }
}

    /**
     * Return multi-row headers
     */
    public function headings(): array
    {
        return [
            // ROW 1: Main group headers
            [
                'Periode',
                'NIK',
                'Nama Karyawan',
                'Company',
                'Salary Type',
                'Gaji Pokok',
                'Monthly Insentif', '', '', '', '', '', // 6 columns
                'Monthly Allowance', '', '', '', // 4 columns
                'Yearly Benefit', '', '', // 3 columns
                'Potongan', '', '', '', '', '', // 6 columns
                'BPJS TK', '', '', '', '', '', // 6 columns
                'BPJS KES', '', // 2 columns
                'Lainnya', '', '', // 3 columns
                'Summary', '', '', '', // 4 columns
                'Status'
            ],
            // ROW 2: Detail column headers
            [
                '', '', '', '', '', '',
                // Monthly Insentif (6)
                'Monthly KPI',
                'Overtime',
                'Medical',
                'Insentif Sholat',
                'Monthly Bonus',
                'Rapel',
                // Monthly Allowance (4)
                'Tunj. Pulsa',
                'Tunj. Kehadiran',
                'Tunj. Transport',
                'Tunj. Lainnya',
                // Yearly Benefit (3)
                'Yearly Bonus',
                'THR',
                'Other',
                // Potongan (6)
                'CA Corporate',
                'CA Personal',
                'CA Kehadiran',
                'BPJS TK',
                'BPJS Kes',
                'PPh 21 Ded',
                // BPJS TK (6)
                'JHT 3.7%',
                'JHT 2%',
                'JKK 0.24%',
                'JKM 0.3%',
                'JP 2%',
                'JP 1%',
                // BPJS KES (2)
                'Kes 4%',
                'Kes 1%',
                // Lainnya (3)
                'PPh 21',
                'GLH',
                'LM',
                // Summary (4)
                'Salary',
                'Total Penerimaan',
                'Total Potongan',
                'Gaji Bersih',
                ''
            ]
        ];
    }

    public function map($payroll): array
    {
        return [
            $payroll->periode,
            $payroll->karyawan?->nik ?? '-',
            $payroll->karyawan?->nama_lengkap ?? '-',
            $payroll->company?->company_name ?? '-',
            $payroll->salary_type ?? '-',
            $payroll->gaji_pokok ?? 0,
            // Monthly Insentif (6)
            $payroll->monthly_kpi ?? 0,
            $payroll->overtime ?? 0,
            $payroll->medical_reimbursement ?? 0,
            $payroll->insentif_sholat ?? 0,
            $payroll->monthly_bonus ?? 0,
            $payroll->rapel ?? 0,
            // Monthly Allowance (4)
            $payroll->tunjangan_pulsa ?? 0,
            $payroll->tunjangan_kehadiran ?? 0,
            $payroll->tunjangan_transport ?? 0,
            $payroll->tunjangan_lainnya ?? 0,
            // Yearly Benefit (3)
            $payroll->yearly_bonus ?? 0,
            $payroll->thr ?? 0,
            $payroll->other ?? 0,
            // Potongan (6)
            $payroll->ca_corporate ?? 0,
            $payroll->ca_personal ?? 0,
            $payroll->ca_kehadiran ?? 0,
            $payroll->bpjs_tenaga_kerja ?? 0,
            $payroll->bpjs_kesehatan ?? 0,
            $payroll->pph_21_deduction ?? 0,
            // BPJS TK (6)
            $payroll->bpjs_tk_jht_3_7_percent ?? 0,
            $payroll->bpjs_tk_jht_2_percent ?? 0,
            $payroll->bpjs_tk_jkk_0_24_percent ?? 0,
            $payroll->bpjs_tk_jkm_0_3_percent ?? 0,
            $payroll->bpjs_tk_jp_2_percent ?? 0,
            $payroll->bpjs_tk_jp_1_percent ?? 0,
            // BPJS KES (2)
            $payroll->bpjs_kes_4_percent ?? 0,
            $payroll->bpjs_kes_1_percent ?? 0,
            // Lainnya (3)
            $payroll->pph_21 ?? 0,
            $payroll->glh ?? 0,
            $payroll->lm ?? 0,
            // Summary (4)
            $payroll->salary ?? 0,
            $payroll->total_penerimaan ?? 0,
            abs($payroll->total_potongan ?? 0),
            $payroll->gaji_bersih ?? 0,
            // Status
            $payroll->is_released ? ($payroll->is_released_slip ? 'Released Slip' : 'Released') : 'Pending'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Gaji Pokok
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
            'W' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS TK
            'X' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // BPJS Kes
            'Y' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // PPh 21 Deduction
            'Z' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // JHT 3.7%
            'AA' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // JHT 2%
            'AB' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // JKK 0.24%
            'AC' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // JKM 0.3%
            'AD' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // JP 2%
            'AE' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // JP 1%
            'AF' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Kes 4%
            'AG' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Kes 1%
            'AH' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // PPh 21
            'AI' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // GLH
            'AJ' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // LM
            'AK' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Salary
            'AL' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Penerimaan
            'AM' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Potongan
            'AN' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Gaji Bersih
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk header rows
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8E8E8']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F0F0']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Merge cells untuk group headers di row 1
                $merges = [
                    'A1:A2', 'B1:B2', 'C1:C2', 'D1:D2', 'E1:E2', 'F1:F2', // Fixed columns
                    'G1:L1',  // Monthly Insentif (6 cols)
                    'M1:P1',  // Monthly Allowance (4 cols)
                    'Q1:S1',  // Yearly Benefit (3 cols)
                    'T1:Y1',  // Potongan (6 cols)
                    'Z1:AE1', // BPJS TK (6 cols)
                    'AF1:AG1',// BPJS KES (2 cols)
                    'AH1:AJ1',// Lainnya (3 cols)
                    'AK1:AN1',// Summary (4 cols)
                    'AO1:AO2' // Status
                ];

                foreach ($merges as $merge) {
                    $sheet->mergeCells($merge);
                }

                // Set borders untuk semua cells
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();
                
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Auto-size columns
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Set row heights untuk headers
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // Color coding untuk group headers
                $colorGroups = [
                    ['range' => 'G1:L1', 'color' => 'FFF9C4'],   // Monthly Insentif - Yellow
                    ['range' => 'M1:P1', 'color' => 'C8E6C9'],   // Monthly Allowance - Green
                    ['range' => 'Q1:S1', 'color' => 'BBDEFB'],   // Yearly Benefit - Blue
                    ['range' => 'T1:Y1', 'color' => 'FFCCBC'],   // Potongan - Orange
                    ['range' => 'Z1:AE1', 'color' => 'E1BEE7'],  // BPJS TK - Purple
                    ['range' => 'AF1:AG1', 'color' => 'F8BBD0'], // BPJS KES - Pink
                    ['range' => 'AH1:AJ1', 'color' => 'B2DFDB'], // Lainnya - Teal
                    ['range' => 'AK1:AN1', 'color' => 'CFD8DC'], // Summary - Grey
                ];

                foreach ($colorGroups as $group) {
                    $sheet->getStyle($group['range'])->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $group['color']]
                        ]
                    ]);
                }

                // Freeze panes (freeze first 2 rows and first 6 columns)
                $sheet->freezePane('G3');
            },
        ];
    }
}