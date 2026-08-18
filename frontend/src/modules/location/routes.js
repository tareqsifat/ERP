// Location module routes (sdd.md §2) — PRD v2 §3.21. Stock Transfer
// routes live here too, matching the backend's Modules/Location repo
// layout (see api.js's comment).
export default [
  {
    path: '/locations',
    name: 'locations.index',
    component: () => import('./views/LocationListView.vue'),
    meta: { requiresAuth: true, permission: 'location.view' },
  },
  {
    path: '/stock-transfers',
    name: 'stock-transfers.index',
    component: () => import('./views/StockTransferView.vue'),
    meta: { requiresAuth: true, permission: 'stock-transfer.view' },
  },
]
