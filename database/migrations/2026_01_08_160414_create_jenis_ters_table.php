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
        Schema::create('jenis_ters', function (Blueprint $table) {
            $table->id();

            // ✅ IMPORTANT: ID dari aplikasi ABSEN (foreign reference)
            $table->unsignedBigInteger('absen_jenis_ter_id')->unique();
            
            // Data Jenis TER (sesuai struktur dari API ABSEN)
            $table->string('jenis_ter');
            
            // Sync metadata
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('absen_jenis_ter_id');
            $table->index('jenis_ter');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_ters');
    }
};
