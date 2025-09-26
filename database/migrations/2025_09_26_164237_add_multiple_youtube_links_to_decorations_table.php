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
            // Add multiple YouTube links support
            $table->json('youtube_links')->nullable()->after('video_url');
            
            // Keep video_url for backward compatibility but make it nullable
            $table->string('video_url')->nullable()->change();
            
            // Remove video_file column as we're disabling video upload
            $table->dropColumn('video_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decorations', function (Blueprint $table) {
            // Restore video_file column
            $table->string('video_file')->nullable()->after('video_url');
            
            // Remove youtube_links column
            $table->dropColumn('youtube_links');
        });
    }
};