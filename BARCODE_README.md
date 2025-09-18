# نظام توليد وطباعة الباركود

## نظرة عامة
تم إضافة نظام شامل لتوليد وطباعة الباركود للمنتجات في النظام. يدعم النظام أنواع مختلفة من الباركود وطرق طباعة متعددة.

## الميزات المضافة

### 1. توليد الباركود
- توليد باركود تلقائي للمنتجات
- دعم أنواع مختلفة من الباركود (Code 128, Code 39, EAN-13, إلخ)
- إمكانية تخصيص أبعاد الباركود
- حفظ الباركود في قاعدة البيانات

### 2. طباعة الباركود
- طباعة فردية للمنتجات
- طباعة مجمعة لعدة منتجات
- دعم طابعات الباركود الحرارية
- معاينة الباركود قبل الطباعة
- تحميل الباركود كصورة

### 3. إدارة الباركود
- واجهة مستخدم سهلة الاستخدام
- بحث وتصفية المنتجات
- إعدادات طابعة قابلة للتخصيص
- سجل عمليات الباركود

## الملفات المضافة

### Backend
- `app/Services/BarcodeService.php` - خدمة توليد الباركود
- `app/Http/Controllers/BarcodeController.php` - كونترولر الباركود
- `app/Facades/Barcode.php` - Facade للباركود
- `app/Providers/BarcodeServiceProvider.php` - Service Provider
- `config/barcode.php` - إعدادات الباركود
- `database/migrations/2025_01_27_230000_add_barcode_to_products_table.php` - ميجريشن الباركود

### Frontend
- `resources/js/Pages/Barcode/Index.vue` - صفحة إدارة الباركود
- `resources/js/Components/BarcodePrinter.vue` - مكون طباعة الباركود

### Routes
```php
Route::prefix('barcode')->name('barcode.')->group(function () {
    Route::get('/', [BarcodeController::class, 'index'])->name('index');
    Route::post('/generate', [BarcodeController::class, 'generate'])->name('generate');
    Route::post('/print', [BarcodeController::class, 'print'])->name('print');
    Route::post('/batch-print', [BarcodeController::class, 'batchPrint'])->name('batch.print');
    Route::get('/download/{product}', [BarcodeController::class, 'download'])->name('download');
    Route::get('/preview', [BarcodeController::class, 'preview'])->name('preview');
    Route::get('/printer-settings', [BarcodeController::class, 'printerSettings'])->name('printer.settings');
});
```

## كيفية الاستخدام

### 1. الوصول إلى صفحة الباركود
- انتقل إلى القائمة الجانبية
- اضغط على "توليد الباركود"

### 2. توليد باركود جديد
- اختر المنتج من القائمة
- اضغط على "توليد"
- اختر نوع الباركود والأبعاد
- اضغط "توليد"

### 3. طباعة الباركود
- اختر المنتج مع الباركود
- اضغط على "طباعة"
- حدد كمية الطباعة
- اضغط "طباعة"

### 4. الطباعة المجمعة
- اختر عدة منتجات باستخدام المربعات
- اضغط على "طباعة مجمعة"
- حدد الكمية لكل منتج
- اضغط "طباعة الكل"

## إعدادات الطابعة

### الطابعات الحرارية
```env
THERMAL_PRINTER_ENABLED=true
THERMAL_PRINTER_IP=192.168.1.100
THERMAL_PRINTER_PORT=9100
```

### إعدادات الباركود الافتراضية
```env
BARCODE_DEFAULT_TYPE=PNG
BARCODE_DEFAULT_WIDTH=2
BARCODE_DEFAULT_HEIGHT=30
```

## أنواع الباركود المدعومة

- **Code 128** - الأكثر شيوعاً
- **Code 39** - للأرقام والحروف
- **EAN-13** - للمنتجات التجارية
- **EAN-8** - للمنتجات الصغيرة
- **UPC-A** - للولايات المتحدة
- **UPC-E** - للمنتجات الصغيرة
- **Codabar** - للمكتبات والصيدليات
- **MSI** - للمخازن
- **Postnet** - للبريد الأمريكي
- **Planet** - للبريد الأمريكي
- **RMS4CC** - للبريد الكندي
- **KIX** - للبريد الهولندي
- **IMB** - للبريد الأمريكي
- **Code 11** - للاتصالات
- **Pharma Code** - للأدوية
- **Pharma Code 2 Tracks** - للأدوية

## الصلاحيات المطلوبة

- `read product` - لعرض المنتجات
- `create product` - لتوليد وطباعة الباركود

## استكشاف الأخطاء

### مشكلة: لا يظهر الباركود
- تأكد من وجود حقل `barcode` في جدول `products`
- تحقق من تشغيل الميجريشن
- تأكد من صلاحيات المستخدم

### مشكلة: فشل الطباعة
- تحقق من إعدادات الطابعة
- تأكد من اتصال الطابعة بالشبكة
- تحقق من السماح للنوافذ المنبثقة

### مشكلة: خطأ في توليد الباركود
- تأكد من تثبيت مكتبة `picqer/php-barcode-generator`
- تحقق من صلاحيات مجلد التخزين
- تأكد من صحة البيانات المدخلة

## التطوير المستقبلي

- دعم QR Code
- طباعة مباشرة عبر USB
- تكامل مع أنظمة ERP
- تقارير الباركود
- API للطرف الثالث