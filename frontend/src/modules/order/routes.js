// Order module routes (sdd.md §2) — PRD v1 §3.1.
export default [
  {
    path: '/orders',
    name: 'orders.index',
    component: () => import('./views/OrderListView.vue'),
    meta: { requiresAuth: true, permission: 'order.view' },
  },
  {
    path: '/orders/new',
    name: 'orders.create',
    component: () => import('./views/OrderFormView.vue'),
    meta: { requiresAuth: true, permission: 'order.create' },
  },
  {
    path: '/orders/:id/edit',
    name: 'orders.edit',
    component: () => import('./views/OrderFormView.vue'),
    meta: { requiresAuth: true, permission: 'order.edit' },
  },
]
