# Getting from docker.hub an image with OS Debian + PHP + FPM already installed
FROM php:8.5-fpm-trixie AS php-deb

# Updating system/repository list and installing nginx, memcached and few dependencies required, also installing the PDO + MySQL driver
RUN apt update && apt install -y nginx memcached libmemcached-dev libcurl4-openssl-dev libxml2-dev default-mysql-client && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install mysqli pdo_mysql
RUN pecl install memcached && docker-php-ext-enable memcached

# Configuring PHP-FPM and OPcache
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "error_log=/var/log/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "display_errors=Off" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "upload_max_filesize=128M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size=128M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit=128M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "display_startup_errors=Off" >> /usr/local/etc/php/conf.d/docker-php.ini

# Getting composer official image and installing dependency
FROM composer:2.9.5 AS dependency-builder

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-cache

# Returning from my debian image get the project and move to /app, where my laravel app is working, also configuring nginx and fpm-sock
FROM php-deb
WORKDIR /app

COPY --from=dependency-builder /app /app

RUN mkdir -p /var/run/php && chown -R www-data:www-data /var/run/php
RUN cp -f /app/php8.5-fpm.sock /usr/local/etc/php-fpm.d/www.conf
RUN cp -f /app/homolog.nginx.conf /etc/nginx/conf.d/
RUN mv /etc/nginx/conf.d/homolog.nginx.conf /etc/nginx/conf.d/nginx.conf
RUN rm -rf /etc/nginx/sites-enabled/*
RUN chown -R www-data:www-data /app/*
RUN mkdir -p /var/lib/nginx/body /var/lib/nginx/proxy /var/lib/nginx/fastcgi /var/log/nginx /var/run/nginx && \
    chown -R www-data:www-data /var/lib/nginx /var/log/nginx /var/run/nginx /etc/nginx
RUN sed -i 's|/run/nginx.pid|/var/run/nginx/nginx.pid|g' /etc/nginx/nginx.conf
RUN mkdir -p /var/www/.config/psysh && chown -R www-data:www-data /var/www
RUN chmod +x /app/start.sh

USER www-data
EXPOSE 8081
ENTRYPOINT ["/app/start.sh"]
