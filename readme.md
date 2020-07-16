# Heseya Store API

## Project setup
```
composer i
```

Copy `.env.example` to `.env`.

Create application key and run migrations with seeder.
```
php artisan key:generate
php artisan migrate --seed
```

After migration run passport install.
```
php artisan passport:install
```

Seeder creates user `admin@example.com` with password `secret`.

When something not working with cache (like routing).
```
php artisan optimize
```

## Docker
Utwórz środowisko
```
docker-compose up
```

Uruchamianie środowiska nie przypinająć go do konsoli (lub z aplikacji)
```
docker-compose up -d
```

Zatrzymywanie środowiska (lub z aplikacji)
```
docker-compose stop
```

Wejście do kontenera (lub z aplikacji)
```
docker exec -it store-api(lub inna nazwa katalogu projektu)_app_1 bash
```

Skasowanie środowiska
```
docker-compose down -v
```

## Code style
Set your IDE to use the .editorconfig file. There is an extension for this in VS Code.

When writing the code for this project, follow rules from [Laravel best practices](https://github.com/alexeymezenin/laravel-best-practices).

Additional:
- all variables in this project should be `snake_case` and functions (relations too) `camelCase`,
- variables containing links like `avatar_url` should always end with `_url`.


## Docs
Write documentation using [Swagger-PHP](http://zircote.github.io/swagger-php/).

Generating documentation:
```
php artisan l5-swagger:generate
```

The generated documentation is available at `/docs`.

Locally I recommend set `L5_SWAGGER_GENERATE_ALWAYS` option in .env to `true`, then the documentation will be generated with every refresh.


## Release checklist
This project uses [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html)

- [ ] Check if migrations work,
- [ ] Check if all tests pass,
- [ ] Write down all changes to `changelog.md`,
- [ ] Change version in `config/app.php`,
- [ ] Change version in `app/Http/Controllers/Controller.php` (Swagger),
- [ ] Create a version tag on the master branch.
