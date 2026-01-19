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

                -- ✅ PPH21 MASA (Jan–Nov / non-last)
                SUM(
                    CASE 
                        WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 0
                            THEN COALESCE(pc.pph_21, 0)
                        ELSE 0
                    END
                ) as tunj_pph_21,

                -- ✅ PPH21 AKHIR (Des / last)
                SUM(
                    CASE 
                        WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 1
                            THEN COALESCE(pc.pph_21, 0)
                        ELSE 0
                    END
                ) as tunj_pph21_akhir,

                SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_1_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) as tunjangan_asuransi, 
                SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) as bpjs_asuransi,
                SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0)) as thr_bonus,

                (
                    SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) + 
                    SUM(COALESCE(pc.overtime, 0)) + 
                    SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) + 
                    SUM(COALESCE(pc.medical_reimbursement, 0)) + 

                    -- ✅ TOTAL_BRUTO tetap sama, tapi bagian PPH21 diganti: MASA + AKHIR (hasil pecahan)
                    (
                        SUM(
                            CASE 
                                WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 0
                                    THEN COALESCE(pc.pph_21, 0)
                                ELSE 0
                            END
                        ) +
                        SUM(
                            CASE 
                                WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 1
                                    THEN COALESCE(pc.pph_21, 0)
                                ELSE 0
                            END
                        )
                    ) +

                    SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_1_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
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

                            -- ✅ biaya_jabatan tetap sama, tapi bagian PPH21 diganti: MASA + AKHIR (hasil pecahan)
                            (
                                SUM(
                                    CASE 
                                        WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 0
                                            THEN COALESCE(pc.pph_21, 0)
                                        ELSE 0
                                    END
                                ) +
                                SUM(
                                    CASE 
                                        WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 1
                                            THEN COALESCE(pc.pph_21, 0)
                                        ELSE 0
                                    END
                                )
                            ) +

                            SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_1_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
                            SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) +
                            SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0))
                        ) / COUNT(pc.id),
                        10000000
                    ) * 0.05 * COUNT(pc.id)
                ) as biaya_jabatan,

                (SUM(COALESCE(pc.bpjs_tk_jht_2_percent, 0)) - SUM(COALESCE(pc.bpjs_tk_jp_1_percent, 0))) as iuran_jht,
                MAX(ptkp.kriteria) as kriteria,
                MAX(ptkp.status) as status,
                MAX(CAST(ptkp.besaran_ptkp AS UNSIGNED)) as besaran_ptkp,

                GREATEST(
                    FLOOR(
                        (
                            (
                                SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) + 
                                SUM(COALESCE(pc.overtime, 0)) + 
                                SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) + 
                                SUM(COALESCE(pc.medical_reimbursement, 0)) + 

                                -- ✅ PKP tetap sama, tapi bagian PPH21 diganti: MASA + AKHIR (hasil pecahan)
                                (
                                    SUM(
                                        CASE 
                                            WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 0
                                                THEN COALESCE(pc.pph_21, 0)
                                            ELSE 0
                                        END
                                    ) +
                                    SUM(
                                        CASE 
                                            WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 1
                                                THEN COALESCE(pc.pph_21, 0)
                                            ELSE 0
                                        END
                                    )
                                ) +

                                SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_1_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
                                SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) +
                                SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0))
                            ) - 
                            ROUND(
                                LEAST(
                                    (
                                        SUM(COALESCE(pc.gaji_pokok, 0) + COALESCE(pc.monthly_kpi, 0) + COALESCE(pc.rapel, 0)) + 
                                        SUM(COALESCE(pc.overtime, 0)) + 
                                        SUM(COALESCE(pc.insentif_sholat, 0) + COALESCE(pc.monthly_bonus, 0) + COALESCE(pc.tunjangan_pulsa, 0) + COALESCE(pc.tunjangan_kehadiran, 0) + COALESCE(pc.tunjangan_transport, 0) + COALESCE(pc.tunjangan_lainnya, 0)) + 
                                        SUM(COALESCE(pc.medical_reimbursement, 0)) + 

                                        -- ✅ biaya_jabatan dalam PKP tetap sama, tapi bagian PPH21 diganti: MASA + AKHIR (hasil pecahan)
                                        (
                                            SUM(
                                                CASE 
                                                    WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 0
                                                        THEN COALESCE(pc.pph_21, 0)
                                                    ELSE 0
                                                END
                                            ) +
                                            SUM(
                                                CASE 
                                                    WHEN pc.salary_type = 'nett' AND COALESCE(pc.is_last_period, 0) = 1
                                                        THEN COALESCE(pc.pph_21, 0)
                                                    ELSE 0
                                                END
                                            )
                                        ) +

                                        SUM(CASE WHEN pc.salary_type = 'nett' THEN (COALESCE(pc.bpjs_tk_jht_2_percent, 0) + COALESCE(pc.bpjs_tk_jp_1_percent, 0) + COALESCE(pc.bpjs_kes_1_percent, 0)) ELSE 0 END) + 
                                        SUM(COALESCE(pc.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(pc.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(pc.bpjs_kes_4_percent, 0)) +
                                        SUM(COALESCE(pc.yearly_bonus, 0) + COALESCE(pc.thr, 0) + COALESCE(pc.other, 0) + COALESCE(pc.glh, 0) + COALESCE(pc.lm, 0) + COALESCE(pc.lainnya, 0))
                                    ) / COUNT(pc.id),
                                    10000000
                                ) * 0.05 * COUNT(pc.id)
                            ) -
                            (SUM(COALESCE(pc.bpjs_tk_jht_2_percent, 0)) - SUM(COALESCE(pc.bpjs_tk_jp_1_percent, 0))) -
                            COALESCE(MAX(CAST(ptkp.besaran_ptkp AS UNSIGNED)), 0)
                        ) / 1000
                    ) * 1000,
                    0
                ) as pkp

            FROM payrolls pc
            LEFT JOIN karyawan_ptkp_histories kph 
                ON pc.karyawan_id = kph.absen_karyawan_id 
                AND kph.tahun = YEAR(STR_TO_DATE(CONCAT(pc.periode, '-01'), '%Y-%m-%d'))
            LEFT JOIN list_ptkps ptkp 
                ON kph.absen_ptkp_id = ptkp.absen_ptkp_id
            WHERE pc.karyawan_id IS NOT NULL 
            AND pc.company_id IS NOT NULL 
            AND pc.periode IS NOT NULL 
            GROUP BY 
                YEAR(STR_TO_DATE(CONCAT(pc.periode, '-01'), '%Y-%m-%d')), 
                pc.karyawan_id, 
                pc.company_id, 
                pc.salary_type;
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
