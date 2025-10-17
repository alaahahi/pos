@echo off
echo.
echo ========================================
echo Running Migrations
echo ========================================
echo.
php artisan migrate
echo.
echo ========================================
echo Migration Complete!
echo ========================================
echo.
echo Checking migration status...
php artisan migrate:status
echo.
pause

