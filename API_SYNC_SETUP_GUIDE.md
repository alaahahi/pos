# 🚀 دليل إعداد المزامنة عبر API

## 📋 نظرة عامة

تم تطبيق نظام المزامنة عبر API بدلاً من الاتصال المباشر بـ MySQL.

---

## ✅ ما تم تطبيقه

### 1. `ApiSyncService` ✅
- خدمة جديدة للتعامل مع API
- دعم `insert`, `update`, `delete`, `batch`
- Retry mechanism
- Error handling
- Caching للـ mappings

### 2. تعديل `DatabaseSyncService` ✅
- دعم API mode
- التحقق من `SYNC_VIA_API` في `.env`
- استخدام API أو MySQL حسب الإعداد

---

## ⚙️ الإعدادات

### في ملف `.env`:

```env
# تفعيل المزامنة عبر API
SYNC_VIA_API=true

# إعدادات API
ONLINE_URL=https://nissan.intellij-app.com
SYNC_API_TOKEN=your-api-token-here
SYNC_API_TIMEOUT=30
```

---

## 🔧 على السيرفر (Server Side)

### 1. إنشاء Controller

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

                    switch ($action) {
                        case 'insert':
                            DB::connection('mysql')->table($table)->insert($data);
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

### 2. إضافة Routes

**الملف**: `routes/api.php`

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

### 3. إنشاء API Token

```bash
php artisan tinker
```

```php
$user = User::first();
$token = $user->createToken('sync-api-token')->plainTextToken;
echo $token;
```

ثم أضف في `.env`:
```env
SYNC_API_TOKEN=your-generated-token
```

---

## ✅ الفوائد

1. ✅ **الأمان**: لا حاجة لفتح MySQL للاتصالات الخارجية
2. ✅ **المرونة**: يمكن التحكم في المزامنة من خلال API
3. ✅ **التحقق**: يمكن إضافة validation و authentication
4. ✅ **التتبع**: يمكن تتبع جميع عمليات المزامنة
5. ✅ **التحكم**: يمكن إضافة rate limiting و throttling

---

## 🔄 التبديل بين MySQL و API

### استخدام MySQL مباشر:
```env
SYNC_VIA_API=false
```

### استخدام API:
```env
SYNC_VIA_API=true
SYNC_API_TOKEN=your-token
ONLINE_URL=https://nissan.intellij-app.com
```

---

## 📊 المقارنة

| الميزة | MySQL مباشر | API |
|--------|------------|-----|
| الأمان | ⚠️ يحتاج فتح MySQL | ✅ آمن |
| المرونة | ⚠️ محدود | ✅ مرن |
| التحكم | ⚠️ محدود | ✅ كامل |
| التتبع | ⚠️ صعب | ✅ سهل |
| الأداء | ✅ أسرع | ⚠️ أبطأ قليلاً |

---

**تاريخ التطبيق**: 2025-12-23
**الحالة**: ✅ جاهز للاستخدام (يحتاج إعداد API على السيرفر)

