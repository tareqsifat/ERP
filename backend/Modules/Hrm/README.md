# Hrm Module

## What this solves

PRD v1 §3.11/§4.10/§5.5/§7.5 — Designations master list, Employee
directory, and a Salaries List with a "Pay Salary" action tracking
salary amount / paid amount / due per employee per month.

**Explicitly NOT attendance-based payroll.** PRD v2 §7 Out of Scope:
"Attendance-based payroll, PF/tax deductions, payslip generation — v1
keeps the simple salary pay/due tracking from the original PRD." This
module builds exactly that simple tracking and nothing more —
`hrm.attendance.manage` stays in `PermissionSeeder`'s catalogue as an
unused, forward-looking permission for whenever that v3 candidate
actually gets built; no Attendance model/table exists in this phase.

## Main entities

- `App\Models\Designation` — plain master list.
- `App\Models\Employee` — directory. `salary` is the *current* agreed
  rate; a payroll run snapshots it onto `SalaryPayment.salary_amount`
  rather than reading this column live, so a later raise doesn't rewrite
  history for already-paid months. NID/passport uploads stored the same
  way as Party's image (sdd.md §8 — outside the public web root).
- `App\Models\SalaryPayment` — one row per employee per
  (`month`,`year`) (unique constraint), **not** an append-only movement
  ledger — a deliberate deviation from sdd.md §5's usual pattern, because
  the PRD explicitly models this as a single per-period summary row
  (SL, Employee, Month, Year, Salary Amount, Paid Amount, Due Salary,
  Payment Method, Pay Date), not a transaction log. `due_amount` is a
  computed accessor (`salary_amount - paid_amount`), never stored, so it
  can't drift from the two numbers it's derived from.

## The payroll workflow (App\Services\SalaryService)

`openMonth()` is `firstOrCreate` — opening the same employee+month twice
is a no-op, it just returns the existing row (idempotent, matches PRD
§7.5's "User navigates to Salaries List, selects an employee/month").
`pay()` adds an amount to `paid_amount` (rejecting a payment that would
overpay past `salary_amount`) and records `payment_method`/`pay_date` —
this supports paying a month's salary in installments, with `due_amount`
recomputed automatically each time.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET/POST/PUT/DELETE | `/api/v1/designations[/{id}]` | Designations master list | `hrm.designation.manage` |
| GET/POST/PUT/DELETE | `/api/v1/employees[/{id}]` | Employee directory | `hrm.employee.manage` |
| GET | `/api/v1/salaries` | Salaries List, filterable by employee/month/year | `hrm.salary.view` |
| POST | `/api/v1/salaries/open` | Open (or fetch) a month's payroll row for an employee | `hrm.salary.pay` |
| POST | `/api/v1/salaries/{id}/pay` | Pay Salary action | `hrm.salary.pay` |

## Depends on / depended on by

- Depends on: nothing outside this module.
- Depended on by: nothing yet.

## Known simplifications (documented, not bugs)

- **No attendance tracking at all** — see above.
- **`SalaryPayment` is a mutable rollup row, not a ledger** — see the
  entity description above for why this is a deliberate exception to
  sdd.md §5's usual ledger contract.
- **Salary payments always post as cash/bank *conceptually* via
  `payment_method` free text**, not wired into Modules/Accounting's
  actual cash/bank ledgers. A "Pay Salary" action doesn't move
  `CashLedgerService`/`BankLedgerService` balances — it's tracked purely
  within this module's own `paid_amount`. Wiring salary payouts into the
  real Accounting ledgers (so a cash salary payment actually reduces
  `CashLedgerService::balance()`) is a natural follow-up, not built here.
