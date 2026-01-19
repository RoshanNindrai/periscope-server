#!/bin/bash
set -euo pipefail

# EB expects /var/www/html/public to exist.
# So /var/www/html must point to the Laravel project root.
# EB's nginx/php config appends /public internally.

if [ -e /var/www/html ]; then
  rm -rf /var/www/html
fi

ln -sfn /var/app/current /var/www/html

# Sanity check - EB looks for /var/www/html/public/index.php
test -f /var/www/html/public/index.php

# Reload nginx
systemctl reload nginx || systemctl restart nginx
