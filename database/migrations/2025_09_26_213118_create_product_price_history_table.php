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
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
            $table->string('change_reason')->nullable(); // 'purchase', 'manual', 'bulk_update'
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'created_at']);
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_history');
    }
};
