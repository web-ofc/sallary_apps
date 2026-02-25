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
           CREATE OR REPLACE VIEW balance_reimbursements AS
          SELECT 
            ms.karyawan_id,
            ms.salary AS budget_claim,
            ms.year,
            COALESCE(reimbursement_total.total_used, 0) AS total_used,
            ms.salary - COALESCE(reimbursement_total.total_used, 0) AS sisa_budget
        FROM (
            SELECT 
                ms1.karyawan_id,
                ms1.year,
                ms1.salary,
                ms1.update_date
            FROM master_salaries ms1
            INNER JOIN (
                SELECT 
                    karyawan_id,
                    year,
                    MAX(update_date) AS latest_update_date  -- ✅ pakai update_date
                FROM master_salaries
                WHERE status_medical = '1'
                GROUP BY karyawan_id, year
            ) ms2 ON ms1.karyawan_id = ms2.karyawan_id 
                AND ms1.year = ms2.year 
                AND ms1.update_date = ms2.latest_update_date  -- ✅ join ke update_date
            WHERE ms1.status_medical = '1'
        ) AS ms
        INNER JOIN (
            SELECT 
                CAST(SUBSTRING_INDEX(periode, ' - ', 1) AS UNSIGNED) AS start_year,
                CAST(SUBSTRING_INDEX(periode, ' - ', -1) AS UNSIGNED) AS end_year
            FROM reimbursement_periods
            WHERE CURDATE() BETWEEN expired_reimburs_start AND end_reimburs_start
            LIMIT 1
        ) AS active_period 
            ON ms.year BETWEEN active_period.start_year AND active_period.end_year
        LEFT JOIN (
            SELECT 
                r.karyawan_id,
                r.year_budget,
                SUM(
                    COALESCE(rc.tagihan_dokter, 0) +
                    COALESCE(rc.tagihan_obat, 0) +
                    COALESCE(rc.tagihan_kacamata, 0) +
                    COALESCE(rc.tagihan_gigi, 0)
                ) AS total_used
            FROM reimbursements r
            INNER JOIN reimbursement_childs rc ON rc.reimbursement_id = r.id
            WHERE r.status = 1
            AND r.deleted_at IS NULL
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
