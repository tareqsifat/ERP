// Production module routes (sdd.md §2) — PRD v2 §3.17/§3.18/§3.22.
export default [
  {
    path: '/production/cutting',
    name: 'production.cutting',
    component: () => import('./views/CuttingView.vue'),
    meta: { requiresAuth: true, permission: 'production.cutting.view' },
  },
  {
    path: '/production/sewing',
    name: 'production.sewing',
    component: () => import('./views/SewingView.vue'),
    meta: { requiresAuth: true, permission: 'production.sewing.view' },
  },
  {
    path: '/production/trace/:serial?',
    name: 'production.trace',
    component: () => import('./views/TraceabilityView.vue'),
    meta: { requiresAuth: true, permission: 'production.trace.view' },
  },
  {
    path: '/machines',
    name: 'machines.index',
    component: () => import('./views/MachineRegisterView.vue'),
    meta: { requiresAuth: true, permission: 'machine.view' },
  },
]
