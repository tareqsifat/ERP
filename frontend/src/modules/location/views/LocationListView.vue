<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useLocationStore } from '../store'

// PRD v2 §3.21 / §4.1 — Locations List. Simple master data (name/type/
// address), so create is an inline form rather than a separate route.
const store = useLocationStore()
const auth = useAuthStore()

const typeFilter = ref('')
const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

function blankForm() {
  return { name: '', type: 'factory', address: '' }
}
const form = reactive(blankForm())

async function load() {
  await store.fetchList({ type: typeFilter.value || undefined })
}

onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await store.createLocation(form)
    Object.assign(form, blankForm())
    showForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this location.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleDelete(location) {
  if (!confirm(`Delete location "${location.name}"?`)) return
  await store.removeLocation(location.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Locations</h1>
      <button
        v-if="auth.can('location.create')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'Add Location' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 grid grid-cols-4 gap-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <input v-model="form.name" required placeholder="Name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <select v-model="form.type" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="factory">Factory</option>
        <option value="store">Store</option>
        <option value="showroom">Showroom</option>
      </select>
      <input v-model="form.address" placeholder="Address" class="col-span-2 rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <p v-if="errorMessage" role="alert" class="col-span-4 text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Save' }}
      </button>
    </form>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <select v-model="typeFilter" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All types</option>
        <option value="factory">Factory</option>
        <option value="store">Store</option>
        <option value="showroom">Showroom</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Name</th>
          <th class="px-3 py-2">Type</th>
          <th class="px-3 py-2">Address</th>
          <th class="px-3 py-2">Active</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="location in store.items" :key="location.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-medium">{{ location.name }}</td>
          <td class="px-3 py-2 capitalize">{{ location.type }}</td>
          <td class="px-3 py-2">{{ location.address }}</td>
          <td class="px-3 py-2">{{ location.is_active ? 'Yes' : 'No' }}</td>
          <td class="px-3 py-2 text-right">
            <button
              v-if="auth.can('location.delete')"
              type="button"
              class="text-red-600 hover:underline"
              @click="handleDelete(location)"
            >
              Delete
            </button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="5" class="px-3 py-6 text-center text-gray-400">No locations yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
