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
        if (!Schema::hasTable('customer_balances')) {
            Schema::create('customer_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->decimal('balance_dollar', 15, 2)->default(0);
                $table->decimal('balance_dinar', 15, 2)->default(0);
                $table->date('last_transaction_date')->nullable();
                $table->enum('currency_preference', ['dollar', 'dinar'])->default('dinar');
                $table->timestamps();
            
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_balances');
    }
};
