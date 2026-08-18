// Finished Goods module routes (sdd.md §2) — PRD v2 §3.20.
export default [
  {
    path: '/finished-goods',
    name: 'finished-goods.index',
    component: () => import('./views/FinishedGoodsStockView.vue'),
    meta: { requiresAuth: true, permission: 'finished-goods.view' },
  },
]
