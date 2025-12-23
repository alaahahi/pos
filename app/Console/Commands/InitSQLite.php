<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class InitSQLite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sqlite:init {--force : Force initialization even if tables exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تهيئة SQLite بنسخ الجداول والبيانات من MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 بدء تهيئة SQLite...');

        // التحقق من توفر MySQL
        try {
            DB::connection('mysql')->getPdo();
            $this->info('✅ MySQL متوفر');
        } catch (\Exception $e) {
            $this->error('❌ MySQL غير متوفر: ' . $e->getMessage());
            $this->warn('⚠️  سيتم إنشاء جداول فارغة فقط');
            return $this->createEmptyTables();
        }

        // التحقق من وجود ملف SQLite
        $sqlitePath = config('database.connections.sync_sqlite.database');
        $dir = dirname($sqlitePath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $this->info("📁 تم إنشاء المجلد: {$dir}");
        }

        if (!file_exists($sqlitePath)) {
            touch($sqlitePath);
            chmod($sqlitePath, 0666);
            $this->info("📄 تم إنشاء ملف SQLite: {$sqlitePath}");
        }

        // جلب جميع الجداول من MySQL
        try {
            $mysqlTables = DB::connection('mysql')->select('SHOW TABLES');
            $dbName = DB::connection('mysql')->getDatabaseName();
            $tableKey = "Tables_in_{$dbName}";
            
            $tables = [];
            foreach ($mysqlTables as $table) {
                $tableName = $table->$tableKey;
                // استثناء جداول النظام
                if (!in_array($tableName, ['migrations', 'sync_metadata', 'sync_queue', 'sync_id_mapping', 'failed_jobs', 'jobs', 'password_reset_tokens', 'personal_access_tokens'])) {
                    $tables[] = $tableName;
                }
            }

            $this->info("📊 تم العثور على " . count($tables) . " جدول في MySQL");

            $bar = $this->output->createProgressBar(count($tables));
            $bar->start();

            $created = 0;
            $synced = 0;
            $failed = 0;

            foreach ($tables as $tableName) {
                try {
                    // إنشاء الجدول في SQLite
                    if (!Schema::connection('sync_sqlite')->hasTable($tableName) || $this->option('force')) {
                        $this->createTableInSQLite($tableName);
                        $created++;
                    }

                    // نسخ البيانات
                    $syncedCount = $this->copyDataFromMySQL($tableName);
                    $synced += $syncedCount;

                    $bar->advance();
                } catch (\Exception $e) {
                    $failed++;
                    $this->newLine();
                    $this->warn("⚠️  فشل في جدول {$tableName}: " . $e->getMessage());
                    Log::error("Failed to init table {$tableName} in SQLite", ['error' => $e->getMessage()]);
                }
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ تم إنشاء {$created} جدول");
            $this->info("✅ تم نسخ {$synced} سجل");
            if ($failed > 0) {
                $this->warn("⚠️  فشل {$failed} جدول");
            }

            $this->info('🎉 تمت تهيئة SQLite بنجاح!');

        } catch (\Exception $e) {
            $this->error('❌ فشلت التهيئة: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * إنشاء جدول في SQLite بناءً على MySQL
     */
    private function createTableInSQLite($tableName)
    {
        try {
            // جلب بنية الجدول من MySQL
            $columns = DB::connection('mysql')->select("SHOW COLUMNS FROM `{$tableName}`");
            
            $createTable = "CREATE TABLE IF NOT EXISTS `{$tableName}` (";
            $columnDefinitions = [];
            $primaryKeyColumns = [];
            
            foreach ($columns as $column) {
                $name = $column->Field;
                $type = $this->convertMySQLTypeToSQLite($column->Type);
                $null = $column->Null === 'YES' ? '' : 'NOT NULL';
                $default = '';
                
                if ($column->Default !== null && $column->Default !== '') {
                    if (is_numeric($column->Default)) {
                        $default = "DEFAULT {$column->Default}";
                    } else {
                        $default = "DEFAULT '{$column->Default}'";
                    }
                }
                
                if ($column->Key === 'PRI') {
                    $primaryKeyColumns[] = "`{$name}`";
                    $columnDefinitions[] = "`{$name}` {$type} {$null} {$default}";
                } else {
                    $columnDefinitions[] = "`{$name}` {$type} {$null} {$default}";
                }
            }
            
            $createTable .= implode(', ', $columnDefinitions);
            
            // إضافة composite primary key إذا كان موجوداً
            if (count($primaryKeyColumns) > 0) {
                $createTable .= ', PRIMARY KEY (' . implode(', ', $primaryKeyColumns) . ')';
            }
            
            $createTable .= ')';
            
            DB::connection('sync_sqlite')->statement($createTable);
        } catch (\Exception $e) {
            // إذا فشل إنشاء الجدول، حاول إنشاءه بشكل بسيط
            try {
                DB::connection('sync_sqlite')->statement("CREATE TABLE IF NOT EXISTS `{$tableName}` (id INTEGER PRIMARY KEY)");
            } catch (\Exception $e2) {
                throw new \Exception("فشل إنشاء الجدول {$tableName} في SQLite: " . $e->getMessage());
            }
        }
    }

    /**
     * نسخ البيانات من MySQL إلى SQLite
     */
    private function copyDataFromMySQL($tableName): int
    {
        $syncedCount = 0;
        $batchSize = 500;

        try {
            DB::connection('mysql')
                ->table($tableName)
                ->orderBy('id')
                ->chunk($batchSize, function ($rows) use ($tableName, &$syncedCount) {
                    foreach ($rows as $row) {
                        try {
                            $rowArray = (array) $row;
                            DB::connection('sync_sqlite')
                                ->table($tableName)
                                ->updateOrInsert(['id' => $rowArray['id']], $rowArray);
                            $syncedCount++;
                        } catch (\Exception $e) {
                            // تخطي السجلات التي تفشل
                            continue;
                        }
                    }
                });
        } catch (\Exception $e) {
            // إذا فشل chunk، جرب الطريقة العادية
            $rows = DB::connection('mysql')->table($tableName)->get();
            foreach ($rows as $row) {
                try {
                    $rowArray = (array) $row;
                    DB::connection('sync_sqlite')
                        ->table($tableName)
                        ->updateOrInsert(['id' => $rowArray['id']], $rowArray);
                    $syncedCount++;
                } catch (\Exception $e2) {
                    continue;
                }
            }
        }

        return $syncedCount;
    }

    /**
     * تحويل نوع MySQL إلى SQLite
     */
    private function convertMySQLTypeToSQLite($mysqlType)
    {
        $mysqlType = strtoupper($mysqlType);
        
        if (strpos($mysqlType, 'INT') !== false) {
            return 'INTEGER';
        } elseif (strpos($mysqlType, 'TEXT') !== false || strpos($mysqlType, 'VARCHAR') !== false || strpos($mysqlType, 'CHAR') !== false) {
            return 'TEXT';
        } elseif (strpos($mysqlType, 'DECIMAL') !== false || strpos($mysqlType, 'FLOAT') !== false || strpos($mysqlType, 'DOUBLE') !== false) {
            return 'REAL';
        } elseif (strpos($mysqlType, 'DATE') !== false || strpos($mysqlType, 'TIME') !== false || strpos($mysqlType, 'DATETIME') !== false || strpos($mysqlType, 'TIMESTAMP') !== false) {
            return 'TEXT';
        } else {
            return 'TEXT';
        }
    }

    /**
     * إنشاء جداول فارغة (عند عدم توفر MySQL)
     */
    private function createEmptyTables(): int
    {
        $this->warn('⚠️  سيتم إنشاء جداول أساسية فارغة فقط');
        
        // قائمة الجداول الأساسية
        $essentialTables = [
            'users', 'user_type', 'products', 'orders', 'categories', 
            'customers', 'suppliers', 'boxes', 'wallets', 'transactions',
            'jobs', 'failed_jobs', 'sync_queue', 'sync_id_mapping' // جداول المزامنة والـ Queue
        ];

        $sqlitePath = config('database.connections.sync_sqlite.database');
        $dir = dirname($sqlitePath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($sqlitePath)) {
            touch($sqlitePath);
            chmod($sqlitePath, 0666);
        }

        foreach ($essentialTables as $tableName) {
            try {
                if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                    DB::connection('sync_sqlite')->statement("CREATE TABLE IF NOT EXISTS `{$tableName}` (id INTEGER PRIMARY KEY)");
                    $this->info("✅ تم إنشاء جدول: {$tableName}");
                }
            } catch (\Exception $e) {
                $this->warn("⚠️  فشل إنشاء جدول {$tableName}: " . $e->getMessage());
            }
        }

        $this->info('✅ تم إنشاء الجداول الأساسية');
        return 0;
    }
}

