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
        Schema::create('companies', function (Blueprint $table) {
             $table->id(); // Local ID di aplikasi GAJI
            
            // ✅ IMPORTANT: ID dari aplikasi ABSEN (foreign reference)
            $table->unsignedBigInteger('absen_company_id')->unique();
            
            // Data Company (sesuai struktur dari API ABSEN)
            $table->unsignedBigInteger('user_id')->nullable(); // user pembuat/admin
            $table->string('code', 20)->nullable(); // Allow nullable karena mungkin ada yang kosong
            $table->string('company_name', 150);
            $table->string('logo', 150)->nullable();
            $table->string('ttd', 150)->nullable();
            $table->string('nama_ttd', 150)->nullable();
            $table->string('jabatan_ttd', 150)->nullable();
            
            // Sync metadata
            $table->timestamp('last_synced_at')->nullable(); // Kapan terakhir di-sync
            $table->text('sync_metadata')->nullable(); // Metadata tambahan dari API
            
            $table->timestamps();
            $table->softDeletes(); // Untuk track deleted records
            
            // Indexes
            $table->index('absen_company_id');
            $table->index('code');
            $table->index('user_id');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
