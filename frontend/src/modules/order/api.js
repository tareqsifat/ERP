import api from '@/shared/api/axios'

// Axios calls scoped to the Order module (sdd.md §2) — PRD v1 §3.1.
export default {
  list(params = {}) {
    return api.get('/orders', { params })
  },
  get(id) {
    return api.get(`/orders/${id}`)
  },
  create(payload) {
    return api.post('/orders', payload)
  },
  update(id, payload) {
    return api.put(`/orders/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/orders/${id}`)
  },
}
