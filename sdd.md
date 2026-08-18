# Software Design Document (SDD)

## Garments ERP — Laravel API + Vue Admin

Companion to `PRD_GarmentsERP_v2.md`. This document is what Claude
Code should follow when scaffolding and building the project.

---

## 1. Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | Laravel (latest stable) | API-only, no Blade views |
| Modularization | `nwidart/laravel-modules` | One Laravel package/module per business domain |
| Auth | Laravel Passport | OAuth2, password grant for the admin SPA + personal access tokens |
| DB | MySQL (latest stable) | InnoDB, utf8mb4 |
| RBAC | `spatie/laravel-permission` | Roles/permissions per §6 of PRD v2, not hardcoded enums |
| Frontend | Vue 3 (latest) + Vite | Composition API, `<script setup>` |
| State | Pinia | One store per module, matching backend module boundary |
| Routing | Vue Router | Module-based route files, lazy-loaded |
| HTTP | Axios | Single instance, interceptor for token refresh + 401 → logout |
| Styling | Tailwind (or existing admin theme, TBD by whoever picks the UI kit) | Not prescribed here — pick during scaffolding |
| Backend tests | Pest (or PHPUnit) | Feature tests per module |
| Frontend tests | Vitest | Component + store smoke tests |
| File storage | Laravel filesystem, `local` in dev / `s3`-compatible in prod | Item images, ID docs, avatars |

---

## 2. Repository Layout

```
garments-erp/
├── backend/                      # Laravel app (API only)
│   ├── app/
│   │   └── Http/Controllers/Api/  # Only truly cross-module controllers, if any
│   ├── Modules/
│   │   ├── Auth/
│   │   ├── Order/
│   │   ├── Booking/
│   │   ├── Budgeting/
│   │   ├── Costing/
│   │   ├── Sampling/
│   │   ├── Shipment/
│   │   ├── Production/            # Cutting, sewing output, serials, machines/lines
│   │   ├── RawMaterial/
│   │   ├── FinishedGoods/
│   │   ├── Location/              # Factory/Store/Showroom + stock transfer
│   │   ├── Subcontract/           # Outward + Inward
│   │   ├── Accounting/            # Bank, Cash, Cheque, Vouchers, Ledger, Cashbook
│   │   ├── Party/                 # Buyer/Supplier/Subcontractor
│   │   ├── User/                  # Role-segmented user directories
│   │   ├── Hrm/                   # Designations, Employees, Salaries
│   │   ├── Report/
│   │   └── Setting/
│   ├── routes/
│   │   └── api.php                # Includes each Module's routes/api.php — see §3
│   └── ...standard Laravel dirs
│
├── frontend/                     # Vue 3 + Vite admin SPA
│   └── src/
│       ├── modules/
│       │   ├── order/
│       │   │   ├── views/
│       │   │   ├── components/
│       │   │   ├── store.js       # Pinia store for this module
│       │   │   ├── api.js         # axios calls scoped to this module
│       │   │   └── routes.js      # Vue Router routes for this module
│       │   ├── booking/
│       │   ├── production/
│       │   ├── raw-material/
│       │   ├── finished-goods/
│       │   ├── location/
│       │   ├── subcontract/
│       │   ├── accounting/
│       │   ├── party/
│       │   ├── user/
│       │   ├── hrm/
│       │   ├── report/
│       │   └── setting/
│       ├── shared/                # axios instance, auth store, layout, guards
│       ├── router/index.js        # imports each module's routes.js
│       └── main.js
│
├── PRD_GarmentsERP_v2.md
├── sdd.md
├── failed_doc.md
├── todo.md
└── user_usage_guide.md
```

**Rule:** one Laravel Module ↔ one Vue `src/modules/<name>` folder ↔
one PRD feature-catalog section. Keep the mapping 1:1 so anyone
(including future-Claude Code) can find where a feature lives without
guessing.

---

## 3. API Routing Convention

`backend/routes/api.php` does **only** this — it never defines a
route itself:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require module_path('Auth', 'routes/api.php');
    require module_path('Order', 'routes/api.php');
    require module_path('Booking', 'routes/api.php');
    require module_path('Budgeting', 'routes/api.php');
    require module_path('Costing', 'routes/api.php');
    require module_path('Sampling', 'routes/api.php');
    require module_path('Shipment', 'routes/api.php');
    require module_path('Production', 'routes/api.php');
    require module_path('RawMaterial', 'routes/api.php');
    require module_path('FinishedGoods', 'routes/api.php');
    require module_path('Location', 'routes/api.php');
    require module_path('Subcontract', 'routes/api.php');
    require module_path('Accounting', 'routes/api.php');
    require module_path('Party', 'routes/api.php');
    require module_path('User', 'routes/api.php');
    require module_path('Hrm', 'routes/api.php');
    require module_path('Report', 'routes/api.php');
    require module_path('Setting', 'routes/api.php');
});
```

Each module's own `Modules/<Name>/routes/api.php` defines its
resource routes with `auth:api` (Passport) middleware and, where
relevant, `role:` / `permission:` middleware from
`spatie/laravel-permission`:

```php
Route::middleware(['auth:api'])->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store'])->middleware('permission:order.create');
    // ...
});
```

All responses use a consistent envelope (`{data, meta}` for
collections, `{data}` for single resources, `{message, errors}` for
4xx/5xx) via Laravel API Resources + a shared exception handler —
don't let each module invent its own shape.

---

## 4. Auth (Laravel Passport)

- Use the **Password Grant** for the admin SPA login (email +
  password → access token + refresh token), since this is a
  first-party client, not third-party OAuth.
- Access token short-lived (e.g. 1 hour); refresh token longer-lived
  (e.g. 2 weeks); axios interceptor auto-refreshes on 401 and retries
  the original request once.
- `spatie/laravel-permission` sits on top of Passport's authenticated
  user for role/permission checks — Passport handles *who you are*,
  spatie handles *what you can do*. Don't try to encode roles as
  Passport scopes; scopes are for third-party API clients, not
  internal RBAC.
- Location-scoping (Showroom Staff sees only their showroom) is
  **not** a role — it's a `location_id` on the user record, checked
  in policies/queries, separate from the role/permission layer.

---

## 5. Database Notes

- Every table gets `created_at`, `updated_at`, and `deleted_at`
  (soft deletes) — financial and traceability data should never be
  hard-deleted.
- Money columns: `decimal(15,2)`, never `float`/`double`.
- Piece Serial (`cut_pieces.serial`) is a unique, indexed string
  column — this is the traceability spine of the whole system; get
  the index right from migration #1.
- Stock balance (`raw_materials.current_stock`,
  `finished_goods_stock.quantity`) is **computed from the movement
  ledger**, not stored as a mutable column that drifts — either a DB
  view/generated column or a scheduled reconciliation job, so stock
  can never silently go wrong. Cache the computed value for read
  performance if needed, but the ledger is the source of truth.
- Foreign keys `restrict` on delete for anything referenced by
  financial or traceability records (you cannot delete a Party that
  has vouchers against it); `cascade` only for genuinely dependent
  child rows (e.g. order line items when the parent order itself is
  deleted — which itself should be soft-delete-only in practice).

---

## 6. Testing Strategy (see also `failed_doc.md`)

### Backend

- One Pest/PHPUnit feature test file per module, minimum: a smoke
  test that hits every route in that module's `routes/api.php` and
  asserts a non-5xx response with an authenticated user, plus at
  least one real assertion per core write endpoint (e.g. creating an
  Order actually persists the row and line items).
- A dedicated `AuthTest` covering: login issues a token, protected
  routes reject an unauthenticated request (401), and a route gated
  by `permission:` rejects a user without that permission (403).
- A dedicated `TraceabilityTest`: create an order → cut ticket →
  confirm serials generated → move through sewing → QC pass → assert
  it appears in Finished Goods Inventory with the serial chain
  intact.
- Run with `php artisan test` (or `pest`) — CI should fail the build
  on any red test.

### Frontend

- Vitest smoke test per module: the main list view mounts without
  throwing, and the Pinia store's core action (e.g. `fetchOrders`)
  resolves against a mocked axios response.
- `npm run build` must complete with zero errors — treat a failed
  Vite build as a release blocker, same severity as a failed backend
  test.

### "API running / Vue compiling" baseline check

Before anything else, Claude Code should be able to run and show
green for:

```bash
# backend
php artisan serve &
curl -s http://127.0.0.1:8000/api/v1/health   # simple unauthenticated health route

# frontend
npm run build
npm run dev &
curl -s http://127.0.0.1:5173
```

Add a trivial `GET /api/v1/health` route (no auth) in a lightweight
`Modules/Core` or directly in `routes/api.php` outside the module
loop, purely so there's a one-line way to prove the API is alive
without needing a token.

---

## 7. Per-Module Documentation

Every `Modules/<Name>/README.md` must answer, briefly:

1. What business problem this module solves (link back to the PRD
   section).
2. Its main entities/tables.
3. Its API endpoints (method, path, one-line purpose).
4. What other modules it depends on / is depended on by.

This is the file the PRD asked for ("in each module, create a md
file explaining what this module does") — one per Laravel module,
living inside that module's folder.

---

## 8. Non-Functional Requirements

- **Environments:** `.env.example` committed with every required
  key; no secrets in git, ever (see `failed_doc.md` §1).
- **Logging:** Laravel's default channel to file in dev; structured
  JSON logging recommended for anything beyond local dev.
- **Rate limiting:** Laravel's built-in throttle middleware on
  `auth` routes especially (login/token endpoints) to blunt
  credential-stuffing — see `failed_doc.md`.
- **File uploads:** validated by MIME type and size server-side
  (never trust the client extension), stored outside the public web
  root or via signed URLs — see `failed_doc.md` §3.
- **Migrations:** every schema change is a migration, never a manual
  DB edit — this matters especially for the stock-ledger tables where
  history is the whole point.
