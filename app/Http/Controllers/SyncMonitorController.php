<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

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
                    $mysqlTables = DB::select('SHOW TABLES');
                    $dbName = DB::getDatabaseName();
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
                // إنشاء نسخة احتياطية (يمكن إضافتها لاحقاً)
                $response['backup_file'] = null;
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
            
            return response()->json([
                'success' => true,
                'metadata' => $metadata,
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
}

