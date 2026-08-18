# Modules/Report

PRD v1 §3.14/§4.13 — "a dedicated Reports section exposing seven report
types ... covering operational and financial data, generally supporting
date-range filtering and export/print options." PRD v2 §2/§7 adds the
piece-traceability lookup to this suite. Phase 7 of `todo.md`: *"Report
suite (financial + operational + the new traceability lookup by
serial)."*

## Design

This module owns **no tables of its own**. Every report is a read-only
query against another module's data — the same cross-module-read
precedent already used by `Modules\Accounting\App\Services\
PartyFinancialsService` (reads `Modules\Party`) and
`Modules\Party\App\Models\Party::bills()/vouchers()` (reads
`Modules\Accounting`). Nothing here ever writes.

- `App\Services\ReportService` — five aggregate reports (sales/orders,
  production, stock, subcontract, party ledger), plus a `cashbook()`
  method that **delegates** to `Modules\Accounting\App\Http\Controllers\
  CashbookController::index()` rather than re-implementing the
  running-summary computation a second time.
- `App\Services\TraceabilityService` — the seventh report type, "Piece
  Traceability Lookup." Walks Piece Serial → Bundle → Cut Ticket (+
  inward Subcontract Order tag, if any) → Order, plus every Finished
  Goods Movement keyed to that exact piece. `Modules\Production\App\
  Http\Controllers\PieceSerialController::show()` already exposes the
  bare row and its docblock explicitly earmarks this fuller read for
  "a dedicated Report-module endpoint (Phase 7)" — this is that
  endpoint.

## Known simplifications (a deliberate v1 interpretation)

PRD v1 §3.14 says "seven report types (including the Daily Cashbook
shown above)" but never names the other six. This module's seven are:

1. Sales / Order Report — `GET /reports/sales-orders`
2. Production Report — `GET /reports/production`
3. Stock Report — `GET /reports/stock`
4. Subcontract Report — `GET /reports/subcontract`
5. Party Ledger Report — `GET /reports/party-ledger`
6. Daily Cashbook — `GET /reports/cashbook` (delegates to Accounting)
7. Piece Traceability Lookup — `GET /reports/traceability/{serial}`
   (delegates to the same query Production's piece-serials endpoint
   uses, with a richer chain attached)

All seven sit behind the single `report.view` permission — this is an
aggregate/management view, not a module with its own write-side
authorization story. Finer-grained permissions still gate the
underlying data elsewhere (e.g. `production.trace.view` on
`/piece-serials/{id}`, `accounting.cashbook.view` on
`/cashbook`) — a `report.view` holder sees the same data through this
read-only lens regardless of whether they also hold those grains.

Stock, production, and subcontract reports use simple date-range/
group-by aggregates rather than a dedicated reporting/OLAP layer —
appropriate for a factory this size (PRD's own numbers are in the
hundreds/low-thousands of rows per table, not millions).

## Known gaps

- No PDF/Excel/CSV export wiring yet (PRD v1 mentions "export/print
  options consistent with the rest of the application" generally,
  but no concrete export mechanism exists anywhere else in the app
  yet either — this is a frontend-only concern for a later pass, not
  specific to Report).
- No caching — every call re-aggregates live. Fine at this data
  volume; would need revisiting well before it became a real system.
