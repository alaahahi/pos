<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->string('vin', 17)->unique();
                $table->string('color')->nullable();
                $table->string('vehicle_model')->nullable();
                $table->string('make')->nullable();
                $table->string('year', 4)->nullable();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
                $table->foreignId('purchase_invoice_item_id')->nullable()->constrained('purchase_invoice_items')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->enum('status', ['available', 'sold'])->default('available');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['status', 'product_id']);
            });
        }

        if (!Schema::hasColumn('order_product', 'vehicle_id')) {
            Schema::table('order_product', function (Blueprint $table) {
                try {
                    $table->dropUnique(['order_id', 'product_id']);
                } catch (\Throwable $e) {
                    // SQLite may use a different index name or lack the constraint
                }
            });

            Schema::table('order_product', function (Blueprint $table) {
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_product', 'vehicle_id')) {
            Schema::table('order_product', function (Blueprint $table) {
                $table->dropForeign(['vehicle_id']);
                $table->dropColumn('vehicle_id');
            });

            Schema::table('order_product', function (Blueprint $table) {
                try {
                    $table->unique(['order_id', 'product_id']);
                } catch (\Throwable $e) {
                    //
                }
            });
        }

        Schema::dropIfExists('vehicles');
    }
};
