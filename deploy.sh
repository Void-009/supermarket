#!/usr/bin/env bash
set -euo pipefail

# ============================================================================
# M-DELIGHT SINGER STORES - Production Deployment Script
# Usage: ./deploy.sh
# Run as a user with write access to the application directory.
# ============================================================================

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "==> [1/9] Setting production environment"
if [ ! -f .env ]; then
    echo "ERROR: .env not found. Create it from .env.example first."
    exit 1
fi

echo "==> [2/9] Installing Composer dependencies (no dev)"
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "==> [3/9] Installing NPM dependencies"
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi

echo "==> [4/9] Building frontend assets"
npm run build

echo "==> [5/9] Creating storage symlink"
php artisan storage:link

echo "==> [6/9] Running database migrations"
php artisan migrate --force

echo "==> [7/9] Caching configuration"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> [8/9] Restarting queue worker"
php artisan queue:restart || true

echo "==> [9/9] Clearing stale caches"
php artisan cache:clear || true

echo ""
echo "Deployment complete."
echo "Remember to start/restart the queue worker:  php artisan queue:work --sleep=3 --tries=3"
echo "Remember to start/restart Reverb:            php artisan reverb:start --host=0.0.0.0 --port=8080"
