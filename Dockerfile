# Multi-stage Dockerfile for Laravel 12 on Railway (Powered by FrankenPHP)

# Stage 1: Build Frontend Assets (Vite & Tailwind CSS v4)
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm ci
COPY resources/ resources/
COPY public/ public/
RUN npm run build

# Stage 2: Install PHP Dependencies (Composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Stage 3: Production Server (FrankenPHP + PHP 8.3)
FROM dunglas/frankenphp:1-php8.3-bookworm AS runner

# Install required PHP extensions for Laravel & MySQL
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    mbstring \
    opcache \
    intl \
    zip \
    pcntl

# Configure PHP for production
RUN cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini && \
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 32M/' $PHP_INI_DIR/php.ini && \
    sed -i 's/post_max_size = 8M/post_max_size = 32M/' $PHP_INI_DIR/php.ini && \
    sed -i 's/memory_limit = 128M/memory_limit = 256M/' $PHP_INI_DIR/php.ini

WORKDIR /app

# Copy application source code
COPY . .

# Copy built vendor and frontend assets from previous stages
COPY --from=vendor /app/vendor/ /app/vendor/
COPY --from=frontend /app/public/build/ /app/public/build/

# Set correct storage & bootstrap permissions
RUN rm -rf /app/bootstrap/cache/*.php /app/bootstrap/cache/*.tmp /app/public/hot && \
    mkdir -p /app/storage/framework/cache/data \
             /app/storage/framework/sessions \
             /app/storage/framework/views \
             /app/storage/logs && \
    php artisan package:discover --ansi && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Environment defaults
ENV SERVER_NAME=":8000"

# Koyeb Entrypoint Script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
