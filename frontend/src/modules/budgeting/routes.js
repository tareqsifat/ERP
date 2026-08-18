// Budgeting module routes (sdd.md §2) — PRD v1 §3.3. Route names match
// navConfig.js's 'budgets.index' entry (not 'budgeting.index').
export default [
  {
    path: '/budgets',
    name: 'budgets.index',
    component: () => import('./views/BudgetListView.vue'),
    meta: { requiresAuth: true, permission: 'budgeting.view' },
  },
  {
    path: '/budgets/new',
    name: 'budgets.create',
    component: () => import('./views/BudgetFormView.vue'),
    meta: { requiresAuth: true, permission: 'budgeting.create' },
  },
  {
    path: '/budgets/:id/edit',
    name: 'budgets.edit',
    component: () => import('./views/BudgetFormView.vue'),
    meta: { requiresAuth: true, permission: 'budgeting.edit' },
  },
]
