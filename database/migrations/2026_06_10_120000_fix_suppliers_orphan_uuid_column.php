<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
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
        $hasUuidColumn = Schema::hasColumn('suppliers', 'uuid');
        $idColumn = DB::selectOne("SHOW COLUMNS FROM suppliers WHERE Field = 'id'");
        $idType = strtolower($idColumn->Type ?? '');
        $idExtra = strtolower($idColumn->Extra ?? '');

        $idIsUuid = str_contains($idType, 'char') || str_contains($idType, 'binary');

        if ($idIsUuid && $hasUuidColumn) {
            // id is already UUID PK — drop leftover uuid column
            DB::table('suppliers')
                ->where(function ($q) {
                    $q->whereNull('uuid')->orWhere('uuid', '');
                })
                ->update(['uuid' => DB::raw('id')]);

            $this->dropUuidColumnMySql('suppliers');

            return;
        }

        if (! $hasUuidColumn) {
            return;
        }

        // id is still integer + orphan uuid column from a partial migration
        $this->completeIntegerToUuidMigrationMySql($idColumn);
    }

    private function completeIntegerToUuidMigrationMySql(object $idColumn): void
    {
        // Fill missing uuid values
        DB::table('suppliers')
            ->where(function ($q) {
                $q->whereNull('uuid')->orWhere('uuid', '');
            })
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('suppliers')->where('id', $row->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            });

        DB::statement('ALTER TABLE suppliers MODIFY uuid VARCHAR(36) NOT NULL');

        $childTables = [
            ['table' => 'purchase_invoices', 'fk' => 'supplier_id'],
            ['table' => 'supplier_balances', 'fk' => 'supplier_id'],
        ];

        $map = DB::table('suppliers')->pluck('uuid', 'id');

        foreach ($childTables as $child) {
            if (! Schema::hasTable($child['table'])) {
                continue;
            }

            $uuidCol = $child['fk'].'_uuid';

            // Migrate FK column to uuid (idempotent)
            if (Schema::hasColumn($child['table'], $child['fk'])) {
                if (! Schema::hasColumn($child['table'], $uuidCol)) {
                    Schema::table($child['table'], function (Blueprint $table) use ($child, $uuidCol) {
                        $table->string($uuidCol, 36)->nullable()->after($child['fk']);
                    });
                }

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

            // Rename _uuid column if migration stopped after dropping old FK
            if (Schema::hasColumn($child['table'], $uuidCol) && ! Schema::hasColumn($child['table'], $child['fk'])) {
                DB::statement("ALTER TABLE {$child['table']} CHANGE {$uuidCol} {$child['fk']} VARCHAR(36) NULL");
            }
        }

        // Remove AUTO_INCREMENT before dropping PRIMARY KEY (MySQL requirement)
        if (str_contains(strtolower($idColumn->Extra ?? ''), 'auto_increment')) {
            $rawType = $idColumn->Type ?? 'BIGINT UNSIGNED';
            DB::statement("ALTER TABLE suppliers MODIFY id {$rawType} NOT NULL");
        }

        DB::statement('ALTER TABLE suppliers DROP PRIMARY KEY');
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        DB::statement('ALTER TABLE suppliers CHANGE uuid id VARCHAR(36) NOT NULL');
        DB::statement('ALTER TABLE suppliers ADD PRIMARY KEY (id)');

        foreach ($childTables as $child) {
            if (! Schema::hasTable($child['table']) || ! Schema::hasColumn($child['table'], $child['fk'])) {
                continue;
            }

            $this->dropForeignKeyIfExists($child['table'], $child['fk']);

            Schema::table($child['table'], function (Blueprint $table) use ($child) {
                $table->foreign($child['fk'])->references('id')->on('suppliers')->onDelete(
                    $child['table'] === 'purchase_invoices' ? 'set null' : 'cascade'
                );
            });
        }
    }

    private function fixSqlite(): void
    {
        if (! Schema::hasColumn('suppliers', 'uuid')) {
            return;
        }

        $cols = collect(DB::select('PRAGMA table_info(suppliers)'));
        $idCol = $cols->firstWhere('name', 'id');
        $idType = strtolower($idCol->type ?? '');

        $idIsUuid = in_array($idType, ['text', 'varchar', 'char'], true);

        if (! $idIsUuid) {
            return;
        }

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
        //
    }
};
