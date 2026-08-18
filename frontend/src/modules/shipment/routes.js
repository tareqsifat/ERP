// Shipment module routes (sdd.md §2) — PRD v1 §3.6.
export default [
  {
    path: '/shipments',
    name: 'shipments.index',
    component: () => import('./views/ShipmentListView.vue'),
    meta: { requiresAuth: true, permission: 'shipment.view' },
  },
  {
    path: '/shipments/new',
    name: 'shipments.create',
    component: () => import('./views/ShipmentFormView.vue'),
    meta: { requiresAuth: true, permission: 'shipment.create' },
  },
  {
    path: '/shipments/:id/edit',
    name: 'shipments.edit',
    component: () => import('./views/ShipmentFormView.vue'),
    meta: { requiresAuth: true, permission: 'shipment.edit' },
  },
]
