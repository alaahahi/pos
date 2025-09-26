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
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_employee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', [
                'created', 'received', 'executing', 
                'partial_payment', 'full_payment', 'completed', 'cancelled'
            ])->default('created')->change();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->datetime('scheduled_date')->nullable();
            $table->datetime('received_at')->nullable();
            $table->datetime('executing_at')->nullable();
            $table->datetime('partial_payment_at')->nullable();
            $table->datetime('full_payment_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decoration_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['assigned_employee_id']);
            $table->dropColumn([
                'customer_id', 'assigned_employee_id', 'paid_amount',
                'scheduled_date', 'received_at', 'executing_at',
                'partial_payment_at', 'full_payment_at'
            ]);
            $table->enum('status', [
                'pending', 'confirmed', 'in_progress', 
                'completed', 'cancelled', 'paid'
            ])->default('pending')->change();
        });
    }
};
