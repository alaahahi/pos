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
        Schema::create('simple_decoration_orders', function (Blueprint $table) {
            $table->id();
            $table->string('decoration_name');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->date('event_date');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('assigned_employee_id')->nullable();
            $table->text('special_requests')->nullable();
            $table->enum('status', [
                'created',
                'received',
                'executing',
                'partial_payment',
                'full_payment',
                'completed',
                'cancelled'
            ])->default('created');
            $table->string('currency')->default('dollar');
            $table->timestamps();
            
            $table->foreign('assigned_employee_id')->references('id')->on('users')->onDelete('set null');
            $table->index('event_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simple_decoration_orders');
    }
};
