<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BoxesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Api\ActiveUsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('/clear-config-cache', function () {


    //return ;

    Artisan::call('config:cache');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    //Artisan::call('command:cache_most_visited');
    //$content_controller = new ContentEntityRepository();
    //$content_controller->log_visit_cache_job([]);
    return "Configuration cache file removed";
});
Route::middleware(['auth:sanctum', 'license'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('convertDollarDinar',[AccountingController::class, 'convertDollarDinar'])->name('convertDollarDinar');
Route::post('convertDinarDollar',[AccountingController::class, 'convertDinarDollar'])->name('convertDinarDollar');
Route::post('salesDebt',[AccountingController::class, 'salesDebt'])->name('salesDebt');
Route::post('delTransactions',[AccountingController::class, 'delTransactions'])->name('delTransactions');
Route::post('receiptArrived',[AccountingController::class, 'receiptArrived'])->name('receiptArrived');
Route::post('receiptArrivedUser',[AccountingController::class, 'receiptArrivedUser'])->name('receiptArrivedUser');
Route::post('salesDebtUser',[AccountingController::class, 'salesDebtUser'])->name('salesDebtUser');
Route::get('getIndexAccountsSelas',[AccountingController::class, 'getIndexAccountsSelas'])->name('getIndexAccountsSelas');
Route::post('add-to-box',[BoxesController::class, 'addToBox'])->name('add-to-box');
Route::post('drop-from-box',[BoxesController::class, 'dropFromBox'])->name('drop-from-box');
Route::get('products/{barcode}', [ProductController::class, 'findByBarcode']);
Route::get('products-search', [ProductController::class, 'searchProducts'])->name('products.search');
Route::get('check-stock/{product_id}', [ProductController::class, 'checkStock'])->name('check-stock');
Route::post('products/process-purchase', [ProductController::class, 'processPurchase'])->name('products.processPurchase');

Route::get('boxes/transactions',[BoxesController::class, 'transactions'])->name('transactions');
Route::post('TransactionsUpload',[AccountingController::class, 'TransactionsUpload'])->name('TransactionsUpload');
Route::get('TransactionsImageDel',[AccountingController::class, 'TransactionsImageDel'])->name('TransactionsImageDel');
Route::post('createOrder',[OrderController::class, 'createOrder'])->name('createOrder');
Route::get('today-sales',[OrderController::class, 'getTodaySales'])->name('today-sales');

// Active Users API - استخدام web middleware للسماح بالوصول من نفس الجلسة
Route::middleware(['web', 'auth'])->get('active-users', [ActiveUsersController::class, 'index'])->name('api.active-users');

Route::get('/check-session', function () {
    $user = Auth::user();

    if ($user && $user->session_id !== session()->getId()) {
        Auth::logout();
        return response()->json(['message' => 'Session expired.'], 401);
    }

    return response()->json(['message' => 'Session valid.'], 200);
});

// التحقق من الاتصال الحالي بقاعدة البيانات
Route::get('/check-database-connection', function () {
    try {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}");
        
        // معلومات الاتصال
        $connectionInfo = [
            'default_connection' => $defaultConnection,
            'driver' => $connectionConfig['driver'] ?? 'unknown',
            'database' => $connectionConfig['database'] ?? 'unknown',
            'host' => $connectionConfig['host'] ?? 'N/A',
            'is_local' => in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']),
            'app_url' => env('APP_URL'),
            'app_env' => env('APP_ENV'),
        ];
        
        // محاولة الاتصال
        try {
            $pdo = \DB::connection()->getPdo();
            $connectionInfo['connected'] = true;
            
            // الحصول على اسم قاعدة البيانات بناءً على نوع الاتصال
            if ($defaultConnection === 'sync_sqlite' || $defaultConnection === 'sqlite') {
                // SQLite: استخدم مسار الملف
                $dbPath = $connectionConfig['database'] ?? '';
                $connectionInfo['database_name'] = basename($dbPath) ?? 'N/A';
                $connectionInfo['file_exists'] = file_exists($dbPath);
                $connectionInfo['file_size'] = file_exists($dbPath) ? filesize($dbPath) : 0;
                $connectionInfo['file_path'] = $dbPath;
            } else {
                // MySQL: استخدم SELECT database()
                try {
                    $connectionInfo['database_name'] = $pdo->query('SELECT database()')->fetchColumn() ?? 
                                                       ($connectionConfig['database'] ?? 'N/A');
                } catch (\Exception $e) {
                    $connectionInfo['database_name'] = $connectionConfig['database'] ?? 'N/A';
                }
            }
            
            // اختبار query بسيط
            $testQuery = \DB::select('SELECT 1 as test');
            $connectionInfo['query_test'] = $testQuery[0]->test === 1;
            
        } catch (\Exception $e) {
            $connectionInfo['connected'] = false;
            $connectionInfo['error'] = $e->getMessage();
        }
        
        // معلومات MySQL (إذا كان متاحاً)
        $mysqlInfo = [
            'available' => false,
            'connected' => false,
        ];
        
        try {
            $mysqlPdo = \DB::connection('mysql')->getPdo();
            $mysqlInfo['available'] = true;
            $mysqlInfo['connected'] = true;
            try {
                $mysqlInfo['database_name'] = $mysqlPdo->query('SELECT database()')->fetchColumn();
            } catch (\Exception $e) {
                $mysqlInfo['database_name'] = config('database.connections.mysql.database', 'N/A');
            }
        } catch (\Exception $e) {
            $mysqlInfo['available'] = true;
            $mysqlInfo['connected'] = false;
            $mysqlInfo['error'] = $e->getMessage();
        }
        
        return response()->json([
            'success' => true,
            'connection' => $connectionInfo,
            'mysql' => $mysqlInfo,
            'message' => $defaultConnection === 'sync_sqlite' ? 
                '✅ متصل على SQLite (Local Mode)' : 
                '✅ متصل على MySQL (Online Mode)'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// License API Routes
require __DIR__.'/api_license.php';

// Sync Monitor API Routes
Route::prefix('sync-monitor')->group(function () {
    // جلب جميع البيانات في request واحد
    Route::get('/all-data', [App\Http\Controllers\SyncMonitorController::class, 'getAllData']);
    
    Route::get('/tables', [App\Http\Controllers\SyncMonitorController::class, 'tables']);
    Route::get('/table/{tableName}', [App\Http\Controllers\SyncMonitorController::class, 'tableDetails']);
    Route::post('/sync', [App\Http\Controllers\SyncMonitorController::class, 'sync']);
    Route::get('/sync-progress', [App\Http\Controllers\SyncMonitorController::class, 'syncProgress']);
    Route::get('/metadata', [App\Http\Controllers\SyncMonitorController::class, 'syncMetadata']);
    Route::get('/test/{tableName}', [App\Http\Controllers\SyncMonitorController::class, 'testSync']);
    
    // Smart Sync routes (المزامنة الذكية)
    Route::get('/sync-health', [App\Http\Controllers\SyncMonitorController::class, 'checkSyncHealth']); // فحص شامل لحالة المزامنة
    Route::get('/check-health', [App\Http\Controllers\SyncMonitorController::class, 'checkSystemStatus']); // فحص سريع - Offline First (لا يتطلب اتصال)
    Route::get('/auto-sync-status', [App\Http\Controllers\SyncMonitorController::class, 'getAutoSyncStatus']); // حالة المزامنة التلقائية
    Route::post('/smart-sync', [App\Http\Controllers\SyncMonitorController::class, 'smartSync']);
    
    // Migration routes (تنفيذ Migrations بأمان)
    Route::get('/migrations', [App\Http\Controllers\SyncMonitorController::class, 'getMigrations']); // جلب قائمة Migrations
    Route::post('/check-migration', [App\Http\Controllers\SyncMonitorController::class, 'checkMigration']); // فحص Migration قبل التنفيذ
    Route::post('/run-migration', [App\Http\Controllers\SyncMonitorController::class, 'runMigration']); // تنفيذ Migration محدد
    Route::post('/auto-sync', [App\Http\Controllers\SyncMonitorController::class, 'performAutoSync']); // تنفيذ المزامنة التلقائية
    Route::post('/force-sync', [App\Http\Controllers\SyncMonitorController::class, 'forceSyncNow']); // فرض المزامنة الآن
    Route::get('/sync-status', [App\Http\Controllers\SyncMonitorController::class, 'getSyncStatus']); // جديد: الحصول على حالة المزامنة
    Route::get('/pending-changes', [App\Http\Controllers\SyncMonitorController::class, 'getPendingChanges']);
    Route::get('/sync-queue-details', [App\Http\Controllers\SyncMonitorController::class, 'getSyncQueueDetails']); // جديد: تفاصيل sync_queue
    Route::post('/retry-failed', [App\Http\Controllers\SyncMonitorController::class, 'retryFailed']);
    Route::post('/api-sync', [App\Http\Controllers\SyncMonitorController::class, 'apiSync']); // استقبال طلبات المزامنة من النظام المحلي
    Route::post('/compare-tables', [App\Http\Controllers\SyncMonitorController::class, 'compareTables']); // مقارنة البيانات بين السيرفر والمحلي
    Route::post('/sync-missing-records', [App\Http\Controllers\SyncMonitorController::class, 'syncMissingRecords']); // مزامنة السجلات المفقودة من SQLite إلى MySQL
    Route::post('/sync-from-server', [App\Http\Controllers\SyncMonitorController::class, 'syncFromServer']); // مزامنة البيانات من السيرفر (MySQL) إلى المحلي (SQLite) عبر API
    Route::get('/sync-from-server-jobs', [App\Http\Controllers\SyncMonitorController::class, 'getSyncFromServerJobs']); // جلب قائمة Jobs للمزامنة من السيرفر
    Route::delete('/sync-from-server-job', [App\Http\Controllers\SyncMonitorController::class, 'deleteSyncFromServerJob']); // حذف Job واحد
    Route::delete('/sync-from-server-jobs', [App\Http\Controllers\SyncMonitorController::class, 'clearSyncFromServerJobs']); // حذف جميع Jobs
    Route::post('/test-dispatch-sync-from-server-job', [App\Http\Controllers\SyncMonitorController::class, 'testDispatchSyncFromServerJob']); // اختبار dispatch Job
    Route::get('/id-conflicts', [App\Http\Controllers\SyncMonitorController::class, 'checkIdConflicts']);
    Route::get('/id-mappings', [App\Http\Controllers\SyncMonitorController::class, 'getIdMappings']);
    
    // تهيئة SQLite
    Route::post('/init-sqlite', [App\Http\Controllers\SyncMonitorController::class, 'initSQLite']);
    
    // Backup routes
    Route::get('/backups', [App\Http\Controllers\SyncMonitorController::class, 'backups']);
    Route::post('/backup/create', [App\Http\Controllers\SyncMonitorController::class, 'createBackupManual']);
    Route::post('/backup/restore', [App\Http\Controllers\SyncMonitorController::class, 'restoreBackup']);
    Route::get('/backup/download', [App\Http\Controllers\SyncMonitorController::class, 'downloadBackup']);
    Route::delete('/backup/delete', [App\Http\Controllers\SyncMonitorController::class, 'deleteBackup']);
});
