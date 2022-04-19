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

If port is taken you can change DOCKER_PORT in .env to your liking
Easy start only works with default dirname `store-api` for now.

## Git hooks
Project uses git pre-commit hook to automaticly generate IDE Helper docs and fix style issues

>### Attention
>Hook scripts assume the project catalogue uses the default repository name: store-api.
>The copied hooks will need to be modified with correct catalogue name.

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
- don't use laravel class aliases like `\DB`, `\Arr`, use full import instead (we deleted aliases, so it's won't work either),
- all variables in this project should be `snake_case` and functions (relations too) `camelCase`,
- variables containing links like `avatar_url` should always end with `_url`,
- all models must have uuid4 formatted ID,
- always get model id by getKey() method,
- in tests use `assertOk()`, `assertCreated()` etc.
- `$x === null` > `is_null()`,
- avoid magic methods,

## Tests
Tests are automatically performed during CI. GitLab will not allow you to merge changes until they all pass. You can run tests locally with the command:
```
php artisan test
```

## Docs
OpenAPI documentation files are located under `./public/docs`

Validate documentation:
```
make swagger-validate
```

The documentation page is available at `/docs`.

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
or
docker exec -it <dir-name>_app_1 bash
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

After migration run jwt install.
```
php artisan jwt:secret
```

Seeder creates user `admin@example.com` with password `secret`.

When something not working with cache (like routing).
```
php artisan optimize
```

## Database Queue
Set `QUEUE_CONNECTION=database` variable in `.env`

Run migrations to create `jobs` table in database.
```
php artisan migrate
```

To process queued jobs start a queue worker.
```
php artisan queue:work
```
It will continue to run until it is manually stopped.
