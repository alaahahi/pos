# دليل إعداد نظام الترخيص والعمل Offline

## 📋 ملخص الملفات المنقولة

### ✅ نظام الترخيص (License System)
- ✅ `app/Models/License.php` - نموذج الترخيص
- ✅ `app/Services/LicenseService.php` - خدمة الترخيص
- ✅ `app/Helpers/LicenseHelper.php` - دوال مساعدة للترخيص
- ✅ `app/Http/Controllers/LicenseController.php` - Controller للترخيص
- ✅ `app/Http/Middleware/CheckLicense.php` - Middleware للتحقق من الترخيص
- ✅ `config/license.php` - إعدادات الترخيص
- ✅ `routes/api_license.php` - Routes للترخيص

### ✅ Migrations
- ✅ `database/migrations/2025_12_20_000000_create_licenses_table.php` - جدول الترخيص
- ✅ `database/migrations/2025_12_20_000001_create_sync_metadata_table.php` - جدول مزامنة البيانات

### ✅ ملفات العمل Offline
- ✅ `public/sw.js` - Service Worker للعمل Offline
- ✅ `public/offline.html` - صفحة عدم الاتصال
- ✅ `public/app-shell.html` - App Shell للعمل Offline
- ✅ `resources/js/composables/useSimpleOffline.js` - Composable للعمل Offline

### ✅ التحديثات على الملفات الموجودة
- ✅ `composer.json` - إضافة LicenseHelper في autoload
- ✅ `routes/api.php` - إضافة license routes
- ✅ `app/Http/Kernel.php` - تسجيل CheckLicense middleware
- ✅ `config/database.php` - إضافة sync_sqlite connection

---

## 🚀 خطوات الإعداد

### 1. تحديث Composer Autoload
```bash
cd C:\xampp\htdocs\pos
composer dump-autoload
```

### 2. تشغيل Migrations
```bash
php artisan migrate
```

سيتم إنشاء الجداول التالية:
- `licenses` - جدول الترخيصات
- `sync_metadata` - جدول مزامنة البيانات

### 3. إنشاء ملف SQLite للمزامنة (اختياري)
```bash
# إنشاء ملف sync.sqlite في مجلد database/
# يمكن إنشاؤه يدوياً أو سيتم إنشاؤه تلقائياً عند الاستخدام
```

### 4. إعداد متغيرات البيئة (.env)

أضف المتغيرات التالية إلى ملف `.env`:

```env
# ============================================
# إعدادات نظام الترخيص (License System)
# ============================================

# تفعيل/تعطيل نظام الترخيص
LICENSE_ENABLED=true

# التحقق من الترخيص عند كل طلب (قد يؤثر على الأداء)
# قم بتعطيله (false) لتحسين الأداء
LICENSE_CHECK_EVERY_REQUEST=false

# فترة السماح بعد انتهاء الترخيص (بالأيام)
# يمكن للمستخدم الاستمرار في الاستخدام لمدة 7 أيام بعد انتهاء الترخيص
LICENSE_GRACE_PERIOD=7

# فترة التحقق الدوري من الترخيص (بالثواني)
# 3600 = ساعة واحدة
LICENSE_VERIFICATION_INTERVAL=3600

# دعم التفعيل Offline
# إذا كان true، يمكن تفعيل الترخيص بدون اتصال بالإنترنت
LICENSE_OFFLINE_MODE=true

# Secret Key لتوقيع الترخيص
# ⚠️ مهم جداً: يجب تغيير هذا المفتاح إلى قيمة عشوائية قوية
# استخدم: php artisan key:generate --show للحصول على مفتاح عشوائي
LICENSE_SECRET_KEY=your-secret-key-change-this-to-random-string

# URL للتحقق Online من الترخيص (اختياري)
# اتركه فارغاً للعمل Offline فقط
LICENSE_VERIFICATION_URL=

# السماح بتعدد التثبيتات لنفس المفتاح
# false = لا يسمح بتثبيت نفس المفتاح على أكثر من سيرفر
LICENSE_ALLOW_MULTIPLE=false

# ============================================
# إعدادات المزامنة (Sync Settings)
# ============================================

# مسار ملف SQLite للمزامنة
# سيتم استخدامه لتخزين البيانات محلياً للمزامنة
SYNC_SQLITE_PATH=database/sync.sqlite
```

### 5. توليد Secret Key آمن

```bash
# توليد مفتاح عشوائي آمن
php artisan key:generate --show
```

انسخ المفتاح المُولد وضعه في `LICENSE_SECRET_KEY` في ملف `.env`.

أو يمكنك استخدام هذا الأمر لتوليد مفتاح عشوائي:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 6. تسجيل Service Worker (اختياري)

إذا كنت تريد تفعيل دعم PWA والعمل Offline، أضف الكود التالي في ملف `resources/views/app.blade.php`:

```html
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('✅ Service Worker registered:', registration);
            })
            .catch(error => {
                console.log('❌ Service Worker registration failed:', error);
            });
    });
}
</script>
```

---

## 📝 استخدام نظام الترخيص

### استخدام Middleware

#### تطبيق على جميع Routes:
```php
// في app/Http/Kernel.php - أضف إلى $middlewareGroups['web']
\App\Http\Middleware\CheckLicense::class,
```

#### تطبيق على Routes محددة:
```php
Route::middleware('license')->group(function () {
    // Routes محمية بالترخيص
});
```

### استخدام Helper Functions

```php
// التحقق من الترخيص
if (license()) {
    // الترخيص مفعل
}

// الحصول على معلومات الترخيص
$info = license_info();

// نوع الترخيص
$type = license_type(); // 'trial', 'standard', 'premium'

// تاريخ انتهاء الترخيص
$expires = license_expires_at();

// الأيام المتبقية
$days = license_days_remaining();
```

---

## 🔌 API Endpoints

### الحصول على معلومات السيرفر (للتوليد Offline)
```
GET /api/license/server-info
```

### تفعيل الترخيص
```
POST /api/license/activate
Content-Type: application/json

{
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

---

## ⚙️ إعدادات متقدمة

### تعطيل نظام الترخيص مؤقتاً
في ملف `.env`:
```env
LICENSE_ENABLED=false
```

### تغيير فترة السماح
```env
LICENSE_GRACE_PERIOD=14  # 14 يوم بدلاً من 7
```

### تفعيل التحقق عند كل طلب (للتطوير فقط)
```env
LICENSE_CHECK_EVERY_REQUEST=true
```

⚠️ **تحذير**: هذا سيؤثر على الأداء، استخدمه فقط للتطوير والاختبار.

---

## 🔍 استكشاف الأخطاء

### الترخيص لا يعمل
1. ✅ تأكد من تشغيل Migration: `php artisan migrate`
2. ✅ تحقق من إعدادات `.env`
3. ✅ تأكد من تسجيل Helper في `composer.json`
4. ✅ قم بتشغيل: `composer dump-autoload`

### Service Worker لا يعمل
1. ✅ تأكد من تسجيل Service Worker في الصفحة الرئيسية
2. ✅ تحقق من Console في المتصفح للأخطاء
3. ✅ تأكد من أن الملف `public/sw.js` موجود
4. ✅ تأكد من أن HTTPS مفعل (أو localhost للتطوير)

### خطأ في قاعدة البيانات
1. ✅ تأكد من أن جدول `licenses` موجود
2. ✅ تحقق من إعدادات قاعدة البيانات في `.env`
3. ✅ تأكد من أن ملف `sync.sqlite` موجود (إذا كنت تستخدم المزامنة)

---

## 📌 ملاحظات مهمة

1. **Secret Key**: ⚠️ يجب تغيير `LICENSE_SECRET_KEY` إلى قيمة عشوائية قوية قبل الإنتاج.

2. **Offline Mode**: النظام يدعم التفعيل Offline، يمكن حفظ الترخيص في ملف محلي (`storage/app/license.key`).

3. **Service Worker**: Service Worker يعمل تلقائياً عند تسجيله، ويوفر دعم Offline للصفحات.

4. **Database Connection**: النظام يدعم العمل مع MySQL و SQLite تلقائياً.

5. **Performance**: لتجنب التأثير على الأداء، استخدم `LICENSE_CHECK_EVERY_REQUEST=false` في الإنتاج.

---

## 📞 الدعم

للمزيد من المعلومات، راجع ملفات الكود المنسوخة أو تواصل مع فريق التطوير.




