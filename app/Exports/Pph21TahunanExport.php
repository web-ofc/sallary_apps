<?php

namespace App\Exports;

use App\Models\PeriodeKaryawanMasaJabatan;
use App\Services\Pph21CalculationService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class Pph21TahunanExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected $filters;
    protected $pph21Service;
    protected $rowNumber = 0;
    protected $bracketHeaders;

    public function __construct($filters = [], Pph21CalculationService $pph21Service)
    {
        $this->filters = $filters;
        $this->pph21Service = $pph21Service;
        
        // Get bracket headers untuk tahun yang dipilih
        $year = $filters['year'] ?? date('Y');
        $date = "{$year}-12-31";
        $this->bracketHeaders = $pph21Service->formatBracketHeaderInfo($date);
    }

    /**
     * Collection data
     */
    public function collection()
    {
        DB::connection()->disableQueryLog();

        $query = PeriodeKaryawanMasaJabatan::query()
            ->with(['karyawan:absen_karyawan_id,nama_lengkap,nik', 'company:absen_company_id,company_name,code']);

        // Apply filters
        if (!empty($this->filters['year'])) {
            $query->where('periode', $this->filters['year']);
        }

        if (!empty($this->filters['company_id'])) {
            $query->where('company_id', $this->filters['company_id']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('karyawan', function($query) use ($search) {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                          ->orWhere('nik', 'like', "%{$search}%");
                });
            });
        }

        return $query->orderBy('periode', 'desc')->get();
    }

    /**
     * Table headings (dynamic dengan bracket)
     */
    public function headings(): array
    {
        $headers = [
            'No',
            'NIK',
            'Nama Karyawan',
            'Company',
            'Periode',
            'Salary Type',
            'Salary',
            'Overtime',
            'Tunjangan',
            'Tunj PPh21 Masa',
            'Tunj PPh21 Akhir',
            'Tunj Asuransi',
            'Natura',
            'BPJS Asuransi',
            'THR & Bonus',
            'Total Bruto',
            'Masa Jabatan',
            'Premi Asuransi',
            'Biaya Jabatan',
            'Iuran JHT',
            'Status PTKP',
            'PTKP',
            'PKP',
        ];
        
        // Add PKP per Bracket headers
        foreach ($this->bracketHeaders as $bracket) {
            $headers[] = "PKP {$bracket['rate_percent']}% ({$bracket['max_label']})";
        }
        
        // Add Pajak per Bracket headers
        foreach ($this->bracketHeaders as $bracket) {
            $headers[] = "Pajak {$bracket['rate_percent']}% ({$bracket['max_label']})";
        }
        
        $headers[] = 'PPh 21 Tahunan';
        $headers[] = 'PPh 21 Masa';
        $headers[] = 'PPh 21 Akhir';
        
        return $headers;
    }

    /**
     * Map data untuk setiap row
     */
    public function map($row): array
    {
        $this->rowNumber++;
        
        // Calculate bracket data
        $lastPeriodDate = $this->pph21Service->getLastPeriodDate($row->karyawan_id, $row->periode);
        $pph21Data = $this->pph21Service->calculatePph21Tahunan((float) $row->pkp, $lastPeriodDate);
        $breakdown = $this->pph21Service->formatBreakdownForDisplay($pph21Data['breakdown']);
        
        $data = [
            $this->rowNumber,
            $row->karyawan->nik ?? '-',
            $row->karyawan->nama_lengkap ?? '-',
            $row->company->company_name ?? '-',
            $row->periode,
            strtoupper($row->salary_type),
            $row->salary,
            $row->overtime,
            $row->tunjangan,
            $row->tunj_pph_21,
            $row->tunj_pph21_akhir,
            $row->tunjangan_asuransi,
            $row->natura,
            $row->bpjs_asuransi,
            $row->thr_bonus,
            $row->total_bruto,
            $row->masa_jabatan,
            $row->premi_asuransi,
            $row->biaya_jabatan,
            $row->iuran_jht,
            $row->status,
            $row->besaran_ptkp,
            $row->pkp,
        ];
        
        // Add PKP per bracket
        foreach ($this->bracketHeaders as $bracket) {
            $data[] = $breakdown["bracket_{$bracket['order_index']}_pkp"] ?? 0;
        }
        
        // Add Pajak per bracket
        foreach ($this->bracketHeaders as $bracket) {
            $data[] = $breakdown["bracket_{$bracket['order_index']}_pph21"] ?? 0;
        }
        
        $data[] = $pph21Data['total_pph21_tahunan'];
        $data[] = $row->tunj_pph_21;
        $data[] = $row->tunj_pph21_akhir;
        
        return $data;
    }

    /**
     * Column widths
     */
    public function columnWidths(): array
    {
        $widths = [
            'A' => 5,   // No
            'B' => 15,  // NIK
            'C' => 25,  // Nama
            'D' => 25,  // Company
            'E' => 10,  // Periode
            'F' => 12,  // Type
            'G' => 15,  // Salary
            'H' => 15,  // Overtime
            'I' => 15,  // Tunjangan
            'J' => 15,  // Tunj PPh21 Masa
            'K' => 15,  // Tunj PPh21 Akhir
            'L' => 15,  // Tunj Asuransi
            'M' => 15,  // Natura
            'N' => 15,  // BPJS Asuransi
            'O' => 15,  // THR Bonus
            'P' => 15,  // Total Bruto
            'Q' => 12,  // Masa Jabatan
            'R' => 15,  // Premi Asuransi
            'S' => 15,  // Biaya Jabatan
            'T' => 15,  // Iuran JHT
            'U' => 12,  // Status PTKP
            'V' => 15,  // PTKP
            'W' => 15,  // PKP
        ];
        
        // Add bracket column widths dynamically
        $currentCol = 'X';
        $bracketCount = count($this->bracketHeaders) * 2; // PKP + Pajak
        for ($i = 0; $i < $bracketCount; $i++) {
            $widths[$currentCol] = 15;
            $currentCol++;
        }
        
        // Last 3 columns (PPh21 Tahunan, Masa, Akhir)
        $widths[$currentCol] = 18;
        $currentCol++;
        $widths[$currentCol] = 15;
        $currentCol++;
        $widths[$currentCol] = 15;
        
        return $widths;
    }

    /**
     * Styles untuk header
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Events untuk formatting additional
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Set border
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Format currency (kolom G hingga sebelum terakhir, kecuali Q yang masa jabatan)
                $currencyColumns = range('G', $highestColumn);
                $skipColumns = ['Q', 'U']; // Masa Jabatan & Status PTKP
                
                foreach ($currencyColumns as $column) {
                    if (!in_array($column, $skipColumns)) {
                        $sheet->getStyle($column . '2:' . $column . $highestRow)
                            ->getNumberFormat()
                            ->setFormatCode('#,##0');
                    }
                }

                // Center alignment
                $centerColumns = ['A', 'E', 'F', 'Q', 'U'];
                foreach ($centerColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                // Freeze pane
                $sheet->freezePane('A2');

                // Auto filter
                $sheet->setAutoFilter('A1:' . $highestColumn . '1');
            },
        ];
    }
}