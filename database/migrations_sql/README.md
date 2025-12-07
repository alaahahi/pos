# ملفات SQL للتعديلات الأخيرة

## الملفات المتوفرة

### ملف شامل (موصى به):
1. **`all_migrations.sql`** - ملف واحد يحتوي على جميع التعديلات (الإغلاقات + التصنيفات)

### ملفات منفصلة:

#### للإغلاقات اليومية والشهرية:
1. **`latest_migrations.sql`** - نسخة آمنة مع فحص وجود الأعمدة قبل الإضافة
2. **`latest_migrations_simple.sql`** - نسخة بسيطة بدون فحوصات (أسرع)

#### للتصنيفات:
1. **`categories_migrations.sql`** - نسخة آمنة مع فحص وجود الجداول والأعمدة قبل الإضافة
2. **`categories_migrations_simple.sql`** - نسخة بسيطة بدون فحوصات (أسرع)

## التعديلات المطلوبة

### جدول `daily_closes` (الإغلاقات اليومية)
إضافة الأعمدة التالية:
- `direct_deposits_usd` - الإضافات المباشرة بالدولار
- `direct_deposits_iqd` - الإضافات المباشرة بالدينار
- `direct_withdrawals_usd` - السحوبات المباشرة بالدولار
- `direct_withdrawals_iqd` - السحوبات المباشرة بالدينار

### جدول `monthly_closes` (الإغلاقات الشهرية)
إضافة نفس الأعمدة المذكورة أعلاه.

## طريقة التطبيق

### الطريقة الموصى بها: استخدام الملف الشامل
```bash
mysql -u username -p database_name < all_migrations.sql
```

### أو من خلال phpMyAdmin:
1. افتح phpMyAdmin
2. اختر قاعدة البيانات
3. اضغط على تبويب SQL
4. انسخ محتوى `all_migrations.sql` والصقه
5. اضغط تنفيذ

### طرق أخرى (ملفات منفصلة):

#### للإغلاقات:
```bash
mysql -u username -p database_name < latest_migrations_simple.sql
```

#### للتصنيفات:
```bash
mysql -u username -p database_name < categories_migrations_simple.sql
```


## ملاحظات مهمة

- تأكد من عمل نسخة احتياطية من قاعدة البيانات قبل التطبيق
- إذا ظهر خطأ "Column already exists" في النسخة البسيطة، يمكن تجاهله
- النسخة الآمنة تتحقق من وجود الأعمدة قبل الإضافة، لذا لن تظهر أخطاء

## التحقق من نجاح التطبيق

بعد تطبيق SQL، يمكنك التحقق من نجاح العملية باستخدام:

```sql
-- التحقق من جدول daily_closes
DESCRIBE daily_closes;

-- التحقق من جدول monthly_closes
DESCRIBE monthly_closes;

-- التحقق من جدول categories
DESCRIBE categories;

-- التحقق من جدول products (عمود category_id)
DESCRIBE products;

-- التحقق من جميع التعديلات
SHOW TABLES LIKE 'categories';
SHOW COLUMNS FROM `daily_closes` LIKE 'direct_%';
SHOW COLUMNS FROM `monthly_closes` LIKE 'direct_%';
SHOW COLUMNS FROM `products` LIKE 'category_id';
```

يجب أن ترى الأعمدة الجديدة في القائمة.


