# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**EventReserve** — a Symfony 7.4 event reservation management web application with JWT API, WebAuthn/Passkeys support, and a Twig-rendered admin dashboard. Stack: PHP 8.2, PostgreSQL 15, Doctrine ORM 3, Nginx, Docker.

## Common Commands

### Docker (recommended)

```bash
# Start all services (app, nginx, db, adminer)
docker-compose up -d --build

# Run Symfony console commands inside the container
docker-compose exec app php bin/console <command>

# Run migrations
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
docker-compose exec app php bin/console cache:clear
```

### Local development (without Docker)

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console lexik:jwt:generate-keypair
symfony serve
# or: php -S localhost:8080 -t public/
```

### Database migrations

```bash
# Generate a new migration from entity changes
php bin/console doctrine:migrations:diff

# Apply pending migrations
php bin/console doctrine:migrations:migrate
```

### Useful debug commands

```bash
php bin/console debug:router          # List all routes
php bin/console debug:container       # List all services
php bin/console doctrine:schema:validate
```

## Architecture

### Dual authentication system

The app has **two parallel auth flows**:

1. **Web (session-based)**: `SecurityController` handles `/login` and `/register` using Symfony's `form_login` firewall. JWT is NOT involved here — this is standard Symfony session auth for the Twig UI.

2. **API (stateless JWT)**: `ApiAuthController` handles `/api/login` and `/api/register`. The `lexik` firewall issues JWT tokens returned as JSON. `PasskeyController` handles WebAuthn flows under `/api/passkeys/*`.

These are separate firewalls in `config/packages/security.yaml`. The `main` firewall covers web routes; the `api` firewall covers `/api/*`.

### Controllers and their responsibilities

- `AdminController` — CRUD for events (with image upload to `public/uploads/events/`), reservation deletion. All routes prefixed `/admin`, requires `ROLE_ADMIN`. Manual form parsing (no Symfony Form component used).
- `EventController` — public event listing and detail view (`/events`).
- `ReservationController` — user reservation creation and personal history (`/reservations`).
- `SecurityController` — web login/logout/register.
- `ApiAuthController` — JWT login and register endpoints, plus `/api/profile`.
- `PasskeyController` — WebAuthn registration and authentication options/verify endpoints.

### Passkey implementation note

`PasskeyService` is a **lightweight custom implementation** — credentials are stored as a JSON blob in `User.passkeyCredentials` (text column). It generates challenges and stores public key credential IDs but does **not** perform full cryptographic assertion verification. For production, replace with `web-auth/webauthn-symfony-bundle`.

### Entity relationships

- `User` has many `Reservation` (nullable orphanRemoval — deleting a user does NOT cascade-delete reservations)
- `Event` has many `Reservation` (orphanRemoval=true — deleting an event deletes its reservations)
- `Reservation` belongs to both `User` and `Event`

### Image uploads

Event images are stored in `public/uploads/events/` (gitignored). The filename is slugified + `uniqid()`. The `image` column on `Event` stores only the filename, not the full path.

## Environment Variables

Copy `.env.example` to `.env.local`. Key variables:

| Variable | Description |
|---|---|
| `DATABASE_URL` | PostgreSQL DSN (`postgresql://event_user:event_pass@db:5432/event_db`) |
| `JWT_SECRET_KEY` / `JWT_PUBLIC_KEY` | Paths to PEM keys (generate with `lexik:jwt:generate-keypair`) |
| `JWT_PASSPHRASE` | Passphrase for the JWT private key |
| `APP_RP_ID` | WebAuthn Relying Party ID (e.g. `localhost`) |
| `APP_RP_NAME` | WebAuthn Relying Party display name |
| `CORS_ALLOW_ORIGIN` | Regex for allowed CORS origins |

Docker Compose hardcodes `DATABASE_URL` via environment in `docker-compose.yml` — this overrides `.env`.

## Access URLs (Docker)

- App: http://localhost:8080
- Adminer (DB GUI): http://localhost:8081
