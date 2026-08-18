import { defineStore } from 'pinia'
import budgetApi from './api'

// Pinia store for the Budgeting module (sdd.md §2).
export const useBudgetStore = defineStore('budgeting', {
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
        const { data } = await budgetApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load budgets.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createBudget(payload) {
      const { data } = await budgetApi.create(payload)
      return data.data
    },

    async updateBudget(id, payload) {
      const { data } = await budgetApi.update(id, payload)
      return data.data
    },

    async removeBudget(id) {
      await budgetApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
