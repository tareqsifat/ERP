import { defineStore } from 'pinia'
import rawMaterialApi from './api'

// Pinia store for the Raw Material module (sdd.md §2).
export const useRawMaterialStore = defineStore('rawMaterial', {
  state: () => ({
    items: [],
    meta: null,
    loading: false,
    error: null,

    movements: [],
    purchaseOrders: [],
  }),

  actions: {
    async fetchList(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await rawMaterialApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load raw materials.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createMaterial(payload) {
      const { data } = await rawMaterialApi.create(payload)
      return data.data
    },

    async removeMaterial(id) {
      await rawMaterialApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },

    async fetchMovements(params = {}) {
      const { data } = await rawMaterialApi.movements.list(params)
      this.movements = data.data
      return this.movements
    },

    async fetchPurchaseOrders(params = {}) {
      const { data } = await rawMaterialApi.purchaseOrders.list(params)
      this.purchaseOrders = data.data
      return this.purchaseOrders
    },

    async createPurchaseOrder(payload) {
      const { data } = await rawMaterialApi.purchaseOrders.create(payload)
      this.purchaseOrders.unshift(data.data)
      return data.data
    },

    async receivePurchaseOrder(id, payload) {
      const { data } = await rawMaterialApi.purchaseOrders.receive(id, payload)
      const index = this.purchaseOrders.findIndex((po) => po.id === id)
      if (index !== -1) this.purchaseOrders[index] = data.data
      return data.data
    },
  },
})
