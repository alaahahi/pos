# قائمة الملفات المنسوخة للعمل Offline

## ✅ الملفات المنسوخة بنجاح

### نظام الترخيص
- ✅ `app/Models/License.php`
- ✅ `app/Services/LicenseService.php`
- ✅ `app/Helpers/LicenseHelper.php`
- ✅ `app/Http/Controllers/LicenseController.php`
- ✅ `app/Http/Middleware/CheckLicense.php`
- ✅ `config/license.php`
- ✅ `routes/api_license.php`
- ✅ `database/migrations/2025_12_20_000000_create_licenses_table.php`

### ملفات العمل Offline الأساسية
- ✅ `public/sw.js` - Service Worker
- ✅ `public/offline.html` - صفحة عدم الاتصال
- ✅ `public/app-shell.html` - App Shell

### Composables
- ✅ `resources/js/composables/useSimpleOffline.js`

### Migrations
- ✅ `database/migrations/2025_12_20_000001_create_sync_metadata_table.php`

### التحديثات
- ✅ `composer.json` - إضافة LicenseHelper في autoload
- ✅ `routes/api.php` - إضافة license routes
- ✅ `app/Http/Kernel.php` - تسجيل CheckLicense middleware
- ✅ `config/database.php` - إضافة sync_sqlite connection

## ⚠️ الملفات التي تحتاج نسخ يدوي (كبيرة الحجم)

### Composables (3 ملفات)
- `resources/js/composables/useOfflineContracts.js` (~590 سطر)
- `resources/js/composables/useOfflineSync.js` (~145 سطر)
- `resources/js/composables/useIndexedDB.js` (~455 سطر)

### Utils
- `resources/js/utils/db.js` (~710 سطر) - LocalDatabase class

### Services
- `app/Services/DatabaseSyncService.php` (~1800 سطر) - خدمة المزامنة الرئيسية
- `app/Services/SyncQueueService.php` (~240 سطر)

### Jobs
- `app/Jobs/SyncDataJob.php` (~103 سطر)

### Controllers
- `app/Http/Controllers/SyncMonitorController.php` (~990 سطر)

### Routes
- Routes المتعلقة بالمزامنة في `routes/api.php`:
  ```php
  Route::get('/sync-monitor/tables', [SyncMonitorController::class, 'tables']);
  Route::get('/sync-monitor/table/{tableName}', [SyncMonitorController::class, 'tableDetails']);
  Route::post('/sync-monitor/sync', [SyncMonitorController::class, 'sync']);
  Route::get('/sync-monitor/sync-progress', [SyncMonitorController::class, 'syncProgress']);
  Route::get('/sync-monitor/metadata', [SyncMonitorController::class, 'syncMetadata']);
  Route::get('/sync-monitor/test/{tableName}', [SyncMonitorController::class, 'testSync']);
  Route::post('/sync-monitor/table/{tableName}/truncate', [SyncMonitorController::class, 'truncateTable']);
  Route::delete('/sync-monitor/table/{tableName}/delete', [SyncMonitorController::class, 'deleteTable']);
  Route::get('/sync-monitor/backups', [SyncMonitorController::class, 'backups']);
  Route::post('/sync-monitor/restore-backup', [SyncMonitorController::class, 'restoreBackup']);
  Route::get('/sync-monitor/backup-content', [SyncMonitorController::class, 'getBackupContent']);
  Route::post('/sync-monitor/restore-selected', [SyncMonitorController::class, 'restoreSelectedTables']);
  Route::get('/sync-monitor/download-backup', [SyncMonitorController::class, 'downloadBackup']);
  Route::delete('/sync-monitor/backup/delete', [SyncMonitorController::class, 'deleteBackup']);
  ```

## 📝 ملاحظات مهمة

1. **Database Connection**: تم إضافة `sync_sqlite` connection في `config/database.php`
2. **Service Worker**: يحتاج تسجيل في الصفحة الرئيسية
3. **Migrations**: يجب تشغيل `php artisan migrate`
4. **Composer**: يجب تشغيل `composer dump-autoload`

## 🔧 الخطوات التالية

1. نسخ الملفات الكبيرة يدوياً من المشروع الأصلي
2. إضافة Routes المزامنة إلى `routes/api.php`
3. تشغيل Migrations
4. اختبار النظام

