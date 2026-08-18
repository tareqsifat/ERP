# Party Module

## What this solves

Buyer/Supplier directories — PRD v1 §3.10 / §4.9 / §6.3 — extended by
PRD v2 §4.9 with the `Subcontractor` party type (used by Modules/Subcontract,
Phase 5).

## Main entities

- `App\Models\Party` — `name`, `type` (`buyer`/`supplier`/`subcontractor`),
  `email`, `phone`, `address`, `country`, `opening_balance_type`
  (`debit`/`credit`), `opening_balance`, `remarks`, `image_path`,
  `is_active`, soft-deletable.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/parties` | Paginated, searchable, type-filterable party list | `permission:party.view` |
| GET | `/api/v1/parties/{party}` | Single party | `permission:party.view` |
| POST | `/api/v1/parties` | Create a party | `permission:party.create` |
| PUT | `/api/v1/parties/{party}` | Update a party | `permission:party.edit` |
| DELETE | `/api/v1/parties/{party}` | Soft-delete a party | `permission:party.delete` |

## Depends on / depended on by

- Depends on: Auth module (`auth:api` guard).
- Depended on by: Order (`party_id`, buyer), Modules/Subcontract (Phase 5,
  subcontractor party), Modules/Accounting (Phase 6, vouchers/ledger
  reference a party).

## Known gaps (deliberate, not oversights)

- **No portal login.** PRD v1 §5.3 showed a Party Email/Password form
  field; PRD v2 §7 explicitly moves self-service Buyer/Supplier/
  Subcontractor portal logins to **Out of Scope for v1** ("party records
  exist, but no external-facing login in v1"). This table has no
  `password` column on purpose — `email`/`phone` here are plain contact
  fields, nothing more.
- **No `total_bill`/`advance`/`paid`/`due`/`balance` columns.** PRD v1
  §6.3 lists these as "System-computed". sdd.md §5 already establishes
  the rule that a running total should be computed from a movement
  ledger, not stored as a column that can drift — the same principle
  applies here to money as it does to stock. Those figures depend on
  Modules/Accounting vouchers (Phase 6) and Order values (this phase);
  they'll be added as a computed summary (e.g. `GET
  /api/v1/parties/{party}/summary`) once vouchers exist, not before.
  `opening_balance` is the one exception — it's a real one-time input
  captured at party creation, not a running total, so it's a stored
  column.
- Image upload is validated server-side by MIME type and file size
  (never the client-sent extension) and stored on the `local` disk
  outside the public web root (sdd.md §8) — there is no public URL for
  it yet; a signed-URL download route will be added when the frontend
  needs to actually display party images.
