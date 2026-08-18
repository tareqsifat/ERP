# Subcontract Module

## What this solves

Outward and Inward job-work with external subcontractors — PRD v2
§3.23/§3.24/§4.8/§4.9. Outward: we hand off cutting/sewing capacity to a
subcontractor, either already-cut pieces or raw material. Inward: an
external party hands *us* job-work capacity — their fabric, our floor.
Both directions share one shape (`App\Models\SubcontractOrder`,
`direction` is the only structural difference), because the same
questions apply either way: what was issued, what came back, what's it
worth.

## Main entities

- `App\Models\SubcontractOrder` — `subcontract_no` (`SC-YYYY-NNNN`, same
  race-safe per-year-sequence pattern as Shipment/RawMaterial/
  StockTransfer's number generators). `status` moves
  `open` → `partially_returned` → `closed` as pieces resolve (Outward) or
  gets set to `closed` directly by `dispatchBack()` (Inward).
- `App\Models\SubcontractOrderPiece` — the source of truth for "is this
  piece currently with a subcontractor" (Outward only). Deliberately does
  **not** mutate `PieceSerial.status` on issue — the piece is still
  "ours", just physically elsewhere. `resolution` (`returned` /
  `written_off`) + `resolved_at` record how it came back, or didn't.
- `App\Models\SubcontractLedgerEntry` — append-only value ledger
  (`issue_value` / `return_value` / `shortage_deduction` /
  `job_work_income` / `payment`), same "ledger is the source of truth"
  contract as every other movement table in this system (sdd.md §5).
  Only ever written through `App\Services\SubcontractLedgerService`.

## Outward: two ways to issue (App\Services\SubcontractOutwardService)

- **`issuePieces()`** — hand over already-cut `PieceSerial`s by
  reference. No status mutation (see above); a piece already outstanding
  on another open subcontract order cannot be issued again.
- **`issueRawMaterial()`** — issue raw material instead. This creates and
  *finalizes a real `CutTicket`* (reusing `Modules\Production\App\
  Services\CuttingService::finalize()` — same fabric deduction + bundle/
  serial generation as an in-house cut), then attaches every generated
  piece to the subcontract order. This is deliberate: it's what lets a
  subcontractor-cut piece keep the exact same traceability guarantees
  (unique serial, full chain) as one cut on our own floor, even though
  physically a subcontractor did the cutting.

Either path posts an `issue_value` ledger entry
(`SubcontractOrder::valueFor()` — piece rate direct, dozen rate divided
by 12, bcmath throughout to avoid float drift on money).

`returnPieces()` accepts two lists — pieces that came back (set to
`sewn`, i.e. QC-ready — this bypasses `SewingService` since there was no
line assignment for external work) and pieces written off (lost/damaged,
never coming back). Posts `return_value` / `shortage_deduction` entries
and calls `refreshStatus()` to recompute `open` / `partially_returned` /
`closed` from what's still outstanding.

## Inward: tagging a Cut Ticket, then dispatching back

An Inward job doesn't get its own cutting flow — it reuses Production's
real one. A Cut Ticket is created with `inward_subcontract_order_id` set
(`Modules\Production\App\Http\Requests\StoreCutTicketRequest` validates
it references an `inward`-direction order), finalized normally, sewn
normally, and QC'd normally — except `Modules\Production\App\Services\
QcService::pass()` checks that tag: if set, the piece stays at
`qc_passed` instead of auto-intaking into **our own** Finished Goods,
because it was never ours to begin with.

`App\Services\SubcontractInwardService::dispatchBack()` is the point
where the finished pieces physically leave: every `qc_passed` piece under
the order's tagged Cut Tickets moves to `shipped` — reusing PieceSerial's
existing status rather than inventing a new one, since "shipped out of
this factory, not into our own Finished Goods" is exactly what it already
means for a StockTransfer-dispatched piece. Computes and posts a
`job_work_income` ledger entry, then closes the order.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/subcontract-orders[/{id}]` | List/view, filterable by `direction`/`party_id`/`status` | any `subcontract.*` permission |
| POST | `/api/v1/subcontract-orders` | Create (Outward or Inward) | `subcontract.outward.manage` or `.inward.manage` |
| POST | `/api/v1/subcontract-orders/{id}/issue-pieces` | Outward: issue already-cut pieces | `subcontract.outward.manage` |
| POST | `/api/v1/subcontract-orders/{id}/issue-raw-material` | Outward: issue raw material (creates+finalizes a Cut Ticket) | `subcontract.outward.manage` |
| POST | `/api/v1/subcontract-orders/{id}/return-pieces` | Outward: resolve returned/written-off pieces | `subcontract.outward.manage` |
| POST | `/api/v1/subcontract-orders/{id}/dispatch-back` | Inward: ship finished job-work back to the external party | `subcontract.inward.manage` |
| GET | `/api/v1/subcontract-orders/{id}/ledger` | Value ledger for this order | any `subcontract.*` permission |
| POST | `/api/v1/subcontract-orders/{id}/payment` | Record a manual `payment` ledger entry | `subcontract.outward.manage` or `.inward.manage` |

## Depends on / depended on by

- Depends on: Party (`type=subcontractor`), Order (Outward's source
  style; Inward's Cut Ticket still needs a real Order to hang its serial
  numbers off of), RawMaterial (Outward raw-material issue),
  Modules/Production (`CuttingService::finalize()`, `QcService`'s inward
  branch), Location.
- Depended on by: nothing yet — see Known gaps.

## Known simplifications and gaps (documented, not bugs)

- **Inward Cut Tickets still deduct `raw_material_id`/`fabric_consumed`
  from our own Raw Material stock** via the normal
  `CuttingService::finalize()` path, even though in practice the external
  party brought their own fabric. v1 has no "externally supplied, don't
  touch our stock" flag on RawMaterial or CutTicket — operators should
  model the subcontractor's fabric as its own RawMaterial record (or
  accept the (small) stock-ledger noise) rather than pointing at real
  owned stock. A first-class distinction is deferred to a future phase.
- **The ledger is not yet wired into Modules/Accounting** — same caveat
  as `Party.total_bill/advance/paid/due` (see Modules/Party/README.md):
  `SubcontractLedgerEntry` rows are the source of truth for what's owed,
  but nothing posts them into a general ledger/voucher yet. Deferred to
  Phase 6.
- **`SubcontractOrder` has no update/destroy endpoint.** Rate, quantity,
  and party are fixed at creation in v1 — correcting a mistake means
  creating a fresh order. Given the ledger and piece-attachment
  complexity once issuing has started, this is deliberately conservative
  rather than allowing an edit that could silently invalidate posted
  ledger entries.
- **`payment` is the only ledger entry type a controller posts directly**
  — every other type (`issue_value`, `return_value`,
  `shortage_deduction`, `job_work_income`) is a side effect of a piece
  movement, posted only by `SubcontractOutwardService`/
  `SubcontractInwardService`, never client-supplied.
