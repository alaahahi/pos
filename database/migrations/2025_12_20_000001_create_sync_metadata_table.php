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
        if (!Schema::hasTable('sync_metadata')) {
            Schema::create('sync_metadata', function (Blueprint $table) {
                $table->id();
                $table->string('table_name');
                $table->enum('direction', ['up', 'down']); // up = SQLite to MySQL, down = MySQL to SQLite
                $table->unsignedBigInteger('last_synced_id')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamp('last_updated_at')->nullable();
                $table->unsignedInteger('total_synced')->default(0);
                $table->timestamps();

                // Index for fast lookups
                $table->unique(['table_name', 'direction']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_metadata');
    }
};

