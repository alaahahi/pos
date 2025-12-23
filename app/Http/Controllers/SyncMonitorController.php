<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\DatabaseSyncService;
use App\Services\SyncQueueService;
use App\Services\SyncIdMappingService;

class SyncMonitorController extends Controller
{
    /**
     * عرض صفحة Sync Monitor
     */
    public function index()
    {
        return Inertia::render('SyncMonitor/Index', [
            'translations' => __('messages'),
        ]);
    }

    /**
     * جلب جميع البيانات المطلوبة في request واحد
     */
    public function getAllData(Request $request)
    {
        try {
            $forceConnection = $request->input('force_connection', 'auto');
            
            // جلب الجداول
            $tables = [];
            if (!$forceConnection || $forceConnection === 'mysql' || $forceConnection === 'auto') {
                try {
                    // استخدام MySQL بشكل صريح
                    $mysqlTables = DB::connection('mysql')->select('SHOW TABLES');
                    $dbName = DB::connection('mysql')->getDatabaseName();
                    $tableKey = "Tables_in_{$dbName}";
                    
                    foreach ($mysqlTables as $table) {
                        $tableName = $table->$tableKey;
                        try {
                            $rowCount = DB::table($tableName)->count();
                        } catch (\Exception $e) {
                            $rowCount = 0;
                        }
                        
                        $tables[] = [
                            'name' => $tableName,
                            'rows' => $rowCount,
                            'count' => $rowCount,
                            'connection' => 'mysql',
                        ];
                    }
                } catch (\Exception $e) {
                    // MySQL connection error
                }
            }
            
            if (!$forceConnection || $forceConnection === 'sync_sqlite' || $forceConnection === 'auto') {
                try {
                    $sqlitePath = config('database.connections.sync_sqlite.database');
                    if ($sqlitePath && file_exists($sqlitePath)) {
                        $sqliteTables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                        
                        foreach ($sqliteTables as $table) {
                            $tableName = $table->name;
                            try {
                                $rowCount = DB::connection('sync_sqlite')->table($tableName)->count();
                            } catch (\Exception $e) {
                                $rowCount = 0;
                            }
                            
                            $tables[] = [
                                'name' => $tableName,
                                'rows' => $rowCount,
                                'count' => $rowCount,
                                'connection' => 'sync_sqlite',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // SQLite not available
                }
            }

            // جلب metadata المزامنة
            $metadata = [];
            $queueStats = [
                'pending' => 0,
                'synced' => 0,
                'failed' => 0,
                'total' => 0,
            ];
            
            if (Schema::hasTable('sync_metadata')) {
                $metadata = DB::table('sync_metadata')
                    ->orderBy('table_name')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'table_name' => $item->table_name,
                            'direction' => $item->direction,
                            'last_synced_id' => $item->last_synced_id,
                            'last_synced_at' => $item->last_synced_at,
                            'total_synced' => $item->total_synced,
                        ];
                    });
            }

            // جلب إحصائيات sync_queue
            if (Schema::hasTable('sync_queue')) {
                try {
                    $stats = DB::table('sync_queue')
                        ->selectRaw('
                            COUNT(*) as total,
                            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = "synced" THEN 1 ELSE 0 END) as synced,
                            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
                        ')
                        ->first();
                    
                    $queueStats = [
                        'pending' => (int) ($stats->pending ?? 0),
                        'synced' => (int) ($stats->synced ?? 0),
                        'failed' => (int) ($stats->failed ?? 0),
                        'total' => (int) ($stats->total ?? 0),
                    ];
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            // جلب النسخ الاحتياطية
            $backups = [];
            $backupDir = storage_path('app/backups');
            if (is_dir($backupDir)) {
                $files = glob($backupDir . '/*.sql');
                foreach ($files as $file) {
                    $backups[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => filesize($file),
                        'size_formatted' => $this->formatBytes(filesize($file)),
                        'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                        'type' => 'sql',
                    ];
                }
                
                usort($backups, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }

            return response()->json([
                'success' => true,
                'tables' => $tables,
                'metadata' => $metadata,
                'queue_stats' => $queueStats,
                'backups' => $backups,
                'database_info' => [
                    'type' => 'MySQL',
                    'total_tables' => count($tables),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على قائمة الجداول
     */
    public function tables(Request $request)
    {
        try {
            $tables = [];
            $forceConnection = $request->input('force_connection');
            
            // Get MySQL tables
            if (!$forceConnection || $forceConnection === 'mysql' || $forceConnection === 'auto') {
                try {
                    // استخدام MySQL بشكل صريح
                    $mysqlTables = DB::connection('mysql')->select('SHOW TABLES');
                    $dbName = DB::connection('mysql')->getDatabaseName();
                    $tableKey = "Tables_in_{$dbName}";
                    
                    foreach ($mysqlTables as $table) {
                        $tableName = $table->$tableKey;
                        try {
                            $rowCount = DB::table($tableName)->count();
                        } catch (\Exception $e) {
                            $rowCount = 0;
                        }
                        
                        $tables[] = [
                            'name' => $tableName,
                            'rows' => $rowCount,
                            'count' => $rowCount, // إضافة count أيضاً للتوافق
                            'connection' => 'mysql',
                        ];
                    }
                } catch (\Exception $e) {
                    // MySQL connection error
                }
            }
            
            // Get SQLite tables if exists
            if (!$forceConnection || $forceConnection === 'sync_sqlite' || $forceConnection === 'auto') {
                try {
                    $sqlitePath = config('database.connections.sync_sqlite.database');
                    if ($sqlitePath && file_exists($sqlitePath)) {
                        $sqliteTables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                        
                        foreach ($sqliteTables as $table) {
                            $tableName = $table->name;
                            try {
                                $rowCount = DB::connection('sync_sqlite')->table($tableName)->count();
                            } catch (\Exception $e) {
                                $rowCount = 0;
                            }
                            
                            $tables[] = [
                                'name' => $tableName,
                                'rows' => $rowCount,
                                'count' => $rowCount, // إضافة count أيضاً للتوافق
                                'connection' => 'sync_sqlite',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // SQLite not available
                }
            }
            
            return response()->json([
                'success' => true,
                'tables' => $tables,
                'connection' => $forceConnection ?: 'auto',
                'total' => count($tables),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'tables' => [],
            ], 500);
        }
    }

    /**
     * الحصول على تفاصيل جدول معين
     */
    public function tableDetails(Request $request, $tableName)
    {
        try {
            $limit = (int) ($request->input('limit', 50));
            $offset = (int) ($request->input('offset', 0));
            $forceConnection = $request->input('force_connection');
            
            // تحديد الاتصال المستخدم
            $connection = 'mysql';
            if ($forceConnection === 'sync_sqlite') {
                $connection = 'sync_sqlite';
            } elseif ($forceConnection === 'auto' || !$forceConnection) {
                // استخدام MySQL كافتراضي
                $connection = 'mysql';
            }
            
            $columns = [];
            $data = [];
            $total = 0;
            $connectionName = $connection;
            
            // جلب البيانات من الاتصال المحدد
            try {
                if ($connection === 'sync_sqlite') {
                    $sqlitePath = config('database.connections.sync_sqlite.database');
                    if (!file_exists($sqlitePath)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'ملف SQLite غير موجود',
                        ], 404);
                    }
                    
                    if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'الجدول غير موجود في SQLite',
                        ], 404);
                    }
                    
                    // جلب الأعمدة
                    $columns = Schema::connection('sync_sqlite')->getColumnListing($tableName);
                    
                    // جلب إجمالي السجلات
                    $total = DB::connection('sync_sqlite')->table($tableName)->count();
                    
                    // جلب البيانات
                    $data = DB::connection('sync_sqlite')
                        ->table($tableName)
                        ->offset($offset)
                        ->limit($limit)
                        ->get()
                        ->map(function ($row) {
                            return (array) $row;
                        })
                        ->toArray();
                } else {
                    // MySQL
                    if (!Schema::hasTable($tableName)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'الجدول غير موجود في MySQL',
                        ], 404);
                    }
                    
                    // جلب الأعمدة
                    $columns = Schema::getColumnListing($tableName);
                    
                    // جلب إجمالي السجلات
                    $total = DB::table($tableName)->count();
                    
                    // جلب البيانات
                    $data = DB::table($tableName)
                        ->offset($offset)
                        ->limit($limit)
                        ->get()
                        ->map(function ($row) {
                            return (array) $row;
                        })
                        ->toArray();
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage(),
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'table' => [
                    'name' => $tableName,
                    'columns' => $columns,
                    'data' => $data,
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'connection' => $connectionName,
                ],
                // للتوافق مع الكود القديم
                'columns' => $columns,
                'data' => $data,
                'total' => $total,
                'offset' => $offset,
                'connection' => $connectionName,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * بدء عملية المزامنة
     */
    public function sync(Request $request)
    {
        try {
            $direction = $request->input('direction', 'down'); // down = MySQL to SQLite, up = SQLite to MySQL
            $tables = $request->input('tables'); // يمكن أن يكون null (جميع الجداول) أو string (جدول واحد) أو array
            $safeMode = $request->input('safe_mode', false); // Safe Mode: إضافة فقط، لا تحديث
            $createBackup = $request->input('create_backup', false);
            
            $sqlitePath = config('database.connections.sync_sqlite.database');
            
            // التحقق من وجود ملف SQLite
            if (!file_exists($sqlitePath)) {
                // إنشاء ملف SQLite إذا لم يكن موجوداً
                touch($sqlitePath);
                chmod($sqlitePath, 0666);
            }
            
            $results = [
                'success' => [],
                'failed' => [],
                'total_synced' => 0,
            ];
            
            // تحديد الجداول المراد مزامنتها
            $tablesToSync = [];
            if ($tables) {
                if (is_string($tables)) {
                    $tablesToSync = explode(',', $tables);
                } elseif (is_array($tables)) {
                    $tablesToSync = $tables;
                }
            } else {
                // جميع الجداول
                if ($direction === 'down') {
                    // من MySQL إلى SQLite - جلب جميع جداول MySQL
                    $mysqlTables = DB::select('SHOW TABLES');
                    $dbName = DB::getDatabaseName();
                    $tableKey = "Tables_in_{$dbName}";
                    foreach ($mysqlTables as $table) {
                        $tableName = $table->$tableKey;
                        // استثناء جداول النظام
                        if (!in_array($tableName, ['migrations', 'sync_metadata', 'sync_queue', 'failed_jobs', 'jobs', 'password_reset_tokens', 'personal_access_tokens'])) {
                            $tablesToSync[] = $tableName;
                        }
                    }
                } else {
                    // من SQLite إلى MySQL - جلب جميع جداول SQLite
                    try {
                        $sqliteTables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                        foreach ($sqliteTables as $table) {
                            $tablesToSync[] = $table->name;
                        }
                    } catch (\Exception $e) {
                        // SQLite غير متاح
                    }
                }
            }
            
            // مزامنة كل جدول
            foreach ($tablesToSync as $tableName) {
                $tableName = trim($tableName);
                if (empty($tableName)) continue;
                
                try {
                    $synced = $this->syncTable($tableName, $direction, $safeMode);
                    $results['success'][$tableName] = $synced;
                    $results['total_synced'] += $synced;
                } catch (\Exception $e) {
                    $results['failed'][$tableName] = $e->getMessage();
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'تمت المزامنة بنجاح',
                'direction' => $direction,
                'results' => $results,
            ];
            
            if ($createBackup && $direction === 'up') {
                // إنشاء نسخة احتياطية
                try {
                    $backupFile = $this->createBackup();
                    $response['backup_file'] = $backupFile;
                } catch (\Exception $e) {
                    Log::warning('Failed to create backup: ' . $e->getMessage());
                    $response['backup_file'] = null;
                }
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * مزامنة جدول واحد
     */
    private function syncTable($tableName, $direction, $safeMode = false)
    {
        $syncedCount = 0;
        
        if ($direction === 'down') {
            // من MySQL إلى SQLite
            if (!Schema::hasTable($tableName)) {
                throw new \Exception("الجدول {$tableName} غير موجود في MySQL");
            }
            
            // التحقق من ملف SQLite
            $sqlitePath = config('database.connections.sync_sqlite.database');
            if (!file_exists($sqlitePath)) {
                // إنشاء ملف SQLite إذا لم يكن موجوداً
                $dir = dirname($sqlitePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                touch($sqlitePath);
                chmod($sqlitePath, 0666);
            }
            
            // إنشاء الجدول في SQLite إذا لم يكن موجوداً
            try {
                if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                    $this->createTableInSQLite($tableName);
                }
            } catch (\Exception $e) {
                Log::error("Failed to create table {$tableName} in SQLite: " . $e->getMessage());
                throw new \Exception("فشل إنشاء الجدول {$tableName} في SQLite: " . $e->getMessage());
            }
            
            // جلب البيانات من MySQL (باستخدام chunks للجداول الكبيرة)
            $batchSize = 500;
            
            // محاولة استخدام id للترتيب، وإلا بدون ترتيب
            try {
                $query = DB::table($tableName);
                $columns = Schema::getColumnListing($tableName);
                if (in_array('id', $columns)) {
                    $query->orderBy('id');
                }
                $query->chunk($batchSize, function ($mysqlData) use ($tableName, $safeMode, &$syncedCount) {
                    // إدراج البيانات في SQLite
                    foreach ($mysqlData as $row) {
                        try {
                            $rowArray = (array) $row;
                            
                            if ($safeMode) {
                                // Safe Mode: إضافة فقط إذا لم يكن موجوداً
                                $query = DB::connection('sync_sqlite')->table($tableName);
                                
                                // استخدام id إذا كان موجوداً
                                if (isset($rowArray['id'])) {
                                    $exists = $query->where('id', $rowArray['id'])->exists();
                                } else {
                                    $exists = false;
                                }
                                
                                if (!$exists) {
                                    DB::connection('sync_sqlite')->table($tableName)->insert($rowArray);
                                    $syncedCount++;
                                }
                            } else {
                                // إدراج أو تحديث
                                if (isset($rowArray['id'])) {
                                    DB::connection('sync_sqlite')->table($tableName)->updateOrInsert(
                                        ['id' => $rowArray['id']],
                                        $rowArray
                                    );
                                } else {
                                    // إدراج فقط إذا لم يكن هناك id
                                    DB::connection('sync_sqlite')->table($tableName)->insert($rowArray);
                                }
                                $syncedCount++;
                            }
                        } catch (\Exception $e) {
                            // تخطي السجلات التي تفشل
                            Log::warning("Failed to sync row in {$tableName}: " . $e->getMessage());
                            continue;
                        }
                    }
                });
            } catch (\Exception $e) {
                // إذا فشل chunk، جرب الطريقة العادية
                $mysqlData = DB::table($tableName)->get();
                foreach ($mysqlData as $row) {
                    try {
                        $rowArray = (array) $row;
                        
                        if ($safeMode) {
                            if (isset($rowArray['id'])) {
                                $exists = DB::connection('sync_sqlite')->table($tableName)->where('id', $rowArray['id'])->exists();
                                if (!$exists) {
                                    DB::connection('sync_sqlite')->table($tableName)->insert($rowArray);
                                    $syncedCount++;
                                }
                            } else {
                                DB::connection('sync_sqlite')->table($tableName)->insert($rowArray);
                                $syncedCount++;
                            }
                        } else {
                            if (isset($rowArray['id'])) {
                                DB::connection('sync_sqlite')->table($tableName)->updateOrInsert(['id' => $rowArray['id']], $rowArray);
                            } else {
                                DB::connection('sync_sqlite')->table($tableName)->insert($rowArray);
                            }
                            $syncedCount++;
                        }
                    } catch (\Exception $e2) {
                        Log::warning("Failed to sync row in {$tableName}: " . $e2->getMessage());
                        continue;
                    }
                }
            }
        } else {
            // من SQLite إلى MySQL
            $sqlitePath = config('database.connections.sync_sqlite.database');
            if (!file_exists($sqlitePath)) {
                throw new \Exception("ملف SQLite غير موجود");
            }
            
            if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                throw new \Exception("الجدول {$tableName} غير موجود في SQLite");
            }
            
            // إنشاء الجدول في MySQL إذا لم يكن موجوداً
            if (!Schema::hasTable($tableName)) {
                $this->createTableInMySQL($tableName);
            }
            
            // جلب البيانات من SQLite
            $sqliteData = DB::connection('sync_sqlite')->table($tableName)->get();
            
            // إدراج البيانات في MySQL
            foreach ($sqliteData as $row) {
                try {
                    $rowArray = (array) $row;
                    
                    if ($safeMode) {
                        // Safe Mode: إضافة فقط إذا لم يكن موجوداً
                        $query = DB::table($tableName);
                        
                        // استخدام id إذا كان موجوداً
                        if (isset($rowArray['id'])) {
                            $exists = $query->where('id', $rowArray['id'])->exists();
                        } else {
                            $exists = false;
                        }
                        
                        if (!$exists) {
                            DB::table($tableName)->insert($rowArray);
                            $syncedCount++;
                        }
                    } else {
                        // إدراج أو تحديث
                        if (isset($rowArray['id'])) {
                            DB::table($tableName)->updateOrInsert(
                                ['id' => $rowArray['id']],
                                $rowArray
                            );
                        } else {
                            // إدراج فقط إذا لم يكن هناك id
                            DB::table($tableName)->insert($rowArray);
                        }
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    // تخطي السجلات التي تفشل
                    Log::warning("Failed to sync row in {$tableName}: " . $e->getMessage());
                    continue;
                }
            }
        }
        
        // تحديث metadata
        $this->updateSyncMetadata($tableName, $direction, $syncedCount);
        
        return $syncedCount;
    }
    
    /**
     * إنشاء جدول في SQLite بناءً على MySQL
     */
    private function createTableInSQLite($tableName)
    {
        try {
            // جلب بنية الجدول من MySQL
            $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
            
            $createTable = "CREATE TABLE IF NOT EXISTS `{$tableName}` (";
            $columnDefinitions = [];
            $primaryKey = null;
            
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
                    $primaryKey = $name;
                    $columnDefinitions[] = "`{$name}` {$type} PRIMARY KEY {$null} {$default}";
                } else {
                    $columnDefinitions[] = "`{$name}` {$type} {$null} {$default}";
                }
            }
            
            $createTable .= implode(', ', $columnDefinitions) . ')';
            
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
     * إنشاء جدول في MySQL بناءً على SQLite
     */
    private function createTableInMySQL($tableName)
    {
        // جلب بنية الجدول من SQLite
        $createTable = DB::connection('sync_sqlite')->select("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tableName}'");
        
        if (!empty($createTable)) {
            $sql = $createTable[0]->sql;
            // تحويل SQLite SQL إلى MySQL SQL (مبسط)
            $mysqlSql = str_replace('INTEGER PRIMARY KEY', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
            $mysqlSql = str_replace('TEXT', 'TEXT', $mysqlSql);
            $mysqlSql = str_replace('REAL', 'DECIMAL(10,2)', $mysqlSql);
            
            DB::statement($mysqlSql);
        }
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
     * تحديث metadata المزامنة
     */
    private function updateSyncMetadata($tableName, $direction, $syncedCount)
    {
        if (!Schema::hasTable('sync_metadata')) {
            return;
        }
        
        $existing = DB::table('sync_metadata')
            ->where('table_name', $tableName)
            ->where('direction', $direction)
            ->first();
        
        if ($existing) {
            DB::table('sync_metadata')
                ->where('table_name', $tableName)
                ->where('direction', $direction)
                ->update([
                    'last_synced_at' => now(),
                    'last_updated_at' => now(),
                    'total_synced' => $existing->total_synced + $syncedCount,
                ]);
        } else {
            DB::table('sync_metadata')->insert([
                'table_name' => $tableName,
                'direction' => $direction,
                'last_synced_at' => now(),
                'last_updated_at' => now(),
                'total_synced' => $syncedCount,
            ]);
        }
    }

    /**
     * الحصول على حالة تقدم المزامنة
     */
    public function syncProgress()
    {
        try {
            return response()->json([
                'success' => true,
                'progress' => 0,
                'status' => 'idle',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على metadata المزامنة
     */
    public function syncMetadata()
    {
        try {
            $metadata = [];
            
            if (Schema::hasTable('sync_metadata')) {
                $metadata = DB::table('sync_metadata')
                    ->orderBy('table_name')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'table_name' => $item->table_name,
                            'direction' => $item->direction,
                            'last_synced_id' => $item->last_synced_id,
                            'last_synced_at' => $item->last_synced_at,
                            'total_synced' => $item->total_synced,
                        ];
                    });
            }

            // إضافة إحصائيات sync_queue للمزامنة الذكية
            $syncService = new DatabaseSyncService();
            $queueStats = $syncService->getQueueStats();
            
            return response()->json([
                'success' => true,
                'metadata' => $metadata,
                'queue_stats' => $queueStats, // إحصائيات المزامنة الذكية
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'metadata' => [],
            ], 500);
        }
    }

    /**
     * المزامنة الذكية: مزامنة التغييرات من sync_queue فقط
     */
    /**
     * بدء المزامنة في الخلفية (Background)
     */
    public function smartSync(Request $request)
    {
        try {
            $tableName = $request->input('table_name'); // اختياري
            $limit = $request->input('limit', 100);
            
            // إنشاء Job جديد للمزامنة في الخلفية
            $job = new \App\Jobs\SyncPendingChangesJob($tableName, $limit);
            $jobId = $job->getJobId();
            
            // إرسال Job إلى Queue (في الخلفية - Background)
            // تأكد من أن QUEUE_CONNECTION=database في .env
            dispatch($job)->onQueue('sync'); // استخدام queue مخصص للمزامنة
            
            Log::info('Sync job dispatched', [
                'job_id' => $jobId,
                'table_name' => $tableName,
                'limit' => $limit,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'تم بدء المزامنة في الخلفية',
                'job_id' => $jobId,
                'status' => 'queued',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch sync job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على حالة المزامنة (للـ polling من Frontend)
     */
    public function getSyncStatus(Request $request)
    {
        try {
            $jobId = $request->input('job_id');
            
            if (!$jobId) {
                return response()->json([
                    'success' => false,
                    'message' => 'job_id مطلوب',
                ], 400);
            }
            
            $status = \Illuminate\Support\Facades\Cache::get("sync_status_{$jobId}");
            
            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على حالة المزامنة',
                    'status' => 'not_found',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get sync status', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إعادة تعيين السجلات الفاشلة إلى pending
     */
    public function retryFailed(Request $request)
    {
        try {
            $syncQueueService = new SyncQueueService();
            $resetCount = $syncQueueService->resetFailedToPending();
            
            return response()->json([
                'success' => true,
                'message' => "تم إعادة تعيين {$resetCount} سجل(ات) فاشل(ة) إلى pending",
                'reset_count' => $resetCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على التغييرات المعلقة في sync_queue
     */
    public function getPendingChanges(Request $request)
    {
        try {
            $tableName = $request->input('table_name'); // اختياري
            $limit = $request->input('limit', 100);
            
            $syncQueueService = new SyncQueueService();
            $pendingChanges = $syncQueueService->getPendingChanges($tableName, $limit);
            
            return response()->json([
                'success' => true,
                'pending_changes' => $pendingChanges,
                'count' => count($pendingChanges),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'pending_changes' => [],
            ], 500);
        }
    }

    /**
     * التحقق من التعارضات في ID
     */
    public function checkIdConflicts(Request $request)
    {
        try {
            $tableName = $request->input('table_name');
            
            if (!$tableName) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تحديد اسم الجدول',
                ], 400);
            }

            $idMappingService = new SyncIdMappingService();
            $conflicts = [];

            // جلب جميع السجلات من SQLite
            try {
                $sqliteRecords = DB::connection('sync_sqlite')
                    ->table($tableName)
                    ->get(['id']);

                foreach ($sqliteRecords as $record) {
                    $localId = $record->id;
                    
                    // التحقق من وجود ID في السيرفر
                    if ($idMappingService->checkIdConflict($tableName, $localId)) {
                        $serverId = $idMappingService->getServerId($tableName, $localId, 'up');
                        
                        // إذا كان هناك mapping مختلف، أو لا يوجد mapping
                        if (!$serverId || $serverId != $localId) {
                            $conflicts[] = [
                                'local_id' => $localId,
                                'server_id' => $serverId,
                                'has_mapping' => $serverId !== null,
                                'conflict_type' => $serverId ? 'mapped_different' : 'no_mapping',
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // SQLite غير متاح
            }

            return response()->json([
                'success' => true,
                'table_name' => $tableName,
                'conflicts' => $conflicts,
                'conflict_count' => count($conflicts),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على ID mappings
     */
    public function getIdMappings(Request $request)
    {
        try {
            $tableName = $request->input('table_name');
            
            if (!Schema::hasTable('sync_id_mapping')) {
                return response()->json([
                    'success' => true,
                    'mappings' => [],
                ]);
            }

            $query = DB::table('sync_id_mapping')
                ->orderBy('table_name')
                ->orderBy('local_id');

            if ($tableName) {
                $query->where('table_name', $tableName);
            }

            $mappings = $query->get()->map(function ($item) {
                return [
                    'table_name' => $item->table_name,
                    'local_id' => $item->local_id,
                    'server_id' => $item->server_id,
                    'sync_direction' => $item->sync_direction,
                    'created_at' => $item->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'mappings' => $mappings,
                'count' => count($mappings),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'mappings' => [],
            ], 500);
        }
    }

    /**
     * اختبار المزامنة لجدول معين
     */
    public function testSync($tableName)
    {
        try {
            $mysqlExists = Schema::hasTable($tableName);
            $sqliteExists = false;
            
            try {
                $sqlitePath = config('database.connections.sync_sqlite.database');
                if (file_exists($sqlitePath)) {
                    $sqliteExists = Schema::connection('sync_sqlite')->hasTable($tableName);
                }
            } catch (\Exception $e) {
                // SQLite not available
            }
            
            return response()->json([
                'success' => true,
                'table' => $tableName,
                'mysql_exists' => $mysqlExists,
                'sqlite_exists' => $sqliteExists,
                'can_sync' => $mysqlExists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء نسخة احتياطية من قاعدة البيانات بصيغة SQL
     */
    private function createBackup(): ?string
    {
        try {
            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';

            // إنشاء نسخة احتياطية من MySQL بصيغة SQL
            $this->createMySQLBackup($backupFile);

            return $backupFile;
        } catch (\Exception $e) {
            Log::error('Failed to create backup: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * إنشاء نسخة احتياطية من MySQL بصيغة SQL
     */
    private function createMySQLBackup(string $backupFile): void
    {
        $dbConfig = config('database.connections.mysql');
        $dbName = $dbConfig['database'];
        $dbUser = $dbConfig['username'];
        $dbPass = $dbConfig['password'];
        $dbHost = $dbConfig['host'];
        $dbPort = $dbConfig['port'] ?? 3306;

        // محاولة استخدام mysqldump إذا كان متاحاً
        $mysqldumpPath = $this->findMysqldumpPath();
        
        if ($mysqldumpPath) {
            // استخدام mysqldump (الأفضل) - البيانات فقط بدون البنية
            // استثناء جداول النظام والـ logs
            $ignoreTables = ['migrations', 'sync_metadata', 'sync_queue', 'sync_id_mapping', 'logs'];
            $ignoreTablesParam = '';
            foreach ($ignoreTables as $table) {
                $ignoreTablesParam .= ' --ignore-table=' . escapeshellarg($dbName . '.' . $table);
            }
            
            $command = sprintf(
                '"%s" --host=%s --port=%s --user=%s --password=%s --single-transaction --no-create-info --no-create-db --skip-triggers --routines=false%s %s > "%s" 2>&1',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                $ignoreTablesParam,
                escapeshellarg($dbName),
                escapeshellarg($backupFile)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('فشل mysqldump: ' . implode("\n", $output));
            }
        } else {
            // استخدام PHP لإنشاء SQL يدوياً (بديل) - البيانات فقط
            $this->createMySQLBackupManually($backupFile, $dbName);
        }
    }

    /**
     * إنشاء نسخة احتياطية يدوياً باستخدام PHP - البيانات فقط بدون البنية
     */
    private function createMySQLBackupManually(string $backupFile, string $dbName): void
    {
        $sql = "-- MySQL Data Dump (Data Only - No Structure)\n";
        $sql .= "-- Database: {$dbName}\n";
        $sql .= "-- Created: " . now()->toDateTimeString() . "\n";
        $sql .= "-- Note: This backup contains only INSERT statements, no CREATE TABLE statements\n\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";

        // جلب جميع الجداول
        $tables = DB::select('SHOW TABLES');
        $tableKey = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // استثناء جداول النظام والجداول غير المهمة
            if (in_array($tableName, ['migrations', 'sync_metadata', 'sync_queue', 'sync_id_mapping', 'logs'])) {
                continue;
            }

            // جلب البيانات فقط (بدون بنية الجدول)
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $sql .= "\n-- --------------------------------------------------------\n";
                $sql .= "-- Dumping data for table `{$tableName}`\n";
                $sql .= "-- --------------------------------------------------------\n\n";
                $sql .= "LOCK TABLES `{$tableName}` WRITE;\n";
                $sql .= "/*!40000 ALTER TABLE `{$tableName}` DISABLE KEYS */;\n\n";

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $columns = array_keys($rowArray);
                    $values = array_map(function ($value) {
                        if ($value === null) {
                            return 'NULL';
                        } elseif (is_numeric($value)) {
                            return $value;
                        } else {
                            return "'" . addslashes($value) . "'";
                        }
                    }, array_values($rowArray));

                    // استخدام INSERT IGNORE لتجنب أخطاء المفاتيح المكررة
                    $sql .= "INSERT IGNORE INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }

                $sql .= "\n/*!40000 ALTER TABLE `{$tableName}` ENABLE KEYS */;\n";
                $sql .= "UNLOCK TABLES;\n\n";
            }
        }

        $sql .= "\n-- Data dump completed on " . now()->toDateTimeString() . "\n";

        file_put_contents($backupFile, $sql);
    }

    /**
     * البحث عن مسار mysqldump
     */
    private function findMysqldumpPath(): ?string
    {
        $possiblePaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe', // XAMPP Windows
            'C:\\wamp\\bin\\mysql\\mysql' . $this->getMySQLVersion() . '\\bin\\mysqldump.exe', // WAMP
            '/usr/bin/mysqldump', // Linux
            '/usr/local/bin/mysqldump', // macOS
            '/opt/local/bin/mysqldump', // macOS MacPorts
        ];

        // أولاً: تحقق من المسارات المحددة
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // ثانياً: تحقق من وجوده في PATH
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            exec('where mysqldump 2>nul', $output, $returnVar);
        } else {
            // Linux/macOS
            exec('which mysqldump 2>/dev/null', $output, $returnVar);
        }
        
        if ($returnVar === 0 && !empty($output)) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * الحصول على إصدار MySQL (لـ WAMP)
     */
    private function getMySQLVersion(): string
    {
        try {
            $version = DB::select('SELECT VERSION() as version');
            if (!empty($version)) {
                $versionStr = $version[0]->version;
                return explode('.', $versionStr)[0] . explode('.', $versionStr)[1];
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return '8.0'; // Default
    }

    /**
     * الحصول على قائمة النسخ الاحتياطية
     */
    public function backups()
    {
        try {
            $backupDir = storage_path('app/backups');
            $backups = [];

            if (is_dir($backupDir)) {
                $files = glob($backupDir . '/*.sql');
                foreach ($files as $file) {
                    $backups[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => filesize($file),
                        'size_formatted' => $this->formatBytes(filesize($file)),
                        'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                        'type' => 'sql',
                    ];
                }
                
                // ترتيب حسب التاريخ (الأحدث أولاً)
                usort($backups, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }

            return response()->json([
                'success' => true,
                'backups' => $backups,
                'count' => count($backups),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تنسيق حجم الملف
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * استعادة نسخة احتياطية من ملف SQL
     */
    public function restoreBackup(Request $request)
    {
        try {
            $backupFile = $request->input('backup_file');
            $tables = $request->input('tables'); // جداول محددة للاستعادة (اختياري)
            
            if (!$backupFile || !file_exists($backupFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف النسخة الاحتياطية غير موجود',
                ], 404);
            }

            // التحقق من أن الملف هو SQL
            if (pathinfo($backupFile, PATHINFO_EXTENSION) !== 'sql') {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف يجب أن يكون بصيغة SQL',
                ], 400);
            }

            // قراءة محتوى ملف SQL
            $sql = file_get_contents($backupFile);
            
            if (empty($sql)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف النسخة الاحتياطية فارغ',
                ], 400);
            }

            // إذا تم تحديد جداول محددة، استرجعها فقط
            if ($tables && is_array($tables) && count($tables) > 0) {
                $this->restoreSelectedTables($sql, $tables);
            } else {
                // استعادة كاملة
                $this->restoreFullBackup($sql);
            }

            return response()->json([
                'success' => true,
                'message' => 'تمت استعادة النسخة الاحتياطية بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restore backup', [
                'file' => $backupFile ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاستعادة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * استعادة كاملة من ملف SQL
     */
    private function restoreFullBackup(string $sql): void
    {
        // تقسيم SQL إلى أوامر منفصلة
        $statements = $this->splitSQLStatements($sql);
        
        // تنفيذ الأوامر التي لا تعمل داخل Transactions أولاً
        $nonTransactionStatements = [];
        $transactionStatements = [];
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            // الأوامر التي لا تعمل داخل Transactions
            if (preg_match('/^(SET|LOCK TABLES|UNLOCK TABLES|CREATE DATABASE|USE)/i', $statement)) {
                $nonTransactionStatements[] = $statement;
            } else {
                $transactionStatements[] = $statement;
            }
        }
        
        // تنفيذ الأوامر خارج Transaction
        foreach ($nonTransactionStatements as $statement) {
            try {
                // تخطي بعض الأوامر غير الضرورية
                if (preg_match('/^(SET SQL_MODE|SET time_zone|SET NAMES|SET CHARACTER_SET)/i', $statement)) {
                    continue;
                }
                DB::statement($statement);
            } catch (\Exception $e) {
                // تخطي الأخطاء في الأوامر غير الحرجة
                Log::warning('Skipped non-transaction statement', [
                    'statement' => substr($statement, 0, 100),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // تنفيذ الأوامر داخل Transaction
        if (count($transactionStatements) > 0) {
            DB::beginTransaction();
            
            try {
                foreach ($transactionStatements as $statement) {
                    try {
                        // تحويل INSERT INTO إلى INSERT IGNORE لتجنب أخطاء المفاتيح المكررة
                        if (stripos($statement, 'INSERT INTO') !== false && stripos($statement, 'INSERT IGNORE') === false) {
                            $statement = preg_replace('/INSERT\s+INTO\s+/i', 'INSERT IGNORE INTO ', $statement, 1);
                        }
                        
                        DB::statement($statement);
                    } catch (\Exception $e) {
                        // تخطي الأخطاء في بعض الأوامر
                        if (stripos($statement, 'DROP TABLE') !== false || 
                            stripos($statement, 'ALTER TABLE') !== false ||
                            stripos($statement, 'CREATE TABLE') !== false) {
                            Log::warning('Skipped statement in transaction', [
                                'statement' => substr($statement, 0, 100),
                                'error' => $e->getMessage(),
                            ]);
                            continue;
                        }
                        // تخطي أخطاء المفاتيح المكررة
                        if (stripos($e->getMessage(), 'Duplicate entry') !== false || stripos($e->getMessage(), '1062') !== false) {
                            Log::info('Skipped duplicate entry', [
                                'statement' => substr($statement, 0, 100),
                            ]);
                            continue;
                        }
                        throw $e;
                    }
                }
                
                try {
                    DB::commit();
                } catch (\Exception $commitException) {
                    // إذا فشل commit، قد لا يكون هناك transaction نشط
                    Log::warning('Commit failed', [
                        'error' => $commitException->getMessage(),
                    ]);
                }
            } catch (\Exception $e) {
                // التحقق من وجود transaction نشط قبل rollback
                try {
                    // محاولة rollback - إذا لم يكن هناك transaction نشط، سيفشل بشكل آمن
                    DB::rollBack();
                } catch (\Exception $rollbackException) {
                    // تجاهل خطأ rollback إذا لم يكن هناك transaction نشط
                    // هذا طبيعي إذا كان الخطأ حدث قبل beginTransaction أو بعد commit
                    if (stripos($rollbackException->getMessage(), 'no active transaction') === false) {
                        Log::warning('Rollback failed', [
                            'error' => $rollbackException->getMessage(),
                        ]);
                    }
                }
                throw $e;
            }
        }
    }

    /**
     * استعادة جداول محددة
     */
    private function restoreSelectedTables(string $sql, array $tables): void
    {
        // تقسيم SQL إلى أوامر
        $statements = $this->splitSQLStatements($sql);
        
        $currentTable = null;
        $tableStatements = [];
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            // تخطي الأوامر التي لا تعمل داخل Transactions
            if (preg_match('/^(SET|LOCK TABLES|UNLOCK TABLES)/i', $statement)) {
                continue;
            }
            
            // التحقق من بداية جدول جديد
            if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches)) {
                $currentTable = $matches[1];
                if (!isset($tableStatements[$currentTable])) {
                    $tableStatements[$currentTable] = [];
                }
            }
            
            // إضافة السطر إلى الجدول الحالي
            if ($currentTable && in_array($currentTable, $tables)) {
                $tableStatements[$currentTable][] = $statement;
            }
        }
        
        // تنفيذ أوامر الجداول المحددة داخل Transaction
        if (count($tableStatements) > 0) {
            DB::beginTransaction();
            
            try {
                foreach ($tables as $tableName) {
                    if (isset($tableStatements[$tableName])) {
                    foreach ($tableStatements[$tableName] as $statement) {
                        try {
                            // تحويل INSERT INTO إلى INSERT IGNORE لتجنب أخطاء المفاتيح المكررة
                            if (stripos($statement, 'INSERT INTO') !== false && stripos($statement, 'INSERT IGNORE') === false) {
                                $statement = preg_replace('/INSERT\s+INTO\s+/i', 'INSERT IGNORE INTO ', $statement, 1);
                            }
                            
                            DB::statement($statement);
                        } catch (\Exception $e) {
                            // تخطي الأخطاء في بعض الأوامر
                            if (stripos($statement, 'DROP TABLE') !== false || 
                                stripos($statement, 'ALTER TABLE') !== false ||
                                stripos($statement, 'CREATE TABLE') !== false) {
                                Log::warning('Skipped statement for table', [
                                    'table' => $tableName,
                                    'statement' => substr($statement, 0, 100),
                                    'error' => $e->getMessage(),
                                ]);
                                continue;
                            }
                            // تخطي أخطاء المفاتيح المكررة إذا كان INSERT IGNORE موجود
                            if (stripos($statement, 'INSERT IGNORE') !== false && stripos($e->getMessage(), 'Duplicate entry') !== false) {
                                Log::info('Skipped duplicate entry for table', [
                                    'table' => $tableName,
                                    'statement' => substr($statement, 0, 100),
                                ]);
                                continue;
                            }
                            throw $e;
                        }
                    }
                    }
                }
                
                try {
                    DB::commit();
                } catch (\Exception $commitException) {
                    // إذا فشل commit، قد لا يكون هناك transaction نشط
                    Log::warning('Commit failed', [
                        'error' => $commitException->getMessage(),
                    ]);
                }
            } catch (\Exception $e) {
                // التحقق من وجود transaction نشط قبل rollback
                try {
                    // محاولة rollback - إذا لم يكن هناك transaction نشط، سيفشل بشكل آمن
                    DB::rollBack();
                } catch (\Exception $rollbackException) {
                    // تجاهل خطأ rollback إذا لم يكن هناك transaction نشط
                    // هذا طبيعي إذا كان الخطأ حدث قبل beginTransaction أو بعد commit
                    if (stripos($rollbackException->getMessage(), 'no active transaction') === false) {
                        Log::warning('Rollback failed', [
                            'error' => $rollbackException->getMessage(),
                        ]);
                    }
                }
                throw $e;
            }
        }
    }

    /**
     * تقسيم SQL إلى أوامر منفصلة
     */
    private function splitSQLStatements(string $sql): array
    {
        // إزالة التعليقات
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // تقسيم حسب الفاصلة المنقوطة
        $statements = preg_split('/;\s*$/m', $sql);
        
        return array_filter(array_map('trim', $statements));
    }

    /**
     * إنشاء نسخة احتياطية يدوياً
     */
    public function createBackupManual(Request $request)
    {
        try {
            $backupFile = $this->createBackup();
            
            if (!$backupFile) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل إنشاء النسخة الاحتياطية',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء النسخة الاحتياطية بنجاح',
                'backup_file' => $backupFile,
                'backup_name' => basename($backupFile),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create manual backup', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحميل نسخة احتياطية
     */
    public function downloadBackup(Request $request)
    {
        try {
            $backupFile = $request->input('backup_file');
            
            if (!$backupFile || !file_exists($backupFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف النسخة الاحتياطية غير موجود',
                ], 404);
            }

            return response()->download($backupFile, basename($backupFile), [
                'Content-Type' => 'application/sql',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف نسخة احتياطية
     */
    public function deleteBackup(Request $request)
    {
        try {
            $backupFile = $request->input('backup_file');
            
            if (!$backupFile || !file_exists($backupFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف النسخة الاحتياطية غير موجود',
                ], 404);
            }

            unlink($backupFile);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف النسخة الاحتياطية بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * فحص شامل لحالة المزامنة وتشخيص المشاكل
     */
    public function checkSyncHealth(Request $request)
    {
        try {
            $health = [
                'timestamp' => now()->toDateTimeString(),
                'overall_status' => 'unknown',
                'issues' => [],
                'warnings' => [],
                'info' => [],
            ];

            // 1. فحص إعدادات API Sync
            $syncViaApi = env('SYNC_VIA_API', false);
            $useApi = filter_var($syncViaApi, FILTER_VALIDATE_BOOLEAN);
            
            $health['api_sync'] = [
                'enabled' => $useApi,
                'config_value' => $syncViaApi,
                'online_url' => env('ONLINE_URL', 'غير محدد'),
                'api_token_set' => !empty(env('SYNC_API_TOKEN')),
                'api_timeout' => env('SYNC_API_TIMEOUT', 30),
            ];

            if (!$useApi) {
                $health['warnings'][] = 'API Sync غير مفعّل (SYNC_VIA_API=false أو غير محدد)';
            }

            if (empty(env('ONLINE_URL'))) {
                $health['issues'][] = 'ONLINE_URL غير محدد في .env';
            }

            if (empty(env('SYNC_API_TOKEN'))) {
                $health['issues'][] = 'SYNC_API_TOKEN غير محدد في .env';
            }

            // 2. فحص ApiSyncService
            try {
                $apiService = new \App\Services\ApiSyncService();
                $apiAvailable = $apiService->isApiAvailable();
                
                $health['api_service'] = [
                    'available' => $apiAvailable,
                    'status' => $apiAvailable ? 'متاح' : 'غير متاح',
                ];

                if (!$apiAvailable) {
                    $health['issues'][] = 'API غير متاح - لا يمكن الاتصال بالسيرفر';
                }
            } catch (\Exception $e) {
                $health['api_service'] = [
                    'available' => false,
                    'status' => 'خطأ',
                    'error' => $e->getMessage(),
                ];
                $health['issues'][] = 'خطأ في ApiSyncService: ' . $e->getMessage();
            }

            // 3. فحص DatabaseSyncService
            try {
                $syncService = new DatabaseSyncService();
                
                // استخدام reflection للوصول إلى protected properties
                $reflection = new \ReflectionClass($syncService);
                $useApiProperty = $reflection->getProperty('useApi');
                $useApiProperty->setAccessible(true);
                $useApiValue = $useApiProperty->getValue($syncService);
                
                $health['database_sync_service'] = [
                    'use_api' => $useApiValue,
                    'status' => $useApiValue ? 'يستخدم API' : 'يستخدم MySQL مباشرة',
                ];

                if (!$useApiValue && $useApi) {
                    $health['issues'][] = 'DatabaseSyncService لا يستخدم API رغم تفعيل SYNC_VIA_API';
                }
            } catch (\Exception $e) {
                $health['database_sync_service'] = [
                    'status' => 'خطأ',
                    'error' => $e->getMessage(),
                ];
                $health['issues'][] = 'خطأ في DatabaseSyncService: ' . $e->getMessage();
            }

            // 4. فحص MySQL (إذا لم يكن API مفعّل)
            if (!$useApi) {
                try {
                    $mysqlAvailable = false;
                    $mysqlError = null;
                    
                    try {
                        $pdo = DB::connection('mysql')->getPdo();
                        DB::connection('mysql')->select('SELECT 1');
                        $mysqlAvailable = true;
                    } catch (\Exception $e) {
                        $mysqlError = $e->getMessage();
                    }
                    
                    $health['mysql'] = [
                        'available' => $mysqlAvailable,
                        'status' => $mysqlAvailable ? 'متاح' : 'غير متاح',
                        'error' => $mysqlError,
                    ];

                    if (!$mysqlAvailable) {
                        $health['issues'][] = 'MySQL غير متاح: ' . ($mysqlError ?? 'خطأ غير معروف');
                    }
                } catch (\Exception $e) {
                    $health['mysql'] = [
                        'available' => false,
                        'status' => 'خطأ',
                        'error' => $e->getMessage(),
                    ];
                    $health['issues'][] = 'خطأ في فحص MySQL: ' . $e->getMessage();
                }
            }

            // 5. فحص sync_queue
            try {
                $connection = config('database.default');
                $queueTableExists = Schema::connection($connection)->hasTable('sync_queue');
                
                if ($queueTableExists) {
                    $queueStats = $syncService->getQueueStats();
                    $health['sync_queue'] = [
                        'table_exists' => true,
                        'stats' => $queueStats,
                        'status' => $queueStats['pending'] > 0 ? 'يوجد سجلات في الانتظار' : 'لا توجد سجلات في الانتظار',
                    ];

                    if ($queueStats['failed'] > 0) {
                        $health['warnings'][] = "يوجد {$queueStats['failed']} سجل(ات) فاشل(ة) في sync_queue";
                    }

                    if ($queueStats['pending'] > 0) {
                        $health['info'][] = "يوجد {$queueStats['pending']} سجل(ات) في انتظار المزامنة";
                    }
                } else {
                    $health['sync_queue'] = [
                        'table_exists' => false,
                        'status' => 'الجدول غير موجود',
                    ];
                    $health['issues'][] = 'جدول sync_queue غير موجود';
                }
            } catch (\Exception $e) {
                $health['sync_queue'] = [
                    'status' => 'خطأ',
                    'error' => $e->getMessage(),
                ];
                $health['issues'][] = 'خطأ في فحص sync_queue: ' . $e->getMessage();
            }

            // 6. فحص Queue Worker (إذا كان مفعّل)
            try {
                $queueConnection = config('queue.default');
                $health['queue_worker'] = [
                    'connection' => $queueConnection,
                    'status' => $queueConnection === 'database' ? 'مفعّل (database)' : ($queueConnection === 'sync' ? 'غير مفعّل (sync)' : 'غير معروف'),
                ];

                if ($queueConnection === 'sync') {
                    $health['warnings'][] = 'Queue Worker غير مفعّل (QUEUE_CONNECTION=sync) - المزامنة ستكون متزامنة وليست في الخلفية';
                }

                // فحص جدول jobs
                if ($queueConnection === 'database') {
                    $jobsTableExists = Schema::connection($connection)->hasTable('jobs');
                    $health['queue_worker']['jobs_table_exists'] = $jobsTableExists;
                    
                    if (!$jobsTableExists) {
                        $health['issues'][] = 'جدول jobs غير موجود - Queue Worker لن يعمل';
                    }
                }
            } catch (\Exception $e) {
                $health['queue_worker'] = [
                    'status' => 'خطأ',
                    'error' => $e->getMessage(),
                ];
            }

            // 7. تحديد الحالة العامة
            if (count($health['issues']) > 0) {
                $health['overall_status'] = 'error';
                $health['message'] = 'يوجد مشاكل في المزامنة';
            } elseif (count($health['warnings']) > 0) {
                $health['overall_status'] = 'warning';
                $health['message'] = 'المزامنة تعمل لكن يوجد تحذيرات';
            } else {
                $health['overall_status'] = 'ok';
                $health['message'] = 'المزامنة تعمل بشكل صحيح';
            }

            // 8. توصيات
            $health['recommendations'] = [];
            
            if (!$useApi && !empty($health['mysql']['available']) && !$health['mysql']['available']) {
                $health['recommendations'][] = 'تفعيل API Sync (SYNC_VIA_API=true) لتجنب مشاكل الاتصال بـ MySQL';
            }

            if ($queueConnection === 'sync') {
                $health['recommendations'][] = 'تفعيل Queue Worker (QUEUE_CONNECTION=database) للمزامنة في الخلفية';
            }

            if (!empty($health['sync_queue']['stats']['failed']) && $health['sync_queue']['stats']['failed'] > 0) {
                $health['recommendations'][] = 'إعادة محاولة السجلات الفاشلة (استخدم /api/sync-monitor/retry-failed)';
            }

            return response()->json([
                'success' => true,
                'health' => $health,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check sync health', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء فحص حالة المزامنة: ' . $e->getMessage(),
                'health' => [
                    'overall_status' => 'error',
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}

