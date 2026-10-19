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
| GET | `/sanctum/csrf-cookie` | Official Sanctum CSRF cookie |
| GET | `/api/v1/auth/csrf` | CSRF cookie + `csrf_token` for `X-CSRF-TOKEN` |
| POST | `/api/v1/auth/login` | Admin login |
| GET | `/api/v1/auth/user` | Current admin profile |
| POST | `/api/v1/auth/logout` | Logout |

## Frontend origins

Configure `FRONTEND_URLS` (comma-separated origins) and `SANCTUM_STATEFUL_DOMAINS` (comma-separated hosts) for:

- `http://localhost:8080` and `http://localhost:8081`
- `https://www.luvanexrealty.com`
- `https://luvanexrealty.com`
- `https://web.luvanexrealty.com`

Do not use CORS wildcards with credentials.

## Admin portal (React SPA)

The public website and admin CMS live in the separate React/Vite app (not this repository).

- Public entry: **Portal Login** → `/admin/login`
- After authentication: `/admin/dashboard`
- Session auth: Sanctum cookies via `credentials: "include"` — no JWT, no `localStorage` tokens
- Production administrator credentials come from Forge `ADMIN_EMAIL` / `ADMIN_PASSWORD` (seed with `AdminUserSeeder`). Never put those values in the frontend.

Frontend environment (Vercel):

```
VITE_SITE_URL=https://web.luvanexrealty.com
VITE_API_BASE_URL=https://<ACTUAL-FORGE-HOSTNAME>
```

`VITE_SITE_URL` is the SEO canonical origin. `VITE_API_BASE_URL` is the Laravel API. They must stay different.

## Production (Laravel Forge)

Set MySQL credentials, `APP_URL` to the Forge hostname, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=none`, and production `ADMIN_*` values via Forge environment — never commit secrets. Set `FRONTEND_URL=https://www.luvanexrealty.com`. Include `https://www.luvanexrealty.com` and `https://luvanexrealty.com` in `FRONTEND_URLS`, and `www.luvanexrealty.com` and `luvanexrealty.com` in `SANCTUM_STATEFUL_DOMAINS`. After changing those values, run `php artisan config:cache` so cached config picks them up. Do not copy local `SESSION_SAME_SITE=lax` to Forge; cross-origin SPA cookies require `none`. Keep `SESSION_DOMAIN` empty so cookies stay on the API host.
