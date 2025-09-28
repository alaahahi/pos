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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('customers')->onDelete('set null'); // Assuming suppliers are also in customers table
            $table->decimal('total_amount', 10, 2);
            $table->date('invoice_date');
            $table->text('notes')->nullable();
            $table->string('invoice_number')->unique()->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes
            $table->index('supplier_id');
            $table->index('invoice_date');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
