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
        Schema::create('supplier_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('balance_dollar', 15, 2)->default(0);
            $table->decimal('balance_dinar', 15, 2)->default(0);
            $table->date('last_transaction_date')->nullable();
            $table->enum('currency_preference', ['dollar', 'dinar'])->default('dinar');
            $table->timestamps();
        
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_balances');
    }
};

