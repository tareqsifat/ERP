<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useHrmStore } from '../store'
import hrmApi from '../api'

// PRD v1 §3.11/§4.10/§7.5 — Salaries List with "Pay Salary" action.
const store = useHrmStore()
const auth = useAuthStore()

const employees = ref([])
const showOpenForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const payingId = ref(null)
const payForm = reactive({ amount: '', payment_method: 'cash' })

function blankOpenForm() {
  return { employee_id: '', month: new Date().getMonth() + 1, year: new Date().getFullYear() }
}
const openForm = reactive(blankOpenForm())

async function load() {
  const empRes = await hrmApi.employees.list({ per_page: 100 })
  employees.value = empRes.data.data
  await store.fetchSalaries({ per_page: 100 })
}
onMounted(load)

async function handleOpen() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await store.openSalaryMonth(openForm)
    Object.assign(openForm, blankOpenForm())
    showOpenForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not open this salary month.'
  } finally {
    isSubmitting.value = false
  }
}

function startPay(payment) {
  payingId.value = payment.id
  payForm.amount = payment.due_amount
  payForm.payment_method = 'cash'
  errorMessage.value = ''
}

async function submitPay(payment) {
  errorMessage.value = ''
  try {
    await store.paySalary(payment.id, payForm)
    payingId.value = null
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not record this payment.'
  }
}

function employeeName(id) {
  return employees.value.find((e) => e.id === id)?.full_name || `#${id}`
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Salaries List</h1>
      <button
        v-if="auth.can('hrm.salary.pay')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showOpenForm = !showOpenForm"
      >
        {{ showOpenForm ? 'Cancel' : 'Open Salary Month' }}
      </button>
    </div>

    <form v-if="showOpenForm" class="mb-6 flex items-end gap-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleOpen">
      <select v-model="openForm.employee_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="" disabled>Employee…</option>
        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.full_name }}</option>
      </select>
      <select v-model.number="openForm.month" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
      </select>
      <input v-model.number="openForm.year" type="number" class="w-24 rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Open' }}
      </button>
    </form>
    <p v-if="errorMessage" role="alert" class="mb-4 text-sm text-red-600">{{ errorMessage }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Employee</th>
          <th class="px-3 py-2">Month/Year</th>
          <th class="px-3 py-2">Salary</th>
          <th class="px-3 py-2">Paid</th>
          <th class="px-3 py-2">Due</th>
          <th class="px-3 py-2">Payment Method</th>
          <th class="px-3 py-2">Pay Date</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <template v-for="payment in store.salaryPayments" :key="payment.id">
          <tr class="border-b border-gray-100">
            <td class="px-3 py-2">{{ employeeName(payment.employee_id) }}</td>
            <td class="px-3 py-2">{{ payment.month }}/{{ payment.year }}</td>
            <td class="px-3 py-2 font-mono">{{ payment.salary_amount }}</td>
            <td class="px-3 py-2 font-mono">{{ payment.paid_amount }}</td>
            <td class="px-3 py-2 font-mono">{{ payment.due_amount }}</td>
            <td class="px-3 py-2">{{ payment.payment_method || '—' }}</td>
            <td class="px-3 py-2">{{ payment.pay_date || '—' }}</td>
            <td class="px-3 py-2 text-right">
              <button
                v-if="payment.due_amount !== '0.00' && auth.can('hrm.salary.pay')"
                type="button"
                class="text-brand-600 hover:underline"
                @click="startPay(payment)"
              >
                Pay Salary
              </button>
            </td>
          </tr>
          <tr v-if="payingId === payment.id" class="border-b border-gray-100 bg-gray-50">
            <td colspan="8" class="px-3 py-3">
              <form class="flex items-end gap-2" @submit.prevent="submitPay(payment)">
                <input v-model.number="payForm.amount" type="number" step="0.01" min="0.01" required placeholder="Amount" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
                <select v-model="payForm.payment_method" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
                  <option value="cash">Cash</option>
                  <option value="bank">Bank</option>
                </select>
                <button type="submit" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white">Confirm</button>
                <button type="button" class="rounded border border-gray-300 px-3 py-1.5 text-sm" @click="payingId = null">Cancel</button>
              </form>
            </td>
          </tr>
        </template>
        <tr v-if="!store.loading && store.salaryPayments.length === 0">
          <td colspan="8" class="px-3 py-6 text-center text-gray-400">No salary records yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
