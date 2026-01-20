#!/bin/bash
# Optimize Laravel after app is in /var/app/current
set -euo pipefail

echo "Optimizing Laravel in /var/app/current..."

cd /var/app/current

# Clear any cached files from staging
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-*.php
rm -f bootstrap/cache/services.php

# Clear Laravel caches
sudo -u webapp php artisan config:clear || true
sudo -u webapp php artisan cache:clear || true

# Regenerate config cache with correct /var/app/current paths
sudo -u webapp php artisan config:cache || echo 'Config cache failed'

echo "Laravel optimization complete"