FROM php:8.0-fpm

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && \
    apt-get install -y zip unzip libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    apt-get -y autoremove && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
