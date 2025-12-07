# API لإضافة صلاحيات التصنيفات

## الوصف
API endpoints لإضافة صلاحيات التصنيفات بسرعة على السيرفر بدون الحاجة لتشغيل seeders يدوياً.

## Endpoints المتوفرة

### 1. إضافة صلاحيات التصنيفات مباشرة
**URL:** `POST /api/permissions/add-category-permissions`

**الوصف:** يضيف صلاحيات التصنيفات ويربطها بالأدوار (superadmin, admin, staff)

**المصادقة:** يتطلب تسجيل الدخول (middleware auth)

**Response (نجاح):**
```json
{
  "success": true,
  "message": "تم إضافة صلاحيات التصنيفات بنجاح",
  "data": {
    "permissions_created": [
      "create category",
      "read category",
      "update category",
      "delete category",
      "view category"
    ],
    "roles_updated": ["superadmin", "admin", "staff"],
    "total_permissions": 5
  }
}
```

**Response (خطأ):**
```json
{
  "success": false,
  "message": "حدث خطأ أثناء إضافة الصلاحيات",
  "error": "Error message here"
}
```

### 2. تشغيل CategoryPermissionSeeder
**URL:** `POST /api/permissions/run-category-seeder`

**الوصف:** يشغل CategoryPermissionSeeder مباشرة

**المصادقة:** يتطلب تسجيل الدخول (middleware auth)

**Response (نجاح):**
```json
{
  "success": true,
  "message": "تم تشغيل CategoryPermissionSeeder بنجاح",
  "output": "Seeder output..."
}
```

## طرق الاستخدام

### 1. باستخدام cURL

#### إضافة الصلاحيات مباشرة:
```bash
curl -X POST https://your-domain.com/api/permissions/add-category-permissions \
  -H "Content-Type: application/json" \
  -H "Cookie: your_session_cookie" \
  -H "X-CSRF-TOKEN: your_csrf_token"
```

#### تشغيل Seeder:
```bash
curl -X POST https://your-domain.com/api/permissions/run-category-seeder \
  -H "Content-Type: application/json" \
  -H "Cookie: your_session_cookie" \
  -H "X-CSRF-TOKEN: your_csrf_token"
```

### 2. باستخدام Postman

1. افتح Postman
2. اختر POST method
3. أدخل URL: `https://your-domain.com/api/permissions/add-category-permissions`
4. في Headers أضف:
   - `Content-Type: application/json`
   - `X-CSRF-TOKEN: your_csrf_token`
   - `Cookie: your_session_cookie`
5. اضغط Send

### 3. باستخدام JavaScript (من المتصفح)

```javascript
// بعد تسجيل الدخول
fetch('/api/permissions/add-category-permissions', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  credentials: 'same-origin'
})
.then(response => response.json())
.then(data => {
  console.log('Success:', data);
})
.catch(error => {
  console.error('Error:', error);
});
```

### 4. باستخدام PHP (من السيرفر)

```php
// استخدام Laravel HTTP Client
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'X-CSRF-TOKEN' => csrf_token(),
])->post('/api/permissions/add-category-permissions');

$data = $response->json();
```

## الصلاحيات المضافة

سيتم إضافة الصلاحيات التالية:

1. `create category` - إضافة تصنيف جديد
2. `read category` - قراءة/عرض التصنيفات
3. `update category` - تعديل التصنيفات
4. `delete category` - حذف التصنيفات
5. `view category` - عرض تفاصيل التصنيف

## ربط الصلاحيات بالأدوار

- **superadmin**: جميع الصلاحيات (5 صلاحيات)
- **admin**: جميع الصلاحيات (5 صلاحيات)
- **staff**: صلاحيات القراءة والعرض فقط (2 صلاحيات)

## ملاحظات مهمة

1. **المصادقة مطلوبة**: يجب أن تكون مسجلاً دخولاً للوصول إلى الـ API
2. **CSRF Token**: يجب إرسال CSRF token مع الطلب
3. **Cache**: يتم مسح cache الصلاحيات تلقائياً بعد الإضافة
4. **Idempotent**: يمكن تشغيل الـ API عدة مرات بأمان (لن يضيف صلاحيات مكررة)

## مثال كامل باستخدام cURL مع CSRF

```bash
# 1. الحصول على CSRF token (من صفحة تسجيل الدخول)
CSRF_TOKEN="your_csrf_token_here"

# 2. الحصول على Session Cookie (من المتصفح بعد تسجيل الدخول)
SESSION_COOKIE="laravel_session=your_session_value"

# 3. إرسال الطلب
curl -X POST https://your-domain.com/api/permissions/add-category-permissions \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -H "Cookie: $SESSION_COOKIE"
```

## التحقق من النجاح

بعد تشغيل الـ API، يمكنك التحقق من الصلاحيات:

```sql
-- التحقق من الصلاحيات المضافة
SELECT * FROM permissions WHERE name LIKE '%category%';

-- التحقق من ربط الصلاحيات بالأدوار
SELECT r.name as role_name, p.name as permission_name
FROM role_has_permissions rhp
JOIN roles r ON rhp.role_id = r.id
JOIN permissions p ON rhp.permission_id = p.id
WHERE p.name LIKE '%category%';
```

