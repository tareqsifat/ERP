<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBookingStore } from '../store'
import bookingApi from '../api'
import orderApi from '@/modules/order/api'
import userApi from '@/modules/user/api'

// PRD v1 §4.4 "Add New Booking".
const route = useRoute()
const router = useRouter()
const store = useBookingStore()

const isEdit = computed(() => !!route.params.id)
const errorMessage = ref('')
const isSubmitting = ref(false)
const orders = ref([])
const preparers = ref([])

function blankLineItem() {
  return { style: '', color: '', quantity: 1, unit_price: 0, dzn_quantity: '', gray_fabric_consumption_kg: '' }
}

const form = reactive({
  order_id: '',
  preparer_id: '',
  booking_date: new Date().toISOString().slice(0, 10),
  composition: '',
  process_loss_percent: '',
  other_fabrics: '',
  rib: '',
  collar: '',
  status: 'draft',
  line_items: [blankLineItem()],
})

function addLineItem() {
  form.line_items.push(blankLineItem())
}

function removeLineItem(index) {
  if (form.line_items.length > 1) form.line_items.splice(index, 1)
}

onMounted(async () => {
  const [orderRes, userRes] = await Promise.all([orderApi.list({ per_page: 100 }), userApi.list({ per_page: 100 })])
  orders.value = orderRes.data.data
  preparers.value = userRes.data.data

  if (isEdit.value) {
    const { data } = await bookingApi.get(route.params.id)
    const booking = data.data
    Object.assign(form, {
      order_id: booking.order_id,
      preparer_id: booking.preparer_id,
      booking_date: booking.booking_date,
      composition: booking.composition,
      process_loss_percent: booking.process_loss_percent,
      other_fabrics: booking.other_fabrics,
      rib: booking.rib,
      collar: booking.collar,
      status: booking.status,
      line_items: booking.line_items?.length
        ? booking.line_items.map((li) => ({
            style: li.style,
            color: li.color,
            quantity: li.quantity,
            unit_price: li.unit_price,
            dzn_quantity: li.dzn_quantity,
            gray_fabric_consumption_kg: li.gray_fabric_consumption_kg,
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
      await store.updateBooking(route.params.id, form)
    } else {
      await store.createBooking(form)
    }
    router.push({ name: 'bookings.index' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save this booking.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-4xl p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">
      {{ isEdit ? 'Edit Booking' : 'Add New Booking' }}
    </h1>

    <form class="space-y-6" @submit.prevent="handleSubmit">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Order</label>
          <select v-model="form.order_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="" disabled>Select an order…</option>
            <option v-for="order in orders" :key="order.id" :value="order.id">{{ order.order_no }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Preparer</label>
          <select v-model="form.preparer_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="" disabled>Select a preparer…</option>
            <option v-for="user in preparers" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Booking Date</label>
          <input v-model="form.booking_date" type="date" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Process Loss %</label>
          <input v-model.number="form.process_loss_percent" type="number" step="0.01" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Rib / Collar</label>
          <div class="flex gap-2">
            <input v-model="form.rib" placeholder="Rib" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
            <input v-model="form.collar" placeholder="Collar" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
          </div>
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Composition</label>
        <textarea v-model="form.composition" rows="2" class="w-full rounded border border-gray-300 px-3 py-2 text-sm"></textarea>
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
              <th class="px-2 py-1">Qty</th>
              <th class="px-2 py-1">Unit Price</th>
              <th class="px-2 py-1">DZN Qty</th>
              <th class="px-2 py-1">Gray Fabric (KG)</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in form.line_items" :key="index" class="border-b border-gray-100">
              <td class="px-2 py-1"><input v-model="item.style" required class="w-24 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model="item.color" required class="w-20 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model.number="item.quantity" type="number" min="1" required class="w-16 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model.number="item.unit_price" type="number" step="0.01" min="0" required class="w-20 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model.number="item.dzn_quantity" type="number" step="0.01" class="w-20 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1"><input v-model.number="item.gray_fabric_consumption_kg" type="number" step="0.01" class="w-24 rounded border border-gray-300 px-2 py-1" /></td>
              <td class="px-2 py-1">
                <button type="button" class="text-red-600" @click="removeLineItem(index)">✕</button>
              </td>
            </tr>
          </tbody>
        </table>
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
