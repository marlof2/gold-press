FROM php:8.4-apache AS runtime
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev libxml2-dev libsqlite3-dev \
    && docker-php-ext-install -j$(nproc) pdo_sqlite mbstring intl zip bcmath dom xml xmlwriter \
    && a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
ENTRYPOINT ["sh", "/usr/local/bin/app-entrypoint"]
CMD ["apache2-foreground"]

FROM runtime AS app
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
