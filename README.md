# Garments ERP (Vishesh Textiles)

A Laravel API + Vue 3 admin SPA for a garment manufacturing/export
business — order lifecycle, piece-level production traceability
(cut → sew → QC → finished goods), raw material & finished goods
inventory across 4 locations, subcontracting (both directions), full
accounting, HRM, and reporting.

## Source documents

- [`PRD_GarmentsERP_v1.md`](./PRD_GarmentsERP_v1.md) — original
  competitor-audit PRD (commercial/office modules; field-level detail
  for Order/Booking/Budgeting/Costing/Sampling/Shipment/Accounting/
  Party/HRM/Reports/Settings).
- [`PRD_GarmentsERP_v2.md`](./PRD_GarmentsERP_v2.md) — supersedes v1 for
  everything it changes: production traceability, raw material &
  finished goods inventory, locations & stock transfer, machines,
  subcontracting.
- [`sdd.md`](./sdd.md) — system design: stack, module layout, routing
  convention, DB rules, testing strategy.
- [`todo.md`](./todo.md) — phased build plan.
- [`failed_doc.md`](./failed_doc.md) — security/robustness checklist,
  re-verified against the actual code at the end of Phases 2, 4, and 8
  (see its Review Log).

## Layout

```
backend/    Laravel API (nwidart/laravel-modules, Passport, spatie/laravel-permission)
frontend/   Vue 3 + Vite admin SPA (Pinia, Vue Router, Axios, Tailwind)
```

## Getting started

- Backend: see [`backend/SETUP.md`](./backend/SETUP.md) — **required
  reading before first run**; this backend was built in an environment
  without Packagist access, so `composer install` has never actually
  been run against it yet and the setup doc explains that up front.
- Frontend:
  ```bash
  cd frontend
  npm install
  npm run dev
  ```
  This half was built and verified for real (npm registry was reachable)
  — build, dev server, and Vitest all ran successfully during
  development.

## Build status

Tracked phase-by-phase per `todo.md`. See git log for phase commits.

- **Phase 0–2** — done (scaffolding, Auth & Users).
- **Phase 3** — done: Party, Order, Booking, Budgeting, Costing,
  Sampling, Shipment — backend (models, migrations, controllers,
  requests, resources, routes, Pest smoke tests, per-module READMEs)
  and frontend (Pinia stores, Vue list/form views, Vue Router routes,
  Vitest smoke tests) for all 7 modules. `npm run build` and
  `npm test` pass for real; backend is `php -l`-lint-clean but still
  awaiting its first real `composer install` — see
  `backend/SETUP.md`.
- **Phase 4** — done: Production traceability (Cutting → Sewing → QC),
  Raw Material inventory & Purchase Orders, Locations & Stock
  Transfer, Machines — backend + frontend, with the sequence-number
  race-condition class of bug fixed and re-verified.
- **Phase 5** — done: Finished Goods inventory, Subcontracting
  (Outward and Inward, both fully traceable), Subcontractor Ledger.
- **Phase 6** — done: Accounting suite (Bank, Cash, Cheques, Income/
  Expense categories, Credit/Debit Vouchers, Monthly Transactions,
  Party Ledger, Party Due List, Loss & Profit), HRM (Designations,
  Employees, Salaries — flat monthly, no attendance tracking by
  design per PRD v2 §7).
- **Phase 7** — done: Report Suite (7 report types, reusing existing
  module logic rather than duplicating it) and Settings (key-value
  store, 4 tabs, `setting.manage`-gated).
- **Phase 8** — done: full security pass against `failed_doc.md`'s
  checklist via a 6-way parallel audit — fixed missing server-side
  image re-encoding on upload, an unwired rate limiter, a
  `DB::transaction()` regression around a sequence-number
  `lockForUpdate()`, and a voucher/cheque party-mismatch gap. The
  Shipment → Finished Goods stock deduction gap remains open by
  deliberate choice (documented, not guessed — see `failed_doc.md`'s
  Pass 3 entry and `user_usage_guide.md` §9 "Known limitations"); it
  needs a real product decision (a Shipment line-item schema) rather
  than an arbitrary fix.
- **Phase 9** — done: `DemoDataSeeder` (a full, connected, realistic
  demo dataset built by calling the real Service classes, not raw
  `Model::create`) and [`user_usage_guide.md`](./user_usage_guide.md),
  a client-facing walkthrough verified path-by-path against the
  actual routes.
- **Phase 10 (Handover)** — out of scope for this build; not
  attempted.

Backend note (unchanged since Phase 3): this backend was built in a
sandboxed environment without Packagist access, so `composer install`
has never actually been run against it — everything is `php -l`-lint-
clean and carefully hand-traced, but run `composer install` and the
test suite for real on the first machine that has internet access
before going live. See `backend/SETUP.md`.
