#!/bin/bash
# Fix document root to point to public directory after deployment

# Remove existing symlink if it exists
if [ -L /var/www/html ]; then
    rm -f /var/www/html
fi

# Create symlink to public directory
ln -sf /var/app/current/public /var/www/html

# Ensure nginx can read the directory
chmod -R 755 /var/app/current/public || true

# Restart nginx to pick up changes
systemctl reload nginx || systemctl restart nginx || true
