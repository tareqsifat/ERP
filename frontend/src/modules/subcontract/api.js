import api from '@/shared/api/axios'

// Axios calls scoped to the Subcontract module (sdd.md §2) — PRD v2
// §3.23/§3.24. Outward and Inward share one `subcontract-orders`
// resource (`direction` is the field that differs), matching the
// backend's single SubcontractOrderController.
export default {
  list(params = {}) {
    return api.get('/subcontract-orders', { params })
  },
  get(id) {
    return api.get(`/subcontract-orders/${id}`)
  },
  create(payload) {
    return api.post('/subcontract-orders', payload)
  },
  issuePieces(id, payload) {
    return api.post(`/subcontract-orders/${id}/issue-pieces`, payload)
  },
  issueRawMaterial(id, payload) {
    return api.post(`/subcontract-orders/${id}/issue-raw-material`, payload)
  },
  returnPieces(id, payload) {
    return api.post(`/subcontract-orders/${id}/return-pieces`, payload)
  },
  dispatchBack(id) {
    return api.post(`/subcontract-orders/${id}/dispatch-back`)
  },
  ledger(id) {
    return api.get(`/subcontract-orders/${id}/ledger`)
  },
  payment(id, payload) {
    return api.post(`/subcontract-orders/${id}/payment`, payload)
  },
}
