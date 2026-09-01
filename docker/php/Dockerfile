FROM php:8.4-fpm-bookworm AS runtime

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl git libpq-dev libzip-dev unzip autoconf g++ make pkg-config \
    && docker-php-ext-install pdo_pgsql pcntl zip \
    && yes '' | pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove autoconf g++ make pkg-config \
    && groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN COMPOSER_HOME=/tmp/composer composer install --no-dev --no-interaction --no-progress --no-scripts
COPY --chown=www-data:www-data . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && composer dump-autoload --no-dev --no-interaction -o --no-scripts \
    && su www-data -s /bin/sh -c 'php artisan package:discover --ansi'
USER www-data
CMD ["php-fpm"]
