<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useLocationStore } from '../store'
import locationApi from '../api'
import orderApi from '@/modules/order/api'

// PRD v2 §3.21 — Stock Transfer dispatch/receive between locations.
// sdd.md §4: a Showroom Staff user only sees transfers touching their
// own location — enforced server-side (StockTransferController); this
// view just renders whatever the API returns, no client-side filtering.
const store = useLocationStore()
const auth = useAuthStore()

const locations = ref([])
const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const receivingId = ref(null)
const receiveQty = ref(null)

function blankForm() {
  return { from_location_id: '', to_location_id: '', order_id: '', style: '', color: '', size: '', quantity: 1 }
}
const form = reactive(blankForm())

async function load() {
  const [locRes] = await Promise.all([locationApi.list({ per_page: 100 }), store.fetchTransfers()])
  locations.value = locRes.data.data
}

onMounted(load)

async function handleDispatch() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await store.dispatchTransfer(form)
    Object.assign(form, blankForm())
    showForm.value = false
    await store.fetchTransfers()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not dispatch this transfer.'
  } finally {
    isSubmitting.value = false
  }
}

function startReceive(transfer) {
  receivingId.value = transfer.id
  receiveQty.value = transfer.quantity_dispatched
}

async function confirmReceive(transfer) {
  errorMessage.value = ''
  try {
    await store.receiveTransfer(transfer.id, { quantity_received: receiveQty.value })
    receivingId.value = null
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not confirm receipt.'
  }
}

function locationName(id) {
  return locations.value.find((l) => l.id === id)?.name || `#${id}`
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Stock Transfers</h1>
      <button
        v-if="auth.can('stock-transfer.dispatch')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'Dispatch Transfer' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleDispatch">
      <div class="grid grid-cols-3 gap-3">
        <select v-model="form.from_location_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>From location…</option>
          <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
        </select>
        <select v-model="form.to_location_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>To location…</option>
          <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
        </select>
        <input v-model.number="form.order_id" type="number" required placeholder="Order ID" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="grid grid-cols-4 gap-3">
        <input v-model="form.style" required placeholder="Style" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.color" required placeholder="Color" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.size" placeholder="Size (optional)" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model.number="form.quantity" type="number" min="1" required placeholder="Quantity" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Dispatching…' : 'Dispatch' }}
      </button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Transfer No</th>
          <th class="px-3 py-2">From → To</th>
          <th class="px-3 py-2">Style/Color/Size</th>
          <th class="px-3 py-2">Dispatched</th>
          <th class="px-3 py-2">Received</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="transfer in store.transfers" :key="transfer.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-mono">{{ transfer.transfer_no }}</td>
          <td class="px-3 py-2">{{ locationName(transfer.from_location_id) }} → {{ locationName(transfer.to_location_id) }}</td>
          <td class="px-3 py-2">{{ transfer.style }} / {{ transfer.color }} / {{ transfer.size || '—' }}</td>
          <td class="px-3 py-2">{{ transfer.quantity_dispatched }}</td>
          <td class="px-3 py-2">{{ transfer.quantity_received ?? '—' }}</td>
          <td class="px-3 py-2">
            <span
              class="rounded px-2 py-0.5 text-xs capitalize"
              :class="{
                'bg-yellow-100 text-yellow-800': transfer.status === 'dispatched',
                'bg-green-100 text-green-800': transfer.status === 'received',
                'bg-red-100 text-red-800': transfer.status === 'discrepancy',
              }"
            >
              {{ transfer.status }}
            </span>
          </td>
          <td class="px-3 py-2 text-right">
            <template v-if="transfer.status === 'dispatched' && auth.can('stock-transfer.receive')">
              <div v-if="receivingId === transfer.id" class="flex items-center justify-end gap-2">
                <input v-model.number="receiveQty" type="number" min="0" class="w-20 rounded border border-gray-300 px-2 py-1 text-sm" />
                <button type="button" class="text-brand-600 hover:underline" @click="confirmReceive(transfer)">Confirm</button>
              </div>
              <button v-else type="button" class="text-brand-600 hover:underline" @click="startReceive(transfer)">Receive</button>
            </template>
          </td>
        </tr>
        <tr v-if="!store.loading && store.transfers.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No stock transfers yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
