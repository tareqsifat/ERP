<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useProductionStore } from '../store'
import productionApi from '../api'
import orderApi from '@/modules/order/api'
import userApi from '@/modules/user/api'
import rawMaterialApi from '@/modules/raw-material/api'
import locationApi from '@/modules/location/api'
import subcontractApi from '@/modules/subcontract/api'

// PRD v2 §3.17 — Cutting: Cut Ticket create + finalize. Finalize is the
// one irreversible action (deducts fabric, generates bundles/serials —
// see backend Modules/Production/App/Services/CuttingService), so it's
// a separate confirmed button, never bundled into save.
const store = useProductionStore()
const auth = useAuthStore()

const orders = ref([])
const users = ref([])
const materials = ref([])
const factories = ref([])
// PRD v2 §3.24 — open Inward Subcontract jobs, so a Cut Ticket can be
// tagged as processing external job-work capacity (optional; leave blank
// for a normal in-house cut).
const inwardOrders = ref([])

const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const statusFilter = ref('')

function blankForm() {
  return {
    order_id: '', style: '', color: '', size: '', cut_date: new Date().toISOString().slice(0, 10),
    cutting_master_id: '', raw_material_id: '', fabric_consumed: 0, location_id: '',
    bundle_size: 20, planned_quantity: 1, inward_subcontract_order_id: '',
  }
}
const form = reactive(blankForm())

async function load() {
  const [orderRes, userRes, matRes, locRes, inwardRes] = await Promise.all([
    orderApi.list({ per_page: 100 }),
    userApi.list({ per_page: 100 }),
    rawMaterialApi.list({ per_page: 100 }),
    locationApi.list({ type: 'factory', per_page: 100 }),
    subcontractApi.list({ direction: 'inward', status: 'open', per_page: 100 }).catch(() => ({ data: { data: [] } })),
  ])
  orders.value = orderRes.data.data
  users.value = userRes.data.data
  materials.value = matRes.data.data
  factories.value = locRes.data.data
  inwardOrders.value = inwardRes.data.data
  await store.fetchCutTickets({ status: statusFilter.value || undefined })
}

onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    const payload = { ...form, inward_subcontract_order_id: form.inward_subcontract_order_id || null }
    const { data } = await productionApi.cutTickets.create(payload)
    store.cutTickets.unshift(data.data)
    Object.assign(form, blankForm())
    showForm.value = false
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this cut ticket.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleFinalize(ticket) {
  if (!confirm(`Finalize cut ticket #${ticket.id}? This deducts fabric stock and generates bundles/serials — it cannot be undone.`)) return
  errorMessage.value = ''
  try {
    await store.finalizeCutTicket(ticket.id)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not finalize this cut ticket.'
  }
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Cutting — Cut Tickets</h1>
      <button
        v-if="auth.can('production.cutting.create')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Cut Ticket' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <div class="grid grid-cols-3 gap-3">
        <select v-model="form.order_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Order…</option>
          <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.order_no || `Order #${o.id}` }}</option>
        </select>
        <input v-model="form.style" required placeholder="Style" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.color" required placeholder="Color" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="grid grid-cols-3 gap-3">
        <input v-model="form.size" placeholder="Size (optional)" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.cut_date" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.cutting_master_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Cutting Master…</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
      </div>
      <div class="grid grid-cols-4 gap-3">
        <select v-model="form.raw_material_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Fabric (raw material)…</option>
          <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>
        <input v-model.number="form.fabric_consumed" type="number" step="0.001" min="0.001" required placeholder="Fabric consumed" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.location_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Factory location…</option>
          <option v-for="l in factories" :key="l.id" :value="l.id">{{ l.name }}</option>
        </select>
      </div>
      <div class="grid grid-cols-3 gap-3">
        <input v-model.number="form.bundle_size" type="number" min="1" required placeholder="Bundle size" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model.number="form.planned_quantity" type="number" min="1" required placeholder="Planned quantity" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.inward_subcontract_order_id" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="">Not an Inward Subcontract job</option>
          <option v-for="sc in inwardOrders" :key="sc.id" :value="sc.id">{{ sc.subcontract_no }} — {{ sc.style }}</option>
        </select>
      </div>
      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Save Draft' }}
      </button>
    </form>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <select v-model="statusFilter" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All statuses</option>
        <option value="draft">Draft</option>
        <option value="finalized">Finalized</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Style / Color / Size</th>
          <th class="px-3 py-2">Cut Date</th>
          <th class="px-3 py-2">Planned Qty</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="ticket in store.cutTickets" :key="ticket.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ ticket.style }} / {{ ticket.color }} / {{ ticket.size || '—' }}</td>
          <td class="px-3 py-2">{{ ticket.cut_date }}</td>
          <td class="px-3 py-2">{{ ticket.planned_quantity }}</td>
          <td class="px-3 py-2 capitalize">{{ ticket.status }}</td>
          <td class="px-3 py-2 text-right">
            <button
              v-if="ticket.status === 'draft' && auth.can('production.cutting.create')"
              type="button"
              class="text-brand-600 hover:underline"
              @click="handleFinalize(ticket)"
            >
              Finalize
            </button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.cutTickets.length === 0">
          <td colspan="5" class="px-3 py-6 text-center text-gray-400">No cut tickets yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
