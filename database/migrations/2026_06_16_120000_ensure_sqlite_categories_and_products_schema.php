<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite sync DB may have incomplete category/product tables (stub init or partial sync).
 * - Missing products.deleted_at breaks Category::withCount('products') on index.
 * - INTEGER categories.id breaks UUID inserts on create (datatype mismatch).
 */
return new class extends Migration
{
    private const CONNECTION = 'sync_sqlite';

    public function up(): void
    {
        if (! array_key_exists(self::CONNECTION, config('database.connections', []))) {
            return;
        }

        if (! Schema::connection(self::CONNECTION)->hasTable('categories')) {
            return;
        }

        $this->ensureCategoryColumns();
        $this->ensureProductColumns();

        $this->fixColumnTypeInSqlite('categories', 'id', makePrimaryKeyText: true);
        if (Schema::connection(self::CONNECTION)->hasTable('products')
            && Schema::connection(self::CONNECTION)->hasColumn('products', 'category_id')) {
            $this->fixColumnTypeInSqlite('products', 'category_id', makePrimaryKeyText: false);
        }

        $this->ensureCategoriesPrimaryKey();
    }

    public function down(): void
    {
        // no-op
    }

    private function ensureCategoryColumns(): void
    {
        Schema::connection(self::CONNECTION)->table('categories', function (Blueprint $table) {
            if (! Schema::connection(self::CONNECTION)->hasColumn('categories', 'slug')) {
                $table->string('slug')->nullable();
            }
            if (! Schema::connection(self::CONNECTION)->hasColumn('categories', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::connection(self::CONNECTION)->hasColumn('categories', 'color')) {
                $table->string('color', 7)->default('#667eea');
            }
            if (! Schema::connection(self::CONNECTION)->hasColumn('categories', 'icon')) {
                $table->string('icon')->nullable();
            }
            if (! Schema::connection(self::CONNECTION)->hasColumn('categories', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (! Schema::connection(self::CONNECTION)->hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::connection(self::CONNECTION)->hasColumn('categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    private function ensureProductColumns(): void
    {
        if (! Schema::connection(self::CONNECTION)->hasTable('products')) {
            return;
        }

        Schema::connection(self::CONNECTION)->table('products', function (Blueprint $table) {
            if (! Schema::connection(self::CONNECTION)->hasColumn('products', 'category_id')) {
                $table->string('category_id', 36)->nullable();
            }
            if (! Schema::connection(self::CONNECTION)->hasColumn('products', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    private function ensureCategoriesPrimaryKey(): void
    {
        $columns = DB::connection(self::CONNECTION)->select("PRAGMA table_info('categories')");
        if (empty($columns)) {
            return;
        }

        $hasPrimaryKey = collect($columns)->contains(fn ($col) => (int) ($col->pk ?? 0) === 1);
        if ($hasPrimaryKey) {
            return;
        }

        $idColumn = collect($columns)->first(fn ($col) => ($col->name ?? null) === 'id');
        if (! $idColumn) {
            return;
        }

        $this->fixColumnTypeInSqlite('categories', 'id', makePrimaryKeyText: true);
    }

    private function fixColumnTypeInSqlite(string $table, string $column, bool $makePrimaryKeyText = false): void
    {
        $columns = DB::connection(self::CONNECTION)->select("PRAGMA table_info('{$table}')");
        if (empty($columns)) {
            return;
        }

        $target = collect($columns)->first(fn ($c) => ($c->name ?? null) === $column);
        if (! $target) {
            return;
        }

        $currentType = strtoupper((string) ($target->type ?? ''));
        $isPk = (int) ($target->pk ?? 0) === 1;
        $needsText = ! str_contains($currentType, 'TEXT')
            && ! str_contains($currentType, 'CHAR')
            && ! str_contains($currentType, 'VARCHAR')
            && ! str_contains($currentType, 'CLOB');

        if (! $needsText && ($isPk || ! $makePrimaryKeyText)) {
            return;
        }

        $tmp = "__tmp_{$table}_" . time();
        $defs = [];
        $selects = [];

        foreach ($columns as $col) {
            $name = $col->name;
            $isTarget = $name === $column;
            $colIsPk = (int) ($col->pk ?? 0) === 1;
            $notNull = (int) ($col->notnull ?? 0) === 1;
            $default = $col->dflt_value;

            if ($isTarget) {
                if ($makePrimaryKeyText || $colIsPk) {
                    $defs[] = "`{$name}` TEXT PRIMARY KEY NOT NULL";
                } else {
                    $defs[] = "`{$name}` TEXT" . ($notNull ? ' NOT NULL' : '');
                }
                $selects[] = "CAST(`{$name}` AS TEXT) AS `{$name}`";
                continue;
            }

            $type = (string) ($col->type ?: 'TEXT');
            $def = "`{$name}` {$type}";
            if ($colIsPk) {
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

        if ($makePrimaryKeyText && ! collect($columns)->contains(fn ($c) => (int) ($c->pk ?? 0) === 1)) {
            $defs = array_map(function (string $def) use ($column) {
                if (str_starts_with($def, "`{$column}`")) {
                    return "`{$column}` TEXT PRIMARY KEY NOT NULL";
                }

                return $def;
            }, $defs);
        }

        DB::connection(self::CONNECTION)->statement('PRAGMA foreign_keys = OFF');
        try {
            DB::connection(self::CONNECTION)->statement('CREATE TABLE `' . $tmp . '` (' . implode(', ', $defs) . ')');
            DB::connection(self::CONNECTION)->statement(
                'INSERT INTO `' . $tmp . '` (' . implode(', ', array_map(fn ($c) => "`{$c->name}`", $columns)) . ')
                 SELECT ' . implode(', ', $selects) . " FROM `{$table}`"
            );
            DB::connection(self::CONNECTION)->statement("DROP TABLE `{$table}`");
            DB::connection(self::CONNECTION)->statement("ALTER TABLE `{$tmp}` RENAME TO `{$table}`");
        } finally {
            DB::connection(self::CONNECTION)->statement('PRAGMA foreign_keys = ON');
        }
    }
};
