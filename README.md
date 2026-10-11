# Order Management API

REST API for managing customers, products, orders, order items, VAT rates
and payments. Built with Laravel and PostgreSQL, with a Redis cache, PHPUnit
feature tests and a Docker Compose setup.

## Features
- CRUD endpoints for customers, products, orders, order items, VAT rates and payments
- Order creation and updates with line items, running inside database
  transactions: if any part of the request fails, nothing is saved
- Automatic line totals, calculated from quantity, price and discount
- Search, filtering, sorting and pagination on orders
- Redis cache for the orders list (tagged, 60-second TTL), cleared whenever an
  order or one of its items is created, updated or deleted. Changes to
  customers or payments appear in cached lists after at most 60 seconds.
- Deleting an order also deletes its items (cascade)
- Request validation with Laravel Form Requests
- Service layer (`OrderService`) and API Resources for consistent responses
- PHPUnit feature tests, including transaction rollback and cache invalidation

## Tech stack
PHP 8.4 · Laravel · PostgreSQL 16 · Redis 7 · Docker Compose · PHPUnit (SQLite in-memory and array cache for tests)

## Getting started
```bash
cp .env.example .env
docker compose build
docker compose run --rm app composer install
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```
The API runs at `http://localhost:8000/api`.
PostgreSQL is exposed on port `5433`.

## Endpoints
| Resource    | Path               |
|-------------|--------------------|
| Customers   | `/api/customers`   |
| Products    | `/api/products`    |
| Orders      | `/api/orders`      |
| Order items | `/api/order-items` |
| VAT rates   | `/api/vats`        |
| Payments    | `/api/payments`    |

Each resource supports list, show, create, update and delete.

## Orders

### List orders
`GET /api/orders` returns 10 orders per page.

| Parameter | Description |
|-----------|-------------|
| `status`  | Filter by order status: `created`, `pending`, `completed` or `cancelled` (other values return `422`) |
| `search`  | Search by order number or customer name/surname |
| `sort`    | `number`, `status` or `created_at`; prefix with `-` for descending (e.g. `-number`) |
| `page`    | Page number |

Example: `GET /api/orders?status=completed&search=John&sort=-number`

### Create an order
`POST /api/orders`

```json
{
  "customer_id": 1,
  "payment_id": 1,
  "number": 1001,
  "notes": "Test order",
  "items": [
    { "product_code": "TEST001", "quantity": 2, "discount": 10 }
  ]
}
```
The price, name and VAT code of each item are taken from the product unless
`price` or `name` are provided. If `discount` is omitted, the product's
discount is used. The customer, payment and product codes must exist, the
quantity must be at least 1 and the discount between 0 and 100; otherwise the
API returns `422`. A successful request returns `201`.

### Update an order
`PUT /api/orders/{id}` accepts order fields (e.g. `status`, `notes`) and,
optionally, `items`:
- items with an `id` are updated
- items without an `id` are created
- existing items that are not in the list are deleted

The whole update runs in a single transaction.

## Order items
Items can also be managed one by one through `/api/order-items`.
- Creating an item requires `product_code`, `order_id`, `vat_code`, `name`,
  `price` (at least 0) and `quantity` (at least 1). `discount` (0 to 100)
  defaults to 0.
- The line total is calculated by the API:
  `quantity x price x (100 - discount) / 100`.
- When updating, any field that is not sent keeps its current value, and the
  total is recalculated.

## Caching
`GET /api/orders` is cached in Redis for 60 seconds, with a separate entry for
each combination of `status`, `search`, `sort` and `page`. The cache is cleared
after an order is created, updated or deleted, or when its items are changed
through `/api/order-items`.

## Running tests
The tests run on an in-memory SQLite database and an array cache (configured in
`phpunit.xml`), so they need no extra setup and never touch the PostgreSQL or
Redis data.
```bash
docker compose exec app php artisan test
```