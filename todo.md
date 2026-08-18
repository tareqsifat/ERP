# TODO — Garments ERP Build

Working order. Don't skip phases — each one is a checkpoint where
you should actually run/click through what got built before moving
on, especially given the traceability and money-touching modules.

## Phase 0 — Before writing any code

- [x] Read `PRD_GarmentsERP_v2.md` §0 (gap analysis) and confirm with
      the client that the assumptions are right, especially:
  - [x] Bundle-level vs. piece-level physical barcode printing —
        which one does the factory actually want to print and scan?
        **Answered: Both.**
  - [x] Whether showrooms need a real POS/checkout (currently marked
        **Out of Scope** in PRD v2 §7 — this is the single biggest
        "did we miss something" risk, confirm explicitly)
        **Confirmed Out of Scope.**
  - [x] Payroll depth (v1's simple pay/due tracking vs. full
        attendance-based payroll) — confirm simple is enough for now
        **Answered: full attendance-based payroll — Phase 6 will need
        its own schema beyond what the PRD specs, see Phase 6 note.**
  - [x] Whether Buyers/Suppliers/Subcontractors need portal logins in
        v1 or party records are enough
        **Resolved by PRD v2 §7: party records only, no portal login
        in v1 — see Modules/Party/README.md "Known gaps".**
- [x] Decide on hosting target (shared VPS, cloud, etc.) — affects
      file storage config (`local` vs `s3`) decided now, not later
      **Answered: local disk for now.**
- [x] Set up the git repo with a remote (GitHub/GitLab) from commit
      #1 — given your past experience losing local work to a power
      outage, this is non-negotiable this time. Commit early, commit
      often, push before you close the laptop.
      **Remote: github.com/tareqsifat/ERP, pushed after every phase.**

## Phase 1 — Scaffolding

- [x] Laravel latest install, API-only mode
- [x] Install `nwidart/laravel-modules`, `laravel/passport`,
      `spatie/laravel-permission`
- [x] Vue 3 + Vite scaffold, install Pinia, Vue Router, Axios
- [x] Set up `routes/api.php` module-include pattern (SDD §3)
- [x] Add the `GET /api/v1/health` unauthenticated route (SDD §6)
- [x] Confirm baseline: `php artisan serve` responds on `/health`,
      `npm run build` completes clean, `npm run dev` serves the shell
      **Frontend confirmed for real (npm registry reachable). Backend
      could not be run in the sandbox that built it — see
      backend/SETUP.md — hand-written + `php -l`-linted instead;
      needs a real `composer install` + manual walkthrough on a
      machine with normal internet access before go-live.**
- [x] First commit + push

## Phase 2 — Auth & Users

- [x] Passport install + key generation, password grant client
- [x] User module: roles (Admin, Buyer, Merchandiser, Commercial,
      Accountant, Production, Cutting Master, Line Supervisor, Store
      Keeper (Raw Material), Store Keeper (Finished Goods), Showroom
      Staff) via spatie permissions
- [x] Login/logout flow end-to-end (Vue login form → token → stored →
      attached to axios → protected route works)
- [x] `AuthTest` (SDD §6) passing **(written, not yet run — see Phase 1
      note; `php -l` clean)**
- [x] Run `failed_doc.md` §1 and §2 checks on what exists so far
      **See failed_doc.md Review Log, "Pass 1 — after Phase 2".**

## Phase 3 — Core commercial modules (v1 PRD scope)

- [x] Party (Buyer/Supplier/Subcontractor)
- [x] Order (incl. line items, auto order number, grand total calc)
- [x] Booking
- [x] Budgeting / Costing
- [x] Sampling
- [x] Shipment
- [x] Module README.md for each (SDD §7)
- [x] Smoke tests for each module's routes (SDD §6)
      **Written for all 7 modules; `php -l` clean across the board.
      Not yet run against a real DB — same standing limitation as
      Phase 1/2, see backend/SETUP.md. Frontend: list/form views +
      Pinia stores + Vitest smoke tests built for all 7 modules too
      (sdd.md's 1:1 module mapping), `npm run build` and `npm test`
      both pass for real.**

## Phase 4 — Inventory & Production traceability (the new core)

- [x] Location module (Factory, Main Store, Showroom ×3 seeded)
- [x] Raw Material module (master + stock ledger + purchase orders)
- [x] Machine/Line register
- [x] Production: Cutting → Cut Ticket → Bundle → Piece Serial
      generation
- [x] Production: Sewing line input/output logging
- [x] QC pass/reject flow
- [x] Finished Goods Inventory (intake on QC pass)
- [x] Stock Transfer (dispatch/receive) between Main Store and
      Showrooms
- [x] `TraceabilityTest` (SDD §6) passing **(written — full order →
      cut ticket → bundle/serial → sewing → QC pass → Finished Goods →
      Stock Transfer chain in one HTTP-level test,
      `backend/tests/Feature/TraceabilityTest.php`; `php -l` clean.
      Not yet run against a real DB — same standing limitation noted
      in Phase 1/2/3, see backend/SETUP.md.)**
- [ ] Manually walk one piece through the whole lifecycle in the UI:
      cut → assign to line → sew → QC pass → appears in Finished
      Goods → transfer to a showroom → confirm receipt. Screenshot
      or note each step. **Blocked on the same sandbox limitation —
      needs a real `composer install` + running MySQL, see
      backend/SETUP.md. Frontend views for this phase (Cutting,
      Sewing/QC, Machine/Line register, Finished Goods, Stock
      Transfer, Locations) are built; walk through once the backend
      can actually run.**

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
