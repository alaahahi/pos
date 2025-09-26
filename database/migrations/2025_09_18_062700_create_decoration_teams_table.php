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
        Schema::create('decoration_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الفريق
            $table->text('description')->nullable(); // وصف الفريق
            $table->json('members')->nullable(); // أعضاء الفريق
            $table->json('specialties')->nullable(); // تخصصات الفريق
            $table->decimal('hourly_rate', 8, 2)->default(50); // معدل الساعة
            $table->boolean('is_available')->default(true); // حالة التوفر
            $table->integer('max_orders_per_day')->default(2); // الحد الأقصى للطلبات يومياً
            $table->json('working_hours')->nullable(); // ساعات العمل
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decoration_teams');
    }
};
