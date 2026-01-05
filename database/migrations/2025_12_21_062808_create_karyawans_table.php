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
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            // ✅ IMPORTANT: ID dari aplikasi ABSEN (foreign reference)
            $table->unsignedBigInteger('absen_karyawan_id')->unique();
            
            // Data Karyawan (sesuai struktur dari API ABSEN)
            $table->string('nik', 50)->nullable();
            $table->string('nama_lengkap', 200);
            $table->string('email_pribadi', 150)->nullable();
            $table->string('telp_pribadi', 20)->nullable();
            $table->date('join_date')->nullable();
            $table->string('tempat_tanggal_lahir', 200)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('status_pernikahan', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_ktp', 50)->nullable();
            
            // File attachments (store path/URL)
            $table->string('file_pas_foto')->nullable();
            $table->string('file_ktp')->nullable();
            $table->string('file_kk')->nullable();
            $table->string('file_ijazah')->nullable();
            $table->string('file_npwp')->nullable();
            $table->string('file_skck')->nullable();
            $table->unsignedBigInteger('ptkp_id')->nullable();
            
            // Status & Sync metadata
            $table->boolean('status_resign')->default(false);
            $table->timestamp('last_synced_at')->nullable(); // Kapan terakhir di-sync
            $table->json('sync_metadata')->nullable(); // Metadata tambahan dari API
            
            
            $table->timestamps();
            $table->softDeletes(); // Untuk track deleted records
            
            // Indexes
            $table->index('absen_karyawan_id');
            $table->index('nik');
            $table->index('status_resign');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};
