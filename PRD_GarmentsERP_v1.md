# Product Requirements Document

## Garments ERP (Vishesh Textiles) — garmentserp.acnoo.xyz

*Document Date: July 29, 2026*

------------------------------------------------------------------------

## 1. Executive Summary

Garments ERP is a web-based enterprise resource planning application
purpose-built for the garment manufacturing and export industry,
operated in this instance under the brand name "Vishesh Textiles". The
system centralizes order intake, production tracking, booking and
costing, inventory of accessories, financial accounting (cash, bank,
cheques, vouchers, ledgers), human resource management, and party
(buyer/supplier) relationship management into a single authenticated
dashboard.

**Key Features:**

- Order lifecycle management: order creation, budgeting, costing,
  sampling, production tracking, and shipment
- Booking management tied to orders, with detailed fabric/garment
  specification capture
- Inventory management for units of measure and accessories, including
  accessory purchase orders
- Role-based user management (Admin, Buyer, Merchandiser, Commercial,
  Accountant, Production)
- Full accounting suite: bank accounts, cash in hand, cheques,
  income/expense categories, credit/debit vouchers, monthly
  transactions, party ledgers, and daily cashbook
- Party (Buyer/Supplier) management with balance tracking and dues
- HRM module: designations, employees, and salary payment tracking
- Reporting suite covering financial and operational reports
- Configurable system settings, currency settings, notifications, and
  company branding
- User profile/account self-management

**Target Users / Personas:** Super Admin / factory owner (full system
access and configuration), Merchandisers (order and booking creation,
buyer liaison), Accountants (financial vouchers, ledgers, cashbook),
Commercial staff (banking, shipments), Buyers/Suppliers (represented as
party records, not necessarily direct system logins), Production staff
(production tracking).

## 2. Site Map & Navigation

The application uses a persistent left sidebar with expandable module
groups, plus a top bar containing notifications and the user's profile
menu. The hierarchical site map, as discovered, is as follows:

- **Dashboard** (/dashboard)
- **Order Management**
  - Order List (/orders) — incl. Add New Order tab
  - Booking List (/bookings) — incl. Add New Booking tab
  - Budget List (/budgets) — incl. Add New Budget tab
  - Costing List (/costings) — incl. Costing Form tab
  - Sample List (/samples) — incl. Add New Sample tab
  - Production List (/productions)
  - Shipments List (/shipments) — incl. Add New Shipment tab
- **Manage Inventory**
  - Units (/units) — incl. Create Unit tab
  - Accessory List (/accessories) — incl. Create Accessory tab
  - Accessories Orders (/accessory-orders) — incl. Create New tab
- **User Management**
  - Admin (/users?role=admin)
  - Buyer (/users?role=buyer)
  - Merchandiser (/users?role=merchandiser)
  - Commercial (/users?role=commercial)
  - Accountant (/users?role=accountant)
  - Production (/users?role=production)
- **Accounts & Bank — Commercial**
  - Bank Accounts (/banks)
  - Cash in Hand (/cashes)
  - Cheques (/cheques)
- **Accounts & Bank — General**
  - Income (/income)
  - Expenses (/expense)
  - Credit Voucher (/credit-vouchers)
  - Debit Voucher (/debit-vouchers)
  - Monthly Transaction (/transactions)
  - Party Ledger (/party-ledger)
  - Daily Cashbook (/reports/cashbooks)
- **Party List**
  - Buyers (/parties?parties-type=buyer)
  - Suppliers (/parties?parties-type=supplier)
- **HRM Management**
  - Designations (/designations)
  - Employees (/employees)
  - Salaries List (/salaries)
- **Party Due List** (/dues) — tabs: Buyer, Supplier, Credit Voucher,
  Debit Voucher
- **Loss Profit** (/loss-profit)
- **Reports** (multiple report pages)
- **Settings**
  - Currency Settings
  - Notifications
  - System Settings (multiple tabs)
  - Company Settings
- **My Profile** (user account page)

Navigation flow: users authenticate at the login screen, land on the
Dashboard, and use the sidebar to drill into a module; most list pages
expose a secondary tab strip (e.g., "List" vs "Add New") allowing
in-place creation without leaving the module context.

## 3. Feature Catalog

### 3.1 Orders Management

**Order List:** Central table of all orders with columns for order
number, party, merchandiser, GSM, shipment/payment mode, season,
quantity, unit price, total value, and status. Supports per-page sizing,
text search, date-range filtering, export to PDF/Excel/CSV, print, and
pagination. Row-level actions include Print, Edit, Delete, Order
Summary, and Order Details (opens a printable invoice-style detail
view).

**Add New Order:** Comprehensive order intake form capturing party,
merchandiser, item image, auto-generated order number,
title/description, fabrication, GSM, yarn count, shipment and payment
mode, bank account, year, season, pantone, consignee/notify details,
contract and expiry dates, negotiation period, ports of
loading/discharge, payment terms, remarks, and a dynamic multi-row
line-item table (style, color, item, shipment date, quantity, unit
price, computed total price) with auto-calculated grand total.

### 3.2 Booking Management

**Booking List:** Table linking bookings to existing orders, showing
booking date, party, fabric type/composition, process loss,
ribbing/collar details, preparer, and status.

**Add New Booking:** Detailed specification form referencing an existing
order, capturing preparer, booking date, composition, process loss,
other fabrics, rib, collar, an item image, and an extensive
horizontally-scrolling table capturing per-style/color garment
specifications including shipment date, quantity, unit price, computed
value, garment description and picture, pantone, body fabrication, yarn
counts, DZN-based quantity conversions, and gray fabric/rib consumption
figures.

### 3.3 Budgeting & Costing

**Budget List / Add New Budget:** Tracks per-order budgeted quantity,
average unit price, and total value against status.

**Costing List / Costing Form:** Mirrors the budget structure for cost
tracking per order and style.

### 3.4 Sampling

**Sample List / Add New Sample:** Tracks sample requests per order
including consignee, style number, item, sample type, and garment
quantity with status.

### 3.5 Production Tracking

**Production List:** A wide, grouped-header operational table ("Daily
Production List") tracking, per order, cutting progress
(today/total/balance), print/embroidery send-and-receive status, line
input and output status (daily/total/balance), and finishing stages,
with per-order subtotal rows.

### 3.6 Shipments

**Shipments List / Add New Shipment:** Tracks shipment invoices (format
SHIP-YYYY-NNNN) per order, including creator, total quantity, and total
CBM (cubic measurement).

### 3.7 Inventory Management

**Units:** Simple master list of measurement units (pcs, dzn, kg, meter)
with active/inactive toggle status.

**Accessory List:** Master list of purchasable accessories with unit,
price, description, and active status.

**Accessories Orders:** Purchase-order style records for ordering
accessories from suppliers, tracking quantity, unit price, and total
amount.

### 3.8 User Management

Role-segmented user directories (Admin, Buyer, Merchandiser, Commercial,
Accountant, Production), each with a consistent "Add New" form capturing
full name, phone, role, username/email, password with confirmation, and
a profile image upload.

### 3.9 Accounts & Bank

A full lightweight accounting suite: Bank Accounts (account directory
with deposit/withdraw actions and running balances), Cash in Hand (cash
ledger with increase/reduce transaction types and running totals),
Cheques (passed/unused cheque tracking), Income and Expense category
masters, Credit and Debit Vouchers (transaction recording tied to
parties with payment type/method badges), Monthly Transaction summaries
(filterable by year/month), Party Ledger (per-buyer/supplier bill,
payment, advance, and due tracking), and Daily Cashbook (date-ranged
receivable/payment register with running cash-in-hand balance).

### 3.10 Party (Buyer/Supplier) Management

Buyer and Supplier directories with contact info, country, and running
financial summaries (total bill, advance, paid, due, balance). Includes
an Add New Party form capturing contact details, opening balance (with
debit/credit type), country, remarks, and an image upload.

### 3.11 HRM

Designations master list, Employee directory (with a detailed onboarding
form capturing personal details, employment type, designation, salary,
and ID document uploads), and a Salaries List tracking monthly pay runs
per employee (salary amount, paid amount, due, payment method, and pay
date) with a "Pay Salary" action.

### 3.12 Party Due List

A consolidated dues dashboard with four tabs (Buyer, Supplier, Credit
Voucher, Debit Voucher) summarizing outstanding balances across parties
and vouchers.

### 3.13 Loss & Profit

A year-filterable summary view showing total sales, total expenses,
total profit, and total loss figures for high-level financial health
monitoring.

### 3.14 Reporting

A dedicated Reports section exposing seven report types (including the
Daily Cashbook shown above) covering operational and financial data,
generally supporting date-range filtering and export/print options
consistent with the rest of the application.

### 3.15 Settings

Administrative configuration covering Currency Settings (default
currency/format), Notifications (system alert preferences), System
Settings (spread across multiple tabs for broader application
configuration), and Company Settings (company name/branding, shown here
as "Vishesh Textiles", logo, and contact details).

### 3.16 User Profile

A self-service "My Profile" page allowing the logged-in user (Super
Admin, superadmin@superadmin.com) to view and edit their own personal
details and credentials.

## 4. Page-by-Page Documentation

### 4.1 Dashboard (/dashboard)

**Purpose:** Landing page summarizing overall business health.

**Components:**

| Component         | Type           | Description                                                                                                                                          | Actions                |
|-------------------|----------------|------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------|
| KPI Cards         | Card group     | Total/Running/Pending Order counts; Weekly/Monthly/Yearly value; Cash & Bank balance; Supplier Due; Monthly Expense; Debit/Credit transaction totals | None (display only)    |
| Income vs Expense | Line/Bar chart | Year-filterable financial trend chart                                                                                                                | Year dropdown filter   |
| New Order Feed    | List           | Recent orders with status badges (Approved/Pending)                                                                                                  | Status dropdown filter |
| Sales Ratio       | Chart          | Visual breakdown of sales composition                                                                                                                | None                   |
| Sales By Country  | Donut chart    | Distribution across UAE, US, Colombia, Others                                                                                                        | None                   |

### 4.2 Order List (/orders)

| Component         | Type          | Description                                                                                                                                                 | Actions                         |
|-------------------|---------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|
| Orders Table      | Data table    | Columns: SL, Order No, Image, Party, Merchandiser, GSM, Shipment Mode, Payment Mode, Year, Season, Total Qty, Total Unit Price, Total Value, Status, Action | Sort, per-page size, pagination |
| Search/Filter bar | Form controls | Text search plus From/To date range                                                                                                                         | Filter table                    |
| Export toolbar    | Icon buttons  | PDF, Excel, CSV, Print                                                                                                                                      | Export current table view       |
| Row action menu   | Dropdown      | Print, Edit, Delete, Order Summary, Order Details                                                                                                           | Opens respective view/action    |

**Order Details view** (opened from row action, e.g. /order-details/1):
printable invoice-style layout with company header,
style/color/item/shipment date/qty/unit price/total price table, and a
totals row.

### 4.3 Add New Order Form

| Field                          | Type            | Required         | Validation/Notes                                                                                                          |
|--------------------------------|-----------------|------------------|---------------------------------------------------------------------------------------------------------------------------|
| Party Name                     | Dropdown (+Add) | Yes              | Selectable from Party List or add new inline                                                                              |
| Merchandiser Name              | Dropdown (+Add) | Yes              | Selectable from Merchandiser users                                                                                        |
| Item Image                     | File upload     | No               | Image file                                                                                                                |
| Order No                       | Text            | Auto             | Auto-generated (e.g., 0000012)                                                                                            |
| Order Title / Description      | Text            | No               | Free text                                                                                                                 |
| Fabrication / GSM / Yarn Count | Text/Number     | No               | Fabric specification fields                                                                                               |
| Shipment Mode / Payment Mode   | Dropdown        | Yes              | Preset options                                                                                                            |
| Bank Account                   | Dropdown (+Add) | No               | Linked to Bank Accounts module                                                                                            |
| Year / Season / Pantone        | Text/Dropdown   | No               |                                                                                                                           |
| Consignee & Notify             | Text            | No               |                                                                                                                           |
| Contact Date / Expiry Date     | Date picker     | No               |                                                                                                                           |
| Negotiation Period             | Text            | No               |                                                                                                                           |
| Port of Loading / Discharge    | Text            | No               |                                                                                                                           |
| Payment Terms / Remarks        | Textarea        | No               |                                                                                                                           |
| Line items table               | Dynamic rows    | Yes (at least 1) | Style, Color, Item, Shipment Date, Qty, Unit Price, auto-computed Total Price; "+" adds rows; grand total auto-calculated |

### 4.4 Booking List & Add New Booking

Booking List columns: SL, Order No, Booking Date, Party Name, Type,
Composition, Order Image, Process Loss, Others Fabric, Rib, Collar,
Prepared By, Status, Action.

Add New Booking key fields: Order No (select), Prepared By, Booking
Date, Composition, Process Loss, Others Fabric, Rib, Collar, Order Item
Image, plus a wide specification table with columns spanning Style,
Color, Item, Shipment Date, Garments Qty, Unit Price, Total Value,
Description, Picture, Pantone, Body Fabrication, Yarn Count, DZN
conversions, and gray fabric/rib consumption in KG.

### 4.5 Other Order-Module Pages

Budget List, Costing List, Sample List, Production List, and Shipments
List each follow the list+add tab pattern described in the Feature
Catalog (Section 3), with column sets as documented there.

### 4.6 Manage Inventory Pages

Units (/units): SL, Name, Status toggle, Action. Accessory List
(/accessories): SL, Name, Unit, Price, Description, Status toggle,
Action (7 sample records observed). Accessories Orders
(/accessory-orders): SL, Invoice No., Party Name, Accessories, Unit,
Qty, Unit Price, Total Amount, Action (empty state in demo data).

### 4.7 User Management Pages

Each role list (Admin, Buyer, Merchandiser, Commercial, Accountant,
Production) shows SL, Name, Phone, User Name, Action. The Add form
(e.g., /users/create?role=admin) captures Full Name, Phone, Role (preset
per section), User Name/email, Password, Confirm Password, and an avatar
upload.

### 4.8 Accounts & Bank Pages

**Bank Accounts (/banks):** Summary cards for Balance/Deposit/Withdraw;
table of Account Holder Name, Bank Name, Account Number, Branch Name,
Routing/Swift Number, Amount, Action; supports Add New Bank and
Deposit/Withdraw actions.

**Cash in Hand (/cashes):** Summary cards for Total Balance, Total
Credit, Total Debit; transaction table with Date, Bank/Account Name
Note, Type (Increase/Reduce), Amount, Action; supports an Adjust Cash
action.

**Cheques (/cheques):** Passed/Unused tabs; table of Issue Date,
Income-Expense, Party Name, Bank Name, Amount, Type, Status, Action.

**Income / Expenses:** Category master lists with Name, Description,
Status toggle, Action, plus an Add Category tab.

**Credit Voucher / Debit Voucher:** Transaction tables with Bill No.,
Voucher No., Transaction By, Party, Date, Payment Type/Method badges,
and colored Amount column (green for credit, red for debit).

**Monthly Transaction (/transactions):** Year/Month filterable summary
with Date, Total Transaction, Total Amount, Type, Remarks, Action.

**Party Ledger (/party-ledger):** Buyer/Supplier tabs; table of Party
Name, Party Type badge, Total Bill, Pay Amount, Advance Amount, Due
Amount, Balance, Action.

**Daily Cashbook (/reports/cashbooks):** Date-ranged register with
Receivable/Payment Purpose, Voucher No., Type badge, Credit/Debit
Amount, Bill info, Due Bill, Remarks, plus a running summary panel
(Previous Balance, Credit, Sub Total, Total Expenses, Cash In Hand).

### 4.9 Party List Pages

Buyers/Suppliers (/parties): summary cards (Total Bill, Advance, Pay,
Due) and a table of Party Name, Phone, Country, Total Bill, Advance
Amount, Pay Amount, Due Amount, Balance, Remarks, Action. Add New Party
form captures Party Name, Email, Phone, Password, Address, Opening
Balance Type, Opening Balance, Country, Remarks, and an image upload.

### 4.10 HRM Pages

Designations (/designations): SL, Designation, Description, Action.
Employees (/employees): SL, Join Date, Full Name, Phone, Designation,
Salary, Status badge, Action, with an Add Employee form capturing
personal details, gender, employment type, birth/joining dates,
designation, salary, and NID/passport uploads (front and optional back).
Salaries List (/salaries): SL, Employee, Month, Year, Salary Amount,
Paid Amount, Due Salary, Payment Method, Pay Date, Action, with a Pay
Salary action.

### 4.11 Party Due List (/dues)

Four tabs (Buyer, Supplier, Credit Voucher, Debit Voucher) each showing
summary cards and a table of Party Name, Phone, Total Bill, Advance
Amount, Pay Amount, Due Amount, Remarks, Action.

### 4.12 Loss Profit (/loss-profit)

Year-filterable summary cards: Total Sale, Total Expense, Total Profit,
Total Loss.

### 4.13 Settings Pages

Currency Settings, Notifications, System Settings (multi-tab), and
Company Settings (branded "Vishesh Textiles") allow administrators to
configure application-wide defaults and branding.

### 4.14 My Profile

Self-service page for the logged-in Super Admin to view/edit personal
account details.

## 5. Forms Reference

### 5.1 Add New Order

**Location:** Order List \> Add New Order tab. See Section 4.3 for full
field table. **Buttons:** Cancel, Save. **Related workflow:** Order
Creation Workflow (Section 7.1).

### 5.2 Add New Booking

**Location:** Booking List \> Add New Booking tab. Fields as documented
in Section 4.4. **Buttons:** Reset, Save. **Related workflow:** Booking
Creation Workflow (Section 7.2).

### 5.3 Add New Buyer / Supplier

| Field                | Type     | Required | Notes                        |
|----------------------|----------|----------|------------------------------|
| Party Name           | Text     | Yes      |                              |
| Party Email          | Email    | Yes      | Used as login/contact        |
| Phone                | Text     | Yes      |                              |
| Password             | Password | Yes      | Sets party portal credential |
| Address              | Textarea | No       |                              |
| Opening Balance Type | Dropdown | No       | Debit/Credit                 |
| Opening Balance      | Number   | No       |                              |
| Country              | Dropdown | No       |                              |
| Remarks              | Textarea | No       |                              |
| Upload Image         | File     | No       |                              |

**Buttons:** Reset, Save.

### 5.4 Add User (Admin/Buyer/Merchandiser/Commercial/Accountant/Production)

| Field                       | Type     | Required | Notes                                |
|-----------------------------|----------|----------|--------------------------------------|
| Full Name                   | Text     | Yes      |                                      |
| Phone                       | Text     | No       |                                      |
| Role                        | Dropdown | Yes      | Preset based on section entered from |
| User Name                   | Email    | Yes      | Used as login                        |
| Password / Confirm Password | Password | Yes      | Must match                           |
| Upload Image                | File     | No       | Avatar                               |

### 5.5 Add Employee

Fields: Name, Phone Number, Email, Address, Gender (dropdown),
Employment Type (dropdown), Birth Date, Joining Date, Designation
(dropdown), Salary, NID/Passport upload, NID/Passport back (optional
upload). Buttons: Reset, Save.

### 5.6 Category / Master-list Forms (Units, Accessories, Designations, Income/Expense Categories)

These share a lightweight pattern: Name, optional Description/Unit/Price
fields, and a Status toggle, with Reset/Save buttons on a secondary
"Create/Add" tab alongside the corresponding list.

### 5.7 Vouchers (Credit/Debit)

Create Credit / Create Debit forms capture Party, Date, Payment Type,
Payment Method, Amount, and Bill/Voucher references, feeding the
corresponding voucher list and the Daily Cashbook.

## 6. Data Models & Entities

### 6.1 Order

| Field                        | Data Type         | Description                                | Constraints                  |
|------------------------------|-------------------|--------------------------------------------|------------------------------|
| Order No                     | String            | Unique auto-generated identifier           | Auto-increment, e.g. 0000012 |
| Party                        | Reference (Party) | Buyer placing the order                    | Required                     |
| Merchandiser                 | Reference (User)  | Internal owner of the order                | Required                     |
| GSM, Fabrication, Yarn Count | String/Number     | Fabric specification                       | Optional                     |
| Shipment Mode, Payment Mode  | Enum              | Logistics/payment classification           | Required                     |
| Line Items                   | Array of objects  | Style/Color/Item/Date/Qty/Unit Price/Total | At least 1                   |
| Status                       | Enum              | Approved / Pending / etc.                  | System-managed               |

**Example (from live data):** Order List row 1 — Party: sample buyer
record, Season/Year fields populated, Status badge "Approved".

### 6.2 Booking

References an Order (one-to-one/one-to-many per order), plus
Composition, Process Loss, Rib/Collar details, and a nested line-item
table mirroring order styles with additional fabric-consumption fields
(DZN conversions, gray fabric KG).

### 6.3 Party (Buyer/Supplier)

| Field                                      | Data Type | Description                | Constraints     |
|--------------------------------------------|-----------|----------------------------|-----------------|
| Party Name                                 | String    | Company/individual name    | Required        |
| Type                                       | Enum      | Buyer or Supplier          | Required        |
| Total Bill / Advance / Pay / Due / Balance | Currency  | Running financial position | System-computed |
| Country                                    | String    |                            | Optional        |

**Example (live data):** Buyer "LC Waikiki" with recorded Total Bill of
\$772,000 across the Buyers directory; Supplier "COATS" with Total Bill
\$55,438.

### 6.4 User

Fields: Full Name, Phone, Role
(Admin/Buyer/Merchandiser/Commercial/Accountant/Production), User Name
(email, used for login), Password (hashed), Avatar. Relationship: a User
with role Merchandiser can be referenced by many Orders; a User with
role Admin (Super Admin) has unrestricted access.

### 6.5 Employee

Fields: Name, Phone, Email, Address, Gender, Employment Type, Birth
Date, Joining Date, Designation (reference), Salary, ID documents.
Relationship: one Employee has many Salary payment records
(one-to-many), keyed by Month/Year.

### 6.6 Financial Transaction (Credit/Debit Voucher, Cash, Bank)

Common shape: Date, Party (optional reference), Amount, Payment Type,
Payment Method, Bill/Voucher Number, and a Type flag
(credit=green/increase vs debit=red/reduce) that feeds both the specific
ledger (Bank, Cash, Voucher list) and the aggregate Daily Cashbook and
Monthly Transaction views.

### 6.7 Accessory / Unit

Unit: Name (pcs/dzn/kg/meter), Status. Accessory: Name, Unit
(reference), Price, Description, Status. Accessory Order references one
or more Accessories with quantity and unit price, computing a total
amount, and is linked to a Supplier party.

### 6.8 Relationships Summary

- Party (Buyer) 1—N Orders
- Order 1—1 Booking (per order specification), 1—N Line Items
- Order 1—N Budget/Costing/Sample/Shipment records
- User (Merchandiser) 1—N Orders
- Employee 1—N Salary Payments
- Party (Supplier) 1—N Accessory Orders
- Bank Account / Cash 1—N Transactions (deposits, withdrawals,
  adjustments)
- Party 1—N Credit/Debit Vouchers

## 7. User Workflows

### 7.1 Create New Order Workflow

1.  **Start:** User navigates to Order Management \> Order List.
2.  User selects the "Add New Order" tab.
3.  User selects or adds a Party and Merchandiser.
4.  User completes order specification fields (fabrication, GSM,
    shipment/payment mode, dates, ports, terms).
5.  User adds one or more line items (style, color, item, shipment date,
    quantity, unit price); the system auto-computes each line's total
    and a grand total.
6.  **Decision point:** user reviews computed totals before submitting.
7.  User clicks Save.
8.  **End state:** New order appears in Order List with an
    auto-generated Order No and default status (e.g., Pending).

### 7.2 Create New Booking Workflow

1.  **Start:** User navigates to Order Management \> Booking List.
2.  User selects "Add New Booking" and chooses the associated Order No.
3.  User enters preparer, booking date, and fabric composition details
    (process loss, other fabrics, rib, collar).
4.  User completes the detailed per-style specification table (garment
    quantity, unit price, fabric consumption in DZN/KG).
5.  **Decision point:** system computes total value per line from
    quantity × unit price.
6.  User clicks Save.
7.  **End state:** Booking appears in Booking List linked to its parent
    order.

### 7.3 Search / Filter Workflow

1.  User opens any list page (e.g., Order List).
2.  User enters a search term and/or selects a From/To date range.
3.  Table results update to reflect the filter criteria.
4.  User may further export the filtered results (PDF/Excel/CSV) or
    print.

### 7.4 Party Due Settlement Workflow

1.  User navigates to Party Due List and selects the Buyer or Supplier
    tab.
2.  User reviews outstanding Due Amount per party.
3.  User creates a Credit Voucher (for buyer receipts) or Debit Voucher
    (for supplier payments) referencing the party.
4.  The transaction reduces the party's due balance and is reflected in
    the Daily Cashbook and Monthly Transaction views.

### 7.5 Salary Payment Workflow

1.  User navigates to HRM Management \> Salaries List.
2.  User selects an employee/month and clicks "Pay Salary".
3.  User enters the paid amount and payment method.
4.  System records the payment, updating Due Salary for that period.

## 8. Permissions & Access

The system observed during this audit was accessed as **Super Admin**
(superadmin@superadmin.com), which has unrestricted access to every
module listed in the site map. The User Management module's role
segmentation (Admin, Buyer, Merchandiser, Commercial, Accountant,
Production) strongly implies role-based access control exists, where
each role likely has a scoped view of the sidebar (e.g., an Accountant
would primarily see Accounts & Bank modules, a Merchandiser would
primarily see Order Management). However, since only the Super Admin
session was available, role-specific UI restrictions could not be
directly observed or confirmed in this pass.

**Login/Logout Flow:** The application requires authentication before
any dashboard route is accessible; unauthenticated requests redirect to
a login screen requiring a username/email and password. A logout action
is available from the top-bar profile menu, returning the user to the
login screen.

## 9. Technical Specifications

**Frontend:** The application renders as a modern single-page-style
dashboard with a persistent sidebar, tabbed sub-navigation, data tables
with client-side pagination/sorting, and chart components (line/bar and
donut charts) on the Dashboard — consistent with a JavaScript
framework-driven admin template (commonly Laravel + Blade/Livewire or a
React/Vue admin theme; exact framework was not confirmed from visible
markup during this audit).

**Database structure (inferred):** Relational structure is implied by
the consistent master-detail patterns observed: Orders reference Parties
and Users; Bookings, Budgets, Costings, Samples, and Shipments each
reference an Order; Salaries reference Employees; Vouchers and Cash/Bank
transactions reference Parties and feed aggregate ledger views.

**Integrations:** No third-party payment gateway or external API calls
were directly observed during this audit. File upload capability is
present across multiple forms (item images, avatars, ID documents),
suggesting server-side or cloud file storage. A Notifications settings
page exists, suggesting an internal or email-based notification system,
though outbound integration (e.g., SMTP/SMS provider) was not directly
verifiable from the UI.

**Export capabilities:** Most list pages expose PDF, Excel, CSV, and
Print export options via a consistent toolbar.

**Search/Filtering:** Implemented consistently as client-facing text
search plus date-range pickers on list pages, with immediate table
re-rendering.

## 10. Appendices

### 10.1 Glossary

- **GSM** — Grams per Square Meter, a fabric weight specification.
- **DZN** — Dozen, a garment quantity unit used in fabric consumption
  calculations.
- **CBM** — Cubic Meter, a shipment volume measurement.
- **Party** — A Buyer or Supplier business entity.
- **Voucher** — A recorded financial transaction (Credit = money in,
  Debit = money out).
- **Cashbook** — A running daily register of all cash receivables and
  payments.

### 10.2 URL Map (Representative)

| Page                 | URL                                     |
|----------------------|-----------------------------------------|
| Dashboard            | /dashboard                              |
| Order List           | /orders                                 |
| Order Details        | /order-details/{id}                     |
| Booking List         | /bookings                               |
| Budgets              | /budgets                                |
| Costings             | /costings                               |
| Samples              | /samples                                |
| Production           | /productions                            |
| Shipments            | /shipments                              |
| Units                | /units                                  |
| Accessories          | /accessories                            |
| Accessory Orders     | /accessory-orders                       |
| Users by role        | /users?role={role}                      |
| Bank Accounts        | /banks                                  |
| Cash in Hand         | /cashes                                 |
| Cheques              | /cheques                                |
| Income               | /income                                 |
| Expenses             | /expense                                |
| Credit Vouchers      | /credit-vouchers                        |
| Debit Vouchers       | /debit-vouchers                         |
| Monthly Transactions | /transactions                           |
| Party Ledger         | /party-ledger                           |
| Daily Cashbook       | /reports/cashbooks                      |
| Parties              | /parties?parties-type={buyer\|supplier} |
| Designations         | /designations                           |
| Employees            | /employees                              |
| Salaries             | /salaries                               |
| Party Due List       | /dues                                   |
| Loss Profit          | /loss-profit                            |

### 10.3 Notes, Observations & Gaps

- The Add New Booking tab intermittently caused the browser tab to
  become unresponsive during this audit; the form was successfully
  re-captured using a fresh tab.
- The Production role's dedicated user list was not separately captured
  in this pass and should be revisited for completeness.
- Accessories Orders showed an empty state in the demo data, so a live
  example of a submitted accessory order was not available.
- Cheques list showed an empty state (no passed/unused cheques recorded
  in demo data).
- This document was compiled from live, non-destructive observation only
  — no records were created, edited, or deleted, and no forms were
  submitted, in accordance with the agreed exploration plan.
- Per tooling constraints, this document is delivered as an HTML file
  with a .doc extension (opens natively in Microsoft Word) rather than a
  native binary .docx, and uses written component/screenshot
  descriptions rather than embedded images.
