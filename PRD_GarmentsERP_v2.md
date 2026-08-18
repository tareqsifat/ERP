# Product Requirements Document — v2

## Garments ERP (Vishesh Textiles)

Document Date: 2026-08-16
Supersedes: `PRD_GarmentsERP_2026-07-29.doc` (v1, competitor audit)

---

## 0. What changed from v1 and why

v1 was produced by auditing an existing competitor's admin panel
(garmentserp.acnoo.xyz). It correctly captures the commercial/office
side of a garments ERP — order intake, booking, budgeting/costing,
sampling, shipments, a light accessory inventory, accounting, and
HRM. It does **not** cover the physical factory-floor reality the
client actually described:

| Gap | v1 status | v2 fix |
|---|---|---|
| Piece-level traceability after cutting | Missing entirely | New §3.17 Cutting & Serial Generation |
| Piece-level traceability after sewing → finished goods | Missing entirely | New §3.18 Sewing & Finished Goods Intake |
| Raw material inventory (fabric, buttons, zipper, thread, poly, cartons, labels — "everything") | Only a thin "Accessories" master + PO list | New §3.19 Raw Material Inventory (replaces/extends Accessories) |
| Finished goods inventory (post-QC, ready to ship/transfer) | Missing entirely | New §3.20 Finished Goods Inventory |
| Multi-location: 1 factory, 1 main store, 3 showrooms | Not modeled — single implicit location | New §3.21 Locations & Stock Transfer |
| 10–20 sewing machines / line tracking | Not modeled | New §3.22 Machine & Line Register (lightweight) |
| Subcontracting **out** (client gives work to others) | Missing entirely | New §3.23 Subcontract — Outward |
| Subcontracting **in** (client takes work from others) | Missing entirely | New §3.24 Subcontract — Inward |
| Payroll | Basic salary pay/due tracking only | Kept as-is for v1 scope; flagged as a v3 candidate (attendance-based payroll, deductions, PF) — see Out of Scope |

Everything from v1 §1–§10 that is not called out above is retained
and still applies (order/booking/costing/sampling/shipment
lifecycle, accounting suite, party ledger, user roles, reporting,
settings). This document is the single source of truth going
forward; treat the original `.doc` as historical reference only.

---

## 1. Executive Summary

Garments ERP is a web-based ERP for a garment manufacturing and
export business ("Vishesh Textiles") that runs **one factory, one
central store/warehouse, and three retail showrooms**, with **10–20
sewing machines** on the factory floor, and that both **subcontracts
work out** to external units and **takes subcontract work in** from
other factories.

The system centralizes:

- Order lifecycle: intake, budgeting, costing, sampling, production,
  shipment
- Booking management tied to orders (fabric/garment spec capture)
- **Raw material inventory** — fabric and all trims/accessories
  (buttons, zippers, thread, labels, poly bags, cartons, elastic,
  interlining, etc.) with stock in/out and reorder alerts
- **Piece-level production traceability** — every cut piece gets a
  serial number at cutting; every sewn item gets tracked through to
  finished-goods intake, carrying that traceability forward
- **Finished goods inventory** across the main store and the 3
  showrooms, with stock transfer between locations
- **Subcontracting**, both directions: issuing raw material/cut
  pieces to outside vendors and tracking their return (outward), and
  receiving job-work orders from other factories (inward)
- Full accounting suite (bank, cash, cheques, income/expense,
  vouchers, party ledger, daily cashbook)
- Party (buyer/supplier/subcontractor) relationship management with
  balances and dues
- HRM: designations, employees, salary payment tracking
- Reporting suite (financial + operational + traceability)
- Role-based access control, system/company settings

### Target Users / Personas

| Role | Scope |
|---|---|
| Super Admin (factory owner) | Full access, all locations, all modules |
| Merchandiser | Orders, bookings, buyer liaison |
| Accountant | Vouchers, ledgers, cashbook, banking |
| Commercial | Banking, shipments, LC/documents |
| Production / Cutting Master | Cutting entries, serial generation, line assignment |
| Line Supervisor | Sewing line output entry, WIP status |
| Store Keeper (Raw Material) | Raw material receipt, issue, stock counts |
| Store Keeper (Finished Goods) | Finished goods intake, transfer to showrooms, dispatch |
| Showroom Staff | View/receive stock transfers for their showroom |
| Buyer / Supplier / Subcontractor | Party records; portal login optional, not required for v1 |

---

## 2. Site Map & Navigation (updated)

Existing v1 site map is retained. New/changed entries:

- Manage Inventory
  - **Raw Materials** (/raw-materials) — incl. Create Raw Material tab
  - **Raw Material Stock** (/raw-materials/stock) — receipts, issues, current balance, reorder alerts
  - Units (/units) *(unchanged)*
  - ~~Accessory List / Accessory Orders~~ → folded into Raw Materials as a category, see §3.19
- Production
  - **Cutting** (/production/cutting) — cut ticket entry, serial/bundle generation
  - **Sewing Line Output** (/production/sewing) — daily line input/output against bundles
  - Production List (/productions) *(unchanged — daily production tracking table)*
  - **Piece Traceability Lookup** (/production/trace/{serial}) — search any serial, see full history
- **Finished Goods** (new top-level group)
  - Finished Goods Inventory (/finished-goods) — by location
  - Stock Transfer (/stock-transfers) — factory store ↔ showroom
- **Locations** (new)
  - Locations List (/locations) — Factory, Main Store, Showroom 1/2/3
- **Subcontracting** (new top-level group)
  - Outward Subcontract (/subcontract/outward) — orders given to external vendors
  - Inward Subcontract (/subcontract/inward) — job work taken from other factories
  - Subcontractor Ledger (/subcontract/ledger) — cost/billing per subcontractor
- Manage Machines
  - **Machine Register** (/machines) — machine list, status, line assignment
- (all other v1 sections unchanged: Order Management, User Management,
  Accounts & Bank, Party List, HRM Management, Party Due List, Loss
  Profit, Reports, Settings, My Profile)

---

## 3. Feature Catalog — new/changed sections

*(Sections 3.1–3.16 from v1 are retained unchanged; not repeated
here. See §0 table above for the mapping.)*

### 3.17 Cutting & Serial Generation

Cutting is done against an approved Order + Booking. A **Cut
Ticket** is created per style/color/lay, recording fabric
consumption (pulled from Raw Material stock), planned quantity, and
cutting date. On save, the system generates:

- A **Bundle** for each cut lot (configurable bundle size, e.g. 20–30
  pcs), and
- A **unique Serial Number per piece** within that bundle, using the
  pattern `{OrderNo}-{Style}-{Color}-{CutDate:YYMMDD}-{BundleSeq}-{PieceSeq}`
  (e.g. `0000012-A1-BLK-260816-003-014`).

Each serial carries: order, style/color/size, cut date, cutter/line
assigned, and status (`Cut` → `In Sewing` → `Sewn` → `QC Passed` /
`QC Rejected` → `Finished Goods`). Barcode/QR generation per bundle
(printable label) is in scope for v1 UI; per-piece physical barcodes
are optional (v1 supports it technically, factory decides whether to
print at piece or bundle level).

### 3.18 Sewing & Finished Goods Intake

Line Supervisors log daily line input (bundles received into a line)
and output (bundles/pieces completed) against the Production List
table (existing v1 structure, now backed by real bundle/serial
data instead of manual counts). On output:

- Pieces move to status `Sewn`, then through QC (`QC Passed` /
  `QC Rejected` — rejected pieces get a defect reason and route back
  to rework or scrap).
- QC-passed pieces are received into **Finished Goods Inventory**
  at the **Main Store** location, closing the traceability loop from
  cut piece → finished unit.

### 3.19 Raw Material Inventory

Replaces and extends v1's "Accessory List / Accessory Orders" with a
full raw material module covering **fabric and every trim item** —
buttons, zippers, thread, interlining, elastic, labels, hang tags,
poly bags, cartons, and any other consumable.

- **Raw Material Master**: name, category (Fabric / Trim /
  Packaging / Other), unit (kg, meter, pcs, cone, roll…), reorder
  level, default supplier, unit cost, active/inactive.
- **Stock Ledger**: every receipt (from Purchase Order or
  subcontract return) and every issue (to cutting, to a sewing line,
  or to an outward subcontractor) is a ledger entry with running
  balance per material.
- **Purchase Orders**: (evolves v1 Accessory Orders) — order raw
  material from a Supplier party, receive against PO (partial
  receipt supported), auto-updates stock ledger and party dues.
- **Reorder Alerts**: dashboard/report flags materials at or below
  reorder level.
- **Consumption Traceability**: fabric/trim issued to a specific Cut
  Ticket is recorded, enabling a rough per-order material cost
  rollup.

### 3.20 Finished Goods Inventory

Tracks completed, QC-passed units by **location** (Main Store or one
of the 3 Showrooms) and by order/style/color/size.

- Stock increases on QC-pass intake (§3.18) or on completed inward
  subcontract receipt (§3.24).
- Stock decreases on Shipment (existing v1 Shipments module, now
  deducts from Finished Goods) or on Stock Transfer to a showroom.
- Each finished-goods unit retains a link back to its originating
  serial number(s) for full audit trail (cut → sew → QC → store →
  showroom/shipment).

### 3.21 Locations & Stock Transfer

Models the client's physical footprint: **1 Factory**, **1 Main
Store**, **3 Showrooms** (named/configurable). Every inventory
record (raw material and finished goods) is location-scoped.

- **Stock Transfer**: move finished goods from Main Store to a
  Showroom (or between showrooms). Two-step: Dispatch (deducts
  source) → Receive (adds to destination, confirmed by Showroom
  Staff) — so stock is never "lost" in transit and discrepancies are
  visible.
- Raw material is factory/store-scoped only in v1 (showrooms don't
  hold raw material).

### 3.22 Machine & Line Register (lightweight)

- **Machine Master**: machine ID/tag, type (e.g., single needle,
  overlock, flatlock), status (Active/Under Maintenance/Idle).
- **Line**: a named grouping of machines (e.g., "Line A") that Cut
  Tickets and bundles get assigned to for output tracking (§3.18).
- v1 scope is a register + assignment, not real-time IoT machine
  monitoring — that's explicitly out of scope (see §9 Out of Scope).

### 3.23 Subcontract — Outward (work given out)

For when the factory sends cutting/sewing/finishing work to an
external vendor.

- **Subcontractor** is a Party sub-type (alongside Buyer/Supplier),
  with its own balance/dues tracking.
- **Outward Subcontract Order**: references an Order/style, lists
  quantity sent, and what was issued (cut pieces by serial, and/or
  raw material issued to the subcontractor), rate per piece/dozen
  agreed, expected return date.
- **Return / Receipt**: partial and full returns supported; returned
  pieces are QC'd on receipt (same QC flow as §3.18) before entering
  Finished Goods. Shortages/damages are recorded and reflected in the
  Subcontractor Ledger.
- **Subcontractor Ledger**: running balance of value issued vs. value
  returned/billed vs. paid — feeds Party Due List and vouchers, same
  as Buyer/Supplier.

### 3.24 Subcontract — Inward (work taken in)

For when the factory takes job-work from another factory/party.

- **Inward Subcontract Order**: references the external party as the
  "customer" for this job, records what was received from them
  (cut pieces or raw material supplied by them), agreed rate, due
  date.
- Work is processed through the normal Cutting/Sewing/QC flow
  (§3.17–3.18), tagged as inward-subcontract so it doesn't get
  counted as the factory's own finished goods stock for sale — it's
  tracked to completion and **dispatched back** to the external
  party, generating a billable invoice line (job-work income).
- Feeds Income (accounting module) as job-work revenue.

---

## 4. Data Models & Entities — additions

*(v1 §6.1–6.8 retained as-is. New entities below.)*

### 4.1 Location

| Field | Type | Notes |
|---|---|---|
| Name | String | e.g. "Factory", "Main Store", "Showroom - Gulshan" |
| Type | Enum | Factory / Store / Showroom |
| Address | String | |
| Status | Enum | Active/Inactive |

### 4.2 RawMaterial

| Field | Type | Notes |
|---|---|---|
| Name | String | |
| Category | Enum | Fabric / Trim / Packaging / Other |
| Unit | Reference (Unit) | |
| Reorder Level | Number | |
| Default Supplier | Reference (Party) | Optional |
| Current Stock | Number | Computed from ledger |

### 4.3 RawMaterialStockMovement

| Field | Type | Notes |
|---|---|---|
| Raw Material | Reference | |
| Location | Reference | Factory/Store only |
| Type | Enum | Receipt / Issue / Adjustment |
| Quantity | Number | |
| Reference | Polymorphic | PurchaseOrder, CutTicket, OutwardSubcontract, etc. |
| Date | Date | |

### 4.4 CutTicket / Bundle / PieceSerial

| Field | Type | Notes |
|---|---|---|
| Order / Booking | Reference | |
| Style / Color / Size | String | |
| Cut Date | Date | |
| Bundle No | String | Auto |
| Bundle Size | Number | |
| Piece Serial | String | Auto, unique, see §3.17 pattern |
| Status | Enum | Cut / In Sewing / Sewn / QC Passed / QC Rejected / Finished Goods / Shipped |
| Line / Machine | Reference | Optional |

### 4.5 FinishedGoods / FinishedGoodsStock

| Field | Type | Notes |
|---|---|---|
| Order / Style / Color / Size | Reference/String | |
| Location | Reference | |
| Quantity | Number | |
| Linked Serials | Array | Traceability |

### 4.6 StockTransfer

| Field | Type | Notes |
|---|---|---|
| From Location / To Location | Reference | |
| Items | Array | Style/Color/Size + Qty |
| Status | Enum | Dispatched / Received / Discrepancy |
| Dispatched By / Received By | Reference (User) | |

### 4.7 Machine / Line

| Field | Type | Notes |
|---|---|---|
| Machine Tag | String | |
| Type | String | |
| Status | Enum | Active/Maintenance/Idle |
| Line | Reference | |

### 4.8 SubcontractOrder (Outward & Inward, shared shape)

| Field | Type | Notes |
|---|---|---|
| Direction | Enum | Outward / Inward |
| Party (Subcontractor) | Reference | |
| Order / Style | Reference | |
| Items Issued/Received | Array | Serials and/or raw material |
| Rate | Currency | Per piece/dozen |
| Expected Return/Delivery Date | Date | |
| Status | Enum | Open / Partially Returned / Closed |

### 4.9 Party — extended

`Type` enum extended: `Buyer` / `Supplier` / `Subcontractor`. All
other Party fields (balance, dues, ledger) apply the same way.

---

## 5. Key Workflows — additions

### 5.1 Cutting → Finished Goods Traceability Workflow

1. Merchandiser/Production confirms Order + Booking is ready to cut.
2. Cutting Master creates a Cut Ticket: pulls required fabric from
   Raw Material stock (auto-deducts), records lay/marker quantity.
3. System generates Bundles and Piece Serials for the cut quantity.
4. Bundles are assigned to a Sewing Line.
5. Line Supervisor logs daily input/output; pieces move `Cut` →
   `In Sewing` → `Sewn`.
6. QC checks each bundle/piece; passes go to Finished Goods intake at
   Main Store, rejects go to rework/scrap with a reason code.
7. End state: Finished Goods stock increases at Main Store; every
   unit traceable back to its cut ticket and fabric lot.

### 5.2 Outward Subcontract Workflow

1. Production decides a style/quantity goes to an external vendor.
2. Store Keeper issues cut pieces (by serial) and/or raw material to
   the Subcontractor (Party), creating an Outward Subcontract Order.
3. Vendor returns finished/semi-finished pieces; Store Keeper records
   receipt (full or partial), pieces go through QC (§3.18).
4. Subcontractor Ledger updates with value owed; Accountant settles
   via Debit Voucher, same as any Supplier payment.

### 5.3 Inward Subcontract Workflow

1. External party sends cut pieces or raw material for job work.
2. Inward Subcontract Order created, items tagged so they don't mix
   with the factory's own sellable stock.
3. Work processed through Cutting/Sewing/QC as needed.
4. Completed goods dispatched back to the external party; system
   generates a job-work income entry (Voucher/Income module).

### 5.4 Stock Transfer to Showroom Workflow

1. Store Keeper (Finished Goods) selects items and quantity to send
   to a Showroom, creates a Stock Transfer (status: Dispatched).
2. Showroom Staff confirms receipt (status: Received) — or flags a
   discrepancy (short/damaged) which routes to Accountant/Admin.

---

## 6. Permissions & Access — additions

New roles beyond v1's (Admin, Buyer, Merchandiser, Commercial,
Accountant, Production):

- **Cutting Master** — Cutting module, Raw Material issue (write),
  read-only elsewhere in production.
- **Line Supervisor** — Sewing output entry only.
- **Store Keeper (Raw Material)** — Raw Material module (full),
  Purchase Order receipt.
- **Store Keeper (Finished Goods)** — Finished Goods, Stock Transfer
  (dispatch side).
- **Showroom Staff** — Stock Transfer (receive side, own showroom
  only), read-only Finished Goods for their location.

Recommendation: implement with `spatie/laravel-permission`
(roles + permissions), not just a hardcoded enum, so the owner can
adjust access later without a code change. See `sdd.md` §4.

---

## 7. Out of Scope for v1 (explicitly deferred)

- Attendance-based payroll, PF/tax deductions, payslip generation —
  v1 keeps the simple salary pay/due tracking from the original PRD.
- Real-time IoT machine monitoring/utilization dashboards.
- Buyer/Supplier/Subcontractor self-service portal logins (party
  records exist, but no external-facing login in v1).
- POS/checkout flow for showrooms (v1 is inventory *visibility* and
  transfer only, not a retail sales/POS system) — flag this
  explicitly with the client; if showrooms sell to walk-in customers
  and need a register, that's a real gap worth a follow-up
  conversation before build starts.
- Barcode scanner hardware integration (v1 generates printable
  labels; scan-to-update via a connected scanner is a fast v1.1
  add-on once the UI exists).

---

## 8. Glossary — additions

- **Cut Ticket** — a single cutting instruction/lot for a
  style/color, the source of bundles and serials.
- **Bundle** — a batch of cut pieces (e.g. 20–30) moved together
  through sewing.
- **Piece Serial** — unique identifier for one physical cut piece,
  tracked cut-to-shipment.
- **WIP** — Work in Progress; pieces between Cut and Finished Goods.
- **Job Work** — subcontracted manufacturing work, billed per
  piece/dozen rather than as a finished-goods sale.
- **Subcontract Challan** — the issue/return document accompanying
  goods sent to or received from a subcontractor.
