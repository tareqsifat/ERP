<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useAccountingStore } from '../store'
import accountingApi from '../api'

// PRD v1 §3.9/§4.8 — Cash in Hand.
const store = useAccountingStore()
const auth = useAuthStore()

const isSubmitting = ref(false)
const errorMessage = ref('')
const form = reactive({ action: 'increase', amount: null, note: '' })

async function load() {
  await store.fetchCash({ per_page: 50 })
}
onMounted(load)

async function handleAdjust() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    const payload = { amount: form.amount, note: form.note || null }
    if (form.action === 'increase') {
      await accountingApi.cash.increase(payload)
    } else {
      await accountingApi.cash.reduce(payload)
    }
    form.amount = null
    form.note = ''
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not record this transaction.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Cash in Hand</h1>
      <div class="rounded border border-gray-200 bg-white px-4 py-2 text-sm">
        Balance: <span class="font-mono font-semibold">{{ store.cashBalance }}</span>
      </div>
    </div>

    <form v-if="auth.can('accounting.cash.manage')" class="mb-6 flex items-end gap-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleAdjust">
      <div>
        <label class="mb-1 block text-xs text-gray-500">Action</label>
        <select v-model="form.action" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="increase">Increase</option>
          <option value="reduce">Reduce</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Amount</label>
        <input v-model.number="form.amount" type="number" step="0.01" min="0.01" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="flex-1">
        <label class="mb-1 block text-xs text-gray-500">Note</label>
        <input v-model="form.note" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Adjust' }}
      </button>
    </form>
    <p v-if="errorMessage" role="alert" class="mb-4 text-sm text-red-600">{{ errorMessage }}</p>

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
        <tr v-for="tx in store.cashTransactions" :key="tx.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ tx.occurred_on }}</td>
          <td class="px-3 py-2 capitalize">{{ tx.type }}</td>
          <td class="px-3 py-2 font-mono">{{ tx.amount }}</td>
          <td class="px-3 py-2">{{ tx.note || '—' }}</td>
        </tr>
        <tr v-if="!store.loading && store.cashTransactions.length === 0">
          <td colspan="4" class="px-3 py-6 text-center text-gray-400">No cash transactions yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
