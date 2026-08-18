import { defineStore } from 'pinia'
import bookingApi from './api'

// Pinia store for the Booking module (sdd.md §2).
export const useBookingStore = defineStore('booking', {
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
        const { data } = await bookingApi.list(params)
        this.items = data.data
        this.meta = data.meta ?? null
        return this.items
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load bookings.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createBooking(payload) {
      const { data } = await bookingApi.create(payload)
      return data.data
    },

    async updateBooking(id, payload) {
      const { data } = await bookingApi.update(id, payload)
      return data.data
    },

    async removeBooking(id) {
      await bookingApi.remove(id)
      this.items = this.items.filter((item) => item.id !== id)
    },
  },
})
