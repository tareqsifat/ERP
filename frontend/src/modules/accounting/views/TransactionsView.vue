<script setup>
import { onMounted, ref } from 'vue'
import accountingApi from '../api'

// PRD v1 §3.9/§4.8 — Monthly Transaction: year/month-filterable daily
// rollup (Date, Total Transaction, Total Amount, Type).
const year = ref(new Date().getFullYear())
const month = ref('')
const rows = ref([])
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await accountingApi.transactions({ year: year.value, month: month.value || undefined })
    rows.value = data.data
  } finally {
    loading.value = false
  }
}
onMounted(load)
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Monthly Transaction</h1>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <input v-model.number="year" type="number" class="w-28 rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <select v-model="month" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All months</option>
        <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Date</th>
          <th class="px-3 py-2">Type</th>
          <th class="px-3 py-2">Total Transactions</th>
          <th class="px-3 py-2">Total Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, i) in rows" :key="i" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ row.date }}</td>
          <td class="px-3 py-2 capitalize">{{ row.type }}</td>
          <td class="px-3 py-2">{{ row.total_transactions }}</td>
          <td class="px-3 py-2 font-mono">{{ row.total_amount }}</td>
        </tr>
        <tr v-if="!loading && rows.length === 0">
          <td colspan="4" class="px-3 py-6 text-center text-gray-400">No transactions in this period.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
