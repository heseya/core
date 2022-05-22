FROM escolasoft/php:8.0-heseya
ADD . /var/www/html
RUN composer i --no-dev --no-interaction --prefer-dist && rm .env
RUN chown -R www-data:www-data /var/www
