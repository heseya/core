DOMAIN1=$(echo $2 | sed -e 's/\//-/g')
NAME=$(echo $DOMAIN1 | sed -e 's/\./-/g')
DOMAIN=$(echo $NAME | tr '[:upper:]' '[:lower:]').***REMOVED***
devil www add $DOMAIN
devil www options $DOMAIN php_openbasedir /usr/home/heseya-dev/domains/$DOMAIN
devil mysql db add $NAME --collate=utf8_unicode_ci
devil mysql privileges dev $NAME +ALL
devil ssl www add 128.204.216.222 le le $DOMAIN
devil www options $DOMAIN sslonly on
cd ./domains/$DOMAIN
rm -r public_html
rm -r logs
git pull || git clone $3 .
git checkout $2
ln -s public public_html
cp .env.deploy .env
echo "DB_DATABASE=m1457_$NAME" >> .env
/home/heseya-dev/composer/composer i --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-reqs
/home/heseya-dev/bin/php artisan cache:clear
/home/heseya-dev/bin/php artisan key:generate
/home/heseya-dev/bin/php artisan apps:remove --force
/home/heseya-dev/bin/php artisan migrate:fresh --seed
/home/heseya-dev/bin/php artisan jwt:secret --always-no
/home/heseya-dev/bin/php artisan optimize:clear
