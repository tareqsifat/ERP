import { defineStore } from 'pinia'
import reportApi from './api'

// Pinia store for the Report module (sdd.md §2). Thin — each view owns
// its own date-range filter state; the store just holds the last
// fetched payload per report type so switching tabs doesn't lose data.
export const useReportStore = defineStore('report', {
  state: () => ({
    salesOrders: null,
    production: null,
    stock: null,
    subcontract: null,
    partyLedger: [],
    cashbook: null,
    traceResult: null,
    traceError: '',
    loading: false,
  }),

  actions: {
    async fetchSalesOrders(params = {}) {
      const { data } = await reportApi.salesOrders(params)
      this.salesOrders = data.data
    },
    async fetchProduction(params = {}) {
      const { data } = await reportApi.production(params)
      this.production = data.data
    },
    async fetchStock() {
      const { data } = await reportApi.stock()
      this.stock = data.data
    },
    async fetchSubcontract(params = {}) {
      const { data } = await reportApi.subcontract(params)
      this.subcontract = data.data
    },
    async fetchPartyLedger(params = {}) {
      const { data } = await reportApi.partyLedger(params)
      this.partyLedger = data.data
    },
    async fetchCashbook(params = {}) {
      const { data } = await reportApi.cashbook(params)
      this.cashbook = data
    },
    async lookupSerial(serial) {
      this.traceError = ''
      this.traceResult = null
      try {
        const { data } = await reportApi.traceability(serial)
        this.traceResult = data.data
      } catch (error) {
        this.traceError = error.response?.data?.message || 'No piece found for that serial.'
      }
    },
  },
})
