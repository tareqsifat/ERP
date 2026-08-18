import api from '@/shared/api/axios'

// Axios calls scoped to the Production module (sdd.md §2) — PRD v2
// §3.17/§3.18/§3.22. Grouped by sub-resource (unlike most modules'
// flat default export) since this module covers five distinct
// resources sharing one backend module — mirrors
// Modules/Production/routes/api.php's own grouping.
export default {
  lines: {
    list(params = {}) {
      return api.get('/lines', { params })
    },
    create(payload) {
      return api.post('/lines', payload)
    },
    update(id, payload) {
      return api.put(`/lines/${id}`, payload)
    },
    remove(id) {
      return api.delete(`/lines/${id}`)
    },
  },

  machines: {
    list(params = {}) {
      return api.get('/machines', { params })
    },
    create(payload) {
      return api.post('/machines', payload)
    },
    update(id, payload) {
      return api.put(`/machines/${id}`, payload)
    },
    remove(id) {
      return api.delete(`/machines/${id}`)
    },
  },

  cutTickets: {
    list(params = {}) {
      return api.get('/cut-tickets', { params })
    },
    get(id) {
      return api.get(`/cut-tickets/${id}`)
    },
    create(payload) {
      return api.post('/cut-tickets', payload)
    },
    update(id, payload) {
      return api.put(`/cut-tickets/${id}`, payload)
    },
    remove(id) {
      return api.delete(`/cut-tickets/${id}`)
    },
    finalize(id) {
      return api.post(`/cut-tickets/${id}/finalize`)
    },
  },

  bundles: {
    list(params = {}) {
      return api.get('/bundles', { params })
    },
    get(id) {
      return api.get(`/bundles/${id}`)
    },
    assignToLine(id, payload) {
      return api.post(`/bundles/${id}/assign-to-line`, payload)
    },
    logOutput(id) {
      return api.post(`/bundles/${id}/log-output`)
    },
  },

  pieceSerials: {
    list(params = {}) {
      return api.get('/piece-serials', { params })
    },
    get(id) {
      return api.get(`/piece-serials/${id}`)
    },
    qc(id, payload) {
      return api.post(`/piece-serials/${id}/qc`, payload)
    },
  },
}
