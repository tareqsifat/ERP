<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useSubcontractStore } from '../store'

// PRD v2 §3.23/§3.24/§4.9 — the append-only value ledger for a single
// subcontract order (issue_value/return_value/shortage_deduction/
// job_work_income are posted automatically by the outward/inward
// actions; `payment` is the only entry recorded directly here).
const store = useSubcontractStore()
const auth = useAuthStore()

const selectedOrderId = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

function blankPayment() {
  return { amount: '', occurred_on: new Date().toISOString().slice(0, 10), remarks: '' }
}
const paymentForm = reactive(blankPayment())

async function load() {
  await store.fetchList({ per_page: 100 })
}

onMounted(load)

watch(selectedOrderId, async (id) => {
  if (!id) {
    store.ledgerEntries = []
    return
  }
  await store.fetchLedger(id)
})

async function handlePayment() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await store.recordPayment(selectedOrderId.value, paymentForm)
    Object.assign(paymentForm, blankPayment())
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not record this payment.'
  } finally {
    isSubmitting.value = false
  }
}

const typeLabels = {
  issue_value: 'Issue Value',
  return_value: 'Return Value',
  shortage_deduction: 'Shortage Deduction',
  job_work_income: 'Job Work Income',
  payment: 'Payment',
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Subcontractor Ledger</h1>

    <select v-model="selectedOrderId" class="mb-4 w-full max-w-md rounded border border-gray-300 px-3 py-1.5 text-sm">
      <option value="" disabled>Select a subcontract order…</option>
      <option v-for="order in store.orders" :key="order.id" :value="order.id">
        {{ order.subcontract_no }} — {{ order.direction }} — {{ order.style }}
      </option>
    </select>

    <template v-if="selectedOrderId">
      <form
        v-if="auth.can('subcontract.outward.manage') || auth.can('subcontract.inward.manage')"
        class="mb-6 flex items-end gap-3 rounded border border-gray-200 bg-white p-4"
        @submit.prevent="handlePayment"
      >
        <div>
          <label class="mb-1 block text-xs text-gray-500">Amount</label>
          <input v-model.number="paymentForm.amount" type="number" step="0.01" min="0.01" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">Date</label>
          <input v-model="paymentForm.occurred_on" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        </div>
        <div class="flex-1">
          <label class="mb-1 block text-xs text-gray-500">Remarks</label>
          <input v-model="paymentForm.remarks" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
        </div>
        <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
          {{ isSubmitting ? 'Saving…' : 'Record Payment' }}
        </button>
      </form>
      <p v-if="errorMessage" role="alert" class="mb-4 text-sm text-red-600">{{ errorMessage }}</p>

      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Date</th>
            <th class="px-3 py-2">Type</th>
            <th class="px-3 py-2">Amount</th>
            <th class="px-3 py-2">Remarks</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in store.ledgerEntries" :key="entry.id" class="border-b border-gray-100">
            <td class="px-3 py-2">{{ entry.occurred_on }}</td>
            <td class="px-3 py-2">{{ typeLabels[entry.type] || entry.type }}</td>
            <td class="px-3 py-2">{{ entry.amount }}</td>
            <td class="px-3 py-2">{{ entry.remarks || '—' }}</td>
          </tr>
          <tr v-if="!store.loading && store.ledgerEntries.length === 0">
            <td colspan="4" class="px-3 py-6 text-center text-gray-400">No ledger entries for this order yet.</td>
          </tr>
        </tbody>
      </table>
    </template>
  </div>
</template>
