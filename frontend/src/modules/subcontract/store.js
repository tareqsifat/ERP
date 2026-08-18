import { defineStore } from 'pinia'
import subcontractApi from './api'

// Pinia store for the Subcontract module (sdd.md §2). One store covers
// both directions — views filter `orders` by `direction` client-side,
// matching how the backend shares one controller/resource.
export const useSubcontractStore = defineStore('subcontract', {
  state: () => ({
    orders: [],
    meta: null,
    loading: false,
    error: null,

    ledgerEntries: [],
    ledgerOrderId: null,
  }),

  getters: {
    outwardOrders: (state) => state.orders.filter((o) => o.direction === 'outward'),
    inwardOrders: (state) => state.orders.filter((o) => o.direction === 'inward'),
  },

  actions: {
    async fetchList(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await subcontractApi.list(params)
        this.orders = data.data
        this.meta = data.meta ?? null
        return this.orders
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load subcontract orders.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createOrder(payload) {
      const { data } = await subcontractApi.create(payload)
      this.orders.unshift(data.data)
      return data.data
    },

    replaceOrder(updated) {
      const index = this.orders.findIndex((o) => o.id === updated.id)
      if (index !== -1) this.orders[index] = updated
    },

    async issuePieces(id, payload) {
      return (await subcontractApi.issuePieces(id, payload)).data.data
    },

    async issueRawMaterial(id, payload) {
      return (await subcontractApi.issueRawMaterial(id, payload)).data.data
    },

    async returnPieces(id, payload) {
      const { data } = await subcontractApi.returnPieces(id, payload)
      this.replaceOrder(data.data)
      return data.data
    },

    async dispatchBack(id) {
      const { data } = await subcontractApi.dispatchBack(id)
      this.replaceOrder(data.data)
      return data.data
    },

    async fetchLedger(id) {
      this.loading = true
      this.error = null
      try {
        const { data } = await subcontractApi.ledger(id)
        this.ledgerEntries = data.data
        this.ledgerOrderId = id
        return this.ledgerEntries
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load the ledger.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async recordPayment(id, payload) {
      const { data } = await subcontractApi.payment(id, payload)
      this.ledgerEntries.unshift(data.data)
      return data.data
    },
  },
})
