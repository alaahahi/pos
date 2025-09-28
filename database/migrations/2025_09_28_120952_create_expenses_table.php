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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان المصروف
            $table->text('description')->nullable(); // وصف المصروف
            $table->decimal('amount', 10, 2); // المبلغ
            $table->string('category'); // فئة المصروف (رواتب، مصاريف المحل، إلخ)
            $table->date('expense_date'); // تاريخ المصروف
            $table->unsignedBigInteger('created_by'); // المستخدم الذي أنشأ المصروف
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
