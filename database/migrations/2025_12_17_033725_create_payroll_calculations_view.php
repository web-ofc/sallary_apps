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
                p.*,
                
                -- Salary Calculation
                COALESCE(p.gaji_pokok, 0) + COALESCE(p.pph_21_deduction, 0) + COALESCE(p.pph_21, 0) AS salary,
                
                -- BPJS Income Calculations (POSITIF)
                COALESCE(p.bpjs_tk_jht_3_7_percent, 0) + 
                COALESCE(p.bpjs_tk_jkk_0_24_percent, 0) + 
                COALESCE(p.bpjs_tk_jkm_0_3_percent, 0) + 
                COALESCE(p.bpjs_tk_jp_2_percent, 0) AS bpjs_tenaga_kerja_perusahaan_income,
                
                COALESCE(p.bpjs_tk_jht_2_percent, 0) + 
                COALESCE(p.bpjs_tk_jp_1_percent, 0) + 
                COALESCE(p.bpjs_tenaga_kerja, 0) AS bpjs_tenaga_kerja_pegawai_income,
                
                COALESCE(p.bpjs_kes_4_percent, 0) AS bpjs_kesehatan_perusahaan_income,
                
                COALESCE(p.bpjs_kes_1_percent, 0) + 
                COALESCE(p.bpjs_kesehatan, 0) AS bpjs_kesehatan_pegawai_income,
                
                -- BPJS Deduction Calculations (NEGATIF)
                -(COALESCE(p.bpjs_tk_jht_3_7_percent, 0) + 
                  COALESCE(p.bpjs_tk_jkk_0_24_percent, 0) + 
                  COALESCE(p.bpjs_tk_jkm_0_3_percent, 0) + 
                  COALESCE(p.bpjs_tk_jp_2_percent, 0)) AS bpjs_tenaga_kerja_perusahaan_deduction,
                
                -(COALESCE(p.bpjs_tk_jht_2_percent, 0) + 
                  COALESCE(p.bpjs_tk_jp_1_percent, 0)) AS bpjs_tenaga_kerja_pegawai_deduction,
                
                -COALESCE(p.bpjs_kes_4_percent, 0) AS bpjs_kesehatan_perusahaan_deduction,
                
                -COALESCE(p.bpjs_kes_1_percent, 0) AS bpjs_kesehatan_pegawai_deduction,
                
                -- Total Penerimaan
                COALESCE(p.gaji_pokok, 0) + 
                COALESCE(p.monthly_kpi, 0) + 
                COALESCE(p.overtime, 0) + 
                COALESCE(p.medical_reimbursement, 0) + 
                (COALESCE(p.bpjs_tk_jht_3_7_percent, 0) + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(p.bpjs_tk_jp_2_percent, 0)) + 
                (COALESCE(p.bpjs_tk_jht_2_percent, 0) + COALESCE(p.bpjs_tk_jp_1_percent, 0) + COALESCE(p.bpjs_tenaga_kerja, 0)) + 
                COALESCE(p.bpjs_kes_4_percent, 0) + 
                (COALESCE(p.bpjs_kes_1_percent, 0) + COALESCE(p.bpjs_kesehatan, 0)) + 
                COALESCE(p.insentif_sholat, 0) + 
                COALESCE(p.monthly_bonus, 0) + 
                COALESCE(p.rapel, 0) + 
                COALESCE(p.tunjangan_pulsa, 0) + 
                COALESCE(p.tunjangan_kehadiran, 0) + 
                COALESCE(p.tunjangan_transport, 0) + 
                COALESCE(p.tunjangan_lainnya, 0) + 
                COALESCE(p.yearly_bonus, 0) + 
                COALESCE(p.thr, 0) + 
                COALESCE(p.other, 0) AS total_penerimaan,
                
                -- Total Potongan (PAKAI pph_21_deduction, BUKAN pph_21)
                COALESCE(p.ca_corporate, 0) + 
                COALESCE(p.ca_personal, 0) + 
                COALESCE(p.ca_kehadiran, 0) + 
                COALESCE(p.pph_21_deduction, 0) + 
                (-(COALESCE(p.bpjs_tk_jht_3_7_percent, 0) + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(p.bpjs_tk_jp_2_percent, 0))) + 
                (-(COALESCE(p.bpjs_tk_jht_2_percent, 0) + COALESCE(p.bpjs_tk_jp_1_percent, 0))) + 
                (-COALESCE(p.bpjs_kes_4_percent, 0)) + 
                (-COALESCE(p.bpjs_kes_1_percent, 0)) AS total_potongan,
                
                -- Gaji Bersih
                (
                    COALESCE(p.gaji_pokok, 0) + 
                    COALESCE(p.monthly_kpi, 0) + 
                    COALESCE(p.overtime, 0) + 
                    COALESCE(p.medical_reimbursement, 0) + 
                    (COALESCE(p.bpjs_tk_jht_3_7_percent, 0) + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(p.bpjs_tk_jp_2_percent, 0)) + 
                    (COALESCE(p.bpjs_tk_jht_2_percent, 0) + COALESCE(p.bpjs_tk_jp_1_percent, 0) + COALESCE(p.bpjs_tenaga_kerja, 0)) + 
                    COALESCE(p.bpjs_kes_4_percent, 0) + 
                    (COALESCE(p.bpjs_kes_1_percent, 0) + COALESCE(p.bpjs_kesehatan, 0)) + 
                    COALESCE(p.insentif_sholat, 0) + 
                    COALESCE(p.monthly_bonus, 0) + 
                    COALESCE(p.rapel, 0) + 
                    COALESCE(p.tunjangan_pulsa, 0) + 
                    COALESCE(p.tunjangan_kehadiran, 0) + 
                    COALESCE(p.tunjangan_transport, 0) + 
                    COALESCE(p.tunjangan_lainnya, 0) + 
                    COALESCE(p.yearly_bonus, 0) + 
                    COALESCE(p.thr, 0) + 
                    COALESCE(p.other, 0)
                ) + (
                    COALESCE(p.ca_corporate, 0) + 
                    COALESCE(p.ca_personal, 0) + 
                    COALESCE(p.ca_kehadiran, 0) + 
                    COALESCE(p.pph_21_deduction, 0) + 
                    (-(COALESCE(p.bpjs_tk_jht_3_7_percent, 0) + COALESCE(p.bpjs_tk_jkk_0_24_percent, 0) + COALESCE(p.bpjs_tk_jkm_0_3_percent, 0) + COALESCE(p.bpjs_tk_jp_2_percent, 0))) + 
                    (-(COALESCE(p.bpjs_tk_jht_2_percent, 0) + COALESCE(p.bpjs_tk_jp_1_percent, 0))) + 
                    (-COALESCE(p.bpjs_kes_4_percent, 0)) + 
                    (-COALESCE(p.bpjs_kes_1_percent, 0))
                ) AS gaji_bersih
                
            FROM payrolls p
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_calculations_view');
    }
};
