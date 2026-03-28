#!/bin/sh

set -e

until php artisan tinker --execute="DB::connection()->getPdo()"; do
    echo "Waiting for database..."
    sleep 2
done

echo "Connected to database"
php artisan config:clear
php artisan cache:clear
php artisan migrate --seed --force
php artisan cache:clear
php artisan view:clear

(while true; do
  php artisan schedule:run --no-interaction
  sleep 60
done) &

php-fpm -D
envsubst '${IP_PASS}' < /app/nginx.conf.template > /etc/nginx/nginx.conf
nginx -g "daemon off;"
