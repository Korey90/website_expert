#!/bin/bash
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --optimize-autoloader
fi

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        sleep 1
    done
fi

php artisan migrate --force

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
