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
