#!/bin/sh
set -e

if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -z "$(grep '^APP_KEY=base64' .env 2>/dev/null)" ]; then
    php artisan key:generate --no-interaction
fi

php artisan serve --host=0.0.0.0 --port=8000
