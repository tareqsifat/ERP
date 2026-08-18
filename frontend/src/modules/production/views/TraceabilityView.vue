<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import productionApi from '../api'

// PRD v2 §2 — Piece Traceability Lookup (/production/trace/{serial}):
// "search any serial, see full history." Backend has no dedicated
// history endpoint — a piece's full history is the fields already on
// PieceSerialResource (status + QC fields) plus its bundle/cut-ticket
// chain, so this page shows what GET /piece-serials?serial= returns.
const route = useRoute()
const router = useRouter()

const query = ref(route.params.serial || '')
const result = ref(null)
const notFound = ref(false)
const isSearching = ref(false)

async function search() {
  if (!query.value) return
  isSearching.value = true
  notFound.value = false
  result.value = null
  router.replace({ name: 'production.trace', params: { serial: query.value } })
  try {
    const { data } = await productionApi.pieceSerials.list({ serial: query.value })
    result.value = data.data[0] || null
    notFound.value = !result.value
  } finally {
    isSearching.value = false
  }
}

onMounted(() => {
  if (query.value) search()
})

const statusFlow = ['cut', 'in_sewing', 'sewn', 'qc_passed', 'finished_goods', 'shipped']
</script>

<template>
  <div class="mx-auto max-w-2xl p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Piece Traceability Lookup</h1>

    <form class="mb-6 flex gap-2" @submit.prevent="search">
      <input
        v-model="query"
        placeholder="Enter a piece serial, e.g. 0000012-A1-BLK-260816-003-014"
        class="flex-1 rounded border border-gray-300 px-3 py-2 text-sm"
      />
      <button type="submit" :disabled="isSearching" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSearching ? 'Searching…' : 'Search' }}
      </button>
    </form>

    <p v-if="notFound" class="text-sm text-gray-500">No piece found with that serial.</p>

    <div v-if="result" class="rounded border border-gray-200 bg-white p-4">
      <p class="mb-2 font-mono text-sm font-semibold">{{ result.serial }}</p>
      <dl class="grid grid-cols-2 gap-2 text-sm">
        <dt class="text-gray-500">Order</dt>
        <dd>#{{ result.order_id }}</dd>
        <dt class="text-gray-500">Bundle</dt>
        <dd>#{{ result.bundle_id }}</dd>
        <dt class="text-gray-500">Status</dt>
        <dd class="capitalize">{{ result.status.replace('_', ' ') }}</dd>
        <template v-if="result.qc_reject_reason">
          <dt class="text-gray-500">Reject Reason</dt>
          <dd class="text-red-600">{{ result.qc_reject_reason }}</dd>
        </template>
      </dl>

      <ol class="mt-4 flex flex-wrap gap-2 text-xs">
        <li
          v-for="stage in statusFlow"
          :key="stage"
          class="rounded px-2 py-1 capitalize"
          :class="statusFlow.indexOf(result.status) >= statusFlow.indexOf(stage) || result.status === 'qc_rejected'
            ? 'bg-brand-100 text-brand-800' : 'bg-gray-100 text-gray-400'"
        >
          {{ stage.replace('_', ' ') }}
        </li>
      </ol>
    </div>
  </div>
</template>
