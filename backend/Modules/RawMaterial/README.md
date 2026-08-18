# Raw Material Module

## What this solves

Fabric and every trim/packaging consumable — PRD v2 §3.19. Replaces v1's
Accessory List/Accessory Orders.

## Main entities

- `App\Models\RawMaterial` — master record: `name`, `category`
  (`fabric`/`trim`/`packaging`/`other`), `unit` (plain string — see
  "Deliberate defaults" below), `reorder_level`, `default_supplier_id`,
  `unit_cost`, `is_active`. **No `current_stock` column** — see below.
- `App\Models\RawMaterialStockMovement` — the stock ledger. **Immutable,
  append-only** (sdd.md §5): created exclusively through
  `App\Services\RawMaterialStockService`, never directly. `quantity` is
  signed (+in/-out); `type` (`receipt`/`issue`/`adjustment`) is for
  reporting only, never sign logic.
- `App\Models\RawMaterialPurchaseOrder` / `RawMaterialPurchaseOrderItem`
  — PO header + lines, `po_no` auto-generated `PO-YYYY-NNNN` (same
  race-safe per-year-sequence pattern as Modules/Shipment's invoice_no).

## Stock is always computed, never stored

`RawMaterial::stockOn(?Location $location)` sums
`raw_material_stock_movements` (optionally scoped to one location).
There is no `current_stock` column to drift out of sync — this is the
same principle sdd.md §5 states for finished goods, applied here too.
`RawMaterialResource` only includes `current_stock` when the caller
passes `?with_stock=1` (and optionally `&location_id=`), so the default
list endpoint doesn't sum the ledger for every row on every page load.

## Ledger is append-only

`RawMaterialStockMovement` has no update/destroy route. A wrong entry is
corrected by posting a new `adjustment` movement with the opposite sign,
never by editing the mistaken row — the ledger is a permanent audit
trail, not working memory. `App\Services\RawMaterialStockService` is the
only code path allowed to create a row; it also enforces PRD v2 §3.21's
"raw material is factory/store-scoped only" rule (showroom locations are
rejected) in one place instead of at every call site.

## Purchase order receiving

Partial receipt is supported (PRD v2 §3.19): `POST
/raw-material-purchase-orders/{po}/receive` accepts a subset of the PO's
items, posts a `receipt` movement per item via
`RawMaterialStockService::receipt()`, bumps each item's cached
`quantity_received`, and recomputes the PO's overall `status`
(`ordered` → `partially_received` → `received`). Over-receiving beyond
an item's outstanding quantity is rejected with a 422, not silently
allowed to overshoot.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET/POST/PUT/DELETE | `/api/v1/raw-materials[/{rawMaterial}]` | Raw material master CRUD | `permission:raw-material.*` |
| GET | `/api/v1/raw-material-movements` | Ledger, filterable by material/location/type | `permission:raw-material.view` |
| POST | `/api/v1/raw-material-movements` | Post a manual `adjustment` | `permission:raw-material.edit` |
| GET/POST | `/api/v1/raw-material-purchase-orders[/{purchaseOrder}]` | PO list/create | `permission:raw-material.purchase-order.manage` |
| POST | `/api/v1/raw-material-purchase-orders/{purchaseOrder}/receive` | Post a (partial) receipt | `permission:raw-material.purchase-order.manage` |

## Depends on / depended on by

- Depends on: Party (`default_supplier_id`/PO `supplier_id`, must be
  `type=supplier`), Location (movements/PO receiving location, must be
  `factory`/`store`).
- Depended on by: Modules/Production (Cutting issues fabric via
  `RawMaterialStockService::issue()`), Modules/Subcontract (outward
  issue of raw material to a subcontractor, Phase 5).

## Deliberate defaults (not PRD-specified, documented here)

- `unit` is a plain string, not a `unit_id` FK — no dedicated Units
  module was scaffolded (sdd.md §2's module list has none), and a whole
  module for what's effectively a 5-row lookup (kg/meter/pcs/cone/roll)
  would be over-engineering. Same call as Order's `bank_account_name`.
- Reorder alerts (`GET /raw-materials?low_stock_only=1`) filter
  in-memory after the paginated query rather than in SQL, since the
  comparison is against a computed ledger sum, not a column — fine at
  the capped page size (100), but not something to build a dashboard
  widget on top of without reconsidering the approach at real scale.
