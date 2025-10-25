# إصلاح مشكلة حذف دفعة من الصندوق

## المشكلة
كانت عملية حذف دفعة من الصندوق لا تعمل بشكل صحيح. الرصيد لم يكن يتم إرجاعه بشكل صحيح بعد حذف المعاملة.

## السبب
الكود القديم في دالة `delTransactions` في ملف `app/Http/Controllers/AccountingController.php` كان:

1. **يستخدم `decrement` دائماً** بغض النظر عن قيمة المعاملة (موجبة أو سالبة)
2. **لا يعكس تأثير المعاملات الفرعية** على محافظها بشكل صحيح
3. **منطق معقد ومكرر** مع العديد من الشروط المتداخلة
4. **لا يستخدم Transaction** لضمان سلامة البيانات

### مثال على المشكلة:
- إذا تم إيداع 100 دولار (amount = +100)، يزيد الرصيد
- عند الحذف، كان الكود يستخدم `decrement` مما يطرح 100 مرة أخرى
- النتيجة: الرصيد ينقص 100 بدلاً من العودة للقيمة الأصلية
- **الصحيح**: يجب طرح الـ 100 لإرجاع الرصيد للقيمة الأصلية

## الإصلاح

### 1. إعادة كتابة دالة `delTransactions`
تم تبسيط الدالة وجعلها أكثر وضوحاً ودقة:

```php
public function delTransactions(Request $request)
{
    DB::beginTransaction();
    
    try {
        $transaction_id = $request->id ?? 0;
        $originalTransaction = Transactions::find($transaction_id);
        
        if (!$originalTransaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        
        $wallet_id = $originalTransaction->wallet_id;
        $wallet = Wallet::find($wallet_id);
        
        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        // عكس تأثير المعاملة الأصلية على المحفظة
        $this->reverseTransactionEffect($originalTransaction, $wallet);

        // معالجة المعاملات الفرعية
        $childTransactions = Transactions::where('parent_id', $transaction_id)->get();
        
        if ($childTransactions->isNotEmpty()) {
            foreach ($childTransactions as $childTransaction) {
                $childWallet = Wallet::find($childTransaction->wallet_id);
                
                if ($childWallet) {
                    // عكس تأثير المعاملة الفرعية على محفظتها
                    $this->reverseTransactionEffect($childTransaction, $childWallet);
                }
                
                // حذف المعاملة الفرعية
                $childTransaction->delete();
            }
        }

        // حذف الصور المرتبطة بالمعاملة
        if ($originalTransaction->TransactionsImages) {
            foreach ($originalTransaction->TransactionsImages as $transactionsImage) {
                File::delete(public_path('uploads/' . $transactionsImage->name));
                File::delete(public_path('uploadsResized/' . $transactionsImage->name));
                $transactionsImage->delete();
            }
        }

        // حذف المعاملة الأصلية
        $originalTransaction->delete();
        
        DB::commit();
        
        return response()->json(['message' => 'Transaction deleted successfully']);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'message' => 'Error deleting transaction',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

### 2. إضافة دالة مساعدة `reverseTransactionEffect`
دالة بسيطة ومفهومة لعكس تأثير المعاملة:

```php
/**
 * عكس تأثير المعاملة على المحفظة
 * 
 * @param Transactions $transaction
 * @param Wallet $wallet
 * @return void
 */
private function reverseTransactionEffect($transaction, $wallet)
{
    $amount = $transaction->amount;
    $currency = $transaction->currency;
    
    // تحديد الحقل المناسب في المحفظة
    if ($currency == '$' || $currency == 'USD') {
        $balanceField = 'balance';
    } elseif ($currency == 'IQD') {
        $balanceField = 'balance_dinar';
    } else {
        $balanceField = 'balance';
    }
    
    // عكس تأثير المعاملة
    // إذا كانت amount موجبة (تم إضافتها للمحفظة)، نطرحها
    // إذا كانت amount سالبة (تم طرحها من المحفظة)، نضيفها
    $newBalance = $wallet->$balanceField - $amount;
    $wallet->update([$balanceField => $newBalance]);
}
```

## المزايا الجديدة

1. **منطق بسيط ومفهوم**: دالة واحدة لعكس التأثير بدلاً من شروط معقدة
2. **يعمل مع جميع الحالات**:
   - إيداع USD/IQD
   - سحب USD/IQD
   - معاملات فرعية بأي عملة
3. **استخدام Database Transaction**: لضمان سلامة البيانات
4. **معالجة الأخطاء**: في حالة حدوث خطأ، يتم التراجع عن جميع التغييرات
5. **كود قابل للصيانة**: سهل القراءة والفهم والتعديل

## الاختبار

تم إنشاء ملفين للاختبار:

### 1. PHPUnit Test
ملف: `tests/Feature/BoxTransactionDeletionTest.php`

يحتوي على اختبارات شاملة:
- اختبار حذف إيداع USD
- اختبار حذف إيداع IQD
- اختبار حذف سحب USD
- اختبار حذف معاملة مع معاملات فرعية

### 2. سكريبت اختبار بسيط
ملف: `test_box_deletion.php`

سكريبت يمكن تشغيله مباشرة من سطر الأوامر لاختبار الإصلاح بشكل سريع.

## كيفية تشغيل الاختبارات

### الطريقة 1: PHPUnit
```bash
php artisan test --filter=BoxTransactionDeletionTest
```

### الطريقة 2: السكريبت البسيط
```bash
php test_box_deletion.php
```

أو استخدام ملف الـ batch:
```bash
run_test.bat
```

## أمثلة على الحالات

### حالة 1: إيداع ثم حذف
```
الرصيد الأولي: 1000 USD
إيداع: +100 USD → الرصيد: 1100 USD
حذف المعاملة → الرصيد: 1000 USD ✓
```

### حالة 2: سحب ثم حذف
```
الرصيد الأولي: 100000 IQD
سحب: -50000 IQD → الرصيد: 50000 IQD
حذف المعاملة → الرصيد: 100000 IQD ✓
```

### حالة 3: معاملة مع معاملات فرعية
```
الرصيد الأولي: 1000 USD, 100000 IQD
معاملة رئيسية: +200 USD
معاملة فرعية 1: +50 USD
معاملة فرعية 2: +25000 IQD
الرصيد بعد المعاملات: 1250 USD, 125000 IQD

حذف المعاملة الرئيسية:
→ الرصيد: 1000 USD, 100000 IQD ✓
→ حذف جميع المعاملات الفرعية ✓
```

## ملاحظات مهمة

1. **استخدام Database Transaction**: جميع العمليات في transaction واحدة، إذا فشلت أي عملية يتم التراجع عن الكل
2. **المنطق الصحيح**: `newBalance = currentBalance - transactionAmount`
   - إذا كانت `transactionAmount` موجبة: نطرح (نعكس الإيداع)
   - إذا كانت `transactionAmount` سالبة: نضيف (نعكس السحب)
3. **المعاملات الفرعية**: يتم حذفها وعكس تأثيرها على محافظها الخاصة

## التحقق اليدوي

يمكنك التحقق من الإصلاح بالخطوات التالية:

1. افتح صفحة الصندوق في النظام
2. سجل الرصيد الحالي
3. أضف دفعة جديدة (إيداع أو سحب)
4. تحقق من تحديث الرصيد
5. احذف الدفعة
6. تحقق من أن الرصيد عاد للقيمة الأصلية ✓

---

**تم الإصلاح بتاريخ**: ٢٥ أكتوبر ٢٠٢٥
**الملفات المعدلة**: 
- `app/Http/Controllers/AccountingController.php`
**الملفات المضافة**:
- `tests/Feature/BoxTransactionDeletionTest.php`
- `test_box_deletion.php`
- `run_test.bat`
- `BOX_DELETION_FIX_README.md`

