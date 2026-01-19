#!/bin/bash
# Start queue worker after deployment

cd /var/app/current

# Stop existing queue workers
pkill -f "queue:work sqs" || true

# Start new queue worker
nohup php artisan queue:work sqs --sleep=3 --tries=3 --max-time=3600 > /dev/null 2>&1 &

echo "Queue worker started"
