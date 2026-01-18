@echo off
echo.
echo ===============================================================
echo            Starting Laravel Scheduler (Auto-Sync)
echo ===============================================================
echo.
echo This will start Laravel Scheduler which will:
echo   - Auto-sync every 5 minutes
echo   - Upload data from SQLite to MySQL
echo   - Download updates from MySQL to SQLite
echo.
echo WARNING: Do NOT close this window! Keep it running.
echo.
pause

cd /d "%~dp0"

echo.
echo Starting Scheduler...
echo.

php artisan schedule:work
