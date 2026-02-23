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
        Schema::create('master_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->onDelete('cascade');
            $table->integer('salary'); // Gaji (15 digit total, 2 desimal)
            $table->date('update_date'); // Tanggal update salary
            $table->year('year'); // Tahun berlaku
            $table->string('status_medical')->nullable(); // Status medical (misal: '0,1' atau bisa disesuaikan)
            $table->timestamps();
            
            // Index untuk performa query
            $table->index(['karyawan_id', 'year']);
            $table->index('update_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_salaries');
    }
};
