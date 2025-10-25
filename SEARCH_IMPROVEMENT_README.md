# تحسين البحث في صفحة إنشاء الطلبات

## المشكلة
كان البحث في صفحة إنشاء الطلبات (`/orders/create`) يعمل محلياً فقط على المنتجات المحملة مسبقاً في الصفحة، مما يحد من نتائج البحث.

## الحل
تم تحويل البحث ليعمل من الباك إند (Backend) مثل البركود تماماً، مع إظهار النتائج أثناء الكتابة (Live Search).

## التغييرات المطبقة

### 1. إضافة API Endpoint للبحث
**الملف:** `app/Http/Controllers/ProductController.php`

تم إضافة دالة جديدة `searchProducts()` للبحث في المنتجات:

```php
public function searchProducts(Request $request)
{
    $query = $request->input('query');
    
    if (empty($query)) {
        return response()->json([]);
    }

    // البحث في المنتجات النشطة فقط
    $products = Product::where('is_active', 1)
        ->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('barcode', 'LIKE', "%{$query}%")
              ->orWhere('model', 'LIKE', "%{$query}%");
        })
        ->select('id', 'name', 'model', 'price', 'barcode', 'quantity', 'image', 'is_featured', 'is_best_selling')
        ->limit(50) // تحديد النتائج بـ 50 منتج
        ->get();

    // استخدام image_url accessor من Model (يتم إضافته تلقائياً من $appends)
    return response()->json($products);
}
```

**ملاحظة:** يتم إضافة `image_url` تلقائياً من Model باستخدام accessor المعرّف في `Product.php`

### 2. إضافة Route للـ API
**الملف:** `routes/api.php`

```php
Route::get('products-search', [ProductController::class, 'searchProducts'])->name('products.search');
```

### 3. تحديث دالة البحث في الواجهة الأمامية
**الملف:** `resources/js/Pages/Orders/Create.vue`

تم تحويل دالة `searchProducts()` لتستخدم API مع debounce:

```javascript
const searchProducts = debounce(async () => {
  if (!searchQuery.value.trim()) {
    filterByType(selectedFilter.value);
    return;
  }

  try {
    loadingProducts.value = true;
    
    // البحث في الباك إند
    const response = await axios.get('/api/products-search', {
      params: {
        query: searchQuery.value.trim()
      }
    });

    if (response.data && response.data.length > 0) {
      filteredProducts.value = response.data;
      
      // حفظ المنتجات الجديدة في الكاش
      response.data.forEach(product => {
        if (product.barcode && !cachedProducts.value.has(product.barcode)) {
          cachedProducts.value.set(product.barcode, product);
        }
      });
    } else {
      filteredProducts.value = [];
      toast.info("لا توجد نتائج للبحث", {
        timeout: 2000,
        position: "bottom-right",
        rtl: true
      });
    }
  } catch (error) {
    console.error('خطأ في البحث:', error);
    toast.error("خطأ في البحث عن المنتجات", {
      timeout: 3000,
      position: "bottom-right",
      rtl: true
    });
    filterByType(selectedFilter.value);
  } finally {
    loadingProducts.value = false;
  }
}, 300);
```

## المميزات الجديدة

### 1. **البحث من الباك إند**
- يبحث في جميع المنتجات في قاعدة البيانات، وليس فقط المحملة في الصفحة
- يبحث في: الاسم، الباركود، والموديل

### 2. **النتائج الفورية (Live Search)**
- تظهر النتائج أثناء الكتابة مع تأخير 300ms (debounce)
- يمنع إرسال طلبات كثيرة للسيرفر

### 3. **حالة التحميل (Loading State)**
- يظهر مؤشر تحميل أثناء البحث
- يعطي تجربة مستخدم أفضل

### 4. **نظام الكاش**
- يحفظ المنتجات المبحوث عنها في الكاش المحلي
- يحسن الأداء عند البحث المتكرر

### 5. **رسائل توضيحية**
- رسالة "لا توجد نتائج للبحث" عند عدم وجود نتائج
- رسالة خطأ في حالة فشل الاتصال بالسيرفر

### 6. **تحديد النتائج**
- يحدد النتائج بـ 50 منتج لتحسين الأداء
- يمكن تعديل هذا العدد حسب الحاجة

## كيفية الاستخدام

1. افتح صفحة إنشاء الطلبات: `/orders/create`
2. ابدأ الكتابة في حقل البحث
3. ستظهر النتائج تلقائياً أثناء الكتابة (بعد 300ms من آخر حرف)
4. يمكنك البحث بـ:
   - **الاسم**: مثل "لابتوب"
   - **الباركود**: مثل "123456"
   - **الموديل**: مثل "HP-2024"

## ملاحظات تقنية

- **Debounce Time**: 300ms (يمكن تعديله حسب الحاجة)
- **حد النتائج**: 50 منتج (يمكن تعديله في ProductController)
- **المنتجات المعروضة**: فقط المنتجات النشطة (`is_active = 1`)
- **التوافق**: يعمل مع نظام الباركود الموجود

## الاختبار

للتأكد من أن التحديث يعمل بشكل صحيح:

1. ✅ افتح صفحة إنشاء الطلبات
2. ✅ اكتب في حقل البحث
3. ✅ تأكد من ظهور مؤشر التحميل
4. ✅ تأكد من ظهور النتائج بعد 300ms
5. ✅ تأكد من البحث في المنتجات غير المحملة في الصفحة
6. ✅ اختبر البحث بالاسم والباركود والموديل
7. ✅ تأكد من رسالة "لا توجد نتائج" عند البحث عن شيء غير موجود

## إصلاح مشكلة الصور

### المشكلة
كانت صور المنتجات لا تظهر عند البحث من الـ API.

### السبب
كان هناك اختلاف في مسار الصورة بين المنتجات المحملة في الصفحة والمنتجات من الـ API.

### الحل
تم استخدام الـ accessor المعرّف في Model المنتج (`image_url`) والذي يتم إضافته تلقائياً عند تحويل المنتج إلى JSON بفضل `$appends = ['image_url']`.

في `app/Models/Product.php`:
```php
protected $appends = ['image_url'];

public function getImageUrlAttribute(): ?string
{
    return isset($this->attributes['image']) && $this->attributes['image'] 
        ? asset("storage/{$this->attributes['image']}") 
        : null;
}
```

هذا يضمن أن جميع المنتجات (من الصفحة أو من الـ API) تستخدم نفس المسار للصور.

## تاريخ التحديثات
- **25 أكتوبر 2025**: إضافة البحث من الباك إند
- **25 أكتوبر 2025**: إصلاح مشكلة عرض الصور

