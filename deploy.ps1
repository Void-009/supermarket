# ============================================================================
# M-DELIGHT SINGER STORES - Production Deployment Script (Windows)
# Usage: .\deploy.ps1
# Run from the project root directory.
# ============================================================================

$ErrorActionPreference = "Stop"
$AppDir = $PSScriptRoot
Set-Location $AppDir

Write-Host "==> [1/9] Setting production environment" -ForegroundColor Cyan
if (-not (Test-Path ".env")) {
    Write-Error "ERROR: .env not found. Create it from .env.example first."
    exit 1
}

Write-Host "==> [2/9] Installing Composer dependencies (no dev)" -ForegroundColor Cyan
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

Write-Host "==> [3/9] Installing NPM dependencies" -ForegroundColor Cyan
if (Test-Path "package-lock.json") {
    npm ci --no-audit --no-fund
} else {
    npm install --no-audit --no-fund
}

Write-Host "==> [4/9] Building frontend assets" -ForegroundColor Cyan
npm run build

Write-Host "==> [5/9] Creating storage link" -ForegroundColor Cyan
# "php artisan storage:link" creates a symlink, which on Windows needs admin
# rights. A Windows "junction" works without admin and is identical for
# reading/writing, so fall back to it whenever the symlink is not available.
if (Test-Path "public/storage") {
    Write-Host "public/storage already exists (link or folder) - leaving as is" -ForegroundColor Yellow
} else {
    php artisan storage:link 2>$null
    if (-not (Test-Path "public/storage")) {
        cmd /c mklink /J "public\storage" "storage\app\public" | Out-Null
    }
    if (-not (Test-Path "public/storage")) {
        Write-Error "ERROR: could not create storage link. Copy storage/app/public into public/storage manually."
        exit 1
    }
}

Write-Host "==> [6/9] Running database migrations" -ForegroundColor Cyan
php artisan migrate --force

Write-Host "==> [7/9] Caching configuration" -ForegroundColor Cyan
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

Write-Host "==> [8/9] Restarting queue worker" -ForegroundColor Cyan
php artisan queue:restart 2>$null

Write-Host "==> [9/9] Clearing stale caches" -ForegroundColor Cyan
php artisan cache:clear 2>$null

Write-Host ""
Write-Host "Deployment complete." -ForegroundColor Green
Write-Host "Remember to start/restart the queue worker:  php artisan queue:work --sleep=3 --tries=3"
Write-Host "Remember to start/restart Reverb:            php artisan reverb:start --host=0.0.0.0 --port=8080"
