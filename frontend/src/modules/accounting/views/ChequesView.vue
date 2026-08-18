<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useAccountingStore } from '../store'
import accountingApi from '../api'

// PRD v1 §3.9/§4.8 — Cheques (Passed/Unused tabs).
const store = useAccountingStore()
const auth = useAuthStore()

const banks = ref([])
const statusFilter = ref('')
const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

function blankForm() {
  return { bank_account_id: '', cheque_no: '', amount: '', issue_date: new Date().toISOString().slice(0, 10), type: 'expense' }
}
const form = reactive(blankForm())

async function load() {
  const bankRes = await accountingApi.banks.list()
  banks.value = bankRes.data.data
  await store.fetchCheques({ status: statusFilter.value || undefined })
}
onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await accountingApi.cheques.create(form)
    Object.assign(form, blankForm())
    showForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this cheque.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleMarkPassed(cheque) {
  if (!confirm(`Mark cheque ${cheque.cheque_no} as passed? This posts it to the bank ledger.`)) return
  errorMessage.value = ''
  try {
    await accountingApi.cheques.markPassed(cheque.id)
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not mark this cheque as passed.'
  }
}

function bankName(id) {
  return banks.value.find((b) => b.id === id)?.bank_name || `#${id}`
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Cheques</h1>
      <button
        v-if="auth.can('accounting.cheque.manage')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Cheque' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <div class="grid grid-cols-3 gap-3">
        <select v-model="form.bank_account_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Bank Account…</option>
          <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.bank_name }} — {{ b.account_number }}</option>
        </select>
        <input v-model="form.cheque_no" required placeholder="Cheque No" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model.number="form.amount" type="number" step="0.01" min="0.01" required placeholder="Amount" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input v-model="form.issue_date" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.type" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="income">Income (received)</option>
          <option value="expense">Expense (issued)</option>
        </select>
      </div>
      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Create' }}
      </button>
    </form>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <select v-model="statusFilter" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All</option>
        <option value="unused">Unused</option>
        <option value="passed">Passed</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Cheque No</th>
          <th class="px-3 py-2">Bank</th>
          <th class="px-3 py-2">Amount</th>
          <th class="px-3 py-2">Issue Date</th>
          <th class="px-3 py-2">Type</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="cheque in store.cheques" :key="cheque.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-mono">{{ cheque.cheque_no }}</td>
          <td class="px-3 py-2">{{ bankName(cheque.bank_account_id) }}</td>
          <td class="px-3 py-2">{{ cheque.amount }}</td>
          <td class="px-3 py-2">{{ cheque.issue_date }}</td>
          <td class="px-3 py-2 capitalize">{{ cheque.type }}</td>
          <td class="px-3 py-2">
            <span class="rounded px-2 py-0.5 text-xs capitalize" :class="cheque.status === 'passed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
              {{ cheque.status }}
            </span>
          </td>
          <td class="px-3 py-2 text-right">
            <button
              v-if="cheque.status === 'unused' && auth.can('accounting.cheque.manage')"
              type="button"
              class="text-brand-600 hover:underline"
              @click="handleMarkPassed(cheque)"
            >
              Mark Passed
            </button>
          </td>
        </tr>
        <tr v-if="store.cheques.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No cheques yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
