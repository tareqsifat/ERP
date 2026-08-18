import api from '@/shared/api/axios'

// Axios calls scoped to the Shipment module (sdd.md §2) — PRD v1 §3.6.
export default {
  list(params = {}) {
    return api.get('/shipments', { params })
  },
  get(id) {
    return api.get(`/shipments/${id}`)
  },
  create(payload) {
    return api.post('/shipments', payload)
  },
  update(id, payload) {
    return api.put(`/shipments/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/shipments/${id}`)
  },
}
