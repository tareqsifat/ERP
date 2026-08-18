# Auth Module

## What this solves

Authentication for the admin SPA — PRD v1 §8 ("Login/Logout Flow: the
application requires authentication before any dashboard route is
accessible"). Implements sdd.md §4: Laravel Passport's Password Grant,
executed server-side so the OAuth client secret never ships to the
browser.

## Main entities

This module owns no database tables of its own — it operates on
`App\Models\User` (see the User module) and Passport's `oauth_*` tables
(migrated from `database/migrations/2025_01_01_00001x_*`).

## API endpoints

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/v1/auth/login` | Email+password → access/refresh token pair. Rate-limited (`throttle:login`). |
| POST | `/api/v1/auth/refresh` | Refresh token → new access/refresh token pair. Rate-limited. |
| POST | `/api/v1/auth/logout` | Revokes the caller's current access + refresh token. Requires `auth:api`. |
| GET | `/api/v1/auth/me` | Current user + roles + permissions. Requires `auth:api`. |

## Depends on / depended on by

- Depends on: `App\Models\User` (HasApiTokens, HasRoles), the Passport
  password-grant Client (created via `passport:client --password`, see
  `backend/SETUP.md`).
- Depended on by: every other module — all of them gate their routes with
  `auth:api` and, where relevant, `permission:*` (spatie/laravel-permission,
  seeded in `database/seeders/RoleSeeder.php` / `PermissionSeeder.php`).

## Security notes (see failed_doc.md §1)

- Access tokens expire in 1 hour, refresh tokens in 14 days
  (`App\Providers\AppServiceProvider::boot()`).
- Logout revokes both the access token and its refresh token
  (`AuthController::logout()` → `TokenRepository`/`RefreshTokenRepository`).
- Login never reveals whether an email exists — invalid credentials and a
  deactivated account both return the same generic validation error.
