#!/bin/bash
# Optimize Laravel after app is in /var/app/current
set -euo pipefail

cd /var/app/current

# Storage and bootstrap/cache must be writable by webapp (and by artisan when run via sudo -u webapp)
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/app/public
chown -R webapp:webapp storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
touch storage/logs/laravel.log
chown webapp:webapp storage/logs/laravel.log

rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/services.php
sudo -u webapp php artisan config:clear || true
sudo -u webapp php artisan cache:clear || true
sudo -u webapp php artisan config:cache || echo 'Config cache failed'