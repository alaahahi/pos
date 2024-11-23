<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('oe_number')->nullable();
            $table->string('situation')->nullable();
            $table->decimal('price_cost', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('price_with_transport', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->string('balen')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes(); // Soft delete column
            $table->timestamps(); // Created at and updated at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}
