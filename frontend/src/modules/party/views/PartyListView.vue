<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { usePartyStore } from '../store'

// PRD v1 §3.10 — Party List (one view reused for the Buyers/Suppliers/
// Subcontractors sidebar entries via the `type` prop, set by routes.js).
const props = defineProps({
  type: { type: String, required: true },
})

const store = usePartyStore()
const search = ref('')

const title = computed(
  () => ({ buyer: 'Buyers', supplier: 'Suppliers', subcontractor: 'Subcontractors' })[props.type],
)

async function load() {
  await store.fetchList({ type: props.type, search: search.value || undefined })
}

onMounted(load)
watch(() => props.type, load)

async function handleDelete(party) {
  if (!confirm(`Delete ${party.name}?`)) return
  await store.removeParty(party.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">{{ title }}</h1>
      <router-link
        :to="{ name: 'parties.create', query: { type } }"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add New
      </router-link>
    </div>

    <form class="mb-4" @submit.prevent="load">
      <input
        v-model="search"
        type="search"
        placeholder="Search by name or email…"
        class="w-full max-w-xs rounded border border-gray-300 px-3 py-1.5 text-sm"
      />
    </form>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Name</th>
          <th class="px-3 py-2">Email</th>
          <th class="px-3 py-2">Phone</th>
          <th class="px-3 py-2">Country</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="party in store.items" :key="party.id" class="border-b border-gray-100">
          <td class="px-3 py-2">{{ party.name }}</td>
          <td class="px-3 py-2">{{ party.email }}</td>
          <td class="px-3 py-2">{{ party.phone }}</td>
          <td class="px-3 py-2">{{ party.country }}</td>
          <td class="space-x-2 px-3 py-2 text-right">
            <router-link :to="{ name: 'parties.edit', params: { id: party.id } }" class="text-brand-600 hover:underline">
              Edit
            </router-link>
            <button type="button" class="text-red-600 hover:underline" @click="handleDelete(party)">Delete</button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="5" class="px-3 py-6 text-center text-gray-400">No records yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
