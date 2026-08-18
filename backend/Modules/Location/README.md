# Location Module

## What this solves

Models the client's physical footprint — PRD v2 §3.21 / §4.1 — and the
Stock Transfer workflow that moves Finished Goods between locations.

## Main entities

- `App\Models\Location` — `name`, `type` (`factory`/`store`/`showroom`),
  `address`, `is_active`. Seeded (Phase 4) with 1 Factory, 1 Main Store,
  3 Showrooms — generic placeholder names, rename via the UI once the
  client confirms real showroom names.
- `App\Models\StockTransfer` — two-step dispatch/receive of Finished
  Goods between locations. One row per `{order, style, color, size}`
  transferred (no line-item table — a transfer of multiple style/color/
  size combinations in one physical shipment is simply several
  StockTransfer rows sharing the same `dispatched_at` moment, matching
  how Finished Goods stock itself is keyed). `App\Services\
  StockTransferService` is the only code that writes this table —
  `dispatch()` posts a `transfer_out` movement immediately (see
  Modules/FinishedGoods/README.md for the ledger mechanics; this module
  only owns the transfer *workflow*, not the stock ledger itself);
  `receive()` posts `transfer_in` for the **actually received**
  quantity, which may differ from `quantity_dispatched` — a short/over
  receipt sets `status = discrepancy` rather than silently reconciling.
  `transfer_no` follows the same `PREFIX-YYYY-NNNN` race-safe pattern as
  Shipment's `invoice_no` and RawMaterial's `po_no`.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/locations` | Paginated, type-filterable location list | `permission:location.view` |
| GET | `/api/v1/locations/{location}` | Single location | `permission:location.view` |
| POST | `/api/v1/locations` | Create a location | `permission:location.create` |
| PUT | `/api/v1/locations/{location}` | Update a location | `permission:location.edit` |
| DELETE | `/api/v1/locations/{location}` | Soft-delete a location | `permission:location.delete` |
| GET | `/api/v1/stock-transfers` | Paginated stock transfer list | `permission:stock-transfer.view` |
| GET | `/api/v1/stock-transfers/{stockTransfer}` | Single transfer with items | `permission:stock-transfer.view` |
| POST | `/api/v1/stock-transfers` | Dispatch a transfer (deducts source FG stock) | `permission:stock-transfer.dispatch` |
| POST | `/api/v1/stock-transfers/{stockTransfer}/receive` | Confirm receipt (adds destination FG stock; short/over receipt flags `discrepancy`) | `permission:stock-transfer.receive` |

## Depends on / depended on by

- Depends on: User (`dispatched_by`/`received_by`), Modules/FinishedGoods
  (stock movement ledger — see that module's README for how dispatch/
  receive actually move stock).
- Depended on by: User.location_id (Showroom Staff location-scoping,
  sdd.md §4), Modules/RawMaterial (movements are location-scoped to
  factory/store), Modules/FinishedGoods (stock is location-scoped).

## Raw material is factory/store-scoped only

Per PRD v2 §3.21: "Raw material is factory/store-scoped only in v1 —
showrooms don't hold raw material." Enforced in
Modules/RawMaterial's StoreStockMovementRequest-equivalent validation,
not here — noted for cross-reference.
