<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) VIEW gabung slip source (tetap)
        DB::statement("
            CREATE OR REPLACE VIEW payrolls_merge AS
            
            SELECT 
                pf.id,
                pf.periode,
                pf.karyawan_id,
                pf.company_id,
                pf.gaji_pokok,
                pf.monthly_kpi,
                pf.overtime,
                pf.medical_reimbursement,
                pf.insentif_sholat,
                pf.monthly_bonus,
                pf.rapel,
                pf.tunjangan_pulsa,
                pf.tunjangan_kehadiran,
                pf.tunjangan_transport,
                pf.tunjangan_lainnya,
                pf.yearly_bonus,
                pf.thr,
                pf.other,
                pf.ca_corporate,
                pf.ca_personal,
                pf.ca_kehadiran,
                pf.pph_21,
                pf.salary_type,
                pf.bpjs_tenaga_kerja,
                pf.bpjs_kesehatan,
                pf.pph_21_deduction,
                pf.bpjs_tk_jht_3_7_percent,
                pf.bpjs_tk_jht_2_percent,
                pf.bpjs_tk_jkk_0_24_percent,
                pf.bpjs_tk_jkm_0_3_percent,
                pf.bpjs_tk_jp_2_percent,
                pf.bpjs_tk_jp_1_percent,
                pf.bpjs_kes_4_percent,
                pf.bpjs_kes_1_percent,
                pf.glh,
                pf.lm,
                pf.lainnya,
                pf.is_released,
                pf.is_released_slip,
                pf.is_last_period,
                pf.created_at,
                pf.updated_at,
                'payrolls_fakes' AS source_table
            FROM payrolls_fakes pf
            WHERE pf.is_released = 1 
              AND pf.is_released_slip = 1
            
            UNION ALL
            
            SELECT 
                p.id,
                p.periode,
                p.karyawan_id,
                p.company_id,
                p.gaji_pokok,
                p.monthly_kpi,
                p.overtime,
                p.medical_reimbursement,
                p.insentif_sholat,
                p.monthly_bonus,
                p.rapel,
                p.tunjangan_pulsa,
                p.tunjangan_kehadiran,
                p.tunjangan_transport,
                p.tunjangan_lainnya,
                p.yearly_bonus,
                p.thr,
                p.other,
                p.ca_corporate,
                p.ca_personal,
                p.ca_kehadiran,
                p.pph_21,
                p.salary_type,
                p.bpjs_tenaga_kerja,
                p.bpjs_kesehatan,
                p.pph_21_deduction,
                p.bpjs_tk_jht_3_7_percent,
                p.bpjs_tk_jht_2_percent,
                p.bpjs_tk_jkk_0_24_percent,
                p.bpjs_tk_jkm_0_3_percent,
                p.bpjs_tk_jp_2_percent,
                p.bpjs_tk_jp_1_percent,
                p.bpjs_kes_4_percent,
                p.bpjs_kes_1_percent,
                p.glh,
                p.lm,
                p.lainnya,
                p.is_released,
                p.is_released_slip,
                p.is_last_period,
                p.created_at,
                p.updated_at,
                'payrolls' AS source_table
            FROM payrolls p
            WHERE p.is_released = 1 
              AND p.is_released_slip = 1
              AND NOT EXISTS (
                  SELECT 1 
                  FROM payrolls_fakes pf
                  WHERE pf.karyawan_id = p.karyawan_id
                    AND pf.periode = p.periode
                    AND pf.is_released_slip = 1
              )
        ");

        // 2) VIEW kalkulasi merge (pegawai BPJS income hanya untuk NETT)
        DB::statement("
            CREATE OR REPLACE VIEW payroll_calculations_merge AS
            SELECT
                p.id,
                p.periode,
                p.karyawan_id,
                p.company_id,
                p.gaji_pokok,
                p.monthly_kpi,
                p.overtime,
                p.medical_reimbursement,
                p.insentif_sholat,
                p.monthly_bonus,
                p.rapel,
                p.tunjangan_pulsa,
                p.tunjangan_kehadiran,
                p.tunjangan_transport,
                p.tunjangan_lainnya,
                p.yearly_bonus,
                p.thr,
                p.other,
                p.ca_corporate,
                p.ca_personal,
                p.ca_kehadiran,
                p.pph_21,
                p.salary_type,

                -- disimpan MINUS di tabel (ikut apa adanya)
                p.bpjs_tenaga_kerja,
                p.bpjs_kesehatan,

                /* =========================
                PPh21 yang ditampilkan di PDF harus POSITIF
                ========================= */
                CASE
                    WHEN p.salary_type = 'nett' THEN COALESCE(p.pph_21, 0)
                    ELSE COALESCE(p.pph_21_deduction, 0)
                END AS pph_21_deduction,

                p.bpjs_tk_jht_3_7_percent,
                p.bpjs_tk_jht_2_percent,
                p.bpjs_tk_jkk_0_24_percent,
                p.bpjs_tk_jkm_0_3_percent,
                p.bpjs_tk_jp_2_percent,
                p.bpjs_tk_jp_1_percent,
                p.bpjs_kes_4_percent,
                p.bpjs_kes_1_percent,
                p.glh,
                p.lm,
                p.lainnya,
                p.is_released,
                p.is_released_slip,
                p.is_last_period,
                p.created_at,
                p.updated_at,
                p.source_table,

                /* =========================
                Salary Calculation (biarin gaya lama)
                ========================= */
                COALESCE(p.gaji_pokok, 0)
                + COALESCE(p.pph_21_deduction, 0)
                + COALESCE(p.pph_21, 0) AS salary,

                /* =========================
                ⭐ Tunjangan (NETT)
                ========================= */
                CASE
                    WHEN p.salary_type = 'nett' THEN COALESCE(p.pph_21, 0)
                    ELSE 0
                END AS tunjangan,

                /* =========================
                BPJS perusahaan income (tetap)
                ========================= */
                (
                    COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
                    + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
                    + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
                    + COALESCE(p.bpjs_tk_jp_2_percent, 0)
                ) AS bpjs_tenaga_kerja_perusahaan_income,

                COALESCE(p.bpjs_kes_4_percent, 0) AS bpjs_kesehatan_perusahaan_income,

                /* =========================
                ✅ BPJS pegawai income: HANYA NETT, kalau GROSS jadi 0
                (kolom manual sudah MINUS -> ikut nilai tabel, tapi hanya NETT)
                ========================= */
                CASE
                    WHEN p.salary_type = 'nett' THEN
                        COALESCE(p.bpjs_tk_jht_2_percent, 0)
                        + COALESCE(p.bpjs_tk_jp_1_percent, 0)
                        + COALESCE(p.bpjs_tenaga_kerja, 0)
                    ELSE 0
                END AS bpjs_tenaga_kerja_pegawai_income,

                CASE
                    WHEN p.salary_type = 'nett' THEN
                        COALESCE(p.bpjs_kes_1_percent, 0)
                        + COALESCE(p.bpjs_kesehatan, 0)
                    ELSE 0
                END AS bpjs_kesehatan_pegawai_income,

                /* =========================
                BPJS deduction (NEGATIF)
                - perusahaan: minus total perusahaan
                - pegawai: TETAP kepotong (gross & nett), tapi JANGAN pakai kolom manual
                    karena kolom manual sudah MINUS di tabel -> bisa double minus
                ========================= */
                -(
                    COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
                    + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
                    + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
                    + COALESCE(p.bpjs_tk_jp_2_percent, 0)
                ) AS bpjs_tenaga_kerja_perusahaan_deduction,

                -COALESCE(p.bpjs_kes_4_percent, 0) AS bpjs_kesehatan_perusahaan_deduction,

                -(
                    COALESCE(p.bpjs_tk_jht_2_percent, 0)
                    + COALESCE(p.bpjs_tk_jp_1_percent, 0)
                ) AS bpjs_tenaga_kerja_pegawai_deduction,

                -COALESCE(p.bpjs_kes_1_percent, 0) AS bpjs_kesehatan_pegawai_deduction,

                /* =========================
                Total Penerimaan:
                ✅ pegawai BPJS income hanya masuk kalau NETT
                ========================= */
                (
                    COALESCE(p.gaji_pokok, 0)
                    + COALESCE(p.monthly_kpi, 0)
                    + COALESCE(p.overtime, 0)
                    + COALESCE(p.medical_reimbursement, 0)

                    -- perusahaan income
                    + (
                        COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
                        + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
                        + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
                        + COALESCE(p.bpjs_tk_jp_2_percent, 0)
                    )
                    + COALESCE(p.bpjs_kes_4_percent, 0)

                    -- pegawai income (nett only)
                    + (
                        CASE
                            WHEN p.salary_type = 'nett' THEN
                                (
                                    COALESCE(p.bpjs_tk_jht_2_percent, 0)
                                    + COALESCE(p.bpjs_tk_jp_1_percent, 0)
                                    + COALESCE(p.bpjs_tenaga_kerja, 0)  -- minus ikut tabel
                                )
                                +
                                (
                                    COALESCE(p.bpjs_kes_1_percent, 0)
                                    + COALESCE(p.bpjs_kesehatan, 0)     -- minus ikut tabel
                                )
                            ELSE 0
                        END
                    )

                    + COALESCE(p.insentif_sholat, 0)
                    + COALESCE(p.monthly_bonus, 0)
                    + COALESCE(p.rapel, 0)
                    + COALESCE(p.tunjangan_pulsa, 0)
                    + COALESCE(p.tunjangan_kehadiran, 0)
                    + COALESCE(p.tunjangan_transport, 0)
                    + COALESCE(p.tunjangan_lainnya, 0)
                    + COALESCE(p.yearly_bonus, 0)
                    + COALESCE(p.thr, 0)
                    + COALESCE(p.other, 0)

                    -- tunjangan pajak (nett only)
                    + (CASE WHEN p.salary_type = 'nett' THEN COALESCE(p.pph_21, 0) ELSE 0 END)
                ) AS total_penerimaan,

                /* =========================
                Total Potongan (negatif untuk THP)
                - PPh21: nett pakai -pph_21, gross pakai -pph_21_deduction
                - BPJS pegawai potongan: persen doang (tanpa kolom manual)
                ========================= */
                (
                    COALESCE(p.ca_corporate, 0)
                    + COALESCE(p.ca_personal, 0)
                    + COALESCE(p.ca_kehadiran, 0)

                    + CASE
                        WHEN p.salary_type = 'nett' THEN -COALESCE(p.pph_21, 0)
                        ELSE -COALESCE(p.pph_21_deduction, 0)
                    END

                    - (
                        COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
                        + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
                        + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
                        + COALESCE(p.bpjs_tk_jp_2_percent, 0)
                    )

                    - (
                        COALESCE(p.bpjs_tk_jht_2_percent, 0)
                        + COALESCE(p.bpjs_tk_jp_1_percent, 0)
                    )

                    - COALESCE(p.bpjs_kes_4_percent, 0)
                    - COALESCE(p.bpjs_kes_1_percent, 0)
                ) AS total_potongan,

                /* =========================
                Gaji Bersih = total_penerimaan + total_potongan
                ========================= */
                (
                    (
                        COALESCE(p.gaji_pokok, 0)
                        + COALESCE(p.monthly_kpi, 0)
                        + COALESCE(p.overtime, 0)
                        + COALESCE(p.medical_reimbursement, 0)

                        + (
                            COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
                            + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
                            + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
                            + COALESCE(p.bpjs_tk_jp_2_percent, 0)
                        )
                        + COALESCE(p.bpjs_kes_4_percent, 0)

                        + (
                            CASE
                                WHEN p.salary_type = 'nett' THEN
                                    (
                                        COALESCE(p.bpjs_tk_jht_2_percent, 0)
                                        + COALESCE(p.bpjs_tk_jp_1_percent, 0)
                                        + COALESCE(p.bpjs_tenaga_kerja, 0)
                                    )
                                    +
                                    (
                                        COALESCE(p.bpjs_kes_1_percent, 0)
                                        + COALESCE(p.bpjs_kesehatan, 0)
                                    )
                                ELSE 0
                            END
                        )

                        + COALESCE(p.insentif_sholat, 0)
                        + COALESCE(p.monthly_bonus, 0)
                        + COALESCE(p.rapel, 0)
                        + COALESCE(p.tunjangan_pulsa, 0)
                        + COALESCE(p.tunjangan_kehadiran, 0)
                        + COALESCE(p.tunjangan_transport, 0)
                        + COALESCE(p.tunjangan_lainnya, 0)
                        + COALESCE(p.yearly_bonus, 0)
                        + COALESCE(p.thr, 0)
                        + COALESCE(p.other, 0)

                        + (CASE WHEN p.salary_type = 'nett' THEN COALESCE(p.pph_21, 0) ELSE 0 END)
                    )
                    +
                    (
                        COALESCE(p.ca_corporate, 0)
                        + COALESCE(p.ca_personal, 0)
                        + COALESCE(p.ca_kehadiran, 0)

                        + CASE
                            WHEN p.salary_type = 'nett' THEN -COALESCE(p.pph_21, 0)
                            ELSE -COALESCE(p.pph_21_deduction, 0)
                        END

                        - (
                            COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
                            + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
                            + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
                            + COALESCE(p.bpjs_tk_jp_2_percent, 0)
                        )
                        - (
                            COALESCE(p.bpjs_tk_jht_2_percent, 0)
                            + COALESCE(p.bpjs_tk_jp_1_percent, 0)
                        )
                        - COALESCE(p.bpjs_kes_4_percent, 0)
                        - COALESCE(p.bpjs_kes_1_percent, 0)
                    )
                ) AS gaji_bersih

            FROM payrolls_merge p
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS payroll_calculations_merge");
        DB::statement("DROP VIEW IF EXISTS payrolls_merge");
    }
};