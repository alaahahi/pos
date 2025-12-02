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
        Schema::create('monthly_closes', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->tinyInteger('month'); // 1-12
            $table->decimal('total_sales_usd', 15, 2)->default(0);
            $table->decimal('total_sales_iqd', 15, 2)->default(0);
            $table->decimal('total_expenses_usd', 15, 2)->default(0);
            $table->decimal('total_expenses_iqd', 15, 2)->default(0);
            $table->decimal('opening_balance_usd', 15, 2)->default(0);
            $table->decimal('opening_balance_iqd', 15, 2)->default(0);
            $table->decimal('closing_balance_usd', 15, 2)->default(0);
            $table->decimal('closing_balance_iqd', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_closes');
    }
};

