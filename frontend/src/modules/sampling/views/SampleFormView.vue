<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSampleStore } from '../store'
import sampleApi from '../api'
import orderApi from '@/modules/order/api'

// PRD v1 §3.4 "Add New Sample".
const route = useRoute()
const router = useRouter()
const store = useSampleStore()

const isEdit = computed(() => !!route.params.id)
const errorMessage = ref('')
const isSubmitting = ref(false)
const orders = ref([])

const form = reactive({
  order_id: '',
  consignee: '',
  style_number: '',
  item: '',
  sample_type: 'proto',
  quantity: 1,
  status: 'requested',
})

onMounted(async () => {
  const { data } = await orderApi.list({ per_page: 100 })
  orders.value = data.data

  if (isEdit.value) {
    const { data: sampleData } = await sampleApi.get(route.params.id)
    Object.assign(form, {
      order_id: sampleData.data.order_id,
      consignee: sampleData.data.consignee,
      style_number: sampleData.data.style_number,
      item: sampleData.data.item,
      sample_type: sampleData.data.sample_type,
      quantity: sampleData.data.quantity,
      status: sampleData.data.status,
    })
  }
})

async function handleSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    if (isEdit.value) {
      await store.updateSample(route.params.id, form)
    } else {
      await store.createSample(form)
    }
    router.push({ name: 'samples.index' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save this sample.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-lg p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">
      {{ isEdit ? 'Edit Sample' : 'Add New Sample' }}
    </h1>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Order</label>
        <select v-model="form.order_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
          <option value="" disabled>Select an order…</option>
          <option v-for="order in orders" :key="order.id" :value="order.id">{{ order.order_no }}</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Consignee</label>
          <input v-model="form.consignee" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Style Number</label>
          <input v-model="form.style_number" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Item</label>
          <input v-model="form.item" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Sample Type</label>
          <select v-model="form.sample_type" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="proto">Proto</option>
            <option value="fit">Fit</option>
            <option value="pp">PP (Pre-Production)</option>
            <option value="size_set">Size Set</option>
            <option value="shipment">Shipment</option>
            <option value="salesman">Salesman</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
          <input v-model.number="form.quantity" type="number" min="1" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
          <select v-model="form.status" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="requested">Requested</option>
            <option value="sent">Sent</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
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
