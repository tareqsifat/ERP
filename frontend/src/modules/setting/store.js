import { defineStore } from 'pinia'
import settingApi from './api'

// Pinia store for the Setting module (sdd.md §2).
export const useSettingStore = defineStore('setting', {
  state: () => ({
    groups: { currency: {}, notification: {}, system: {}, company: {} },
    loading: false,
  }),

  actions: {
    async fetchAll() {
      this.loading = true
      try {
        const { data } = await settingApi.list()
        this.groups = data.data
      } finally {
        this.loading = false
      }
    },

    async updateGroup(group, values) {
      const { data } = await settingApi.update(group, values)
      this.groups = data.data
    },
  },
})
