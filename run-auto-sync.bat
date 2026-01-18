@echo off
title Auto-Sync Scheduler
color 0A
cls

echo ===============================================================
echo    Laravel Scheduler - Auto Sync Every 5 Minutes
echo ===============================================================
echo.
echo Status: STARTING...
echo.
echo Keep this window OPEN to enable auto-sync!
echo Close this window to STOP auto-sync.
echo.
echo ===============================================================
echo.

cd /d "%~dp0"
php artisan schedule:work

pause
