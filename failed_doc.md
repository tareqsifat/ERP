# failed_doc.md — Security & Robustness Verification Checklist

**Purpose:** After the project is built (or after any significant
change), Claude Code must go through this file item by item and
verify — against the actual code, not from memory — whether each
statement is **true or false** for this codebase. The project is
only considered "ok" once every item below can be marked **FALSE**
(i.e., the vulnerability does *not* exist / the defense *is* in
place). Any item that comes back **TRUE** is a defect: fix it, then
re-check.

Format for the check-in: for each item, state `FALSE — <why>` with a
one-line pointer to the code/config that proves it (e.g. a file +
line, a middleware name, a validation rule), or `TRUE — <what's
missing>` if it's a real gap.

This is a working checklist, not a compliance certificate — it
doesn't replace a real third-party penetration test before this
system touches real client financial data.

---

## 1. Authentication & Session / Token Handling

- [ ] Passport tokens have no expiry (never expire) or an excessively
      long expiry (>24h access token).
- [ ] Refresh tokens are not revoked on logout.
- [ ] Login endpoint has no rate limiting / lockout — allows
      unlimited password guesses.
- [ ] Passwords are stored anywhere other than `bcrypt`/`argon2`
      hashed (e.g. plain text, MD5, SHA1, reversible encryption).
- [ ] Password reset (if implemented) uses a predictable or
      non-expiring token.
- [ ] There is no server-side check that a Passport token actually
      belongs to an active, non-deleted user before honoring it.
- [ ] Registration/user-creation endpoints are reachable without
      authentication when they shouldn't be (only Admin should create
      Admin/Accountant/etc. users — verify no public signup route
      exists for internal roles).

## 2. Authorization / Access Control (IDOR & Privilege Escalation)

- [ ] Any endpoint that takes an `{id}` (order, party, voucher,
      employee, serial, subcontract order, etc.) does **not** verify
      the authenticated user is allowed to access *that specific
      record* — i.e., a Merchandiser can view/edit another
      Merchandiser's order, or a Showroom Staff user for Showroom A
      can view/transfer stock for Showroom B, just by changing the
      ID in the URL (classic IDOR).
- [ ] Role/permission checks exist only on the frontend (Vue route
      guards) and are missing on the backend API — i.e., hitting the
      API route directly with a valid-but-under-privileged token
      still succeeds.
- [ ] Mass assignment is possible — a request body can set fields
      like `role`, `is_admin`, `status`, `location_id`, or any
      party/user's `balance`/`due` directly, because a model uses
      `$guarded = []` or an overly broad `$fillable`.
- [ ] A user can escalate their own role by including a `role_id` /
      `permission` field in a profile-update request.
- [ ] Financial write endpoints (vouchers, salary payment, stock
      adjustment) are reachable by roles that should only have
      read access (e.g. Merchandiser posting a Credit Voucher).

## 3. File Upload Vulnerabilities

- [ ] Uploaded files (item images, avatars, NID/passport uploads) are
      validated only by client-side JS or by file *extension*, not by
      actual MIME type / magic bytes on the server.
- [ ] Uploaded files are stored inside the public web root and
      directly executable (e.g. a `.php` file disguised with a `.jpg`
      extension, or double-extension trick, can be uploaded and then
      requested directly to execute server-side code).
- [ ] There is no file size limit, allowing a trivial denial-of-service
      via huge uploads.
- [ ] Uploaded filenames are used as-is (not sanitized/renamed),
      allowing path traversal (`../../etc/passwd`-style names) or
      overwriting another user's file via a crafted filename.
- [ ] Uploaded images are not re-processed/re-encoded server-side,
      leaving room for embedded malicious payloads (e.g. polyglot
      files) to be served back to other users.

## 4. Local/Remote File Inclusion & Path Traversal

- [ ] Any code path builds a file path from user input (e.g. a report
      template name, an export filename, a module name) and passes it
      to `include`/`require`/`file_get_contents`/`Storage::get`
      without an allow-list or realpath containment check —
      i.e., local file inclusion is possible.
- [ ] Any code path fetches a remote URL supplied by user input
      (e.g. "import from URL", a webhook target, a logo-by-URL
      setting) without validating/allow-listing the destination —
      i.e., server-side request forgery (SSRF) / remote file
      inclusion is possible.
- [ ] File download/export endpoints (PDF/Excel/CSV export mentioned
      throughout the PRD) accept a raw filename/path parameter instead
      of an opaque ID, allowing traversal to arbitrary files on disk.

## 5. Injection

- [ ] Any raw SQL is built via string concatenation with unescaped
      user input instead of Eloquent/query builder bindings — i.e.,
      SQL injection is possible anywhere (search filters, date-range
      filters, and report generation are the highest-risk spots given
      how many list pages have search/filter bars).
- [ ] Any shell command is built from user input (e.g. a PDF/export
      tool invoked via `exec`/`shell_exec`) without escaping — i.e.,
      OS command injection is possible.
- [ ] User-supplied input is reflected into API responses or into any
      server-rendered content without escaping — i.e., stored or
      reflected XSS is possible (relevant even in an API+SPA setup if
      the SPA renders any field with `v-html` instead of default
      text interpolation — audit every `v-html` usage specifically).

## 6. CSRF / CORS

- [ ] CORS is configured with `Access-Control-Allow-Origin: *`
      alongside credentialed requests (cookies/Authorization header
      accepted from any origin) — i.e., any website can make
      authenticated requests to this API on behalf of a logged-in
      user's browser.
- [ ] If any session/cookie-based auth is used anywhere (it shouldn't
      be, given Passport bearer tokens — but check `stateful` domain
      config and any Sanctum leftovers) without CSRF protection.

## 7. Business-Logic / Traceability Integrity

*(Specific to this system's actual purpose — a generic pentest
checklist won't catch these.)*

- [ ] A Piece Serial can be generated more than once (duplicate
      serials across different cut tickets) — i.e., the uniqueness
      constraint is missing or only enforced in application code, not
      at the database level.
- [ ] Finished Goods stock can go negative (more shipped/transferred
      out than was ever received in) without the system blocking or
      at minimum flagging it.
- [ ] Raw material stock can go negative (issued more than what was
      ever received) silently.
- [ ] A Stock Transfer can be marked "Received" for more quantity
      than was "Dispatched".
- [ ] An Outward Subcontract return can be recorded for more pieces
      than were issued to that subcontractor.
- [ ] Deleting an Order (or any parent record with financial
      children — vouchers, ledger entries) is possible via a hard
      delete that removes the audit trail, instead of being blocked
      or soft-deleted.
- [ ] Two simultaneous requests can both pass a stock-availability
      check and both deduct the same unit of stock (race condition —
      no row locking / DB transaction around stock-affecting writes).

## 8. Secrets & Configuration

- [ ] `.env` file, database credentials, or Passport encryption keys
      are committed to the git repository.
- [ ] `APP_DEBUG=true` (or equivalent) is left on in a
      production-intended config, leaking stack traces (with file
      paths, and potentially DB queries/credentials) to API
      consumers on error.
- [ ] Default/example credentials (e.g. `admin@admin.com` /
      `password`) are seeded into a build meant to hold real client
      data.
- [ ] API error responses leak internal details (stack traces, SQL
      error text, file paths) instead of a generic message in
      non-debug mode.

## 9. Dependency & Platform

- [ ] `composer.json` / `package.json` pin known-vulnerable versions,
      or `composer audit` / `npm audit` reports high/critical issues
      that haven't been reviewed.
- [ ] Laravel's own security-relevant config defaults are left
      unreviewed (`config/cors.php`, `config/session.php`,
      `config/sanctum.php` if present at all despite Passport being
      the intended auth).

## 10. General Availability / DoS

- [ ] List endpoints with no pagination limit — a client can request
      an unbounded page size and exhaust server memory.
- [ ] No rate limiting on any write-heavy endpoint (e.g. voucher
      creation, stock movement) beyond login.
- [ ] Export endpoints (PDF/Excel/CSV, used across nearly every list
      page per the PRD) can be triggered on arbitrarily large date
      ranges/datasets with no size cap, allowing a resource-exhaustion
      DoS via a single request.

---

## How Claude Code should use this file

1. After initial scaffolding and after every subsequent feature
   addition that touches auth, file handling, or money/stock
   movement, re-run this checklist.
2. Don't just grep for keywords — actually trace the code path for
   each item (open the controller, the form request, the middleware
   stack) before marking it FALSE.
3. Log the result of each pass (date + summary of any TRUE items
   found and fixed) at the bottom of this file, so there's a visible
   history of security review rather than a one-time check.

### Review Log

*(Claude Code: append an entry here each time this checklist is run.)*

---

#### Pass 1 — after Phase 2 (Auth & Users), scope: §1 and §2 only

Traced against the actual code in `backend/Modules/Auth`,
`backend/Modules/User`, `backend/app/Models/User.php`,
`backend/app/Http/Middleware/`, `backend/app/Providers/AppServiceProvider.php`,
and `backend/bootstrap/app.php`. Everything outside Auth/User (Party,
Order, Accounting, etc.) does not exist yet and is explicitly deferred
to the Phase-3/4/8 passes noted below — not marked FALSE by omission.

**§1 Authentication & Session / Token Handling**

1. Passport token expiry — **FALSE**. `AppServiceProvider::boot()`:
   `Passport::tokensExpireIn(now()->addHour())` (access, 1h < 24h cap),
   `refreshTokensExpireIn(14 days)`.
2. Refresh tokens not revoked on logout — **FALSE**.
   `AuthController::logout()` → `revokeToken()` calls both
   `TokenRepository::revokeAccessToken()` and
   `RefreshTokenRepository::revokeRefreshTokensByAccessTokenId()`.
   Covered by `AuthTest::"logout revokes the access token so it cannot be reused"`.
3. Login has no rate limiting — **FALSE**.
   `Modules/Auth/routes/api.php` applies `throttle:login` to
   `/auth/login` and `/auth/refresh`; limiter defined in
   `AppServiceProvider::boot()` as 5/min keyed by IP+email.
4. Passwords stored other than bcrypt/argon2 — **FALSE**.
   `User::casts()` → `'password' => 'hashed'` (Laravel's hashed cast,
   default bcrypt driver, `config/hashing.php` untouched).
5. Password reset token predictable/non-expiring — **N/A, not yet
   implemented**. No password-reset flow exists in the codebase to be
   vulnerable; re-check if/when one is built.
6. No server-side check that a token belongs to an active, non-deleted
   user — **was TRUE, now FIXED.** Soft-deleted users were already safe
   (Eloquent's default query scope excludes them from the user
   provider), but nothing re-checked `is_active` on every request — an
   Admin deactivating a user mid-session had no effect until that
   user's token happened to expire naturally (up to 1h). Fixed by
   `app/Http/Middleware/EnsureUserIsActive.php`, wired into a new
   `auth.api` middleware group (`bootstrap/app.php`) that every module
   route uses instead of bare `auth:api`, so the check can't be
   forgotten module-by-module. Now **FALSE**.
7. Public registration/user-creation route reachable unauthenticated —
   **FALSE**. Only route is `POST /api/v1/users`, gated by
   `auth.api` + `permission:user.create`; no public signup route exists
   anywhere in Modules/Auth or Modules/User.

**§2 Authorization / Access Control (IDOR & Privilege Escalation)**

1. `{id}` endpoints don't verify the caller may access that specific
   record — **FALSE for what exists (User module)**: `user.*`
   permissions are intentionally global/Admin-scoped (no "own record"
   concept in the PRD for user management), so this doesn't apply the
   way it will for Order/Party/Location-scoped resources. **Deferred**:
   re-check at Phase 3 (Merchandiser-owned Orders) and Phase 4/8
   (Showroom Staff's `location_id` scoping on Stock Transfers).
2. Role/permission checks exist only on the frontend — **FALSE**. Every
   `Modules/User/routes/api.php` route is gated server-side with
   `permission:*` middleware, independent of the Vue router guard
   (which is explicitly commented as "UX convenience only, never the
   source of truth"). Verified by `UserModuleTest::"a non-admin cannot
   create a user"` (asserts 403 from the API directly).
3. Mass assignment possible for role/is_admin/status/location_id/balance
   — **was a related bug, now FIXED.** `User`'s `#[Fillable]` attribute
   never included `role` (not a real column — spatie relation table) or
   `is_admin` (doesn't exist). It DID list `location_id` but every write
   path (`UserController::store/update`, `ProfileController::update`)
   already explicitly whitelists fields via `->only([...])` on
   `validated()` data — never `$request->all()` — so no exploitable
   path existed. Found instead: `is_active` was being mass-assigned in
   `UserController::store()`/`update()` but was missing from
   `#[Fillable]`, which would have thrown `MassAssignmentException` at
   runtime (a correctness bug that, if "fixed" carelessly by loosening
   to `$guarded = []`, would have BECOME the real vulnerability this
   checklist item describes). Fixed narrowly by adding `is_active` to
   the explicit `#[Fillable]` list rather than loosening the guard.
4. Self role-escalation via profile-update fields — **FALSE**.
   `UpdateProfileRequest::rules()` has no `role`/`location_id`/
   `is_active` key at all, and `ProfileController::update()` only
   applies `->only(['name','email','phone'])` of validated data.
   Covered by `UserModuleTest::"a user can update their own profile but
   cannot smuggle a role change through it"`.
5. Financial write endpoints reachable by read-only roles — **N/A, not
   yet implemented** (Modules/Accounting is Phase 6). Re-check then.

**Fixes applied this pass:** `app/Http/Middleware/EnsureUserIsActive.php`
(new), `bootstrap/app.php` (`auth.api` middleware group),
`Modules/Auth/routes/api.php` + `Modules/User/routes/api.php` (switched
to `auth.api`), `app/Models/User.php` (`is_active` added to `#[Fillable]`).

---

#### Pass 2 — after Phase 4 (Inventory & Production traceability), scope: §1 (spot-check only), §2, §7, §9 (partial), §10 (spot-check)

Traced against the actual code added in Phase 3 (Party, Order, Booking,
Budgeting, Costing, Sampling, Shipment) and Phase 4 (Location,
RawMaterial, Production, FinishedGoods, Stock Transfer). §3/§4/§5/§6/§8
are unchanged from Pass 1 (no file uploads, no `include`/remote-URL code
paths, no raw SQL string concatenation, no CORS/session config touched,
no secrets added) — not re-audited line-by-line this pass, just spot-
checked for regressions and found none.

**§1 (spot-check)** — no new auth code this phase; `auth.api` middleware
is applied on every new module's routes (`Location`, `RawMaterial`,
`Production`, `FinishedGoods`), verified by reading each `routes/api.php`
directly rather than assuming. Still **FALSE** across the board.

**§2 Authorization / Access Control (IDOR & Privilege Escalation)**

1. `{id}` endpoints don't verify the caller may access that specific
   record — **partially TRUE, now FIXED for the item sdd.md §4 actually
   specifies.** sdd.md §4 is explicit that location-scoping ("Showroom
   Staff sees only their showroom") is enforced via `user.location_id`
   in policies/queries, not the role/permission layer — and this was
   flagged as a deferred Phase-4 re-check in Pass 1. It was missing:
   `StockTransferController` had no location check at all (a Showroom
   Staff user could dispatch/receive/view any location's transfers by
   ID), and `FinishedGoodsController::stock()`/`movements()` trusted a
   client-supplied `location_id` filter instead of the caller's own.
   Fixed: `StockTransferController::guardLocationScope()` (403 on
   `show`/`receive` for a transfer that doesn't touch the caller's
   location; `index()` query-scoped; `store()` rejects dispatching from
   a location that isn't the caller's own when `location_id` is set) and
   `FinishedGoodsController` now computes `$scopedLocationId = $request
   ->user()->location_id ?: $request->integer('location_id') ?: null`
   — a scoped user's own location always wins over whatever the request
   asks for. Covered by `StockTransferModuleTest::"showroom staff can
   only see and receive stock transfers bound for their own showroom"`
   and `FinishedGoodsModuleTest::"a showroom staff user only sees stock
   for their own location, even if another is requested"`. Order/Party/
   Booking/etc. have **no** per-record ownership restriction (e.g. a
   Merchandiser can view another Merchandiser's Order) — this is
   deliberate, not an oversight: nothing in the PRD describes "my orders
   only" visibility, every relevant role's permission grant in
   `RoleSeeder` is already flat or `location.view`-style module-wide,
   and the small-team nature of this ERP (client is one factory with a
   handful of named roles, not a multi-tenant platform) makes row-level
   ownership scoping outside the one case sdd.md actually calls out
   (`location_id`) an unrequested feature, not a security gap. Flagging
   this reasoning explicitly rather than silently deciding it.
2. Role/permission checks exist only on the frontend — **FALSE**. Every
   route added this phase (`Location`, `RawMaterial`, `Production`,
   `FinishedGoods`) is gated server-side with `permission:*` middleware;
   verified by reading each `routes/api.php`, not assumed. Covered by a
   "user without permission X gets 403" test in every new module's test
   file (`ProductionRegisterTest`, `CuttingModuleTest`,
   `SewingQcModuleTest`, `FinishedGoodsModuleTest`,
   `StockTransferModuleTest`, `PurchaseOrderModuleTest`).
3. Mass assignment possible — **was TRUE (5 latent instances), now
   FIXED.** Already logged in detail in this session's working notes:
   `Model::preventSilentlyDiscardingAttributes(true)` was added globally
   in `AppServiceProvider::boot()`, which surfaced (via a full
   `::create(`/`->create([`/`new Model([` call-site audit against every
   model's `#[Fillable]` list, not left to be found by accident at
   runtime) 5 real bugs: `OrderLineItem.total_price`,
   `BookingLineItem.total_value`, `Budget.total_value`,
   `Costing.total_cost` (all Phase 3, all missing from `#[Fillable]`
   despite being written server-side on every create), and
   `RawMaterialPurchaseOrderItem.total_price` (Phase 4, caught before
   ever being committed). In every case the original code's own comment
   claimed excluding the field from `#[Fillable]` was "what stops a
   client from setting the total" — false: the real defense is that the
   FormRequest never accepts that field from the client in the first
   place, so excluding it from `#[Fillable]` only broke the legitimate
   server-side write. Fixed by adding each field to its model's
   `#[Fillable]`, not by loosening the guard.
4. Self role-escalation — **FALSE**, unchanged from Pass 1 (no new
   profile/role-adjacent endpoints this phase).
5. Financial write endpoints reachable by read-only roles — **N/A,
   still not implemented** (Modules/Accounting is Phase 6). Re-check
   then. Phase 4's closest equivalent — stock-affecting writes — *is*
   in scope here and is covered under §7 below instead, since
   "financial" in the original checklist item means Accounting
   specifically.

**§7 Business-Logic / Traceability Integrity** *(the core of this
phase's actual purpose)*

1. A Piece Serial can be generated more than once — **FALSE**.
   `piece_serials.serial` is `string()->unique()` at the DB level (see
   migration), not just a uniqueness check in `CuttingService`; a
   duplicate insert would throw a DB-level unique-constraint exception,
   not silently succeed. `CuttingService::finalize()` also has its own
   idempotency guard (rejects finalizing the same Cut Ticket twice with
   a 422 before generating anything), which is the actual path that
   would otherwise produce colliding serials. Covered by
   `CuttingModuleTest::"...unique serials per piece"` and the
   idempotency test.
2. Finished Goods stock can go negative without blocking/flagging —
   **FALSE for the one write path that can move stock without an
   existing physical unit (Stock Transfer dispatch).**
   `StockTransferService::dispatch()` calls `FinishedGoodsStockService
   ::stockOf()` and throws a 422 `ValidationException` before posting
   any movement if `quantity > $available`. QC intake (`+1`, always
   backed by a real cut/sewn piece) and transfer receipt (bounded by
   whatever was actually dispatched) can't independently create a
   negative balance. Shipment's deduction call site is documented as
   Phase-4-added wiring in `FinishedGoodsStockService`'s docblock but
   the actual `Modules/Shipment` controller call to `::shipment()` was
   **not** wired up this phase — **TRUE gap, tracked for the Phase 5/8
   pass**: shipping can currently be recorded without checking Finished
   Goods availability, since Shipment predates this ledger (Phase 3) and
   wasn't revisited. Not fixed now because doing so correctly requires
   deciding whether Shipment's existing `total_quantity` field should
   decompose into per-style/color/size lines to match the ledger's key
   — a schema question, not a one-line fix, and out of the "stock
   quantities" ambiguity this project's instructions say to ask about
   rather than guess. Flagging for the Phase 8 pass / a user decision
   rather than silently deferring it again.
3. Raw material stock can go negative silently — **TRUE, by deliberate
   design, not a bug.** `RawMaterialStockService::issue()` posts a
   negative movement unconditionally with no balance check (this is
   pre-existing Phase 4 RawMaterial code, not something changed this
   pass). PRD v2 only specifies reorder-level *alerts* for raw material
   (`isBelowReorderLevel()`), never a hard block on issuing more than is
   on hand — unlike Finished Goods, where blocking is justified by "you
   physically cannot ship a unit that doesn't exist," raw material in a
   real cutting floor is sometimes issued against a receipt that's
   still in transit/paperwork, so a hard block would fight how the
   client's floor actually operates. Documented here as an explicit,
   reviewed decision rather than an unreviewed gap — revisit only if the
   client explicitly asks for a hard stop instead of the reorder alert.
4. A Stock Transfer can be "Received" for more than "Dispatched" — this
   phase's design **intentionally allows it** but never silently: any
   `quantity_received != quantity_dispatched` (including *over*-receipt)
   sets `status = discrepancy` rather than `received`, and the ledger
   records exactly what was actually received, not what was claimed
   dispatched. **FALSE** as a silent-corruption risk; verified by
   `StockTransferModuleTest::"receiving less than dispatched marks the
   transfer as a discrepancy"`.
5. An Outward Subcontract return recorded for more pieces than issued —
   **N/A, not yet implemented** (Phase 5). Re-check then.
6. Hard-delete of a parent record with financial/traceability children —
   **FALSE**. Every new model this phase (`Location`, `RawMaterial`,
   `RawMaterialStockMovement`'s parent PO, `CutTicket`, `Bundle`,
   `PieceSerial`, `StockTransfer`) uses `SoftDeletes`; the two ledger
   tables (`RawMaterialStockMovement`, `FinishedGoodsMovement`) have no
   delete route at all — append-only, not even soft-deletable via the
   API, matching sdd.md §5's "ledger is the source of truth."
7. Race condition — two requests both pass a stock check and both
   deduct the same unit — **FALSE for the sequence-number generators,
   was TRUE for a different reason, now FIXED.** `PurchaseOrderNumber
   Generator`/`ShipmentInvoiceNumberGenerator`/`StockTransferNumber
   Generator` all use `lockForUpdate()` inside a `DB::transaction()`,
   correctly serializing concurrent number generation. Separately found
   (not a race condition, a plain constraint-ordering bug) while tracing
   this: `ShipmentController::store()`, `PurchaseOrderController::store()`,
   and this phase's new `StockTransferService::dispatch()` all called
   `->save()` on the header row **before** `year`/`sequence_no` were
   assigned — and both columns are `NOT NULL` with no default (see each
   migration). Under strict SQL mode (MySQL's default, and enforced by
   SQLite too) the *first* insert would fail outright, before the race-
   safety logic ever mattered. Fixed in all three controllers/services
   by computing the sequence first, then doing exactly one `save()` with
   every NOT NULL column already populated — and the same bug, one level
   removed, existed in the *factories* for these three models
   (`ShipmentFactory`, `RawMaterialPurchaseOrderFactory`, and this
   phase's new `StockTransferFactory`), which computed the sequence in
   an `afterCreating()` callback that runs strictly after Laravel's
   factory `create()` has already performed its own first insert. Fixed
   by moving the sequence computation into each factory's `definition()`
   instead. (`CutTicketFactory` does **not** have this bug despite
   `CutTicket` deliberately excluding `status` from `#[Fillable]` —
   Laravel's `Factory::make()` wraps model construction in
   `Model::unguarded()`, which bypasses mass-assignment guarding
   entirely for factory-built instances; verified this is real Laravel
   behavior, not assumed, before ruling it a non-issue.)

**§9/§10 (spot-check)** — no new dependencies added this phase
(`bcmath` is a PHP extension, not a Composer package); every new
paginated list endpoint caps `per_page` (`min((int) $request->integer
('per_page', N), CAP)`) the same way Phase 2/3 endpoints do — verified
by grep across every new controller, not assumed from the pattern
existing elsewhere.

**Fixes applied this pass:** `app/Providers/AppServiceProvider.php`
(`preventSilentlyDiscardingAttributes`), `#[Fillable]` additions to
`OrderLineItem`, `BookingLineItem`, `Budget`, `Costing`,
`RawMaterialPurchaseOrderItem`; `Modules/Location/App/Http/Controllers
/StockTransferController.php` (location-scoping guard) and
`Modules/FinishedGoods/App/Http/Controllers/FinishedGoodsController.php`
(location-scoping on `stock`/`movements`); sequence-before-save fix in
`Modules/Shipment/App/Http/Controllers/ShipmentController.php`,
`Modules/RawMaterial/App/Http/Controllers/PurchaseOrderController.php`,
`Modules/Location/App/Services/StockTransferService.php`, and the
matching factory fix in `ShipmentFactory`,
`RawMaterialPurchaseOrderFactory`, `StockTransferFactory`.

**Open item carried to Phase 5/8:** Shipment's Finished Goods deduction
(`FinishedGoodsStockService::shipment()`) is not yet called from
`Modules/Shipment` — shipping currently doesn't check or deduct
Finished Goods stock. Needs a decision on whether `Shipment` should gain
per-style/color/size line items to match the ledger's key, or deduct
against `total_quantity` some other way — a stock-quantity-schema
question, not guessed at here.

---

#### Pass 3 — after Phase 7 (Report, Setting), scope: full pass, §1–§10

A genuinely full pass, not a spot-check, run as six parallel research
agents (not this session's own memory) each tracing real code — one per
checklist section-group (§1/§6/§8, §2, §3, §4/§5, §7, §9/§10) — against
the complete codebase through Phase 7 (Party, Order, Booking, Budgeting,
Costing, Sampling, Shipment, Location, RawMaterial, Production,
FinishedGoods, Subcontract, Accounting, Hrm, Report, Setting). Findings
below are synthesized from those traces, with file:line evidence
verified before any fix was applied.

**§1 Authentication & Session / Token Handling — all 7 items FALSE**
(item 5, password reset, still N/A — not implemented). Zero regressions
across Phase 5–7: all 18 modules' `routes/api.php` use the `auth.api`
group, none reverted to bare `auth:api`.

**§2 Authorization / Access Control — all 5 items FALSE.** Every route
added in Phase 5–7 is permission-gated matching `RoleSeeder.php`; mass
assignment audited across every new model with no exploitable path;
financial writes (Accounting, Hrm salary pay) correctly restricted to
Accountant/Commercial/Admin. One **data-integrity note** (not a §2
verdict-changing finding — no ownership boundary was bypassed, since any
Accountant/Commercial can already touch any party/cheque by design):
`VoucherService`/`StoreVoucherRequest` accepted a `cheque_id` and
`party_id` independently with no check that the cheque was actually
issued for that party. **Fixed anyway** — `StoreVoucherRequest::
withValidator()` (`Modules/Accounting/App/Http/Requests/
StoreVoucherRequest.php`) now rejects attaching a cheque to a voucher
naming a different party than the cheque's own `party_id` (when both are
set). Covered by a new test in `AccountingModuleTest.php`.

**§3 File Upload Vulnerabilities — 4/5 FALSE, 1 TRUE, now FIXED.** All
four upload points (Party image, Order/Booking item images, Hrm
NID/passport) validate real file content (`image`/`mimes:` rules, not
extension), store under server-generated random filenames outside the
public web root (no `storage:link` exposes them, and no download route
even serves them back yet), and enforce a 4MB size cap — items 1–4
FALSE. Item 5 was **TRUE**: uploaded image bytes were persisted exactly
as received, with no server-side re-encode step, so a polyglot file
would be stored byte-for-byte (low practical risk today since nothing
serves these files back yet, but a real gap once a download/view
endpoint is added — and `PartyResource`/`OrderResource`/
`BookingResource` already expose the paths, so that endpoint is clearly
coming). **Fixed**: new `App\Services\ImageUploadService::
storeReencoded()` (`backend/app/Services/ImageUploadService.php`) decodes
every uploaded image via PHP's bundled `gd` extension (no new Composer
dependency — this sandbox can't verify one resolves via `composer
install`) and re-encodes it as a fresh JPEG before storing, flattening
any transparency onto white first. Wired into all six upload call sites
across `PartyController::store()/update()`, `OrderController::
store()/update()`, `BookingController::store()/update()`. Hrm's
NID/passport uploads (which allow PDF, not just images) are
intentionally left untouched — re-encoding doesn't apply to PDFs; noted
in the fix that any future document-viewer endpoint should force
`Content-Disposition: attachment` instead. Covered by a new test in
`PartyModuleTest.php` asserting the stored file is under a random name
and decodes as a real re-encoded image.

**§4 LFI/RFI/Path Traversal — all 3 items FALSE.** No dynamic
`include`/`file_get_contents`/`Storage::get` from user input anywhere;
no remote-URL fetch code path exists (confirmed `Modules/Setting`'s
`company.logo_path` is a plain string, never fetched); no
download/export endpoint exists at all yet (§10.3 below), so nothing to
have a path-traversal parameter.

**§5 Injection — all 3 items FALSE.** Every `selectRaw`/`havingRaw`
usage (`Modules/Report/App/Services/ReportService.php`,
`Modules/Accounting/App/Http/Controllers/TransactionController.php`,
`Modules/FinishedGoods/App/Http/Controllers/FinishedGoodsController.php`)
is a hardcoded aggregation string with user input applied only via
bound `where()`/`whereDate()` calls or a whitelist (`ReportService::
partyLedger()`'s `$type` check). No shell-exec anywhere. Zero `v-html`
usages across all 47+ `.vue` files in the frontend.

**§7 Business-Logic / Traceability Integrity — 5/7 FALSE, 2 findings.**
- Items 1, 4, 6 (Piece Serial uniqueness, Stock Transfer over-receipt,
  hard-delete of financial parents) — **FALSE, unchanged**, re-verified
  against Accounting (`Voucher`/`PartyBill`/`BankAccount` all
  soft-deletable or have no destroy route at all) and Hrm (`Employee`
  soft-deletes, `SalaryPayment`'s FK is `restrictOnDelete()`).
- Item 3 (raw material negative stock) — **TRUE, unchanged deliberate
  design** from Pass 2, re-confirmed no new write path bypasses it
  (Subcontract's `issueRawMaterial()` still routes through the same
  `RawMaterialStockService`/`CuttingService`).
- Item 5 (Outward Subcontract over-return) — **the Pass-2 "N/A, Phase 5"
  placeholder is now resolved: FALSE.**
  `SubcontractOutwardService`'s return/write-off path scopes to
  `outstanding()` pieces on that exact order and 422s on any mismatch —
  structurally can't over-return or double-resolve a piece.
- Item 2 (Finished Goods negative stock) — **TRUE, still open, 3rd
  pass in a row.** `ShipmentController::store()` still never calls
  `FinishedGoodsStockService::shipment()` — shipping still records
  with zero Finished Goods availability check. **Deliberately NOT
  guess-fixed again**: PRD v1 §3.6 only ever describes Shipment as
  carrying a single `total_quantity` per order, while the Finished
  Goods ledger is necessarily keyed per style/color/size (required for
  the traceability PRD v2 exists to add) — an order can have several
  style/color/size line items, so there is no non-guessing way to
  decide which specific units a bare `total_quantity` deducts against
  without either (a) adding a Shipment line-item breakdown (a real
  schema addition beyond what PRD v1 specifies) or (b) picking an
  arbitrary deduction order that risks silently corrupting the
  traceability chain the whole system exists to protect — worse than
  the current honest gap. A check-without-deduct half-fix was
  considered and rejected: it would look like a guard while still
  allowing repeated over-shipment of the same order (nothing would ever
  decrement what it checks against). This needs an explicit product
  decision, surfaced directly to the user rather than guessed a third
  time.
- Item 7 (race condition / NOT-NULL-before-save class) — **TRUE, one
  NEW regression found and fixed.** `SubcontractNumberGenerator` itself
  correctly uses `lockForUpdate()`, but
  `SubcontractOrderController::store()` called it **outside** any
  `DB::transaction()` — a bare `lockForUpdate()` outside a transaction
  commits (and releases its lock) immediately after the `SELECT`,
  giving two concurrent requests no real protection against reading the
  same `max(sequence_no)` before either has inserted (the DB-level
  `unique(['year','sequence_no'])` constraint would turn this into a
  500 for the losing request rather than silent duplicate numbering,
  but it's still the exact race-condition class this item targets, and
  the code's own comment incorrectly claimed it already followed "the
  same lesson as Shipment/PurchaseOrder/StockTransfer" when only the
  NOT-NULL-before-save half of that lesson was applied). **Fixed** by
  wrapping the whole sequence-fetch-and-save block in
  `DB::transaction()`, matching every sibling controller. `Voucher`'s
  equivalent path (`VoucherService::record()`) was already correct;
  Hrm has no sequence-number generator at all (plain auto-increment
  IDs), so this bug class doesn't apply there.

**§9 Dependency & Platform.** `npm audit` (frontend): **0
vulnerabilities**, 309 total dependencies. `composer audit`: **could
not be run** — no `vendor/` directory and no `composer install`
capability in this sandbox (same constraint as every phase since
Phase 2); `composer.json`'s pinned majors (`laravel/framework ^13.17`,
`laravel/passport ^13.0`, `nwidart/laravel-modules ^11.0`,
`spatie/laravel-permission ^6.10`) are all current, actively-maintained
lines on manual inspection — genuine `composer audit` needed in a real
environment before this sub-item is fully closed (tracked for Phase 9's
`user_usage_guide.md` walkthrough / initial deploy). CORS: `config/
cors.php` still doesn't exist in the repo (never published) — not
exploitable today since the SPA is Bearer-token-only, no cookies, but
flagged as an operational item to configure deliberately (not `*` +
credentials) before any cross-origin production deploy.

**§10 General Availability / DoS.**
- Pagination caps — **FALSE** for every genuine list endpoint added in
  Phase 5–7 (`Subcontract`, `Accounting`'s cash/cheque/voucher/
  party-ledger, `Hrm`'s employee/salary all cap `per_page` at 100).
  Master-data endpoints (categories, bank accounts, designations,
  settings) return unbounded `->get()` but are inherently small
  (dozens of rows) — not a DoS vector. `Modules/Report`'s aggregate
  endpoints return unbounded date-range aggregates by design (matches
  the module's own README) — fine at the PRD's stated data volume,
  worth a date-range cap only if that changes.
- Rate limiting beyond login — **TRUE, now FIXED.** An `api` rate
  limiter (120/min per user/IP) was defined in `AppServiceProvider::
  boot()` but never actually wired to any route — every authenticated
  write endpoint (voucher creation, stock movements, salary payment,
  everything) had zero request-rate ceiling beyond the login/refresh
  throttle and the permission gate itself. **Fixed** by adding
  `'throttle:api'` to the shared `auth.api` middleware group in
  `bootstrap/app.php` — every module route already uses that group, so
  the fix applies everywhere at once rather than needing to be added
  route-by-route.
- Export endpoints with no size cap — **N/A**, still no export
  endpoint of any kind exists anywhere in the codebase (confirmed via
  grep, matches `Modules/Report/README.md`'s own documented gap);
  re-check when one is built.

**Fixes applied this pass:** `bootstrap/app.php` (`throttle:api` added
to the `auth.api` group); `app/Services/ImageUploadService.php` (new —
GD-based re-encode-on-upload) wired into `Modules/Party/App/Http/
Controllers/PartyController.php`, `Modules/Order/App/Http/Controllers/
OrderController.php`, `Modules/Booking/App/Http/Controllers/
BookingController.php`; `Modules/Subcontract/App/Http/Controllers/
SubcontractOrderController.php` (wrapped sequence-fetch-and-save in
`DB::transaction()`); `Modules/Accounting/App/Http/Requests/
StoreVoucherRequest.php` (cheque/party coherence check). New tests:
`PartyModuleTest.php` (re-encode-on-upload), `AccountingModuleTest.php`
(cheque/party mismatch rejected). `npm test -- --run` re-run clean
after these changes (unaffected — all changes were backend-only);
backend changes are lint-clean (`php -l` across the entire `backend/`
tree, zero failures) but, as with every phase since Phase 2, could not
be exercised by Pest (`composer install`/MySQL unavailable in this
sandbox).

**Still-open item, carried forward a 3rd time, needs a human decision
(not guessed):** Shipment's Finished Goods deduction. See §7 item 2
above for the full reasoning — this is flagged explicitly to the user
in this session's own summary, not silently deferred again.
