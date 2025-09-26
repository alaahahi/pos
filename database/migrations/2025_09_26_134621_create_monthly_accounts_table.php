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
        Schema::create('monthly_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_orders_amount', 15, 2)->default(0);
            $table->decimal('total_payments_received', 15, 2)->default(0);
            $table->decimal('total_payments_paid', 15, 2)->default(0);
            $table->decimal('total_balance_dollar', 15, 2)->default(0);
            $table->decimal('total_balance_dinar', 15, 2)->default(0);
            $table->decimal('net_profit_dollar', 15, 2)->default(0);
            $table->decimal('net_profit_dinar', 15, 2)->default(0);
            $table->decimal('opening_balance_dollar', 15, 2)->default(0);
            $table->decimal('opening_balance_dinar', 15, 2)->default(0);
            $table->decimal('closing_balance_dollar', 15, 2)->default(0);
            $table->decimal('closing_balance_dinar', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            // Unique constraint for year-month combination
            $table->unique(['year', 'month']);
            
            // Indexes
            $table->index(['year', 'month']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_accounts');
    }
};