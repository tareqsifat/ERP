<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useSubcontractStore } from '../store'
import partyApi from '@/modules/party/api'

// PRD v2 §3.24 — Inward Subcontract: job-work capacity we sell to an
// external party. Creating the order here is step one; the actual
// cutting/sewing/QC happens through the normal Production screens
// (Cutting → Sewing → QC), tagging the Cut Ticket to this order via the
// "Inward Subcontract Order" field on the Cutting screen. Once pieces
// are QC-passed, dispatch them back from here.
const store = useSubcontractStore()
const auth = useAuthStore()

const parties = ref([])
const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

function blankForm() {
  return {
    direction: 'inward', party_id: '', style: '', color: '', size: '',
    rate: '', rate_unit: 'piece', quantity_expected: 1, expected_date: '', remarks: '',
  }
}
const form = reactive(blankForm())

async function load() {
  const partyRes = await partyApi.list({ type: 'subcontractor', per_page: 100 })
  parties.value = partyRes.data.data
  await store.fetchList({ direction: 'inward' })
}

onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await store.createOrder(form)
    Object.assign(form, blankForm())
    showForm.value = false
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this subcontract order.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleDispatchBack(order) {
  if (!confirm(`Dispatch back all QC-passed pieces for ${order.subcontract_no}? This closes the order.`)) return
  errorMessage.value = ''
  try {
    await store.dispatchBack(order.id)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not dispatch this job back — are there any QC-passed pieces yet?'
  }
}

function partyName(id) {
  return parties.value.find((p) => p.id === id)?.name || `#${id}`
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Inward Subcontract</h1>
      <button
        v-if="auth.can('subcontract.inward.manage')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Inward Order' }}
      </button>
    </div>

    <p class="mb-4 rounded border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
      After creating an order here, go to Production → Cutting and tag a Cut Ticket to it via the
      "Inward Subcontract Order" field, then run it through Sewing and QC as normal. QC-passed pieces
      stay here until you dispatch them back.
    </p>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <div class="grid grid-cols-3 gap-3">
        <select v-model="form.party_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Subcontractor…</option>
          <option v-for="p in parties" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <input v-model="form.style" required placeholder="Style" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.color" placeholder="Color" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="grid grid-cols-4 gap-3">
        <input v-model="form.size" placeholder="Size" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model.number="form.rate" type="number" step="0.01" min="0.01" required placeholder="Rate" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.rate_unit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="piece">per piece</option>
          <option value="dozen">per dozen</option>
        </select>
        <input v-model.number="form.quantity_expected" type="number" min="1" required placeholder="Quantity expected" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Create Order' }}
      </button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Subcontract No</th>
          <th class="px-3 py-2">Party</th>
          <th class="px-3 py-2">Style/Color/Size</th>
          <th class="px-3 py-2">Rate</th>
          <th class="px-3 py-2">Job Work Income</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in store.inwardOrders" :key="order.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-mono">{{ order.subcontract_no }}</td>
          <td class="px-3 py-2">{{ partyName(order.party_id) }}</td>
          <td class="px-3 py-2">{{ order.style }} / {{ order.color || '—' }} / {{ order.size || '—' }}</td>
          <td class="px-3 py-2">{{ order.rate }} / {{ order.rate_unit }}</td>
          <td class="px-3 py-2">{{ order.job_work_income_amount ?? '—' }}</td>
          <td class="px-3 py-2">
            <span
              class="rounded px-2 py-0.5 text-xs capitalize"
              :class="{
                'bg-yellow-100 text-yellow-800': order.status === 'open',
                'bg-blue-100 text-blue-800': order.status === 'partially_returned',
                'bg-green-100 text-green-800': order.status === 'closed',
              }"
            >
              {{ order.status.replace('_', ' ') }}
            </span>
          </td>
          <td class="px-3 py-2 text-right">
            <button
              v-if="order.status !== 'closed' && auth.can('subcontract.inward.manage')"
              type="button"
              class="text-brand-600 hover:underline"
              @click="handleDispatchBack(order)"
            >
              Dispatch Back
            </button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.inwardOrders.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No inward subcontract orders yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
