#!/bin/sh
set -e

# Support Koyeb's dynamic $PORT environment variable
export PORT="${PORT:-8000}"
export SERVER_NAME=":${PORT}"

echo "==> Starting Kafeina ERP on port ${PORT}..."

# Cache configuration if in production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Optimizing Laravel cache for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Ensure storage link exists
php artisan storage:link --force || true

# Run database migration if requested or if DB is connected
if [ -n "$DB_HOST" ] && [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "==> Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    for i in $(seq 1 15); do
        if php -r "try { new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:3306), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch(Exception \$e) { exit(1); }"; then
            echo "==> Database is reachable!"
            if [ "$FRESH_DATABASE" = "true" ]; then
                echo "==> FRESH_DATABASE requested! Running migrate:fresh --seed..."
                php artisan migrate:fresh --seed --force || true
            else
                echo "==> Running standard migrations & seeders..."
                php artisan migrate --force || true
                php artisan db:seed --force || true
            fi
            break
        fi
        echo "==> Database not ready yet, retrying in 2s ($i/15)..."
        sleep 2
    done
fi

echo "==> Launching FrankenPHP Web Server..."
exec frankenphp php-server --root public/ --listen 0.0.0.0:${PORT}
