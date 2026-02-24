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
     CREATE OR REPLACE VIEW payroll_calculations AS
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

    -- ini nilainya MINUS di tabel
    p.bpjs_tenaga_kerja,
    p.bpjs_kesehatan,

    -- output pph_21_deduction (positif utk tampilan)
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

    -- ✅ PTKP Status dari history berdasarkan tahun periode
    ptkp.status AS ptkp_status,
    ptkp.kriteria AS ptkp_kriteria,
    ptkp.besaran_ptkp AS ptkp_besaran,

    /* =========================
       Salary Calculation (gaya lama)
       ========================= */
    COALESCE(p.gaji_pokok, 0)
    + COALESCE(p.pph_21_deduction, 0)
    + COALESCE(p.pph_21, 0) AS salary,

    /* =========================
       ✅ Tunjangan (NETT only)
       ========================= */
    CASE
        WHEN p.salary_type = 'nett' THEN COALESCE(p.pph_21, 0)
        ELSE 0
    END AS tunjangan,

    /* =========================
       BPJS Income
       ========================= */
    (
        COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
        + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
        + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
        + COALESCE(p.bpjs_tk_jp_2_percent, 0)
    ) AS bpjs_tenaga_kerja_perusahaan_income,

    (
        COALESCE(p.bpjs_tk_jht_2_percent, 0)
        + COALESCE(p.bpjs_tk_jp_1_percent, 0)
        + COALESCE(p.bpjs_tenaga_kerja, 0)
    ) AS bpjs_tenaga_kerja_pegawai_income,

    COALESCE(p.bpjs_kes_4_percent, 0) AS bpjs_kesehatan_perusahaan_income,

    (
        COALESCE(p.bpjs_kes_1_percent, 0)
        + COALESCE(p.bpjs_kesehatan, 0)
    ) AS bpjs_kesehatan_pegawai_income,

    /* =========================
       BPJS Deduction (HARUS MINUS)
       - pegawai deduction tanpa kolom manual
       ========================= */
    -(
        COALESCE(p.bpjs_tk_jht_3_7_percent, 0)
        + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0)
        + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0)
        + COALESCE(p.bpjs_tk_jp_2_percent, 0)
    ) AS bpjs_tenaga_kerja_perusahaan_deduction,

    -(
        COALESCE(p.bpjs_tk_jht_2_percent, 0)
        + COALESCE(p.bpjs_tk_jp_1_percent, 0)
    ) AS bpjs_tenaga_kerja_pegawai_deduction,

    -COALESCE(p.bpjs_kes_4_percent, 0) AS bpjs_kesehatan_perusahaan_deduction,

    -COALESCE(p.bpjs_kes_1_percent, 0) AS bpjs_kesehatan_pegawai_deduction,

    /* =========================
       Total Penerimaan
       ========================= */
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
        + (
            COALESCE(p.bpjs_tk_jht_2_percent, 0)
            + COALESCE(p.bpjs_tk_jp_1_percent, 0)
            + COALESCE(p.bpjs_tenaga_kerja, 0)
        )
        + COALESCE(p.bpjs_kes_4_percent, 0)
        + (
            COALESCE(p.bpjs_kes_1_percent, 0)
            + COALESCE(p.bpjs_kesehatan, 0)
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
    ) AS total_penerimaan,

    /* =========================
       Total Potongan
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
       Gaji Bersih
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
            + (
                COALESCE(p.bpjs_tk_jht_2_percent, 0)
                + COALESCE(p.bpjs_tk_jp_1_percent, 0)
                + COALESCE(p.bpjs_tenaga_kerja, 0)
            )
            + COALESCE(p.bpjs_kes_4_percent, 0)
            + (
                COALESCE(p.bpjs_kes_1_percent, 0)
                + COALESCE(p.bpjs_kesehatan, 0)
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

FROM payrolls p
LEFT JOIN karyawans k ON p.karyawan_id = k.absen_karyawan_id
LEFT JOIN karyawan_ptkp_histories kph ON k.absen_karyawan_id = kph.absen_karyawan_id 
    AND YEAR(STR_TO_DATE(CONCAT(p.periode, '-01'), '%Y-%m-%d')) = kph.tahun
LEFT JOIN list_ptkps ptkp ON kph.absen_ptkp_id = ptkp.absen_ptkp_id;
");


    }

    /**
     * Reverse the migrations.
     */
        public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS payroll_calculations");
    }

};
