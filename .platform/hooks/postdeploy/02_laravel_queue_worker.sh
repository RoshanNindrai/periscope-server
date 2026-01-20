#!/bin/bash
# Setup Laravel Queue Worker as systemd service
# This ensures the worker auto-restarts on crash and survives reboots

set -euo pipefail

echo "Setting up Laravel queue worker systemd service..."

# Create systemd service file
cat > /etc/systemd/system/laravel-worker.service <<'EOF'
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=webapp
Group=webapp
WorkingDirectory=/var/app/current
ExecStart=/usr/bin/php /var/app/current/artisan queue:work sqs --sleep=3 --tries=3 --timeout=90 --max-time=3600
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=3600
StandardOutput=append:/var/app/current/storage/logs/queue-worker.log
StandardError=append:/var/app/current/storage/logs/queue-worker.log

[Install]
WantedBy=multi-user.target
EOF

# Ensure storage directories exist with correct permissions in current
mkdir -p /var/app/current/storage/logs
touch /var/app/current/storage/logs/queue-worker.log
chown -R webapp:webapp /var/app/current/storage
chmod -R 775 /var/app/current/storage

# Reload systemd, enable and restart the service
systemctl daemon-reload
systemctl enable laravel-worker.service
systemctl restart laravel-worker.service

# Wait a moment and verify the service started
sleep 2
if systemctl is-active --quiet laravel-worker.service; then
    echo "SUCCESS: Laravel queue worker is running"
    systemctl status laravel-worker.service --no-pager || true
else
    echo "WARNING: Laravel queue worker failed to start"
    systemctl status laravel-worker.service --no-pager || true
    journalctl -u laravel-worker.service -n 50 --no-pager || true
fi
