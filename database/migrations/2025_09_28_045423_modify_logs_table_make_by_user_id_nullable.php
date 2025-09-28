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
        Schema::table('logs', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['by_user_id']);
            
            // Modify the column to be nullable
            $table->unsignedBigInteger('by_user_id')->nullable()->change();
            
            // Add the foreign key constraint back with nullable support
            $table->foreign('by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['by_user_id']);
            
            // Make the column not nullable again
            $table->unsignedBigInteger('by_user_id')->nullable(false)->change();
            
            // Add the foreign key constraint back without nullable support
            $table->foreign('by_user_id')->references('id')->on('users');
        });
    }
};
