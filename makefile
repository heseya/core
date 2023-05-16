up:
	- docker-compose up -d

down:
	- docker-compose down

bash:
	- docker exec -it store-api_app_1 bash || docker exec -it store-api-app-1 bash

build:
	- cp .env.example .env
	- docker-compose up -d
	- docker exec store-api_app_1 php artisan key:generate || docker exec store-api-app-1 php artisan key:generate

validate-swagger:
	- swagger-cli validate ./public/docs/api.yml

pre-commit:
	- docker exec store-api-app-1 composer pre-commit
