#!/bin/bash
set -euo pipefail

# Point EB's web root to Laravel's /public
# This is a fallback in case .ebextensions document_root config doesn't work on AL2023
# Primary method: .ebextensions/03-document-root.config sets document_root: /public

if [ -e /var/www/html ]; then
  rm -rf /var/www/html
fi

ln -sfn /var/app/current/public /var/www/html

# Sanity check
test -f /var/www/html/index.php

# Reload nginx
systemctl reload nginx || systemctl restart nginx
