FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
    libc-client-dev \
    libkrb5-dev \
    libpng-dev \
    libjpeg-dev \
    zlib1g-dev \
    libzip-dev \
    zip \
    unzip

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install imap

RUN docker-php-ext-install pdo_mysql

RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install gd

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /usr/src/app/public

WORKDIR /usr/src/app

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
