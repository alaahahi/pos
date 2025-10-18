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
        Schema::create('employee_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('decoration_order_id');
            $table->decimal('commission_rate_percent', 6, 3)->default(0); // e.g., 5.000 = 5%
            $table->decimal('base_amount', 15, 2)->default(0); // order total or configured base
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('IQD'); // IQD or USD
            $table->enum('status', ['accrued', 'paid', 'cancelled'])->default('accrued');
            $table->date('period_month')->index(); // YYYY-MM-01 for grouping by month
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('decoration_order_id')->references('id')->on('decoration_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_commissions');
    }
};
