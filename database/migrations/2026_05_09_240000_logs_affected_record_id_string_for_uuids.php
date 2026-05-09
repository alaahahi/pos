<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('logs')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE logs MODIFY affected_record_id VARCHAR(36) NULL');

            return;
        }

        if ($driver === 'sqlite') {
            $this->sqliteRebuildLogsAffectedRecordIdAsString();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('logs')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE logs MODIFY affected_record_id BIGINT UNSIGNED NULL');

            return;
        }

        if ($driver === 'sqlite') {
            $rows = DB::table('logs')->get();
            Schema::drop('logs');
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('module_name');
                $table->string('action');
                $table->string('badge');
                $table->unsignedBigInteger('affected_record_id')->nullable();
                $table->json('original_data')->nullable();
                $table->json('updated_data')->nullable();
                $table->unsignedBigInteger('by_user_id')->nullable();
                $table->timestamps();
                $table->index('by_user_id');
            });
            foreach ($rows as $row) {
                $aid = $row->affected_record_id;
                DB::table('logs')->insert([
                    'id' => $row->id,
                    'module_name' => $row->module_name,
                    'action' => $row->action,
                    'badge' => $row->badge,
                    'affected_record_id' => is_numeric($aid) ? (int) $aid : 0,
                    'original_data' => $row->original_data,
                    'updated_data' => $row->updated_data,
                    'by_user_id' => $row->by_user_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }
    }

    protected function sqliteRebuildLogsAffectedRecordIdAsString(): void
    {
        $rows = DB::table('logs')->get();
        Schema::drop('logs');
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('module_name');
            $table->string('action');
            $table->string('badge');
            $table->string('affected_record_id', 36)->nullable();
            $table->json('original_data')->nullable();
            $table->json('updated_data')->nullable();
            $table->unsignedBigInteger('by_user_id')->nullable();
            $table->timestamps();
            $table->index('by_user_id');
        });

        foreach ($rows as $row) {
            $aid = $row->affected_record_id ?? null;
            DB::table('logs')->insert([
                'id' => $row->id,
                'module_name' => $row->module_name,
                'action' => $row->action,
                'badge' => $row->badge,
                'affected_record_id' => $aid !== null && $aid !== '' ? (string) $aid : null,
                'original_data' => $row->original_data,
                'updated_data' => $row->updated_data,
                'by_user_id' => $row->by_user_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        $maxId = DB::table('logs')->max('id');
        if ($maxId) {
            DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', ['logs']);
            DB::insert('INSERT INTO sqlite_sequence (name, seq) VALUES (?, ?)', ['logs', $maxId]);
        }
    }
};
