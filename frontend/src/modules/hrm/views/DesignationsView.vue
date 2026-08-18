<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useHrmStore } from '../store'
import hrmApi from '../api'

// PRD v1 §3.11/§4.10 — Designations master list.
const store = useHrmStore()
const auth = useAuthStore()

const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const name = ref('')

async function load() {
  await store.fetchDesignations()
}
onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await hrmApi.designations.create({ name: name.value })
    name.value = ''
    showForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this designation.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleRemove(designation) {
  if (!confirm(`Delete designation "${designation.name}"?`)) return
  await hrmApi.designations.remove(designation.id)
  await load()
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Designations</h1>
      <button
        v-if="auth.can('hrm.designation.manage')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'New Designation' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 flex items-end gap-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <input v-model="name" required placeholder="Designation name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Create' }}
      </button>
    </form>
    <p v-if="errorMessage" role="alert" class="mb-4 text-sm text-red-600">{{ errorMessage }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Designation</th>
          <th class="px-3 py-2">Description</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="designation in store.designations" :key="designation.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ designation.name }}</td>
          <td class="px-3 py-2">{{ designation.description || '—' }}</td>
          <td class="px-3 py-2 text-right">
            <button v-if="auth.can('hrm.designation.manage')" type="button" class="text-red-600 hover:underline" @click="handleRemove(designation)">
              Delete
            </button>
          </td>
        </tr>
        <tr v-if="store.designations.length === 0">
          <td colspan="3" class="px-3 py-6 text-center text-gray-400">No designations yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
