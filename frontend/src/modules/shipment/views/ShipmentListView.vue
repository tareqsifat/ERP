<script setup>
import { onMounted } from 'vue'
import { useShipmentStore } from '../store'

// PRD v1 §3.6 — Shipments List.
const store = useShipmentStore()

onMounted(() => store.fetchList())

async function handleDelete(shipment) {
  if (!confirm(`Delete shipment ${shipment.invoice_no}?`)) return
  await store.removeShipment(shipment.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Shipments</h1>
      <router-link
        :to="{ name: 'shipments.create' }"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add New Shipment
      </router-link>
    </div>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Invoice No</th>
          <th class="px-3 py-2">Order</th>
          <th class="px-3 py-2">Creator</th>
          <th class="px-3 py-2">Total Qty</th>
          <th class="px-3 py-2">Total CBM</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="shipment in store.items" :key="shipment.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-mono">{{ shipment.invoice_no }}</td>
          <td class="px-3 py-2">#{{ shipment.order_id }}</td>
          <td class="px-3 py-2">{{ shipment.creator?.name }}</td>
          <td class="px-3 py-2">{{ shipment.total_quantity }}</td>
          <td class="px-3 py-2">{{ shipment.total_cbm }}</td>
          <td class="px-3 py-2 capitalize">{{ shipment.status }}</td>
          <td class="space-x-2 px-3 py-2 text-right">
            <router-link :to="{ name: 'shipments.edit', params: { id: shipment.id } }" class="text-brand-600 hover:underline">
              Edit
            </router-link>
            <button type="button" class="text-red-600 hover:underline" @click="handleDelete(shipment)">Delete</button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No shipments yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
