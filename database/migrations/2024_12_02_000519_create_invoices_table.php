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
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->decimal('total_amount', 10, 2);
                $table->string('status')->default('pending');
                $table->string('payment_method');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        } else {
            // Table exists, modify it safely
            Schema::table('invoices', function (Blueprint $table) {
                // Add columns that don't exist
                if (!Schema::hasColumn('invoices', 'customer_id')) {
                    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('invoices', 'total_amount')) {
                    $table->decimal('total_amount', 10, 2);
                }
                if (!Schema::hasColumn('invoices', 'status')) {
                    $table->string('status')->default('pending');
                }
                if (!Schema::hasColumn('invoices', 'payment_method')) {
                    $table->string('payment_method');
                }
                if (!Schema::hasColumn('invoices', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (!Schema::hasColumn('invoices', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
