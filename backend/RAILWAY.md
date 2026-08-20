# Deploying to Railway

This backend deploys to Railway as a single Nixpacks service (PHP 8.3 +
Composer, auto-detected + configured by `nixpacks.toml`/`railway.json`
in this repo) plus a Railway-managed MySQL plugin. `deploy/start.sh` is
the boot command — it caches config, runs migrations, runs the full
`db:seed` chain (safe to repeat every boot, see that file's comment),
links storage, then starts `php artisan serve` on Railway's assigned
`$PORT`.

## 1. Create the service

1. New Project → Deploy from GitHub repo → this repo (`ERP-backend`).
2. Add a plugin: **MySQL**. Railway provisions it and exposes
   `MYSQLHOST` / `MYSQLPORT` / `MYSQLUSER` / `MYSQLPASSWORD` /
   `MYSQLDATABASE` on the MySQL service.

## 2. Required environment variables (set on the backend service)

Reference the MySQL plugin's variables with Railway's `${{ServiceName.VAR}}`
syntax so they stay in sync if the plugin ever rotates credentials:

```
APP_NAME=Garments ERP
APP_ENV=production
APP_KEY=              # see step 3 — generate once, never regenerate in place
APP_DEBUG=false
APP_URL=https://<this-service>.up.railway.app

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=array
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

PASSPORT_PRIVATE_KEY=  # see step 3 — PEM contents, generated once
PASSPORT_PUBLIC_KEY=   # see step 3 — PEM contents, generated once
PASSPORT_PASSWORD_GRANT_CLIENT_ID=      # see step 4
PASSPORT_PASSWORD_GRANT_CLIENT_SECRET=  # see step 4

MODULE_VENDOR=garments-erp
MODULE_AUTHOR_NAME="Vishesh Textiles"
MODULE_AUTHOR_EMAIL=dev@vishesh-textiles.example

# CORS — the deployed frontend's exact origin(s), comma-separated.
CORS_ALLOWED_ORIGINS=https://<your-frontend>.vercel.app
# Optional: allow every Vercel preview URL for this project too.
CORS_ALLOWED_ORIGIN_PATTERN=^https://erp-frontend-[a-z0-9-]+\.vercel\.app$

# Only set these if you want a specific admin login / the demo dataset
# in this environment — see backend/SETUP.md §4 and
# database/seeders/AdminUserSeeder.php + DemoDataSeeder.php for why
# both are opt-in in production.
# ADMIN_SEED_EMAIL=admin@vishesh-textiles.example
# ADMIN_SEED_PASSWORD=
# DEMO_SEED_FORCE=1
```

`storage/oauth-private.key`/`oauth-public.key` (the files
`php artisan passport:keys` would normally write) are deliberately
**not** used here — Railway's filesystem doesn't persist across
deploys, so the keys are provided directly via
`PASSPORT_PRIVATE_KEY`/`PASSPORT_PUBLIC_KEY` env vars instead, which
`config/passport.php` already reads (see that file).

## 3. Generate APP_KEY and Passport keys once, locally

Run these on any machine with PHP + OpenSSL (they don't touch Railway,
they just produce values to paste into step 2's env vars):

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"   # APP_KEY

openssl genrsa -out /tmp/oauth-private.key 4096
openssl rsa -in /tmp/oauth-private.key -pubout -out /tmp/oauth-public.key
cat /tmp/oauth-private.key   # -> PASSPORT_PRIVATE_KEY (paste PEM as-is, Railway supports multiline)
cat /tmp/oauth-public.key    # -> PASSPORT_PUBLIC_KEY
```

Do this exactly once per environment and never regenerate in place —
regenerating `APP_KEY` invalidates every encrypted value already
stored, and regenerating the Passport keys invalidates every
outstanding access/refresh token.

## 4. Create the Passport password-grant client (after first deploy)

The first deploy will boot with `PASSPORT_PASSWORD_GRANT_CLIENT_ID/SECRET`
still empty — login will fail until this step is done, then those two
vars are set and the service redeploys:

```bash
railway run php artisan passport:client --password --name="Garments ERP SPA"
```

Copy the printed Client ID/Secret into the env vars from step 2.

## 5. Health check

`railway.json` points Railway's healthcheck at `/api/v1/health`, which
this app already exposes (see `backend/SETUP.md` step 5) and returns
`{"data":{"status":"ok",...}}` with no auth required.

## Known limitation

`php artisan serve` is a development-grade server; it works fine for
Railway's single-container model at this project's current scale, but
isn't a production-hardened app server (no worker pooling). If traffic
grows, swap `deploy/start.sh`'s final line for `php artisan octane:start`
(needs `laravel/octane` added to composer.json first) or a proper
php-fpm + nginx/Caddy setup — not done here to avoid adding an
unverified dependency in an environment that can't run
`composer install` to confirm it resolves (see `backend/SETUP.md`'s
Packagist-access caveat).
