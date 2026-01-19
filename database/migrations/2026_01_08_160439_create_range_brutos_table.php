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
        Schema::create('range_brutos', function (Blueprint $table) {
            $table->id();
             // ✅ IMPORTANT: ID dari aplikasi ABSEN (foreign reference)
            $table->unsignedBigInteger('absen_range_bruto_id')->unique();
            $table->unsignedBigInteger('absen_jenis_ter_id'); // ID Jenis TER dari ABSEN
            
            
            // Data Range Bruto (sesuai struktur dari API ABSEN)
            $table->unsignedBigInteger('min_bruto');
            $table->unsignedBigInteger('max_bruto')->nullable();
            $table->decimal('ter', 5, 2);
            
            // Sync metadata
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('absen_range_bruto_id');
            $table->index('absen_jenis_ter_id');
            $table->index('min_bruto');
            $table->index('max_bruto');
            $table->index('last_synced_at');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('range_brutos');
    }
};
