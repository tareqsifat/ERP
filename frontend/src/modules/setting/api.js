import api from '@/shared/api/axios'

// Axios calls scoped to the Setting module (sdd.md §2) — PRD v1
// §3.15/§4.13.
export default {
  list() {
    return api.get('/settings')
  },
  update(group, values) {
    return api.put('/settings', { group, values })
  },
}
