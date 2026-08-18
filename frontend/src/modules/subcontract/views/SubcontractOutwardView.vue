<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useSubcontractStore } from '../store'
import partyApi from '@/modules/party/api'
import orderApi from '@/modules/order/api'
import rawMaterialApi from '@/modules/raw-material/api'
import locationApi from '@/modules/location/api'
import userApi from '@/modules/user/api'

// PRD v2 §3.23 — Outward Subcontract: create an order, then issue either
// already-cut pieces (by Piece Serial ID — see Production's Piece
// Traceability Lookup to find IDs) or raw material (which creates+
// finalizes a real Cut Ticket server-side), then resolve returns/
// write-offs against it.
const store = useSubcontractStore()
const auth = useAuthStore()

const parties = ref([])
const orders = ref([])
const materials = ref([])
const factories = ref([])
const users = ref([])

const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const activeAction = reactive({ orderId: null, type: null }) // type: 'issue-pieces' | 'issue-raw-material' | 'return-pieces'

function blankForm() {
  return {
    direction: 'outward', party_id: '', order_id: '', style: '', color: '', size: '',
    rate: '', rate_unit: 'piece', quantity_expected: 1,
    raw_material_id: '', raw_material_quantity: '', location_id: '', expected_date: '', remarks: '',
  }
}
const form = reactive(blankForm())

function blankIssuePieces() {
  return { piece_serial_ids: '' }
}
function blankIssueRawMaterial() {
  return { cut_date: new Date().toISOString().slice(0, 10), cutting_master_id: '', bundle_size: 20, quantity: 1 }
}
function blankReturn() {
  return { returned_piece_serial_ids: '', written_off_piece_serial_ids: '' }
}
const actionForm = reactive(blankIssuePieces())

async function load() {
  const [partyRes, orderRes, matRes, locRes, userRes] = await Promise.all([
    partyApi.list({ type: 'subcontractor', per_page: 100 }),
    orderApi.list({ per_page: 100 }),
    rawMaterialApi.list({ per_page: 100 }),
    locationApi.list({ per_page: 100 }),
    userApi.list({ per_page: 100 }),
  ])
  parties.value = partyRes.data.data
  orders.value = orderRes.data.data
  materials.value = matRes.data.data
  factories.value = locRes.data.data
  users.value = userRes.data.data
  await store.fetchList({ direction: 'outward' })
}

onMounted(load)

function parseIds(text) {
  return text.split(',').map((s) => s.trim()).filter(Boolean).map(Number)
}

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

function startAction(order, type) {
  activeAction.orderId = order.id
  activeAction.type = type
  errorMessage.value = ''
  Object.assign(actionForm, type === 'issue-pieces' ? blankIssuePieces() : type === 'issue-raw-material' ? blankIssueRawMaterial() : blankReturn())
}

function cancelAction() {
  activeAction.orderId = null
  activeAction.type = null
}

async function submitAction(order) {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    if (activeAction.type === 'issue-pieces') {
      await store.issuePieces(order.id, { piece_serial_ids: parseIds(actionForm.piece_serial_ids) })
    } else if (activeAction.type === 'issue-raw-material') {
      await store.issueRawMaterial(order.id, actionForm)
    } else if (activeAction.type === 'return-pieces') {
      await store.returnPieces(order.id, {
        returned_piece_serial_ids: parseIds(actionForm.returned_piece_serial_ids),
        written_off_piece_serial_ids: parseIds(actionForm.written_off_piece_serial_ids),
      })
    }
    await store.fetchList({ direction: 'outward' })
    cancelAction()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'This action could not be completed.'
  } finally {
    isSubmitting.value = false
  }
}

function partyName(id) {
  return parties.value.find((p) => p.id === id)?.name || `#${id}`
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Outward Subcontract</h1>
      <button
        v-if="auth.can('subcontract.outward.manage')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Outward Order' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <div class="grid grid-cols-3 gap-3">
        <select v-model="form.party_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Subcontractor…</option>
          <option v-for="p in parties" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <select v-model="form.order_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Order…</option>
          <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.order_no || `Order #${o.id}` }}</option>
        </select>
        <input v-model="form.style" required placeholder="Style" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div class="grid grid-cols-4 gap-3">
        <input v-model="form.color" placeholder="Color" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.size" placeholder="Size" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model.number="form.rate" type="number" step="0.01" min="0.01" required placeholder="Rate" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.rate_unit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="piece">per piece</option>
          <option value="dozen">per dozen</option>
        </select>
      </div>
      <div class="grid grid-cols-4 gap-3">
        <input v-model.number="form.quantity_expected" type="number" min="1" required placeholder="Quantity expected" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.raw_material_id" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="">No raw material (issuing pieces instead)</option>
          <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>
        <input v-model.number="form.raw_material_quantity" type="number" step="0.001" placeholder="Raw material qty" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.location_id" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="">Location (for raw material issue)</option>
          <option v-for="l in factories" :key="l.id" :value="l.id">{{ l.name }}</option>
        </select>
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
          <th class="px-3 py-2">Subcontractor</th>
          <th class="px-3 py-2">Style/Color/Size</th>
          <th class="px-3 py-2">Rate</th>
          <th class="px-3 py-2">Qty Expected</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <template v-for="order in store.outwardOrders" :key="order.id">
          <tr class="border-b border-gray-100">
            <td class="px-3 py-2 font-mono">{{ order.subcontract_no }}</td>
            <td class="px-3 py-2">{{ partyName(order.party_id) }}</td>
            <td class="px-3 py-2">{{ order.style }} / {{ order.color || '—' }} / {{ order.size || '—' }}</td>
            <td class="px-3 py-2">{{ order.rate }} / {{ order.rate_unit }}</td>
            <td class="px-3 py-2">{{ order.quantity_expected }}</td>
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
              <div v-if="order.status !== 'closed' && auth.can('subcontract.outward.manage')" class="flex justify-end gap-2 text-xs">
                <button type="button" class="text-brand-600 hover:underline" @click="startAction(order, 'issue-pieces')">Issue Pieces</button>
                <button type="button" class="text-brand-600 hover:underline" @click="startAction(order, 'issue-raw-material')">Issue Raw Material</button>
                <button type="button" class="text-brand-600 hover:underline" @click="startAction(order, 'return-pieces')">Return/Write-off</button>
              </div>
            </td>
          </tr>
          <tr v-if="activeAction.orderId === order.id" class="border-b border-gray-100 bg-gray-50">
            <td colspan="7" class="px-3 py-3">
              <form class="space-y-2" @submit.prevent="submitAction(order)">
                <template v-if="activeAction.type === 'issue-pieces'">
                  <input v-model="actionForm.piece_serial_ids" required placeholder="Piece Serial IDs, comma-separated" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
                </template>
                <template v-else-if="activeAction.type === 'issue-raw-material'">
                  <div class="grid grid-cols-4 gap-2">
                    <input v-model="actionForm.cut_date" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
                    <select v-model="actionForm.cutting_master_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
                      <option value="" disabled>Cutting Master…</option>
                      <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <input v-model.number="actionForm.bundle_size" type="number" min="1" required placeholder="Bundle size" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
                    <input v-model.number="actionForm.quantity" type="number" min="1" required placeholder="Quantity" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
                  </div>
                </template>
                <template v-else-if="activeAction.type === 'return-pieces'">
                  <input v-model="actionForm.returned_piece_serial_ids" placeholder="Returned Piece Serial IDs, comma-separated" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
                  <input v-model="actionForm.written_off_piece_serial_ids" placeholder="Written-off Piece Serial IDs, comma-separated" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
                </template>
                <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
                <div class="flex gap-2">
                  <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white disabled:opacity-60">
                    {{ isSubmitting ? 'Saving…' : 'Submit' }}
                  </button>
                  <button type="button" class="rounded border border-gray-300 px-3 py-1.5 text-sm" @click="cancelAction">Cancel</button>
                </div>
              </form>
            </td>
          </tr>
        </template>
        <tr v-if="!store.loading && store.outwardOrders.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No outward subcontract orders yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
