<script setup>
import { onMounted } from 'vue'
import { useBudgetStore } from '../store'

// PRD v1 §3.3 — Budget List.
const store = useBudgetStore()

onMounted(() => store.fetchList())

async function handleDelete(budget) {
  if (!confirm('Delete this budget line?')) return
  await store.removeBudget(budget.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Budgets</h1>
      <router-link
        :to="{ name: 'budgets.create' }"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add New Budget
      </router-link>
    </div>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Order</th>
          <th class="px-3 py-2">Style</th>
          <th class="px-3 py-2">Budgeted Qty</th>
          <th class="px-3 py-2">Avg Unit Price</th>
          <th class="px-3 py-2">Total Value</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="budget in store.items" :key="budget.id" class="border-b border-gray-100">
          <td class="px-3 py-2">#{{ budget.order_id }}</td>
          <td class="px-3 py-2">{{ budget.style }}</td>
          <td class="px-3 py-2">{{ budget.budgeted_quantity }}</td>
          <td class="px-3 py-2">{{ budget.average_unit_price }}</td>
          <td class="px-3 py-2">{{ budget.total_value }}</td>
          <td class="px-3 py-2 capitalize">{{ budget.status }}</td>
          <td class="space-x-2 px-3 py-2 text-right">
            <router-link :to="{ name: 'budgets.edit', params: { id: budget.id } }" class="text-brand-600 hover:underline">
              Edit
            </router-link>
            <button type="button" class="text-red-600 hover:underline" @click="handleDelete(budget)">Delete</button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No budget lines yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
