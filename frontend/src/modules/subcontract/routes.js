// Subcontract module routes (sdd.md §2) — PRD v2 §3.23/§3.24. Names
// match shared/layouts/navConfig.js's "Subcontracting" group so the
// sidebar auto-renders them.
export default [
  {
    path: '/subcontract/outward',
    name: 'subcontract.outward',
    component: () => import('./views/SubcontractOutwardView.vue'),
    meta: { requiresAuth: true, permission: 'subcontract.outward.manage' },
  },
  {
    path: '/subcontract/inward',
    name: 'subcontract.inward',
    component: () => import('./views/SubcontractInwardView.vue'),
    meta: { requiresAuth: true, permission: 'subcontract.inward.manage' },
  },
  {
    path: '/subcontract/ledger',
    name: 'subcontract.ledger',
    component: () => import('./views/SubcontractLedgerView.vue'),
    meta: { requiresAuth: true, permission: 'subcontract.ledger.view' },
  },
]
