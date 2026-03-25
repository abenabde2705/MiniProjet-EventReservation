# EventReserve - Application Web de Gestion de Reservations d'Evenements

A full-featured event reservation management system built with **Symfony 7**, featuring JWT authentication, WebAuthn/Passkeys support, and a complete admin dashboard.

## Features

### User Side
- Register and login with username + password
- JWT token authentication (stored in cookie)
- Browse all upcoming events
- View event details (description, date, location, image)
- Reserve a seat with name, email, phone
- Confirmation page after reservation
- View personal reservation history

### Admin Side
- Secure login with ROLE_ADMIN privileges
- Dashboard with statistics (total events, reservations, available seats)
- Full CRUD on events with image upload
- View all reservations per event
- Delete reservations
- Secure logout

### Security
- JWT authentication via `LexikJWTAuthenticationBundle`
- WebAuthn / Passkeys support for passwordless login (custom service)
- Role-based access control: `ROLE_USER`, `ROLE_ADMIN`
- Protected admin routes
- CSRF protection on forms

---

## Technology Stack

| Layer       | Technology                        |
|-------------|-----------------------------------|
| Framework   | Symfony 7.4                       |
| Language    | PHP 8.2                           |
| Database    | PostgreSQL 15                     |
| ORM         | Doctrine ORM 3                    |
| Auth        | LexikJWTAuthenticationBundle 3.x  |
| Passkeys    | WebAuthn (custom service)         |
| Templates   | Twig 3 + Bootstrap 5              |
| Web Server  | Nginx (Alpine)                    |
| Container   | Docker / Docker Compose           |

---

## Project Structure

```
event-reservation/
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── lexik_jwt_authentication.yaml
│   │   ├── security.yaml
│   │   └── ...
│   └── services.yaml
├── docker/
│   ├── nginx/default.conf
│   └── php/local.ini
├── migrations/
├── public/
│   └── uploads/events/          # Uploaded event images
├── src/
│   ├── Controller/
│   │   ├── AdminController.php
│   │   ├── ApiAuthController.php
│   │   ├── EventController.php
│   │   ├── HomeController.php
│   │   ├── PasskeyController.php
│   │   ├── ReservationController.php
│   │   └── SecurityController.php
│   ├── Entity/
│   │   ├── Event.php
│   │   ├── Reservation.php
│   │   └── User.php
│   ├── Repository/
│   └── Service/
│       └── PasskeyService.php
├── templates/
│   ├── admin/
│   ├── event/
│   ├── home/
│   ├── reservation/
│   ├── security/
│   └── base.html.twig
├── docker-compose.yml
├── Dockerfile
└── .env
```

---

## Installation

### Prerequisites
- Docker & Docker Compose
- Git

### With Docker (Recommended)

1. **Clone the repository**
```bash
git clone https://github.com/abenabde2705/MiniProjet-EventReservation.git
cd MiniProjet-EventReservation
```

2. **Copy and configure environment**
```bash
cp .env.example .env.local
# Edit .env.local with your values
```

3. **Start Docker services**
```bash
docker-compose up -d --build
```

4. **Generate JWT keys**
```bash
docker-compose exec app php bin/console lexik:jwt:generate-keypair
```

5. **Run database migrations**
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

6. **Create an admin user**
```bash
docker-compose exec app php bin/console app:create-admin
# Or manually:
docker-compose exec app php bin/console doctrine:query:sql \
  "INSERT INTO \"user\" (username, password, roles) VALUES ('admin', '<hashed>', '[\"ROLE_ADMIN\"]')"
```

7. **Access the application**
- App: http://localhost:8080
- Adminer (DB GUI): http://localhost:8081

---

### Manual Installation (without Docker)

**Requirements:** PHP 8.2+, Composer, PostgreSQL 15, Node.js (optional)

1. **Install dependencies**
```bash
composer install
```

2. **Configure environment**
```bash
cp .env.example .env.local
# Set DATABASE_URL and other vars
```

3. **Generate JWT keys**
```bash
php bin/console lexik:jwt:generate-keypair
```

4. **Create database and run migrations**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Start development server**
```bash
symfony serve
# or
php -S localhost:8080 -t public/
```

---

## Environment Variables

Copy `.env.example` to `.env.local` and configure:

| Variable              | Description                            | Example                                    |
|-----------------------|----------------------------------------|--------------------------------------------|
| `APP_ENV`             | Application environment                | `dev` / `prod`                             |
| `APP_SECRET`          | Symfony app secret (32+ chars)         | `a_random_secure_string`                   |
| `DATABASE_URL`        | PostgreSQL connection URL              | `postgresql://user:pass@db:5432/event_db`  |
| `JWT_SECRET_KEY`      | Path to JWT private key                | `%kernel.project_dir%/config/jwt/private.pem` |
| `JWT_PUBLIC_KEY`      | Path to JWT public key                 | `%kernel.project_dir%/config/jwt/public.pem`  |
| `JWT_PASSPHRASE`      | JWT key passphrase                     | `your_secure_passphrase`                   |
| `APP_RP_ID`           | WebAuthn Relying Party ID              | `localhost`                                |
| `APP_RP_NAME`         | WebAuthn Relying Party Name            | `EventReserve`                             |
| `CORS_ALLOW_ORIGIN`   | CORS allowed origins (regex)           | `^https?://(localhost)(:[0-9]+)?$`         |

---

## API Endpoints (JWT)

| Method | Endpoint                       | Auth        | Description                    |
|--------|--------------------------------|-------------|--------------------------------|
| POST   | `/api/login`                   | Public      | Get JWT token                  |
| POST   | `/api/register`                | Public      | Register new user              |
| GET    | `/api/profile`                 | ROLE_USER   | Get current user profile       |
| POST   | `/api/passkeys/register/options` | ROLE_USER | Start passkey registration     |
| POST   | `/api/passkeys/register/verify`  | ROLE_USER | Complete passkey registration  |
| POST   | `/api/passkeys/login/options`    | Public    | Start passkey authentication   |
| POST   | `/api/passkeys/login/verify`     | Public    | Complete passkey login + JWT   |

---

## Git Branch Strategy

| Branch                  | Purpose                           |
|-------------------------|-----------------------------------|
| `main`                  | Stable production-ready code      |
| `dev`                   | Integration branch                |
| `feature/auth`          | Authentication features           |
| `feature/events`        | Event management features         |
| `feature/reservations`  | Reservation system features       |
| `feature/admin`         | Admin dashboard features          |
| `feature/docker`        | Docker configuration              |
| `feature/jwt-passkeys`  | JWT + WebAuthn/Passkeys           |

---

## License

MIT License. See [LICENSE](LICENSE) for details.
