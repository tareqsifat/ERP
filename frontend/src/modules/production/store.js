import { defineStore } from 'pinia'
import productionApi from './api'

// Pinia store for the Production module (sdd.md §2). Kept intentionally
// thin — this module's views call productionApi's sub-resources
// directly for one-off lookups (lines/machines dropdowns, a single
// serial trace), and only route through here for the lists each view
// actually re-renders from.
export const useProductionStore = defineStore('production', {
  state: () => ({
    lines: [],
    machines: [],
    cutTickets: [],
    bundles: [],
    pieceSerials: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchLines(params = {}) {
      const { data } = await productionApi.lines.list(params)
      this.lines = data.data
      return this.lines
    },

    async fetchMachines(params = {}) {
      const { data } = await productionApi.machines.list(params)
      this.machines = data.data
      return this.machines
    },

    async fetchCutTickets(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await productionApi.cutTickets.list(params)
        this.cutTickets = data.data
        return this.cutTickets
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load cut tickets.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async finalizeCutTicket(id) {
      const { data } = await productionApi.cutTickets.finalize(id)
      const index = this.cutTickets.findIndex((t) => t.id === id)
      if (index !== -1) this.cutTickets[index] = data.data
      return data.data
    },

    async fetchBundles(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await productionApi.bundles.list(params)
        this.bundles = data.data
        return this.bundles
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load bundles.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async assignBundleToLine(id, lineId) {
      const { data } = await productionApi.bundles.assignToLine(id, { line_id: lineId })
      const index = this.bundles.findIndex((b) => b.id === id)
      if (index !== -1) this.bundles[index] = data.data
      return data.data
    },

    async logBundleOutput(id) {
      const { data } = await productionApi.bundles.logOutput(id)
      const index = this.bundles.findIndex((b) => b.id === id)
      if (index !== -1) this.bundles[index] = data.data
      return data.data
    },
  },
})
