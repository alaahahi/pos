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
        Schema::create('system_config', function (Blueprint $table) {
            $table->id();
            $table->string('first_title_ar')->nullable();
            $table->string('first_title_kr')->nullable();
            $table->string('second_title_ar')->nullable();
            $table->string('second_title_kr')->nullable();
            $table->string('third_title_ar')->nullable();
            $table->string('third_title_kr')->nullable();
            $table->json('default_price_s')->nullable();
            $table->json('default_price_p')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_config');
    }
};
