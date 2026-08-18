<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useRawMaterialStore } from '../store'

// PRD v2 §3.19 — Raw Material master list. Create is an inline form,
// same pattern as Locations (simple master data, no multi-step wizard).
const store = useRawMaterialStore()
const auth = useAuthStore()

const categoryFilter = ref('')
const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

function blankForm() {
  return { name: '', category: 'fabric', unit: 'kg', reorder_level: 0, unit_cost: 0 }
}
const form = reactive(blankForm())

async function load() {
  await store.fetchList({ category: categoryFilter.value || undefined })
}

onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await store.createMaterial(form)
    Object.assign(form, blankForm())
    showForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this raw material.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleDelete(material) {
  if (!confirm(`Delete raw material "${material.name}"?`)) return
  await store.removeMaterial(material.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Raw Materials</h1>
      <div class="flex gap-2">
        <router-link :to="{ name: 'raw-materials.stock' }" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          View Stock &amp; Purchase Orders
        </router-link>
        <button
          v-if="auth.can('raw-material.create')"
          type="button"
          class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="showForm = !showForm"
        >
          {{ showForm ? 'Cancel' : 'Add Raw Material' }}
        </button>
      </div>
    </div>

    <form v-if="showForm" class="mb-6 grid grid-cols-5 gap-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <input v-model="form.name" required placeholder="Name" class="col-span-2 rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <select v-model="form.category" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="fabric">Fabric</option>
        <option value="trim">Trim</option>
        <option value="packaging">Packaging</option>
        <option value="other">Other</option>
      </select>
      <input v-model="form.unit" required placeholder="Unit (kg, pcs…)" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <input v-model.number="form.reorder_level" type="number" step="0.001" placeholder="Reorder Level" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <p v-if="errorMessage" role="alert" class="col-span-5 text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Save' }}
      </button>
    </form>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <select v-model="categoryFilter" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All categories</option>
        <option value="fabric">Fabric</option>
        <option value="trim">Trim</option>
        <option value="packaging">Packaging</option>
        <option value="other">Other</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Name</th>
          <th class="px-3 py-2">Category</th>
          <th class="px-3 py-2">Unit</th>
          <th class="px-3 py-2">Reorder Level</th>
          <th class="px-3 py-2">Unit Cost</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="material in store.items" :key="material.id" class="border-b border-gray-100">
          <td class="px-3 py-2 font-medium">{{ material.name }}</td>
          <td class="px-3 py-2 capitalize">{{ material.category }}</td>
          <td class="px-3 py-2">{{ material.unit }}</td>
          <td class="px-3 py-2">{{ material.reorder_level }}</td>
          <td class="px-3 py-2">{{ material.unit_cost }}</td>
          <td class="px-3 py-2 text-right">
            <button
              v-if="auth.can('raw-material.delete')"
              type="button"
              class="text-red-600 hover:underline"
              @click="handleDelete(material)"
            >
              Delete
            </button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="6" class="px-3 py-6 text-center text-gray-400">No raw materials yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
