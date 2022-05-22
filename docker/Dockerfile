FROM escolasoft/php:8.0-heseya
ADD . /var/www/html
RUN composer update && rm .env
RUN chown -R www-data:www-data /var/www
