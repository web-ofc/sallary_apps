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
        Schema::create('reimbursement_periods', function (Blueprint $table) {
            $table->id();
            $table->string('periode'); // Format: '2025 - 2026'
            $table->date('expired_reimburs_start'); // Tanggal mulai periode expired reimbursement
            $table->date('end_reimburs_start'); // Tanggal akhir periode expired reimbursement
            $table->timestamps();
            
            // Index untuk performa query
            $table->index('periode');
            $table->index(['expired_reimburs_start', 'end_reimburs_start'], 'idx_period_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursement_periods');
    }
};
