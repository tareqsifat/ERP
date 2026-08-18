// Costing module routes (sdd.md §2) — PRD v1 §3.3.
export default [
  {
    path: '/costings',
    name: 'costings.index',
    component: () => import('./views/CostingListView.vue'),
    meta: { requiresAuth: true, permission: 'costing.view' },
  },
  {
    path: '/costings/new',
    name: 'costings.create',
    component: () => import('./views/CostingFormView.vue'),
    meta: { requiresAuth: true, permission: 'costing.create' },
  },
  {
    path: '/costings/:id/edit',
    name: 'costings.edit',
    component: () => import('./views/CostingFormView.vue'),
    meta: { requiresAuth: true, permission: 'costing.edit' },
  },
]
