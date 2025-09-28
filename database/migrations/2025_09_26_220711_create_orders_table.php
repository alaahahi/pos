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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('final_amount', 10, 2)->default(0);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->decimal('change_amount', 10, 2)->default(0);
                $table->string('payment_method')->default('cash');
                $table->string('status')->default('completed'); // pending, completed, cancelled
                $table->text('notes')->nullable();
                $table->string('order_number')->unique()->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index(['customer_id', 'created_at']);
                $table->index(['status', 'created_at']);
            });
        } else {
            // Table exists, modify it safely
            Schema::table('orders', function (Blueprint $table) {
                // Add columns that don't exist
                if (!Schema::hasColumn('orders', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                }
                if (!Schema::hasColumn('orders', 'total_amount')) {
                    $table->decimal('total_amount', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('orders', 'discount_amount')) {
                    $table->decimal('discount_amount', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('orders', 'final_amount')) {
                    $table->decimal('final_amount', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('orders', 'amount_paid')) {
                    $table->decimal('amount_paid', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('orders', 'change_amount')) {
                    $table->decimal('change_amount', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('orders', 'payment_method')) {
                    $table->string('payment_method')->default('cash');
                }
                if (!Schema::hasColumn('orders', 'status')) {
                    $table->string('status')->default('completed');
                }
                if (!Schema::hasColumn('orders', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (!Schema::hasColumn('orders', 'order_number')) {
                    $table->string('order_number')->unique()->nullable();
                }
                if (!Schema::hasColumn('orders', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('orders', 'created_at')) {
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
        Schema::dropIfExists('orders');
    }
};