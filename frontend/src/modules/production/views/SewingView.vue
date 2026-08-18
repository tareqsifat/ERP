<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useProductionStore } from '../store'
import productionApi from '../api'
import locationApi from '@/modules/location/api'

// PRD v2 §3.18 — Sewing line input/output + QC. Folded into one page:
// the PRD site map only has one "Sewing Line Output" nav entry (no
// separate QC page), and QC is the natural next step once a bundle's
// pieces are sewn — see Modules/Production/README.md "Bundle status vs.
// piece status" for why QC is per-piece, not per-bundle.
const store = useProductionStore()
const auth = useAuthStore()

const stores = ref([])
const assignLineId = reactive({})
const expandedBundle = ref(null)
const expandedPieces = ref([])
const qcReason = reactive({})
const qcLocation = reactive({})
const statusFilter = ref('')
const errorMessage = ref('')

async function load() {
  const [locRes] = await Promise.all([
    locationApi.list({ type: 'store', per_page: 100 }),
    store.fetchLines({ active_only: 1 }),
  ])
  stores.value = locRes.data.data
  await store.fetchBundles({ status: statusFilter.value || undefined })
}

onMounted(load)

async function handleAssign(bundle) {
  errorMessage.value = ''
  try {
    await store.assignBundleToLine(bundle.id, assignLineId[bundle.id])
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not assign this bundle to a line.'
  }
}

async function handleLogOutput(bundle) {
  errorMessage.value = ''
  try {
    await store.logBundleOutput(bundle.id)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not log output for this bundle.'
  }
}

async function toggleExpand(bundle) {
  if (expandedBundle.value === bundle.id) {
    expandedBundle.value = null
    return
  }
  const { data } = await productionApi.bundles.get(bundle.id)
  expandedPieces.value = data.data.piece_serials || []
  expandedBundle.value = bundle.id
}

async function handleQc(piece, result) {
  errorMessage.value = ''
  try {
    const payload = { result }
    if (result === 'reject') {
      payload.reason = qcReason[piece.id] || ''
    } else {
      payload.location_id = qcLocation[piece.id]
    }
    const { data } = await productionApi.pieceSerials.qc(piece.id, payload)
    const index = expandedPieces.value.findIndex((p) => p.id === piece.id)
    if (index !== -1) expandedPieces.value[index] = data.data
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not record QC for this piece.'
  }
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Sewing Line Output &amp; QC</h1>

    <form class="mb-4 flex gap-2" @submit.prevent="load">
      <select v-model="statusFilter" class="rounded border border-gray-300 px-3 py-1.5 text-sm">
        <option value="">All statuses</option>
        <option value="cut">Cut (awaiting line assignment)</option>
        <option value="in_sewing">In Sewing</option>
        <option value="sewn">Sewn (ready for QC)</option>
      </select>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <p v-if="errorMessage" role="alert" class="mb-3 text-sm text-red-600">{{ errorMessage }}</p>

    <table class="w-full border-collapse bg-white text-sm shadow-sm">
      <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
          <th class="px-3 py-2">Bundle No</th>
          <th class="px-3 py-2">Quantity</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        <template v-for="bundle in store.bundles" :key="bundle.id">
          <tr class="border-b border-gray-100">
            <td class="px-3 py-2 font-mono">{{ bundle.bundle_no }}</td>
            <td class="px-3 py-2">{{ bundle.quantity }}</td>
            <td class="px-3 py-2 capitalize">{{ bundle.status.replace('_', ' ') }}</td>
            <td class="px-3 py-2 text-right">
              <div v-if="bundle.status === 'cut' && auth.can('production.sewing.create')" class="flex items-center justify-end gap-2">
                <select v-model="assignLineId[bundle.id]" class="rounded border border-gray-300 px-2 py-1 text-sm">
                  <option value="" disabled>Line…</option>
                  <option v-for="line in store.lines" :key="line.id" :value="line.id">{{ line.name }}</option>
                </select>
                <button type="button" class="text-brand-600 hover:underline" @click="handleAssign(bundle)">Assign</button>
              </div>
              <button
                v-else-if="bundle.status === 'in_sewing' && auth.can('production.sewing.create')"
                type="button"
                class="text-brand-600 hover:underline"
                @click="handleLogOutput(bundle)"
              >
                Log Output
              </button>
              <button v-else-if="bundle.status === 'sewn'" type="button" class="text-brand-600 hover:underline" @click="toggleExpand(bundle)">
                {{ expandedBundle === bundle.id ? 'Hide Pieces' : 'QC Pieces' }}
              </button>
            </td>
          </tr>
          <tr v-if="expandedBundle === bundle.id" class="border-b border-gray-100 bg-gray-50">
            <td colspan="4" class="px-3 py-3">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left text-xs uppercase text-gray-500">
                    <th class="py-1">Serial</th>
                    <th class="py-1">Status</th>
                    <th class="py-1">QC</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="piece in expandedPieces" :key="piece.id">
                    <td class="py-1 font-mono">{{ piece.serial }}</td>
                    <td class="py-1 capitalize">{{ piece.status.replace('_', ' ') }}</td>
                    <td class="py-1">
                      <div v-if="piece.status === 'sewn' && auth.can('production.qc.record')" class="flex items-center gap-2">
                        <select v-model="qcLocation[piece.id]" class="rounded border border-gray-300 px-2 py-1 text-xs">
                          <option value="" disabled>Intake store…</option>
                          <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <button type="button" class="text-green-700 hover:underline" @click="handleQc(piece, 'pass')">Pass</button>
                        <input v-model="qcReason[piece.id]" placeholder="Reject reason" class="w-32 rounded border border-gray-300 px-2 py-1 text-xs" />
                        <button type="button" class="text-red-600 hover:underline" @click="handleQc(piece, 'reject')">Reject</button>
                      </div>
                      <span v-else-if="piece.qc_reject_reason" class="text-xs text-red-600">{{ piece.qc_reject_reason }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </template>
        <tr v-if="!store.loading && store.bundles.length === 0">
          <td colspan="4" class="px-3 py-6 text-center text-gray-400">No bundles yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
