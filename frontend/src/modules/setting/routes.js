// Setting module routes (sdd.md §2) — PRD v1 §3.15/§4.13. Name matches
// shared/layouts/navConfig.js's "Settings" group ('settings.index').
// No `permission` meta — GET /settings is open to any authenticated
// user (Modules/Setting/routes/api.php); the view itself only shows
// the "Save" actions to `setting.manage` holders.
export default [
  {
    path: '/settings',
    name: 'settings.index',
    component: () => import('./views/SettingsView.vue'),
    meta: { requiresAuth: true },
  },
]
