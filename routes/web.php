<?php

use App\Http\Controllers\DecorationController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\BoxesController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LangController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\SystemConfigController;
use Illuminate\Support\Facades\Artisan;

// ============================================================================
// Migration Management Routes - NO MIDDLEWARE - MUST BE FIRST
// ============================================================================
Route::get('/admin/migrations', [MigrationController::class, 'index'])->name('admin.migrations');
    
Route::post('/admin/migrations/run', [MigrationController::class, 'runMigrations'])->name('admin.migrations.run');
    
Route::post('/admin/migrations/rollback', [MigrationController::class, 'rollbackMigrations'])->name('admin.migrations.rollback');
    
Route::post('/admin/migrations/refresh', [MigrationController::class, 'refreshMigrations'])->name('admin.migrations.refresh');
    
Route::post('/admin/migrations/seeders', [MigrationController::class, 'runSeeders'])->name('admin.migrations.seeders');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('link', function () {
  Artisan::call('storage:link');
  return "yes link";
});
Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'active.session'])->group(function () {


  Route::resource('users', UsersController::class);
  Route::post('users/{user}/activate', [UsersController::class, 'activate'])->name('activate');
  Route::post('users/{user}', [UsersController::class, 'update'])->name('users.update'); //  inertia does not support send files using put request

  Route::resource('customers', CustomersController::class);
  Route::post('customers/{customer}/activate', [CustomersController::class, 'activate'])->name('activate');
  Route::post('customers/{customer}', [CustomersController::class, 'update'])->name('customers.update'); 

  Route::resource('suppliers', SuppliersController::class);
  Route::post('suppliers/{supplier}/activate', [SuppliersController::class, 'activate'])->name('suppliers.activate');
  Route::post('suppliers/{supplier}', [SuppliersController::class, 'update'])->name('suppliers.update');


    // Decoration routes
    Route::get('/decorations-dashboard', [DecorationController::class, 'dashboard'])->name('decorations.dashboard');
    Route::resource('decorations', DecorationController::class);
    Route::post('decorations/{decoration}/update-post', [DecorationController::class, 'updatePost'])->name('decorations.update.post');
    Route::get('/decorations-orders', [DecorationController::class, 'orders'])->name('decorations.orders');
    Route::post('/decoration-orders', [DecorationController::class, 'createOrder'])->name('decoration.orders.store');
    Route::patch('/decoration-orders/{order}/status', [DecorationController::class, 'updateOrderStatus'])->name('decoration.orders.status');
    Route::patch('/decoration-orders/{order}/pricing', [DecorationController::class, 'updateOrderPricing'])->name('decoration.orders.pricing');
    Route::get('/decoration-orders/{order}/print', [DecorationController::class, 'printOrder'])->name('decoration.orders.print');
    Route::get('/decoration-orders/{order}/verify', [DecorationController::class, 'verifyOrder'])->name('decoration.orders.verify');
    Route::get('/decoration-payments/{payment}/receipt', [DecorationController::class, 'printPaymentReceipt'])->name('decoration.payments.receipt');

// Decoration Payments and Accounting Routes
Route::get('/decoration-payments', [DecorationController::class, 'payments'])->name('decoration.payments');
Route::post('/decoration-payments/add', [DecorationController::class, 'addPayment'])->name('decoration.payments.add');
Route::post('/decoration-payments/balance/add', [DecorationController::class, 'addBalance'])->name('decoration.payments.balance.add');
Route::post('/decoration-payments/balance/withdraw', [DecorationController::class, 'withdrawBalance'])->name('decoration.payments.balance.withdraw');
Route::get('/decoration-payments/balance/{customer}', [DecorationController::class, 'getCustomerBalance'])->name('decoration.payments.balance.get');

// Monthly Accounting Routes
Route::get('/decoration-monthly-accounting', [DecorationController::class, 'monthlyAccounting'])->name('decoration.monthly.accounting');
Route::post('/decoration-monthly-accounting/close', [DecorationController::class, 'closeMonth'])->name('decoration.monthly.close');
Route::get('/decoration-monthly-accounting/report', [DecorationController::class, 'getMonthlyReport'])->name('decoration.monthly.report');
Route::put('/decoration-monthly-accounting/{monthlyAccount}', [DecorationController::class, 'updateMonthlyAccount'])->name('decoration.monthly.update');
Route::post('/decoration-monthly-accounting/{monthlyAccount}/recalculate', [DecorationController::class, 'recalculateMonthlyData'])->name('decoration.monthly.recalculate');
Route::post('/decoration-monthly-accounting/payout-commissions', [DecorationController::class, 'payoutMonthlyCommissions'])->name('decoration.monthly.payoutCommissions');



  // Categories routes
  Route::resource('categories', CategoryController::class);
  Route::get('categories/{category}/products', [CategoryController::class, 'getProducts'])->name('categories.products');
  
  // Permission Seeder API routes (for quick setup on server)
  Route::post('api/permissions/add-category-permissions', [App\Http\Controllers\PermissionSeederController::class, 'addCategoryPermissions'])->name('api.permissions.add-category');
  Route::post('api/permissions/run-category-seeder', [App\Http\Controllers\PermissionSeederController::class, 'runCategoryPermissionSeeder'])->name('api.permissions.run-category-seeder');

  // Products routes
  Route::resource('products', ProductController::class);
  Route::post('products/{product}/activate', [ProductController::class, 'activate'])->name('activate');
  Route::post('products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('toggle-featured');
  // Route::post('products/{product}/toggle-best-selling', [ProductController::class, 'toggleBestSelling'])->name('toggle-best-selling'); // Removed: Best selling is now calculated automatically
  Route::post('products/{product}', [ProductController::class, 'update'])->name('products.update'); 
  Route::get('products/trashed', [ProductController::class, 'trashed'])->name('products.trashed');
  Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
  
  // Product API routes
  Route::get('products/find-by-barcode/{barcode}', [ProductController::class, 'findByBarcode'])->name('products.findByBarcode');
  Route::get('products/check-stock/{product_id}', [ProductController::class, 'checkStock'])->name('products.checkStock');
  Route::get('products/check-barcode-unique/{barcode}', [ProductController::class, 'checkBarcodeUnique'])->name('products.checkBarcodeUnique');

  Route::resource('purchase-invoices', App\Http\Controllers\PurchaseInvoiceController::class);
  Route::get('purchase-invoices/search/products', [App\Http\Controllers\PurchaseInvoiceController::class, 'searchProducts'])->name('purchase-invoices.search-products');
  Route::get('purchase-invoices/search/suppliers', [App\Http\Controllers\PurchaseInvoiceController::class, 'searchSuppliers'])->name('purchase-invoices.search-suppliers');
  // Orders routes
  Route::resource('orders', OrderController::class);
  Route::post('orders/{order}/activate', [OrderController::class, 'activate'])->name('orders.activate');
  Route::put('ordersEdit/{order}', [OrderController::class, 'update'])->name('orders.update');
  Route::get('orders/trashed', [OrderController::class, 'trashed'])->name('orders.trashed');
  Route::post('orders/{id}/restore', [OrderController::class, 'restore'])->name('orders.restore');
  Route::get('order/print/{id}', [OrderController::class, 'print'])->name('order.print');



  Route::resource('permissions', App\Http\Controllers\PermissionController::class);
  Route::get('permissions/{permissionId}/delete', [App\Http\Controllers\PermissionController::class, 'destroy']);

  Route::resource('roles', App\Http\Controllers\RoleController::class);
  Route::get('roles/{roleId}/delete', [App\Http\Controllers\RoleController::class, 'destroy']);
  Route::get('roles/{roleId}/give-permissions', [App\Http\Controllers\RoleController::class, 'addPermissionToRole']);
  Route::put('roles/{roleId}/give-permissions', [App\Http\Controllers\RoleController::class, 'givePermissionToRole']);


  Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
  Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
  Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

  Route::get('logs', [LogController::class, 'index'])->name('logs');
  Route::get('logs/{log}', [LogController::class, 'view'])->name('logs.view');
  Route::post('logs/undo/{log}', [LogController::class, 'undo'])->name('logs.undo');
  Route::post('logs/clean', [LogController::class, 'cleanOldLogs'])->name('logs.clean');

  Route::get('lang/change', [LangController::class, 'change'])->name('changeLang');

  Route::resource('notification', NotificationController::class)
    ->middleware('auth')
    ->only(['index']);

  // Frontend interaction logs endpoint
  Route::post('logs', [LogController::class, 'store'])->name('logs.store');

  // Box routes - specific routes must come before resource route
  Route::post('boxes/add-to-box', [BoxesController::class, 'addToBox'])->name('boxes.add-to-box');
  Route::post('boxes/drop-from-box', [BoxesController::class, 'dropFromBox'])->name('boxes.drop-from-box');
  Route::post('boxes/close-daily', [BoxesController::class, 'closeDaily'])->name('boxes.close-daily');
  Route::post('boxes/close-monthly', [BoxesController::class, 'closeMonthly'])->name('boxes.close-monthly');
  Route::get('boxes/daily-close', [BoxesController::class, 'getDailyClose'])->name('boxes.daily-close');
  Route::get('boxes/monthly-close', [BoxesController::class, 'getMonthlyClose'])->name('boxes.monthly-close');
  Route::get('boxes/closes-list', [BoxesController::class, 'closesList'])->name('boxes.closes-list');
  
  Route::resource('boxes', BoxesController::class);
  Route::post('boxes/{box}/activate', [BoxesController::class, 'activate'])->name('activate');
  Route::post('boxes/{box}', [BoxesController::class, 'update'])->name('boxes.update');

  // System Config routes
  Route::get('system-config', [SystemConfigController::class, 'index'])->name('system-config.index');
  Route::put('system-config', [SystemConfigController::class, 'update'])->name('system-config.update');

  // Expenses routes
  Route::resource('expenses', App\Http\Controllers\ExpenseController::class); 

  // Sync Monitor routes
  Route::get('sync-monitor', [App\Http\Controllers\SyncMonitorController::class, 'index'])->name('sync-monitor.index');

  // Barcode routes (with authentication)
  Route::prefix('barcode')->name('barcode.')->group(function () {
    Route::get('/', [BarcodeController::class, 'index'])->name('index');
    Route::post('/batch-print', [BarcodeController::class, 'batchPrint'])->name('batch.print');
    Route::get('/printer-settings', [BarcodeController::class, 'printerSettings'])->name('printer.settings');
  });

});

Route::get('/export-users', [ExportController::class, 'export'])->name('export.users');
Route::get('/export-customers', [ExportController::class, 'export'])->name('export.customers');

// Public Gallery Routes (No Authentication Required)
Route::get('/gallery', [App\Http\Controllers\PublicGalleryController::class, 'index'])->name('public.gallery');
Route::get('/gallery/{decoration}', [App\Http\Controllers\PublicGalleryController::class, 'show'])->name('public.decoration.show');
Route::get('/api/gallery', [App\Http\Controllers\PublicGalleryController::class, 'api'])->name('public.gallery.api');
    Route::get('/decoration-orders/{order}', [DecorationController::class, 'showOrder'])->name('decoration.orders.show');
    Route::get('/my-decoration-orders', [DecorationController::class, 'myOrders'])->name('decoration.orders.my');
// Public barcode routes (no authentication required)
Route::get('/barcode/preview', [BarcodeController::class, 'preview'])->name('barcode.preview');
Route::get('/barcode/download/{product}', [BarcodeController::class, 'download'])->name('barcode.download');

// Barcode routes with CSRF but without authentication
Route::prefix('barcode')->name('barcode.')->middleware(['web'])->group(function () {
  Route::post('/generate', [BarcodeController::class, 'generate'])->name('generate');
  Route::post('/print', [BarcodeController::class, 'print'])->name('print');
});


// License routes
Route::get('/license/activate', [App\Http\Controllers\LicenseController::class, 'showActivate'])->name('license.activate');
Route::get('/license/status', [App\Http\Controllers\LicenseController::class, 'showStatus'])->name('license.status');

require __DIR__ . '/auth.php';
