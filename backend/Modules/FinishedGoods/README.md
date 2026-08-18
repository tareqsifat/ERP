# Finished Goods Module

## What this solves

Finished Goods Inventory — PRD v2 §3.20. Read-only from the outside:
stock only ever changes as a side effect of three other modules' actions
(QC pass, Stock Transfer, Shipment), never a direct write through this
module's own API.

## Main entity

- `App\Models\FinishedGoodsMovement` — the stock ledger (sdd.md §5:
  ledger is the source of truth, same pattern as
  Modules/RawMaterial\App\Models\RawMaterialStockMovement). **Immutable,
  append-only**, created exclusively through `App\Services\
  FinishedGoodsStockService`. `quantity` is always ±1 per row — one row
  per physical piece movement — since `piece_serial_id` links each row
  back to the exact unit that moved ("each finished-goods unit retains a
  link back to its originating serial number(s) for full audit trail",
  PRD v2 §3.20). `piece_serial_id` is nullable only for inward-
  subcontract completions that don't go through the factory's own
  Cutting flow (Phase 5). `reference` (polymorphic) points back to the
  StockTransfer/Shipment/etc. record that caused the movement.

## There is no `finished_goods_stock` table

Exactly like Raw Material: stock at a `{location, order, style, color,
size}` combination is always `SUM(quantity)` over the ledger, computed
live in `FinishedGoodsController::stock()` via a grouped `SUM()` /
`GROUP BY` / `HAVING SUM(quantity) > 0` query — there is no stored,
mutable balance column to drift out of sync.

## App\Services\FinishedGoodsStockService — the only writer

- `intakeFromQc()` — called by `Modules\Production\App\Services\
  QcService::pass()`. +1, `type = qc_intake`.
- `transferOut()` / `transferIn()` — called by `Modules\Location\App\
  Services\StockTransferService`. Signed by the caller's intent
  (transferOut is always negative, transferIn always positive) rather
  than trusting a signed quantity from outside this service.
- `shipment()` — deducts on ship (Phase 4 wiring into Modules/Shipment;
  see that module's README for the call site).
- `stockOf()` — the live `SUM()` read helper other modules call to check
  availability before dispatching (e.g. StockTransferService rejects a
  dispatch that would exceed what `stockOf()` reports).

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/finished-goods/stock` | Grouped, non-zero stock aggregate; filter by `location_id`/`order_id` | `permission:finished-goods.view` |
| GET | `/api/v1/finished-goods/movements` | Raw ledger, filterable by `location_id`/`order_id`/`type` | `permission:finished-goods.view` |

## Depends on / depended on by

- Depends on: Location (movement location), Order (movement order),
  Production (`PieceSerial` link on QC intake).
- Depended on by: Modules/Location (StockTransfer dispatch/receive),
  Modules/Shipment (deducts stock on ship), Modules/Production
  (QcService is the only path stock enters this ledger at all).
