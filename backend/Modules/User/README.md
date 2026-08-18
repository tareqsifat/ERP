# User Module

## What this solves

Role-segmented user directories — PRD v1 §3.8 / §4.7 (User Management:
Admin, Buyer, Merchandiser, Commercial, Accountant, Production) extended
by PRD v2 §6 with the factory-floor and location roles (Cutting Master,
Line Supervisor, Store Keeper × 2, Showroom Staff). Self-service profile
editing (PRD v1 §3.16 "My Profile").

## Main entities

- `App\Models\User` — `name`, `email`, `phone`, `location_id` (nullable
  FK to `locations`, added once Modules/Location exists — see
  sdd.md §4 on location-scoping), `is_active`, soft-deletable.
- Roles/permissions live in spatie/laravel-permission's tables
  (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`),
  seeded from `database/seeders/RoleSeeder.php` and `PermissionSeeder.php`.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/users` | Paginated, searchable, role-filterable user list | `permission:user.view` |
| GET | `/api/v1/users/{user}` | Single user | `permission:user.view` |
| POST | `/api/v1/users` | Create a user + assign a role | `permission:user.create` |
| PUT | `/api/v1/users/{user}` | Update a user, including role/location/active status | `permission:user.edit` |
| DELETE | `/api/v1/users/{user}` | Soft-delete a user | `permission:user.delete` |
| PATCH | `/api/v1/users/me` | Self-service profile update (name/email/phone/password only) | `auth:api` only |

## Depends on / depended on by

- Depends on: Auth module (`auth:api` guard), spatie/laravel-permission.
  `location_id` will reference Modules/Location once that module's
  migration adds the foreign key (Phase 4).
- Depended on by: every module that records "who did this" (Orders'
  Merchandiser, Cut Tickets' Cutting Master, vouchers' Transaction By,
  etc.).

## Security notes (see failed_doc.md §2)

- `StoreUserRequest`/`UpdateUserRequest` (role assignment) are reachable
  **only** through the Admin-gated `/users` endpoints.
- `UpdateProfileRequest` (self-service `/users/me`) has no `role`,
  `location_id`, or `is_active` field in its validation rules at all —
  a user cannot escalate their own role by adding those keys to the
  request body, because the controller only mass-assigns
  `->only([...])` of the *validated* data, never `$request->all()`.
