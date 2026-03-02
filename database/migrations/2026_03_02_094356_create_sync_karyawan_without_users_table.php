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
        Schema::create('sync_karyawan_without_users', function (Blueprint $table) {
            $table->id();

            // ── Key utama — id dari tabel karyawans apps absen ────
            // Ini yang akan jadi absen_karyawan_id di tabel karyawans apps gaji
            $table->unsignedBigInteger('absen_karyawan_id')->unique();

            // ── Data karyawan dari apps absen ─────────────────────
            $table->string('nik', 50)->nullable();
            $table->string('nama_lengkap', 200);
            $table->string('email_pribadi', 150)->nullable();
            $table->string('telp_pribadi', 30)->nullable();
            $table->date('join_date')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('status_pernikahan', 50)->nullable();
            $table->string('tempat_tanggal_lahir', 200)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_ktp', 20)->nullable();
            $table->boolean('status_resign')->default(false);

            // ── Sync metadata ─────────────────────────────────────
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // Softdelete:
            // - Jika karyawan sudah dapat user di apps absen
            //   → tidak muncul di API lagi → softdelete di sini
            // - Jika muncul lagi (user dihapus) → restore
            $table->softDeletes();

            // Indexes untuk search
            $table->index('nama_lengkap');
            $table->index('nik');
            $table->index('absen_karyawan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_karyawan_without_users');
    }
};
