# 📖 دليل تشغيل اختبارات صفحة Boxes

## 🎯 نظرة عامة

تم إنشاء نظام اختبار شامل لصفحة Boxes يغطي جميع الوظائف الأساسية:

- ✅ التحميل والعرض
- ✅ البحث والفلترة
- ✅ الإضافة والسحب
- ✅ التحويل بين العملات
- ✅ الحذف
- ✅ الطباعة
- ✅ التحديث
- ✅ Pagination

---

## 📁 الملفات المنشأة

### 1. ملفات الاختبار:
```
tests/Feature/BoxesTest.php          - ملف الاختبارات الآلية (15 اختبار)
BOXES_TESTING_GUIDE.md               - دليل الاختبار اليدوي الشامل
```

### 2. ملفات التشغيل:
```
run-boxes-tests.sh                   - سكريبت تشغيل Linux/Mac
run-boxes-tests.bat                  - سكريبت تشغيل Windows
```

### 3. ملفات التوثيق:
```
TESTING_INSTRUCTIONS.md              - هذا الملف
```

---

## 🚀 كيفية تشغيل الاختبارات

### الطريقة 1: تشغيل تلقائي (مُوصى به) ⭐

#### على Windows:
```batch
# افتح PowerShell أو CMD في مجلد المشروع
.\run-boxes-tests.bat
```

#### على Linux/Mac:
```bash
# اجعل الملف قابل للتنفيذ
chmod +x run-boxes-tests.sh

# شغّل الاختبارات
./run-boxes-tests.sh
```

### الطريقة 2: تشغيل يدوي

```bash
# 1. مسح الكاش
php artisan config:clear
php artisan cache:clear

# 2. تجهيز قاعدة البيانات
php artisan migrate:fresh --env=testing --seed

# 3. تشغيل الاختبارات
php artisan test --filter=BoxesTest
```

### الطريقة 3: تشغيل اختبار واحد محدد

```bash
# تشغيل اختبار واحد فقط
php artisan test --filter=test_boxes_page_loads_successfully

# مثال آخر
php artisan test --filter=test_add_to_box_usd
```

---

## 📊 قائمة الاختبارات المتوفرة

### ✅ الاختبارات الآلية (15 اختبار)

| # | اسم الاختبار | الوصف |
|---|-------------|-------|
| 1 | test_boxes_page_loads_successfully | تحميل الصفحة بنجاح |
| 2 | test_filter_by_date_range | فلترة بالتاريخ |
| 3 | test_filter_by_name | فلترة بالاسم |
| 4 | test_filter_by_note | فلترة بالملاحظة |
| 5 | test_add_to_box_usd | إضافة بالدولار |
| 6 | test_add_to_box_iqd | إضافة بالدينار |
| 7 | test_drop_from_box_usd | سحب بالدولار |
| 8 | test_convert_dinar_to_dollar | تحويل دينار→دولار |
| 9 | test_convert_dollar_to_dinar | تحويل دولار→دينار |
| 10 | test_delete_transaction | حذف معاملة |
| 11 | test_print_receipt | طباعة وصل |
| 12 | test_refresh_transactions | تحديث المعاملات |
| 13 | test_pagination_works | Pagination |
| 14 | test_filters_persist_with_pagination | الفلاتر مع Pagination |
| 15 | test_exchange_rate_displayed | عرض سعر الصرف |

---

## 🎨 فهم نتائج الاختبار

### نتيجة ناجحة ✅
```
✓ test_boxes_page_loads_successfully
✓ test_filter_by_date_range
✓ test_add_to_box_usd
...

Tests:  15 passed
Time:   1.23s
```

### نتيجة فاشلة ❌
```
✗ test_add_to_box_usd
  ↳ Failed asserting that 5000 is equal to 5100.

Tests:  14 passed, 1 failed
Time:   1.45s
```

---

## 🛠️ إعداد بيئة الاختبار

### 1. إنشاء قاعدة بيانات للاختبار

في ملف `.env.testing`:
```env
APP_ENV=testing
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_testing
DB_USERNAME=root
DB_PASSWORD=
```

### 2. إنشاء قاعدة البيانات
```bash
# دخول MySQL
mysql -u root -p

# إنشاء قاعدة البيانات
CREATE DATABASE pos_testing;
exit;
```

### 3. تشغيل Migrations
```bash
php artisan migrate --env=testing
php artisan db:seed --env=testing
```

---

## 📝 الاختبار اليدوي

للاختبار اليدوي الشامل، افتح ملف:
```
BOXES_TESTING_GUIDE.md
```

يحتوي على 10 سيناريوهات مفصلة مع:
- خطوات الاختبار
- النتائج المتوقعة
- مساحة لتسجيل النتائج الفعلية

---

## 🔍 نصائح لتشخيص الأخطاء

### 1. خطأ في الاتصال بقاعدة البيانات
```bash
# تحقق من ملف .env.testing
cat .env.testing

# تحقق من أن قاعدة البيانات موجودة
mysql -u root -p -e "SHOW DATABASES;"
```

### 2. خطأ في الصلاحيات
```bash
# تحقق من أن جدول permissions موجود
php artisan migrate --path=database/migrations/..._create_permission_tables.php
```

### 3. خطأ في Factory
```bash
# إنشاء Factories المفقودة
php artisan make:factory BoxFactory --model=Box
php artisan make:factory TransactionFactory --model=Transactions
```

### 4. مشاهدة الأخطاء بالتفصيل
```bash
# تشغيل الاختبارات مع تفاصيل أكثر
php artisan test --filter=BoxesTest --verbose
```

---

## 📈 تقارير الاختبار

### إنشاء تقرير HTML
```bash
php artisan test --filter=BoxesTest --log-junit test-results.xml
```

### عرض تغطية الكود
```bash
# مع PHPUnit
./vendor/bin/phpunit --coverage-html coverage tests/Feature/BoxesTest.php

# افتح التقرير
open coverage/index.html
```

---

## ✅ قائمة التحقق قبل Production

- [ ] جميع الاختبارات الآلية تنجح (15/15)
- [ ] تم اختبار جميع السيناريوهات اليدوية
- [ ] لا توجد أخطاء في Console
- [ ] الصفحة تعمل على Chrome
- [ ] الصفحة تعمل على Firefox
- [ ] الصفحة تعمل على Safari
- [ ] Responsive على الموبايل
- [ ] Responsive على التابلت
- [ ] الطباعة تعمل بشكل صحيح
- [ ] جميع الأزرار تعمل
- [ ] جميع الفلاتر تعمل
- [ ] Pagination يعمل
- [ ] الأداء مقبول (< 2 ثانية)

---

## 🐛 مشاكل معروفة وحلولها

### المشكلة 1: "Class 'Database\Factories\BoxFactory' not found"
**الحل:**
```bash
php artisan make:factory BoxFactory --model=Box
```

### المشكلة 2: "SQLSTATE[HY000] [1049] Unknown database"
**الحل:**
```bash
# إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE pos_testing;"
php artisan migrate --env=testing
```

### المشكلة 3: "Permission denied: run-boxes-tests.sh"
**الحل:**
```bash
chmod +x run-boxes-tests.sh
```

### المشكلة 4: اختبار يفشل بسبب البيانات
**الحل:**
```bash
# إعادة تهيئة قاعدة البيانات
php artisan migrate:fresh --env=testing --seed
```

---

## 📞 الدعم

إذا واجهت أي مشاكل:

1. تحقق من `storage/logs/laravel.log`
2. شغّل الاختبارات مع `--verbose`
3. تأكد من أن جميع Dependencies مثبتة:
   ```bash
   composer install
   ```
4. تأكد من أن `.env.testing` موجود ومُعد بشكل صحيح

---

## 🎯 الخطوات التالية

بعد نجاح جميع الاختبارات:

1. ✅ راجع النتائج
2. ✅ سجل أي ملاحظات
3. ✅ قم بالاختبار اليدوي على البيئة الفعلية
4. ✅ أنشر على Production
5. ✅ راقب الأداء بعد النشر

---

## 📚 مصادر إضافية

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Pest PHP](https://pestphp.com/) - إطار اختبار بديل

---

**آخر تحديث**: 2025-10-17  
**الإصدار**: 1.0.0  
**الحالة**: ✅ جاهز للإنتاج

