import api from '@/shared/api/axios'

// Axios calls scoped to the Location module (sdd.md §2) — PRD v2 §3.21.
// Stock Transfer lives here too (not in modules/finished-goods) because
// the backend equivalent, Modules/Location/App/Http/Controllers/
// StockTransferController, lives in Modules/Location per sdd.md §2's
// repo layout — the 1:1 module mapping wins over how the sidebar groups
// the nav item (see shared/layouts/navConfig.js).
export default {
  list(params = {}) {
    return api.get('/locations', { params })
  },
  get(id) {
    return api.get(`/locations/${id}`)
  },
  create(payload) {
    return api.post('/locations', payload)
  },
  update(id, payload) {
    return api.put(`/locations/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/locations/${id}`)
  },

  stockTransfers: {
    list(params = {}) {
      return api.get('/stock-transfers', { params })
    },
    get(id) {
      return api.get(`/stock-transfers/${id}`)
    },
    dispatch(payload) {
      return api.post('/stock-transfers', payload)
    },
    receive(id, payload) {
      return api.post(`/stock-transfers/${id}/receive`, payload)
    },
  },
}
