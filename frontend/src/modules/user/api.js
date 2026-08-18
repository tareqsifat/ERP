import api from '@/shared/api/axios'

// Axios calls scoped to the User module (sdd.md §2).
//
// `list()` is implemented ahead of the rest of this module's CRUD UI
// (Phase 6+) because Order/Booking's "assign a Merchandiser/Preparer"
// dropdowns (Phase 3) need it now — see order/views/OrderFormView.vue.
export default {
  list(params = {}) {
    return api.get('/users', { params })
  },
  updateMe(payload) {
    return api.patch('/users/me', payload)
  },
}
