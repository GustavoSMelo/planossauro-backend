# Planossauro Backend

![Laravel](https://img.shields.io/badge/Laravel-12.0-red?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5-blue?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Sanctum](https://img.shields.io/badge/Sanctum-4.0-orange?style=flat-square&logo=laravel)
![Stripe](https://img.shields.io/badge/Stripe-19.3-purple?style=flat-square)

RESTful API backend for the Planossauro planning management platform. Built with Laravel and Sanctum for authentication.

## Tech Stack

───────────────────────────────

- **Framework:** Laravel 12
- **Auth:** Laravel Sanctum
- **Payments:** Stripe (webhooks integration)
- **Documentation:** OpenAPI specs in `docs/API_ROUTES.md`

───────────────────────────────

## Features

- User management with GitHub and Google OAuth
- Planning and planning hours tracking
- Subscription management with Stripe
- Payment history with NFe upload support
- Support email system
- Rate-limited API endpoints

───────────────────────────────

## Getting Started

### Requirements

- PHP 8.2+
- Composer
- SQLite/MySQL/MariaDB/PostgreSQL

---

### Installation

```bash
# Install dependencies
composer install

# Run migrations and seeder
php artisan migrate --seed

# Start development server
php artisan serve
```

---

### Docker

```bash
# Build and start containers
docker compose up -d

# View logs
docker compose logs -f

# Stop containers
docker compose down
```

The API will be available at `http://localhost:8080/api`

───────────────────────────────

### Docker Homolog

```bash
# Build and start homolog containers
docker compose -f docker-compose.homolog.yml up -d

# View logs
docker compose -f docker-compose.homolog.yml logs -f

# Stop containers
docker compose -f docker-compose.homolog.yml down
```

───────────────────────────────

## Docker Configuration

### nginx.conf
- Listens on port 8080
- Web root: `/app/public`
- PHP-FPM via Unix socket at `/var/run/php/php8.5-fpm.sock`
- Security headers (X-Frame-Options, X-Content-Type-Options)
- 300s timeouts for PHP-FPM requests

### php8.5-fpm.sock
- Socket: `/var/run/php/php8.5-fpm.sock`
- Process manager: dynamic (min 2, max 25 children)
- Max request timeout: 300s
- Status endpoint enabled at `/status`

───────────────────────────────

## Scheduled Tasks

The following tasks run daily at midnight (America/Sao_Paulo timezone):

| Task | Description |
|------|-------------|
| `reset_subscription_tokens` | Resets daily/weekly plan usage and updates billing date for active/paid subscriptions |
| `delete_user_accounts` | Permanently deletes users soft-deleted over 1 month ago |

Run the scheduler manually:
```bash
php artisan schedule:run
```

───────────────────────────────

## API Documentation

Full API documentation available at `docs/API_ROUTES.md`.

**Base URL:** `/api`

### Quick Examples

```bash
# Health check
curl http://localhost:8000/api/health

# Create user
curl -X POST http://localhost:8000/api/user \
  -H "Content-Type: application/json" \
  -d '{"full_name":"John","cellphone_number":"11999999999","email":"john@example.com"}'

# List plans (no auth required)
curl http://localhost:8000/api/plans/
```

───────────────────────────────

## Project Structure

```
app/
├── Dto/Stripe/          # Stripe webhook DTOs
├── Http/
│   ├── Controllers/     # API controllers
│   └── Middleware/       # Custom middleware
├── Mail/                # Email templates
├── Models/               # Eloquent models
└── Providers/            # Service providers
config/                   # Laravel configuration
docs/                     # API documentation
routes/                   # Route definitions
```

───────────────────────────────

## Key Endpoints

| Resource | Description |
|----------|-------------|
| `/user` | User management |
| `/planning` | Planning CRUD |
| `/planninghour` | Planning hours tracking |
| `/plans` | Available subscription plans |
| `/subscription` | Subscription management |
| `/payment/history` | Payment records |
| `/auth/*` | GitHub/Google authentication |
| `/webhook/payment` | Stripe webhook handler |

───────────────────────────────

## Rate Limits

| Endpoint Group | Limit |
|----------------|-------|
| Logout | 10 req/min |
| Health, User creation, Support, Auth | 20 req/min |
| Plans listing | 40 req/min |
| Planning, Subscription, Payment | 100 req/min |

───────────────────────────────

## License

Private - You can see and check how the software works, but is not allowed to redistribute the software, but you are allowed to running locally on your machine.
