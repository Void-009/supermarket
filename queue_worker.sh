#!/usr/bin/env bash
set -euo pipefail

# POS Sync Queue Worker - processes pos-sync batches
# Keep this terminal open while the agent is running.
# Press Ctrl+C to stop.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "============================================================"
echo "  POS Sync Queue Worker - processes pos-sync batches"
echo "  Keep this terminal open while the agent is running."
echo "  Press Ctrl+C to stop."
echo "============================================================"
echo ""

php artisan queue:work --queue=pos-sync,default --tries=3 --backoff=10,60,300

echo ""
echo "Worker stopped."
