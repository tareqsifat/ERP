<script setup>
import { onMounted, ref } from 'vue'
import { useFinishedGoodsStore } from '../store'
import locationApi from '@/modules/location/api'

// PRD v2 §3.20 — Finished Goods Inventory, by location. sdd.md §4: a
// Showroom Staff user is server-side scoped to their own location
// regardless of the `location_id` filter sent here (see
// FinishedGoodsController::stock()), so the location dropdown below is
// a convenience for Admin/Store Keeper users, not a security boundary.
const store = useFinishedGoodsStore()
const locations = ref([])
const locationFilter = ref('')

async function load() {
  const locRes = await locationApi.list({ per_page: 100 })
  locations.value = locRes.data.data
  await Promise.all([
    store.fetchStock({ location_id: locationFilter.value || undefined }),
    store.fetchMovements({ location_id: locationFilter.value || undefined }),
  ])
}

onMounted(load)

function locationName(id) {
  return locations.value.find((l) => l.id === id)?.name || `#${id}`
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Finished Goods Inventory</h1>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <select v-model="locationFilter" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All locations</option>
        <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <section class="mb-8">
      <h2 class="mb-2 text-sm font-semibold text-gray-900">Stock</h2>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Location</th>
            <th class="px-3 py-2">Order</th>
            <th class="px-3 py-2">Style / Color / Size</th>
            <th class="px-3 py-2">Quantity</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in store.stock" :key="`${row.location_id}-${row.order_id}-${row.style}-${row.color}-${row.size}`" class="border-b border-gray-100">
            <td class="px-3 py-2">{{ locationName(row.location_id) }}</td>
            <td class="px-3 py-2">#{{ row.order_id }}</td>
            <td class="px-3 py-2">{{ row.style }} / {{ row.color }} / {{ row.size || '—' }}</td>
            <td class="px-3 py-2 font-semibold">{{ row.quantity }}</td>
          </tr>
          <tr v-if="!store.loading && store.stock.length === 0">
            <td colspan="4" class="px-3 py-6 text-center text-gray-400">No Finished Goods stock yet.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section>
      <h2 class="mb-2 text-sm font-semibold text-gray-900">Recent Movements</h2>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Location</th>
            <th class="px-3 py-2">Type</th>
            <th class="px-3 py-2">Style / Color / Size</th>
            <th class="px-3 py-2">Quantity</th>
            <th class="px-3 py-2">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in store.movements" :key="m.id" class="border-b border-gray-100">
            <td class="px-3 py-2">{{ locationName(m.location_id) }}</td>
            <td class="px-3 py-2 capitalize">{{ m.type.replace('_', ' ') }}</td>
            <td class="px-3 py-2">{{ m.style }} / {{ m.color }} / {{ m.size || '—' }}</td>
            <td class="px-3 py-2" :class="m.quantity < 0 ? 'text-red-600' : 'text-green-700'">{{ m.quantity }}</td>
            <td class="px-3 py-2">{{ m.occurred_on }}</td>
          </tr>
          <tr v-if="store.movements.length === 0">
            <td colspan="5" class="px-3 py-6 text-center text-gray-400">No movements yet.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>
