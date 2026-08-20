#!/usr/bin/env bash
# Railway boot script. Every step here is safe to run on every deploy
# and every container restart:
#   - `migrate --force` only applies migrations not yet in the
#     migrations table (Laravel tracks this itself).
#   - `db:seed --force` is safe to re-run: PermissionSeeder/RoleSeeder/
#     LocationSeeder/SettingSeeder use idempotent
#     firstOrCreate/updateOrCreate patterns, AdminUserSeeder skips if
#     the admin already exists (and refuses to create a default admin
#     in production unless ADMIN_SEED_PASSWORD is set explicitly), and
#     DemoDataSeeder checks its own idempotency marker and refuses to
#     run in production unless DEMO_SEED_FORCE=1 is set. See
#     database/seeders/*.php for each seeder's own guard.
# See ../RAILWAY.md for the full deploy runbook and required env vars.
set -euo pipefail

echo "==> php artisan config:cache"
php artisan config:cache

# One-time recovery switch: set MIGRATE_FRESH=1 to drop and recreate
# every table instead of the normal incremental migrate. Only ever set
# this deliberately (e.g. to recover a migrations-table/schema
# mismatch on a database with no real data yet) and unset it again
# right after - it is destructive on any database with real rows.
if [ "${MIGRATE_FRESH:-}" = "1" ]; then
  echo "==> MIGRATE_FRESH=1 set - php artisan migrate:fresh --force (DESTRUCTIVE, one-time recovery only)"
  php artisan migrate:fresh --force
else
  echo "==> php artisan migrate --force"
  php artisan migrate --force
fi

echo "==> php artisan db:seed --force"
php artisan db:seed --force

echo "==> php artisan storage:link (idempotent, ignores existing link)"
php artisan storage:link || true

# First-boot only: create the Passport password-grant client the SPA
# needs to log in (RAILWAY.md step 4). Guarded on the env var being
# empty so this never runs twice. The Client ID/Secret are only
# printed to this boot's logs — copy them into
# PASSPORT_PASSWORD_GRANT_CLIENT_ID/SECRET as real env vars afterward;
# until that's done, every login attempt will fail (expected, one-time).
if [ -z "${PASSPORT_PASSWORD_GRANT_CLIENT_ID:-}" ]; then
  echo "==> no PASSPORT_PASSWORD_GRANT_CLIENT_ID set yet - creating the Passport password-grant client"
  php artisan passport:client --password --name="Garments ERP SPA" --no-interaction || true
  echo "==> copy the Client ID/Secret above into PASSPORT_PASSWORD_GRANT_CLIENT_ID/SECRET env vars, then redeploy"
fi

PORT="${PORT:-8000}"
echo "==> booting on 0.0.0.0:${PORT}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
