<script setup>
import { onMounted, ref } from 'vue'
import { useOrderStore } from '../store'

// PRD v1 §3.1 / §4.2 — Order List.
const store = useOrderStore()
const statusFilter = ref('')

async function load() {
  await store.fetchList({ status: statusFilter.value || undefined })
}

onMounted(load)

async function handleDelete(order) {
  if (!confirm(`Delete order ${order.order_no}?`)) return
  await store.removeOrder(order.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Orders</h1>
      <router-link
        :to="{ name: 'orders.create' }"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add New Order
      </router-link>
    </div>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <select v-model="statusFilter" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All statuses</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="in_production">In Production</option>
        <option value="shipped">Shipped</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Order No</th>
          <th class="px-3 py-2">Party</th>
          <th class="px-3 py-2">Season</th>
          <th class="px-3 py-2">Grand Total</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in store.items" :key="order.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-mono">{{ order.order_no }}</td>
          <td class="px-3 py-2">{{ order.party?.name }}</td>
          <td class="px-3 py-2">{{ order.season }}</td>
          <td class="px-3 py-2">{{ order.grand_total }}</td>
          <td class="px-3 py-2 capitalize">{{ order.status?.replace('_', ' ') }}</td>
          <td class="space-x-2 px-3 py-2 text-right">
            <router-link :to="{ name: 'orders.edit', params: { id: order.id } }" class="text-brand-600 hover:underline">
              Edit
            </router-link>
            <button type="button" class="text-red-600 hover:underline" @click="handleDelete(order)">Delete</button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="6" class="px-3 py-6 text-center text-gray-400">No orders yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
