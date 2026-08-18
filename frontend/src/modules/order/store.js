import { defineStore } from 'pinia'
import orderApi from './api'

// Pinia store for the Order module (sdd.md §2).
export const useOrderStore = defineStore('order', {
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
        const { data } = await orderApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load orders.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createOrder(payload) {
      const { data } = await orderApi.create(payload)
      return data.data
    },

    async updateOrder(id, payload) {
      const { data } = await orderApi.update(id, payload)
      return data.data
    },

    async removeOrder(id) {
      await orderApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
