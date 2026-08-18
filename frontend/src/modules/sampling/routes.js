// Sampling module routes (sdd.md §2) — PRD v1 §3.4. Route names match
// navConfig.js's 'samples.index' entry (not 'sampling.index').
export default [
  {
    path: '/samples',
    name: 'samples.index',
    component: () => import('./views/SampleListView.vue'),
    meta: { requiresAuth: true, permission: 'sampling.view' },
  },
  {
    path: '/samples/new',
    name: 'samples.create',
    component: () => import('./views/SampleFormView.vue'),
    meta: { requiresAuth: true, permission: 'sampling.create' },
  },
  {
    path: '/samples/:id/edit',
    name: 'samples.edit',
    component: () => import('./views/SampleFormView.vue'),
    meta: { requiresAuth: true, permission: 'sampling.edit' },
  },
]
