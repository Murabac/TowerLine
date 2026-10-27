FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app /app
RUN npm run build

FROM dunglas/frankenphp:1-php8.3
WORKDIR /app

RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    bcmath \
    exif \
    pcntl \
    opcache

ENV SERVER_NAME=":80"
ENV PHP_OPCACHE_ENABLE=1

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/towerline-entrypoint
RUN chmod +x /usr/local/bin/towerline-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=45s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1/up') === false ? 1 : 0);"

ENTRYPOINT ["/usr/local/bin/towerline-entrypoint"]
