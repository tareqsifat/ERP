import api from '@/shared/api/axios'

// Axios calls scoped to the Party module (sdd.md §2) — PRD v1 §3.10 / §4.9.
export default {
  list(params = {}) {
    return api.get('/parties', { params })
  },
  get(id) {
    return api.get(`/parties/${id}`)
  },
  create(payload) {
    return api.post('/parties', payload)
  },
  update(id, payload) {
    return api.put(`/parties/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/parties/${id}`)
  },
}
