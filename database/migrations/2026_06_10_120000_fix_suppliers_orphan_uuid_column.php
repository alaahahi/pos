<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers') || ! Schema::hasColumn('suppliers', 'uuid')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $this->fixMySql();
        } elseif ($driver === 'sqlite') {
            $this->fixSqlite();
        }
    }

    private function fixMySql(): void
    {
        $idColumn = DB::selectOne("SHOW COLUMNS FROM suppliers WHERE Field = 'id'");
        $idType = strtolower($idColumn->Type ?? '');

        $idIsUuid = str_contains($idType, 'char') || str_contains($idType, 'binary');

        if ($idIsUuid) {
            // id is already UUID — uuid column is leftover from a failed migration
            DB::table('suppliers')
                ->where(function ($q) {
                    $q->whereNull('uuid')->orWhere('uuid', '');
                })
                ->update(['uuid' => DB::raw('id')]);

            $this->dropUuidColumnMySql('suppliers');

            return;
        }

        // id is still integer — complete migration using raw SQL (no doctrine/dbal renameColumn)
        DB::table('suppliers')
            ->where(function ($q) {
                $q->whereNull('uuid')->orWhere('uuid', '');
            })
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('suppliers')->where('id', $row->id)->update([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                ]);
            });

        DB::statement('ALTER TABLE suppliers MODIFY uuid VARCHAR(36) NOT NULL');

        $childTables = [
            ['table' => 'purchase_invoices', 'fk' => 'supplier_id', 'onDelete' => 'SET NULL'],
            ['table' => 'supplier_balances', 'fk' => 'supplier_id', 'onDelete' => 'CASCADE'],
        ];

        foreach ($childTables as $child) {
            if (! Schema::hasTable($child['table']) || ! Schema::hasColumn($child['table'], $child['fk'])) {
                continue;
            }

            $uuidCol = $child['fk'].'_uuid';
            if (! Schema::hasColumn($child['table'], $uuidCol)) {
                Schema::table($child['table'], function (Blueprint $table) use ($child, $uuidCol) {
                    $table->string($uuidCol, 36)->nullable()->after($child['fk']);
                });
            }

            $map = DB::table('suppliers')->pluck('uuid', 'id');
            foreach (DB::table($child['table'])->whereNotNull($child['fk'])->get() as $row) {
                $uuid = $map[$row->{$child['fk']}] ?? null;
                if ($uuid) {
                    DB::table($child['table'])->where('id', $row->id)->update([$uuidCol => $uuid]);
                }
            }

            $this->dropForeignKeyIfExists($child['table'], $child['fk']);
            Schema::table($child['table'], function (Blueprint $table) use ($child) {
                $table->dropColumn($child['fk']);
            });
        }

        DB::statement('ALTER TABLE suppliers DROP PRIMARY KEY');
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        DB::statement('ALTER TABLE suppliers CHANGE uuid id VARCHAR(36) NOT NULL');
        DB::statement('ALTER TABLE suppliers ADD PRIMARY KEY (id)');

        foreach ($childTables as $child) {
            $uuidCol = $child['fk'].'_uuid';
            if (! Schema::hasTable($child['table']) || ! Schema::hasColumn($child['table'], $uuidCol)) {
                continue;
            }

            DB::statement("ALTER TABLE {$child['table']} CHANGE {$uuidCol} {$child['fk']} VARCHAR(36) NULL");

            Schema::table($child['table'], function (Blueprint $table) use ($child) {
                $table->foreign($child['fk'])->references('id')->on('suppliers')->onDelete(
                    $child['table'] === 'purchase_invoices' ? 'set null' : 'cascade'
                );
            });
        }
    }

    private function fixSqlite(): void
    {
        $cols = collect(DB::select('PRAGMA table_info(suppliers)'));
        $idCol = $cols->firstWhere('name', 'id');
        $idType = strtolower($idCol->type ?? '');

        $idIsUuid = in_array($idType, ['text', 'varchar', 'char'], true);

        if ($idIsUuid) {
            // Rebuild without uuid column
            $omit = ['uuid'];
            $qTable = '"suppliers"';
            $rows = DB::select('PRAGMA table_info(suppliers)');

            $defs = [];
            $selectParts = [];
            foreach ($rows as $col) {
                if (in_array($col->name, $omit, true)) {
                    continue;
                }
                $cn = '"'.str_replace('"', '""', $col->name).'"';
                $selectParts[] = $cn;
                $type = $col->type !== '' && $col->type !== null ? $col->type : 'TEXT';
                $line = $cn.' '.$type;
                if ((int) $col->pk === 1) {
                    $line .= ' PRIMARY KEY';
                } elseif ((int) $col->notnull === 1) {
                    $line .= ' NOT NULL';
                }
                $defs[] = $line;
            }

            $tmp = 'suppliers__fix_uuid_'.substr(sha1('suppliers'), 0, 8);
            DB::statement('DROP TABLE IF EXISTS "'.$tmp.'"');
            DB::statement('CREATE TABLE "'.$tmp.'" ('.implode(', ', $defs).')');
            DB::statement('INSERT INTO "'.$tmp.'" SELECT '.implode(', ', $selectParts).' FROM '.$qTable);
            DB::statement('DROP TABLE '.$qTable);
            DB::statement('ALTER TABLE "'.$tmp.'" RENAME TO suppliers');
        }
    }

    private function dropUuidColumnMySql(string $table): void
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = 'uuid'");
        foreach ($indexes as $index) {
            if ($index->Key_name === 'PRIMARY') {
                continue;
            }
            DB::statement("ALTER TABLE {$table} DROP INDEX `{$index->Key_name}`");
        }

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $fks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $column]
        );

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
    }

    public function down(): void
    {
        // Irreversible repair migration
    }
};
