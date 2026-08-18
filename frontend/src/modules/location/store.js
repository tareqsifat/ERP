import { defineStore } from 'pinia'
import locationApi from './api'

// Pinia store for the Location module (sdd.md §2).
export const useLocationStore = defineStore('location', {
  state: () => ({
    items: [],
    meta: null,
    loading: false,
    error: null,

    transfers: [],
    transfersMeta: null,
  }),

  actions: {
    async fetchList(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await locationApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load locations.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createLocation(payload) {
      const { data } = await locationApi.create(payload)
      return data.data
    },

    async removeLocation(id) {
      await locationApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },

    async fetchTransfers(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await locationApi.stockTransfers.list(params)
        this.transfers = data.data
        this.transfersMeta = data.meta ?? null
        return this.transfers
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load stock transfers.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async dispatchTransfer(payload) {
      const { data } = await locationApi.stockTransfers.dispatch(payload)
      return data.data
    },

    async receiveTransfer(id, payload) {
      const { data } = await locationApi.stockTransfers.receive(id, payload)
      const index = this.transfers.findIndex((t) => t.id === id)
      if (index !== -1) this.transfers[index] = data.data
      return data.data
    },
  },
})
