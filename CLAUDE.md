# eHub API

Laravel 10 REST API for e-sports league management.

## Commands

```bash
php artisan serve          # Dev server (port 8000)
php artisan migrate        # Run migrations
php artisan migrate:fresh  # Drop all + re-migrate
php artisan tinker         # REPL
vendor/bin/phpunit         # Run tests
php artisan pint           # Fix code style (Laravel Pint)
```

## Database

- MySQL, database: `ehub`
- Migrations: `database/migrations/2023_10_12_ehub_v1_0_0/`
- Route model binding uses slugs: `{leagueRoute}`, `{tournamentRoute}`

## Architecture

- Auth: Sanctum (Bearer tokens)
- Images: Intervention/Image → WebP, 250px thumbnail in `storage/app/public/`
- WebSocket: broadcasts via `POST http://127.0.0.1:3001/broadcast` with `{data, room, update}`
- Payments: PayPal SDK (sandbox in dev)

## Code Style

- PSR-12 via Laravel Pint — run `pint` before committing
- Controllers grouped by domain: `Auth/`, `League/`, `Tournament/`, `Category/`, `Payment/`
- Protected routes use `auth:sanctum` middleware

## Environment

Required `.env` keys beyond Laravel defaults:
- `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_MODE`
- `SUPPORT_EMAIL`, `SUPPORT_NAME`
- WebSocket URL hardcoded as `http://127.0.0.1:3001` in route handler

## Testing

```bash
vendor/bin/phpunit                        # All tests
vendor/bin/phpunit --filter TestName     # Single test
```
