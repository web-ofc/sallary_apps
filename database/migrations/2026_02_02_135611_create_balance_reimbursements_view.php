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
            -- DROP VIEW IF EXISTS balance_reimbursements;
            CREATE OR REPLACE VIEW balance_reimbursements AS
            SELECT 
                ms.karyawan_id,
                ms.salary AS budget_claim,  -- ✅ Ambil dari salary terbaru (by created_at)
                ms.year,
                COALESCE(reimbursement_total.total_used, 0) AS total_used,
                ms.salary - COALESCE(reimbursement_total.total_used, 0) AS sisa_budget
            FROM (
                -- Ambil salary TERBARU per karyawan per tahun berdasarkan created_at
                SELECT 
                    ms1.karyawan_id,
                    ms1.year,
                    ms1.salary,
                    ms1.created_at
                FROM master_salaries ms1
                INNER JOIN (
                    -- Cari created_at terbaru per karyawan per tahun
                    SELECT 
                        karyawan_id,
                        year,
                        MAX(created_at) AS latest_created_at
                    FROM master_salaries
                    WHERE status_medical = '1'
                    GROUP BY karyawan_id, year
                ) ms2 ON ms1.karyawan_id = ms2.karyawan_id 
                    AND ms1.year = ms2.year 
                    AND ms1.created_at = ms2.latest_created_at
                WHERE ms1.status_medical = '1'
            ) AS ms
            -- Inner join dengan periode aktif
            INNER JOIN (
                SELECT 
                    CAST(SUBSTRING_INDEX(periode, ' - ', 1) AS UNSIGNED) AS start_year,
                    CAST(SUBSTRING_INDEX(periode, ' - ', -1) AS UNSIGNED) AS end_year
                FROM reimbursement_periods
                WHERE CURDATE() BETWEEN expired_reimburs_start AND end_reimburs_start
                LIMIT 1
            ) AS active_period 
                ON ms.year BETWEEN active_period.start_year AND active_period.end_year
            -- Left join dengan total reimbursement yang sudah approved
            LEFT JOIN (
                SELECT 
                    r.karyawan_id,
                    r.year_budget,
                    SUM(rc.harga) AS total_used
                FROM reimbursements r
                INNER JOIN reimbursement_childs rc ON rc.reimbursement_id = r.id
                WHERE r.status = 1
                GROUP BY r.karyawan_id, r.year_budget
            ) AS reimbursement_total 
                ON reimbursement_total.karyawan_id = ms.karyawan_id 
                AND reimbursement_total.year_budget = ms.year;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_reimbursements_view');
    }
};
