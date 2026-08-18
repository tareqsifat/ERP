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
