# دليل إعداد نظام الترخيص والعمل Offline

تم نسخ نظام الترخيص والعمل Offline من مشروع shipping إلى مشروع pos.

## الملفات المنسوخة

### نظام الترخيص (License System)
- ✅ `app/Models/License.php` - نموذج الترخيص
- ✅ `app/Services/LicenseService.php` - خدمة الترخيص
- ✅ `app/Helpers/LicenseHelper.php` - دوال مساعدة للترخيص
- ✅ `app/Http/Controllers/LicenseController.php` - Controller للترخيص
- ✅ `app/Http/Middleware/CheckLicense.php` - Middleware للتحقق من الترخيص
- ✅ `config/license.php` - إعدادات الترخيص
- ✅ `routes/api_license.php` - Routes للترخيص
- ✅ `database/migrations/2025_12_20_000000_create_licenses_table.php` - Migration لجدول الترخيص

### ملفات العمل Offline
- ✅ `public/sw.js` - Service Worker للعمل Offline
- ✅ `public/offline.html` - صفحة عدم الاتصال
- ✅ `public/app-shell.html` - App Shell للعمل Offline

## الخطوات التالية

### 1. تحديث Composer
```bash
cd C:\xampp\htdocs\pos
composer dump-autoload
```

### 2. تشغيل Migration
```bash
php artisan migrate
```

### 3. إعداد متغيرات البيئة (.env)
أضف المتغيرات التالية إلى ملف `.env`:
```env
LICENSE_ENABLED=true
LICENSE_CHECK_EVERY_REQUEST=false
LICENSE_GRACE_PERIOD=7
LICENSE_VERIFICATION_INTERVAL=3600
LICENSE_OFFLINE_MODE=true
LICENSE_SECRET_KEY=your-secret-key-change-this-to-random-string
LICENSE_VERIFICATION_URL=
LICENSE_ALLOW_MULTIPLE=false
```

### 4. تسجيل Service Worker
أضف الكود التالي في ملف `resources/views/app.blade.php` أو في ملف HTML الرئيسي:
```html
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered:', registration);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    });
}
</script>
```

### 5. استخدام Middleware (اختياري)
إذا كنت تريد تطبيق التحقق من الترخيص على جميع Routes:
```php
// في app/Http/Kernel.php - أضف إلى $middlewareGroups['web']
\App\Http\Middleware\CheckLicense::class,
```

أو استخدمه على Routes محددة:
```php
Route::middleware('license')->group(function () {
    // Routes محمية بالترخيص
});
```

### 6. استخدام Helper Functions
```php
// التحقق من الترخيص
if (license()) {
    // الترخيص مفعل
}

// الحصول على معلومات الترخيص
$info = license_info();

// نوع الترخيص
$type = license_type();

// تاريخ انتهاء الترخيص
$expires = license_expires_at();

// الأيام المتبقية
$days = license_days_remaining();
```

## API Endpoints

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

## ملاحظات مهمة

1. **تغيير Secret Key**: تأكد من تغيير `LICENSE_SECRET_KEY` في ملف `.env` إلى قيمة عشوائية قوية.

2. **Offline Mode**: النظام يدعم التفعيل Offline، يمكن حفظ الترخيص في ملف محلي.

3. **Service Worker**: Service Worker يعمل تلقائياً عند تسجيله، ويوفر دعم Offline للصفحات.

4. **Database Connection**: النظام يدعم العمل مع MySQL و SQLite تلقائياً.

## استكشاف الأخطاء

### الترخيص لا يعمل
- تأكد من تشغيل Migration
- تحقق من إعدادات `.env`
- تأكد من تسجيل Helper في `composer.json`

### Service Worker لا يعمل
- تأكد من تسجيل Service Worker في الصفحة الرئيسية
- تحقق من Console في المتصفح للأخطاء
- تأكد من أن الملف `public/sw.js` موجود

## الدعم

للمزيد من المعلومات، راجع ملفات الكود المنسوخة أو تواصل مع فريق التطوير.

