@echo off
echo.
echo ========================================
echo Running Migration for Supplier Balances
echo ========================================
echo.
php artisan migrate
echo.
echo ========================================
echo Migration Complete!
echo ========================================
echo.
pause

