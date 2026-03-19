#!/bin/sh

until php artisan tinker --execute="DB::connection()->getPdo()"; do
    echo "Waiting for database..."
    sleep 2
done

echo "Connected to database"
php artisan config:clear
php artisan cache:clear
php artisan migrate --seed

php-fpm -D
nginx -g "daemon off;"
