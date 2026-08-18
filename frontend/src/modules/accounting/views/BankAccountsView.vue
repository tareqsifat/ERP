<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useAccountingStore } from '../store'
import accountingApi from '../api'

// PRD v1 §3.9/§4.8 — Bank Accounts directory + Deposit/Withdraw.
const store = useAccountingStore()
const auth = useAuthStore()

const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const activeAdjust = reactive({ id: null, action: null })

function blankForm() {
  return { account_holder_name: '', bank_name: '', account_number: '', branch_name: '', routing_swift_no: '' }
}
const form = reactive(blankForm())
const adjustAmount = ref(null)

async function load() {
  await store.fetchBanks()
}
onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await accountingApi.banks.create(form)
    Object.assign(form, blankForm())
    showForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this bank account.'
  } finally {
    isSubmitting.value = false
  }
}

function startAdjust(account, action) {
  activeAdjust.id = account.id
  activeAdjust.action = action
  adjustAmount.value = null
  errorMessage.value = ''
}

async function submitAdjust(account) {
  errorMessage.value = ''
  try {
    if (activeAdjust.action === 'deposit') {
      await accountingApi.banks.deposit(account.id, { amount: adjustAmount.value })
    } else {
      await accountingApi.banks.withdraw(account.id, { amount: adjustAmount.value })
    }
    activeAdjust.id = null
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not record this transaction.'
  }
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Bank Accounts</h1>
      <button
        v-if="auth.can('accounting.bank.manage')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Bank Account' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <div class="grid grid-cols-3 gap-3">
        <input v-model="form.account_holder_name" required placeholder="Account Holder Name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.bank_name" required placeholder="Bank Name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.account_number" required placeholder="Account Number" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input v-model="form.branch_name" placeholder="Branch Name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.routing_swift_no" placeholder="Routing/Swift No" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Create' }}
      </button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Account Holder</th>
          <th class="px-3 py-2">Bank / Branch</th>
          <th class="px-3 py-2">Account No.</th>
          <th class="px-3 py-2">Balance</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <template v-for="account in store.banks" :key="account.id">
          <tr class="border-b border-gray-100">
            <td class="px-3 py-2">{{ account.account_holder_name }}</td>
            <td class="px-3 py-2">{{ account.bank_name }} / {{ account.branch_name || '—' }}</td>
            <td class="px-3 py-2">{{ account.account_number }}</td>
            <td class="px-3 py-2 font-mono">{{ account.balance }}</td>
            <td class="px-3 py-2 text-right">
              <div v-if="auth.can('accounting.bank.manage')" class="flex justify-end gap-2 text-xs">
                <button type="button" class="text-brand-600 hover:underline" @click="startAdjust(account, 'deposit')">Deposit</button>
                <button type="button" class="text-brand-600 hover:underline" @click="startAdjust(account, 'withdraw')">Withdraw</button>
              </div>
            </td>
          </tr>
          <tr v-if="activeAdjust.id === account.id" class="border-b border-gray-100 bg-gray-50">
            <td colspan="5" class="px-3 py-3">
              <form class="flex items-end gap-2" @submit.prevent="submitAdjust(account)">
                <input v-model.number="adjustAmount" type="number" step="0.01" min="0.01" required placeholder="Amount" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
                <button type="submit" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white">
                  Confirm {{ activeAdjust.action === 'deposit' ? 'Deposit' : 'Withdraw' }}
                </button>
                <button type="button" class="rounded border border-gray-300 px-3 py-1.5 text-sm" @click="activeAdjust.id = null">Cancel</button>
              </form>
              <p v-if="errorMessage" role="alert" class="mt-2 text-sm text-red-600">{{ errorMessage }}</p>
            </td>
          </tr>
        </template>
        <tr v-if="store.banks.length === 0">
          <td colspan="5" class="px-3 py-6 text-center text-gray-400">No bank accounts yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
