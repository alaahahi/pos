# 🚀 دليل رفع التعديلات على السيرفر

## 📋 الملفات المطلوب رفعها

### 1. الملفات الجديدة

```
app/Services/ApiSyncService.php          ✅ جديد
app/Jobs/SyncPendingChangesJob.php       ✅ محدث
app/Services/DatabaseSyncService.php     ✅ محدث
```

### 2. الملفات المحدثة

```
config/database.php                      ✅ محدث (إعدادات MySQL)
routes/api.php                          ✅ (يحتاج إضافة routes للـ API على السيرفر)
```

---

## 🔧 خطوات الرفع على السيرفر

### المرحلة 1: رفع الملفات

#### 1.1. رفع الملفات الجديدة والمحدثة

```bash
# على السيرفر
cd /path/to/your/project

# رفع الملفات
scp app/Services/ApiSyncService.php user@server:/path/to/project/app/Services/
scp app/Jobs/SyncPendingChangesJob.php user@server:/path/to/project/app/Jobs/
scp app/Services/DatabaseSyncService.php user@server:/path/to/project/app/Services/
scp config/database.php user@server:/path/to/project/config/
```

أو استخدام Git:
```bash
git add .
git commit -m "Add API sync support"
git push origin main

# على السيرفر
git pull origin main
```

---

### المرحلة 2: إعداد API على السيرفر

#### 2.1. إنشاء SyncApiController

**الملف**: `app/Http/Controllers/SyncApiController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SyncApiController extends Controller
{
    public function health()
    {
        return response()->json(['status' => 'ok']);
    }

    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $table = $request->input('table');
            $data = $request->input('data');

            // إزالة timestamps إذا كانت موجودة
            unset($data['created_at'], $data['updated_at']);

            // Insert to MySQL
            $id = DB::connection('mysql')->table($table)->insertGetId($data);

            return response()->json([
                'success' => true,
                'id' => $id,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Sync API insert failed', [
                'table' => $request->input('table'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'id' => 'required|integer',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $table = $request->input('table');
            $id = $request->input('id');
            $data = $request->input('data');

            // إزالة timestamps
            unset($data['created_at']);

            // Update in MySQL
            $updated = DB::connection('mysql')->table($table)
                ->where('id', $id)
                ->update($data);

            return response()->json([
                'success' => true,
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            Log::error('Sync API update failed', [
                'table' => $request->input('table'),
                'id' => $request->input('id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $table = $request->input('table');
            $id = $request->input('id');

            // Delete from MySQL
            $deleted = DB::connection('mysql')->table($table)
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('Sync API delete failed', [
                'table' => $request->input('table'),
                'id' => $request->input('id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function batch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'changes' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $changes = $request->input('changes');
            $results = [
                'synced' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($changes as $change) {
                try {
                    $table = $change['table'];
                    $action = $change['action'];
                    $data = $change['data'] ?? [];
                    $id = $change['id'] ?? null;

                    // إزالة timestamps
                    unset($data['created_at'], $data['updated_at']);

                    switch ($action) {
                        case 'insert':
                            DB::connection('mysql')->table($table)->insertGetId($data);
                            $results['synced']++;
                            break;
                        case 'update':
                            DB::connection('mysql')->table($table)
                                ->where('id', $id)
                                ->update($data);
                            $results['synced']++;
                            break;
                        case 'delete':
                            DB::connection('mysql')->table($table)
                                ->where('id', $id)
                                ->delete();
                            $results['synced']++;
                            break;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'table' => $change['table'] ?? 'unknown',
                        'action' => $change['action'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Sync API batch failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getMapping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'local_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $table = $request->input('table');
            $localId = $request->input('local_id');

            // البحث عن server_id من sync_id_mapping
            $mapping = DB::table('sync_id_mapping')
                ->where('table_name', $table)
                ->where('local_id', $localId)
                ->where('sync_direction', 'up')
                ->first();

            return response()->json([
                'success' => true,
                'server_id' => $mapping->server_id ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Sync API getMapping failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
```

---

#### 2.2. إضافة Routes

**الملف**: `routes/api.php`

أضف في نهاية الملف:

```php
// Sync API Routes (على السيرفر)
Route::prefix('sync')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/health', [App\Http\Controllers\SyncApiController::class, 'health']);
    Route::post('/insert', [App\Http\Controllers\SyncApiController::class, 'insert']);
    Route::put('/update', [App\Http\Controllers\SyncApiController::class, 'update']);
    Route::delete('/delete', [App\Http\Controllers\SyncApiController::class, 'delete']);
    Route::post('/batch', [App\Http\Controllers\SyncApiController::class, 'batch']);
    Route::get('/mapping', [App\Http\Controllers\SyncApiController::class, 'getMapping']);
});
```

---

#### 2.3. إنشاء API Token

```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();
$token = $user->createToken('sync-api-token')->plainTextToken;
echo $token;
```

انسخ الـ token وأضفه في `.env` على **العميل** (Local):

```env
SYNC_API_TOKEN=your-generated-token-here
```

---

### المرحلة 3: إعداد العميل (Local)

#### 3.1. تحديث `.env`

```env
# تفعيل المزامنة عبر API
SYNC_VIA_API=true

# إعدادات API
ONLINE_URL=https://nissan.intellij-app.com
SYNC_API_TOKEN=your-generated-token-here
SYNC_API_TIMEOUT=30
```

---

### المرحلة 4: الاختبار

#### 4.1. على السيرفر

```bash
# اختبار health endpoint
curl -X GET https://nissan.intellij-app.com/api/sync/health \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 4.2. على العميل

```bash
php test-api-sync.php
```

---

## ✅ Checklist قبل الرفع

- [ ] رفع `ApiSyncService.php`
- [ ] رفع `SyncPendingChangesJob.php` (محدث)
- [ ] رفع `DatabaseSyncService.php` (محدث)
- [ ] رفع `config/database.php` (محدث)
- [ ] إنشاء `SyncApiController.php` على السيرفر
- [ ] إضافة Routes في `routes/api.php` على السيرفر
- [ ] إنشاء API Token
- [ ] إضافة Token في `.env` على العميل
- [ ] تحديث `.env` على العميل (`SYNC_VIA_API=true`)
- [ ] تشغيل `php artisan config:clear` على السيرفر
- [ ] تشغيل `php artisan config:clear` على العميل
- [ ] اختبار API endpoints
- [ ] اختبار المزامنة

---

## 🔍 التحقق من الرفع

### 1. التحقق من الملفات

```bash
# على السيرفر
ls -la app/Services/ApiSyncService.php
ls -la app/Http/Controllers/SyncApiController.php
```

### 2. التحقق من Routes

```bash
php artisan route:list | grep sync
```

### 3. اختبار API

```bash
php test-api-sync.php
```

---

## 📝 ملاحظات مهمة

1. **API Token**: يجب أن يكون آمناً ولا يُشارك
2. **Rate Limiting**: تم إضافة `throttle:60,1` (60 request/دقيقة)
3. **Authentication**: يستخدم `auth:sanctum`
4. **Logging**: جميع الأخطاء تُسجل في `storage/logs/laravel.log`

---

**تاريخ الإنشاء**: 2025-12-23
**الحالة**: ✅ جاهز للرفع

