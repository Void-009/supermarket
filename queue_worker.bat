@echo off
title POS Sync Queue Worker (pos-sync)
cd /d "%~dp0"
set PATH=C:\xampp\php;%PATH%

echo ============================================================
echo   POS Sync Queue Worker - processes pos-sync batches
echo   Keep this window open while the agent is running.
echo   Press Ctrl+C to stop.
echo ============================================================
echo.

php artisan queue:work --queue=pos-sync,default --tries=3 --backoff=10,60,300

echo.
echo Worker stopped.
pause
