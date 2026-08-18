<script setup>
import { onMounted, ref, watch } from 'vue'
import accountingApi from '../api'

// PRD v1 §3.12/§4.11 — Party Due List: a consolidated dues dashboard.
// Same underlying data as Party Ledger (App\Services\
// PartyFinancialsService), reframed as a dues-first view; the PRD's
// other two tabs ("Credit Voucher"/"Debit Voucher") are just the
// existing voucher lists (see the sidebar's Accounts & Bank group) —
// see Modules/Accounting/README.md "Known simplifications".
const tab = ref('buyer')
const rows = ref([])
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await accountingApi.partyLedger.list({ type: tab.value, per_page: 100 })
    rows.value = data.data
  } finally {
    loading.value = false
  }
}
onMounted(load)
watch(tab, load)

const totals = () => ({
  total_bill: rows.value.reduce((sum, r) => sum + Number(r.financials.total_bill), 0).toFixed(2),
  paid: rows.value.reduce((sum, r) => sum + Number(r.financials.paid), 0).toFixed(2),
  due: rows.value.reduce((sum, r) => sum + Number(r.financials.due), 0).toFixed(2),
})
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Party Due List</h1>

    <div class="mb-4 flex gap-2">
      <button
        v-for="t in ['buyer', 'supplier', 'subcontractor']"
        :key="t"
        type="button"
        class="rounded px-3 py-1.5 text-sm capitalize"
        :class="tab === t ? 'bg-brand-600 text-white' : 'border border-gray-300 text-gray-700'"
        @click="tab = t"
      >
        {{ t }}
      </button>
    </div>

    <div v-if="rows.length" class="mb-4 grid grid-cols-3 gap-3">
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Total Bill</div>
        <div class="font-mono font-semibold">{{ totals().total_bill }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Total Paid</div>
        <div class="font-mono font-semibold">{{ totals().paid }}</div>
      </div>
      <div class="rounded border border-gray-200 bg-white p-3 text-center">
        <div class="text-xs text-gray-500">Total Due</div>
        <div class="font-mono font-semibold">{{ totals().due }}</div>
      </div>
    </div>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Party Name</th>
          <th class="px-3 py-2">Phone</th>
          <th class="px-3 py-2">Total Bill</th>
          <th class="px-3 py-2">Paid</th>
          <th class="px-3 py-2">Due</th>
          <th class="px-3 py-2">Remarks</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.party.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ row.party.name }}</td>
          <td class="px-3 py-2">{{ row.party.phone || '—' }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.total_bill }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.paid }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.due }}</td>
          <td class="px-3 py-2">{{ row.party.remarks || '—' }}</td>
        </tr>
        <tr v-if="!loading && rows.length === 0">
          <td colspan="6" class="px-3 py-6 text-center text-gray-400">No {{ tab }}s yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
