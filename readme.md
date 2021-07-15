# Heseya Store API

## Easy start with Docker
```
make build
```

Api should be available at `localhost`

Adminer should be available at `adminer.localhost`

Seeder creates user `admin@example.com` with password `secret`.

If you have already built the application you can restart it with:
```
make up
```

Entry to the container (or from the application)
```
make bash
```

## Git hooks
Project uses git pre-commit hook to automaticly generate IDE Helper docs and fix style issues

>### Attention
>This section assumes the project catalogue uses the default repository name: store-api.
>The commands and the git hooks running on host will need to be modified with correct catalogue name otherwise.

Enable pre-commit scripts by copying git hooks
```
make hooks
```
or if you commit directly from inside the container
```
cp ./git_hooks/host/* ./.git/hooks/
```

## Code style
Set your IDE to use the .editorconfig file. There is an extension for this in VS Code.

When writing the code for this project, follow rules from [Laravel best practices](https://github.com/alexeymezenin/laravel-best-practices).

You can test your code with [PHP Insights](https://phpinsights.com/).
```
php artisan insights
```

Additional:
- all variables in this project should be `snake_case` and functions (relations too) `camelCase`,
- variables containing links like `avatar_url` should always end with `_url`,
- all models must have uuid4 formatted ID,
- always get model id by getKey() method,
- in tests use `getJson()`, `postJson()` etc.
- in tests use `assertOk()`, `assertCreated()` etc.
- `$x === null` > `is_null()`,
- avoid magic methods,

## Tests
Tests are automatically performed during CI. GitLab will not allow you to merge changes until they all pass. You can run tests locally with the command:
```
php artisan test
```

## Docs
Write documentation using [Swagger-PHP](http://zircote.github.io/swagger-php/).

Generating documentation:
```
composer docs
```

The generated documentation is available at `/docs`.

Locally I recommend set `L5_SWAGGER_GENERATE_ALWAYS` option in .env to `true`, then the documentation will be generated with every refresh.

## IDE-helper
Laravel [IDE Helper](https://packagist.org/packages/barryvdh/laravel-ide-helper), generates correct PHPDocs for all Facade classes, to improve auto-completion.

Set the ide-helper for the new model:
```
composer ide-helper
```

## Release checklist
This project uses [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html)

- [ ] Check if migrations work,
- [ ] Check if all tests pass,
- [ ] Write down all changes to `changelog.md`,
- [ ] Change version in `config/app.php`,
- [ ] Change version in `app/Http/Controllers/Controller.php` (Swagger),
- [ ] Create a version tag on the master branch.

## Deploy
- Remember to config Sentry when deploying new instance!

## Manual project setup with Docker
### Attention
This section assumes the project catalogue uses the default repository name: store-api.
The commands and the git hooks running on host will need to be modified with correct catalogue name otherwise.
Preparation
- Copy `.env.example` to `.env`.
- Configure DOCKER_PORT in .env to free port on your host eg.
```
DOCKER_PORT=3000
```

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

Optionally, you can clear the entire project cache by
```
docker system prune
```

## Project setup without Docker
Enable pre-commit scripts by copying git hooks
```
cp ./git_hooks/host/* ./.git/hooks/
```

Install dependencies
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
