# TODO — Garments ERP Build

Working order. Don't skip phases — each one is a checkpoint where
you should actually run/click through what got built before moving
on, especially given the traceability and money-touching modules.

## Phase 0 — Before writing any code

- [ ] Read `PRD_GarmentsERP_v2.md` §0 (gap analysis) and confirm with
      the client that the assumptions are right, especially:
  - [ ] Bundle-level vs. piece-level physical barcode printing —
        which one does the factory actually want to print and scan?
  - [ ] Whether showrooms need a real POS/checkout (currently marked
        **Out of Scope** in PRD v2 §7 — this is the single biggest
        "did we miss something" risk, confirm explicitly)
  - [ ] Payroll depth (v1's simple pay/due tracking vs. full
        attendance-based payroll) — confirm simple is enough for now
  - [ ] Whether Buyers/Suppliers/Subcontractors need portal logins in
        v1 or party records are enough
- [ ] Decide on hosting target (shared VPS, cloud, etc.) — affects
      file storage config (`local` vs `s3`) decided now, not later
- [ ] Set up the git repo with a remote (GitHub/GitLab) from commit
      #1 — given your past experience losing local work to a power
      outage, this is non-negotiable this time. Commit early, commit
      often, push before you close the laptop.

## Phase 1 — Scaffolding

- [ ] Laravel latest install, API-only mode
- [ ] Install `nwidart/laravel-modules`, `laravel/passport`,
      `spatie/laravel-permission`
- [ ] Vue 3 + Vite scaffold, install Pinia, Vue Router, Axios
- [ ] Set up `routes/api.php` module-include pattern (SDD §3)
- [ ] Add the `GET /api/v1/health` unauthenticated route (SDD §6)
- [ ] Confirm baseline: `php artisan serve` responds on `/health`,
      `npm run build` completes clean, `npm run dev` serves the shell
- [ ] First commit + push

## Phase 2 — Auth & Users

- [ ] Passport install + key generation, password grant client
- [ ] User module: roles (Admin, Buyer, Merchandiser, Commercial,
      Accountant, Production, Cutting Master, Line Supervisor, Store
      Keeper (Raw Material), Store Keeper (Finished Goods), Showroom
      Staff) via spatie permissions
- [ ] Login/logout flow end-to-end (Vue login form → token → stored →
      attached to axios → protected route works)
- [ ] `AuthTest` (SDD §6) passing
- [ ] Run `failed_doc.md` §1 and §2 checks on what exists so far

## Phase 3 — Core commercial modules (v1 PRD scope)

- [ ] Party (Buyer/Supplier/Subcontractor)
- [ ] Order (incl. line items, auto order number, grand total calc)
- [ ] Booking
- [ ] Budgeting / Costing
- [ ] Sampling
- [ ] Shipment
- [ ] Module README.md for each (SDD §7)
- [ ] Smoke tests for each module's routes (SDD §6)

## Phase 4 — Inventory & Production traceability (the new core)

- [ ] Location module (Factory, Main Store, Showroom ×3 seeded)
- [ ] Raw Material module (master + stock ledger + purchase orders)
- [ ] Machine/Line register
- [ ] Production: Cutting → Cut Ticket → Bundle → Piece Serial
      generation
- [ ] Production: Sewing line input/output logging
- [ ] QC pass/reject flow
- [ ] Finished Goods Inventory (intake on QC pass)
- [ ] Stock Transfer (dispatch/receive) between Main Store and
      Showrooms
- [ ] `TraceabilityTest` (SDD §6) passing — this is the single most
      important test in the whole project; don't move on until it's
      green
- [ ] Manually walk one piece through the whole lifecycle in the UI:
      cut → assign to line → sew → QC pass → appears in Finished
      Goods → transfer to a showroom → confirm receipt. Screenshot
      or note each step.

## Phase 5 — Subcontracting

- [ ] Outward Subcontract: issue (serials and/or raw material) →
      return → QC → Finished Goods intake → Subcontractor Ledger
- [ ] Inward Subcontract: receive → process (cutting/sewing/QC,
      tagged separately from own stock) → dispatch back → job-work
      income entry
- [ ] Manually run one outward and one inward subcontract cycle
      end-to-end in the UI

## Phase 6 — Accounting & HRM (v1 PRD scope, largely unchanged)

- [ ] Bank Accounts, Cash in Hand, Cheques
- [ ] Income/Expense categories
- [ ] Credit/Debit Vouchers (make sure Subcontractor party type flows
      through here correctly, not just Buyer/Supplier)
- [ ] Monthly Transactions, Party Ledger, Daily Cashbook
- [ ] Party Due List (should now show Buyer/Supplier/Subcontractor
      tabs — confirm subcontractor dues actually surface here)
- [ ] Loss & Profit
- [ ] HRM: Designations, Employees, Salaries

## Phase 7 — Reporting & Settings

- [ ] Report suite (financial + operational + the new traceability
      lookup by serial)
- [ ] Currency/Notification/System/Company settings

## Phase 8 — Security pass

- [ ] Go through `failed_doc.md` item by item, in full, against the
      finished codebase — not spot-checks
- [ ] Fix every item that comes back TRUE
- [ ] Log the pass in `failed_doc.md`'s Review Log
- [ ] Re-run all backend + frontend tests, confirm all green
- [ ] `composer audit` and `npm audit` — review anything high/critical

## Phase 9 — Test data & usage guide

- [ ] Build the seeder(s) described in `user_usage_guide.md` (create
      that file once the above phases are stable — it needs real
      routes/fields to reference accurately, don't write it too
      early or it'll drift from the actual build)
- [ ] Seed test data: at least one Order → Booking → Cutting →
      Sewing → Finished Goods → Shipment chain, one Outward and one
      Inward subcontract, a handful of vouchers, one full Employee +
      Salary cycle, all 4 locations populated
- [ ] Walk through `user_usage_guide.md` yourself top to bottom before
      handing it to the client — if a step doesn't work as written,
      the guide is wrong, fix the guide (or the bug)

## Phase 10 — Handover

- [ ] Final `failed_doc.md` pass after test-data seeding (seeders
      sometimes introduce their own shortcuts/vulnerabilities — e.g.
      weak seeded passwords left active)
- [ ] Confirm seeded default credentials are either removed or
      clearly marked "change before go-live" in the usage guide
- [ ] Client walkthrough using `user_usage_guide.md`
