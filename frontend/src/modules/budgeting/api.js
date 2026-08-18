import api from '@/shared/api/axios'

// Axios calls scoped to the Budgeting module (sdd.md §2) — PRD v1 §3.3.
export default {
  list(params = {}) {
    return api.get('/budgets', { params })
  },
  get(id) {
    return api.get(`/budgets/${id}`)
  },
  create(payload) {
    return api.post('/budgets', payload)
  },
  update(id, payload) {
    return api.put(`/budgets/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/budgets/${id}`)
  },
}
