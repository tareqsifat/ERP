<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useHrmStore } from '../store'
import hrmApi from '../api'

// PRD v1 §3.11/§4.10/§5.5 — Employee directory.
const store = useHrmStore()
const auth = useAuthStore()

const designations = ref([])
const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

function blankForm() {
  return { full_name: '', phone: '', gender: '', employment_type: '', joining_date: new Date().toISOString().slice(0, 10), designation_id: '', salary: '' }
}
const form = reactive(blankForm())

async function load() {
  const desigRes = await hrmApi.designations.list()
  designations.value = desigRes.data.data
  await store.fetchEmployees({ per_page: 100 })
}
onMounted(load)

async function handleCreate() {
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    await hrmApi.employees.create(form)
    Object.assign(form, blankForm())
    showForm.value = false
    await load()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this employee.'
  } finally {
    isSubmitting.value = false
  }
}

function designationName(id) {
  return designations.value.find((d) => d.id === id)?.name || `#${id}`
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Employees</h1>
      <button
        v-if="auth.can('hrm.employee.manage')"
        type="button"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="showForm = !showForm"
      >
        {{ showForm ? 'Cancel' : 'Add Employee' }}
      </button>
    </div>

    <form v-if="showForm" class="mb-6 space-y-3 rounded border border-gray-200 bg-white p-4" @submit.prevent="handleCreate">
      <div class="grid grid-cols-3 gap-3">
        <input v-model="form.full_name" required placeholder="Full Name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.phone" placeholder="Phone" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.gender" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="">Gender…</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="grid grid-cols-4 gap-3">
        <input v-model="form.employment_type" placeholder="Employment Type" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="form.joining_date" type="date" required class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="form.designation_id" required class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="" disabled>Designation…</option>
          <option v-for="d in designations" :key="d.id" :value="d.id">{{ d.name }}</option>
        </select>
        <input v-model.number="form.salary" type="number" step="0.01" min="0.01" required placeholder="Salary" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <p v-if="errorMessage" role="alert" class="text-sm text-red-600">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Save' }}
      </button>
    </form>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Join Date</th>
          <th class="px-3 py-2">Full Name</th>
          <th class="px-3 py-2">Phone</th>
          <th class="px-3 py-2">Designation</th>
          <th class="px-3 py-2">Salary</th>
          <th class="px-3 py-2">Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="employee in store.employees" :key="employee.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ employee.joining_date }}</td>
          <td class="px-3 py-2">{{ employee.full_name }}</td>
          <td class="px-3 py-2">{{ employee.phone || '—' }}</td>
          <td class="px-3 py-2">{{ designationName(employee.designation_id) }}</td>
          <td class="px-3 py-2 font-mono">{{ employee.salary }}</td>
          <td class="px-3 py-2">
            <span class="rounded px-2 py-0.5 text-xs capitalize" :class="employee.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
              {{ employee.status }}
            </span>
          </td>
        </tr>
        <tr v-if="!store.loading && store.employees.length === 0">
          <td colspan="6" class="px-3 py-6 text-center text-gray-400">No employees yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
