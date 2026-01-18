<?php
/**
 * Display .env file contents to diagnose the issue
 */

echo "\n========================================\n";
echo "   .env File Content Inspector\n";
echo "========================================\n\n";

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "✗ .env file not found at: $envFile\n";
    exit(1);
}

echo "✓ .env file found\n\n";

$content = file_get_contents($envFile);
$lines = explode("\n", $content);

echo "Showing Database-related lines:\n";
echo "================================\n\n";

$lineNumber = 0;
$dbVars = [];

foreach ($lines as $line) {
    $lineNumber++;
    $trimmedLine = trim($line);
    
    // Check if line contains DB_ or database related keywords
    if (stripos($line, 'DB_') !== false || 
        stripos($line, 'DATABASE') !== false ||
        stripos($line, 'MYSQL') !== false ||
        ($lineNumber >= 10 && $lineNumber <= 25)) {
        
        // Show line number and content
        printf("Line %3d: %s\n", $lineNumber, $line);
        
        // If it's a variable assignment
        if (strpos($trimmedLine, '=') !== false && strpos($trimmedLine, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Check for Arabic characters in key
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $key)) {
                echo "         ⚠️ WARNING: Arabic characters detected in variable name!\n";
            }
            
            // Check for spaces around =
            if (strpos($line, ' = ') !== false || strpos($line, '= ') === strpos($line, '=') || strpos($line, ' =') !== false) {
                echo "         ⚠️ WARNING: Spaces detected around '=' sign\n";
            }
            
            // Store the variable
            if (strpos($key, 'DB_') === 0) {
                $dbVars[$key] = $value;
            }
        }
    }
}

echo "\n";
echo "Extracted Database Variables:\n";
echo "==============================\n";

$requiredVars = ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

foreach ($requiredVars as $var) {
    $value = $dbVars[$var] ?? '(NOT FOUND)';
    
    // Remove quotes if present
    $cleanValue = trim($value, '"\'');
    
    $status = '✗';
    if (isset($dbVars[$var])) {
        if (empty($cleanValue)) {
            $status = '⚠️';
            $displayValue = '(EMPTY)';
        } else {
            $status = '✓';
            // Mask password
            if ($var === 'DB_PASSWORD') {
                $displayValue = str_repeat('*', strlen($cleanValue));
            } else {
                $displayValue = $cleanValue;
            }
        }
    } else {
        $displayValue = '(NOT FOUND)';
    }
    
    printf("%s %-20s = %s\n", $status, $var, $displayValue);
}

echo "\n";
echo "Issues Found:\n";
echo "=============\n";

$issuesFound = false;

foreach ($requiredVars as $var) {
    if (!isset($dbVars[$var])) {
        echo "✗ $var is missing from .env file\n";
        $issuesFound = true;
    } elseif (empty(trim($dbVars[$var], '"\''))) {
        if ($var === 'DB_PASSWORD') {
            echo "⚠️ $var is empty (this might be OK if no password is set)\n";
        } else {
            echo "✗ $var is empty\n";
            $issuesFound = true;
        }
    }
}

// Check for Arabic characters in variable names
foreach ($lines as $line) {
    $trimmedLine = trim($line);
    if (empty($trimmedLine) || strpos($trimmedLine, '#') === 0) {
        continue;
    }
    
    if (strpos($trimmedLine, '=') !== false) {
        list($key, $value) = explode('=', $trimmedLine, 2);
        $key = trim($key);
        
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $key)) {
            echo "✗ Variable name contains Arabic characters: $key\n";
            echo "  This will cause Laravel to fail!\n";
            echo "  Please rename it to English letters only\n";
            $issuesFound = true;
        }
    }
}

if (!$issuesFound) {
    echo "✓ No major issues found!\n";
    echo "\nHowever, the values might still be incorrect.\n";
    echo "Please verify that the database credentials are correct.\n";
}

echo "\n";
echo "Recommended .env format:\n";
echo "========================\n";
echo "DB_CONNECTION=mysql\n";
echo "DB_HOST=127.0.0.1\n";
echo "DB_PORT=3306\n";
echo "DB_DATABASE=your_database_name\n";
echo "DB_USERNAME=your_username\n";
echo "DB_PASSWORD=your_password\n";
echo "\n";
echo "IMPORTANT RULES:\n";
echo "- Variable names MUST be in English only\n";
echo "- NO spaces around the = sign\n";
echo "- Values can be in Arabic (but keys cannot)\n";
echo "- Put quotes around values with spaces: DB_DATABASE=\"my database\"\n";
echo "\n";
