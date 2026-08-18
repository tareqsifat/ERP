// Raw Material module routes (sdd.md §2) — PRD v2 §3.19.
export default [
  {
    path: '/raw-materials',
    name: 'raw-materials.index',
    component: () => import('./views/RawMaterialListView.vue'),
    meta: { requiresAuth: true, permission: 'raw-material.view' },
  },
  {
    path: '/raw-materials/stock',
    name: 'raw-materials.stock',
    component: () => import('./views/RawMaterialStockView.vue'),
    meta: { requiresAuth: true, permission: 'raw-material.view' },
  },
]
