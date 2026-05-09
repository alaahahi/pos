<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sync_queue')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE sync_queue MODIFY record_id VARCHAR(36) NOT NULL');

            return;
        }

        if ($driver === 'sqlite') {
            $this->sqliteRebuildSyncQueueRecordIdAsString();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sync_queue')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE sync_queue MODIFY record_id BIGINT UNSIGNED NOT NULL');

            return;
        }

        if ($driver === 'sqlite') {
            $this->sqliteRebuildSyncQueueRecordIdAsInteger();
        }
    }

    protected function sqliteRebuildSyncQueueRecordIdAsString(): void
    {
        $q = '"sync_queue"';
        $tmp = 'sync_queue__rid_str_'.substr(sha1('sync_queue'), 0, 8);
        $qTmp = '"'.$tmp.'"';

        DB::statement('DROP TABLE IF EXISTS '.$qTmp);
        DB::statement(
            'CREATE TABLE '.$qTmp.' (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                table_name VARCHAR NOT NULL,
                record_id VARCHAR(36) NOT NULL,
                action VARCHAR NOT NULL,
                data TEXT,
                changes TEXT,
                status VARCHAR NOT NULL DEFAULT \'pending\',
                retry_count INTEGER NOT NULL DEFAULT 0,
                error_message TEXT,
                synced_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )'
        );
        DB::statement(
            'INSERT INTO '.$qTmp.' (id, table_name, record_id, action, data, changes, status, retry_count, error_message, synced_at, created_at, updated_at)
             SELECT id, table_name, CAST(record_id AS TEXT), action, data, changes, status, retry_count, error_message, synced_at, created_at, updated_at FROM '.$q
        );
        DB::statement('DROP TABLE '.$q);
        DB::statement('ALTER TABLE '.$qTmp.' RENAME TO '.$q);

        Schema::table('sync_queue', function (Blueprint $table) {
            $table->index(['table_name', 'record_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    protected function sqliteRebuildSyncQueueRecordIdAsInteger(): void
    {
        $q = '"sync_queue"';
        $tmp = 'sync_queue__rid_int_'.substr(sha1('sync_queue_down'), 0, 8);
        $qTmp = '"'.$tmp.'"';

        DB::statement('DROP TABLE IF EXISTS '.$qTmp);
        DB::statement(
            'CREATE TABLE '.$qTmp.' (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                table_name VARCHAR NOT NULL,
                record_id INTEGER NOT NULL,
                action VARCHAR NOT NULL,
                data TEXT,
                changes TEXT,
                status VARCHAR NOT NULL DEFAULT \'pending\',
                retry_count INTEGER NOT NULL DEFAULT 0,
                error_message TEXT,
                synced_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )'
        );
        DB::statement(
            'INSERT INTO '.$qTmp.' (id, table_name, record_id, action, data, changes, status, retry_count, error_message, synced_at, created_at, updated_at)
             SELECT id, table_name, CAST(record_id AS INTEGER), action, data, changes, status, retry_count, error_message, synced_at, created_at, updated_at FROM '.$q
        );
        DB::statement('DROP TABLE '.$q);
        DB::statement('ALTER TABLE '.$qTmp.' RENAME TO '.$q);

        Schema::table('sync_queue', function (Blueprint $table) {
            $table->index(['table_name', 'record_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }
};
