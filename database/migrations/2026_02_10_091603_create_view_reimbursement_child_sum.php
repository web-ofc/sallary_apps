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
            CREATE VIEW reimbursement_child_sum AS
            SELECT
                r.id AS id,
                r.id_recapan,
                r.karyawan_id,
                r.company_id,
                r.year_budget,
                r.periode_slip,
                r.status,
                COALESCE(SUM(rc.harga), 0) AS total_harga
            FROM reimbursements r
            LEFT JOIN reimbursement_childs rc 
                ON rc.reimbursement_id = r.id
            WHERE r.deleted_at IS NULL  -- ✅ TAMBAH INI: Exclude soft deleted reimbursements
            GROUP BY
                r.id,
                r.id_recapan,
                r.karyawan_id,
                r.company_id,
                r.year_budget,
                r.periode_slip,
                r.status;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('view_reimbursement_child_sum');
    }
};
