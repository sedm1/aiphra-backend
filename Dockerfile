ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-alpine

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install pdo_mysql

COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-progress

COPY src ./src
COPY public ./public
