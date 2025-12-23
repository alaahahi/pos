@echo off
echo ========================================
echo   تشغيل Queue Worker للمزامنة
echo ========================================
echo.

cd /d "%~dp0"

echo جاري تشغيل Queue Worker...
echo.
echo ملاحظة: هذا الأمر سيعمل حتى تقوم بإيقافه (Ctrl+C)
echo.

php artisan queue:work database --queue=sync --sleep=3 --tries=3 --timeout=600

pause

