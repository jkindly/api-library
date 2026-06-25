# Library - API

A simple REST API for keeping track of a library's book collection, built with
**Symfony 8** (PHP 8.4, FrankenPHP) and a **PostgreSQL 17** database. The whole
thing is started with a single `docker compose up`.

## Quick start

```bash
docker compose up --build
```

On startup the container:

1. waits for the database to become ready,
2. runs the Doctrine migrations,
3. starts the HTTP server.

The API is then available at **http://127.0.0.1:8080**.

Check that it is up:

```bash
curl http://127.0.0.1:8080/api/health
# {"status":"ok"}
```

## Data model

Book (`book`):

| Field                  | Description                                                  |
|------------------------|-------------------------------------------------------------|
| `serialNumber`         | unique, six-digit serial number (e.g. `000123`)             |
| `title`                | title                                                       |
| `author`               | author                                                      |
| `borrowed`             | whether it is currently borrowed                            |
| `borrowedAt`           | when it was borrowed (ISO-8601, `null` when available)      |
| `borrowerCardNumber`   | six-digit borrower card number (`null` when available)      |

The serial number and the card number are stored as strings so that leading
zeros are preserved, and are validated against `^\d{6}$`.

## Endpoints

| Method   | Path                               | Description                          |
|----------|------------------------------------|--------------------------------------|
| `GET`    | `/api/books`                       | list all books                       |
| `POST`   | `/api/books`                       | add a new book                       |
| `DELETE` | `/api/books/{serialNumber}`        | delete a book                        |
| `PATCH`  | `/api/books/{serialNumber}/status` | change status: borrowed / available  |

### Examples

Add a book:

```bash
curl -X POST http://127.0.0.1:8080/api/books \
  -H 'Content-Type: application/json' \
  -d '{"serialNumber":"000123","title":"Solaris","author":"Stanisław Lem"}'
```

List books:

```bash
curl http://127.0.0.1:8080/api/books
```

Borrow a book (card number required):

```bash
curl -X PATCH http://127.0.0.1:8080/api/books/000123/status \
  -H 'Content-Type: application/json' \
  -d '{"status":"borrowed","cardNumber":"654321"}'
```

Return a book:

```bash
curl -X PATCH http://127.0.0.1:8080/api/books/000123/status \
  -H 'Content-Type: application/json' \
  -d '{"status":"available"}'
```

Delete a book:

```bash
curl -X DELETE http://127.0.0.1:8080/api/books/000123
```

### Response codes

| Code | Situation                                                                |
|------|--------------------------------------------------------------------------|
| 200  | successful `GET` / `PATCH`                                                |
| 201  | book created                                                              |
| 204  | book deleted                                                              |
| 404  | no book with the given serial number                                     |
| 409  | duplicate serial number / borrowing an already-borrowed book / returning an available one |
| 422  | validation error (a `violations` list is returned)                       |

Errors share a consistent JSON shape, e.g.:

```json
{
  "error": "Validation failed.",
  "violations": [
    {"field": "serialNumber", "message": "Serial number must be exactly 6 digits."}
  ]
}
```

## Concurrency (race conditions)

- **Adding** - serial-number uniqueness is enforced by a `UNIQUE` constraint in
  the database. When the same number is added concurrently, the second operation
  ends with `409` (the `UniqueConstraintViolationException` is caught) instead of
  creating a duplicate.
- **Changing status** - borrowing/returning runs inside a transaction with a
  row-level lock (`SELECT ... FOR UPDATE`, `LockMode::PESSIMISTIC_WRITE`). As a
  result, two concurrent requests to borrow the same book are serialised - only
  the first succeeds, the second gets `409`. The entity also has a `version`
  column (optimistic locking) as an extra safeguard against lost updates.

## Code organisation

```
src/
├── Catalog/         # Catalog - application logic, transactions and locking
├── Controller/      # thin HTTP layer (BookController, HealthController)
├── Dto/             # request/response objects + validation
├── Entity/          # the Book entity with domain logic (borrow / returnToLibrary)
├── Enum/            # BookStatus
├── EventListener/   # JsonExceptionListener - consistent JSON errors
├── Exception/       # domain exceptions mapped to HTTP status codes
└── Repository/      # queries, including the locked read for updates
```

Business rules (e.g. "an already-borrowed book cannot be borrowed again") live
in the `Book` entity; `Catalog` orchestrates the use cases (transactions and
locking); the controller deals only with HTTP. Input validation is declarative on
the DTOs (`#[MapRequestPayload]`).

## Tests

The suite has two layers:

- **Unit tests** (`tests/Unit/`) - domain invariants of the `Book` entity and the
  DTO validation rules. No database, no kernel.
- **Functional tests** (`tests/Functional/`) - the HTTP API end to end through the
  Symfony kernel against a real PostgreSQL database: status codes, validation
  errors, borrow/return flow, conflicts and not-found cases.

Tests run against a **separate `app_test` database** (configured in `.env.test`),
so they never touch the real `app` data. That database is provisioned
automatically on first `docker compose up` by `docker/postgres/init/`. Each
functional test starts from a clean schema.

Run the whole suite inside the container (dev dependencies are installed there):

```bash
docker compose exec app sh -c 'composer install && vendor/bin/phpunit'
```

Run a single suite:

```bash
docker compose exec app vendor/bin/phpunit --testsuite unit
docker compose exec app vendor/bin/phpunit --testsuite functional
```

> Note on concurrency: the race-condition guarantees (pessimistic lock on
> borrow/return, unique constraint on creation) are verified manually with
> parallel requests rather than in PHPUnit, where reliable concurrency testing is
> impractical.

## Code quality

Static analysis with **PHPStan** (level 8) and code style with **Easy Coding
Standard** (PSR-12 + common + clean-code sets). Configuration lives in
`phpstan.dist.neon` and `ecs.php`.

```bash
# static analysis
docker compose exec app sh -c 'composer install && vendor/bin/phpstan analyse'

# check coding standard
docker compose exec app vendor/bin/ecs check

# auto-fix coding standard
docker compose exec app vendor/bin/ecs check --fix
```

## Tech stack

- PHP 8.4 + Symfony 8 (framework-bundle, serializer, validator)
- Doctrine ORM 3 + Doctrine Migrations
- PostgreSQL 17
- FrankenPHP (application server) in a single container
