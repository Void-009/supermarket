#!/usr/bin/env bash
set -euo pipefail

# POS Sync - Create Agent Token
# The token is shown once - copy it into pos-sync-agent\config.json -> api.token

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Creating a Sanctum token for the POS sync agent."
echo "The token is shown once - copy it into pos-sync-agent/config.json -> api.token"
echo ""

php artisan pos:token:create
