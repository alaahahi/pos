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
    
    // توليد مفتاح الترخيص - محمي بكلمة مرور فقط
    Route::post('/generate', [LicenseController::class, 'generate'])
        ->middleware('license.password')
        ->name('api.license.generate');
    
    // تفعيل الترخيص - بدون حماية كلمة مرور
    Route::post('/activate', [LicenseController::class, 'activate'])->name('api.license.activate');
    
    // حالة الترخيص - بدون حماية كلمة مرور
    Route::get('/status', [LicenseController::class, 'status'])->name('api.license.status');
    
    // التحقق من الترخيص - بدون حماية كلمة مرور
    Route::get('/verify', [LicenseController::class, 'verify'])->name('api.license.verify');
    
    // إلغاء تفعيل الترخيص - بدون حماية كلمة مرور
    Route::post('/deactivate', [LicenseController::class, 'deactivate'])->name('api.license.deactivate');
});

