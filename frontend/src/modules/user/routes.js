// User module routes (sdd.md §2) — PRD v1 §3.16/§4.14 My Profile.
// `profile.index` isn't in shared/layouts/navConfig.js's sidebar (My
// Profile is reached from the top-bar user menu per PRD, not the
// sidebar — see AppLayout.vue) but still needs a route name for the
// router-link there to resolve.
export default [
  {
    path: '/profile',
    name: 'profile.index',
    component: () => import('./views/ProfileView.vue'),
    meta: { requiresAuth: true },
  },
]
