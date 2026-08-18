import api from '@/shared/api/axios'

// Axios calls scoped to the Sampling module (sdd.md §2) — PRD v1 §3.4.
export default {
  list(params = {}) {
    return api.get('/samples', { params })
  },
  get(id) {
    return api.get(`/samples/${id}`)
  },
  create(payload) {
    return api.post('/samples', payload)
  },
  update(id, payload) {
    return api.put(`/samples/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/samples/${id}`)
  },
}
