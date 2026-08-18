import api from '@/shared/api/axios'

// Axios calls scoped to the Raw Material module (sdd.md §2) — PRD v2 §3.19.
export default {
  list(params = {}) {
    return api.get('/raw-materials', { params })
  },
  get(id) {
    return api.get(`/raw-materials/${id}`)
  },
  create(payload) {
    return api.post('/raw-materials', payload)
  },
  update(id, payload) {
    return api.put(`/raw-materials/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/raw-materials/${id}`)
  },

  movements: {
    list(params = {}) {
      return api.get('/raw-material-movements', { params })
    },
    adjust(payload) {
      return api.post('/raw-material-movements', payload)
    },
  },

  purchaseOrders: {
    list(params = {}) {
      return api.get('/raw-material-purchase-orders', { params })
    },
    get(id) {
      return api.get(`/raw-material-purchase-orders/${id}`)
    },
    create(payload) {
      return api.post('/raw-material-purchase-orders', payload)
    },
    receive(id, payload) {
      return api.post(`/raw-material-purchase-orders/${id}/receive`, payload)
    },
  },
}
