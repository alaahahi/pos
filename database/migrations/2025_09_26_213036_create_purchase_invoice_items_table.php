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
        if (!Schema::hasTable('purchase_invoice_items')) {
            Schema::create('purchase_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->integer('quantity');
                $table->decimal('cost_price', 10, 2);
                $table->decimal('sales_price', 10, 2);
                $table->decimal('total', 10, 2);
                $table->timestamps();
                
                // Indexes
                $table->index('purchase_invoice_id');
                $table->index('product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
