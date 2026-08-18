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
      needs a real `composer install` + running MySQL/backend, see
      backend/SETUP.md. Frontend is built and ready for this walkthrough:
      Locations + Stock Transfer (`frontend/src/modules/location`),
      Raw Material + Purchase Orders (`.../raw-material`), Cutting +
      Sewing/QC + Machine-Line register + Traceability lookup
      (`.../production`), Finished Goods Inventory
      (`.../finished-goods`) — `npm run build` and `npm test` both
      pass for real (29 tests, 13 files). Do this walkthrough as soon
      as the backend can run.**

## Phase 5 — Subcontracting

- [x] Outward Subcontract: issue (serials and/or raw material) →
      return → QC → Finished Goods intake → Subcontractor Ledger.
      Backend done (`Modules/Subcontract`): SubcontractOrder/
      SubcontractOrderPiece/SubcontractLedgerEntry models,
      SubcontractOutwardService (issuePieces/issueRawMaterial/
      returnPieces/refreshStatus), full REST API, RoleSeeder grants,
      `SubcontractOutwardModuleTest` covering issue/return/write-off/
      permission-denied — written but not run against a real DB (no
      composer install / MySQL in this sandbox, same caveat as Phase 4).
      QC → Finished Goods intake is the *existing* Production QC flow
      (returned pieces go back to `sewn`, i.e. QC-ready) — no separate
      code path needed for that leg.
- [x] Inward Subcontract: receive → process (cutting/sewing/QC,
      tagged separately from own stock) → dispatch back → job-work
      income entry. Backend done: `CutTicket.inward_subcontract_order_id`
      tag, `QcService::pass()` inward branch (stays `qc_passed`, skips
      Finished Goods intake), `SubcontractInwardService::dispatchBack()`
      (ships via the existing `shipped` status + posts job_work_income),
      `SubcontractInwardModuleTest` covering the full QC→dispatch-back
      chain + permission-denied — written but not run against a real DB.
- [x] Frontend built (`frontend/src/modules/subcontract`): `api.js`/
      `store.js`/`routes.js` + `SubcontractOutwardView` (create order,
      issue pieces, issue raw material, return/write-off pieces),
      `SubcontractInwardView` (create order, dispatch back), and
      `SubcontractLedgerView` (per-order ledger + manual payment entry).
      Production's `CuttingView` gained an optional "Inward Subcontract
      Order" field so a Cut Ticket can be tagged from the existing
      Cutting screen, matching the backend's design (the Cut Ticket is
      the real entry point for inward job-work). `npm run build` and
      `npm test -- --run` both pass (14 files / 35 tests).
- [ ] Manually run one outward and one inward subcontract cycle
      end-to-end in the UI. Blocked on a real backend run — no
      composer install/MySQL in this sandbox (same caveat as Phase 4);
      the frontend and backend code paths are both in place and unit/
      feature-tested, just never exercised together against a live API.

## Phase 6 — Accounting & HRM (v1 PRD scope, largely unchanged)

- [x] Bank Accounts, Cash in Hand, Cheques. Backend done
      (`Modules/Accounting`): `BankAccount`/`BankTransaction`,
      `CashTransaction` (single pool), `Cheque` (Passed/Unused,
      `ChequeService::markPassed()` is the one place a cheque moves the
      bank ledger). Balances are always `SUM(signed amount)` over the
      ledger tables (sdd.md §5), never stored columns.
- [x] Income/Expense categories. `AccountingCategory` (one table,
      `kind` discriminates), validated to match `Voucher.type` on create.
- [x] Credit/Debit Vouchers (make sure Subcontractor party type flows
      through here correctly, not just Buyer/Supplier). `Voucher` +
      `VoucherService::record()` — `party_id` accepts any Party
      regardless of `type`, so Subcontractor vouchers work identically to
      Buyer/Supplier ones; confirmed no type-specific branching exists
      that would exclude subcontractors.
- [x] Monthly Transactions, Party Ledger, Daily Cashbook.
      `TransactionController` (daily rollup by date+type),
      `PartyLedgerController` (also serves Party Due List — see below),
      `CashbookController` (date-ranged register + running summary panel).
- [x] Party Due List (should now show Buyer/Supplier/Subcontractor
      tabs — confirm subcontractor dues actually surface here).
      `GET /party-ledger?type=` filters by any Party type including
      `subcontractor` — same endpoint as Party Ledger (documented as a
      deliberate merge in Accounting/README.md, PRD's two pages are the
      same underlying data). This is also what closes Modules/Party's
      long-deferred "Known gap": `PartyResource.financials` now returns
      real total_bill/paid/advance/due/balance via
      `PartyFinancialsService::summarize()`.
- [x] Loss & Profit. `LossProfitController` + `LossProfitService` —
      year-filterable, nets all Credit vs. Debit vouchers for the year.
- [x] HRM: Designations, Employees, Salaries. Backend done
      (`Modules/Hrm`): `Designation`, `Employee`, `SalaryPayment` (one row
      per employee+month, `paid_amount` incremented via "Pay Salary",
      `due_amount` a computed accessor). Deliberately NOT attendance-based
      — PRD v2 §7 explicitly flags attendance-based payroll as Out of
      Scope for v1/v2; flat salary pay/due tracking only, per PRD v1
      §3.11/§7.5.
      `AccountingModuleTest`/`HrmModuleTest` cover both modules — written
      but not run against a real DB (no composer install/MySQL in this
      sandbox, same caveat as every phase since Phase 4).
- [x] Frontend built: `frontend/src/modules/accounting` (Bank Accounts
      +deposit/withdraw, Cash +increase/reduce, Cheques +mark-passed,
      shared `CategoryView`/`VoucherView` components parameterized by
      `kind`/`type` route props for Income/Expense and Credit/Debit
      respectively, Monthly Transactions, Party Ledger with drill-down
      +Add Bill, Daily Cashbook with running summary panel, Party Due
      List, Loss & Profit) and `frontend/src/modules/hrm`
      (Designations, Employees, Salaries List with Open Month/Pay Salary
      actions). No `attendance.index` route/view — deliberately out of
      scope, and the sidebar's `router.hasRoute()` filter just omits
      that pre-scaffolded nav item until/unless it's ever built.
      `npm run build` and `npm test -- --run` both pass (16 files / 53
      tests).
- [ ] Manually run through Accounting + HRM end-to-end in the UI.
      Blocked on a real backend run — no composer install/MySQL in this
      sandbox (same caveat as every phase since Phase 4); the frontend
      and backend code paths are both in place and unit/feature-tested,
      just never exercised together against a live API.

## Phase 7 — Reporting & Settings

- [x] Report suite (financial + operational + the new traceability
      lookup by serial). Backend: `Modules/Report` (no tables of its
      own — read-only aggregates over other modules' data).
      `App\Services\ReportService` covers Sales/Order, Production,
      Stock, Subcontract, and Party Ledger reports; `cashbook()`
      delegates to Modules/Accounting's already-built
      `CashbookController` instead of duplicating the running-summary
      computation. `App\Services\TraceabilityService::traceBySerial()`
      is the seventh type — walks Piece Serial → Bundle → Cut Ticket
      (+ inward Subcontract Order tag) → Order → every Finished Goods
      Movement for that exact piece, per the "friendlier UI on top of
      the same query" note left in
      `Modules/Production/App/Http/Controllers/PieceSerialController`'s
      docblock. All seven behind one `report.view` gate
      (`Modules/Report/routes/api.php`), tests in
      `Modules/Report/tests/Feature/ReportModuleTest.php` (permission
      grant/denial + a full traceability chain + 404 on unknown
      serial), design rationale — including which "seven report
      types" this build chose, since PRD v1 §3.14 never names all
      seven — in `Modules/Report/README.md`. Frontend:
      `frontend/src/modules/report` (tabbed `ReportSuiteView.vue`,
      one tab per report type, date-range filter where applicable,
      serial-lookup form for Traceability).
- [x] Currency/Notification/System/Company settings. Backend:
      `Modules/Setting` — a single key/value `settings` table
      (`key` unique, `group` enum) rather than one table per tab,
      `App\Services\SettingService` as the only writer
      (`get()`/`set()`/`allGrouped()`), `SettingController` (`GET
      /settings` open to any authenticated user, `PUT /settings`
      bulk-upserts one group gated by `setting.manage`),
      `database/seeders/SettingSeeder.php` seeds sane defaults (BDT
      currency, Asia/Dhaka timezone, "Vishesh Textiles" company name)
      and is now called from the top-level `DatabaseSeeder`. Tests in
      `Modules/Setting/tests/Feature/SettingModuleTest.php`. My
      Profile (PRD v1 §3.16/§4.14) needed no new backend work — `GET
      /auth/me` and `PATCH /users/me` already existed from Phase 2 —
      just a new `frontend/src/modules/user/views/ProfileView.vue`
      plus a `profile.index` route linked from the top-bar user menu
      in `AppLayout.vue` (My Profile isn't a sidebar item per PRD).
      Frontend: `frontend/src/modules/setting` (tabbed
      `SettingsView.vue`, read-only unless `setting.manage`).
      `npm run build` and `npm test -- --run` both pass (19 files / 61
      tests).
- [ ] Manually run through Reports + Settings + My Profile in the UI.
      Blocked on a real backend run — no composer install/MySQL in
      this sandbox (same caveat as every phase since Phase 4).

## Phase 8 — Security pass

- [x] Go through `failed_doc.md` item by item, in full, against the
      finished codebase — not spot-checks. Ran as six parallel
      research passes (one per checklist section-group) tracing real
      code across every module through Phase 7, not from memory —
      see `failed_doc.md`'s "Pass 3" Review Log entry for the full
      per-item breakdown.
- [x] Fix every item that comes back TRUE. Five real findings, four
      fixed: (1) uploaded Party/Order/Booking images weren't
      re-encoded server-side — new `App\Services\ImageUploadService`
      (GD-based, no new Composer dependency) now re-encodes every
      upload before storing; (2) the `api` rate limiter (120/min) was
      defined but never wired to any route — added `throttle:api` to
      the shared `auth.api` middleware group in `bootstrap/app.php`;
      (3) `SubcontractOrderController::store()`'s sequence generation
      wasn't wrapped in `DB::transaction()` (a regression of the
      Phase-4 race-condition bug class) — fixed; (4) a voucher could
      attach a cheque issued for a different party — added a coherence
      check to `StoreVoucherRequest`. One item — raw material issuing
      allowing negative stock — remains an intentional, documented
      design decision (PRD only specifies reorder alerts, not a hard
      block), unchanged since Pass 2. **One item deliberately NOT
      fixed, flagged for a human decision**: Shipment still doesn't
      check/deduct Finished Goods stock — see `failed_doc.md`'s Pass 3
      entry and this session's summary to the user for why a guess-fix
      here would risk corrupting the traceability chain the whole
      system exists to protect.
- [x] Log the pass in `failed_doc.md`'s Review Log — see "Pass 3"
      entry.
- [x] Re-run all backend + frontend tests, confirm all green. Frontend:
      `npm test -- --run` — 19 files / 61 tests pass. Backend: all
      touched files pass `php -l` (zero failures across the entire
      `backend/` tree); Pest itself still can't run in this sandbox
      (no composer install/MySQL, same caveat as every phase since
      Phase 2) — two new tests were added
      (`PartyModuleTest.php`'s re-encode-on-upload test,
      `AccountingModuleTest.php`'s cheque/party-mismatch test) but not
      executed, same as every other backend test this project.
- [x] `composer audit` and `npm audit` — review anything high/critical.
      `npm audit`: 0 vulnerabilities (309 total deps). `composer
      audit`: could not run (no `vendor/`, no composer install
      capability in this sandbox) — `composer.json`'s pinned majors
      were manually reviewed and are all current/maintained; a real
      `composer audit` is needed once this runs somewhere with
      Composer, tracked for Phase 9's usage-guide walkthrough.

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
