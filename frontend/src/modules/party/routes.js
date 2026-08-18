// Party module routes (sdd.md §2) — PRD v1 §3.10 / §4.9. One shared list
// view reused across the Buyers/Suppliers/Subcontractors sidebar entries
// (navConfig.js), distinguished by the `type` prop.
export default [
  {
    path: '/parties/buyers',
    name: 'parties.buyers',
    component: () => import('./views/PartyListView.vue'),
    props: { type: 'buyer' },
    meta: { requiresAuth: true, permission: 'party.view' },
  },
  {
    path: '/parties/suppliers',
    name: 'parties.suppliers',
    component: () => import('./views/PartyListView.vue'),
    props: { type: 'supplier' },
    meta: { requiresAuth: true, permission: 'party.view' },
  },
  {
    path: '/parties/subcontractors',
    name: 'parties.subcontractors',
    component: () => import('./views/PartyListView.vue'),
    props: { type: 'subcontractor' },
    meta: { requiresAuth: true, permission: 'party.view' },
  },
  {
    path: '/parties/new',
    name: 'parties.create',
    component: () => import('./views/PartyFormView.vue'),
    meta: { requiresAuth: true, permission: 'party.create' },
  },
  {
    path: '/parties/:id/edit',
    name: 'parties.edit',
    component: () => import('./views/PartyFormView.vue'),
    meta: { requiresAuth: true, permission: 'party.edit' },
  },
]
