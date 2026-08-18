import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/shared/stores/auth'

import authRoutes from '@/modules/auth/routes'
import userRoutes from '@/modules/user/routes'
import partyRoutes from '@/modules/party/routes'
import orderRoutes from '@/modules/order/routes'
import bookingRoutes from '@/modules/booking/routes'
import budgetingRoutes from '@/modules/budgeting/routes'
import costingRoutes from '@/modules/costing/routes'
import samplingRoutes from '@/modules/sampling/routes'
import shipmentRoutes from '@/modules/shipment/routes'
import locationRoutes from '@/modules/location/routes'
import rawMaterialRoutes from '@/modules/raw-material/routes'
import productionRoutes from '@/modules/production/routes'
import finishedGoodsRoutes from '@/modules/finished-goods/routes'
import subcontractRoutes from '@/modules/subcontract/routes'
import accountingRoutes from '@/modules/accounting/routes'
import hrmRoutes from '@/modules/hrm/routes'
import reportRoutes from '@/modules/report/routes'
import settingRoutes from '@/modules/setting/routes'

const moduleRoutes = [
  ...authRoutes,
  ...userRoutes,
  ...partyRoutes,
  ...orderRoutes,
  ...bookingRoutes,
  ...budgetingRoutes,
  ...costingRoutes,
  ...samplingRoutes,
  ...shipmentRoutes,
  ...locationRoutes,
  ...rawMaterialRoutes,
  ...productionRoutes,
  ...finishedGoodsRoutes,
  ...subcontractRoutes,
  ...accountingRoutes,
  ...hrmRoutes,
  ...reportRoutes,
  ...settingRoutes,
]

const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...moduleRoutes,
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/shared/components/DashboardPlaceholder.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/shared/components/NotFound.vue'),
    },
  ],
})

// sdd.md §4/§6: role/permission checks must ALSO exist on the backend API
// (failed_doc.md §2) — this guard is a UX convenience only, never the
// source of truth for authorization.
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.role && !auth.hasRole(to.meta.role)) {
    return { name: 'dashboard' }
  }

  if (to.meta.permission && !auth.can(to.meta.permission)) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
