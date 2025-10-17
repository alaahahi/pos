<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class MigrationController extends Controller
{
    public function __construct()
    {
        // لا نحتاج middleware للـ auth لأن قاعدة البيانات غير موجودة حالياً
        // سنستخدم مفتاح ثابت بسيط للحماية
    }

    /**
     * Check if access key is valid
     */
    private function checkAccessKey(Request $request)
    {
        $accessKey = $request->get('key') ?? $request->header('X-Access-Key');
        $validKey = 'migrate123'; // مفتاح ثابت بسيط
        
        if ($accessKey !== $validKey) {
            abort(403, 'مفتاح الوصول غير صحيح. استخدم: ?key=migrate123');
        }
        
        return true;
    }

    /**
     * Display migration management page
     */
    public function index(Request $request)
    {
        $this->checkAccessKey($request);
        
        try {
            $migrations = $this->getMigrationsStatus();
            $tables = $this->getTablesInfo();
        } catch (\Exception $e) {
            // إذا كانت قاعدة البيانات فارغة
            $migrations = [
                'executed' => [],
                'pending' => [],
                'total' => 0,
                'executed_count' => 0,
                'pending_count' => 0,
            ];
            $tables = [];
        }
        
        // استخدم Blade بدلاً من Inertia لتجنب مشاكل Auth
        return view('admin.migrations', [
            'migrations' => $migrations,
            'tables' => $tables,
        ]);
    }

    /**
     * Run pending migrations
     */
    public function runMigrations(Request $request)
    {
        $this->checkAccessKey($request);
        
        try {
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            
            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);
            
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('success', 'تم تشغيل المايكريشنات بنجاح! ✅');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Rollback last batch of migrations
     */
    public function rollbackMigrations(Request $request)
    {
        $this->checkAccessKey($request);
        
        try {
            $output = [];
            
            // Disable foreign key checks temporarily (MySQL only)
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }
            
            // Rollback last batch
            Artisan::call('migrate:rollback', ['--force' => true]);
            $output[] = Artisan::output();
            
            // Re-enable foreign key checks (MySQL only)
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            // Get updated status
            $migrations = $this->getMigrationsStatus();
            $tables = $this->getTablesInfo();
            
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('success', 'تم تراجع المايكريشنات بنجاح! ✅');
            
        } catch (\Exception $e) {
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Run seeders only
     */
    public function runSeeders(Request $request)
    {
        $this->checkAccessKey($request);
        
        try {
            Artisan::call('db:seed', ['--force' => true]);
            
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('success', 'تم تشغيل الـ Seeders بنجاح! ✅');
            
        } catch (\Exception $e) {
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Refresh all migrations
     */
    public function refreshMigrations(Request $request)
    {
        $this->checkAccessKey($request);
        
        try {
            $output = [];
            
            // Disable foreign key checks temporarily (MySQL only)
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }
            
            // Refresh migrations (rollback all then migrate)
            Artisan::call('migrate:refresh', ['--force' => true]);
            $output[] = Artisan::output();
            
            // Run seeders to create users and permissions
            Artisan::call('db:seed', ['--force' => true]);
            $output[] = 'Seeding completed: ' . Artisan::output();
            
            // Re-enable foreign key checks (MySQL only)
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('success', 'تم إعادة تشغيل المايكريشنات بنجاح! ✅');
            
        } catch (\Exception $e) {
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            return redirect()->route('admin.migrations', ['key' => 'migrate123'])
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Get migrations status
     */
    private function getMigrationsStatus()
    {
        try {
            // Get all migration files
            $migrationFiles = glob(database_path('migrations/*.php'));
            $migrationNames = collect($migrationFiles)->map(function ($file) {
                return basename($file, '.php');
            })->sort()->values();

            // Check if migrations table exists
            if (!Schema::hasTable('migrations')) {
                return [
                    'executed' => [],
                    'pending' => $migrationNames->map(function ($migration) {
                        return [
                            'migration' => $migration,
                            'status' => 'Pending',
                            'batch' => 'N/A'
                        ];
                    }),
                    'total' => $migrationNames->count(),
                    'executed_count' => 0,
                    'pending_count' => $migrationNames->count(),
                ];
            }

            // Get ran migrations from database
            $ranMigrations = collect(DB::select('SELECT migration, batch FROM migrations ORDER BY id DESC'))
                ->keyBy('migration')
                ->toArray();

            // Combine and categorize
            $allMigrations = $migrationNames->map(function ($migration) use ($ranMigrations) {
                $isRan = isset($ranMigrations[$migration]);
                return [
                    'migration' => $migration,
                    'status' => $isRan ? 'Ran' : 'Pending',
                    'batch' => $isRan ? $ranMigrations[$migration]->batch : 'N/A'
                ];
            });

            // Separate executed and pending
            $executed = $allMigrations->where('status', 'Ran')->values();
            $pending = $allMigrations->where('status', 'Pending')->values();

            return [
                'executed' => $executed,
                'pending' => $pending,
                'all' => $allMigrations
            ];
            
        } catch (\Exception $e) {
            return [
                'executed' => [],
                'pending' => [],
                'all' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get tables information
     */
    private function getTablesInfo()
    {
        try {
            // Check if database is accessible
            if (!Schema::hasTable('migrations')) {
                return [];
            }
            
            $connection = config('database.default');
            
            if ($connection === 'sqlite') {
                // SQLite syntax
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                
                return collect($tables)->map(function ($table) {
                    $tableName = $table->name;
                    $rowCount = DB::table($tableName)->count();
                    
                    return [
                        'name' => $tableName,
                        'rows' => $rowCount,
                        'size' => 'N/A',
                        'created' => 'N/A',
                        'updated' => 'N/A',
                        'engine' => 'SQLite',
                        'collation' => 'N/A'
                    ];
                })->sortBy('name')->values();
            } else {
                // MySQL syntax
                $tables = DB::select('SHOW TABLE STATUS');
                
                return collect($tables)->map(function ($table) {
                    return [
                        'name' => $table->Name,
                        'rows' => $table->Rows,
                        'size' => $this->formatBytes($table->Data_length + $table->Index_length),
                        'created' => $table->Create_time,
                        'updated' => $table->Update_time,
                        'engine' => $table->Engine,
                        'collation' => $table->Collation
                    ];
                })->sortBy('name')->values();
            }
            
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check for conflicting tables
     */
    private function checkForConflictingTables()
    {
        // With safe migrations, we don't need to check for conflicts
        // The migrations will handle existing tables safely
        return true;
    }

    /**
     * Get suggestion for error
     */
    private function getSuggestionForError($errorMessage)
    {
        if (strpos($errorMessage, 'already exists') !== false) {
            return 'الجدول موجود مسبقاً. استخدم "إعادة تشغيل الكل" لحل هذا التعارض.';
        }
        
        if (strpos($errorMessage, 'foreign key constraint') !== false) {
            return 'مشكلة في قيود المفاتيح الخارجية. تم إصلاحها تلقائياً.';
        }
        
        if (strpos($errorMessage, 'Table') !== false && strpos($errorMessage, 'doesn\'t exist') !== false) {
            return 'الجدول غير موجود. تأكد من أن المايكريشنات الأساسية تم تشغيلها.';
        }
        
        return 'تحقق من رسالة الخطأ أعلاه للحصول على تفاصيل أكثر.';
    }
}
