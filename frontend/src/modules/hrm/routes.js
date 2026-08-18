// Hrm module routes (sdd.md §2) — PRD v1 §3.11. Names match
// shared/layouts/navConfig.js's "HRM Management" group. No
// `attendance.index` route — attendance-based payroll is Out of Scope
// for v1/v2 (PRD v2 §7); the sidebar's `router.hasRoute()` filter just
// quietly omits that nav item until it exists (see Modules/Hrm/README.md).
export default [
  {
    path: '/hrm/designations',
    name: 'designations.index',
    component: () => import('./views/DesignationsView.vue'),
    meta: { requiresAuth: true, permission: 'hrm.designation.manage' },
  },
  {
    path: '/hrm/employees',
    name: 'employees.index',
    component: () => import('./views/EmployeesView.vue'),
    meta: { requiresAuth: true, permission: 'hrm.employee.manage' },
  },
  {
    path: '/hrm/salaries',
    name: 'salaries.index',
    component: () => import('./views/SalariesView.vue'),
    meta: { requiresAuth: true, permission: 'hrm.salary.view' },
  },
]
