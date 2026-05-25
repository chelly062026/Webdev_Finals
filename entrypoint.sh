#!/bin/bash
set -e

# Map MYSQL_URL to DATABASE_URL if DATABASE_URL is not set (common on Railway)
if [ -z "$DATABASE_URL" ] && [ -n "$MYSQL_URL" ]; then
    export DATABASE_URL="$MYSQL_URL"
    echo "Exported DATABASE_URL from MYSQL_URL"
fi

echo "Starting PHP-FPM..."
php-fpm -F &
PHP_PID=$!

echo "Waiting for PHP-FPM to start..."
sleep 2

# Ensure JWT keys exist
if [ ! -f config/jwt/private.pem ]; then
    echo "Generating JWT keys..."
    mkdir -p config/jwt
    php bin/console lexik:jwt:generate-keypair --no-interaction || echo "Warning: Failed to generate JWT keys"
fi

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || echo "Migration failed or already applied"

echo "Loading fixtures..."
# Only load fixtures if not in production or if specifically requested
# For this project, we'll keep it but ensure it doesn't break deployment
php bin/console doctrine:fixtures:load --append --no-interaction || echo "Fixtures loading failed (possibly due to existing data)"

echo "Starting Nginx..."
nginx -g "daemon off;"

wait $PHP_PID