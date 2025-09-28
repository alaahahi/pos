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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_pay')->default(false);
            $table->unsignedBigInteger('morphed_id')->nullable();
            $table->string('morphed_type')->nullable();
            $table->string('currency', 3)->default('IQD');
            $table->date('created')->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys will be added later when wallets table is created
            // $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            // $table->foreign('parent_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
