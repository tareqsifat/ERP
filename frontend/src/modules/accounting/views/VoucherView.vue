<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useAccountingStore } from '../store'
import accountingApi from '../api'
import partyApi from '@/modules/party/api'

// PRD v1 §3.9/§4.8/§6.6 — Credit/Debit Vouchers. One component, two
// routes (credit-vouchers.index / debit-vouchers.index) with `type` as
// a route prop — mirrors the backend's single Voucher model.
const props = defineProps({ type: { type: String, required: true } })

const store = useAccountingStore()
const auth = useAuthStore()

const parties = ref([])
const categories = ref([])
const banks = ref([])
const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

function blankForm() {
  return {
    type: props.type, purpose: 'general', party_id: '', category_id: '', amount: '',
    payment_type: 'cash', bank_account_id: '', date: new Date().toISOString().slice(0, 10),
    bill_no: '', remarks: '',
  }
}
const form = reactive(blankForm())

async function load() {
  const [partyRes, catRes, bankRes] = await Promise.all([
    partyApi.list({ per_page: 100 }),
    accountingApi.categories.list({ kind: props.type === 'credit' ? 'income' : 'expense' }),
    accountingApi.banks.list(),
  ])
  parties.value = partyRes.data.data
  categories.value = catRes.data.data
  banks.value = bankRes.data.data
  await store.fetchVouchers({ type: props.type })
}
onMounted(load)
watch(() => props.type, () => {
  Object.assign(form, blankForm())
  load()
})

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    const payload = { ...form, type: props.type }
    if (!payload.party_id) delete payload.party_id
    if (!payload.category_id) delete payload.category_id
    if (!payload.bank_account_id) delete payload.bank_account_id
    await store.createVoucher(payload)
    Object.assign(form, blankForm())
    showForm.value = false
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not record this voucher.'
  } finally {
    isSubmitting.value = false
  }
}

function partyName(id) {
  return parties.value.find((p) => p.id === id)?.name || '—'
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">{{ type === 'credit' ? 'Credit' : 'Debit' }} Voucher</h1>
      <button
        v-if="auth.can('accounting.voucher.create')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Voucher' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <div class="grid grid-cols-4 gap-3">
        <select v-model="form.purpose" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="general">General</option>
          <option value="payment">Payment (against a party)</option>
          <option value="advance">Advance (against a party)</option>
        </select>
        <select v-model="form.party_id" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="">No party</option>
          <option v-for="p in parties" :key="p.id" :value="p.id">{{ p.name }} ({{ p.type }})</option>
        </select>
        <select v-model="form.category_id" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="">No category</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <input v-model.number="form.amount" type="number" step="0.01" min="0.01" required placeholder="Amount" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="grid grid-cols-4 gap-3">
        <select v-model="form.payment_type" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="cheque">Cheque</option>
        </select>
        <select v-if="form.payment_type === 'bank'" v-model="form.bank_account_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Bank Account…</option>
          <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.bank_name }}</option>
        </select>
        <input v-model="form.date" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.bill_no" placeholder="Bill No (optional)" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <input v-model="form.remarks" placeholder="Remarks (optional)" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Record Voucher' }}
      </button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Voucher No</th>
          <th class="px-3 py-2">Party</th>
          <th class="px-3 py-2">Purpose</th>
          <th class="px-3 py-2">Payment</th>
          <th class="px-3 py-2">Date</th>
          <th class="px-3 py-2" :class="type === 'credit' ? 'text-green-700' : 'text-red-700'">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="voucher in store.vouchers" :key="voucher.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-mono">{{ voucher.voucher_no }}</td>
          <td class="px-3 py-2">{{ voucher.party_id ? partyName(voucher.party_id) : '—' }}</td>
          <td class="px-3 py-2 capitalize">{{ voucher.purpose }}</td>
          <td class="px-3 py-2 capitalize">{{ voucher.payment_type }}</td>
          <td class="px-3 py-2">{{ voucher.date }}</td>
          <td class="px-3 py-2 font-mono" :class="type === 'credit' ? 'text-green-700' : 'text-red-700'">{{ voucher.amount }}</td>
        </tr>
        <tr v-if="!store.loading && store.vouchers.length === 0">
          <td colspan="6" class="px-3 py-6 text-center text-gray-400">No vouchers yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
