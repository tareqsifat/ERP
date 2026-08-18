<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrderStore } from '../store'
import orderApi from '../api'
import partyApi from '@/modules/party/api'
import userApi from '@/modules/user/api'

// PRD v1 §4.3 "Add New Order" — dynamic multi-row line-item table with
// auto-calculated grand total (computed server-side, see backend
// Modules/Order/README.md "Grand total" — this view only displays the
// live client-side estimate while editing, never sends totals to the API).
const route = useRoute()
const router = useRouter()
const store = useOrderStore()

const isEdit = computed(() => !!route.params.id)
const errorMessage = ref('')
const isSubmitting = ref(false)
const buyers = ref([])
const merchandisers = ref([])

function blankLineItem() {
  return { style: '', color: '', item: '', shipment_date: '', quantity: 1, unit_price: 0 }
}

const form = reactive({
  party_id: '',
  merchandiser_id: '',
  title: '',
  fabrication: '',
  gsm: '',
  yarn_count: '',
  shipment_mode: 'sea',
  payment_mode: 'lc',
  season: '',
  year: new Date().getFullYear(),
  pantone: '',
  remarks: '',
  line_items: [blankLineItem()],
})

const estimatedGrandTotal = computed(() =>
  form.line_items.reduce((sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0), 0),
)

function addLineItem() {
  form.line_items.push(blankLineItem())
}

function removeLineItem(index) {
  if (form.line_items.length > 1) form.line_items.splice(index, 1)
}

onMounted(async () => {
  const [buyerRes, userRes] = await Promise.all([
    partyApi.list({ type: 'buyer', per_page: 100 }),
    userApi.list({ per_page: 100 }),
  ])
  buyers.value = buyerRes.data.data
  merchandisers.value = userRes.data.data

  if (isEdit.value) {
    const { data } = await orderApi.get(route.params.id)
    const order = data.data
    Object.assign(form, {
      party_id: order.party_id,
      merchandiser_id: order.merchandiser_id,
      title: order.title,
      fabrication: order.fabrication,
      gsm: order.gsm,
      yarn_count: order.yarn_count,
      shipment_mode: order.shipment_mode,
      payment_mode: order.payment_mode,
      season: order.season,
      year: order.year,
      pantone: order.pantone,
      remarks: order.remarks,
      line_items: order.line_items?.length
        ? order.line_items.map((li) => ({
            style: li.style,
            color: li.color,
            item: li.item,
            shipment_date: li.shipment_date,
            quantity: li.quantity,
            unit_price: li.unit_price,
          }))
        : [blankLineItem()],
    })
  }
})

async function handleSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    if (isEdit.value) {
      await store.updateOrder(route.params.id, form)
    } else {
      await store.createOrder(form)
    }
    router.push({ name: 'orders.index' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save this order.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-4xl p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">
      {{ isEdit ? 'Edit Order' : 'Add New Order' }}
    </h1>

    <form class="space-y-6" @submit.prevent="handleSubmit">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Party (Buyer)</label>
          <select v-model="form.party_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="" disabled>Select a buyer…</option>
            <option v-for="party in buyers" :key="party.id" :value="party.id">{{ party.name }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Merchandiser</label>
          <select v-model="form.merchandiser_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="" disabled>Select a merchandiser…</option>
            <option v-for="user in merchandisers" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Title</label>
          <input v-model="form.title" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Fabrication</label>
          <input v-model="form.fabrication" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">GSM</label>
          <input v-model="form.gsm" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div class="grid grid-cols-4 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Shipment Mode</label>
          <select v-model="form.shipment_mode" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="sea">Sea</option>
            <option value="air">Air</option>
            <option value="sea_air">Sea &amp; Air</option>
            <option value="road">Road</option>
            <option value="courier">Courier</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Payment Mode</label>
          <select v-model="form.payment_mode" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="lc">L/C</option>
            <option value="tt">T/T</option>
            <option value="advance">Advance</option>
            <option value="on_delivery">On Delivery</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Year</label>
          <input v-model="form.year" type="number" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Season</label>
          <input v-model="form.season" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <div class="mb-2 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-900">Line Items</h2>
          <button type="button" class="text-sm text-brand-600 hover:underline" @click="addLineItem">+ Add Row</button>
        </div>
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
              <th class="px-2 py-1">Style</th>
              <th class="px-2 py-1">Color</th>
              <th class="px-2 py-1">Item</th>
              <th class="px-2 py-1">Ship Date</th>
              <th class="px-2 py-1">Qty</th>
              <th class="px-2 py-1">Unit Price</th>
              <th class="px-2 py-1">Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in form.line_items" :key="index" class="border-b border-gray-100">
              <td class="px-2 py-1"><input v-model="item.style" required class="w-24 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model="item.color" required class="w-20 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model="item.item" required class="w-24 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model="item.shipment_date" type="date" class="rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model.number="item.quantity" type="number" min="1" required class="w-16 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model.number="item.unit_price" type="number" step="0.01" min="0" required class="w-20 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1 text-right">{{ ((item.quantity || 0) * (item.unit_price || 0)).toFixed(2) }}</td>
              <td class="px-2 py-1">
                <button type="button" class="text-red-600" @click="removeLineItem(index)">✕</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p class="mt-2 text-right text-sm font-semibold">
          Estimated Grand Total: {{ estimatedGrandTotal.toFixed(2) }}
        </p>
      </div>

      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>

      <div class="flex gap-2">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="rounded bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        >
          {{ isSubmitting ? 'Saving…' : 'Save' }}
        </button>
        <button type="button" class="rounded border border-gray-300 px-4 py-2 text-sm" @click="router.back()">
          Cancel
        </button>
      </div>
    </form>
  </div>
</template>
