<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\BoxesController;
use App\Http\Controllers\LangController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Artisan;


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
  Route::post('customers/{customer}', [CustomersController::class, 'update'])->name('customers.update'); //  inertia does not support send files using put request

  Route::resource('products', ProductController::class);
  Route::post('products/{product}/activate', [ProductController::class, 'activate'])->name('activate');
  Route::post('products/{product}', [ProductController::class, 'update'])->name('products.update'); 
  Route::get('products/trashed', [ProductController::class, 'trashed'])->name('products.trashed');
  Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');

  Route::resource('orders', OrderController::class);
  Route::post('orders/{order}/activate', [OrderController::class, 'activate'])->name('orders.activate');
  Route::post('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
  Route::get('orders/trashed', [OrderController::class, 'trashed'])->name('orders.trashed');
  Route::post('orders/{id}/restore', [OrderController::class, 'restore'])->name('orders.restore');
  

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

  Route::get('lang/change', [LangController::class, 'change'])->name('changeLang');

  Route::resource('notification', NotificationController::class)
    ->middleware('auth')
    ->only(['index']);

  Route::resource('boxes', BoxesController::class);
  Route::post('boxes/{box}/activate', [BoxesController::class, 'activate'])->name('activate');
  Route::post('boxes/{box}', [BoxesController::class, 'update'])->name('boxes.update'); 

});

Route::get('/export-users', [ExportController::class, 'export'])->name('export.users');
Route::get('/export-customers', [ExportController::class, 'export'])->name('export.customers');

require __DIR__ . '/auth.php';
