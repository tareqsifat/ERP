import { defineStore } from 'pinia'
import costingApi from './api'

// Pinia store for the Costing module (sdd.md §2).
export const useCostingStore = defineStore('costing', {
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
        const { data } = await costingApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load costings.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createCosting(payload) {
      const { data } = await costingApi.create(payload)
      return data.data
    },

    async updateCosting(id, payload) {
      const { data } = await costingApi.update(id, payload)
      return data.data
    },

    async removeCosting(id) {
      await costingApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
