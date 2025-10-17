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
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // اسم المورد
                $table->string('phone')->nullable(); // رقم الهاتف
                $table->string('address')->nullable(); // عنوان المورد
                $table->boolean('is_active')->default(true); // حالة النشاط
                $table->text('notes')->nullable(); // ملاحظات
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
