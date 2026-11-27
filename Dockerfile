# syntax=docker/dockerfile:1

# Frontend assets
FROM node:24.21.0-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY vite.config.js tsconfig.json tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

# PHP dependencies
FROM composer:2.9 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts

# Runtime
FROM dunglas/frankenphp:1.13.6-php8.4-alpine

RUN install-php-extensions pdo_mysql redis intl bcmath pcntl opcache zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /app

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

# TLS is terminated by the host's nginx (Forge), Caddy only serves plain HTTP
ENV SERVER_NAME=:8080

RUN rm -rf tests node_modules .env* \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && adduser -D -u 1000 app \
    && chown -R app:app storage bootstrap/cache /data/caddy /config/caddy

USER app

EXPOSE 8080

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
