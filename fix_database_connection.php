<?php
/**
 * Fix database connection to use MySQL in web interface
 */

echo "\n========================================\n";
echo "   Fix Database Connection Issue\n";
echo "========================================\n\n";

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "✗ .env file not found!\n";
    exit(1);
}

echo "Step 1: Reading current .env settings\n";
echo "======================================\n";

$content = file_get_contents($envFile);
$lines = explode("\n", $content);

$config = [];
foreach ($lines as $line) {
    $trimmedLine = trim($line);
    if (empty($trimmedLine) || strpos($trimmedLine, '#') === 0) {
        continue;
    }
    
    if (strpos($trimmedLine, '=') !== false) {
        list($key, $value) = explode('=', $trimmedLine, 2);
        $config[trim($key)] = trim($value, '"\'');
    }
}

// Display current settings
$importantKeys = ['APP_ENV', 'APP_URL', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'];
foreach ($importantKeys as $key) {
    $value = $config[$key] ?? '(not set)';
    if ($key === 'DB_PASSWORD') {
        $value = str_repeat('*', strlen($value));
    }
    echo "$key = $value\n";
}

echo "\n";
echo "Step 2: Identifying the issue\n";
echo "==============================\n";

$needsFix = false;
$issues = [];

// Check APP_ENV
if (!isset($config['APP_ENV']) || $config['APP_ENV'] === 'local') {
    $issues[] = "APP_ENV is 'local' - this causes Laravel to use SQLite";
    $needsFix = true;
}

// Check APP_URL
if (isset($config['APP_URL'])) {
    $appUrl = $config['APP_URL'];
    if (strpos($appUrl, 'localhost') !== false || strpos($appUrl, '127.0.0.1') !== false) {
        $issues[] = "APP_URL contains localhost - this causes Laravel to use SQLite";
        $needsFix = true;
    }
}

if (count($issues) > 0) {
    echo "⚠️ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
} else {
    echo "✓ No issues found\n";
}

echo "\n";
echo "Step 3: Applying fix\n";
echo "====================\n";

if ($needsFix) {
    $newLines = [];
    $appEnvFixed = false;
    $appUrlFixed = false;
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Fix APP_ENV
        if (strpos($trimmedLine, 'APP_ENV=') === 0) {
            $newLines[] = 'APP_ENV=production';
            echo "✓ Changed APP_ENV to 'production'\n";
            $appEnvFixed = true;
        }
        // Fix APP_URL
        elseif (strpos($trimmedLine, 'APP_URL=') === 0) {
            // Extract current URL
            $currentUrl = str_replace('APP_URL=', '', $trimmedLine);
            $currentUrl = trim($currentUrl, '"\'');
            
            // If it's localhost, change to a dummy production URL
            if (strpos($currentUrl, 'localhost') !== false || strpos($currentUrl, '127.0.0.1') !== false) {
                $newLines[] = 'APP_URL=http://app.production.local';
                echo "✓ Changed APP_URL to 'http://app.production.local'\n";
                $appUrlFixed = true;
            } else {
                $newLines[] = $line;
            }
        }
        else {
            $newLines[] = $line;
        }
    }
    
    // Add APP_ENV if not found
    if (!$appEnvFixed && !isset($config['APP_ENV'])) {
        echo "✓ Adding APP_ENV=production\n";
        // Find the best place to insert (after APP_NAME or at the beginning)
        $insertIndex = 0;
        foreach ($newLines as $i => $line) {
            if (strpos($line, 'APP_NAME=') === 0) {
                $insertIndex = $i + 1;
                break;
            }
        }
        array_splice($newLines, $insertIndex, 0, ['APP_ENV=production']);
    }
    
    // Backup original file
    $backupFile = $envFile . '.backup.' . date('Y-m-d_H-i-s');
    copy($envFile, $backupFile);
    echo "✓ Backup created: " . basename($backupFile) . "\n";
    
    // Write updated content
    file_put_contents($envFile, implode("\n", $newLines));
    echo "✓ .env file updated\n";
    
    echo "\n";
    echo "Step 4: Clear Laravel cache\n";
    echo "============================\n";
    
    // Clear cache using PHP
    echo "Running cache clear commands...\n";
    
    $commands = [
        'php artisan config:clear',
        'php artisan cache:clear',
        'php artisan view:clear',
        'php artisan route:clear'
    ];
    
    foreach ($commands as $cmd) {
        echo "  Running: $cmd\n";
        exec($cmd . ' 2>&1', $output, $return);
        if ($return === 0) {
            echo "    ✓ Success\n";
        } else {
            echo "    ⚠️ " . implode("\n    ", $output) . "\n";
        }
        $output = [];
    }
    
    echo "\n";
    echo "========================================\n";
    echo "   Fix Applied Successfully!\n";
    echo "========================================\n\n";
    
    echo "What changed:\n";
    echo "- APP_ENV set to 'production'\n";
    if ($appUrlFixed) {
        echo "- APP_URL changed to non-localhost\n";
    }
    echo "- Laravel cache cleared\n";
    echo "\n";
    echo "Now Laravel will use MySQL by default!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Refresh your browser\n";
    echo "2. Test the application\n";
    echo "3. Check /sync-monitor page\n";
    
} else {
    echo "✓ Configuration is already correct\n";
    echo "\nBut let's clear the cache anyway...\n\n";
    
    $commands = [
        'php artisan config:clear',
        'php artisan cache:clear',
        'php artisan view:clear'
    ];
    
    foreach ($commands as $cmd) {
        echo "Running: $cmd\n";
        exec($cmd . ' 2>&1', $output, $return);
        $output = [];
    }
    
    echo "\n✓ Cache cleared\n";
}

echo "\n";
