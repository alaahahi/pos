<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = 'sync_sqlite';
        if (!array_key_exists($connection, config('database.connections', []))) {
            return;
        }

        if (!Schema::connection($connection)->hasTable('categories')) {
            return;
        }

        // إصلاح categories.id إذا كان رقمي (INTEGER) بدل نص UUID
        $this->fixColumnTypeInSqlite($connection, 'categories', 'id', makePrimaryKeyText: true);

        // إصلاح products.category_id إذا كان رقمي ليتوافق مع UUID
        if (Schema::connection($connection)->hasTable('products') && Schema::connection($connection)->hasColumn('products', 'category_id')) {
            $this->fixColumnTypeInSqlite($connection, 'products', 'category_id', makePrimaryKeyText: false);
        }
    }

    public function down(): void
    {
        // no-op
    }

    /**
     * يعيد إنشاء الجدول في SQLite مع تحويل نوع العمود المطلوب إلى TEXT.
     */
    private function fixColumnTypeInSqlite(string $connection, string $table, string $column, bool $makePrimaryKeyText = false): void
    {
        $columns = DB::connection($connection)->select("PRAGMA table_info('{$table}')");
        if (empty($columns)) {
            return;
        }

        $target = collect($columns)->first(fn ($c) => ($c->name ?? null) === $column);
        if (!$target) {
            return;
        }

        $currentType = strtoupper((string) ($target->type ?? ''));
        if (str_contains($currentType, 'TEXT') || str_contains($currentType, 'CHAR') || str_contains($currentType, 'VARCHAR')) {
            return; // النوع صحيح مسبقاً
        }

        $tmp = "__tmp_{$table}_" . time();
        $defs = [];
        $selects = [];

        foreach ($columns as $col) {
            $name = $col->name;
            $isTarget = $name === $column;
            $isPk = (int) ($col->pk ?? 0) === 1;
            $notNull = (int) ($col->notnull ?? 0) === 1;
            $default = $col->dflt_value;

            if ($isTarget) {
                if ($makePrimaryKeyText || $isPk) {
                    $defs[] = "`{$name}` TEXT PRIMARY KEY";
                } else {
                    $defs[] = "`{$name}` TEXT" . ($notNull ? ' NOT NULL' : '');
                }
                $selects[] = "CAST(`{$name}` AS TEXT) AS `{$name}`";
                continue;
            }

            $type = (string) ($col->type ?: 'TEXT');
            $def = "`{$name}` {$type}";
            if ($isPk) {
                $def .= ' PRIMARY KEY';
            }
            if ($notNull) {
                $def .= ' NOT NULL';
            }
            if ($default !== null) {
                $def .= " DEFAULT {$default}";
            }
            $defs[] = $def;
            $selects[] = "`{$name}`";
        }

        DB::connection($connection)->statement('PRAGMA foreign_keys = OFF');
        try {
            DB::connection($connection)->statement("CREATE TABLE `{$tmp}` (" . implode(', ', $defs) . ")");
            DB::connection($connection)->statement(
                "INSERT INTO `{$tmp}` (" . implode(', ', array_map(fn ($c) => "`{$c->name}`", $columns)) . ")
                 SELECT " . implode(', ', $selects) . " FROM `{$table}`"
            );
            DB::connection($connection)->statement("DROP TABLE `{$table}`");
            DB::connection($connection)->statement("ALTER TABLE `{$tmp}` RENAME TO `{$table}`");
        } finally {
            DB::connection($connection)->statement('PRAGMA foreign_keys = ON');
        }
    }
};

