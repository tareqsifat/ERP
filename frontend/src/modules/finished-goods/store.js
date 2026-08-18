import { defineStore } from 'pinia'
import finishedGoodsApi from './api'

// Pinia store for the Finished Goods module (sdd.md §2).
export const useFinishedGoodsStore = defineStore('finishedGoods', {
  state: () => ({
    stock: [],
    movements: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchStock(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await finishedGoodsApi.stock(params)
        this.stock = data.data
        return this.stock
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load Finished Goods stock.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchMovements(params = {}) {
      const { data } = await finishedGoodsApi.movements(params)
      this.movements = data.data
      return this.movements
    },
  },
})
