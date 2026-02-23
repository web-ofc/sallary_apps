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
        Schema::create('reimbursement_childs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reimbursement_id')->constrained('reimbursements')->onDelete('cascade');
            $table->foreignId('reimbursement_type_id')->constrained('master_reimbursement_types')->onDelete('cascade');
            $table->integer('harga'); // Harga/nominal reimbursement
            $table->string('jenis_penyakit')->nullable(); // Jenis penyakit
            $table->string('status_keluarga')->nullable(); // Untuk diri sendiri/istri/anak/dll
            $table->string('note')->nullable(); // Format: '2025-01'
            $table->timestamps();
            
            // Index untuk performa query
            $table->index('reimbursement_id');
            $table->index('reimbursement_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursement_children');
    }
};
