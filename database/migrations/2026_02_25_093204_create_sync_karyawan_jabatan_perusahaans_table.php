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
        Schema::create('sync_karyawan_jabatan_perusahaans', function (Blueprint $table) {
             $table->id();

            // ── Karyawan ──────────────────────────────────────────
            // Pakai absen_karyawan_id sebagai key utama, bukan id lokal
            $table->unsignedBigInteger('absen_karyawan_id')->unique();
            $table->string('nik', 50)->nullable();
            $table->string('nama_lengkap', 200);
            $table->string('email', 150)->nullable();
            $table->date('join_date')->nullable();

            // ── Jabatan terbaru (dari mutasi_jabatans) ────────────
            $table->unsignedBigInteger('absen_jabatan_id')->nullable();
            $table->string('kode_jabatan', 50)->nullable();
            $table->string('nama_jabatan', 150)->nullable();
            $table->date('tgl_mutasi_jabatan')->nullable();

            // ── Company terbaru (dari mutasi_perusahaans) ─────────
            // Ini adalah suggestion — yang masuk reimbursements.company_id
            // tetap absen_company_id yang dipilih user
            $table->unsignedBigInteger('absen_company_id')->nullable();
            $table->string('company_code', 50)->nullable();
            $table->string('company_name', 200)->nullable();
            $table->string('company_logo', 300)->nullable();
            $table->date('tgl_mutasi_perusahaan')->nullable();

            // ── Sync metadata ─────────────────────────────────────
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // ✅ jika di API tidak ada lagi → softdelete
                                   //    jika muncul lagi → restore (hapus deleted_at)

            // Indexes
            $table->index('absen_karyawan_id');
            $table->index('absen_company_id');
            $table->index('nama_lengkap');
            $table->index('nik');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_karyawan_jabatan_perusahaans');
    }
};
