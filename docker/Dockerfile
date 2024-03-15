FROM php:8.3-apache-bookworm

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

RUN apt update
RUN apt install -y sudo zip libxml2-dev libkrb5-dev libc-client-dev

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install soap pdo_mysql imap opcache

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ./docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY ./docker/file-upload.ini /usr/local/etc/php/conf.d/file-upload.ini

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

RUN rm -f .env && composer install --no-dev
RUN chown -R www-data:www-data /var/www/html
