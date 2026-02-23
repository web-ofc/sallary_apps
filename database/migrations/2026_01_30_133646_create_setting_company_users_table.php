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
        Schema::create('setting_company_users', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke tabel users (pakai primary key)
            $table->unsignedBigInteger('user_id');
            
            // Foreign key ke tabel companies (pakai absen_company_id, BUKAN primary key)
            $table->unsignedBigInteger('absen_company_id');
            
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index('user_id');
            $table->index('absen_company_id');
            
            // Unique constraint - kombinasi user_id dan absen_company_id harus unik
            // Satu user tidak bisa punya company yang sama 2x
            $table->unique(['user_id', 'absen_company_id'], 'unique_user_company');
            
            // Foreign key constraints
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // Kalau user dihapus, setting ini juga dihapus
            
            $table->foreign('absen_company_id')
                  ->references('absen_company_id') // PENTING: pakai absen_company_id bukan id
                  ->on('companies')
                  ->onDelete('cascade'); // Kalau company dihapus, setting ini juga dihapus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_company_users');
    }
};