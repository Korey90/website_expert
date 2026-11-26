#!/bin/bash
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --optimize-autoloader
fi

# Read an existing .env key first: rotating it would break encrypted data.
if [ -z "${APP_KEY:-}" ]; then
    file_key="$(sed -n 's/^APP_KEY=//p' .env | head -n 1 | tr -d '\r')"

    if [ -z "$file_key" ]; then
        if [ "${APP_ENV:-local}" = "production" ]; then
            echo "APP_KEY is missing; refusing to create a key in production." >&2
            exit 1
        fi
        php artisan key:generate --force
        file_key="$(sed -n 's/^APP_KEY=//p' .env | head -n 1 | tr -d '\r')"
    fi

    if [ -z "$file_key" ]; then
        echo "APP_KEY could not be read from .env." >&2
        exit 1
    fi

    export APP_KEY="$file_key"
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
