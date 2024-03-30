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
        Schema::create('hpp_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('description', ['Pembelian', 'Penjualan']);
            $table->date('date');
            $table->integer('qty');
            $table->double('cost');
            $table->double('price');
            $table->double('total_cost');
            $table->integer('qty_balance');
            $table->double('value_balance');
            $table->double('hpp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hpp_transactions');
    }
};
