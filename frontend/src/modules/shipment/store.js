import { defineStore } from 'pinia'
import shipmentApi from './api'

// Pinia store for the Shipment module (sdd.md §2).
export const useShipmentStore = defineStore('shipment', {
  state: () => ({
    items: [],
    meta: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetchList(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await shipmentApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load shipments.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createShipment(payload) {
      const { data } = await shipmentApi.create(payload)
      return data.data
    },

    async updateShipment(id, payload) {
      const { data } = await shipmentApi.update(id, payload)
      return data.data
    },

    async removeShipment(id) {
      await shipmentApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
