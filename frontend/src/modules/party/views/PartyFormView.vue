<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePartyStore } from '../store'
import partyApi from '../api'

// PRD v1 §5.3 "Add New Buyer / Supplier" — extended by PRD v2 §4.9 with
// the Subcontractor type. No login/password field here on purpose — see
// backend Modules/Party/README.md "Known gaps".
const route = useRoute()
const router = useRouter()
const store = usePartyStore()

const isEdit = computed(() => !!route.params.id)
const errorMessage = ref('')
const isSubmitting = ref(false)

const form = reactive({
  name: '',
  type: route.query.type || 'buyer',
  email: '',
  phone: '',
  address: '',
  country: '',
  opening_balance_type: '',
  opening_balance: '',
  remarks: '',
})

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await partyApi.get(route.params.id)
    Object.assign(form, {
      name: data.data.name,
      type: data.data.type,
      email: data.data.email,
      phone: data.data.phone,
      address: data.data.address,
      country: data.data.country,
      opening_balance_type: data.data.opening_balance_type,
      opening_balance: data.data.opening_balance,
      remarks: data.data.remarks,
    })
  }
})

async function handleSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    if (isEdit.value) {
      await store.updateParty(route.params.id, form)
    } else {
      await store.createParty(form)
    }
    // form.type is 'buyer'/'supplier'/'subcontractor' -> route names
    // 'parties.buyers'/'parties.suppliers'/'parties.subcontractors'.
    router.push({ name: `parties.${form.type}s` })
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save this party.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-xl p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">
      {{ isEdit ? 'Edit Party' : 'Add New Party' }}
    </h1>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
        <select v-model="form.type" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">
          <option value="buyer">Buyer</option>
          <option value="supplier">Supplier</option>
          <option value="subcontractor">Subcontractor</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Party Name</label>
        <input v-model="form.name" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
          <input v-model="form.email" type="email" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
          <input v-model="form.phone" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Address</label>
        <textarea v-model="form.address" rows="2" class="w-full rounded border border-gray-300 px-3 py-2 text-sm"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Country</label>
          <input v-model="form.country" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Opening Balance</label>
          <div class="flex gap-2">
            <select v-model="form.opening_balance_type" class="rounded border border-gray-300 px-2 py-2 text-sm">
              <option value="">—</option>
              <option value="debit">Debit</option>
              <option value="credit">Credit</option>
            </select>
            <input v-model="form.opening_balance" type="number" step="0.01" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
          </div>
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
