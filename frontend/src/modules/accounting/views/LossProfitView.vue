<script setup>
import { onMounted, ref } from 'vue'
import { useAccountingStore } from '../store'

// PRD v1 §3.13/§4.12 — Loss & Profit, year-filterable summary cards.
const store = useAccountingStore()
const year = ref(new Date().getFullYear())

async function load() {
  await store.fetchLossProfit({ year: year.value })
}
onMounted(load)
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Loss & Profit</h1>

    <form class="mb-6 flex gap-2" @submit.prevent="load">
      <input v-model.number="year" type="number" class="w-28 rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <div v-if="store.lossProfit" class="grid grid-cols-4 gap-4">
      <div class="rounded border border-gray-200 bg-white p-4 text-center">
        <div class="text-xs text-gray-500">Total Sale</div>
        <div class="mt-1 font-mono text-lg font-semibold">{{ store.lossProfit.total_sale }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-4 text-center">
        <div class="text-xs text-gray-500">Total Expense</div>
        <div class="mt-1 font-mono text-lg font-semibold">{{ store.lossProfit.total_expense }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-4 text-center">
        <div class="text-xs text-gray-500">Total Profit</div>
        <div class="mt-1 font-mono text-lg font-semibold text-green-700">{{ store.lossProfit.total_profit }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-4 text-center">
        <div class="text-xs text-gray-500">Total Loss</div>
        <div class="mt-1 font-mono text-lg font-semibold text-red-700">{{ store.lossProfit.total_loss }}</div>
      </div>
    </div>
  </div>
</template>
