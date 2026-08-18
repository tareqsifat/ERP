import api from '@/shared/api/axios'

// Axios calls scoped to the Report module (sdd.md §2) — PRD v1
// §3.14/§4.13. Mirrors Modules/Report/routes/api.php's seven report
// types.
export default {
  types() {
    return api.get('/reports')
  },
  salesOrders(params = {}) {
    return api.get('/reports/sales-orders', { params })
  },
  production(params = {}) {
    return api.get('/reports/production', { params })
  },
  stock() {
    return api.get('/reports/stock')
  },
  subcontract(params = {}) {
    return api.get('/reports/subcontract', { params })
  },
  partyLedger(params = {}) {
    return api.get('/reports/party-ledger', { params })
  },
  cashbook(params = {}) {
    return api.get('/reports/cashbook', { params })
  },
  traceability(serial) {
    return api.get(`/reports/traceability/${encodeURIComponent(serial)}`)
  },
}
