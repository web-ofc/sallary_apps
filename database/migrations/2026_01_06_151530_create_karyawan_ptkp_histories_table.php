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
        Schema::create('karyawan_ptkp_histories', function (Blueprint $table) {
           $table->id(); // Local ID di aplikasi GAJI
            
            // ✅ IMPORTANT: ID dari aplikasi ABSEN (foreign reference)
            $table->unsignedBigInteger('absen_ptkp_history_id')->unique()->comment('ID history dari API ABSEN');
            $table->unsignedBigInteger('absen_karyawan_id')->comment('ID karyawan dari API ABSEN');
            $table->unsignedBigInteger('absen_ptkp_id')->comment('ID PTKP dari API ABSEN');
            
            // Data history
            $table->year('tahun')->comment('Tahun berlaku PTKP');
            $table->unsignedBigInteger('absen_updated_by_id')->nullable()->comment('ID user yang update di ABSEN');
            
            // Sync metadata
            $table->timestamp('last_synced_at')->nullable()->comment('Kapan terakhir di-sync');
            $table->text('sync_metadata')->nullable()->comment('Metadata tambahan dari API');
            
            $table->timestamps();
            $table->softDeletes(); // Untuk track deleted records
            
            // Indexes
            $table->index('absen_ptkp_history_id');
            $table->index('absen_karyawan_id');
            $table->index('absen_ptkp_id');
            $table->index(['absen_karyawan_id', 'tahun'], 'idx_karyawan_tahun');
            $table->index('tahun');
            $table->index('last_synced_at');
            
        

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawan_ptkp_histories');
    }
};
