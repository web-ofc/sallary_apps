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
            CREATE OR REPLACE VIEW pph21_masa_company_periode AS
            SELECT
                p.company_id,
                c.company_name,
                p.periode,
                COUNT(p.id) AS total_karyawan,
                SUM(COALESCE(p.pph_21, 0)) AS total_pph21_masa
            FROM payrolls p
            JOIN companies c
                ON c.absen_company_id = p.company_id
            WHERE
                p.is_last_period = 0
                AND p.is_released = 1
                AND p.pph_21 IS NOT NULL
            GROUP BY
                p.company_id,
                c.company_name,
                p.periode
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('view_pph21_masa_company_periode');
    }
};
