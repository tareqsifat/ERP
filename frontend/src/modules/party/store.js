import { defineStore } from 'pinia'
import partyApi from './api'

// Pinia store for the Party module (sdd.md §2).
export const usePartyStore = defineStore('party', {
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
        const { data } = await partyApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load parties.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createParty(payload) {
      const { data } = await partyApi.create(payload)
      return data.data
    },

    async updateParty(id, payload) {
      const { data } = await partyApi.update(id, payload)
      return data.data
    },

    async removeParty(id) {
      await partyApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
