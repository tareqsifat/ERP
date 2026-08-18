<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useProductionStore } from '../store'
import productionApi from '../api'

// PRD v2 §3.22 / §4.7 — Machine/Line register. One page for both, same
// as the PRD's single "Machine Register" nav entry — lines are the
// grouping a machine gets assigned into, not a separate workflow.
const store = useProductionStore()
const auth = useAuthStore()

const showLineForm = ref(false)
const showMachineForm = ref(false)
const errorMessage = ref('')

function blankLineForm() {
  return { name: '', capacity: null }
}
function blankMachineForm() {
  return { tag: '', type: '', line_id: '' }
}
const lineForm = reactive(blankLineForm())
const machineForm = reactive(blankMachineForm())

async function load() {
  await Promise.all([store.fetchLines(), store.fetchMachines()])
}
onMounted(load)

async function handleCreateLine() {
  errorMessage.value = ''
  try {
    await productionApi.lines.create(lineForm)
    Object.assign(lineForm, blankLineForm())
    showLineForm.value = false
    await store.fetchLines()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this line.'
  }
}

async function handleCreateMachine() {
  errorMessage.value = ''
  try {
    await productionApi.machines.create(machineForm)
    Object.assign(machineForm, blankMachineForm())
    showMachineForm.value = false
    await store.fetchMachines()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not create this machine.'
  }
}

function lineName(id) {
  return store.lines.find((l) => l.id === id)?.name || '—'
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Machine &amp; Line Register</h1>
    <p v-if="errorMessage" role="alert" class="mb-3 text-sm text-red-600">{{ errorMessage }}</p>

    <section class="mb-8">
      <div class="mb-2 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Lines</h2>
        <button
          v-if="auth.can('machine.create')"
          type="button"
          class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="showLineForm = !showLineForm"
        >
          {{ showLineForm ? 'Cancel' : 'Add Line' }}
        </button>
      </div>
      <form v-if="showLineForm" class="mb-3 flex gap-3" @submit.prevent="handleCreateLine">
        <input v-model="lineForm.name" required placeholder="Line name" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model.number="lineForm.capacity" type="number" min="1" placeholder="Capacity" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <button type="submit" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white">Save</button>
      </form>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Capacity</th>
            <th class="px-3 py-2">Active</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="line in store.lines" :key="line.id" class="border-b border-gray-100">
            <td class="px-3 py-2">{{ line.name }}</td>
            <td class="px-3 py-2">{{ line.capacity ?? '—' }}</td>
            <td class="px-3 py-2">{{ line.is_active ? 'Yes' : 'No' }}</td>
          </tr>
          <tr v-if="store.lines.length === 0">
            <td colspan="3" class="px-3 py-6 text-center text-gray-400">No lines yet.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section>
      <div class="mb-2 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Machines</h2>
        <button
          v-if="auth.can('machine.create')"
          type="button"
          class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="showMachineForm = !showMachineForm"
        >
          {{ showMachineForm ? 'Cancel' : 'Add Machine' }}
        </button>
      </div>
      <form v-if="showMachineForm" class="mb-3 flex gap-3" @submit.prevent="handleCreateMachine">
        <input v-model="machineForm.tag" required placeholder="Tag / asset ID" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <input v-model="machineForm.type" required placeholder="Type (overlock, single needle…)" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
        <select v-model="machineForm.line_id" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
          <option value="">Unassigned</option>
          <option v-for="line in store.lines" :key="line.id" :value="line.id">{{ line.name }}</option>
        </select>
        <button type="submit" class="rounded bg-brand-600 px-3 py-1.5 text-sm text-white">Save</button>
      </form>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Tag</th>
            <th class="px-3 py-2">Type</th>
            <th class="px-3 py-2">Status</th>
            <th class="px-3 py-2">Line</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="machine in store.machines" :key="machine.id" class="border-b border-gray-100">
            <td class="px-3 py-2 font-mono">{{ machine.tag }}</td>
            <td class="px-3 py-2">{{ machine.type }}</td>
            <td class="px-3 py-2 capitalize">{{ machine.status }}</td>
            <td class="px-3 py-2">{{ machine.line_id ? lineName(machine.line_id) : '—' }}</td>
          </tr>
          <tr v-if="store.machines.length === 0">
            <td colspan="4" class="px-3 py-6 text-center text-gray-400">No machines yet.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>
