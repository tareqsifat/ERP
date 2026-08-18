<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useShipmentStore } from '../store'
import shipmentApi from '../api'
import orderApi from '@/modules/order/api'

// PRD v1 §3.6 "Add New Shipment". invoice_no is auto-generated
// server-side (backend Modules/Shipment/README.md) — not entered here.
// order_id is not editable once created (see backend
// UpdateShipmentRequest), so the order select is disabled while editing.
const route = useRoute()
const router = useRouter()
const store = useShipmentStore()

const isEdit = computed(() => !!route.params.id)
const errorMessage = ref('')
const isSubmitting = ref(false)
const orders = ref([])
const invoiceNo = ref('')

const form = reactive({
  order_id: '',
  total_quantity: '',
  total_cbm: '',
  shipment_date: '',
  status: 'draft',
  remarks: '',
})

onMounted(async () => {
  const { data } = await orderApi.list({ per_page: 100 })
  orders.value = data.data

  if (isEdit.value) {
    const { data: shipmentData } = await shipmentApi.get(route.params.id)
    invoiceNo.value = shipmentData.data.invoice_no
    Object.assign(form, {
      order_id: shipmentData.data.order_id,
      total_quantity: shipmentData.data.total_quantity,
      total_cbm: shipmentData.data.total_cbm,
      shipment_date: shipmentData.data.shipment_date,
      status: shipmentData.data.status,
      remarks: shipmentData.data.remarks,
    })
  }
})

async function handleSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    if (isEdit.value) {
      // order_id is intentionally excluded — not accepted by the
      // backend's UpdateShipmentRequest.
      const { order_id, ...updatable } = form
      await store.updateShipment(route.params.id, updatable)
    } else {
      await store.createShipment(form)
    }
    router.push({ name: 'shipments.index' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save this shipment.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-lg p-6">
    <h1 class="mb-1 text-lg font-semibold text-gray-900">
      {{ isEdit ? 'Edit Shipment' : 'Add New Shipment' }}
    </h1>
    <p v-if="isEdit" class="mb-4 font-mono text-sm text-gray-500">{{ invoiceNo }}</p>
    <p v-else class="mb-4 text-sm text-gray-500">Invoice number is generated automatically on save.</p>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Order</label>
        <select
          v-model="form.order_id"
          required
          :disabled="isEdit"
          class="w-full rounded border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"
        >
          <option value="" disabled>Select an order…</option>
          <option v-for="order in orders" :key="order.id" :value="order.id">{{ order.order_no }}</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Total Quantity</label>
          <input v-model.number="form.total_quantity" type="number" min="1" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Total CBM</label>
          <input v-model.number="form.total_cbm" type="number" step="0.001" min="0" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Shipment Date</label>
          <input v-model="form.shipment_date" type="date" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
          <select v-model="form.status" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="draft">Draft</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
          </select>
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
        <textarea v-model="form.remarks" rows="2" class="w-full rounded border border-gray-300 px-3 py-2 text-sm"></textarea>
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
