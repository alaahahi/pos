<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BoxesController;
use App\Http\Controllers\OrderController;

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
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
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

Route::get('/check-session', function () {
    $user = Auth::user();

    if ($user && $user->session_id !== session()->getId()) {
        Auth::logout();
        return response()->json(['message' => 'Session expired.'], 401);
    }

    return response()->json(['message' => 'Session valid.'], 200);
});
