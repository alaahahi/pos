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
        if (!Schema::hasTable('sync_id_mapping')) {
            Schema::create('sync_id_mapping', function (Blueprint $table) {
                $table->id();
                $table->string('table_name');
                $table->unsignedBigInteger('local_id'); // ID في SQLite/المحلي
                $table->unsignedBigInteger('server_id'); // ID في MySQL/السيرفر
                $table->string('sync_direction')->default('up'); // up = local to server, down = server to local
                $table->timestamps();

                // Indexes for fast lookups
                $table->unique(['table_name', 'local_id', 'sync_direction'], 'unique_local_mapping');
                $table->unique(['table_name', 'server_id', 'sync_direction'], 'unique_server_mapping');
                $table->index(['table_name', 'local_id']);
                $table->index(['table_name', 'server_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_id_mapping');
    }
};

