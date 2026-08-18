<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useRawMaterialStore } from '../store'
import rawMaterialApi from '../api'
import locationApi from '@/modules/location/api'
import partyApi from '@/modules/party/api'

// PRD v2 §3.19 — Raw Material Stock: ledger view + Purchase Order
// create/receive. Folded into one page rather than a separate PO route
// since shared/layouts/navConfig.js only has one Raw Material Stock nav
// entry (see Modules/RawMaterial/README.md for the ledger mechanics).
const store = useRawMaterialStore()
const auth = useAuthStore()

const materials = ref([])
const locations = ref([])
const suppliers = ref([])

const showPoForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const receivingId = ref(null)
const receiveQuantities = reactive({})

function blankPoForm() {
  return {
    supplier_id: '',
    location_id: '',
    order_date: new Date().toISOString().slice(0, 10),
    items: [{ raw_material_id: '', quantity_ordered: 1, unit_price: 0 }],
  }
}
const poForm = reactive(blankPoForm())

function addPoItem() {
  poForm.items.push({ raw_material_id: '', quantity_ordered: 1, unit_price: 0 })
}
function removePoItem(index) {
  if (poForm.items.length > 1) poForm.items.splice(index, 1)
}

async function load() {
  const [matRes, locRes, supRes] = await Promise.all([
    rawMaterialApi.list({ per_page: 100, with_stock: 1 }),
    locationApi.list({ per_page: 100 }),
    partyApi.list({ type: 'supplier', per_page: 100 }),
  ])
  materials.value = matRes.data.data
  locations.value = locRes.data.data
  suppliers.value = supRes.data.data
  await Promise.all([store.fetchMovements(), store.fetchPurchaseOrders()])
}

onMounted(load)

function materialName(id) {
  return materials.value.find((m) => m.id === id)?.name || `#${id}`
}
function locationName(id) {
  return locations.value.find((l) => l.id === id)?.name || `#${id}`
}

async function handleCreatePo() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await store.createPurchaseOrder(poForm)
    Object.assign(poForm, blankPoForm())
    poForm.items = [{ raw_material_id: '', quantity_ordered: 1, unit_price: 0 }]
    showPoForm.value = false
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this purchase order.'
  } finally {
    isSubmitting.value = false
  }
}

function startReceive(po) {
  receivingId.value = po.id
  po.items.forEach((item) => {
    receiveQuantities[item.id] = Number(item.quantity_ordered) - Number(item.quantity_received || 0)
  })
}

async function confirmReceive(po) {
  errorMessage.value = ''
  try {
    const items = po.items
      .filter((item) => receiveQuantities[item.id] > 0)
      .map((item) => ({ item_id: item.id, quantity: receiveQuantities[item.id] }))
    await store.receivePurchaseOrder(po.id, { items })
    receivingId.value = null
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not receive this purchase order.'
  }
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Raw Material Stock &amp; Purchase Orders</h1>
      <router-link :to="{ name: 'raw-materials.index' }" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        Back to Materials
      </router-link>
    </div>

    <p v-if="errorMessage" role="alert" class="mb-3 text-sm text-red-600">{{ errorMessage }}</p>

    <section class="mb-8">
      <h2 class="mb-2 text-sm font-semibold text-gray-900">Reorder Alerts</h2>
      <ul class="mb-4 space-y-1 text-sm">
        <li v-for="m in materials.filter((x) => x.current_stock !== undefined && x.current_stock <= x.reorder_level)" :key="m.id" class="text-red-600">
          {{ m.name }} is at or below its reorder level ({{ m.reorder_level }} {{ m.unit }})
        </li>
      </ul>

      <h2 class="mb-2 text-sm font-semibold text-gray-900">Stock Movements</h2>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Material</th>
            <th class="px-3 py-2">Location</th>
            <th class="px-3 py-2">Type</th>
            <th class="px-3 py-2">Quantity</th>
            <th class="px-3 py-2">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in store.movements" :key="m.id" class="border-b border-gray-100">
            <td class="px-3 py-2">{{ materialName(m.raw_material_id) }}</td>
            <td class="px-3 py-2">{{ locationName(m.location_id) }}</td>
            <td class="px-3 py-2 capitalize">{{ m.type }}</td>
            <td class="px-3 py-2" :class="Number(m.quantity) < 0 ? 'text-red-600' : 'text-green-700'">{{ m.quantity }}</td>
            <td class="px-3 py-2">{{ m.occurred_on }}</td>
          </tr>
          <tr v-if="store.movements.length === 0">
            <td colspan="5" class="px-3 py-6 text-center text-gray-400">No movements yet.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section>
      <div class="mb-2 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Purchase Orders</h2>
        <button
          v-if="auth.can('raw-material.purchase-order.manage')"
          type="button"
          class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="showPoForm = !showPoForm"
        >
          {{ showPoForm ? 'Cancel' : 'New Purchase Order' }}
        </button>
      </div>

      <form v-if="showPoForm" class="mb-4 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreatePo">
        <div class="grid grid-cols-3 gap-3">
          <select v-model="poForm.supplier_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
            <option value="" disabled>Supplier…</option>
            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <select v-model="poForm.location_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
            <option value="" disabled>Receiving location…</option>
            <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
          </select>
          <input v-model="poForm.order_date" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        </div>

        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase text-gray-500">
              <th class="px-2 py-1">Material</th>
              <th class="px-2 py-1">Qty</th>
              <th class="px-2 py-1">Unit Price</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in poForm.items" :key="index">
              <td class="px-2 py-1">
                <select v-model="item.raw_material_id" required class="w-full rounded border border-gray-300 px-2 py-1">
                  <option value="" disabled>Select…</option>
                  <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
              </td>
              <td class="px-2 py-1"><input v-model.number="item.quantity_ordered" type="number" step="0.001" min="0.001" required class="w-24 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model.number="item.unit_price" type="number" step="0.01" min="0" required class="w-24 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><button type="button" class="text-red-600" @click="removePoItem(index)">✕</button></td>
            </tr>
          </tbody>
        </table>
        <button type="button" class="text-sm text-brand-600 hover:underline" @click="addPoItem">+ Add Item</button>

        <div>
          <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
            {{ isSubmitting ? 'Saving…' : 'Create Purchase Order' }}
          </button>
        </div>
      </form>

      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">PO No</th>
            <th class="px-3 py-2">Status</th>
            <th class="px-3 py-2">Order Date</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <template v-for="po in store.purchaseOrders" :key="po.id">
            <tr class="border-b border-gray-100">
              <td class="px-3 py-2 font-mono">{{ po.po_no }}</td>
              <td class="px-3 py-2 capitalize">{{ po.status?.replace('_', ' ') }}</td>
              <td class="px-3 py-2">{{ po.order_date }}</td>
              <td class="px-3 py-2 text-right">
                <button
                  v-if="po.status !== 'received' && auth.can('raw-material.purchase-order.manage')"
                  type="button"
                  class="text-brand-600 hover:underline"
                  @click="receivingId === po.id ? (receivingId = null) : startReceive(po)"
                >
                  {{ receivingId === po.id ? 'Cancel' : 'Receive' }}
                </button>
              </td>
            </tr>
            <tr v-if="receivingId === po.id" class="border-b border-gray-100 bg-gray-50">
              <td colspan="4" class="px-3 py-3">
                <table class="w-full text-sm">
                  <tr v-for="item in po.items" :key="item.id">
                    <td class="py-1">{{ materialName(item.raw_material_id) }}</td>
                    <td class="py-1">Ordered: {{ item.quantity_ordered }}, Received: {{ item.quantity_received }}</td>
                    <td class="py-1">
                      <input v-model.number="receiveQuantities[item.id]" type="number" step="0.001" min="0" class="w-24 rounded border border-gray-300 px-2 py-1" />
                    </td>
                  </tr>
                  <tr>
                    <td colspan="3" class="pt-2">
                      <button type="button" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white" @click="confirmReceive(po)">
                        Confirm Receipt
                      </button>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </template>
          <tr v-if="store.purchaseOrders.length === 0">
            <td colspan="4" class="px-3 py-6 text-center text-gray-400">No purchase orders yet.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>
