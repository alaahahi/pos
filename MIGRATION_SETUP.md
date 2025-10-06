# إعداد قاعدة البيانات - Migration Management

## الوصول للنظام

يمكنك الآن الوصول لنظام إدارة المايكريشنات بدون تسجيل دخول باستخدام المفتاح الثابت:

### رابط الوصول:
```
http://127.0.0.1:8000/admin/migrations?key=migrate123
```

## المفتاح الثابت
- **المفتاح**: `migrate123`
- **الاستخدام**: أضف `?key=migrate123` في نهاية الرابط

## الميزات المتاحة

1. **عرض حالة المايكريشنات** - GET `/admin/migrations?key=migrate123`
2. **تشغيل المايكريشنات** - POST `/admin/migrations/run?key=migrate123`
3. **تراجع المايكريشنات** - POST `/admin/migrations/rollback?key=migrate123`
4. **إعادة تشغيل الكل** - POST `/admin/migrations/refresh?key=migrate123`
5. **تشغيل الـ Seeders** - POST `/admin/migrations/seeders?key=migrate123`

## ملاحظات مهمة

- ✅ لا يحتاج تسجيل دخول
- ✅ مفتاح ثابت بسيط للحماية
- ✅ يعمل حتى لو لم تكن قاعدة البيانات موجودة
- ✅ يتحقق من المفتاح في كل طلب

## تغيير المفتاح

لتغيير المفتاح، عدّل في ملف `app/Http/Controllers/MigrationController.php`:

```php
private function checkAccessKey(Request $request)
{
    $accessKey = $request->get('key') ?? $request->header('X-Access-Key');
    $validKey = 'YOUR_NEW_KEY_HERE'; // غير هذا المفتاح
    
    if ($accessKey !== $validKey) {
        abort(403, 'مفتاح الوصول غير صحيح. استخدم: ?key=YOUR_NEW_KEY_HERE');
    }
    
    return true;
}
```

## الأمان

⚠️ **تحذير**: هذا النظام مصمم للتطوير والاختبار فقط. لا تستخدمه في البيئة الإنتاجية بدون حماية إضافية.
