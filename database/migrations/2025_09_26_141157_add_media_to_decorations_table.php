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
        Schema::table('decorations', function (Blueprint $table) {
            $table->json('images')->nullable()->after('description');
            $table->string('video_url')->nullable()->after('images');
            $table->string('thumbnail')->nullable()->after('video_url');
            $table->boolean('is_featured')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decorations', function (Blueprint $table) {
            $table->dropColumn(['images', 'video_url', 'thumbnail', 'is_featured']);
        });
    }
};
