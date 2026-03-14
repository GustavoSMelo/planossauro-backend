#!/bin/sh

until php artisan tinker --execute="DB::connection()->getPdo()"; do
    echo "Waiting for database..."
    sleep 2
done

php artisan migrate
php artisan db:seed

php-fpm -D
nginx -g "daemon off;"
