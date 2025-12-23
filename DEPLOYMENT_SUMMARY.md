# 📦 ملخص التعديلات للرفع على السيرفر

## ✅ حالة الاختبار

**النتيجة**: ✅ **كل الاختبارات نجحت!**

```
ApiSyncService: ✅
API Available: ✅
Sync Via API: ✅
Sync Insert: ✅
Sync Update: ✅
Sync Batch: ✅
```

---

## 📁 الملفات المطلوب رفعها

### 1. الملفات الجديدة (على العميل فقط)

```
app/Services/ApiSyncService.php          ✅ جديد
test-api-sync.php                        ✅ للاختبار
```

### 2. الملفات المحدثة (على العميل)

```
app/Jobs/SyncPendingChangesJob.php       ✅ محدث (إضافة DB facade)
app/Services/DatabaseSyncService.php     ✅ محدث (دعم API mode)
config/database.php                      ✅ محدث (إعدادات MySQL)
```

### 3. الملفات المطلوبة على السيرفر (جديدة)

```
app/Http/Controllers/SyncApiController.php  ⚠️ يجب إنشاؤه على السيرفر
routes/api.php                            ⚠️ يجب تحديثه على السيرفر (إضافة routes)
```

---

## 🚀 خطوات الرفع السريعة

### على السيرفر:

#### 1. إنشاء SyncApiController

```bash
# إنشاء الملف
nano app/Http/Controllers/SyncApiController.php
```

انسخ الكود من `DEPLOYMENT_GUIDE.md` (القسم 2.1)

#### 2. إضافة Routes

```bash
# تعديل الملف
nano routes/api.php
```

أضف في النهاية:
```php
// Sync API Routes
Route::prefix('sync')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/health', [App\Http\Controllers\SyncApiController::class, 'health']);
    Route::post('/insert', [App\Http\Controllers\SyncApiController::class, 'insert']);
    Route::put('/update', [App\Http\Controllers\SyncApiController::class, 'update']);
    Route::delete('/delete', [App\Http\Controllers\SyncApiController::class, 'delete']);
    Route::post('/batch', [App\Http\Controllers\SyncApiController::class, 'batch']);
    Route::get('/mapping', [App\Http\Controllers\SyncApiController::class, 'getMapping']);
});
```

#### 3. إنشاء API Token

```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();
$token = $user->createToken('sync-api-token')->plainTextToken;
echo $token;
```

#### 4. تنظيف Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

### على العميل (Local):

#### 1. رفع الملفات (إذا كان هناك Git)

```bash
git add .
git commit -m "Add API sync support"
git push origin main
```

#### 2. تحديث `.env`

```env
SYNC_VIA_API=true
SYNC_API_TOKEN=your-generated-token-from-server
ONLINE_URL=https://nissan.intellij-app.com
SYNC_API_TIMEOUT=30
```

#### 3. تنظيف Cache

```bash
php artisan config:clear
```

#### 4. اختبار

```bash
php test-api-sync.php
```

---

## ✅ Checklist

### على السيرفر:
- [ ] إنشاء `SyncApiController.php`
- [ ] إضافة Routes في `routes/api.php`
- [ ] إنشاء API Token
- [ ] تنظيف Cache

### على العميل:
- [ ] رفع الملفات المحدثة
- [ ] تحديث `.env` (`SYNC_VIA_API=true`)
- [ ] إضافة `SYNC_API_TOKEN` في `.env`
- [ ] تنظيف Cache
- [ ] اختبار (`php test-api-sync.php`)

---

## 🔍 التحقق من الرفع

### 1. اختبار API Health

```bash
curl -X GET https://nissan.intellij-app.com/api/sync/health \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**النتيجة المتوقعة**:
```json
{"status":"ok"}
```

### 2. اختبار على العميل

```bash
php test-api-sync.php
```

**النتيجة المتوقعة**: جميع الاختبارات ✅

---

## 📝 ملاحظات

1. **API Token**: يجب أن يكون آمناً ولا يُشارك
2. **Rate Limiting**: 60 request/دقيقة
3. **Authentication**: يستخدم Sanctum
4. **Logging**: جميع الأخطاء في `storage/logs/laravel.log`

---

## 🆘 استكشاف الأخطاء

### إذا فشل API Health:

1. تحقق من Routes:
   ```bash
   php artisan route:list | grep sync
   ```

2. تحقق من Token:
   ```bash
   # في tinker
   $user->tokens
   ```

3. تحقق من Logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

**تاريخ الإنشاء**: 2025-12-23
**الحالة**: ✅ جاهز للرفع - جميع الاختبارات نجحت

