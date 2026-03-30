<?php

namespace App\Exports;

use App\Models\ReimbursementChildSum;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReimbursementChildSumExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected ?string $periode_slip;
    protected int $rowNumber = 0;

    public function __construct(?string $periode_slip = null)
    {
        $this->periode_slip = $periode_slip;
    }

    // ============================================================
    //  QUERY — pakai eager load chunk-friendly, hindari N+1
    // ============================================================
    public function query()
    {
        return ReimbursementChildSum::query()
            ->with(['karyawan:id,absen_karyawan_id,nama_lengkap,nik']) // select kolom minimal
            ->select([
                'reimbursement_child_sum.karyawan_id',
                'reimbursement_child_sum.periode_slip',
                'reimbursement_child_sum.status',
                'reimbursement_child_sum.jumlah_reimbursement',
                'reimbursement_child_sum.total_harga',
            ])
            ->when($this->periode_slip, fn($q) => $q->where('periode_slip', $this->periode_slip))
            ->orderBy('periode_slip', 'desc')
            ->orderBy('karyawan_id', 'asc');
    }

    // ============================================================
    //  HEADING ROW
    // ============================================================
    public function headings(): array
    {
        return [
            'No',
            'NIK',
            'Nama Karyawan',
            'Periode Slip',
            'Jumlah Pengajuan',
            'Total Tagihan (Rp)',
            'Status',
        ];
    }

    // ============================================================
    //  MAP TIAP ROW — format data sebelum ditulis ke Excel
    // ============================================================
    public function map($row): array
    {
        $this->rowNumber++;

        $karyawan = $row->karyawan;

        return [
            $this->rowNumber,
            $karyawan?->nik ?? '-',
            $karyawan?->nama_lengkap ?? 'Unknown',
            $row->periode_slip,
            $row->jumlah_reimbursement,
            (int) $row->total_harga,            // integer biar Excel bisa SUM
            $row->status ? 'Approved' : 'Pending',
        ];
    }

    // ============================================================
    //  SHEET TITLE
    // ============================================================
    public function title(): string
    {
        return 'Rekap Reimbursement';
    }

    // ============================================================
    //  COLUMN WIDTHS
    // ============================================================
    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 18,  // NIK
            'C' => 30,  // Nama
            'D' => 16,  // Periode
            'E' => 20,  // Jumlah
            'F' => 25,  // Total Tagihan
            'G' => 14,  // Status
        ];
    }

    // ============================================================
    //  STYLES — header bold + warna
    // ============================================================
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1C325E'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Format kolom Total Tagihan sebagai currency Rp
        $lastRow = $this->rowNumber + 1; // +1 karena header di row 1
        if ($lastRow > 1) {
            $sheet->getStyle("F2:F{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');

            // Alternating row color untuk readability
            for ($i = 2; $i <= $lastRow; $i++) {
                if ($i % 2 === 0) {
                    $sheet->getStyle("A{$i}:G{$i}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'],
                        ],
                    ]);
                }
            }

            // Border semua data
            $sheet->getStyle("A1:G{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ]);

            // Center kolom No, Periode, Jumlah, Status
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Right-align total tagihan
            $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // ROW TOTAL di baris terakhir
            $totalRow = $lastRow + 1;
            $sheet->setCellValue("E{$totalRow}", 'TOTAL');
            $sheet->setCellValue("F{$totalRow}", "=SUM(F2:F{$lastRow})");
            $sheet->getStyle("E{$totalRow}:F{$totalRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1C325E'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $sheet->getStyle("F{$totalRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }
}