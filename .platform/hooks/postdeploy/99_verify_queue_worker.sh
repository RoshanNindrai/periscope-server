#!/bin/bash
# Verification script to check Laravel queue worker status
# This runs after deployment to log worker status for debugging

echo "=========================================="
echo "Laravel Queue Worker Status Check"
echo "=========================================="

# Check systemd service status
echo -e "\n[1] Systemd Service Status:"
systemctl is-active laravel-worker.service && echo "✓ Service is ACTIVE" || echo "✗ Service is INACTIVE"
systemctl is-enabled laravel-worker.service && echo "✓ Service is ENABLED" || echo "✗ Service is DISABLED"

# Check if process is running
echo -e "\n[2] Process Status:"
if pgrep -f "artisan queue:work" > /dev/null; then
    echo "✓ Queue worker process is running"
    ps aux | grep "artisan queue:work" | grep -v grep
else
    echo "✗ Queue worker process is NOT running"
fi

# Check working directory and paths
echo -e "\n[3] Path Verification:"
if [ -f "/var/app/current/artisan" ]; then
    echo "✓ /var/app/current/artisan exists"
else
    echo "✗ /var/app/current/artisan NOT FOUND"
fi

# Check storage permissions
echo -e "\n[4] Storage Permissions:"
if [ -d "/var/app/current/storage/logs" ]; then
    echo "✓ Storage logs directory exists"
    ls -la /var/app/current/storage/logs/ | head -n 5
else
    echo "✗ Storage logs directory NOT FOUND"
fi

# Check recent logs
echo -e "\n[5] Recent Queue Worker Logs (last 10 lines):"
if [ -f "/var/app/current/storage/logs/queue-worker.log" ]; then
    tail -n 10 /var/app/current/storage/logs/queue-worker.log
else
    echo "No queue worker log file found"
fi

# Check systemd journal for errors
echo -e "\n[6] Recent Systemd Journal (last 10 lines):"
journalctl -u laravel-worker.service -n 10 --no-pager || echo "Could not read journal"

echo -e "\n=========================================="
echo "Verification Complete"
echo "=========================================="
