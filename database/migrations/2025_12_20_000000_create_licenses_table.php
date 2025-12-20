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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->text('license_key'); // مفتاح الترخيص المشفر
            $table->string('domain')->unique(); // Domain السيرفر
            $table->string('fingerprint')->nullable(); // Server Fingerprint
            $table->string('type')->default('standard'); // نوع الترخيص: trial, standard, premium
            $table->integer('max_installations')->default(1); // عدد التثبيتات المسموح بها
            $table->timestamp('activated_at')->nullable(); // تاريخ التفعيل
            $table->timestamp('expires_at')->nullable(); // تاريخ انتهاء الصلاحية (null = دائم)
            $table->boolean('is_active')->default(true); // حالة الترخيص
            $table->timestamp('last_verified_at')->nullable(); // آخر تحقق
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
            
            // Indexes
            $table->index('domain');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};

