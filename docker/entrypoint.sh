#!/bin/sh
set -eu
if [ "${1:-}" = 'apache2-foreground' ]; then
    mkdir -p /var/www/data storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
    touch /var/www/data/database.sqlite
    if [ ! -s storage/app.key ]; then
        php -r 'file_put_contents("storage/app.key", "base64:".base64_encode(random_bytes(32)));'
    fi
    export APP_KEY="${APP_KEY:-$(cat storage/app.key)}"
    chown -R www-data:www-data /var/www/data storage bootstrap/cache
    php artisan migrate --force
    php artisan db:seed --force
    php artisan storage:link --force
fi
exec "$@"
