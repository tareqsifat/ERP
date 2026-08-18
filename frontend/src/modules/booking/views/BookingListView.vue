<script setup>
import { onMounted } from 'vue'
import { useBookingStore } from '../store'

// PRD v1 §3.2 — Booking List.
const store = useBookingStore()

onMounted(() => store.fetchList())

async function handleDelete(booking) {
  if (!confirm(`Delete this booking?`)) return
  await store.removeBooking(booking.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Bookings</h1>
      <router-link
        :to="{ name: 'bookings.create' }"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add New Booking
      </router-link>
    </div>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Order</th>
          <th class="px-3 py-2">Booking Date</th>
          <th class="px-3 py-2">Composition</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="booking in store.items" :key="booking.id" class="border-b border-gray-100">
          <td class="px-3 py-2">#{{ booking.order_id }}</td>
          <td class="px-3 py-2">{{ booking.booking_date }}</td>
          <td class="px-3 py-2">{{ booking.composition }}</td>
          <td class="px-3 py-2 capitalize">{{ booking.status }}</td>
          <td class="space-x-2 px-3 py-2 text-right">
            <router-link :to="{ name: 'bookings.edit', params: { id: booking.id } }" class="text-brand-600 hover:underline">
              Edit
            </router-link>
            <button type="button" class="text-red-600 hover:underline" @click="handleDelete(booking)">Delete</button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="5" class="px-3 py-6 text-center text-gray-400">No bookings yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
