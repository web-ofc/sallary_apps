<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    protected ?string $periode;
    protected $companyId; // bisa string/int
    protected $isReleased; // null|0|1
    protected $isReleasedSlip; // null|0|1

    protected ?array $assignedCompanyIds;

    public function __construct(
        $periode = null, 
        $companyId = null, 
        $isReleased = null, 
        $isReleasedSlip = null,
        $assignedCompanyIds = null  // ✅ Tambahin ini
    )
    {
        $this->periode            = $periode;
        $this->companyId          = $companyId;
        $this->isReleased         = $isReleased;
        $this->isReleasedSlip     = $isReleasedSlip;
        $this->assignedCompanyIds = $assignedCompanyIds; // ✅ Store assigned companies
    }
    /**
     * ✅ STREAMING QUERY (ga load semua ke RAM)
     */
    public function query(): Builder
    {
        $q = \DB::table('payroll_calculations as pc')
            ->leftJoin('karyawans as k', 'k.absen_karyawan_id', '=', 'pc.karyawan_id')
            ->leftJoin('companies as c', 'c.absen_company_id', '=', 'pc.company_id')
            ->select([
                // penting: pc.id buat sorting stabil saat chunk
                'pc.id',

                'pc.periode',
                'k.nik',
                'k.nama_lengkap',
                'c.company_name',

                'pc.salary_type',
                'pc.ptkp_status',
                'pc.gaji_pokok',
                'pc.monthly_kpi',
                'pc.overtime',
                'pc.medical_reimbursement',
                'pc.insentif_sholat',
                'pc.monthly_bonus',
                'pc.rapel',
                'pc.tunjangan_pulsa',
                'pc.tunjangan_kehadiran',
                'pc.tunjangan_transport',
                'pc.tunjangan_lainnya',
                'pc.yearly_bonus',
                'pc.thr',
                'pc.other',
                'pc.ca_corporate',
                'pc.ca_personal',
                'pc.ca_kehadiran',
                'pc.bpjs_tenaga_kerja',
                'pc.bpjs_kesehatan',
                'pc.pph_21_deduction',
                'pc.bpjs_tk_jht_3_7_percent',
                'pc.bpjs_tk_jht_2_percent',
                'pc.bpjs_tk_jkk_0_24_percent',
                'pc.bpjs_tk_jkm_0_3_percent',
                'pc.bpjs_tk_jp_2_percent',
                'pc.bpjs_tk_jp_1_percent',
                'pc.bpjs_kes_4_percent',
                'pc.bpjs_kes_1_percent',
                'pc.pph_21',
                'pc.glh',
                'pc.lm',

                // ini ada di VIEW lu:
                'pc.salary',
                'pc.total_penerimaan',
                'pc.total_potongan',
                'pc.gaji_bersih',

                'pc.is_released',
                'pc.is_released_slip',
            ]);

        // ✅ Filter berdasarkan assigned companies
        if (!empty($this->assignedCompanyIds)) {
            $q->whereIn('pc.company_id', $this->assignedCompanyIds);
        }

        if (!empty($this->periode)) {
            $q->where('pc.periode', $this->periode);
        }

        if (!empty($this->companyId)) {
            $q->where('pc.company_id', $this->companyId);
        }

        if ($this->isReleased !== null) {
            $q->where('pc.is_released', $this->isReleased);

            if ($this->isReleasedSlip !== null) {
                $q->where('pc.is_released_slip', $this->isReleasedSlip);
            }
        }

        // ✅ chunk-friendly: orderBy id ASC stabil
        return $q->orderBy('pc.id', 'asc');
    }

    /**
     * Ukuran chunk: makin besar makin cepat, tapi makin berat RAM.
     * biasanya 1000-5000 oke.
     */
    public function chunkSize(): int
    {
        return 2000;
    }

    public function headings(): array
    {
        return [
            'Periode',
            'NIK',
            'Nama Karyawan',
            'Company',
            'Salary Type',
            'PTKP Status',
            'Gaji Pokok',
            'Monthly KPI',
            'Overtime',
            'Medical',
            'Insentif Sholat',
            'Monthly Bonus',
            'Rapel',
            'Tunj. Pulsa',
            'Tunj. Kehadiran',
            'Tunj. Transport',
            'Tunj. Lainnya',
            'Yearly Bonus',
            'THR',
            'Other',
            'CA Corporate',
            'CA Personal',
            'CA Kehadiran',
            'BPJS TK',
            'BPJS Kes',
            'PPh 21 Ded',
            'JHT 3.7%',
            'JHT 2%',
            'JKK 0.24%',
            'JKM 0.3%',
            'JP 2%',
            'JP 1%',
            'Kes 4%',
            'Kes 1%',
            'PPh 21',
            'GLH',
            'LM',
            'Salary',
            'Total Penerimaan',
            'Total Potongan',
            'Gaji Bersih',
            'Status',
        ];
    }

    public function map($row): array
    {
        // $row dari Query Builder => stdClass (akses pakai ->)
        $status = 'Pending';
        if (!empty($row->is_released)) {
            $status = !empty($row->is_released_slip) ? 'Released Slip' : 'Released';
        }

        return [
            $row->periode ?? '',
            $row->nik ?? '',
            $row->nama_lengkap ?? '',
            $row->company_name ?? '',
            $row->salary_type ?? '',
            $row->ptkp_status ?? '',

            (int)($row->gaji_pokok ?? 0),
            (int)($row->monthly_kpi ?? 0),
            (int)($row->overtime ?? 0),
            (int)($row->medical_reimbursement ?? 0),
            (int)($row->insentif_sholat ?? 0),
            (int)($row->monthly_bonus ?? 0),
            (int)($row->rapel ?? 0),
            (int)($row->tunjangan_pulsa ?? 0),
            (int)($row->tunjangan_kehadiran ?? 0),
            (int)($row->tunjangan_transport ?? 0),
            (int)($row->tunjangan_lainnya ?? 0),
            (int)($row->yearly_bonus ?? 0),
            (int)($row->thr ?? 0),
            (int)($row->other ?? 0),

            (int)($row->ca_corporate ?? 0),
            (int)($row->ca_personal ?? 0),
            (int)($row->ca_kehadiran ?? 0),

            (int)($row->bpjs_tenaga_kerja ?? 0),
            (int)($row->bpjs_kesehatan ?? 0),

            (int)($row->pph_21_deduction ?? 0),

            (int)($row->bpjs_tk_jht_3_7_percent ?? 0),
            (int)($row->bpjs_tk_jht_2_percent ?? 0),
            (int)($row->bpjs_tk_jkk_0_24_percent ?? 0),
            (int)($row->bpjs_tk_jkm_0_3_percent ?? 0),
            (int)($row->bpjs_tk_jp_2_percent ?? 0),
            (int)($row->bpjs_tk_jp_1_percent ?? 0),
            (int)($row->bpjs_kes_4_percent ?? 0),
            (int)($row->bpjs_kes_1_percent ?? 0),

            (int)($row->pph_21 ?? 0),
            (int)($row->glh ?? 0),
            (int)($row->lm ?? 0),

            (int)($row->salary ?? 0),
            (int)($row->total_penerimaan ?? 0),

            // total_potongan di VIEW lu itu MINUS,
            // untuk export tampilan biasa dibikin positif:
            abs((int)($row->total_potongan ?? 0)),

            (int)($row->gaji_bersih ?? 0),

            $status,
        ];
    }
}
