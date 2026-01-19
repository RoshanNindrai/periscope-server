#!/bin/bash
set -euo pipefail

# Ensure /var/www/html points to app root (not /public)
# EB's document_root: /public config expects /var/www/html/public to exist
# So /var/www/html must point to /var/app/current (app root)

if [ -L /var/www/html ]; then
  current_target=$(readlink -f /var/www/html)
  expected_target="/var/app/current"
  
  if [ "$current_target" != "$expected_target" ]; then
    echo "Fixing /var/www/html symlink: $current_target -> $expected_target"
    rm -f /var/www/html
    ln -sfn "$expected_target" /var/www/html
  fi
elif [ ! -e /var/www/html ]; then
  echo "Creating /var/www/html symlink to app root"
  ln -sfn /var/app/current /var/www/html
fi

# Verify /var/www/html/public/index.php exists
if [ ! -f /var/www/html/public/index.php ]; then
  echo "ERROR: /var/www/html/public/index.php does not exist!"
  echo "Current /var/www/html target: $(readlink -f /var/www/html 2>/dev/null || echo 'not a symlink')"
  exit 1
fi

echo "Document root verified: /var/www/html -> $(readlink -f /var/www/html)"
echo "Laravel index.php exists at: /var/www/html/public/index.php"
