#!/bin/sh

set -e

cat /.env | grep -v '^#' | grep -v '^$' | while IFS='=' read -r key value; do
    export "$key"="$value"
done

until php artisan tinker --execute="DB::connection()->getPdo()"; do
    echo "Waiting for database..."
    sleep 2
done

echo "Connected to database"
php artisan config:clear
php artisan cache:clear
php artisan migrate --seed

(while true; do
  php artisan schedule:run --no-interaction
  sleep 60
done) &

php-fpm -D
envsubst '${IP_PASS}' < /app/nginx.conf.template > /etc/nginx/nginx.conf
nginx -g "daemon off;"
