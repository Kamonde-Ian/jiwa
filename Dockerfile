# ---------- Build stage: composer dependencies ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes

RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# ---------- Build stage: frontend assets ----------
FROM node:22 AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# ---------- Runtime stage ----------
FROM php:8.4-fpm

# System deps: nginx, supervisor, PHP extension libs
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        unzip \
        gettext-base \
        libpq-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_pgsql pgsql \
        gd intl zip bcmath exif pcntl mbstring opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Nginx must be able to write to its temp dirs
RUN mkdir -p /var/lib/nginx /var/log/nginx /run/nginx \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx /run/nginx

# Working dir
WORKDIR /var/www/html

# Copy application
COPY --from=vendor --chown=www-data:www-data /app ./
COPY . .

# Copy built assets + remove any stale build
RUN rm -rf public/build && mkdir -p public/build
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# Nginx + PHP-FPM + supervisord config
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-admin.ini
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Storage + cache dirs, Laravel needs to write here
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Composer + artisan must be executable
RUN chmod +x /var/www/html/artisan

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
