// Sidebar nav, mirroring PRD_GarmentsERP_v2.md §2's site map. Each entry's
// `routeName` only renders once that module actually registers the route
// (router.hasRoute) — so the sidebar grows automatically, phase by phase,
// instead of linking to pages that don't exist yet.
export const navSections = [
  {
    label: 'Order Management',
    items: [
      { label: 'Orders', routeName: 'orders.index' },
      { label: 'Bookings', routeName: 'bookings.index' },
      { label: 'Budgets', routeName: 'budgets.index' },
      { label: 'Costings', routeName: 'costings.index' },
      { label: 'Samples', routeName: 'samples.index' },
      { label: 'Production List', routeName: 'productions.index' },
      { label: 'Shipments', routeName: 'shipments.index' },
    ],
  },
  {
    label: 'Manage Inventory',
    items: [
      { label: 'Raw Materials', routeName: 'raw-materials.index' },
      { label: 'Raw Material Stock', routeName: 'raw-materials.stock' },
    ],
  },
  {
    label: 'Production',
    items: [
      { label: 'Cutting', routeName: 'production.cutting' },
      { label: 'Sewing Line Output', routeName: 'production.sewing' },
      { label: 'Piece Traceability Lookup', routeName: 'production.trace' },
    ],
  },
  {
    label: 'Finished Goods',
    items: [
      { label: 'Finished Goods Inventory', routeName: 'finished-goods.index' },
      { label: 'Stock Transfer', routeName: 'stock-transfers.index' },
    ],
  },
  {
    label: 'Locations',
    items: [{ label: 'Locations List', routeName: 'locations.index' }],
  },
  {
    label: 'Subcontracting',
    items: [
      { label: 'Outward Subcontract', routeName: 'subcontract.outward' },
      { label: 'Inward Subcontract', routeName: 'subcontract.inward' },
      { label: 'Subcontractor Ledger', routeName: 'subcontract.ledger' },
    ],
  },
  {
    label: 'Manage Machines',
    items: [{ label: 'Machine Register', routeName: 'machines.index' }],
  },
  {
    label: 'User Management',
    items: [{ label: 'Users', routeName: 'users.index' }],
  },
  {
    label: 'Accounts & Bank',
    items: [
      { label: 'Bank Accounts', routeName: 'banks.index' },
      { label: 'Cash in Hand', routeName: 'cashes.index' },
      { label: 'Cheques', routeName: 'cheques.index' },
      { label: 'Income', routeName: 'income.index' },
      { label: 'Expenses', routeName: 'expenses.index' },
      { label: 'Credit Voucher', routeName: 'credit-vouchers.index' },
      { label: 'Debit Voucher', routeName: 'debit-vouchers.index' },
      { label: 'Monthly Transaction', routeName: 'transactions.index' },
      { label: 'Party Ledger', routeName: 'party-ledger.index' },
      { label: 'Daily Cashbook', routeName: 'cashbook.index' },
    ],
  },
  {
    label: 'Party List',
    items: [
      { label: 'Buyers', routeName: 'parties.buyers' },
      { label: 'Suppliers', routeName: 'parties.suppliers' },
      { label: 'Subcontractors', routeName: 'parties.subcontractors' },
    ],
  },
  {
    label: 'HRM Management',
    items: [
      { label: 'Designations', routeName: 'designations.index' },
      { label: 'Employees', routeName: 'employees.index' },
      { label: 'Salaries', routeName: 'salaries.index' },
      { label: 'Attendance', routeName: 'attendance.index' },
    ],
  },
  {
    label: 'Party Due List',
    items: [{ label: 'Dues', routeName: 'dues.index' }],
  },
  {
    label: 'Reports',
    items: [
      { label: 'Loss & Profit', routeName: 'loss-profit.index' },
      { label: 'Report Suite', routeName: 'reports.index' },
    ],
  },
  {
    label: 'Settings',
    items: [{ label: 'Settings', routeName: 'settings.index' }],
  },
]
