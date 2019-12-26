FROM php
RUN docker-php-ext-install pdo_mysql sockets
RUN apt-get update \
    && apt-get install -y libzip-dev unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install zip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
