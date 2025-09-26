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
        if (!Schema::hasTable('invoice_product')) {
            Schema::create('invoice_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('quantity')->default(1);  // عدد المنتجات
                $table->decimal('price', 10, 2);  // السعر الفعلي للمنتج
                $table->timestamps();
            });
        } else {
            // Table exists, modify it safely
            Schema::table('invoice_product', function (Blueprint $table) {
                // Add columns that don't exist
                if (!Schema::hasColumn('invoice_product', 'invoice_id')) {
                    $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('invoice_product', 'product_id')) {
                    $table->foreignId('product_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('invoice_product', 'quantity')) {
                    $table->integer('quantity')->default(1);
                }
                if (!Schema::hasColumn('invoice_product', 'price')) {
                    $table->decimal('price', 10, 2);
                }
                if (!Schema::hasColumn('invoice_product', 'created_at')) {
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
        Schema::dropIfExists('invoice_product');
    }
};
