<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW periode_karyawan_masa_jabatans AS
            SELECT 
                YEAR(STR_TO_DATE(CONCAT(pc.periode, '-01'), '%Y-%m-%d')) as periode, 
                pc.karyawan_id, 
                pc.company_id, 
                pc.salary_type, 
                GROUP_CONCAT(pc.id ORDER BY pc.id ASC) as payroll_ids,
                SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) as salary, 
                SUM(COALESCE(pc.overtime, 0)) as overtime, 
                SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) as tunjangan, 
                SUM(COALESCE(pc.medical_reimbursement, 0)) as natura, 
                SUM(CASE WHEN pc.salary_type = 'nett' THEN COALESCE(pc.pph_21, 0) ELSE 0 END) as tunj_pph_21, 
                SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_2_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) as tunjangan_asuransi, 
                SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) as bpjs_asuransi,
                SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0)) as thr_bonus,
                (
                    SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) + 
                    SUM(COALESCE(pc.overtime, 0)) + 
                    SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) + 
                    SUM(COALESCE(pc.medical_reimbursement, 0)) + 
                    SUM(CASE WHEN pc.salary_type = 'nett' THEN COALESCE(pc.pph_21, 0) ELSE 0 END) + 
                    SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_2_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
                    SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) +
                    SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0))
                ) as total_bruto,
                COUNT(pc.id) as masa_jabatan,
                SUM(COALESCE(pc.bpjs_tenaga_kerja, 0)) as premi_asuransi,
                ROUND(
                    LEAST(
                        (
                            SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) + 
                            SUM(COALESCE(pc.overtime, 0)) + 
                            SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) + 
                            SUM(COALESCE(pc.medical_reimbursement, 0)) + 
                            SUM(CASE WHEN pc.salary_type = 'nett' THEN COALESCE(pc.pph_21, 0) ELSE 0 END) + 
                            SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_2_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
                            SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) +
                            SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0))
                        ) / COUNT(pc.id),
                        10000000
                    ) * 0.05 * COUNT(pc.id)
                ) as biaya_jabatan,
                MAX(ptkp.kriteria) as kriteria,
                MAX(CAST(ptkp.besaran_ptkp AS UNSIGNED)) as besaran_ptkp,
                (
                    (
                        SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) + 
                        SUM(COALESCE(pc.overtime, 0)) + 
                        SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) + 
                        SUM(COALESCE(pc.medical_reimbursement, 0)) + 
                        SUM(CASE WHEN pc.salary_type = 'nett' THEN COALESCE(pc.pph_21, 0) ELSE 0 END) + 
                        SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_2_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
                        SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) +
                        SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0))
                    ) - 
                    SUM(COALESCE(pc.bpjs_tenaga_kerja, 0)) -
                    ROUND(
                        LEAST(
                            (
                                SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) + 
                                SUM(COALESCE(pc.overtime, 0)) + 
                                SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) + 
                                SUM(COALESCE(pc.medical_reimbursement, 0)) + 
                                SUM(CASE WHEN pc.salary_type = 'nett' THEN COALESCE(pc.pph_21, 0) ELSE 0 END) + 
                                SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_2_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
                                SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) +
                                SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0))
                            ) / COUNT(pc.id),
                            10000000
                        ) * 0.05 * COUNT(pc.id)
                    ) -
                    COALESCE(MAX(CAST(ptkp.besaran_ptkp AS UNSIGNED)), 0)
                ) as pkp
            FROM payrolls pc
            LEFT JOIN karyawan_ptkp_histories kph ON pc.karyawan_id = kph.absen_karyawan_id 
                AND kph.tahun = YEAR(STR_TO_DATE(CONCAT(pc.periode, '-01'), '%Y-%m-%d'))
            LEFT JOIN list_ptkps ptkp ON kph.absen_ptkp_id = ptkp.absen_ptkp_id
            WHERE pc.karyawan_id IS NOT NULL 
            AND pc.company_id IS NOT NULL 
            AND pc.periode IS NOT NULL 
            GROUP BY 
                YEAR(STR_TO_DATE(CONCAT(pc.periode, '-01'), '%Y-%m-%d')), 
                pc.karyawan_id, 
                pc.company_id, 
                pc.salary_type
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_karyawan_masa_jabatans');
    }
};
