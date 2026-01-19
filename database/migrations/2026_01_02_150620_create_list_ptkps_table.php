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
        Schema::create('list_ptkps', function (Blueprint $table) {
            $table->id(); // Local ID di aplikasi GAJI
            
            // ✅ IMPORTANT: ID dari aplikasi ABSEN (foreign reference)
            $table->unsignedBigInteger('absen_ptkp_id')->unique();
            
            // Data PTKP (sesuai struktur dari API ABSEN)
            $table->string('kriteria')->nullable();
            $table->string('status')->nullable();
            $table->integer('besaran_ptkp')->nullable();
            $table->unsignedBigInteger('absen_jenis_ter_id');
            
            // Sync metadata
            $table->timestamp('last_synced_at')->nullable(); // Kapan terakhir di-sync
            $table->text('sync_metadata')->nullable(); // Metadata tambahan dari API
            
            $table->timestamps();
            $table->softDeletes(); // Untuk track deleted records
            
            // Indexes
            $table->index('absen_ptkp_id');
            $table->index('kriteria');
            $table->index('status');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_ptkps');
    }
};
