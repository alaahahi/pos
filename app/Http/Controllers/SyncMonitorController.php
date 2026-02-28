<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
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
        $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);
        $onlineUrl = rtrim((string) env('ONLINE_URL', ''), '/');
        return Inertia::render('SyncMonitor/Index', [
            'translations' => __('messages'),
            'syncServerApiUrl' => $syncViaApi && $onlineUrl !== '' ? $onlineUrl : null,
        ]);
    }

    /**
     * جلب جميع البيانات المطلوبة في request واحد
     */
    public function getAllData(Request $request)
    {
        try {
            $forceConnection = $request->input('force_connection', 'auto');
            $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);

            // عند طلب MySQL والاعتماد على API: جلب بيانات الجداول من السيرفر وليس من الاتصال المحلي
            if (($forceConnection === 'mysql' || $forceConnection === 'auto') && $syncViaApi) {
                $apiSync = new \App\Services\ApiSyncService();
                if ($apiSync->isApiAvailable()) {
                    $serverResponse = $apiSync->getAllDataFromServer('mysql');
                    if (!empty($serverResponse['success']) && isset($serverResponse['tables'])) {
                        $tables = $serverResponse['tables'];
                        $databaseInfo = $serverResponse['database_info'] ?? ['type' => 'MySQL', 'total_tables' => count($tables)];
                        $defaultConnection = config('database.default');
                        $isSQLite = in_array($defaultConnection, ['sync_sqlite', 'sqlite']);
                        $metadata = [];
                        $queueStats = ['pending' => 0, 'synced' => 0, 'failed' => 0, 'total' => 0];
                        $backups = [];
                        if ($isSQLite) {
                            if (Schema::connection('sync_sqlite')->hasTable('sync_metadata')) {
                                $metadata = DB::connection('sync_sqlite')->table('sync_metadata')
                                    ->orderBy('table_name')->get()
                                    ->map(fn ($item) => [
                                        'table_name' => $item->table_name,
                                        'direction' => $item->direction,
                                        'last_synced_id' => $item->last_synced_id,
                                        'last_synced_at' => $item->last_synced_at,
                                        'total_synced' => $item->total_synced,
                                    ])->toArray();
                            }
                            if (Schema::connection('sync_sqlite')->hasTable('sync_queue')) {
                                $stats = DB::connection('sync_sqlite')->table('sync_queue')
                                    ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = "synced" THEN 1 ELSE 0 END) as synced, SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')->first();
                                $queueStats = [
                                    'pending' => (int) ($stats->pending ?? 0),
                                    'synced' => (int) ($stats->synced ?? 0),
                                    'failed' => (int) ($stats->failed ?? 0),
                                    'total' => (int) ($stats->total ?? 0),
                                ];
                            }
                            $backupDir = storage_path('app/backups');
                            if (is_dir($backupDir)) {
                                foreach (glob($backupDir . '/*.sql') as $file) {
                                    $backups[] = [
                                        'name' => basename($file),
                                        'path' => $file,
                                        'size' => filesize($file),
                                        'size_formatted' => $this->formatBytes(filesize($file)),
                                        'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                                        'type' => 'sql',
                                    ];
                                }
                            }
                            if ($forceConnection === 'auto') {
                                $sqlitePath = config('database.connections.sync_sqlite.database');
                                if ($sqlitePath && file_exists($sqlitePath)) {
                                    try {
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
                                    } catch (\Exception $e) {
                                        // ignore
                                    }
                                }
                            }
                        } else {
                            $metadata = $serverResponse['metadata'] ?? [];
                            $queueStats = $serverResponse['queue_stats'] ?? $queueStats;
                            $backups = $serverResponse['backups'] ?? [];
                        }
                        $databaseInfo['total_tables'] = count($tables);
                        return response()->json([
                            'success' => true,
                            'tables' => $tables,
                            'metadata' => $metadata,
                            'queue_stats' => $queueStats,
                            'backups' => $backups,
                            'database_info' => $databaseInfo,
                            'from_server_api' => true,
                        ]);
                    }
                }
            }

            // جلب الجداول من الاتصال المحلي (MySQL و/أو SQLite)
            $tables = [];
            if (!$forceConnection || $forceConnection === 'mysql' || $forceConnection === 'auto') {
                try {
                    $mysqlTables = DB::connection('mysql')->select('SHOW TABLES');
                    $dbName = DB::connection('mysql')->getDatabaseName();
                    $tableKey = "Tables_in_{$dbName}";
                    
                    foreach ($mysqlTables as $table) {
                        $tableName = $table->$tableKey;
                        try {
                            $rowCount = DB::connection('mysql')->table($tableName)->count();
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
            
            // sync_metadata و sync_queue موجودة فقط على المحلي (SQLite)
            $defaultConnection = config('database.default');
            $isSQLite = in_array($defaultConnection, ['sync_sqlite', 'sqlite']);
            
            if ($isSQLite && Schema::connection('sync_sqlite')->hasTable('sync_metadata')) {
                $metadata = DB::connection('sync_sqlite')->table('sync_metadata')
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

            // جلب إحصائيات sync_queue (فقط على المحلي)
            if ($isSQLite && Schema::connection('sync_sqlite')->hasTable('sync_queue')) {
                try {
                    $stats = DB::connection('sync_sqlite')->table('sync_queue')
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

            // تحديد نوع قاعدة البيانات وعدد الجداول بناءً على الاتصال الافتراضي
            $defaultConnection = config('database.default');
            $isSQLite = in_array($defaultConnection, ['sync_sqlite', 'sqlite']);
            
            // حساب عدد الجداول من الاتصال الصحيح فقط
            $totalTables = 0;
            $dbType = 'MySQL';
            
            if ($isSQLite) {
                // في النظام المحلي: حساب فقط جداول SQLite
                $totalTables = count(array_filter($tables, function($table) {
                    return $table['connection'] === 'sync_sqlite';
                }));
                $dbType = 'SQLite';
            } else {
                // في السيرفر: حساب فقط جداول MySQL
                $totalTables = count(array_filter($tables, function($table) {
                    return $table['connection'] === 'mysql';
                }));
                $dbType = 'MySQL';
            }
            
            // إذا لم يتم العثور على جداول من الاتصال المحدد، استخدم العدد الإجمالي
            if ($totalTables === 0 && count($tables) > 0) {
                $totalTables = count($tables);
            }
            
            return response()->json([
                'success' => true,
                'tables' => $tables,
                'metadata' => $metadata,
                'queue_stats' => $queueStats,
                'backups' => $backups,
                'database_info' => [
                    'type' => $dbType,
                    'total_tables' => $totalTables,
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
                    // MySQL - على السيرفر: استخدام MySQL فقط
                    if (!Schema::connection('mysql')->hasTable($tableName)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'الجدول غير موجود في MySQL',
                        ], 404);
                    }
                    
                    // جلب الأعمدة
                    $columns = Schema::connection('mysql')->getColumnListing($tableName);
                    
                    // جلب إجمالي السجلات
                    $total = DB::connection('mysql')->table($tableName)->count();
                    
                    // جلب البيانات
                    $data = DB::connection('mysql')->table($tableName)
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
     * إرجاع بنية جدول من MySQL (للمزامنة عبر API - يستدعيه النظام المحلي من السيرفر)
     */
    public function tableStructure(Request $request, $tableName)
    {
        try {
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'error' => 'الجدول غير موجود في MySQL',
                ], 404);
            }
            $columns = DB::connection('mysql')->select("SHOW COLUMNS FROM `{$tableName}`");
            $structure = array_map(function ($col) {
                return [
                    'Field' => $col->Field,
                    'Type' => $col->Type,
                    'Null' => $col->Null,
                    'Default' => $col->Default,
                    'Key' => $col->Key,
                ];
            }, $columns);
            return response()->json([
                'success' => true,
                'table' => $tableName,
                'columns' => $structure,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * بدء عملية المزامنة (يدعم async=1 لتشغيلها في الخلفية وتجنب 504)
     */
    public function sync(Request $request)
    {
        $direction = $request->input('direction', 'down');
        $tables = $request->input('tables');
        $safeMode = (bool) $request->input('safe_mode', false);
        $createBackup = (bool) $request->input('create_backup', false);
        $async = filter_var($request->input('async', false), FILTER_VALIDATE_BOOLEAN);

        if ($async) {
            $job = new \App\Jobs\FullSyncJob($direction, $tables, $safeMode, $createBackup);
            $jobId = $job->getJobId();
            dispatch($job)->onQueue('sync');
            Log::info('Full sync dispatched (async)', ['job_id' => $jobId, 'direction' => $direction]);
            return response()->json([
                'success' => true,
                'message' => 'تم بدء المزامنة في الخلفية - تابع الحالة عبر job_id',
                'job_id' => $jobId,
                'status' => 'queued',
                'direction' => $direction,
            ]);
        }

        set_time_limit(300);
        ignore_user_abort(true);
        try {
            $result = $this->runSyncInBackground($direction, $tables, $safeMode, $createBackup, null);
            return response()->json($result);
        } catch (\Exception $e) {
            try {
                DB::connection('sync_sqlite')->statement('PRAGMA foreign_keys = ON');
            } catch (\Exception $pragmaError) {
            }
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تنفيذ المزامنة (يُستدعى من الواجهة مباشرة أو من FullSyncJob)
     * @return array { success, message, direction, results, backup_file? }
     */
    public function runSyncInBackground($direction, $tables, bool $safeMode, bool $createBackup, ?string $jobId = null): array
    {
        $sqlitePath = config('database.connections.sync_sqlite.database');
        if (!file_exists($sqlitePath)) {
            $dir = dirname($sqlitePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            touch($sqlitePath);
            chmod($sqlitePath, 0666);
        }

        $results = [
            'success' => [],
            'failed' => [],
            'total_synced' => 0,
        ];

        $tablesToSync = [];
        if ($tables !== null && $tables !== '') {
            $tablesToSync = is_string($tables) ? array_map('trim', explode(',', $tables)) : (array) $tables;
        } else {
            if ($direction === 'down') {
                $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);
                if ($syncViaApi) {
                    $apiSync = new \App\Services\ApiSyncService();
                    if (!$apiSync->isApiAvailable()) {
                        throw new \Exception('API السيرفر غير متاح - تحقق من ONLINE_URL والاتصال');
                    }
                    $list = $apiSync->getTablesList();
                    if (!($list['success'] ?? false)) {
                        throw new \Exception('فشل جلب قائمة الجداول من API: ' . ($list['error'] ?? 'خطأ غير معروف'));
                    }
                    $tablesToSync = $list['tables'] ?? [];
                } else {
                    $mysqlTables = DB::connection('mysql')->select('SHOW TABLES');
                    $dbName = DB::connection('mysql')->getDatabaseName();
                    $tableKey = "Tables_in_{$dbName}";
                    foreach ($mysqlTables as $table) {
                        $tablesToSync[] = $table->$tableKey;
                    }
                }
            } else {
                $sqliteTables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($sqliteTables as $table) {
                    $tablesToSync[] = $table->name;
                }
            }
        }

        $syncViaApi = filter_var(env('SYNC_VIA_API', false), FILTER_VALIDATE_BOOLEAN);
        foreach ($tablesToSync as $tableName) {
            $tableName = trim($tableName);
            if ($tableName === '') {
                continue;
            }
            try {
                if ($direction === 'down' && $syncViaApi) {
                    $synced = $this->syncTableDownViaApi($tableName, $safeMode);
                } else {
                    $synced = $this->syncTable($tableName, $direction, $safeMode);
                }
                $results['success'][$tableName] = $synced;
                $results['total_synced'] += $synced;
            } catch (\Exception $e) {
                $results['failed'][$tableName] = $e->getMessage();
            }
        }

        DB::connection('sync_sqlite')->statement('PRAGMA foreign_keys = ON');

        $response = [
            'success' => true,
            'message' => 'تمت المزامنة بنجاح',
            'direction' => $direction,
            'results' => $results,
        ];

        if ($createBackup && $direction === 'up') {
            try {
                $response['backup_file'] = $this->createBackup();
            } catch (\Exception $e) {
                Log::warning('Failed to create backup: ' . $e->getMessage());
                $response['backup_file'] = null;
            }
        }

        return $response;
    }
    
    /**
     * مزامنة جدول واحد
     */
    private function syncTable($tableName, $direction, $safeMode = false)
    {
        $syncedCount = 0;
        
        if ($direction === 'down') {
            // من MySQL إلى SQLite
            // على السيرفر: استخدام MySQL فقط
            if (!Schema::connection('mysql')->hasTable($tableName)) {
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
            
            // تعطيل Foreign Key Checks في SQLite لتجنب مشاكل الترتيب
            DB::connection('sync_sqlite')->statement('PRAGMA foreign_keys = OFF');
            
            // نسخ البيانات فقط للجداول غير المستثناة (جداول "بنية فقط" في config/sync لا تُنسخ بياناتها)
            $structureOnlyTables = config('sync.structure_only_tables', []);
            if (!in_array($tableName, $structureOnlyTables)) {
                // جلب البيانات من MySQL (باستخدام chunks للجداول الكبيرة)
                $batchSize = 500;
                
                // محاولة استخدام id للترتيب، وإلا بدون ترتيب
                try {
                    // على السيرفر: استخدام MySQL فقط
                    $query = DB::connection('mysql')->table($tableName);
                    $columns = Schema::connection('mysql')->getColumnListing($tableName);
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
                    // على السيرفر: استخدام MySQL فقط
                    $mysqlData = DB::connection('mysql')->table($tableName)->get();
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
            // على السيرفر: استخدام MySQL فقط
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                $this->createTableInMySQL($tableName);
            }
            
            // عدم رفع جداول "لا تُرفع" من المحلي إلى السيرفر (no_push_tables: migrations، Spatie، إلخ)
            $noPushTables = config('sync.no_push_tables', []);
            if (!in_array($tableName, $noPushTables)) {
                // جلب البيانات من SQLite
                $sqliteData = DB::connection('sync_sqlite')->table($tableName)->get();
                
                // إدراج البيانات في MySQL
                // على السيرفر: استخدام MySQL فقط
                foreach ($sqliteData as $row) {
                    try {
                        $rowArray = (array) $row;
                        
                        if ($safeMode) {
                            // Safe Mode: إضافة فقط إذا لم يكن موجوداً
                            $query = DB::connection('mysql')->table($tableName);
                            
                            // استخدام id إذا كان موجوداً
                            if (isset($rowArray['id'])) {
                                $exists = $query->where('id', $rowArray['id'])->exists();
                            } else {
                                $exists = false;
                            }
                            
                            if (!$exists) {
                                DB::connection('mysql')->table($tableName)->insert($rowArray);
                                $syncedCount++;
                            }
                        } else {
                            // إدراج أو تحديث
                            if (isset($rowArray['id'])) {
                                DB::connection('mysql')->table($tableName)->updateOrInsert(
                                    ['id' => $rowArray['id']],
                                    $rowArray
                                );
                            } else {
                                // إدراج فقط إذا لم يكن هناك id
                                DB::connection('mysql')->table($tableName)->insert($rowArray);
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
        }
        
        // تحديث metadata
        $this->updateSyncMetadata($tableName, $direction, $syncedCount);
        
        return $syncedCount;
    }

    /**
     * مزامنة جدول واحد من السيرفر إلى المحلي عبر API فقط (بدون اتصال MySQL محلي)
     */
    private function syncTableDownViaApi(string $tableName, bool $safeMode = false): int
    {
        $apiSync = new \App\Services\ApiSyncService();
        $syncedCount = 0;

        $sqlitePath = config('database.connections.sync_sqlite.database');
        if (!file_exists($sqlitePath)) {
            $dir = dirname($sqlitePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            touch($sqlitePath);
            chmod($sqlitePath, 0666);
        }

        $structure = $apiSync->getTableStructure($tableName);
        if (!($structure['success'] ?? false) || empty($structure['columns'])) {
            throw new \Exception('فشل جلب بنية الجدول من API: ' . ($structure['error'] ?? 'بنية فارغة'));
        }

        if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
            $this->createTableInSQLiteFromStructure($tableName, $structure['columns']);
        }

        DB::connection('sync_sqlite')->statement('PRAGMA foreign_keys = OFF');

        $structureOnlyTables = config('sync.structure_only_tables', []);
        if (!in_array($tableName, $structureOnlyTables)) {
            $limit = 500;
            $offset = 0;
            do {
                $result = $apiSync->getTableData($tableName, $limit, $offset);
                if (!($result['success'] ?? false) || empty($result['data'])) {
                    break;
                }
                foreach ($result['data'] as $row) {
                    try {
                        $rowArray = is_array($row) ? $row : (array) $row;
                        if ($safeMode && isset($rowArray['id'])) {
                            $exists = DB::connection('sync_sqlite')->table($tableName)->where('id', $rowArray['id'])->exists();
                            if (!$exists) {
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
                    } catch (\Exception $e) {
                        Log::warning("Failed to sync row via API in {$tableName}: " . $e->getMessage());
                    }
                }
                $offset += $limit;
            } while (count($result['data'] ?? []) >= $limit);
        }

        $this->updateSyncMetadata($tableName, 'down', $syncedCount);
        return $syncedCount;
    }

    /**
     * إنشاء جدول في SQLite من بنية مُرجعة من API (بدون الاتصال بـ MySQL)
     */
    private function createTableInSQLiteFromStructure(string $tableName, array $columns): void
    {
        if (empty($columns)) {
            throw new \Exception("لا توجد أعمدة لجدول {$tableName}");
        }
        $columnDefinitions = [];
        $primaryKeyColumns = [];
        foreach ($columns as $col) {
            $field = $col['Field'] ?? $col['field'] ?? '';
            $type = $this->convertMySQLTypeToSQLite($col['Type'] ?? $col['type'] ?? 'TEXT');
            $null = (($col['Null'] ?? $col['null'] ?? 'YES') === 'YES') ? '' : 'NOT NULL';
            $default = '';
            $defaultVal = $col['Default'] ?? $col['default'] ?? null;
            if ($defaultVal !== null && $defaultVal !== '') {
                $default = is_numeric($defaultVal) ? "DEFAULT {$defaultVal}" : "DEFAULT '" . addslashes((string) $defaultVal) . "'";
            }
            $key = $col['Key'] ?? $col['key'] ?? '';
            $columnDefinitions[] = "`{$field}` {$type} {$null} {$default}";
            if ($key === 'PRI') {
                $primaryKeyColumns[] = "`{$field}`";
            }
        }
        $createTable = "CREATE TABLE IF NOT EXISTS `{$tableName}` (" . implode(', ', $columnDefinitions);
        if (!empty($primaryKeyColumns)) {
            $createTable .= ', PRIMARY KEY (' . implode(', ', $primaryKeyColumns) . ')';
        }
        $createTable .= ')';
        DB::connection('sync_sqlite')->statement($createTable);
    }
    
    /**
     * إنشاء جدول في SQLite بناءً على MySQL
     */
    private function createTableInSQLite($tableName)
    {
        try {
            // جلب بنية الجدول من MySQL
            // على السيرفر: استخدام MySQL فقط
            $columns = DB::connection('mysql')->select("SHOW COLUMNS FROM `{$tableName}`");
            
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
            
            // على السيرفر: استخدام MySQL فقط
            DB::connection('mysql')->statement($mysqlSql);
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
        // sync_metadata موجود فقط على المحلي (SQLite)
        // على السيرفر: تجاهل هذا الجدول
        $defaultConnection = config('database.default');
        $isSQLite = in_array($defaultConnection, ['sync_sqlite', 'sqlite']);
        
        if (!$isSQLite) {
            return; // على السيرفر: لا حاجة لـ sync_metadata
        }
        
        if (!Schema::connection('sync_sqlite')->hasTable('sync_metadata')) {
            return;
        }
        
        $existing = DB::connection('sync_sqlite')->table('sync_metadata')
            ->where('table_name', $tableName)
            ->where('direction', $direction)
            ->first();
        
        if ($existing) {
            DB::connection('sync_sqlite')->table('sync_metadata')
                ->where('table_name', $tableName)
                ->where('direction', $direction)
                ->update([
                    'last_synced_at' => now(),
                    'last_updated_at' => now(),
                    'total_synced' => $existing->total_synced + $syncedCount,
                ]);
        } else {
            DB::connection('sync_sqlite')->table('sync_metadata')->insert([
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
            
            // sync_metadata موجودة فقط على المحلي (SQLite)
            $defaultConnection = config('database.default');
            $isSQLite = in_array($defaultConnection, ['sync_sqlite', 'sqlite']);
            
            if ($isSQLite && Schema::connection('sync_sqlite')->hasTable('sync_metadata')) {
                $metadata = DB::connection('sync_sqlite')->table('sync_metadata')
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
     * تشغيل المهام المجدولة مرة واحدة (schedule:run)
     * مثل مشروع shipping - لتشغيل auto-sync فوراً بدون انتظار cron.
     */
    public function runSchedule(Request $request)
    {
        try {
            if (!$this->isLocalEnvironment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الإجراء متاح فقط في البيئة المحلية',
                ], 403);
            }

            $exitCode = Artisan::call('schedule:run');
            $output = trim((string) Artisan::output());

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'تم تشغيل المهام المجدولة' : 'انتهى مع أخطاء',
                'exit_code' => $exitCode,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Run schedule failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'فشل تشغيل المهام المجدولة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تشغيل Queue Worker مرة واحدة (queue:work --once)
     * الهدف: معالجة sync jobs من الواجهة بدون تشغيل worker دائم.
     */
    public function runWorkerOnce(Request $request)
    {
        try {
            if (!$this->isLocalEnvironment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الإجراء متاح فقط في البيئة المحلية',
                ], 403);
            }

            $queue = (string) ($request->input('queue', 'sync') ?? 'sync');
            $timeout = (int) ($request->input('timeout', 60) ?? 60);
            if ($timeout < 10) {
                $timeout = 10;
            }
            if ($timeout > 600) {
                $timeout = 600;
            }

            $exitCode = Artisan::call('queue:work', [
                '--once' => true,
                '--queue' => $queue,
                '--sleep' => 1,
                '--tries' => 1,
                '--timeout' => $timeout,
            ]);
            $output = trim((string) Artisan::output());

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'تم تشغيل الـ Worker مرة واحدة' : 'انتهى تشغيل الـ Worker مع أخطاء',
                'exit_code' => $exitCode,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Run worker once failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'فشل تشغيل الـ Worker',
                'error' => $e->getMessage(),
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
     * الحصول على تفاصيل sync_queue بحسب الحالة
     */
    public function getSyncQueueDetails(Request $request)
    {
        try {
            $status = $request->input('status', 'pending'); // pending, synced, failed
            $tableName = $request->input('table_name'); // اختياري
            $limit = $request->input('limit', 100);
            $offset = $request->input('offset', 0);
            
            $connection = config('database.default');
            
            if (!Schema::hasTable('sync_queue')) {
                return response()->json([
                    'success' => false,
                    'message' => 'جدول sync_queue غير موجود',
                    'changes' => [],
                    'total' => 0,
                ]);
            }
            
            $query = DB::connection($connection)->table('sync_queue')
                ->where('status', $status);
            
            if ($tableName) {
                $query->where('table_name', $tableName);
            }
            
            // جلب العدد الإجمالي
            $total = $query->count();
            
            // جلب البيانات مع pagination
            $changes = $query->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'table_name' => $item->table_name,
                        'record_id' => $item->record_id,
                        'action' => $item->action,
                        'data' => $item->data ? json_decode($item->data, true) : null,
                        'changes' => $item->changes ? json_decode($item->changes, true) : null,
                        'status' => $item->status,
                        'retry_count' => $item->retry_count,
                        'error_message' => $item->error_message,
                        'synced_at' => $item->synced_at,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'changes' => $changes,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get sync queue details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'changes' => [],
                'total' => 0,
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
        // على السيرفر: استخدام MySQL فقط
        $tables = DB::connection('mysql')->select('SHOW TABLES');
        $tableKey = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // استثناء جداول النظام والجداول غير المهمة
            if (in_array($tableName, ['migrations', 'sync_metadata', 'sync_queue', 'sync_id_mapping', 'logs'])) {
                continue;
            }

            // جلب البيانات فقط (بدون بنية الجدول)
            // على السيرفر: استخدام MySQL فقط
            $rows = DB::connection('mysql')->table($tableName)->get();
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
     * جلب معلومات المزامنة التلقائية
     */
    public function getAutoSyncStatus(Request $request)
    {
        try {
            $status = [
                'enabled' => false,
                'is_local' => $this->isLocalEnvironment(),
                'last_sync_at' => null,
                'next_sync_in' => null,
                'sync_interval' => 5, // بالدقائق
                'is_running' => false,
                'schedule_running' => false,
                'scheduler_hint' => 'للمزامنة كل 5 دقائق شغّل في التيرمنال: php artisan schedule:work أو انقر مرتين على run-scheduler.vbs في مجلد المشروع.',
            ];

            // التحقق من البيئة المحلية
            if (!$status['is_local']) {
                return response()->json([
                    'success' => true,
                    'status' => $status,
                    'message' => 'Auto-sync is only available in local environment'
                ]);
            }

            // التحقق من تفعيل المزامنة التلقائية في .env
            $autoSyncEnabled = filter_var(env('AUTO_SYNC_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
            
            // التحقق من وجود Laravel Scheduler يعمل (ملفات قفل في framework)
            try {
                $scheduleFiles = glob(storage_path('framework/schedule-*'));
                $status['schedule_running'] = !empty($scheduleFiles);
            } catch (\Exception $e) {
                // Ignore
            }

            // التحقق من جدول sync_metadata
            $lastSync = null;
            try {
                $lastSync = DB::connection('sync_sqlite')
                    ->table('sync_metadata')
                    ->where('table_name', 'auto_sync')
                    ->orderBy('synced_at', 'desc')
                    ->first();

                if ($lastSync) {
                    $status['last_sync_at'] = $lastSync->synced_at;
                    
                    // حساب الوقت المتبقي للمزامنة القادمة
                    $lastSyncTime = \Carbon\Carbon::parse($lastSync->synced_at);
                    $nextSyncTime = $lastSyncTime->addMinutes(5);
                    $now = \Carbon\Carbon::now();
                    
                    if ($nextSyncTime->isFuture()) {
                        $status['next_sync_in'] = $now->diffInSeconds($nextSyncTime);
                    } else {
                        // المزامنة متأخرة
                        $status['next_sync_in'] = 0;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not get auto-sync status: ' . $e->getMessage());
            }

            // إذا لم يكن هناك سجل مزامنة سابق، لكن المزامنة مفعّلة
            // نفترض أن المزامنة القادمة بعد 5 دقائق من الآن
            if (!$lastSync && $autoSyncEnabled) {
                $status['next_sync_in'] = 5 * 60; // 5 دقائق
                $status['last_sync_at'] = null;
            }

            // تحديد حالة enabled بناءً على:
            // 1. إعدادات .env
            // 2. وجود سجل مزامنة أو تفعيل في .env
            $status['enabled'] = $autoSyncEnabled && ($lastSync !== null || $autoSyncEnabled);

            // التحقق من وجود عملية مزامنة جارية
            try {
                $runningSync = DB::connection('sync_sqlite')
                    ->table('sync_queue')
                    ->where('status', 'syncing')
                    ->exists();
                
                $status['is_running'] = $runningSync;
            } catch (\Exception $e) {
                // Ignore
            }

            return response()->json([
                'success' => true,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking auto-sync status',
                'error' => $e->getMessage(),
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

            // TODO: للتجريب - تحويل إلى warning. بعد التجريب، إعادة تحويله إلى issue
            if (empty(env('SYNC_API_TOKEN'))) {
                $health['warnings'][] = 'SYNC_API_TOKEN غير محدد في .env (للتجريب - يمكن العمل بدون توكن)';
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

    /**
     * استقبال طلبات المزامنة من السيرفر (insert, update, delete)
     * هذا endpoint يستقبل الطلبات من النظام المحلي ويقوم بتنفيذها على قاعدة البيانات
     */
    public function apiSync(Request $request)
    {
        try {
            $tableName = $request->input('table_name');
            $recordId = $request->input('record_id');
            $action = $request->input('action'); // insert, update, delete
            $data = $request->input('data', []); // للـ insert و update

            // التحقق من البيانات المطلوبة
            if (!$tableName) {
                return response()->json([
                    'success' => false,
                    'message' => 'table_name مطلوب',
                ], 400);
            }

            if (!$action || !in_array($action, ['insert', 'update', 'delete'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'action مطلوب ويجب أن يكون insert أو update أو delete',
                ], 400);
            }

            if (in_array($action, ['update', 'delete']) && !$recordId) {
                return response()->json([
                    'success' => false,
                    'message' => 'record_id مطلوب للـ ' . $action,
                ], 400);
            }

            // على السيرفر: استخدام MySQL فقط
            // التحقق من وجود الجدول في MySQL
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                Log::warning('Table not found for API sync', [
                    'table' => $tableName,
                    'action' => $action,
                    'record_id' => $recordId,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => "الجدول {$tableName} غير موجود في MySQL",
                ], 404);
            }

            Log::info('API sync request received', [
                'table' => $tableName,
                'action' => $action,
                'record_id' => $recordId,
                'has_data' => !empty($data),
            ]);

            // تنفيذ العملية حسب النوع
            $result = null;
            switch ($action) {
                case 'insert':
                    if (empty($data)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'data مطلوب للـ insert',
                        ], 400);
                    }

                    $uuidTables = config('sync.uuid_tables', []);
                    $isUuidTable = in_array($tableName, $uuidTables, true);

                    // إزالة id من البيانات للجداول العادية (السيرفر سينشئ id جديد)
                    if (!$isUuidTable) {
                        unset($data['id']);
                    }

                    // إزالة deleted_at للعمليات insert (لا نريد إدراج سجل محذوف)
                    unset($data['deleted_at']);

                    // على السيرفر: استخدام MySQL فقط
                    if ($isUuidTable && isset($data['id'])) {
                        DB::connection('mysql')->table($tableName)->insert($data);
                        $insertedId = $data['id'];
                    } else {
                        $insertedId = DB::connection('mysql')->table($tableName)->insertGetId($data);
                    }
                    $result = DB::connection('mysql')->table($tableName)->where('id', $insertedId)->first();

                    Log::info('API sync insert succeeded', [
                        'table' => $tableName,
                        'inserted_id' => $insertedId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'تم إدراج السجل بنجاح',
                        'data' => $result,
                        'inserted_id' => $insertedId,
                    ]);

                case 'update':
                    if (empty($data)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'data مطلوب للـ update',
                        ], 400);
                    }
                    
                    // على السيرفر: استخدام MySQL فقط
                    // التحقق من وجود السجل
                    $exists = DB::connection('mysql')->table($tableName)->where('id', $recordId)->exists();
                    if (!$exists) {
                        Log::warning('Record not found for API sync update', [
                            'table' => $tableName,
                            'record_id' => $recordId,
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => "السجل {$recordId} غير موجود في الجدول {$tableName}",
                        ], 404);
                    }
                    
                    // إزالة id من البيانات (لا يمكن تحديث id)
                    unset($data['id']);
                    
                    DB::connection('mysql')->table($tableName)->where('id', $recordId)->update($data);
                    $result = DB::connection('mysql')->table($tableName)->where('id', $recordId)->first();
                    
                    Log::info('API sync update succeeded', [
                        'table' => $tableName,
                        'record_id' => $recordId,
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'تم تحديث السجل بنجاح',
                        'data' => $result,
                    ]);

                case 'delete':
                    // على السيرفر: استخدام MySQL فقط
                    // التحقق من وجود السجل
                    $exists = DB::connection('mysql')->table($tableName)->where('id', $recordId)->exists();
                    if (!$exists) {
                        Log::warning('Record not found for API sync delete', [
                            'table' => $tableName,
                            'record_id' => $recordId,
                        ]);
                        
                        // في حالة الحذف، إذا كان السجل غير موجود، نعتبر العملية ناجحة
                        return response()->json([
                            'success' => true,
                            'message' => 'السجل غير موجود (تم حذفه مسبقاً)',
                        ]);
                    }
                    
                    DB::connection('mysql')->table($tableName)->where('id', $recordId)->delete();
                    
                    Log::info('API sync delete succeeded', [
                        'table' => $tableName,
                        'record_id' => $recordId,
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'تم حذف السجل بنجاح',
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'action غير معروف',
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error('API sync failed', [
                'error' => $e->getMessage(),
                'table' => $request->input('table_name'),
                'action' => $request->input('action'),
                'record_id' => $request->input('record_id'),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تنفيذ المزامنة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * مقارنة البيانات بين السيرفر والمحلي
     */
    public function compareTables(Request $request)
    {
        try {
            $tableName = $request->input('table_name');
            $limit = (int) $request->input('limit', 1000); // حد أقصى للسجلات للمقارنة

            if (!$tableName) {
                return response()->json([
                    'success' => false,
                    'message' => 'table_name مطلوب',
                ], 400);
            }

            // التحقق من وجود الجدول محلياً
            if (!Schema::hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "الجدول {$tableName} غير موجود محلياً",
                ], 404);
            }

            Log::info('Starting table comparison', [
                'table' => $tableName,
                'limit' => $limit,
            ]);

            // جلب البيانات من SQLite المحلي (المصدر)
            // المزامنة تكون من SQLite المحلي إلى MySQL على السيرفر
            $sqlitePath = config('database.connections.sync_sqlite.database');
            if (!file_exists($sqlitePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف SQLite غير موجود',
                ], 404);
            }

            if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "الجدول {$tableName} غير موجود في SQLite المحلي",
                ], 404);
            }

            $localData = DB::connection('sync_sqlite')->table($tableName)
                ->limit($limit)
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->keyBy('id') // استخدام id كـ key لتسهيل المقارنة
                ->toArray();

            $localCount = count($localData);

            // جلب البيانات من السيرفر
            $apiSyncService = new \App\Services\ApiSyncService();
            $serverResult = $apiSyncService->getTableData($tableName, $limit, 0);

            if (!$serverResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب البيانات من السيرفر: ' . ($serverResult['error'] ?? 'خطأ غير معروف'),
                    'local_count' => $localCount,
                ], 500);
            }

            $serverData = collect($serverResult['data'])->keyBy('id')->toArray();
            $serverCount = count($serverData);

            // المقارنة
            $localIds = array_keys($localData);
            $serverIds = array_keys($serverData);

            // السجلات الموجودة في المحلي فقط
            $onlyLocal = array_diff($localIds, $serverIds);
            
            // السجلات الموجودة في السيرفر فقط
            $onlyServer = array_diff($serverIds, $localIds);
            
            // السجلات الموجودة في كلا المكانين (للمقارنة التفصيلية)
            $commonIds = array_intersect($localIds, $serverIds);
            
            // مقارنة السجلات المشتركة للتحقق من الاختلافات
            $differences = [];
            foreach ($commonIds as $id) {
                $localRecord = $localData[$id];
                $serverRecord = $serverData[$id];
                
                // إزالة timestamps من المقارنة (قد تختلف)
                unset($localRecord['created_at'], $localRecord['updated_at']);
                unset($serverRecord['created_at'], $serverRecord['updated_at']);
                
                // ترتيب المفاتيح قبل المقارنة (لضمان مقارنة دقيقة)
                ksort($localRecord);
                ksort($serverRecord);
                
                // مقارنة السجلات
                $localJson = json_encode($localRecord, JSON_UNESCAPED_UNICODE);
                $serverJson = json_encode($serverRecord, JSON_UNESCAPED_UNICODE);
                
                if ($localJson !== $serverJson) {
                    // إيجاد الحقول المختلفة
                    $diffFields = [];
                    foreach ($localRecord as $key => $localValue) {
                        $serverValue = $serverRecord[$key] ?? null;
                        if ($localValue != $serverValue) {
                            $diffFields[$key] = [
                                'local' => $localValue,
                                'server' => $serverValue,
                            ];
                        }
                    }
                    
                    $differences[] = [
                        'id' => $id,
                        'fields' => $diffFields,
                        'local_record' => $localRecord,
                        'server_record' => $serverRecord,
                    ];
                }
            }

            // ملخص المقارنة
            $summary = [
                'table_name' => $tableName,
                'local_count' => $localCount,
                'server_count' => $serverCount,
                'common_count' => count($commonIds),
                'only_local_count' => count($onlyLocal),
                'only_server_count' => count($onlyServer),
                'differences_count' => count($differences),
                'matched_count' => count($commonIds) - count($differences),
                'is_identical' => count($onlyLocal) === 0 && count($onlyServer) === 0 && count($differences) === 0,
            ];

            // عينة من السجلات المختلفة (أول 10 فقط)
            $differencesSample = array_slice($differences, 0, 10);
            $onlyLocalSample = array_slice($onlyLocal, 0, 10);
            $onlyServerSample = array_slice($onlyServer, 0, 10);

            Log::info('Table comparison completed', [
                'table' => $tableName,
                'summary' => $summary,
            ]);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'differences' => $differencesSample, // عينة فقط لتوفير البيانات
                'only_local_ids' => $onlyLocalSample, // عينة فقط
                'only_server_ids' => $onlyServerSample, // عينة فقط
                'total_differences' => count($differences),
                'total_only_local' => count($onlyLocal),
                'total_only_server' => count($onlyServer),
            ]);
        } catch (\Exception $e) {
            Log::error('Table comparison failed', [
                'error' => $e->getMessage(),
                'table' => $request->input('table_name'),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء المقارنة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تنظيف البيانات قبل إرسالها للسيرفر
     * - تحويل timestamps من ISO 8601 إلى MySQL format
     * - إزالة deleted_at للعمليات insert
     * - إزالة id (السيرفر سينشئ id جديد)
     */
    protected function cleanDataForSync(array $data, string $action = 'insert'): array
    {
        // إزالة id - السيرفر سينشئ id جديد
        unset($data['id']);
        
        // للعمليات insert، إزالة deleted_at (لا نريد إدراج سجل محذوف)
        if ($action === 'insert') {
            unset($data['deleted_at']);
        }
        
        // تحويل timestamps من ISO 8601 إلى MySQL format (Y-m-d H:i:s)
        $timestampFields = ['created_at', 'updated_at', 'deleted_at'];
        foreach ($timestampFields as $field) {
            if (isset($data[$field])) {
                $timestampValue = $data[$field];
                
                // إذا كانت القيمة بصيغة ISO 8601 (تحتوي على T أو Z)
                if (is_string($timestampValue) && (strpos($timestampValue, 'T') !== false || strpos($timestampValue, 'Z') !== false)) {
                    try {
                        // تحويل من ISO 8601 إلى Carbon ثم إلى MySQL format
                        $carbon = \Carbon\Carbon::parse($timestampValue);
                        $data[$field] = $carbon->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        // إذا فشل التحويل، إزالة الحقل أو ترك القيمة الحالية
                        \Log::warning('Failed to convert timestamp', [
                            'field' => $field,
                            'value' => $timestampValue,
                            'error' => $e->getMessage(),
                        ]);
                        // للعمليات insert، نفضل إزالة timestamps والسماح للسيرفر بإنشائها
                        if ($action === 'insert' && in_array($field, ['created_at', 'updated_at'])) {
                            unset($data[$field]);
                        }
                    }
                }
            }
        }
        
        return $data;
    }

    /**
     * مزامنة البيانات من السيرفر إلى المحلي: جلب البيانات من MySQL على السيرفر عبر API وإدراجها في SQLite المحلي
     */
    public function syncFromServer(Request $request)
    {
        try {
            $tableName = $request->input('table_name');
            $limit = (int) $request->input('limit', 1000);

            if (!$tableName) {
                return response()->json([
                    'success' => false,
                    'message' => 'table_name مطلوب',
                ], 400);
            }

            // التحقق من أننا في النظام المحلي (لا يمكن تنفيذ هذا على السيرفر)
            $appEnv = config('app.env');
            if ($appEnv === 'server' || $appEnv === 'production') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الـ endpoint متاح فقط في النظام المحلي',
                ], 400);
            }

            // التحقق من وجود الجدول في SQLite المحلي
            $sqlitePath = config('database.connections.sync_sqlite.database');
            if (!file_exists($sqlitePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف SQLite غير موجود',
                ], 404);
            }

            if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "الجدول {$tableName} غير موجود في SQLite المحلي",
                ], 404);
            }

            Log::info('Starting sync from server', [
                'table' => $tableName,
                'limit' => $limit,
            ]);

            // جلب البيانات من السيرفر عبر API
            $apiSyncService = new \App\Services\ApiSyncService();
            $serverResult = $apiSyncService->getTableData($tableName, $limit, 0);

            if (!$serverResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب البيانات من السيرفر: ' . ($serverResult['error'] ?? 'خطأ غير معروف'),
                ], 500);
            }

            $serverData = $serverResult['data'] ?? [];
            
            if (empty($serverData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد بيانات في السيرفر',
                    'synced' => 0,
                    'failed' => 0,
                ]);
            }

            // إدراج البيانات في SQLite المحلي
            $synced = 0;
            $failed = 0;
            $errors = [];

            foreach ($serverData as $record) {
                try {
                    $recordId = $record['id'] ?? null;
                    
                    if (!$recordId) {
                        $failed++;
                        $errors[] = "سجل بدون id";
                        continue;
                    }

                    // التحقق من وجود السجل في SQLite
                    $exists = DB::connection('sync_sqlite')->table($tableName)
                        ->where('id', $recordId)
                        ->exists();

                    if ($exists) {
                        // تحديث السجل الموجود
                        DB::connection('sync_sqlite')->table($tableName)
                            ->where('id', $recordId)
                            ->update($record);
                        $synced++;
                        Log::debug('Updated record from server', [
                            'table' => $tableName,
                            'record_id' => $recordId,
                        ]);
                    } else {
                        // إدراج سجل جديد
                        DB::connection('sync_sqlite')->table($tableName)->insert($record);
                        $synced++;
                        Log::debug('Inserted record from server', [
                            'table' => $tableName,
                            'record_id' => $recordId,
                        ]);
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $recordId = $record['id'] ?? 'unknown';
                    $errors[] = "السجل {$recordId}: " . $e->getMessage();
                    Log::error('Exception syncing record from server', [
                        'table' => $tableName,
                        'record_id' => $recordId ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "تمت مزامنة {$synced} سجل من السيرفر، فشل {$failed}",
                'synced' => $synced,
                'failed' => $failed,
                'total_from_server' => count($serverData),
                'errors' => array_slice($errors, 0, 10), // أول 10 أخطاء فقط
            ]);
        } catch (\Exception $e) {
            Log::error('Sync from server failed', [
                'error' => $e->getMessage(),
                'table' => $request->input('table_name'),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء المزامنة من السيرفر: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * مزامنة السجلات المفقودة: مزامنة السجلات الموجودة في SQLite المحلي والتي لم تتم مزامنتها إلى MySQL على السيرفر
     */
    public function syncMissingRecords(Request $request)
    {
        try {
            $tableName = $request->input('table_name');
            $limit = (int) $request->input('limit', 1000);

            if (!$tableName) {
                return response()->json([
                    'success' => false,
                    'message' => 'table_name مطلوب',
                ], 400);
            }

            // التحقق من وجود الجدول في SQLite
            $sqlitePath = config('database.connections.sync_sqlite.database');
            if (!file_exists($sqlitePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف SQLite غير موجود',
                ], 404);
            }

            if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => "الجدول {$tableName} غير موجود في SQLite المحلي",
                ], 404);
            }

            Log::info('Starting sync missing records', [
                'table' => $tableName,
                'limit' => $limit,
            ]);

            // جلب جميع السجلات من SQLite المحلي
            $localRecords = DB::connection('sync_sqlite')->table($tableName)
                ->limit($limit)
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray();

            if (empty($localRecords)) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد سجلات في SQLite المحلي',
                    'synced' => 0,
                    'failed' => 0,
                ]);
            }

            // جلب IDs الموجودة في MySQL على السيرفر
            $apiSyncService = new \App\Services\ApiSyncService();
            $serverResult = $apiSyncService->getTableData($tableName, $limit * 2, 0); // جلب أكثر للتأكد

            if (!$serverResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب البيانات من السيرفر: ' . ($serverResult['error'] ?? 'خطأ غير معروف'),
                ], 500);
            }

            $serverIds = collect($serverResult['data'])->pluck('id')->toArray();

            // إيجاد السجلات المفقودة (موجودة في SQLite وليست في MySQL)
            $missingRecords = [];
            foreach ($localRecords as $record) {
                $recordId = $record['id'] ?? null;
                if ($recordId && !in_array($recordId, $serverIds)) {
                    $missingRecords[] = $record;
                }
            }

            if (empty($missingRecords)) {
                return response()->json([
                    'success' => true,
                    'message' => 'جميع السجلات موجودة في السيرفر',
                    'synced' => 0,
                    'failed' => 0,
                    'total_checked' => count($localRecords),
                ]);
            }

            Log::info('Found missing records to sync', [
                'table' => $tableName,
                'missing_count' => count($missingRecords),
                'total_checked' => count($localRecords),
            ]);

            // مزامنة السجلات المفقودة
            $synced = 0;
            $failed = 0;
            $errors = [];

            foreach ($missingRecords as $record) {
                try {
                    $recordId = $record['id'];
                    
                    // تنظيف البيانات قبل الإرسال (تحويل timestamps، إزالة deleted_at و id)
                    $cleanedRecord = $this->cleanDataForSync($record, 'insert');
                    
                    // إرسال السجل إلى السيرفر
                    $syncResult = $apiSyncService->syncInsert($tableName, $cleanedRecord);
                    
                    if ($syncResult['success'] ?? false) {
                        $synced++;
                        Log::debug('Synced missing record', [
                            'table' => $tableName,
                            'record_id' => $recordId,
                        ]);
                    } else {
                        $failed++;
                        $errorMsg = $syncResult['error'] ?? 'Unknown error';
                        $errors[] = "السجل {$recordId}: {$errorMsg}";
                        Log::warning('Failed to sync missing record', [
                            'table' => $tableName,
                            'record_id' => $recordId,
                            'error' => $errorMsg,
                        ]);
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "السجل {$record['id']}: " . $e->getMessage();
                    Log::error('Exception syncing missing record', [
                        'table' => $tableName,
                        'record_id' => $record['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "تمت مزامنة {$synced} سجل، فشل {$failed}",
                'synced' => $synced,
                'failed' => $failed,
                'total_checked' => count($localRecords),
                'total_missing' => count($missingRecords),
                'errors' => array_slice($errors, 0, 10), // أول 10 أخطاء فقط
            ]);
        } catch (\Exception $e) {
            Log::error('Sync missing records failed', [
                'error' => $e->getMessage(),
                'table' => $request->input('table_name'),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة السجلات المفقودة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * جلب قائمة Jobs للمزامنة من السيرفر
     */
    public function getSyncFromServerJobs(Request $request)
    {
        try {
            $status = $request->input('status'); // pending, processing, completed, failed
            $limit = (int) $request->input('limit', 50);
            $offset = (int) $request->input('offset', 0);

            // استخدام جدول jobs من Laravel
            $queueConnection = config('queue.default');
            $connection = config("queue.connections.{$queueConnection}.connection") ?? config('database.default');
            
            if (!Schema::connection($connection)->hasTable('jobs')) {
                return response()->json([
                    'success' => true,
                    'jobs' => [],
                    'total' => 0,
                    'message' => 'جدول jobs غير موجود',
                ]);
            }

            $query = DB::connection($connection)->table('jobs')
                ->where('queue', 'sync-from-server')
                ->orderBy('id', 'desc');

            $total = $query->count();
            
            $jobs = $query->offset($offset)->limit($limit)->get()->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown';
                $jobId = null;
                
                // محاولة استخراج job_id من البيانات
                if (isset($payload['data']['command'])) {
                    $commandData = unserialize($payload['data']['command']);
                    if (is_object($commandData) && method_exists($commandData, 'getJobId')) {
                        $jobId = $commandData->getJobId();
                    }
                }
                
                // استخراج معلومات من payload
                $tableName = null;
                $recordId = null;
                $action = null;
                
                if (isset($payload['data']['command'])) {
                    try {
                        $commandData = unserialize($payload['data']['command']);
                        if (is_object($commandData)) {
                            $reflection = new \ReflectionClass($commandData);
                            if ($reflection->hasProperty('tableName')) {
                                $prop = $reflection->getProperty('tableName');
                                $prop->setAccessible(true);
                                $tableName = $prop->getValue($commandData);
                            }
                            if ($reflection->hasProperty('recordId')) {
                                $prop = $reflection->getProperty('recordId');
                                $prop->setAccessible(true);
                                $recordId = $prop->getValue($commandData);
                            }
                            if ($reflection->hasProperty('action')) {
                                $prop = $reflection->getProperty('action');
                                $prop->setAccessible(true);
                                $action = $prop->getValue($commandData);
                            }
                        }
                    } catch (\Exception $e) {
                        // تجاهل الأخطاء في استخراج البيانات
                    }
                }

                $jobStatus = 'pending';
                if ($job->reserved_at) {
                    $jobStatus = 'processing';
                }

                // محاولة الحصول على الحالة من Cache
                $statusFromCache = null;
                if ($jobId) {
                    $statusFromCache = \Illuminate\Support\Facades\Cache::get("sync_from_server_status_{$jobId}");
                    if ($statusFromCache && isset($statusFromCache['status'])) {
                        $jobStatus = $statusFromCache['status'];
                    }
                }

                return [
                    'id' => $job->id,
                    'job_id' => $jobId,
                    'queue' => $job->queue,
                    'class' => $jobClass,
                    'table_name' => $tableName,
                    'record_id' => $recordId,
                    'action' => $action,
                    'attempts' => $job->attempts,
                    'status' => $jobStatus,
                    'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                    'available_at' => date('Y-m-d H:i:s', $job->available_at),
                    'created_at' => date('Y-m-d H:i:s', $job->created_at),
                    'cache_status' => $statusFromCache,
                ];
            });

            return response()->json([
                'success' => true,
                'jobs' => $jobs,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get sync from server jobs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'jobs' => [],
            ], 500);
        }
    }

    /**
     * حذف Job للمزامنة من السيرفر
     */
    public function deleteSyncFromServerJob(Request $request)
    {
        try {
            $jobId = $request->input('job_id'); // ID من جدول jobs
            
            if (!$jobId) {
                return response()->json([
                    'success' => false,
                    'message' => 'job_id مطلوب',
                ], 400);
            }

            $queueConnection = config('queue.default');
            $connection = config("queue.connections.{$queueConnection}.connection") ?? config('database.default');
            
            if (!Schema::connection($connection)->hasTable('jobs')) {
                return response()->json([
                    'success' => false,
                    'message' => 'جدول jobs غير موجود',
                ], 404);
            }

            $deleted = DB::connection($connection)->table('jobs')
                ->where('id', $jobId)
                ->where('queue', 'sync-from-server')
                ->delete();

            if ($deleted) {
                Log::info('Deleted sync from server job', ['job_id' => $jobId]);
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف Job بنجاح',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Job غير موجود',
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete sync from server job', [
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
     * حذف جميع Jobs للمزامنة من السيرفر
     */
    public function clearSyncFromServerJobs(Request $request)
    {
        try {
            $status = $request->input('status'); // pending, processing, completed, failed

            $queueConnection = config('queue.default');
            $connection = config("queue.connections.{$queueConnection}.connection") ?? config('database.default');
            
            if (!Schema::connection($connection)->hasTable('jobs')) {
                return response()->json([
                    'success' => false,
                    'message' => 'جدول jobs غير موجود',
                ], 404);
            }

            $query = DB::connection($connection)->table('jobs')
                ->where('queue', 'sync-from-server');

            // إذا تم تحديد status، حذف فقط Jobs المحددة
            // لكن جدول jobs لا يحتوي على status مباشرة، لذا سنحذف جميع jobs
            // (يمكن تحسين هذا لاحقاً)

            $deleted = $query->delete();

            Log::info('Cleared sync from server jobs', [
                'deleted_count' => $deleted,
                'status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deleted} Job(ات)",
                'deleted_count' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear sync from server jobs', [
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
     * اختبار dispatch Job للمزامنة من السيرفر
     */
    public function testDispatchSyncFromServerJob(Request $request)
    {
        try {
            $tableName = $request->input('table_name', 'orders');
            $recordId = $request->input('record_id', 1);
            $recordId = is_numeric($recordId) ? (int) $recordId : $recordId;
            $action = $request->input('action', 'insert');

            Log::info('Testing dispatch sync from server job', [
                'table_name' => $tableName,
                'record_id' => $recordId,
                'action' => $action,
                'app_env' => config('app.env'),
            ]);

            // محاولة dispatch الـ Job
            try {
                $job = new \App\Jobs\SyncFromServerJob($tableName, $recordId, $action);
                $jobId = $job->getJobId();
                
                dispatch($job)->onQueue('sync-from-server');
                
                Log::info('Job dispatched successfully', [
                    'job_id' => $jobId,
                    'table_name' => $tableName,
                    'record_id' => $recordId,
                ]);

                // التحقق من وجود Job في جدول jobs
                $queueConnection = config('queue.default');
                $connection = config("queue.connections.{$queueConnection}.connection") ?? config('database.default');
                
                $jobInQueue = null;
                if (Schema::connection($connection)->hasTable('jobs')) {
                    // البحث عن Job في جدول jobs (قد يستغرق لحظة)
                    sleep(1);
                    $jobInQueue = DB::connection($connection)->table('jobs')
                        ->where('queue', 'sync-from-server')
                        ->orderBy('id', 'desc')
                        ->first();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم dispatch الـ Job بنجاح',
                    'job_id' => $jobId,
                    'job_details' => [
                        'table_name' => $tableName,
                        'record_id' => $recordId,
                        'action' => $action,
                    ],
                    'queue_info' => [
                        'connection' => $connection,
                        'queue' => 'sync-from-server',
                        'queue_driver' => $queueConnection,
                        'job_found_in_queue' => $jobInQueue ? true : false,
                        'job_queue_id' => $jobInQueue ? $jobInQueue->id : null,
                    ],
                    'app_env' => config('app.env'),
                    'note' => 'إذا كان app.env ليس server أو production، لن يتم dispatch الـ Job تلقائياً عند إنشاء فاتورة/منتج',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch job', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'فشل dispatch الـ Job: ' . $e->getMessage(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Test dispatch job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * فحص حالة النظام - Offline First (لا يتطلب اتصال بالإنترنت)
     */
    public function checkSystemStatus(Request $request)
    {
        try {
            $autoSync = new \App\Services\AutoSyncService();
            $health = $autoSync->checkSystemHealth();
            
            return response()->json([
                'success' => true,
                'system_status' => [
                    'mode' => 'offline-first',
                    'local_database_available' => $health['local_database'],
                    'internet_available' => $health['internet'],
                    'remote_server_available' => $health['remote_server'],
                    'auto_sync_enabled' => $health['auto_sync_enabled'],
                    'last_sync' => $health['last_sync'],
                    'next_sync' => $health['next_sync'],
                ],
                'message' => $health['local_database'] 
                    ? 'النظام يعمل (Offline Mode)' 
                    : 'قاعدة البيانات المحلية غير متاحة',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check system status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في فحص حالة النظام: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * تنفيذ المزامنة التلقائية
     */
    public function performAutoSync(Request $request)
    {
        try {
            $autoSync = new \App\Services\AutoSyncService();
            $result = $autoSync->performAutoSync();
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Auto sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في المزامنة التلقائية: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * فرض المزامنة الآن (تجاوز المؤقت)
     */
    public function forceSyncNow(Request $request)
    {
        try {
            $autoSync = new \App\Services\AutoSyncService();
            $result = $autoSync->forceSyncNow();
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Force sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في فرض المزامنة: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * الحصول على قائمة Migrations
     */
    public function getMigrations(Request $request)
    {
        try {
            $showExecuted = filter_var($request->get('show_executed', false), FILTER_VALIDATE_BOOLEAN);
            
            $migrationsPath = database_path('migrations');
            $files = File::files($migrationsPath);
            
            // جلب قائمة المايجريشنز المنفذة من قاعدة البيانات
            $executedMigrations = [];
            try {
                if (Schema::hasTable('migrations')) {
                    $executed = DB::table('migrations')->pluck('migration')->toArray();
                    $executedMigrations = array_map(function($migration) {
                        // إزالة التاريخ من اسم المايجريشن للحصول على الاسم فقط
                        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $migration, $matches)) {
                            return $matches[1];
                        }
                        return $migration;
                    }, $executed);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get executed migrations', [
                    'error' => $e->getMessage()
                ]);
            }
            
            $migrations = [];
            foreach ($files as $file) {
                $fileName = $file->getFilename();
                // استخراج اسم المايجريشن من اسم الملف (بعد التاريخ)
                if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+?)\.php$/', $fileName, $matches)) {
                    $migrationName = $matches[1];
                    $isExecuted = in_array($migrationName, $executedMigrations);
                    
                    // إخفاء المايجريشنز المنفذة إذا لم يكن show_executed = true
                    if ($isExecuted && !$showExecuted) {
                        continue;
                    }
                    
                    $migrations[] = [
                        'file' => $fileName,
                        'name' => $migrationName,
                        'path' => $file->getPathname(),
                        'date' => date('Y-m-d H:i:s', $file->getMTime()),
                        'executed' => $isExecuted
                    ];
                }
            }
            
            // ترتيب حسب التاريخ (الأحدث أولاً)
            usort($migrations, function($a, $b) {
                return strcmp($b['file'], $a['file']);
            });
            
            return response()->json([
                'migrations' => $migrations,
                'total_executed' => count($executedMigrations),
                'total_pending' => count($migrations)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get migrations', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => $e->getMessage(),
                'migrations' => []
            ], 500);
        }
    }

    /**
     * فحص المايجريشن للتحقق من وجود بيانات قبل التنفيذ
     */
    public function checkMigration(Request $request)
    {
        try {
            $migrationName = $request->get('migration_name');
            
            if (!$migrationName) {
                return response()->json([
                    'success' => false,
                    'error' => 'اسم المايجريشن مطلوب'
                ], 422);
            }
            
            // البحث عن ملف المايجريشن
            $migrationsPath = database_path('migrations');
            $files = File::files($migrationsPath);
            
            $migrationFile = null;
            $migrationPath = null;
            foreach ($files as $file) {
                $fileName = $file->getFilename();
                if (str_contains($fileName, $migrationName)) {
                    $migrationFile = $fileName;
                    $migrationPath = $file->getPathname();
                    break;
                }
            }
            
            if (!$migrationFile || !$migrationPath) {
                return response()->json([
                    'success' => false,
                    'error' => 'المايجريشن غير موجود: ' . $migrationName
                ], 404);
            }
            
            // قراءة محتوى المايجريشن للتحقق من الجداول المتأثرة
            $migrationContent = File::get($migrationPath);
            
            // استخراج أسماء الجداول من المايجريشن
            $tables = [];
            
            // البحث عن dropIfExists أو drop
            if (preg_match_all('/dropIfExists\([\'"]([^\'"]+)[\'"]\)|drop\([\'"]([^\'"]+)[\'"]\)/i', $migrationContent, $matches)) {
                $tables = array_merge($tables, array_filter(array_merge($matches[1], $matches[2])));
            }
            
            // البحث عن Schema::drop
            if (preg_match_all('/Schema::drop\([\'"]([^\'"]+)[\'"]\)/i', $migrationContent, $matches)) {
                $tables = array_merge($tables, $matches[1]);
            }
            
            // البحث عن table() في down method
            if (preg_match('/function\s+down\([^)]*\)\s*\{([^}]+)\}/is', $migrationContent, $downMatch)) {
                if (preg_match_all('/table\([\'"]([^\'"]+)[\'"]\)/i', $downMatch[1], $tableMatches)) {
                    $tables = array_merge($tables, $tableMatches[1]);
                }
            }
            
            $tables = array_unique(array_filter($tables));
            
            // التحقق من وجود بيانات في الجداول
            $tablesWithData = [];
            $totalRecords = 0;
            
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        $tablesWithData[] = [
                            'name' => $table,
                            'count' => $count
                        ];
                        $totalRecords += $count;
                    }
                } catch (\Exception $e) {
                    // الجدول غير موجود - لا مشكلة
                }
            }
            
            return response()->json([
                'success' => true,
                'has_data' => count($tablesWithData) > 0,
                'tables_with_data' => $tablesWithData,
                'total_records' => $totalRecords,
                'affected_tables' => $tables,
                'migration_file' => $migrationFile
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check migration', [
                'migration_name' => $request->get('migration_name'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تنفيذ migration محدد حسب الاسم (بدون حذف البيانات)
     */
    public function runMigration(Request $request)
    {
        try {
            $migrationName = $request->get('migration_name');
            $force = filter_var($request->get('force', false), FILTER_VALIDATE_BOOLEAN);
            
            if (!$migrationName) {
                return response()->json([
                    'success' => false,
                    'error' => 'اسم المايجريشن مطلوب'
                ], 422);
            }
            
            // البحث عن ملف المايجريشن
            $migrationsPath = database_path('migrations');
            $files = File::files($migrationsPath);
            
            $migrationFile = null;
            $migrationPath = null;
            foreach ($files as $file) {
                $fileName = $file->getFilename();
                if (str_contains($fileName, $migrationName)) {
                    $migrationFile = $fileName;
                    $migrationPath = $file->getPathname();
                    break;
                }
            }
            
            if (!$migrationFile || !$migrationPath) {
                return response()->json([
                    'success' => false,
                    'error' => 'المايجريشن غير موجود: ' . $migrationName
                ], 404);
            }
            
            // فحص وجود بيانات إذا لم يكن force
            if (!$force) {
                $migrationContent = File::get($migrationPath);
                $tables = [];
                
                // البحث عن dropIfExists أو drop
                if (preg_match_all('/dropIfExists\([\'"]([^\'"]+)[\'"]\)|drop\([\'"]([^\'"]+)[\'"]\)/i', $migrationContent, $matches)) {
                    $tables = array_merge($tables, array_filter(array_merge($matches[1], $matches[2])));
                }
                
                // البحث عن Schema::drop
                if (preg_match_all('/Schema::drop\([\'"]([^\'"]+)[\'"]\)/i', $migrationContent, $matches)) {
                    $tables = array_merge($tables, $matches[1]);
                }
                
                // البحث عن table() في down method
                if (preg_match('/function\s+down\([^)]*\)\s*\{([^}]+)\}/is', $migrationContent, $downMatch)) {
                    if (preg_match_all('/table\([\'"]([^\'"]+)[\'"]\)/i', $downMatch[1], $tableMatches)) {
                        $tables = array_merge($tables, $tableMatches[1]);
                    }
                }
                
                $tables = array_unique(array_filter($tables));
                
                // التحقق من وجود بيانات
                foreach ($tables as $table) {
                    try {
                        $count = DB::table($table)->count();
                        if ($count > 0) {
                            return response()->json([
                                'success' => false,
                                'error' => 'يوجد بيانات في الجداول المتأثرة. لا يمكن تنفيذ المايجريشن حفاظاً على البيانات.',
                                'warning' => true,
                                'table' => $table,
                                'record_count' => $count,
                                'message' => "يوجد {$count} سجل في جدول '{$table}'. استخدم force=true إذا كنت متأكداً من الحذف."
                            ], 400);
                        }
                    } catch (\Exception $e) {
                        // الجدول غير موجود - لا مشكلة
                    }
                }
            }
            
            // تنفيذ المايجريشن
            $exitCode = Artisan::call('migrate', [
                '--path' => 'database/migrations/' . $migrationFile,
                '--force' => true
            ]);
            
            $output = Artisan::output();
            
            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تنفيذ المايجريشن بنجاح',
                    'output' => $output,
                    'migration' => $migrationFile
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'فشل تنفيذ المايجريشن',
                    'output' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Failed to run migration', [
                'migration_name' => $request->get('migration_name'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * التحقق من البيئة المحلية
     */
    protected function isLocalEnvironment(): bool
    {
        // فحص APP_URL
        $appUrl = config('app.url', '');
        if (str_contains($appUrl, '127.0.0.1') || str_contains($appUrl, 'localhost')) {
            return true;
        }
        
        // فحص الـ Host الحالي
        $host = request()->getHost();
        $localHosts = ['localhost', '127.0.0.1', '::1', 'app.production.local'];
        
        if (in_array($host, $localHosts)) {
            return true;
        }
        
        // فحص APP_ENV
        if (app()->environment('local')) {
            return true;
        }
        
        // فحص وجود SQLite (علامة على البيئة المحلية)
        $sqlitePath = config('database.connections.sync_sqlite.database');
        if ($sqlitePath && file_exists($sqlitePath)) {
            // إذا كان SQLite موجود وهو الاتصال الافتراضي
            if (config('database.default') === 'sync_sqlite') {
                return true;
            }
        }
        
        return false;
    }
}

