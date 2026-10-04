#!/usr/bin/env bash
set -euo pipefail

# ============================================================================
# M-DELIGHT SINGER STORES - Sync Diagnostics
# Run this on the server to find why sync is stuck.
# Usage: bash diagnose-sync.sh
# ============================================================================

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "============================================"
echo "  POS SYNC DIAGNOSTICS"
echo "============================================"
echo ""

echo "--- 1. QUEUE CONNECTION ---"
echo "QUEUE_CONNECTION=$(grep QUEUE_CONNECTION .env 2>/dev/null | cut -d= -f2)"
echo ""

echo "--- 2. JOBS TABLE ---"
php artisan tinker --execute="
\$count = DB::table('jobs')->count();
echo \"Jobs in queue: {\$count}\n\";
" 2>/dev/null || echo "  ERROR: Could not query jobs table"
echo ""

echo "--- 3. PENDING BATCHES ---"
php artisan tinker --execute="
use App\Models\PosSyncBatch;
\$pending = PosSyncBatch::where('status', 'pending')->count();
\$processing = PosSyncBatch::where('status', 'processing')->count();
\$completed = PosSyncBatch::where('status', 'completed')->count();
\$failed = PosSyncBatch::where('status', 'failed')->count();
echo \"Pending: {\$pending}, Processing: {\$processing}, Completed: {\$completed}, Failed: {\$failed}\n\";
if (\$pending > 0) {
    \$oldest = PosSyncBatch::where('status', 'pending')->oldest()->first();
    echo \"Oldest pending batch: #{\$oldest->id} created at {\$oldest->created_at} with {\$oldest->total_items} items\n\";
}
" 2>/dev/null || echo "  ERROR: Could not query pos_sync_batches"
echo ""

echo "--- 4. FAILED BATCHES (last 10) ---"
php artisan tinker --execute="
use App\Models\PosSyncBatch;
\$failed = PosSyncBatch::where('status', 'failed')->latest()->limit(10)->get();
foreach (\$failed as \$b) {
    echo \"  Batch #{\$b->id}: sync_id={\$b->sync_id} items={\$b->total_items} error={\$b->error_message}\n\";
}
if (\$failed->isEmpty()) echo \"  (none)\n\";
" 2>/dev/null || echo "  ERROR: Could not query failed batches"
echo ""

echo "--- 5. AGENT STATUS ---"
php artisan tinker --execute="
use App\Models\PosSyncAgentStatus;
\$agent = PosSyncAgentStatus::where('source', 'pos-agent')->first();
if (\$agent) {
    \$online = \$agent->last_seen_at && \$agent->last_seen_at->gte(now()->subMinutes(2));
    echo \"Last seen: {\$agent->last_seen_at} (\" . (\$online ? 'ONLINE' : 'OFFLINE') . \")\n\";
    echo \"Version: {\$agent->version}\n\";
} else {
    echo \"No agent status found - agent has never connected\n\";
}
" 2>/dev/null || echo "  ERROR: Could not query pos_sync_agent_statuses"
echo ""

echo "--- 6. QUEUE WORKER RUNNING? ---"
WORKER_COUNT=$(ps aux 2>/dev/null | grep "queue:work" | grep -v grep | wc -l || echo "0")
echo "Running queue workers: $WORKER_COUNT"
if [ "$WORKER_COUNT" -eq "0" ]; then
    echo "  >>> WARNING: No queue worker is running! Jobs will never be processed!"
    echo "  >>> Start one with: php artisan queue:work --queue=pos-sync,default --tries=3 --backoff=10,60,300"
fi
echo ""

echo "--- 7. CRON / SCHEDULER ---"
CRON_LINE=$(crontab -l 2>/dev/null | grep "schedule:run" || echo "")
if [ -z "$CRON_LINE" ]; then
    echo "  >>> WARNING: No Laravel scheduler cron job found!"
    echo "  >>> Add: * * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
else
    echo "Scheduler cron: $CRON_LINE"
fi
echo ""

echo "--- 8. CONFIG CACHE ---"
if [ -f bootstrap/cache/config.php ]; then
    echo "Config is CACHED (bootstrap/cache/config.php exists)"
    echo "  Run: php artisan config:clear && php artisan config:cache"
else
    echo "Config is NOT cached (OK)"
fi
echo ""

echo "--- 9. STORAGE PERMISSIONS ---"
if [ -w storage/logs ]; then
    echo "storage/logs: writable (OK)"
else
    echo "  >>> WARNING: storage/logs is NOT writable!"
fi
if [ -w storage/framework/cache ]; then
    echo "storage/framework/cache: writable (OK)"
else
    echo "  >>> WARNING: storage/framework/cache is NOT writable!"
fi
echo ""

echo "--- 10. ENV FILE ---"
grep -E "^(QUEUE_CONNECTION|CACHE_STORE|SESSION_DRIVER|BROADCAST_CONNECTION|REDIS_HOST|REDIS_PORT|REDIS_PASSWORD|REDIS_CLIENT)" .env 2>/dev/null || echo "  Could not read .env"
echo ""

echo "--- 11. EXCEL SYNC FILE ---"
EXPORT_PATH=$(grep "^POS_EXPORT_PATH" .env 2>/dev/null | cut -d= -f2 | tr -d '"' || echo "")
if [ -n "$EXPORT_PATH" ]; then
    # Check relative to app root
    if [ -f "$EXPORT_PATH" ]; then
        echo "Export file exists: $EXPORT_PATH (OK)"
    else
        FULL_PATH="$APP_DIR/$EXPORT_PATH"
        if [ -f "$FULL_PATH" ]; then
            echo "Export file exists: $FULL_PATH (OK)"
        else
            echo "  >>> WARNING: Export file NOT found at $EXPORT_PATH"
        fi
    fi
else
    echo "POS_EXPORT_PATH not set in .env"
fi
echo ""

echo "============================================"
echo "  DIAGNOSTICS COMPLETE"
echo "============================================"
