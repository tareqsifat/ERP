<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBudgetStore } from '../store'
import budgetApi from '../api'
import orderApi from '@/modules/order/api'

// PRD v1 §3.3 "Add New Budget". total_value is displayed as a live
// client-side estimate only — the API always recomputes it server-side
// (backend Modules/Budgeting/README.md).
const route = useRoute()
const router = useRouter()
const store = useBudgetStore()

const isEdit = computed(() => !!route.params.id)
const errorMessage = ref('')
const isSubmitting = ref(false)
const orders = ref([])

const form = reactive({
  order_id: '',
  style: '',
  budgeted_quantity: '',
  average_unit_price: '',
  status: 'draft',
})

const estimatedTotal = computed(
  () => (Number(form.budgeted_quantity) || 0) * (Number(form.average_unit_price) || 0),
)

onMounted(async () => {
  const { data } = await orderApi.list({ per_page: 100 })
  orders.value = data.data

  if (isEdit.value) {
    const { data: budgetData } = await budgetApi.get(route.params.id)
    Object.assign(form, {
      order_id: budgetData.data.order_id,
      style: budgetData.data.style,
      budgeted_quantity: budgetData.data.budgeted_quantity,
      average_unit_price: budgetData.data.average_unit_price,
      status: budgetData.data.status,
    })
  }
})

async function handleSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    if (isEdit.value) {
      await store.updateBudget(route.params.id, form)
    } else {
      await store.createBudget(form)
    }
    router.push({ name: 'budgets.index' })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save this budget.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-lg p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">
      {{ isEdit ? 'Edit Budget' : 'Add New Budget' }}
    </h1>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Order</label>
        <select v-model="form.order_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
          <option value="" disabled>Select an order…</option>
          <option v-for="order in orders" :key="order.id" :value="order.id">{{ order.order_no }}</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Style</label>
        <input v-model="form.style" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Budgeted Quantity</label>
          <input v-model.number="form.budgeted_quantity" type="number" min="1" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Average Unit Price</label>
          <input v-model.number="form.average_unit_price" type="number" step="0.01" min="0" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <p class="text-sm text-gray-600">Estimated Total: {{ estimatedTotal.toFixed(2) }}</p>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
        <select v-model="form.status" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
          <option value="draft">Draft</option>
          <option value="approved">Approved</option>
        </select>
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
