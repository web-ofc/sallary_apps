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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->string('id_recapan')->unique(); // ID untuk recap/grouping
            $table->unsignedBigInteger('karyawan_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->year('year_budget'); // Tahun budget yang dipakai
            $table->string('periode_slip'); // Format: '2025-01'
            $table->unsignedBigInteger('approved_id')->default(6);
            $table->foreignId('user_by_id')->nullable();

            $table->date('approved_at')->nullable(); // Status approved
            $table->boolean('status')->default(false); // Status approved/pending
            $table->timestamps();
            $table->softDeletes();
            
            // Index untuk performa query
            $table->index(['karyawan_id', 'year_budget']);
            $table->index('periode_slip');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
