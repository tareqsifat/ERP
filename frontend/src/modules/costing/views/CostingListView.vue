<script setup>
import { onMounted } from 'vue'
import { useCostingStore } from '../store'

// PRD v1 §3.3 — Costing List.
const store = useCostingStore()

onMounted(() => store.fetchList())

async function handleDelete(costing) {
  if (!confirm('Delete this costing line?')) return
  await store.removeCosting(costing.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Costings</h1>
      <router-link
        :to="{ name: 'costings.create' }"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add New Costing
      </router-link>
    </div>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Order</th>
          <th class="px-3 py-2">Style</th>
          <th class="px-3 py-2">Costed Qty</th>
          <th class="px-3 py-2">Avg Unit Cost</th>
          <th class="px-3 py-2">Total Cost</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="costing in store.items" :key="costing.id" class="border-b border-gray-100">
          <td class="px-3 py-2">#{{ costing.order_id }}</td>
          <td class="px-3 py-2">{{ costing.style }}</td>
          <td class="px-3 py-2">{{ costing.costed_quantity }}</td>
          <td class="px-3 py-2">{{ costing.average_unit_cost }}</td>
          <td class="px-3 py-2">{{ costing.total_cost }}</td>
          <td class="px-3 py-2 capitalize">{{ costing.status }}</td>
          <td class="space-x-2 px-3 py-2 text-right">
            <router-link :to="{ name: 'costings.edit', params: { id: costing.id } }" class="text-brand-600 hover:underline">
              Edit
            </router-link>
            <button type="button" class="text-red-600 hover:underline" @click="handleDelete(costing)">Delete</button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No costing lines yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
