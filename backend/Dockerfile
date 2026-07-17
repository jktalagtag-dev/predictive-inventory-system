FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql zip bcmath \
    && rm -rf /var/lib/apt/lists/*

# opcache.enable_cli is off by default, so `php artisan serve` (which runs
# as a CLI script) recompiles the entire framework and app from source on
# every single request. On Windows bind-mounted volumes even opcache's
# per-request mtime stat() check (validate_timestamps=1) is expensive
# enough to dominate request time, so it is disabled here too; combined
# with the container already requiring a restart for --no-reload env
# changes, restarting after code changes is an acceptable, consistent
# trade-off for local development.
RUN { \
    echo "opcache.enable_cli=1"; \
    echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/opcache-cli.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
