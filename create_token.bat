@echo off
title POS Sync - Create Agent Token
cd /d "%~dp0"
set PATH=C:\xampp\php;%PATH%

echo Creating a Sanctum token for the POS sync agent.
echo The token is shown once - copy it into pos-sync-agent\config.json -> api.token
echo.

php artisan pos:token:create

echo.
pause
