import api from '@/shared/api/axios'

// Axios calls scoped to the Booking module (sdd.md §2) — PRD v1 §3.2.
export default {
  list(params = {}) {
    return api.get('/bookings', { params })
  },
  get(id) {
    return api.get(`/bookings/${id}`)
  },
  create(payload) {
    return api.post('/bookings', payload)
  },
  update(id, payload) {
    return api.put(`/bookings/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/bookings/${id}`)
  },
}
