<script setup>
import { onMounted, reactive, ref } from 'vue'
import accountingApi from '../api'

// PRD v1 §3.9/§4.8 — Daily Cashbook: date-ranged register with a running
// summary panel.
const range = reactive({
  from: new Date(new Date().setDate(1)).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
})
const entries = ref([])
const summary = ref(null)
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await accountingApi.cashbook(range)
    entries.value = data.data
    summary.value = data.meta
  } finally {
    loading.value = false
  }
}
onMounted(load)
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Daily Cashbook</h1>

    <form class="mb-4 flex items-end gap-2" @submit.prevent="load">
      <div>
        <label class="mb-1 block text-xs text-gray-500">From</label>
        <input v-model="range.from" type="date" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">To</label>
        <input v-model="range.to" type="date" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <div v-if="summary" class="mb-6 grid grid-cols-5 gap-3">
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Previous Balance</div>
        <div class="font-mono font-semibold">{{ summary.previous_balance }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Credit</div>
        <div class="font-mono font-semibold text-green-700">{{ summary.credit }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Sub Total</div>
        <div class="font-mono font-semibold">{{ summary.sub_total }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Total Expenses</div>
        <div class="font-mono font-semibold text-red-700">{{ summary.total_expenses }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Cash In Hand</div>
        <div class="font-mono font-semibold">{{ summary.cash_in_hand }}</div>
      </div>
    </div>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Date</th>
          <th class="px-3 py-2">Type</th>
          <th class="px-3 py-2">Amount</th>
          <th class="px-3 py-2">Note</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="entry in entries" :key="entry.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ entry.occurred_on }}</td>
          <td class="px-3 py-2 capitalize">{{ entry.type }}</td>
          <td class="px-3 py-2 font-mono">{{ entry.amount }}</td>
          <td class="px-3 py-2">{{ entry.note || '—' }}</td>
        </tr>
        <tr v-if="!loading && entries.length === 0">
          <td colspan="4" class="px-3 py-6 text-center text-gray-400">No entries in this range.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
