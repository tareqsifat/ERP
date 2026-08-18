// Booking module routes (sdd.md §2) — PRD v1 §3.2.
export default [
  {
    path: '/bookings',
    name: 'bookings.index',
    component: () => import('./views/BookingListView.vue'),
    meta: { requiresAuth: true, permission: 'booking.view' },
  },
  {
    path: '/bookings/new',
    name: 'bookings.create',
    component: () => import('./views/BookingFormView.vue'),
    meta: { requiresAuth: true, permission: 'booking.create' },
  },
  {
    path: '/bookings/:id/edit',
    name: 'bookings.edit',
    component: () => import('./views/BookingFormView.vue'),
    meta: { requiresAuth: true, permission: 'booking.edit' },
  },
]
