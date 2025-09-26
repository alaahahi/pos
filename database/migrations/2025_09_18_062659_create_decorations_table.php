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
        // This migration is a duplicate of 2025_09_18_062650_create_decorations_table
        // Skip if table already exists
        if (!Schema::hasTable('decorations')) {
            Schema::create('decorations', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // اسم الديكور
                $table->text('description')->nullable(); // وصف الديكور
                $table->enum('type', [
                    'birthday', 'gender_reveal', 'baby_shower', 
                    'wedding', 'graduation', 'corporate', 'religious'
                ]); // نوع الديكور
                $table->decimal('base_price', 10, 2); // السعر الأساسي
                $table->json('pricing_details')->nullable(); // تفاصيل التسعير (مواد، عمالة، نقل)
                $table->json('included_items')->nullable(); // العناصر المشمولة
                $table->json('optional_items')->nullable(); // العناصر الاختيارية
                $table->integer('duration_hours')->default(4); // مدة التنفيذ بالساعات
                $table->integer('team_size')->default(2); // عدد الفريق المطلوب
                $table->string('image')->nullable(); // صورة الديكور
                $table->boolean('is_active')->default(true); // حالة النشاط
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decorations');
    }
};
