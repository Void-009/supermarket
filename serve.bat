@echo off
title Laravel Dev Server (127.0.0.1:8000)
cd /d "%~dp0"
echo Starting Laravel dev server - keep this window open.
echo Press Ctrl+C to stop.
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
