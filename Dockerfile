# Getting from docker.hub an image with OS Debian + PHP + FPM already installed
FROM php:8.5-fpm-trixie AS php-deb

# Updating system/repository list and installing nginx, memcached and few dependencies required, also installing the PDO + MySQL driver
RUN apt update && apt upgrade && apt install -y nginx memcached libmemcached-dev libcurl4-openssl-dev libxml2-dev
RUN docker-php-ext-install mysqli pdo_mysql
RUN pecl install memcached && docker-php-ext-enable memcached

# Getting composer official image and installing dependency
FROM composer:latest AS dependency-builder

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader

# Returning from my debian image get the project and move to /app, where my laravel app is working, also configuring nginx and fpm-sock
FROM php-deb
WORKDIR /app

COPY --from=dependency-builder /app /app

RUN cp -f /app/php8.5-fpm.sock /usr/local/etc/php-fpm.d/www.sock
RUN cp -f /app/nginx.conf /etc/nginx/conf.d/
RUN rm -rf /etc/nginx/sites-enabled/*
RUN chmod +x /app/start.sh
RUN chown -R www-data:www-data /app/*

EXPOSE 80

ENTRYPOINT ["/app/start.sh"]
