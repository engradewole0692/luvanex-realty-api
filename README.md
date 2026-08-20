# Luvanex Realty API

Laravel 13 API backend for Luvanex Realty Group (Sanctum SPA authentication).

## Requirements

- PHP 8.3+
- Composer
- MySQL (production) or SQLite (local)

## Local setup

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

Default seeded admin (local only — change immediately):

- Email: `admin@luvanexrealty.com`
- Password: `ChangeMeSecurely!`

## Auth endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/auth/csrf` | Initialize CSRF cookie |
| POST | `/api/v1/auth/login` | Admin login |
| GET | `/api/v1/auth/user` | Current admin profile |
| POST | `/api/v1/auth/logout` | Logout |

## Frontend origins

Configure `FRONTEND_URLS` and `SANCTUM_STATEFUL_DOMAINS` for:

- `http://localhost:8080`
- `https://web.luvanexrealty.com`
- `https://luvanexrealty.com`

Do not use CORS wildcards with credentials.

## Production (Laravel Forge)

Set MySQL credentials, `APP_URL` to the Forge hostname, `SESSION_SECURE_COOKIE=true`, and production `ADMIN_*` values via Forge environment — never commit secrets.
