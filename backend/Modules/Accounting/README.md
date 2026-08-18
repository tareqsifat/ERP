# Accounting Module

## What this solves

PRD v1 §3.9/§3.12/§3.13/§4.8/§4.11/§4.12 — a lightweight accounting
suite: Bank Accounts, Cash in Hand, Cheques, Income/Expense categories,
Credit/Debit Vouchers, Monthly Transactions, Party Ledger, Party Due
List, and Loss & Profit. This is also where Modules/Party's
`total_bill`/`paid`/`advance`/`due`/`balance` figures finally get
computed — see `App\Services\PartyFinancialsService` and
`PartyResource`'s `financials` key.

## Main entities

- `App\Models\BankAccount` / `CashTransaction` (singular pool, no
  per-account cash) — directories/ledgers. Balances are never stored
  columns: `App\Services\BankLedgerService::balanceOf()` and
  `CashLedgerService::balance()` are always `SUM(signed amount)` over
  `bank_transactions`/`cash_transactions` (sdd.md §5 — same contract as
  every other ledger in this system).
- `App\Models\Cheque` — Passed/Unused. Deliberately does **not** move the
  bank ledger at issue/receipt time; `App\Services\ChequeService::
  markPassed()` is the one place that does, modeling real clearing lag.
- `App\Models\AccountingCategory` — one table, `kind` (`income`/
  `expense`) discriminates instead of two near-identical tables.
- `App\Models\Voucher` — the central transaction record (`CR-YYYY-NNNN`
  / `DR-YYYY-NNNN`, separate per-type sequences). Created only through
  `App\Services\VoucherService::record()`, which posts to exactly one of
  the cash/bank ledgers per `payment_type` (`cheque` posts nothing —
  deferred to `ChequeService::markPassed()`).
- `App\Models\PartyBill` — append-only "this party was billed X" facts.
  Combines with Voucher rows (`purpose = payment`/`advance`) in
  `PartyFinancialsService::summarize()` to produce a party's
  total_bill/paid/advance/due/balance.

## Party financials: how the numbers are defined

`App\Services\PartyFinancialsService::summarize(Party $party)`:

- `total_bill` = `SUM(PartyBill.amount)` for the party
- `paid` = `SUM(Voucher.amount)` where `party_id` matches and
  `purpose = 'payment'` (regardless of credit/debit `type` — a Buyer's
  payment to us is a credit voucher, a Supplier/Subcontractor's payment
  from us is a debit voucher, but "money changed hands with this party"
  is the same concept either way)
- `advance` = same, but `purpose = 'advance'`
- `due` = `max(total_bill - paid, 0)`
- `balance` = `advance - due` (positive = prepaid beyond what's due;
  negative = still owed beyond any advance held)

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET/POST/PUT/DELETE | `/api/v1/accounting-categories[/{id}]` | Income/Expense category masters | `accounting.voucher.view`/`.create` |
| GET/POST/PUT/DELETE | `/api/v1/bank-accounts[/{id}]` | Bank Accounts directory | `accounting.bank.manage` |
| GET | `/api/v1/bank-accounts/{id}/transactions` | One account's ledger | `accounting.bank.manage` |
| POST | `/api/v1/bank-accounts/{id}/deposit` \| `/withdraw` | Direct manual adjustment | `accounting.bank.manage` |
| GET | `/api/v1/cash` | Cash ledger + running balance | `accounting.cash.manage` |
| POST | `/api/v1/cash/increase` \| `/reduce` | Adjust cash in hand | `accounting.cash.manage` |
| GET/POST | `/api/v1/cheques` | Cheque registry | `accounting.cheque.manage` |
| POST | `/api/v1/cheques/{id}/mark-passed` | Clear a cheque → posts the bank ledger | `accounting.cheque.manage` |
| GET/POST | `/api/v1/vouchers[/{id}]` | Credit/Debit Vouchers | `accounting.voucher.view`/`.create` |
| GET | `/api/v1/party-ledger[?type=]` | Party Ledger **and** Party Due List (same data) | `accounting.ledger.view` |
| GET | `/api/v1/party-ledger/{party}` | One party's financials + bills + vouchers | `accounting.ledger.view` |
| POST | `/api/v1/party-ledger/{party}/bills` | Record a bill against a party | `accounting.voucher.create` |
| GET | `/api/v1/transactions?year=&month=` | Monthly Transaction daily rollup | `accounting.transaction.view` |
| GET | `/api/v1/cashbook?from=&to=` | Daily Cashbook | `accounting.cashbook.view` |
| GET | `/api/v1/loss-profit?year=` | Loss & Profit summary | `accounting.loss-profit.view` |

## Depends on / depended on by

- Depends on: Party (vouchers/bills tie to a party).
- Depended on by: Modules/Party (`PartyResource.financials`,
  `Party::bills()`/`vouchers()` relations) — the one place in this
  system where a "later" module (Accounting) is reached into from an
  "earlier", more foundational one (Party), same precedent as
  Modules/Production ↔ Modules/Subcontract's two-way `CutTicket` ↔
  `SubcontractOrder` reference.

## Known simplifications (documented, not bugs)

- **Vouchers/bills are manually recorded, not auto-derived from
  Order/Purchase Order/Subcontract totals.** A real "what does this
  Buyer owe us" figure would ideally sum confirmed Order line-item
  values; this v1 keeps it simple (an Accountant records a `PartyBill`
  row when they raise an invoice, same "lightweight accounting suite"
  spirit as the rest of the PRD) rather than building cross-module bill
  aggregation across Order/RawMaterial-PO/Subcontract, each of which
  bills differently. Auto-posting from those modules into Accounting is
  a natural follow-up, not built here.
- **Party Due List's "Credit Voucher"/"Debit Voucher" tabs** (PRD
  §4.11) aren't separate backend endpoints — they're just
  `GET /vouchers?type=credit` / `?type=debit`, rendered as a tab on the
  frontend.
- **Cheque clearing has no "bounced" state** — only `unused`/`passed`.
  A bounced cheque in v1 is handled the same way any other correction
  is: an offsetting Voucher, not a third Cheque status.
- **Monthly Transaction rollup groups by `date`+`type`**, not further by
  category — matches the PRD's own table columns (Date, Total
  Transaction, Total Amount, Type), not a deeper breakdown.
