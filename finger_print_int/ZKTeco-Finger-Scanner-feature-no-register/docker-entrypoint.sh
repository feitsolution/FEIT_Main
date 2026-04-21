#!/bin/bash

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! php artisan migrate:status >/dev/null 2>&1; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "MySQL is up - executing migrations"
php artisan migrate --force

echo "Optimizing application"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting Apache"
apache2-foreground
