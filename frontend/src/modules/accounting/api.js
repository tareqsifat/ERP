import api from '@/shared/api/axios'

// Axios calls scoped to the Accounting module (sdd.md §2) — PRD v1
// §3.9/§3.12/§3.13. Grouped by sub-resource, mirroring
// Modules/Accounting/routes/api.php's own grouping.
export default {
  categories: {
    list(params = {}) {
      return api.get('/accounting-categories', { params })
    },
    create(payload) {
      return api.post('/accounting-categories', payload)
    },
    update(id, payload) {
      return api.put(`/accounting-categories/${id}`, payload)
    },
    remove(id) {
      return api.delete(`/accounting-categories/${id}`)
    },
  },

  banks: {
    list() {
      return api.get('/bank-accounts')
    },
    create(payload) {
      return api.post('/bank-accounts', payload)
    },
    transactions(id) {
      return api.get(`/bank-accounts/${id}/transactions`)
    },
    deposit(id, payload) {
      return api.post(`/bank-accounts/${id}/deposit`, payload)
    },
    withdraw(id, payload) {
      return api.post(`/bank-accounts/${id}/withdraw`, payload)
    },
  },

  cash: {
    list(params = {}) {
      return api.get('/cash', { params })
    },
    increase(payload) {
      return api.post('/cash/increase', payload)
    },
    reduce(payload) {
      return api.post('/cash/reduce', payload)
    },
  },

  cheques: {
    list(params = {}) {
      return api.get('/cheques', { params })
    },
    create(payload) {
      return api.post('/cheques', payload)
    },
    markPassed(id) {
      return api.post(`/cheques/${id}/mark-passed`)
    },
  },

  vouchers: {
    list(params = {}) {
      return api.get('/vouchers', { params })
    },
    create(payload) {
      return api.post('/vouchers', payload)
    },
  },

  partyLedger: {
    list(params = {}) {
      return api.get('/party-ledger', { params })
    },
    get(partyId) {
      return api.get(`/party-ledger/${partyId}`)
    },
    createBill(partyId, payload) {
      return api.post(`/party-ledger/${partyId}/bills`, payload)
    },
  },

  transactions(params = {}) {
    return api.get('/transactions', { params })
  },

  cashbook(params = {}) {
    return api.get('/cashbook', { params })
  },

  lossProfit(params = {}) {
    return api.get('/loss-profit', { params })
  },
}
