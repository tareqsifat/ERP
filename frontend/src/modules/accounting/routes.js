// Accounting module routes (sdd.md §2) — PRD v1 §3.9/§3.12/§3.13. Names
// match shared/layouts/navConfig.js's "Accounts & Bank"/"Party Due
// List"/"Reports" groups so the sidebar auto-renders them.
export default [
  {
    path: '/accounting/banks',
    name: 'banks.index',
    component: () => import('./views/BankAccountsView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.bank.manage' },
  },
  {
    path: '/accounting/cash',
    name: 'cashes.index',
    component: () => import('./views/CashView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.cash.manage' },
  },
  {
    path: '/accounting/cheques',
    name: 'cheques.index',
    component: () => import('./views/ChequesView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.cheque.manage' },
  },
  {
    path: '/accounting/income',
    name: 'income.index',
    component: () => import('./views/CategoryView.vue'),
    props: { kind: 'income' },
    meta: { requiresAuth: true, permission: 'accounting.voucher.view' },
  },
  {
    path: '/accounting/expenses',
    name: 'expenses.index',
    component: () => import('./views/CategoryView.vue'),
    props: { kind: 'expense' },
    meta: { requiresAuth: true, permission: 'accounting.voucher.view' },
  },
  {
    path: '/accounting/credit-vouchers',
    name: 'credit-vouchers.index',
    component: () => import('./views/VoucherView.vue'),
    props: { type: 'credit' },
    meta: { requiresAuth: true, permission: 'accounting.voucher.view' },
  },
  {
    path: '/accounting/debit-vouchers',
    name: 'debit-vouchers.index',
    component: () => import('./views/VoucherView.vue'),
    props: { type: 'debit' },
    meta: { requiresAuth: true, permission: 'accounting.voucher.view' },
  },
  {
    path: '/accounting/transactions',
    name: 'transactions.index',
    component: () => import('./views/TransactionsView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.transaction.view' },
  },
  {
    path: '/accounting/party-ledger',
    name: 'party-ledger.index',
    component: () => import('./views/PartyLedgerView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.ledger.view' },
  },
  {
    path: '/accounting/cashbook',
    name: 'cashbook.index',
    component: () => import('./views/CashbookView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.cashbook.view' },
  },
  {
    path: '/accounting/dues',
    name: 'dues.index',
    component: () => import('./views/DuesView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.ledger.view' },
  },
  {
    path: '/accounting/loss-profit',
    name: 'loss-profit.index',
    component: () => import('./views/LossProfitView.vue'),
    meta: { requiresAuth: true, permission: 'accounting.loss-profit.view' },
  },
]
