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
        if (!Schema::hasTable('user_type')) {
            Schema::create('user_type', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        } else {
            // Table exists, modify it safely
            Schema::table('user_type', function (Blueprint $table) {
                if (!Schema::hasColumn('user_type', 'name')) {
                    $table->string('name')->unique();
                }
                if (!Schema::hasColumn('user_type', 'description')) {
                    $table->string('description')->nullable();
                }
                if (!Schema::hasColumn('user_type', 'created_at')) {
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
        Schema::dropIfExists('user_type');
    }
};