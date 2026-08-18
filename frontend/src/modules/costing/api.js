import api from '@/shared/api/axios'

// Axios calls scoped to the Costing module (sdd.md §2) — PRD v1 §3.3.
export default {
  list(params = {}) {
    return api.get('/costings', { params })
  },
  get(id) {
    return api.get(`/costings/${id}`)
  },
  create(payload) {
    return api.post('/costings', payload)
  },
  update(id, payload) {
    return api.put(`/costings/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/costings/${id}`)
  },
}
