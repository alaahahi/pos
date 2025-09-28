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
        Schema::create('cashbox_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'deposit' or 'withdrawal'
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('IQD');
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable(); // Model class name
            $table->unsignedBigInteger('reference_id')->nullable(); // Model ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('from_wallet_id')->nullable();
            $table->unsignedBigInteger('to_wallet_id')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbox_transactions');
    }
};