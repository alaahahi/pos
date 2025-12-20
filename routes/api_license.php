<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseController;

/*
|--------------------------------------------------------------------------
| License API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('license')->group(function () {
    // الحصول على معلومات السيرفر (للتوليد Offline)
    Route::get('/server-info', [LicenseController::class, 'getServerInfo'])->name('api.license.server-info');
    
    // تفعيل الترخيص
    Route::post('/activate', [LicenseController::class, 'activate'])->name('api.license.activate');
    
    // حالة الترخيص
    Route::get('/status', [LicenseController::class, 'status'])->name('api.license.status');
    
    // التحقق من الترخيص
    Route::get('/verify', [LicenseController::class, 'verify'])->name('api.license.verify');
    
    // إلغاء تفعيل الترخيص
    Route::post('/deactivate', [LicenseController::class, 'deactivate'])->name('api.license.deactivate');
});

