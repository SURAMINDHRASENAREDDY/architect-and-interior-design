FROM composer:2 AS composer

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        intl \
        mbstring \
        pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

COPY . .

COPY backend/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf

EXPOSE 10000

CMD ["apache2-foreground"]
