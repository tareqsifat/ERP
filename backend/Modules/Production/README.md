# Production Module

## What this solves

Cutting, Sewing, QC, and the Machine/Line register — PRD v2 §3.17/§3.18/
§3.22. This is the traceability spine of the whole system (sdd.md §5):
every physical garment piece is identified by a unique serial from the
moment it's cut until it ships, and every module downstream (Finished
Goods, Stock Transfer, Shipment, Reporting) keys off that serial or the
ledger rows it produces.

## Main entities

- `App\Models\Line` / `Machine` — the floor register. Assignment/
  tracking only, not real-time IoT monitoring (explicitly Out of Scope,
  PRD v2 §7).
- `App\Models\CutTicket` — created per style/color/lay against an
  approved Order (+ optional Booking). Starts `draft` (freely editable,
  no stock/serial impact) until `POST .../finalize`, the one
  irreversible action in this module.
- `App\Models\Bundle` — generated automatically when a Cut Ticket is
  finalized, sized to `bundle_size` (last bundle gets the remainder).
  Never created by a client request.
- `App\Models\PieceSerial` — the traceability spine itself. Pattern
  `{OrderNo}-{Style}-{Color}-{CutDate:YYMMDD}-{BundleSeq}-{PieceSeq}`
  (PRD v2 §3.17), unique + indexed (sdd.md §5's explicit call-out: "get
  the index right from migration #1"). Generated exclusively by
  `App\Services\CuttingService::finalize()`.

## Bundle status vs. piece status

`Bundle.status` (`cut` → `in_sewing` → `sewn`) is a bulk convenience
cache updated by `App\Services\SewingService` when a whole bundle is
assigned to a line or has its output logged. It is **not** the source of
truth once QC has run, because QC operates per piece
(`App\Services\QcService`) and pieces within one bundle can diverge —
some pass, some reject. `PieceSerial.status` is always the authoritative
per-unit state (`cut` → `in_sewing` → `sewn` → `qc_passed`/`qc_rejected`
→ `finished_goods` → `shipped`); anything that needs to know a specific
piece's real state reads `PieceSerial`, not `Bundle`.

## The finalize transaction (CuttingService)

`CuttingService::finalize()` runs entirely inside one DB transaction: it
(1) deducts `fabric_consumed` from Raw Material stock at the ticket's
factory location via `RawMaterialStockService::issue()`, (2) generates
Bundles sized to `bundle_size`, and (3) generates one PieceSerial per
piece. A Cut Ticket is either fully finalized (stock deducted + every
bundle + every serial exists) or not finalized at all — never half-done.
Calling finalize twice is rejected with a 422 (idempotency guard); this
matters because a second call would double-deduct fabric and generate
duplicate bundles/serials with colliding serial numbers.

## QC closes the loop into Finished Goods

`App\Services\QcService::pass()` moves a piece to `qc_passed`, calls
`Modules\FinishedGoods\App\Services\FinishedGoodsStockService::
intakeFromQc()` to post the Finished Goods ledger entry, then sets the
piece to `finished_goods` — "QC-passed pieces are received into
Finished Goods Inventory... closing the traceability loop from cut piece
to finished unit" (PRD v2 §3.18). `reject()` just records the reason;
no stock movement happens. A piece that has already been QC-ed cannot be
QC-ed again (422) — this is what stops a repeated "pass" call from
double-posting a Finished Goods intake.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET/POST/PUT/DELETE | `/api/v1/lines[/{line}]` | Line register CRUD | `permission:machine.*` |
| GET/POST/PUT/DELETE | `/api/v1/machines[/{machine}]` | Machine register CRUD | `permission:machine.*` |
| GET/POST/PUT/DELETE | `/api/v1/cut-tickets[/{cutTicket}]` | Cut Ticket CRUD (edit/delete only while `draft`) | `permission:production.cutting.*` |
| POST | `/api/v1/cut-tickets/{cutTicket}/finalize` | Deduct fabric, generate bundles + serials | `permission:production.cutting.create` |
| GET | `/api/v1/bundles` | Bundle list, filterable by cut ticket/line/status | `permission:production.sewing.view` |
| POST | `/api/v1/bundles/{bundle}/assign-to-line` | Move a bundle (+ its pieces) to `in_sewing` | `permission:production.sewing.create` |
| POST | `/api/v1/bundles/{bundle}/log-output` | Move a bundle (+ its pieces) to `sewn` | `permission:production.sewing.create` |
| GET | `/api/v1/piece-serials` | Traceability lookup — filter by exact `serial`, `order_id`, `bundle_id`, `status` | `permission:production.trace.view` |
| POST | `/api/v1/piece-serials/{pieceSerial}/qc` | Record pass/reject | `permission:production.qc.record` |

## Depends on / depended on by

- Depends on: Order/Booking (Cut Ticket source), RawMaterial (fabric
  issue on finalize), Location (Cut Ticket must be a `factory`; QC pass
  intake location must be a `store`).
- Depended on by: Modules/FinishedGoods (QC pass is the only way stock
  enters the Finished Goods ledger), Modules/Subcontract (Phase 5 reuses
  Cutting/Sewing/QC for inward jobs, tagged separately from own stock).

## Known simplifications (documented, not bugs)

- **Single primary fabric per Cut Ticket** — `raw_material_id` +
  `fabric_consumed` is one fabric, not a multi-material bill-of-
  materials. Trims (buttons, thread, zippers, etc.) are **not** deducted
  at cutting time in v1 — only the fabric is.
- **Sewing is two lifecycle timestamps, not a daily-log ledger.** PRD v2
  §3.18 describes recurring daily input/output logging; this module
  simplifies that to `assigned_to_line_at` / `line_output_at` on the
  Bundle — a bundle is assigned once, then logged as output once, which
  satisfies the traceability requirement without a bookkeeping table for
  an event that in practice happens twice per bundle.
- **QC intake location is caller-supplied, not auto-detected.** The API
  requires an explicit `store`-type `location_id` on pass rather than
  guessing a single "Main Store" by name/convention, since nothing in
  the schema marks one store as canonical (the client seeds may have
  more than one store location in practice).
