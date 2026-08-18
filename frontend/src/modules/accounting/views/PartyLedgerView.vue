<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import accountingApi from '../api'

// PRD v1 §3.10/§4.9 — Party Ledger: Buyer/Supplier(/Subcontractor) tabs,
// Total Bill/Pay/Advance/Due/Balance. Drilling into a party shows its
// bills and vouchers, and lets an Accountant record a new bill.
const auth = useAuthStore()

const tab = ref('buyer')
const rows = ref([])
const loading = ref(false)
const selected = ref(null)

const showBillForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const billForm = reactive({ amount: '', bill_date: new Date().toISOString().slice(0, 10), description: '', reference: '' })

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

async function viewParty(row) {
  const { data } = await accountingApi.partyLedger.get(row.party.id)
  selected.value = data.data
  showBillForm.value = false
}

async function handleAddBill() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await accountingApi.partyLedger.createBill(selected.value.party.id, billForm)
    Object.assign(billForm, { amount: '', bill_date: new Date().toISOString().slice(0, 10), description: '', reference: '' })
    showBillForm.value = false
    await viewParty({ party: selected.value.party })
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not record this bill.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Party Ledger</h1>

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

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Party</th>
          <th class="px-3 py-2">Total Bill</th>
          <th class="px-3 py-2">Paid</th>
          <th class="px-3 py-2">Advance</th>
          <th class="px-3 py-2">Due</th>
          <th class="px-3 py-2">Balance</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.party.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ row.party.name }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.total_bill }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.paid }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.advance }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.due }}</td>
          <td class="px-3 py-2 font-mono">{{ row.financials.balance }}</td>
          <td class="px-3 py-2 text-right">
            <button type="button" class="text-brand-600 hover:underline" @click="viewParty(row)">Details</button>
          </td>
        </tr>
        <tr v-if="!loading && rows.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No {{ tab }}s yet.</td>
        </tr>
      </tbody>
    </table>

    <div v-if="selected" class="mt-6 rounded border border-gray-200 bg-white p-4">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">{{ selected.party.name }}</h2>
        <button
          v-if="auth.can('accounting.voucher.create')"
          type="button"
          class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white"
          @click="showBillForm = !showBillForm"
        >
          {{ showBillForm ? 'Cancel' : 'Add Bill' }}
        </button>
      </div>

      <form v-if="showBillForm" class="mb-4 flex items-end gap-2" @submit.prevent="handleAddBill">
        <input v-model.number="billForm.amount" type="number" step="0.01" min="0.01" required placeholder="Amount" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="billForm.bill_date" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="billForm.description" placeholder="Description" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="billForm.reference" placeholder="Reference (optional)" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white disabled:opacity-60">Save</button>
      </form>
      <p v-if="errorMessage" role="alert" class="mb-3 text-sm text-red-600">{{ errorMessage }}</p>

      <h3 class="mb-2 text-sm font-medium text-gray-700">Bills</h3>
      <table class="mb-4 w-full border-collapse text-sm">
        <tbody>
          <tr v-for="bill in selected.bills" :key="bill.id" class="border-b border-gray-100">
            <td class="py-1">{{ bill.bill_date }}</td>
            <td class="py-1 font-mono">{{ bill.amount }}</td>
            <td class="py-1">{{ bill.description || '—' }}</td>
          </tr>
          <tr v-if="selected.bills.length === 0"><td class="py-2 text-gray-400">No bills yet.</td></tr>
        </tbody>
      </table>

      <h3 class="mb-2 text-sm font-medium text-gray-700">Vouchers</h3>
      <table class="w-full border-collapse text-sm">
        <tbody>
          <tr v-for="voucher in selected.vouchers" :key="voucher.id" class="border-b border-gray-100">
            <td class="py-1 font-mono">{{ voucher.voucher_no }}</td>
            <td class="py-1 capitalize">{{ voucher.purpose }}</td>
            <td class="py-1 font-mono">{{ voucher.amount }}</td>
            <td class="py-1">{{ voucher.date }}</td>
          </tr>
          <tr v-if="selected.vouchers.length === 0"><td class="py-2 text-gray-400">No vouchers yet.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
