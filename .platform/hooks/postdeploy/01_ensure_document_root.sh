#!/bin/bash
# Ensure /var/www/html points to app root (not /public)
# EB's document_root: /public config expects /var/www/html/public to exist

# Remove strict error handling to prevent hook from failing deployment
set +e

echo "Postdeploy: Ensuring /var/www/html symlink is correct..."

# Wait a moment for EB's symlink creation to complete
sleep 2

# Check if symlink exists and is correct
if [ -L /var/www/html ]; then
  current_target=$(readlink -f /var/www/html 2>/dev/null)
  expected_target="/var/app/current"
  
  if [ "$current_target" != "$expected_target" ]; then
    echo "Fixing /var/www/html symlink: $current_target -> $expected_target"
    rm -f /var/www/html
    ln -sfn "$expected_target" /var/www/html
  else
    echo "Symlink is correct: /var/www/html -> $current_target"
  fi
elif [ ! -e /var/www/html ]; then
  echo "Creating /var/www/html symlink to app root"
  ln -sfn /var/app/current /var/www/html
else
  echo "WARNING: /var/www/html exists but is not a symlink"
  ls -la /var/www/html
fi

# Verify Laravel files
if [ -f /var/www/html/public/index.php ]; then
  echo "SUCCESS: /var/www/html/public/index.php exists"
else
  echo "WARNING: /var/www/html/public/index.php does not exist"
  echo "Checking /var/app/current/public/index.php:"
  ls -la /var/app/current/public/index.php 2>/dev/null || echo "NOT FOUND"
  echo "Current /var/www/html: $(readlink -f /var/www/html 2>/dev/null || echo 'not a symlink')"
fi

# Reload nginx to pick up changes
systemctl reload nginx 2>/dev/null || echo "Could not reload nginx"
