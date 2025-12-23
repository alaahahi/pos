<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

// Route للاختبار - يمكن حذفها بعد التأكد من أن كل شيء يعمل
Route::get('/test-db-connection', function () {
    $results = [
        'timestamp' => now()->toDateTimeString(),
        'app_env' => app()->environment(),
        'host' => request()->getHost(),
        'is_local' => in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']),
    ];
    
    // 1. التحقق من الاتصال الافتراضي
    try {
        $defaultConnection = config('database.default');
        $results['default_connection'] = $defaultConnection;
        $results['default_connection_config'] = config("database.connections.{$defaultConnection}");
        
        // محاولة الاتصال
        $pdo = DB::connection()->getPdo();
        $results['default_connection_status'] = '✅ متصل';
        
        // جلب اسم قاعدة البيانات
        if ($defaultConnection === 'sync_sqlite' || $defaultConnection === 'sqlite') {
            $results['database_name'] = basename(config("database.connections.{$defaultConnection}.database"));
            $results['database_path'] = config("database.connections.{$defaultConnection}.database");
            $results['file_exists'] = file_exists($results['database_path']);
        } else {
            try {
                $results['database_name'] = DB::select('SELECT database() as db')[0]->db ?? 'N/A';
            } catch (\Exception $e) {
                $results['database_name'] = config("database.connections.{$defaultConnection}.database");
            }
        }
    } catch (\Exception $e) {
        $results['default_connection_status'] = '❌ فشل: ' . $e->getMessage();
    }
    
    // 2. التحقق من SQLite
    try {
        $sqlitePath = config('database.connections.sync_sqlite.database');
        $results['sqlite_path'] = $sqlitePath;
        $results['sqlite_file_exists'] = file_exists($sqlitePath);
        
        if (file_exists($sqlitePath)) {
            $pdo = DB::connection('sync_sqlite')->getPdo();
            $results['sqlite_connection'] = '✅ متصل';
            
            // جلب عدد الجداول
            $tables = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $results['sqlite_tables_count'] = count($tables);
            $results['sqlite_tables'] = array_map(function($table) {
                return $table->name;
            }, $tables);
            
            // التحقق من وجود جدول users
            $usersTableExists = DB::connection('sync_sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            $results['users_table_exists'] = count($usersTableExists) > 0;
            
            if ($results['users_table_exists']) {
                // جلب عدد المستخدمين
                $usersCount = DB::connection('sync_sqlite')->table('users')->count();
                $results['users_count'] = $usersCount;
                
                // جلب أول مستخدم
                $firstUser = DB::connection('sync_sqlite')->table('users')->first();
                $results['first_user'] = $firstUser ? [
                    'id' => $firstUser->id,
                    'name' => $firstUser->name,
                    'email' => $firstUser->email,
                ] : null;
            }
        } else {
            $results['sqlite_connection'] = '❌ ملف SQLite غير موجود';
        }
    } catch (\Exception $e) {
        $results['sqlite_connection'] = '❌ فشل: ' . $e->getMessage();
    }
    
    // 3. اختبار User Model
    try {
        // جرب الاتصال الافتراضي
        $userFromDefault = User::first();
        $results['user_from_default'] = $userFromDefault ? [
            'id' => $userFromDefault->id,
            'name' => $userFromDefault->name,
            'email' => $userFromDefault->email,
            'connection' => $userFromDefault->getConnectionName(),
        ] : null;
    } catch (\Exception $e) {
        $results['user_from_default'] = '❌ فشل: ' . $e->getMessage();
    }
    
    try {
        // جرب SQLite مباشرة
        $userFromSQLite = User::on('sync_sqlite')->first();
        $results['user_from_sqlite'] = $userFromSQLite ? [
            'id' => $userFromSQLite->id,
            'name' => $userFromSQLite->name,
            'email' => $userFromSQLite->email,
            'connection' => $userFromSQLite->getConnectionName(),
        ] : null;
    } catch (\Exception $e) {
        $results['user_from_sqlite'] = '❌ فشل: ' . $e->getMessage();
    }
    
    // 4. اختبار getConnectionName في User Model
    try {
        $testUser = new User();
        $connectionName = $testUser->getConnectionName();
        $results['user_model_connection'] = $connectionName;
    } catch (\Exception $e) {
        $results['user_model_connection'] = '❌ فشل: ' . $e->getMessage();
    }
    
    // 5. التحقق من MySQL (إذا كان متوفراً)
    try {
        $mysqlPdo = DB::connection('mysql')->getPdo();
        $results['mysql_connection'] = '✅ متصل';
        try {
            $results['mysql_database'] = DB::connection('mysql')->select('SELECT database() as db')[0]->db ?? 'N/A';
        } catch (\Exception $e) {
            $results['mysql_database'] = config('database.connections.mysql.database');
        }
    } catch (\Exception $e) {
        $results['mysql_connection'] = '❌ غير متوفر: ' . $e->getMessage();
    }
    
    // 6. ملخص
    $results['summary'] = [
        'default_connection' => $results['default_connection'] ?? 'N/A',
        'should_use_sqlite' => $results['is_local'] || ($results['mysql_connection'] ?? '') === '❌ غير متوفر',
        'sqlite_available' => ($results['sqlite_connection'] ?? '') === '✅ متصل',
        'users_table_exists' => $results['users_table_exists'] ?? false,
        'tables_count' => $results['sqlite_tables_count'] ?? 0,
        'users_count' => $results['users_count'] ?? 0,
    ];
    
    return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->middleware('web');

// Route لنسخ الجداول من MySQL إلى SQLite مباشرة
Route::post('/test-db-init', function () {
    try {
        // التحقق من MySQL
        DB::connection('mysql')->getPdo();
        
        // جلب جميع الجداول من MySQL
        $mysqlTables = DB::connection('mysql')->select('SHOW TABLES');
        $dbName = DB::connection('mysql')->getDatabaseName();
        $tableKey = "Tables_in_{$dbName}";
        
        $tables = [];
        foreach ($mysqlTables as $table) {
            $tableName = $table->$tableKey;
            if (!in_array($tableName, ['migrations', 'sync_metadata', 'sync_queue', 'sync_id_mapping', 'failed_jobs', 'jobs', 'password_reset_tokens', 'personal_access_tokens'])) {
                $tables[] = $tableName;
            }
        }
        
        $created = 0;
        $synced = 0;
        $errors = [];
        
        foreach ($tables as $tableName) {
            try {
                // إنشاء الجدول
                if (!Schema::connection('sync_sqlite')->hasTable($tableName)) {
                    $columns = DB::connection('mysql')->select("SHOW COLUMNS FROM `{$tableName}`");
                    
                    $createTable = "CREATE TABLE IF NOT EXISTS `{$tableName}` (";
                    $columnDefinitions = [];
                    
                    foreach ($columns as $column) {
                        $name = $column->Field;
                        $type = strtoupper($column->Type);
                        
                        // تحويل أنواع MySQL إلى SQLite
                        if (strpos($type, 'INT') !== false) {
                            $sqliteType = 'INTEGER';
                        } elseif (strpos($type, 'TEXT') !== false || strpos($type, 'VARCHAR') !== false || strpos($type, 'CHAR') !== false) {
                            $sqliteType = 'TEXT';
                        } elseif (strpos($type, 'DECIMAL') !== false || strpos($type, 'FLOAT') !== false || strpos($type, 'DOUBLE') !== false) {
                            $sqliteType = 'REAL';
                        } else {
                            $sqliteType = 'TEXT';
                        }
                        
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
                            $columnDefinitions[] = "`{$name}` {$sqliteType} PRIMARY KEY {$null} {$default}";
                        } else {
                            $columnDefinitions[] = "`{$name}` {$sqliteType} {$null} {$default}";
                        }
                    }
                    
                    $createTable .= implode(', ', $columnDefinitions) . ')';
                    DB::connection('sync_sqlite')->statement($createTable);
                    $created++;
                }
                
                // نسخ البيانات
                $rows = DB::connection('mysql')->table($tableName)->get();
                foreach ($rows as $row) {
                    try {
                        $rowArray = (array) $row;
                        DB::connection('sync_sqlite')->table($tableName)->updateOrInsert(['id' => $rowArray['id']], $rowArray);
                        $synced++;
                    } catch (\Exception $e) {
                        // تخطي
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "{$tableName}: " . $e->getMessage();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'تم نسخ الجداول بنجاح',
            'created_tables' => $created,
            'synced_records' => $synced,
            'errors' => $errors,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
})->middleware('web');

