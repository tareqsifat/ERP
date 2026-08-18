import { defineStore } from 'pinia'
import sampleApi from './api'

// Pinia store for the Sampling module (sdd.md §2).
export const useSampleStore = defineStore('sampling', {
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
        const { data } = await sampleApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load samples.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createSample(payload) {
      const { data } = await sampleApi.create(payload)
      return data.data
    },

    async updateSample(id, payload) {
      const { data } = await sampleApi.update(id, payload)
      return data.data
    },

    async removeSample(id) {
      await sampleApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
