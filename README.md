# [~~500 Cigarettes~~](https://www.youtube.com/watch?v=8liPBsUtND4) 300 Books

A RESTful API for managing books and authors, built with Laravel 12 and Octane/Swoole.

Based on my template that is slightly overkill for this assignment.

## Thoughts / Notes
The spec left some areas open to interpretation. Here's some thoughts/decisions made:
- Sanctum auth was only specified for one endpoint, so only that endpoint requires it. In a real application I'd limit public access to GET endpoints only.
- Deleting a book does not remove its authors, even if they no longer have any associated books.
- Since there's no `POST /api/authors` endpoint and the Artisan command is listed as a bonus, I opted to create authors on-demand when books are created.
- Model columns weren't strictly defined:
    - Added ISBN to books for additional data to work with.
    - The spec references first and last name for authors, but I store both in a single column. This better accommodates authors with more complex/non-standard name structures without affecting functionality.
- Related models are fully loaded on relevant endpoints. Ideally these would be capped (e.g. `limit(100)` on the relationship - Laravel 12 correctly applies per-parent limits). When the `search` query parameter is supplied on `GET /api/authors`, it could also filter related books to only those matching the query.
- Search is implemented using Postgres-specific features, since that's the chosen database for this stack.
- Job for updating last published book title is dispatched directly from the service instead of an event listener, which does not strictly adhere to SOLID principles. Doing so would add unnecessary complexity (since attach/sync/detach don't fire events), for very little benefit.
- The stack is slightly overbuilt for this assignment - e.g. a scheduler container is running but unused. Octane is also overkill, but `php artisan serve`'s synchronicity and response times leave a lot to be desired.

## Features

- **Books CRUD** - Create, list, view, update, and delete books
- **Authors** - List and view authors; authors are automatically created when adding books. Supports searching/filtering authors by book title via `?search=` query parameter (case-insensitive partial match via PostgreSQL trigram index).
- **Many-to-many relationships** - Books can have multiple authors, authors can have multiple books
- **ISBN validation** - ISBN-10 and ISBN-13 format validation with checksum verification (hyphens allowed)
- **Automatic last book tracking** - Each author's `last_book_title` is kept up-to-date via a queued background job whenever book–author relationships change
- **Pagination** - Configurable page sizes (15, 25, or 50 per page) on list endpoints
- **Authentication** - Sanctum token authentication required for book creation
- **JSON responses** - `ForceJson` middleware ensures all requests return `application/json`
- **Service layer** - Business logic encapsulated in service classes bound via interfaces for testability
- **DTOs** - Typed readonly data transfer objects for passing validated request data to services
- **Artisan commands** - `php artisan author:create` to create authors interactively from the CLI
- **OpenAPI documentation** - Auto-generated interactive API docs via Scramble available at `/docs/api`
- **Background jobs** - Queue processing and monitoring with Horizon + Redis

## Stack

- **PHP 8.4** with **Laravel Octane + Swoole** (persistent in-memory application)
- **PostgreSQL 18** - primary database
- **Redis** - cache, sessions, and queue backend
- **Laravel Horizon** - queue worker and monitoring dashboard
- **Laravel Sanctum** - API token authentication
- **Pest** - testing framework
- **Laravel Pint** - code style fixer (PSR-12 / Laravel preset)
- **Scramble** - automatic OpenAPI documentation
- **Sentry** - error tracking (bring your own DSN with SENTRY_LARAVEL_DSN env var)
- **GitHub Actions** - CI/CD pipeline (lint, test, build)

## Getting Started

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)

### Setup

```bash
# Clone the repository
git clone https://github.com/wokes/300-books.git 300-books
cd 300-books

# Build and start all services
docker compose build && docker compose up -d

# Open API docs in browser
open http://localhost:8080/docs/api
```

On first startup the entrypoint automatically generates an app key, runs `migrate:fresh`, seeds the demo user, and links storage - no manual setup needed.

### Setup via VS Code DevContainer

1. Install the **Dev Containers** extension
2. Open the project in VS Code
3. Press Ctrl+Shift+P then **Dev Containers: Reopen in Container**

### Auth
The demo user and API token are seeded automatically on first startup.

Include `Authorization: Bearer deadbeef-wildcard-demo-token` header in requests for `POST /api/books`.

To re-seed manually: `php artisan db:seed --class=UserSeeder`.

UserSeeder has a guard preventing it from running in production.

### Seeding
Seeders for Books and Authors are also included.

`php artisan db:seed --class=BookSeeder`

`php artisan db:seed --class=AuthorSeeder`

## Common Commands

| Command | Description |
|---------|-------------|
| `docker compose up -d` | Start all services |
| `docker compose down` | Stop all services |
| `docker compose exec laravel.test bash` | Open shell in app container |
| `php artisan test --parallel` | Run Pest tests in parallel |
| `./vendor/bin/pint --test` | Check code style (Pint) |
| `./vendor/bin/pint` | Auto-fix code style |
| `php artisan migrate:fresh --seed` | Re-run migrations with seeding |
| `php artisan scramble:export` | Export OpenAPI docs to `api.json` |
| `php artisan octane:reload` | Gracefully reload Octane workers |

## Live Reloading

Octane keeps the application in memory for performance. File changes are detected automatically:

- **Automatic**: Supervisord runs Octane with `--watch`, which uses [chokidar](https://github.com/paulmillr/chokidar) to monitor `app/`, `config/`, `routes/`, etc. and reload workers on changes.
- **Manual**: Run `php artisan octane:reload` to force a graceful worker restart.

## Testing

Tests are written with [Pest](https://pestphp.com/) and use PostgreSQL with `RefreshDatabase` in Feature tests.

```bash
# Run all tests in parallel
php artisan test --parallel

# Run a specific test file
php artisan test tests/Feature/BookApiTest.php
```

## API Documentation

Scramble auto-generates OpenAPI docs from your API routes. Access:
- **Interactive docs**: http://localhost:8080/docs/api
- **OpenAPI JSON**: http://localhost:8080/docs/api.json

Export to file:
```bash
php artisan scramble:export
```

## CI/CD

GitHub Actions pipeline (`.github/workflows/ci.yml`):

1. **Lint** - Runs Pint to check code style
2. **Test** - Runs Pest with Postgres + Redis + Swoole
3. **Build** - Validates Dockerfile builds successfully

## Octane Considerations

When using Octane, keep these in mind:

- **Static properties persist** between requests. Avoid storing request-specific state in static properties.
- **Singletons registered in service providers** persist. Use `$this->app->bind()` instead of `$this->app->singleton()` for request-scoped services.
- **File uploads and request data** are handled normally by Octane's request lifecycle.
- See [Laravel Octane docs](https://laravel.com/docs/octane) for the full list of caveats.

## Health Check

`GET /healthz` returns 200 when the application is healthy.

## License

[MIT](LICENSE)


![300 books. It. Is. Not. Food.](https://i.imgur.com/DSdyUr9.png)
