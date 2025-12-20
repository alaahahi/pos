# دليل تفعيل نظام الترخيص

## ✅ تم تفعيل نظام الترخيص بنجاح!

جميع الملفات المطلوبة موجودة وجاهزة للعمل.

## 📋 الملفات المضافة/المحدثة

### 1. ملفات Vue للواجهة
- ✅ `resources/js/Pages/License/Activate.vue` - صفحة تفعيل الترخيص
- ✅ `resources/js/Pages/License/Status.vue` - صفحة حالة الترخيص

### 2. Routes
- ✅ `routes/web.php` - تم إضافة routes للصفحات:
  - `/license/activate` - صفحة التفعيل
  - `/license/status` - صفحة الحالة

### 3. الملفات الموجودة مسبقاً
- ✅ `app/Models/License.php`
- ✅ `app/Services/LicenseService.php`
- ✅ `app/Helpers/LicenseHelper.php`
- ✅ `app/Http/Controllers/LicenseController.php`
- ✅ `app/Http/Middleware/CheckLicense.php`
- ✅ `config/license.php`
- ✅ `routes/api_license.php`
- ✅ `database/migrations/2025_12_20_000000_create_licenses_table.php`

## 🚀 خطوات التفعيل

### 1. تشغيل Migration
```bash
php artisan migrate
```

### 2. إضافة إعدادات .env
أضف هذه الإعدادات إلى ملف `.env`:

```env
# تفعيل نظام الترخيص
LICENSE_ENABLED=true

# التحقق من الترخيص عند كل طلب (false للأداء الأفضل)
LICENSE_CHECK_EVERY_REQUEST=false

# فترة السماح بعد انتهاء الترخيص (بالأيام)
LICENSE_GRACE_PERIOD=7

# فترة التحقق الدوري (بالثواني)
LICENSE_VERIFICATION_INTERVAL=3600

# دعم التفعيل Offline
LICENSE_OFFLINE_MODE=true

# Secret Key (يجب تغييره!)
LICENSE_SECRET_KEY=your-secret-key-change-this-to-random-string

# URL للتحقق Online (اختياري)
LICENSE_VERIFICATION_URL=

# السماح بتعدد التثبيتات
LICENSE_ALLOW_MULTIPLE=false
```

### 3. توليد Secret Key آمن
```bash
php -r "echo bin2hex(random_bytes(32));"
```
انسخ المفتاح المُولد وضعه في `LICENSE_SECRET_KEY` في ملف `.env`.

### 4. مسح الكاش
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 5. الوصول للصفحات
- صفحة التفعيل: `/license/activate`
- صفحة الحالة: `/license/status`

## 📡 API Endpoints

### الحصول على معلومات السيرفر
```
GET /api/license/server-info
```

### تفعيل الترخيص
```
POST /api/license/activate
Body: {
  "license_key": "encrypted_license_key",
  "domain": "optional",
  "fingerprint": "optional"
}
```

### حالة الترخيص
```
GET /api/license/status
```

### التحقق من الترخيص
```
GET /api/license/verify
```

### إلغاء تفعيل الترخيص
```
POST /api/license/deactivate
```

## 🔒 استخدام Middleware

لحماية Routes بالترخيص، استخدم:

```php
Route::middleware('license')->group(function () {
    // Routes محمية بالترخيص
});
```

## 📝 ملاحظات مهمة

1. **Secret Key**: ⚠️ يجب تغيير `LICENSE_SECRET_KEY` إلى قيمة عشوائية قوية قبل الإنتاج.

2. **Offline Mode**: النظام يدعم التفعيل Offline، يمكن حفظ الترخيص في ملف محلي (`storage/app/license.key`).

3. **Performance**: لتجنب التأثير على الأداء، استخدم `LICENSE_CHECK_EVERY_REQUEST=false` في الإنتاج.

4. **Routes المستثناة**: Routes التالية لا تحتاج ترخيص:
   - `license.activate`
   - `license.status`
   - `login`
   - `register`

## ✅ التحقق من الجاهزية

1. ✅ جميع الملفات موجودة
2. ✅ Routes مضاف في web.php
3. ✅ ملفات Vue موجودة
4. ⚠️ يجب تشغيل Migration
5. ⚠️ يجب إضافة إعدادات .env
6. ⚠️ يجب توليد Secret Key

## 🎉 النظام جاهز للاستخدام!

بعد إكمال الخطوات أعلاه، سيكون نظام الترخيص جاهزاً للعمل.

