up:
	- docker-compose up -d

down:
	- docker-compose down

bash:
	- docker exec -it store-api_app_1 bash

build:
	- cp .env.example .env
	- docker-compose up -d
	- docker exec store-api_app_1 php artisan key:generate

hooks:
	- cp ./git_hooks/docker/* ./.git/hooks/

hooks-remove:
	- rm ./.git/hooks/*
