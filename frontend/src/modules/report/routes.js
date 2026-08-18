// Report module routes (sdd.md §2) — PRD v1 §3.14/§4.13. Name matches
// shared/layouts/navConfig.js's "Reports" group ('reports.index').
export default [
  {
    path: '/reports',
    name: 'reports.index',
    component: () => import('./views/ReportSuiteView.vue'),
    meta: { requiresAuth: true, permission: 'report.view' },
  },
]
