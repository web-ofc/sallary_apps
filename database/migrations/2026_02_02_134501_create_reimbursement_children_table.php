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
            $table->date('tanggal')->nullable(); // Status approved
            $table->string('nama_reimbursement')->nullable(); // nama dia/anak/istri
            $table->string('status_keluarga')->nullable(); // Untuk diri sendiri/istri/anak/dll
            $table->string('jenis_penyakit')->nullable(); // Jenis penyakit
            $table->integer('tagihan_dokter')->nullable(); // tagihan_dokter
            $table->integer('tagihan_obat')->nullable(); // tagihan_obat
            $table->integer('tagihan_kacamata')->nullable(); // tagihan_kacamata
            $table->integer('tagihan_gigi')->nullable(); // tagihan_gigi
            $table->string('note')->nullable(); 
            $table->timestamps();
            
            // Index untuk performa query
            $table->index('reimbursement_id');
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
