# Shipment Module

## What this solves

Shipment invoice tracking — PRD v1 §3.6 ("Shipments List / Add New
Shipment: Tracks shipment invoices (format SHIP-YYYY-NNNN) per order,
including creator, total quantity, and total CBM").

## Main entities

- `App\Models\Shipment` — `invoice_no` (auto-generated, see below),
  `year`, `sequence_no`, `order_id`, `created_by` (always the
  authenticated user, never client-supplied), `total_quantity`,
  `total_cbm`, `shipment_date`, `status`
  (`draft`/`shipped`/`delivered`), `remarks`.

## Invoice number generation

`SHIP-YYYY-NNNN`, where `NNNN` resets to `0001` at the start of each
calendar year — unlike Order's `order_no` (derived purely from the row's
own auto-increment `id`), this can't be purely id-derived since the
sequence resets. `App/Services/ShipmentInvoiceNumberGenerator` computes
the next sequence via `MAX(sequence_no) WHERE year = ?` inside a DB
transaction with `lockForUpdate()`, so two concurrent shipment creations
in the same year can't both read the same max and collide. `year` /
`sequence_no` are stored as their own columns (not parsed back out of
`invoice_no`) so that lookup is a fast indexed query.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/shipments` | Paginated, order/status-filterable shipment list | `permission:shipment.view` |
| GET | `/api/v1/shipments/{shipment}` | Single shipment with order, creator | `permission:shipment.view` |
| POST | `/api/v1/shipments` | Create a shipment invoice (auto-numbered) | `permission:shipment.create` |
| PUT | `/api/v1/shipments/{shipment}` | Update a shipment (order_id and invoice_no are not editable) | `permission:shipment.edit` |
| DELETE | `/api/v1/shipments/{shipment}` | Soft-delete a shipment | `permission:shipment.delete` |

## Depends on / depended on by

- Depends on: Order (`order_id`), User (`created_by`).
- Depended on by: nothing yet; conceptually Modules/FinishedGoods (Phase
  4) will eventually deduct dispatched stock when a shipment ships, but
  that link isn't wired up until Phase 4.
