<?php

/**
 * اختبار يدوي بسيط لصفحة Boxes
 * 
 * التشغيل: php test-boxes-manual.php
 */

echo "\n";
echo "============================================\n";
echo "🧪 اختبار صفحة Boxes - نسخة يدوية بسيطة\n";
echo "============================================\n";
echo "\n";

// Colors for terminal
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

$passed = 0;
$failed = 0;
$skipped = 0;

function test($name, $callback) {
    global $passed, $failed, $skipped, $green, $red, $yellow, $reset;
    
    echo $yellow . "🔄 اختبار: " . $name . $reset . "\n";
    
    try {
        $result = $callback();
        if ($result === 'skip') {
            echo $yellow . "⏭️  تم تجاوز الاختبار\n" . $reset;
            $skipped++;
        } elseif ($result) {
            echo $green . "✅ نجح\n" . $reset;
            $passed++;
        } else {
            echo $red . "❌ فشل\n" . $reset;
            $failed++;
        }
    } catch (Exception $e) {
        echo $red . "❌ خطأ: " . $e->getMessage() . "\n" . $reset;
        $failed++;
    }
    
    echo "\n";
}

// Test 1: Check if required files exist
test("التحقق من وجود الملفات الأساسية", function() {
    $files = [
        'resources/js/Pages/Boxes/index.vue',
        'app/Http/Controllers/BoxesController.php',
        'resources/views/receiptPayment.blade.php',
        'resources/views/receiptPaymentTotal.blade.php',
        'resources/views/receiptExpensesTotal.blade.php',
    ];
    
    foreach ($files as $file) {
        if (!file_exists($file)) {
            echo "   ❌ ملف مفقود: $file\n";
            return false;
        }
    }
    
    echo "   ✓ جميع الملفات موجودة\n";
    return true;
});

// Test 2: Check Boxes Controller exists
test("التحقق من BoxesController", function() {
    $controller = file_get_contents('app/Http/Controllers/BoxesController.php');
    
    if (strpos($controller, 'public function index') === false) {
        echo "   ❌ method index غير موجود\n";
        return false;
    }
    
    if (strpos($controller, 'whereHasMorph') === false) {
        echo "   ❌ البحث بالاسم غير محدث\n";
        return false;
    }
    
    echo "   ✓ Controller محدث بالكامل\n";
    return true;
});

// Test 3: Check Vue component
test("التحقق من Vue Component", function() {
    $component = file_get_contents('resources/js/Pages/Boxes/index.vue');
    
    $checks = [
        'daily-sales-stats' => false,
        'stat-card' => true,
        'action-btn' => true,
        'filter-grid' => true,
        'loading-overlay' => true,
    ];
    
    foreach ($checks as $check => $expected) {
        $found = strpos($component, $check) !== false;
        if ($found !== $expected) {
            echo "   ❌ $check: " . ($expected ? 'مفقود' : 'موجود بالخطأ') . "\n";
            return false;
        }
    }
    
    echo "   ✓ Component محدث بشكل صحيح\n";
    return true;
});

// Test 4: Check receiptPayment view
test("التحقق من view وصل القبض", function() {
    if (!file_exists('resources/views/receiptPayment.blade.php')) {
        echo "   ❌ الملف غير موجود\n";
        return false;
    }
    
    $view = file_get_contents('resources/views/receiptPayment.blade.php');
    
    if (strpos($view, 'وصل قبض') === false) {
        echo "   ❌ المحتوى غير صحيح\n";
        return false;
    }
    
    if (strpos($view, 'window.print()') === false) {
        echo "   ❌ الطباعة التلقائية مفقودة\n";
        return false;
    }
    
    echo "   ✓ View جاهز للطباعة\n";
    return true;
});

// Test 5: Check API routes
test("التحقق من API Routes", function() {
    $routes = file_get_contents('routes/api.php');
    
    $requiredRoutes = [
        'boxes/transactions',
        'delTransactions',
        'convertDollarDinar',
        'convertDinarDollar',
        'add-to-box',
        'drop-from-box',
    ];
    
    foreach ($requiredRoutes as $route) {
        if (strpos($routes, $route) === false) {
            echo "   ❌ Route مفقود: $route\n";
            return false;
        }
    }
    
    echo "   ✓ جميع Routes موجودة\n";
    return true;
});

// Test 6: Check Factories
test("التحقق من Database Factories", function() {
    $factories = [
        'database/factories/BoxFactory.php',
        'database/factories/TransactionsFactory.php',
        'database/factories/WalletFactory.php',
    ];
    
    foreach ($factories as $factory) {
        if (!file_exists($factory)) {
            echo "   ❌ Factory مفقود: $factory\n";
            return false;
        }
    }
    
    echo "   ✓ جميع Factories موجودة\n";
    return true;
});

// Test 7: Check Models
test("التحقق من Models", function() {
    $models = [
        'app/Models/Box.php',
        'app/Models/Transactions.php',
        'app/Models/Wallet.php',
    ];
    
    foreach ($models as $model) {
        if (!file_exists($model)) {
            echo "   ❌ Model مفقود: $model\n";
            return false;
        }
    }
    
    echo "   ✓ جميع Models موجودة\n";
    return true;
});

// Test 8: Check test files
test("التحقق من ملفات الاختبار", function() {
    $testFiles = [
        'tests/Feature/BoxesTest.php',
        'tests/Feature/BoxesBasicTest.php',
        'BOXES_TESTING_GUIDE.md',
        'TESTING_INSTRUCTIONS.md',
    ];
    
    foreach ($testFiles as $file) {
        if (!file_exists($file)) {
            echo "   ❌ ملف مفقود: $file\n";
            return false;
        }
    }
    
    echo "   ✓ جميع ملفات الاختبار موجودة\n";
    return true;
});

// Test 9: Check documentation
test("التحقق من التوثيق", function() {
    $docs = [
        'BOXES_COMPLETION_SUMMARY.md',
        'BOXES_TESTING_GUIDE.md',
        'TESTING_INSTRUCTIONS.md',
    ];
    
    foreach ($docs as $doc) {
        if (!file_exists($doc)) {
            echo "   ❌ توثيق مفقود: $doc\n";
            return false;
        }
    }
    
    echo "   ✓ جميع الوثائق موجودة\n";
    return true;
});

// Test 10: Check views count
test("التحقق من عدد Views", function() {
    $views = glob('resources/views/receipt*.blade.php');
    
    if (count($views) < 4) {
        echo "   ❌ عدد Views غير كافي: " . count($views) . "/4\n";
        return false;
    }
    
    echo "   ✓ جميع Views موجودة (" . count($views) . "/4)\n";
    return true;
});

// Print Summary
echo "\n";
echo "============================================\n";
echo "📊 ملخص نتائج الاختبار\n";
echo "============================================\n";
echo "\n";

echo $green . "✅ نجح: " . $passed . $reset . "\n";
echo $red . "❌ فشل: " . $failed . $reset . "\n";
echo $yellow . "⏭️  تجاوز: " . $skipped . $reset . "\n";

$total = $passed + $failed + $skipped;
$successRate = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

echo "\n";
echo $blue . "📈 معدل النجاح: " . $successRate . "%" . $reset . "\n";
echo "\n";

if ($failed == 0) {
    echo $green . "🎉 جميع الاختبارات نجحت! الصفحة جاهزة!\n" . $reset;
    echo "\n";
    echo "🚀 الخطوات التالية:\n";
    echo "   1. افتح: http://127.0.0.1:8000/boxes\n";
    echo "   2. اختبر جميع الوظائف يدوياً\n";
    echo "   3. راجع: BOXES_TESTING_GUIDE.md\n";
    echo "\n";
} else {
    echo $red . "⚠️  بعض الاختبارات فشلت. راجع الأخطاء أعلاه.\n" . $reset;
    echo "\n";
}

echo "============================================\n";
echo "\n";

// Open browser automatically (optional)
echo "💡 هل تريد فتح صفحة الاختبار في المتصفح؟\n";
echo "   افتح: http://127.0.0.1:8000/boxes-test.html\n";
echo "\n";

exit($failed > 0 ? 1 : 0);

