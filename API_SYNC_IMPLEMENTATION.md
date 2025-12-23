# 🔄 تطبيق المزامنة عبر API

## 📋 الفكرة

بدلاً من الاتصال المباشر بـ MySQL، استخدام API endpoints للمزامنة.

---

## ✅ الفوائد

1. ✅ **الأمان**: لا حاجة لفتح MySQL للاتصالات الخارجية
2. ✅ **المرونة**: يمكن التحكم في المزامنة من خلال API
3. ✅ **التحقق**: يمكن إضافة authentication و validation
4. ✅ **التتبع**: يمكن تتبع جميع عمليات المزامنة
5. ✅ **التحكم**: يمكن إضافة rate limiting و throttling
6. ✅ **الموثوقية**: يمكن إضافة retry mechanism أفضل
7. ✅ **المراقبة**: يمكن مراقبة جميع الطلبات

---

## 🏗️ البنية المقترحة

### 1. على السيرفر (Server Side)

#### API Endpoints:

```
POST /api/sync/health          - التحقق من توفر API
POST /api/sync/insert          - إدراج سجل جديد
PUT  /api/sync/update          - تحديث سجل موجود
DELETE /api/sync/delete        - حذف سجل
POST /api/sync/batch           - مزامنة batch (أكثر كفاءة)
GET  /api/sync/mapping         - الحصول على server ID من local ID
```

#### Authentication:

- استخدام API Token (Bearer Token)
- يمكن استخدام Sanctum أو Passport

---

### 2. على العميل (Client Side)

#### ApiSyncService:

- خدمة جديدة للتعامل مع API
- Retry mechanism
- Error handling
- Caching للـ mappings

---

## 📁 الملفات المطلوبة

### 1. `app/Services/ApiSyncService.php` ✅ (تم إنشاؤه)

**الوظائف**:
- `isApiAvailable()` - التحقق من توفر API
- `syncInsert()` - مزامنة insert
- `syncUpdate()` - مزامنة update
- `syncDelete()` - مزامنة delete
- `syncBatch()` - مزامنة batch
- `getServerId()` - الحصول على server ID

---

### 2. على السيرفر: `routes/api.php`

```php
Route::prefix('sync')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/health', [SyncApiController::class, 'health']);
    Route::post('/insert', [SyncApiController::class, 'insert']);
    Route::put('/update', [SyncApiController::class, 'update']);
    Route::delete('/delete', [SyncApiController::class, 'delete']);
    Route::post('/batch', [SyncApiController::class, 'batch']);
    Route::get('/mapping', [SyncApiController::class, 'getMapping']);
});
```

---

### 3. على السيرفر: `app/Http/Controllers/SyncApiController.php`

```php
class SyncApiController extends Controller
{
    public function health()
    {
        return response()->json(['status' => 'ok']);
    }

    public function insert(Request $request)
    {
        $table = $request->input('table');
        $data = $request->input('data');
        
        // Validation
        // Insert to MySQL
        // Return response
    }

    // ... rest of methods
}
```

---

## 🔄 التكامل مع النظام الحالي

### تعديل `DatabaseSyncService`:

```php
class DatabaseSyncService
{
    protected $useApi = false;
    protected $apiSyncService;

    public function __construct()
    {
        $this->useApi = env('SYNC_VIA_API', false);
        if ($this->useApi) {
            $this->apiSyncService = new ApiSyncService();
        }
        // ... existing code
    }

    protected function syncInsert(string $tableName, array $data): bool
    {
        if ($this->useApi) {
            $result = $this->apiSyncService->syncInsert($tableName, $data);
            return $result['success'];
        }
        
        // Existing MySQL direct connection code
        // ...
    }
}
```

---

## ⚙️ الإعدادات في `.env`

```env
# استخدام API للمزامنة
SYNC_VIA_API=true

# إعدادات API
ONLINE_URL=https://nissan.intellij-app.com
SYNC_API_TOKEN=your-api-token-here
SYNC_API_TIMEOUT=30
```

---

## 🔒 الأمان

### 1. API Token:

```php
// Generate token
$token = Str::random(60);

// Store in .env
SYNC_API_TOKEN=your-generated-token
```

### 2. Rate Limiting:

```php
Route::middleware(['throttle:60,1'])->group(function () {
    // API routes
});
```

### 3. Validation:

```php
$request->validate([
    'table' => 'required|string',
    'data' => 'required|array',
]);
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
| التعقيد | ✅ بسيط | ⚠️ معقد أكثر |

---

## 🚀 خطوات التطبيق

### المرحلة 1: إنشاء API على السيرفر
1. ✅ إنشاء `SyncApiController`
2. ✅ إنشاء Routes
3. ✅ إضافة Authentication
4. ✅ إضافة Validation

### المرحلة 2: تعديل العميل
1. ✅ إنشاء `ApiSyncService`
2. ✅ تعديل `DatabaseSyncService` لدعم API
3. ✅ إضافة إعدادات `.env`

### المرحلة 3: الاختبار
1. ✅ اختبار API endpoints
2. ✅ اختبار المزامنة
3. ✅ اختبار Error handling

---

## ✅ الخلاصة

المزامنة عبر API هي فكرة ممتازة توفر:
- ✅ أمان أفضل
- ✅ مرونة أكبر
- ✅ تحكم كامل
- ✅ تتبع أفضل

**الخطوة التالية**: تطبيق API endpoints على السيرفر وتعديل `DatabaseSyncService` لاستخدام API.

---

**تاريخ الإنشاء**: 2025-12-23
**الحالة**: ✅ جاهز للتطبيق

