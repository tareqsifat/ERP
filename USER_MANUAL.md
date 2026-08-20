# Garments ERP — User Manual

**For Vishesh Textiles.** This guide walks through the system module by
module, in the order a real order would actually flow through the
factory: from taking an order, through cutting/sewing/QC, into
inventory and shipment, alongside the accounting and HR side of the
business.

**Live system:**

- App: https://erp-frontend-blue-one.vercel.app
- API: https://garments-erp-backend-production.up.railway.app

> **Before this goes live with real client data:** change the Admin
> password immediately (see "Logging in" below). No demo/sample data has
> been loaded on this deployment — everything you see after logging in
> is real data you enter yourself.

---

## 1. Logging in

Open https://erp-frontend-blue-one.vercel.app and sign in with an
email/password.

**The one login that exists right now** is the Super Admin account:

| Field | Value |
|---|---|
| Email | `admin@vishesh-textiles.example` |
| Password | `5hwjxEX39Ju9rkRGGF35` |

**Change this password from My Profile the first time you log in.**
Treat this account as the one that can do everything — create real
staff accounts for day-to-day use instead of sharing this login.

Once you've created real accounts, the sidebar only shows the sections
a logged-in user actually has permission for — if a menu item you
expect is missing, that role doesn't have access to it (this is
enforced on the backend API too, not just hidden in the menu). Roles
are managed by an Admin under **Settings** / user management.

---

## 2. Order Management

**Orders** (`/orders`) is where a new buyer order starts. Click "Add
New Order," pick the Buyer (must already exist under Party List →
Buyers) and a Merchandiser, fill in shipment/payment mode and any
specification fields you have, then add one or more line items (style,
color, item, quantity, unit price) — the grand total is computed
automatically from the line items, never typed in directly. The
Order No (e.g. `0000001`) is generated automatically once saved.

**Bookings** (`/bookings`) layer fabric/specification detail on top of
an existing Order — composition, process loss %, rib/collar, and a
similar per-style/color line item table with garment-specific
measurements.

**Budgets, Costings, Samples** (`/budgets`, `/costings`, `/samples`)
follow the same list-and-add pattern, each tied back to an Order.

**Shipments** (`/shipments`) records the shipment invoice
(`SHIP-YYYY-NNNN`) once goods are ready to leave. *Known limitation:*
recording a Shipment does not currently check or deduct Finished Goods
stock — see §9 "Known limitations" below.

---

## 3. Raw Material & Purchase Orders

**Raw Materials** (`/raw-materials`) is the master list — fabric, trim,
packaging categories, each with a reorder level. **Raw Material Stock**
(`/raw-materials/stock`) shows current stock per material per location,
computed live from every receipt/issue ever recorded (never a stored
number you could edit by mistake).

Purchase Orders bring stock in: create one against a Supplier party,
add line items, then use the "Receive" action (partial receipts are
fine — receive what actually arrived, leave the rest outstanding for
later). Each receipt posts a real stock movement.

---

## 4. Production: Cutting → Sewing → QC → Finished Goods

This is the heart of the system's traceability — every physical cut
piece is tracked individually from the moment it's cut until it leaves
the factory.

1. **Cutting** (`/production/cutting`) — create a Cut Ticket against an
   approved Order (and optionally a Booking): style, color, size, the
   raw material and quantity consumed, a Factory location, bundle size,
   and planned quantity. Saving creates the ticket in `draft`; hit
   **Finalize** to actually deduct raw material stock and generate the
   bundles and a unique serial number for every single piece
   (`{OrderNo}-{Style}-{Color}-{CutDate}-{Bundle}-{Piece}`) — this only
   happens once per ticket, so double-check before finalizing.
2. **Sewing Line Output** (`/production/sewing`) — assign a bundle to a
   line, then log its output once sewing is done. Every piece in that
   bundle moves together.
3. **QC** happens per piece (not per bundle — some pieces in the same
   bundle can pass while others don't), from the piece's own detail
   view: Pass (with the receiving store location) or Reject (with a
   reason). A passed piece is immediately received into Finished Goods
   Inventory at that location — this is the exact moment a cut piece
   becomes a sellable finished unit.
4. **Piece Traceability Lookup** (`/production/trace`) — search any
   serial number to see its full history: which order/style/color, when
   it was cut, which line sewed it, its QC result, and every Finished
   Goods movement since. Also available as one of the seven report
   types under **Report Suite** (`/reports`), with the same lookup.

**Finished Goods Inventory** (`/finished-goods`) shows current stock per
location/order/style/color/size, and **Stock Transfer**
(`/stock-transfers`) moves it between locations — dispatch from one,
receive at the other. If what arrives doesn't match what was dispatched
(short or over), the transfer is marked **Discrepancy** rather than
silently reconciled, so it surfaces for someone to look into.

**Locations List** (`/locations`) and **Machine Register**
(`/machines`) are the two supporting master lists behind all of the
above — the factory, main store, and showrooms every location picker
on this page draws from, and the machine inventory tagged to sewing
lines.

---

## 5. Subcontracting

Two independent directions, both under **Subcontracting** in the
sidebar, both tied to a Party of type "Subcontractor":

- **Outward** (`/subcontract/outward`) — work we send out. Either hand
  over already-cut pieces by serial, or issue raw material for the
  subcontractor to cut themselves (this creates a real Cut Ticket, so
  those pieces stay fully traceable). When work comes back, record
  which pieces returned (ready for QC) and which were written off
  (lost/damaged, never coming back) — the ledger tracks the value of
  each separately.
- **Inward** (`/subcontract/inward`) — paid job-work capacity we sell:
  the subcontractor brings their own cut fabric, our factory sews it. A
  Cut Ticket gets tagged to the inward job; QC leaves those pieces at
  "QC passed" instead of pulling them into our own Finished Goods (they
  were never ours) until you use **Dispatch Back**, which ships them
  out and records the job-work income.

**Subcontractor Ledger** (`/subcontract/ledger`) shows the running
value owed/receivable per subcontract order.

---

## 6. Accounts & Bank

Everything under **Accounts & Bank** revolves around one idea: a
**Voucher** (Credit or Debit) is the one place money actually moves,
and every voucher posts to exactly one of Cash, Bank, or (once a Cheque
clears) Bank again — never more than one, never silently.

- **Bank Accounts** (`/accounting/banks`) — balances are always the
  live sum of every deposit/withdrawal, shown alongside deposit/
  withdraw actions.
- **Cash in Hand** (`/accounting/cash`) — one shared pool, increase/
  reduce actions.
- **Cheques** (`/accounting/cheques`) — issuing a cheque doesn't move
  money yet; only **Mark Passed** does, modeling the real clearing
  delay.
- **Income / Expenses** (`/accounting/income`, `/accounting/expenses`)
  — category master lists a voucher can be tagged against.
- **Credit Voucher / Debit Voucher** (`/accounting/credit-vouchers`,
  `/accounting/debit-vouchers`) — the actual transaction entry points.
  Pick a purpose (Payment/Advance against a party, or General), a
  payment method (cash/bank/cheque), and amount.
- **Monthly Transaction** (`/accounting/transactions`) — a rolled-up
  view by date and type.
- **Party Ledger** (`/accounting/party-ledger`) — per-party running
  Total Bill, Paid, Advance, Due, and Balance, with a drill-down to add
  a bill.
- **Daily Cashbook** (`/accounting/cashbook`) — date-ranged register
  with a running summary panel (previous balance, credit, expenses,
  cash in hand).
- **Party Due List** (`/accounting/dues`) — the same figures as Party
  Ledger, filtered into Buyer/Supplier/Credit Voucher/Debit Voucher
  tabs.
- **Loss & Profit** (`/accounting/loss-profit`) — year-filterable Total
  Sale / Expense / Profit / Loss, netting every Credit voucher against
  every Debit voucher for the year.

---

## 7. Party List

**Buyers, Suppliers, Subcontractors** (`/parties/buyers`,
`/parties/suppliers`, `/parties/subcontractors`) — one shared list
filtered by type. Each party's card shows its live Total Bill/Advance/
Pay/Due/Balance (computed from the Accounting module, not stored on the
party itself).

---

## 8. HRM Management

**Designations** (`/hrm/designations`) is a simple master list.
**Employees** (`/hrm/employees`) captures personal details, designation,
salary, and NID/passport uploads. **Salaries** (`/hrm/salaries`) is a
month-by-month record per employee — "Open Salary Month" creates that
period's row (safe to click more than once, it won't duplicate), then
"Pay Salary" records a payment against it; Due Salary is always
`salary amount − paid so far`, recomputed live rather than a number you
edit directly. **There is no attendance tracking or attendance-based
payroll in this system** — salaries are flat monthly amounts, by
design.

---

## 9. Reports & Settings

**Report Suite** (`/reports`) is one page with seven tabs: Sales/Order,
Production, Stock, Subcontract, Party Ledger, Daily Cashbook, and Piece
Traceability Lookup — each supporting a date range where it makes
sense. These are read-only views layered on the same data every other
page shows; nothing here is a separate copy of the numbers.

**Settings** (`/settings`) has four tabs — Currency, Notifications,
System, Company — editable only by an Admin; everyone else can view
them read-only (useful since the currency format/company name affect
how the rest of the app displays).

**My Profile** — click your name in the top-right corner to view/edit
your own name, email, phone, and password.

### Known limitations (read this before relying on the numbers)

- **Shipment doesn't check or deduct Finished Goods stock yet.**
  Recording a shipment records the invoice but doesn't verify enough
  stock actually exists or reduce it. Until that's resolved, treat
  Finished Goods stock numbers as not accounting for goods that have
  already shipped.
- **Raw material stock can go negative.** Issuing more raw material
  than is on hand isn't blocked — only flagged via the reorder-level
  alert on the Stock Report. This is a deliberate choice (real cutting
  floors sometimes issue against a receipt that's still in transit/
  paperwork), not an oversight.
- **No PDF/Excel/CSV export yet** on any list or report page, even
  though the UI style throughout matches the rest of the app's
  print/export affordances. Nothing is broken — the feature simply
  hasn't been built yet.
- **Vouchers and Party Bills are entered manually**, not automatically
  generated from an Order or Purchase Order's total — someone still
  has to record "Order #12 was billed for $X" as its own step.

---

## 10. If something looks wrong

Every write action in this system goes through the same backend
permission check shown in the sidebar — if an action fails with a
permissions error, that role genuinely doesn't have that grant (ask an
Admin to check that role's permissions under Settings). If a number
looks inconsistent, most balances in this system (stock, account
balances, party dues) are computed live from an append-only transaction
log rather than stored directly — so the fix, if there is one, is
almost always in the underlying transactions, not a total that can be
hand-edited.

---

## 11. No persistent database storage yet

One important operational note not in the original design docs: the
database behind this deployment currently has **no persistent disk
volume attached** (a Railway hosting limitation being worked around —
see the project's `deployment-status.md`). Until a volume is attached,
data could be lost if the database container restarts. Avoid entering
real production data until this is confirmed resolved.
