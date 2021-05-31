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
Create an environment
```
docker-compose up
```

Do not clip the environment to the console (or from the application)
```
docker-compose up -d
```

Stopping the environment (or from applications)
```
docker-compose stop
```

Entry to the container (or from the application)
```
docker exec -it store-api_app_1 bash
```

Deleting the environment
```
docker-compose down -v
```

## Deploy
- Remember to config Sentry when deploying new instance!

## Code style
Set your IDE to use the .editorconfig file. There is an extension for this in VS Code.

When writing the code for this project, follow rules from [Laravel best practices](https://github.com/alexeymezenin/laravel-best-practices).

Additional:
- all variables in this project should be `snake_case` and functions (relations too) `camelCase`,
- variables containing links like `avatar_url` should always end with `_url`,
- all models must have uuid4 formatted ID,
- always get model id by getKey() method,
- in tests use `getJson()`, `postJson()` etc.
- in tests use `assertOk()`, `assertCreated()` etc.
- `$x === null` > `is_null()`.

## Tests
Tests are automatically performed during CI. GitLab will not allow you to merge changes until they all pass. You can run tests locally with the command:
```
php artisan test
```

## Insights
We use [PHP Insights](https://phpinsights.com/) to keep project clean.
```
php artisan insight
```


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
