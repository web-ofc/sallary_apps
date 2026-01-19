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
        Schema::create('pph21_tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('min_pkp');
            $table->unsignedBigInteger('max_pkp')->nullable(); // null = no upper limit
            $table->decimal('rate_percent', 5, 2); // contoh: 5.00, 15.00, 25.00
            $table->unsignedInteger('order_index');
            $table->text('description')->nullable();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pph21_tax_brackets');
    }
};
