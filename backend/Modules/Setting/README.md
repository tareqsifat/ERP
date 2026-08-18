# Modules/Setting

PRD v1 §3.15/§4.13 — "Administrative configuration covering Currency
Settings (default currency/format), Notifications (system alert
preferences), System Settings (spread across multiple tabs for broader
application configuration), and Company Settings (company name/
branding, shown here as 'Vishesh Textiles', logo, and contact
details)."

## Design

A single `settings` table, one row per `key` (e.g. `currency.code`,
`company.name`), each tagged with a `group` (`currency` / `notification`
/ `system` / `company`) matching the Settings page's four tabs. A
key/value store rather than one table per group — PRD v1 only describes
a handful of admin-tunable fields per tab, not a relational domain each
— so adding a new field later (e.g. a fifth notification toggle) is a
`SettingSeeder` line, not a migration.

- `App\Services\SettingService` — the only writer. `get($key, $default)`
  is safe to call from anywhere in the app (e.g. a future feature
  reading `notification.low_stock_alerts` before sending an alert)
  without depending on the HTTP layer; `set()` is an idempotent
  `updateOrCreate`; `allGrouped()` returns the `{group: {shortKey:
  value}}` shape the Settings page's four tabs render directly.
- `App\Http\Controllers\SettingController` — `GET /settings` (any
  authenticated user — the app's own UI needs the currency format/
  company name regardless of role) and `PUT /settings` (bulk-upsert one
  group at a time, gated by `setting.manage`).
- `database/seeders/SettingSeeder.php` — seeds sane defaults
  (BDT currency, Asia/Dhaka timezone, "Vishesh Textiles" company name)
  so the Settings page never renders empty on a fresh install. Called
  from the top-level `DatabaseSeeder`.

## Known simplifications

- `setting.manage` is granted only to Admin (`RoleSeeder::$roleGrants`)
  — PRD v1 doesn't describe any other persona touching Settings, and
  "Super Admin — Full access" (PRD v2 §1) is the natural owner of
  application-wide configuration.
- No file upload wiring for `company.logo_path` yet (stored as a plain
  string/null) — the frontend can start posting a path once a logo
  upload endpoint exists; nothing here blocks adding one later, same
  pattern as Party/Employee's existing image uploads.
- Notification *preferences* are stored and readable via
  `SettingService::get()`, but no notification-*sending* mechanism
  exists anywhere in the app yet (PRD v1 §3.15 only describes the
  settings page itself, not a notification delivery pipeline) — out of
  scope until/unless a future phase adds one.

## My Profile (PRD v1 §3.16/§4.14)

Lives in `Modules\User`, not here — `GET /me` (Modules/Auth,
already built in Phase 2) returns the logged-in user, and
`PATCH /users/me` (`Modules\User\App\Http\Controllers\
ProfileController::update()`, already built in Phase 2) lets them edit
their own name/email/phone/password. Phase 7 only adds the frontend
page for it (`frontend/src/modules/user/views/ProfileView.vue`) — no
new backend work was needed.
