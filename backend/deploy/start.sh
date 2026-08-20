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

echo "==> php artisan migrate --force"
php artisan migrate --force

echo "==> php artisan db:seed --force"
php artisan db:seed --force

echo "==> php artisan storage:link (idempotent, ignores existing link)"
php artisan storage:link || true

PORT="${PORT:-8000}"
echo "==> booting on 0.0.0.0:${PORT}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
