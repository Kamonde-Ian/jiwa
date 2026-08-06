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
        pdo_pgsql pgsql \
        gd intl zip bcmath exif pcntl mbstring opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer binary (deps are installed below, inside this PHP build)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Nginx must be able to write to its temp dirs
RUN mkdir -p /var/lib/nginx /var/log/nginx /run/nginx \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx /run/nginx

# Working dir
WORKDIR /var/www/html

# Copy application source (from the assets stage to reuse the same context)
COPY --from=assets /app ./

# Install PHP dependencies inside this image so all required PHP extensions
# (mbstring, gd, ...) are present for the platform checks
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize --classmap-authoritative --no-dev || true

# Copy built assets + remove any stale build
RUN rm -rf public/build && mkdir -p public/build
COPY --from=assets /app/public/build ./public/build

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

# Artisan must be executable
RUN chmod +x /var/www/html/artisan

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
