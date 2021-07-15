up:
	- docker-compose up -d
	- echo "\033[0;32mProject started!"

bash:
	- docker exec -it store-api_app_1 bash

build:
	- cp .env.example .env
	- docker-compose up -d
	- docker exec store-api_app_1 php artisan key:generate
	- docker exec store-api_app_1 php artisan migrate:fresh --seed
	- echo "\033[0;32mProject ready, make somthing awesome ;)"

hooks:
	- cp ./git_hooks/docker/* ./.git/hooks/

hooks-remove:
	- rm -R ./.git/hooks/
	- mkdir ./.git/hooks/
