<?php

namespace App\Exports;

use App\Models\PeriodeKaryawanMasaJabatan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\DB;

class PeriodeKaryawanExport implements 
    FromQuery, 
    WithHeadings, 
    WithMapping, 
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query optimization dengan select specific columns dan eager loading
     */
    public function query()
    {
        // Disable query log untuk performa
        DB::connection()->disableQueryLog();

        $query = PeriodeKaryawanMasaJabatan::query()
            ->select([
                'periode_karyawan_masa_jabatans.*'
            ])
            ->with([
                'karyawan:absen_karyawan_id,nama_lengkap,nik',
                'company:absen_company_id,company_name,code'
            ]);

        // Apply filters
        if (!empty($this->filters['periode'])) {
            $query->where('periode', $this->filters['periode']);
        }

        if (!empty($this->filters['karyawan_id'])) {
            $query->where('karyawan_id', $this->filters['karyawan_id']);
        }

        if (!empty($this->filters['company_id'])) {
            $query->where('company_id', $this->filters['company_id']);
        }

        if (!empty($this->filters['salary_type'])) {
            $query->where('salary_type', $this->filters['salary_type']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('karyawan', function($query) use ($search) {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                          ->orWhere('nik', 'like', "%{$search}%");
                })
                ->orWhereHas('company', function($query) use ($search) {
                    $query->where('company_name', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhere('periode', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('periode', 'desc');
    }

    /**
     * Table headings
     */
    public function headings(): array
    {
        return [
            'No',
            'Periode',
            'NIK',
            'Nama Karyawan',
            'Company Code',
            'Company Name',
            'Salary Type',
            'Salary',
            'Overtime',
            'Tunjangan',
            'Natura',
            'Tunj. PPH 21',
            'Tunj. Asuransi',
            'BPJS Asuransi',
            'THR Bonus',
            'Total Bruto',
            'Masa Jabatan (Bulan)',
            'Premi Asuransi',
            'Biaya Jabatan',
            'Kriteria',
            'Besaran PTKP',
            'PKP',
        ];
    }

    /**
     * Map data untuk setiap row
     */
    public function map($row): array
    {
        $this->rowNumber++;
        
        return [
            $this->rowNumber,
            $row->periode,
            $row->karyawan->nik ?? '-',
            $row->karyawan->nama_lengkap ?? '-',
            $row->company->code ?? '-',
            $row->company->company_name ?? '-',
            strtoupper($row->salary_type),
            $row->salary,
            $row->overtime,
            $row->tunjangan,
            $row->natura,
            $row->tunj_pph_21,
            $row->tunjangan_asuransi,
            $row->bpjs_asuransi,
            $row->thr_bonus,
            $row->total_bruto,
            $row->masa_jabatan,
            $row->premi_asuransi,
            $row->biaya_jabatan,
            $row->kriteria,
            $row->besaran_ptkp,
            $row->pkp,
        ];
    }

    /**
     * Column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 10,  // Periode
            'C' => 15,  // NIK
            'D' => 25,  // Nama Karyawan
            'E' => 15,  // Company Code
            'F' => 30,  // Company Name
            'G' => 12,  // Salary Type
            'H' => 15,  // Salary
            'I' => 15,  // Overtime
            'J' => 15,  // Tunjangan
            'K' => 15,  // Natura
            'L' => 15,  // Tunj PPH 21
            'M' => 15,  // Tunj Asuransi
            'N' => 15,  // BPJS Asuransi
            'O' => 15,  // THR Bonus
            'P' => 18,  // Total Bruto
            'Q' => 15,  // Masa Jabatan
            'R' => 15,  // Premi Asuransi
            'S' => 15,  // Biaya Jabatan
            'T' => 12,  // Kriteria
            'U' => 15,  // Besaran PTKP
            'V' => 18,  // PKP
        ];
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

                // Set border untuk semua cell
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Format currency untuk kolom H sampai V (kecuali Q yang masa jabatan)
                $currencyColumns = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'U', 'V'];
                foreach ($currencyColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }

                // Center alignment untuk No, Periode, Salary Type, Masa Jabatan
                $centerColumns = ['A', 'B', 'G', 'Q'];
                foreach ($centerColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                // Right alignment untuk currency columns
                foreach ($currencyColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }

                // Freeze pane di row 1
                $sheet->freezePane('A2');

                // Auto filter
                $sheet->setAutoFilter('A1:' . $highestColumn . '1');
            },
        ];
    }
}