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
        Schema::create('reimbursement_files', function (Blueprint $table) {
            $table->id();
            $table->year('year'); // Tahun
            $table->unsignedBigInteger('karyawan_id'); // FK ke absen_karyawan_id
            $table->string('file', 255); // Path file (1 record = 1 file)
            $table->timestamps();
            
            // Indexes
            $table->index(['karyawan_id', 'year']);
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursement_files');
    }
};
