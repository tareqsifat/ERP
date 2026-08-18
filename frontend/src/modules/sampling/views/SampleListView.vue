<script setup>
import { onMounted } from 'vue'
import { useSampleStore } from '../store'

// PRD v1 §3.4 — Sample List.
const store = useSampleStore()

onMounted(() => store.fetchList())

async function handleDelete(sample) {
  if (!confirm('Delete this sample record?')) return
  await store.removeSample(sample.id)
}
</script>

<template>
  <div class="p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Samples</h1>
      <router-link
        :to="{ name: 'samples.create' }"
        class="rounded bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add New Sample
      </router-link>
    </div>

    <p v-if="store.error" role="alert" class="mb-3 text-sm text-red-600">{{ store.error }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Order</th>
          <th class="px-3 py-2">Consignee</th>
          <th class="px-3 py-2">Style No</th>
          <th class="px-3 py-2">Type</th>
          <th class="px-3 py-2">Qty</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="sample in store.items" :key="sample.id" class="border-b border-gray-100">
          <td class="px-3 py-2">#{{ sample.order_id }}</td>
          <td class="px-3 py-2">{{ sample.consignee }}</td>
          <td class="px-3 py-2">{{ sample.style_number }}</td>
          <td class="px-3 py-2 uppercase">{{ sample.sample_type }}</td>
          <td class="px-3 py-2">{{ sample.quantity }}</td>
          <td class="px-3 py-2 capitalize">{{ sample.status }}</td>
          <td class="space-x-2 px-3 py-2 text-right">
            <router-link :to="{ name: 'samples.edit', params: { id: sample.id } }" class="text-brand-600 hover:underline">
              Edit
            </router-link>
            <button type="button" class="text-red-600 hover:underline" @click="handleDelete(sample)">Delete</button>
          </td>
        </tr>
        <tr v-if="!store.loading && store.items.length === 0">
          <td colspan="7" class="px-3 py-6 text-center text-gray-400">No sample requests yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
