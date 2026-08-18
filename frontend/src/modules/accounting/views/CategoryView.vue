<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useAccountingStore } from '../store'
import accountingApi from '../api'

// PRD v1 §3.9/§4.8 — Income/Expense category masters. One component,
// two routes (income.index / expenses.index) with `kind` as a route
// prop — mirrors the backend's single AccountingCategory table with a
// `kind` discriminator.
const props = defineProps({ kind: { type: String, required: true } })

const store = useAccountingStore()
const auth = useAuthStore()

const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const name = ref('')
const description = ref('')

async function load() {
  await store.fetchCategories({ kind: props.kind })
}
onMounted(load)
watch(() => props.kind, load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await accountingApi.categories.create({ kind: props.kind, name: name.value, description: description.value || null })
    name.value = ''
    description.value = ''
    showForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this category.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleRemove(category) {
  if (!confirm(`Delete category "${category.name}"?`)) return
  await accountingApi.categories.remove(category.id)
  await load()
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900 capitalize">{{ kind }} Categories</h1>
      <button
        v-if="auth.can('accounting.voucher.create')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Category' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 flex items-end gap-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <input v-model="name" required placeholder="Name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <input v-model="description" placeholder="Description (optional)" class="flex-1 rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Create' }}
      </button>
    </form>
    <p v-if="errorMessage" role="alert" class="mb-4 text-sm text-red-600">{{ errorMessage }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Name</th>
          <th class="px-3 py-2">Description</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="category in store.categories" :key="category.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ category.name }}</td>
          <td class="px-3 py-2">{{ category.description || '—' }}</td>
          <td class="px-3 py-2">{{ category.is_active ? 'Active' : 'Inactive' }}</td>
          <td class="px-3 py-2 text-right">
            <button v-if="auth.can('accounting.voucher.create')" type="button" class="text-red-600 hover:underline" @click="handleRemove(category)">
              Delete
            </button>
          </td>
        </tr>
        <tr v-if="store.categories.length === 0">
          <td colspan="4" class="px-3 py-6 text-center text-gray-400">No categories yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
