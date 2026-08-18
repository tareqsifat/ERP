# Order Module

## What this solves

Order intake and management — PRD v1 §3.1 / §4.2 / §4.3 / §6.1 (Order List,
Add New Order form, dynamic line-item table with auto-calculated grand
total).

## Main entities

- `App\Models\Order` — header fields (party, merchandiser, fabric spec,
  shipment/payment mode, dates, ports, terms, remarks, status), plus
  `order_no` (auto-generated, see below) and `grand_total` (cached, always
  recomputed from line items).
- `App\Models\OrderLineItem` — `style`, `color`, `item`, `shipment_date`,
  `quantity`, `unit_price`, `total_price` (server-computed).

## Order number generation

`order_no` is derived from the row's own auto-increment `id`, zero-padded
to 7 digits (PRD v1 §6.1 example: `0000012`) — see
`App/Services/OrderNumberGenerator`. This is race-condition-free by
construction (no "read max + 1" window between two concurrent creates)
at the cost of reflecting `id` gaps if a row is ever hard-deleted; since
orders are soft-delete only in practice (sdd.md §5), that doesn't come up
in normal operation.

## Grand total

`grand_total` is a cached column recomputed from `order_line_items` (never
accepted directly from client input — sdd.md §5's "ledger is the source of
truth" principle applied to money) every time line items are created,
replaced, or an order is loaded fresh. Each line item's `total_price` is
likewise always server-computed as `quantity * unit_price`; a client-sent
`total_price` in the request body is silently ignored.

## Line item update semantics

`PUT /orders/{order}` with a `line_items` array present performs a **full
replace**: existing line items are soft-deleted and the new set is
created. This form has no per-row ids for the client to reference an
individual existing line item, so partial/patch-style line-item edits
aren't supported yet — replace-the-whole-set was the simplest option that
matches the PRD's flat intake-form UI. Omit `line_items` entirely to
update only header fields.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/orders` | Paginated, filterable (status/party/season/search/date range) order list | `permission:order.view` |
| GET | `/api/v1/orders/{order}` | Single order with party, merchandiser, line items | `permission:order.view` |
| POST | `/api/v1/orders` | Create an order + its line items | `permission:order.create` |
| PUT | `/api/v1/orders/{order}` | Update header fields and/or replace line items | `permission:order.edit` |
| DELETE | `/api/v1/orders/{order}` | Soft-delete an order | `permission:order.delete` |

## Depends on / depended on by

- Depends on: Party (`party_id`, must be `type=buyer` — validated), User
  (`merchandiser_id`).
- Depended on by: Booking, Budgeting, Costing, Sampling, Shipment (all
  reference `order_id`), and eventually Production (cutting is scoped to
  an order + booking).

## Deliberate defaults (not PRD-specified, documented here)

- `shipment_mode` enum: `sea` / `air` / `sea_air` / `road` / `courier`.
- `payment_mode` enum: `lc` (Letter of Credit) / `tt` (Telegraphic
  Transfer) / `advance` / `on_delivery`. These are standard garment-export
  terms; the PRD names the fields but doesn't enumerate values. If the
  client uses different terminology, this is a one-migration change —
  flag it during the Phase 3 review.
- `bank_account_name` is a plain string for now, not a foreign key —
  Modules/Accounting's Bank Accounts table doesn't exist until Phase 6.
  It'll become a real `bank_account_id` FK then; this is a known,
  intentional temporary gap.
