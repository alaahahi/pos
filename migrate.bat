@echo off
echo ========================================
echo   Database Migration Script
echo ========================================
echo.
echo [1/4] Running database migrations...
php artisan migrate --force
echo.
echo [2/4] Clearing cache...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo.
echo [3/4] Dumping autoload...
composer dump-autoload
echo.
echo [4/4] Done! Migration completed successfully!
echo ========================================
pause

