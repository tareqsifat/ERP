import { defineStore } from 'pinia'
import accountingApi from './api'

// Pinia store for the Accounting module (sdd.md §2).
export const useAccountingStore = defineStore('accounting', {
  state: () => ({
    categories: [],
    banks: [],
    cashTransactions: [],
    cashBalance: '0.00',
    cheques: [],
    vouchers: [],
    partyLedgerRows: [],
    lossProfit: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetchCategories(params = {}) {
      const { data } = await accountingApi.categories.list(params)
      this.categories = data.data
      return this.categories
    },

    async fetchBanks() {
      const { data } = await accountingApi.banks.list()
      this.banks = data.data
      return this.banks
    },

    async fetchCash(params = {}) {
      this.loading = true
      try {
        const { data } = await accountingApi.cash.list(params)
        this.cashTransactions = data.data
        this.cashBalance = data.meta?.balance ?? '0.00'
        return this.cashTransactions
      } finally {
        this.loading = false
      }
    },

    async fetchCheques(params = {}) {
      const { data } = await accountingApi.cheques.list(params)
      this.cheques = data.data
      return this.cheques
    },

    async fetchVouchers(params = {}) {
      this.loading = true
      try {
        const { data } = await accountingApi.vouchers.list(params)
        this.vouchers = data.data
        return this.vouchers
      } finally {
        this.loading = false
      }
    },

    async createVoucher(payload) {
      const { data } = await accountingApi.vouchers.create(payload)
      this.vouchers.unshift(data.data)
      return data.data
    },

    async fetchPartyLedger(params = {}) {
      this.loading = true
      try {
        const { data } = await accountingApi.partyLedger.list(params)
        this.partyLedgerRows = data.data
        return this.partyLedgerRows
      } finally {
        this.loading = false
      }
    },

    async fetchLossProfit(params = {}) {
      const { data } = await accountingApi.lossProfit(params)
      this.lossProfit = data.data
      return this.lossProfit
    },
  },
})
