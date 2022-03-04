NAME=$(echo $2 | sed -e 's/\//_/g')
DOMAIN=$(echo $2 | sed -e 's/\//-/g').***REMOVED***
devil www add $DOMAIN
devil www options $DOMAIN php_openbasedir /usr/home/heseya-dev/domains/$DOMAIN
devil mysql db add $NAME --collate=utf8_unicode_ci
devil mysql privileges dev $NAME +ALL
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
/home/heseya-dev/bin/php artisan migrate:fresh --seed
/home/heseya-dev/bin/php artisan jwt:secret --always-no
/home/heseya-dev/bin/php artisan optimize:clear
