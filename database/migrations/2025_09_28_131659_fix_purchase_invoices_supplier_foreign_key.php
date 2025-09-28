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
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['supplier_id']);
            
            // Add the correct foreign key constraint to suppliers table
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Drop the correct foreign key constraint
            $table->dropForeign(['supplier_id']);
            
            // Restore the old foreign key constraint to customers table
            $table->foreign('supplier_id')->references('id')->on('customers')->onDelete('set null');
        });
    }
};