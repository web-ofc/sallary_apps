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
        CREATE OR REPLACE VIEW reimbursement_child_sum AS
        SELECT
            r.karyawan_id,
            r.periode_slip,
            r.status,
            COUNT(r.id) AS jumlah_reimbursement,
            COALESCE(SUM(
                COALESCE(rc.tagihan_dokter, 0) +
                COALESCE(rc.tagihan_obat, 0) +
                COALESCE(rc.tagihan_kacamata, 0) +
                COALESCE(rc.tagihan_gigi, 0)
            ), 0) AS total_harga
        FROM reimbursements r
        LEFT JOIN reimbursement_childs rc 
            ON rc.reimbursement_id = r.id
        WHERE r.deleted_at IS NULL
        GROUP BY
            r.karyawan_id,
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
