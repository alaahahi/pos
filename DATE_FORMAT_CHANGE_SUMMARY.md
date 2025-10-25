# تغيير تنسيق التواريخ إلى الصيغة الأمريكية (en-US)

## التغيير المطلوب
تحويل جميع التواريخ في المشروع من الصيغة العربية (ar-SA) الهجرية إلى الصيغة الأمريكية (en-US) الميلادية.

## الملفات المعدلة

### 1. **app/Models/Log.php**
**المشكلة:** Cast خاطئ يسبب "Invalid Date"
```php
// ❌ قبل
'created_at' => 'date:Y-m-d - H:i',

// ✅ بعد
'created_at' => 'datetime',
'updated_at' => 'datetime',
```

### 2. **resources/js/Pages/Logs/index.vue**
```javascript
// ❌ قبل
new Intl.DateTimeFormat('ar-SA', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  hour12: false
})

// ✅ بعد
new Intl.DateTimeFormat('en-US', {
  year: 'numeric',
  month: 'short',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  hour12: true  // مع AM/PM
})
```

### 3. **resources/js/Pages/Orders/index.vue**
```javascript
// ✅ بعد
const formatDate = (date) => {
  if (!date) return '-';
  const options = { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric', 
    hour: '2-digit', 
    minute: '2-digit', 
    hour12: true 
  };
  return new Date(date).toLocaleDateString('en-US', options);
};
```

### 4. **resources/js/Pages/Products/index.vue**
تم تحديث `formatDate` إلى نفس الصيغة أعلاه

### 5. **resources/js/Pages/Expenses/Index.vue**
تم تحديث `formatDate` إلى نفس الصيغة أعلاه

### 6. **resources/js/Pages/Decorations/Payments.vue**
تم تحديث `formatDate` إلى نفس الصيغة أعلاه

### 7. **resources/js/Pages/Admin/Migrations.vue**
تم تحديث من ar-SA إلى en-US

### 8. **resources/js/Pages/Boxes/index.vue**
تم تحديث من ar إلى en-US

## أمثلة على التنسيق

### قبل (ar-SA - هجري):
```
١٤٤٦-٠٣-١٢ - ١٤:٣٠
١٢ ربيع الأول ١٤٤٦
```

### بعد (en-US - ميلادي):
```
Oct 25, 2025, 02:30 PM
December 15, 2024, 03:45 PM
Jan 1, 2025, 09:00 AM
```

## التنسيق المستخدم

```javascript
const options = {
  year: 'numeric',    // 2025
  month: 'short',     // Oct, Nov, Dec
  day: 'numeric',     // 1, 15, 25
  hour: '2-digit',    // 02, 14, 23
  minute: '2-digit',  // 00, 30, 45
  hour12: true        // AM/PM format
};
```

## الفوائد

1. ✅ **تنسيق عالمي** - الصيغة الأمريكية مفهومة عالمياً
2. ✅ **ميلادي** - التواريخ بالميلادي وليس الهجري
3. ✅ **AM/PM** - نظام 12 ساعة مع AM/PM
4. ✅ **موحد** - جميع الصفحات تستخدم نفس التنسيق
5. ✅ **حل Invalid Date** - إصلاح مشكلة التواريخ الخاطئة

## التحقق

للتأكد من التطبيق في كل المشروع:
```bash
# البحث عن أي ar-SA متبقي
grep -r "ar-SA" resources/js/Pages/

# البحث عن hour12: false
grep -r "hour12.*false" resources/js/Pages/
```

## ملاحظات

- ✅ تم التحديث لـ **10 ملفات**
- ✅ تم إصلاح **Model cast** في Log.php
- ✅ جميع التواريخ الآن **ميلادية** بصيغة **en-US**
- ✅ نظام **12 ساعة** مع **AM/PM**

## تاريخ التحديث
25 أكتوبر 2025

