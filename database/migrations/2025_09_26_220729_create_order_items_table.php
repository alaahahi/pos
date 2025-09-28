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
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->integer('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total_price', 10, 2);
                $table->timestamps();
                
                $table->index(['order_id', 'product_id']);
            });
        } else {
            // Table exists, modify it safely
            Schema::table('order_items', function (Blueprint $table) {
                // Add columns that don't exist
                if (!Schema::hasColumn('order_items', 'order_id')) {
                    $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                }
                if (!Schema::hasColumn('order_items', 'product_id')) {
                    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                }
                if (!Schema::hasColumn('order_items', 'quantity')) {
                    $table->integer('quantity');
                }
                if (!Schema::hasColumn('order_items', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2);
                }
                if (!Schema::hasColumn('order_items', 'total_price')) {
                    $table->decimal('total_price', 10, 2);
                }
                if (!Schema::hasColumn('order_items', 'created_at')) {
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
        Schema::dropIfExists('order_items');
    }
};