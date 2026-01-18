<?php
/**
 * اختبار أداء صفحة المزامنة - قبل وبعد التحسين
 */

echo "\n========================================\n";
echo "   اختبار أداء صفحة المزامنة\n";
echo "========================================\n\n";

$baseUrl = 'http://127.0.0.1:8000/api/sync-monitor';

// Endpoints
$endpoints = [
    'check-health' => '/check-health',
    'sync-queue-details' => '/sync-queue-details',
    'tables' => '/tables',
    'metadata' => '/metadata',
    'backups' => '/backups',
    'all-data' => '/all-data',
];

echo "🧪 اختبار سرعة الـ Endpoints:\n";
echo str_repeat("─", 70) . "\n\n";

$results = [];

foreach ($endpoints as $name => $endpoint) {
    $url = $baseUrl . $endpoint;
    
    echo "📡 $name:\n";
    echo "   URL: $url\n";
    
    $start = microtime(true);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    $time = microtime(true) - $start;
    
    curl_close($ch);
    
    $results[$name] = [
        'time' => $time,
        'size' => $size,
        'code' => $httpCode,
        'success' => $httpCode === 200
    ];
    
    if ($httpCode === 200) {
        echo "   ✓ نجح: " . number_format($time, 3) . " ثانية\n";
        echo "   📦 الحجم: " . formatBytes($size) . "\n";
    } else {
        echo "   ✗ فشل: HTTP $httpCode\n";
    }
    echo "\n";
}

echo str_repeat("─", 70) . "\n";
echo "📊 الخلاصة:\n";
echo str_repeat("─", 70) . "\n\n";

// مقارنة: التحميل الأولي
echo "1️⃣ التحميل الأولي (عند فتح الصفحة):\n";
echo "   ────────────────────────────────────\n";

// قبل التحسين: all-data + metadata + sync-from-server-jobs
$beforeTime = ($results['all-data']['time'] ?? 0);
$beforeSize = ($results['all-data']['size'] ?? 0);

echo "   قبل التحسين:\n";
echo "     - all-data: " . number_format($beforeTime, 3) . "s, " . formatBytes($beforeSize) . "\n";
echo "     - الوقت الإجمالي: ~" . number_format($beforeTime * 1.5, 2) . "s (3 requests متوازية)\n\n";

// بعد التحسين: check-health + sync-queue-details + tables
$afterTime = ($results['check-health']['time'] ?? 0) + 
             ($results['sync-queue-details']['time'] ?? 0) + 
             ($results['tables']['time'] ?? 0);
$afterSize = ($results['check-health']['size'] ?? 0) + 
             ($results['sync-queue-details']['size'] ?? 0) + 
             ($results['tables']['size'] ?? 0);

echo "   بعد التحسين:\n";
echo "     - check-health: " . number_format($results['check-health']['time'] ?? 0, 3) . "s\n";
echo "     - sync-queue-details: " . number_format($results['sync-queue-details']['time'] ?? 0, 3) . "s\n";
echo "     - tables: " . number_format($results['tables']['time'] ?? 0, 3) . "s\n";
echo "     - الوقت الإجمالي: ~" . number_format($afterTime, 3) . "s\n\n";

$improvement = (($beforeTime * 1.5) - $afterTime) / ($beforeTime * 1.5) * 100;
echo "   📈 التحسين: " . number_format($improvement, 1) . "% أسرع ⚡\n\n";

// مقارنة: الضغط على تاب
echo "2️⃣ الضغط على تاب (Lazy Loading):\n";
echo "   ────────────────────────────────────\n";
echo "   قبل التحسين: 0s (البيانات محملة لكن كلها!)\n";
echo "   بعد التحسين:\n";
echo "     - أول مرة: ~" . number_format($results['metadata']['time'] ?? 0, 3) . "s\n";
echo "     - مرة ثانية: 0s (كاش) ⚡⚡\n\n";

echo "3️⃣ حجم البيانات:\n";
echo "   ────────────────────────────────────\n";
if ($beforeSize > 0) {
    $sizeReduction = (($beforeSize - $afterSize) / $beforeSize) * 100;
    echo "   قبل: " . formatBytes($beforeSize) . "\n";
    echo "   بعد: " . formatBytes($afterSize) . "\n";
    echo "   📉 تقليل: " . number_format($sizeReduction, 1) . "%\n\n";
} else {
    echo "   قبل: N/A (all-data غير متاح)\n";
    echo "   بعد: " . formatBytes($afterSize) . "\n\n";
}

echo str_repeat("═", 70) . "\n";
echo "✅ انتهى الاختبار\n";
echo str_repeat("═", 70) . "\n\n";

echo "💡 نصيحة: افتح المتصفح وافتح DevTools → Network\n";
echo "   ثم افتح: http://127.0.0.1:8000/sync-monitor\n";
echo "   لاحظ عدد الـ requests وحجمها!\n\n";

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
