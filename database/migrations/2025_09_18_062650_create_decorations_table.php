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
        } else {
            // Table exists, modify it safely
            Schema::table('decorations', function (Blueprint $table) {
                // Add columns that don't exist
                if (!Schema::hasColumn('decorations', 'name')) {
                    $table->string('name');
                }
                if (!Schema::hasColumn('decorations', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('decorations', 'type')) {
                    $table->enum('type', [
                        'birthday', 'gender_reveal', 'baby_shower', 
                        'wedding', 'graduation', 'corporate', 'religious'
                    ]);
                }
                if (!Schema::hasColumn('decorations', 'base_price')) {
                    $table->decimal('base_price', 10, 2);
                }
                if (!Schema::hasColumn('decorations', 'pricing_details')) {
                    $table->json('pricing_details')->nullable();
                }
                if (!Schema::hasColumn('decorations', 'included_items')) {
                    $table->json('included_items')->nullable();
                }
                if (!Schema::hasColumn('decorations', 'optional_items')) {
                    $table->json('optional_items')->nullable();
                }
                if (!Schema::hasColumn('decorations', 'duration_hours')) {
                    $table->integer('duration_hours')->default(4);
                }
                if (!Schema::hasColumn('decorations', 'team_size')) {
                    $table->integer('team_size')->default(2);
                }
                if (!Schema::hasColumn('decorations', 'image')) {
                    $table->string('image')->nullable();
                }
                if (!Schema::hasColumn('decorations', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('decorations', 'created_at')) {
                    $table->timestamps();
                }
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
