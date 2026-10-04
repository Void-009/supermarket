#!/usr/bin/env bash
set -euo pipefail

# ============================================================================
# M-DELIGHT SINGER STORES - Production Queue Worker Setup
# Installs a systemd service for the pos-sync queue worker.
# Run as root or with sudo.
# ============================================================================

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_USER="${SUDO_USER:-$(whoami)}"
PHP_BIN="$(command -v php)"
WORKER_CMD="${PHP_BIN} artisan queue:work --queue=pos-sync,default --tries=3 --backoff=10,60,300 --sleep=3"

if [ -z "$PHP_BIN" ]; then
    echo "ERROR: php binary not found in PATH."
    exit 1
fi

SERVICE_FILE="/etc/systemd/system/pos-sync-worker.service"

echo "Creating systemd service at ${SERVICE_FILE} ..."

cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=M-Delight POS Sync Queue Worker
After=network.target mysql.service redis.service

[Service]
Type=simple
User=${APP_USER}
WorkingDirectory=${APP_DIR}
ExecStart=${WORKER_CMD}
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable pos-sync-worker.service
systemctl start pos-sync-worker.service

echo ""
echo "Service installed and started."
echo "  Status:   systemctl status pos-sync-worker"
echo "  Logs:     journalctl -u pos-sync-worker -f"
echo "  Restart:  systemctl restart pos-sync-worker"
echo "  Stop:     systemctl stop pos-sync-worker"
