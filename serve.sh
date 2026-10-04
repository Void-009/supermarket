#!/usr/bin/env bash
set -euo pipefail

# Laravel Dev Server (127.0.0.1:8000)
# Keep this terminal open.
# Press Ctrl+C to stop.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Starting Laravel dev server - keep this window open."
echo "Press Ctrl+C to stop."
php artisan serve --host=127.0.0.1 --port=8000
