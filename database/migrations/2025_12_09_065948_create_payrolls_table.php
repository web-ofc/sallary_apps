<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('periode'); // contoh: '2025-01'
            $table->foreignId('karyawan_id')->nullable()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->onDelete('cascade');
            $table->integer('gaji_pokok')->nullable();
            $table->integer('monthly_kpi')->nullable();
            $table->integer('overtime')->nullable();
            $table->integer('medical_reimbursement')->nullable();
            // di header atas tabel kasih  'Monthly Insentif' namanya sampai rapel
            $table->integer('insentif_sholat')->nullable();
            $table->integer('monthly_bonus')->nullable();
            $table->integer('rapel')->nullable();
            
            // di header atas tabel kasih  'Monthly Allowance' namanya sampai rapel
            $table->integer('tunjangan_pulsa')->nullable();
            $table->integer('tunjangan_kehadiran')->nullable();
            $table->integer('tunjangan_transport')->nullable();
            $table->integer('tunjangan_lainnya')->nullable();
            
            // di header atas tabel kasih  'Yearly Benefit' namanya sampai rapel
            $table->integer('yearly_bonus')->nullable();
            $table->integer('thr')->nullable();
            $table->integer('other')->nullable();

            // di header atas tabel kasih  'Potongan' namanya sampai rapel
            $table->integer('ca_corporate')->nullable();
            $table->integer('ca_personal')->nullable();
            $table->integer('ca_kehadiran')->nullable();
            $table->integer('pph_21')->nullable();
            $table->enum('salary_type', ['gross', 'nett'])->nullable();

            // di header atas tabel kasih  'BPJS' namanya sampai rapel
            $table->integer('bpjs_tenaga_kerja')->nullable();
            $table->integer('bpjs_kesehatan')->nullable();
            $table->integer('pph_21_deduction')->nullable();
            $table->integer('bpjs_tk_jht_3_7_percent')->nullable();
            $table->integer('bpjs_tk_jht_2_percent')->nullable();
            $table->integer('bpjs_tk_jkk_0_24_percent')->nullable();
            $table->integer('bpjs_tk_jkm_0_3_percent')->nullable();
            $table->integer('bpjs_tk_jp_2_percent')->nullable();
            $table->integer('bpjs_tk_jp_1_percent')->nullable();
            $table->integer('bpjs_kes_4_percent')->nullable();
            $table->integer('bpjs_kes_1_percent')->nullable();
            
            // kolom baru
            $table->integer('glh')->nullable();
            $table->integer('lm')->nullable();
            $table->integer('lainnya')->nullable();

            $table->boolean('is_released')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
