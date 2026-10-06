## Tech stack
PHP 8.4 · Laravel · PostgreSQL 16 · Docker Compose · PHPUnit (SQLite in-memory for tests)

## Getting started
```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```
The API runs at `http://localhost:8000/api`.
PostgreSQL is exposed on port `5433`.

## Running tests
The tests run on an in-memory SQLite database (configured in `phpunit.xml`),
so they need no extra setup and never touch the PostgreSQL data.
```bash
docker compose exec app php artisan test
```