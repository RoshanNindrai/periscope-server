#!/bin/bash
# Fix document root to point to public directory after deployment

# Log to a file for debugging
LOG_FILE="/var/app/current/storage/logs/hook.log"
mkdir -p /var/app/current/storage/logs
echo "$(date): Starting document root fix" >> "$LOG_FILE"

# Remove existing symlink if it exists
if [ -L /var/www/html ]; then
    echo "$(date): Removing existing symlink" >> "$LOG_FILE"
    rm -f /var/www/html
fi

# Verify public directory exists
if [ ! -d /var/app/current/public ]; then
    echo "$(date): ERROR - /var/app/current/public does not exist" >> "$LOG_FILE"
    exit 1
fi

# Create symlink to public directory
echo "$(date): Creating symlink to /var/app/current/public" >> "$LOG_FILE"
ln -sf /var/app/current/public /var/www/html

# Verify symlink was created
if [ -L /var/www/html ]; then
    TARGET=$(readlink -f /var/www/html)
    echo "$(date): Symlink created successfully, points to: $TARGET" >> "$LOG_FILE"
    ls -la /var/www/html >> "$LOG_FILE"
else
    echo "$(date): ERROR - Failed to create symlink" >> "$LOG_FILE"
fi

# Ensure nginx can read the directory
chmod -R 755 /var/app/current/public || true

# Verify index.php exists
if [ -f /var/app/current/public/index.php ]; then
    echo "$(date): index.php found in public directory" >> "$LOG_FILE"
else
    echo "$(date): WARNING - index.php not found in public directory" >> "$LOG_FILE"
fi

# Restart nginx to pick up changes
echo "$(date): Reloading nginx" >> "$LOG_FILE"
systemctl reload nginx || systemctl restart nginx || true

echo "$(date): Document root fix completed" >> "$LOG_FILE"
