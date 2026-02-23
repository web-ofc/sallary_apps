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
        Schema::create('master_reimbursement_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Misal: KACAMATA, OBAT_RESEP, dll
            $table->string('medical_type'); // Nama jenis medical
            $table->string('group_medical'); // Other/General
            $table->timestamps();
            
            // Index
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_reimbursement_types');
    }
};
