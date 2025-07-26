<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BoxesController;
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
Route::post('deleteTransactions',[AccountingController::class, 'delTransactions'])->name('deleteTransactions');
Route::get('products/{barcode}', [ProductController::class, 'findByBarcode']);
Route::get('boxes/transactions',[BoxesController::class, 'transactions'])->name('transactions');
Route::get('/check-session', function () {
    $user = Auth::user();

    if ($user && $user->session_id !== session()->getId()) {
        Auth::logout();
        return response()->json(['message' => 'Session expired.'], 401);
    }

    return response()->json(['message' => 'Session valid.'], 200);
});
