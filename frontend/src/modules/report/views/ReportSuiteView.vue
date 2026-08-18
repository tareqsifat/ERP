<script setup>
import { reactive, ref } from 'vue'
import { useReportStore } from '../store'

// PRD v1 §3.14/§4.13 — Reports section: seven report types, tabbed,
// generally date-range filterable (Report/README.md).
const store = useReportStore()

const tabs = [
  { key: 'sales-orders', label: 'Sales / Order Report' },
  { key: 'production', label: 'Production Report' },
  { key: 'stock', label: 'Stock Report' },
  { key: 'subcontract', label: 'Subcontract Report' },
  { key: 'party-ledger', label: 'Party Ledger Report' },
  { key: 'cashbook', label: 'Daily Cashbook' },
  { key: 'traceability', label: 'Piece Traceability Lookup' },
]
const active = ref('sales-orders')
const range = reactive({ from: '', to: '' })
const serial = ref('')
const loading = ref(false)

async function loadActive() {
  loading.value = true
  try {
    switch (active.value) {
      case 'sales-orders':
        await store.fetchSalesOrders(range)
        break
      case 'production':
        await store.fetchProduction(range)
        break
      case 'stock':
        await store.fetchStock()
        break
      case 'subcontract':
        await store.fetchSubcontract(range)
        break
      case 'party-ledger':
        await store.fetchPartyLedger()
        break
      case 'cashbook':
        await store.fetchCashbook(range)
        break
    }
  } finally {
    loading.value = false
  }
}

function selectTab(key) {
  active.value = key
  if (key !== 'traceability') loadActive()
}

async function lookupSerial() {
  if (!serial.value) return
  await store.lookupSerial(serial.value)
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Report Suite</h1>

    <div class="mb-4 flex flex-wrap gap-1 border-b border-gray-200">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        class="rounded-t px-3 py-2 text-sm"
        :class="active === tab.key ? 'border-b-2 border-brand-600 font-medium text-brand-700' : 'text-gray-500 hover:text-gray-700'"
        @click="selectTab(tab.key)"
      >
        {{ tab.label }}
      </button>
    </div>

    <form v-if="['sales-orders', 'production', 'subcontract', 'cashbook'].includes(active)" class="mb-4 flex items-end gap-2" @submit.prevent="loadActive">
      <div>
        <label class="mb-1 block text-xs text-gray-500">From</label>
        <input v-model="range.from" type="date" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">To</label>
        <input v-model="range.to" type="date" class="rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-sm">Filter</button>
    </form>

    <div v-if="loading" class="text-sm text-gray-400">Loading…</div>

    <div v-if="active === 'sales-orders' && store.salesOrders">
      <p class="mb-2 text-sm text-gray-600">
        Total orders: <strong>{{ store.salesOrders.total_orders }}</strong> —
        Total value: <strong class="font-mono">{{ store.salesOrders.total_value }}</strong>
      </p>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Status</th>
            <th class="px-3 py-2">Order Count</th>
            <th class="px-3 py-2">Total Value</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in store.salesOrders.by_status" :key="row.status" class="border-b border-gray-100">
            <td class="px-3 py-2 capitalize">{{ row.status }}</td>
            <td class="px-3 py-2">{{ row.order_count }}</td>
            <td class="px-3 py-2 font-mono">{{ row.total_value }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="active === 'production' && store.production">
      <p class="mb-2 text-sm text-gray-600">
        Cut tickets: <strong>{{ store.production.cut_tickets_count }}</strong> —
        Planned quantity: <strong>{{ store.production.planned_quantity }}</strong>
      </p>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Piece Status</th>
            <th class="px-3 py-2">Count</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in store.production.pieces_by_status" :key="row.status" class="border-b border-gray-100">
            <td class="px-3 py-2 capitalize">{{ row.status }}</td>
            <td class="px-3 py-2">{{ row.piece_count }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="active === 'stock' && store.stock">
      <h2 class="mb-2 text-sm font-medium text-gray-700">Raw Materials</h2>
      <table class="mb-6 w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Stock</th>
            <th class="px-3 py-2">Reorder Level</th>
            <th class="px-3 py-2">Below Reorder?</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in store.stock.raw_materials" :key="row.id" class="border-b border-gray-100" :class="row.below_reorder_level ? 'bg-red-50' : ''">
            <td class="px-3 py-2">{{ row.name }}</td>
            <td class="px-3 py-2 font-mono">{{ row.stock }} {{ row.unit }}</td>
            <td class="px-3 py-2 font-mono">{{ row.reorder_level }}</td>
            <td class="px-3 py-2">{{ row.below_reorder_level ? 'Yes' : 'No' }}</td>
          </tr>
        </tbody>
      </table>

      <h2 class="mb-2 text-sm font-medium text-gray-700">Finished Goods</h2>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Order</th>
            <th class="px-3 py-2">Style</th>
            <th class="px-3 py-2">Color</th>
            <th class="px-3 py-2">Size</th>
            <th class="px-3 py-2">Quantity</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, i) in store.stock.finished_goods" :key="i" class="border-b border-gray-100">
            <td class="px-3 py-2">#{{ row.order_id }}</td>
            <td class="px-3 py-2">{{ row.style }}</td>
            <td class="px-3 py-2">{{ row.color }}</td>
            <td class="px-3 py-2">{{ row.size || '—' }}</td>
            <td class="px-3 py-2 font-mono">{{ row.quantity }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="active === 'subcontract' && store.subcontract">
      <p class="mb-2 text-sm text-gray-600">
        Job work income: <strong class="font-mono">{{ store.subcontract.job_work_income }}</strong>
      </p>
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Direction</th>
            <th class="px-3 py-2">Status</th>
            <th class="px-3 py-2">Count</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, i) in store.subcontract.orders_by_direction_status" :key="i" class="border-b border-gray-100">
            <td class="px-3 py-2 capitalize">{{ row.direction }}</td>
            <td class="px-3 py-2 capitalize">{{ row.status }}</td>
            <td class="px-3 py-2">{{ row.order_count }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="active === 'party-ledger'">
      <table class="w-full border-collapse bg-white text-sm shadow-sm">
        <thead>
          <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="px-3 py-2">Party</th>
            <th class="px-3 py-2">Type</th>
            <th class="px-3 py-2">Total Bill</th>
            <th class="px-3 py-2">Paid</th>
            <th class="px-3 py-2">Advance</th>
            <th class="px-3 py-2">Due</th>
            <th class="px-3 py-2">Balance</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in store.partyLedger" :key="row.id" class="border-b border-gray-100">
            <td class="px-3 py-2">{{ row.name }}</td>
            <td class="px-3 py-2 capitalize">{{ row.type }}</td>
            <td class="px-3 py-2 font-mono">{{ row.total_bill }}</td>
            <td class="px-3 py-2 font-mono">{{ row.paid }}</td>
            <td class="px-3 py-2 font-mono">{{ row.advance }}</td>
            <td class="px-3 py-2 font-mono">{{ row.due }}</td>
            <td class="px-3 py-2 font-mono">{{ row.balance }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="active === 'cashbook' && store.cashbook">
      <div class="mb-4 grid grid-cols-5 gap-3">
        <div class="rounded border border-gray-200 bg-white p-3 text-center">
          <div class="text-xs text-gray-500">Previous Balance</div>
          <div class="font-mono font-semibold">{{ store.cashbook.meta.previous_balance }}</div>
        </div>
        <div class="rounded border border-gray-200 bg-white p-3 text-center">
          <div class="text-xs text-gray-500">Credit</div>
          <div class="font-mono font-semibold text-green-700">{{ store.cashbook.meta.credit }}</div>
        </div>
        <div class="rounded border border-gray-200 bg-white p-3 text-center">
          <div class="text-xs text-gray-500">Sub Total</div>
          <div class="font-mono font-semibold">{{ store.cashbook.meta.sub_total }}</div>
        </div>
        <div class="rounded border border-gray-200 bg-white p-3 text-center">
          <div class="text-xs text-gray-500">Total Expenses</div>
          <div class="font-mono font-semibold text-red-700">{{ store.cashbook.meta.total_expenses }}</div>
        </div>
        <div class="rounded border border-gray-200 bg-white p-3 text-center">
          <div class="text-xs text-gray-500">Cash In Hand</div>
          <div class="font-mono font-semibold">{{ store.cashbook.meta.cash_in_hand }}</div>
        </div>
      </div>
      <p class="text-xs text-gray-400">Full transaction detail is on the Daily Cashbook page under Accounts &amp; Bank.</p>
    </div>

    <div v-else-if="active === 'traceability'">
      <form class="mb-4 flex items-end gap-2" @submit.prevent="lookupSerial">
        <div>
          <label class="mb-1 block text-xs text-gray-500">Piece Serial</label>
          <input v-model="serial" type="text" placeholder="e.g. ORD-1-A1-BLK-M-0001" class="w-72 rounded border border-gray-300 px-3 py-1.5 text-sm" />
        </div>
        <button type="submit" class="rounded bg-brand-600 px-4 py-1.5 text-sm text-white">Look up</button>
      </form>

      <p v-if="store.traceError" role="alert" class="text-sm text-red-600">{{ store.traceError }}</p>

      <div v-if="store.traceResult" class="space-y-4">
        <div class="rounded border border-gray-200 bg-white p-4">
          <p class="text-sm"><strong>Serial:</strong> {{ store.traceResult.serial }} — <strong>Status:</strong> <span class="capitalize">{{ store.traceResult.status }}</span></p>
        </div>
        <div v-if="store.traceResult.cut_ticket" class="rounded border border-gray-200 bg-white p-4">
          <h3 class="mb-2 text-sm font-medium text-gray-700">Cut Ticket</h3>
          <p class="text-sm text-gray-600">
            {{ store.traceResult.cut_ticket.style }} / {{ store.traceResult.cut_ticket.color }} / {{ store.traceResult.cut_ticket.size }}
            — cut on {{ store.traceResult.cut_ticket.cut_date }}
            <span v-if="store.traceResult.cut_ticket.inward_subcontract_order_id"> (inward subcontract job #{{ store.traceResult.cut_ticket.inward_subcontract_order_id }})</span>
          </p>
        </div>
        <div v-if="store.traceResult.bundle" class="rounded border border-gray-200 bg-white p-4">
          <h3 class="mb-2 text-sm font-medium text-gray-700">Bundle</h3>
          <p class="text-sm text-gray-600">{{ store.traceResult.bundle.bundle_no }} — line: {{ store.traceResult.bundle.line || '—' }}</p>
        </div>
        <div class="rounded border border-gray-200 bg-white p-4">
          <h3 class="mb-2 text-sm font-medium text-gray-700">QC</h3>
          <p class="text-sm text-gray-600">
            {{ store.traceResult.qc.result }}
            <span v-if="store.traceResult.qc.reject_reason"> — {{ store.traceResult.qc.reject_reason }}</span>
            <span v-if="store.traceResult.qc.by"> by {{ store.traceResult.qc.by }} on {{ store.traceResult.qc.at }}</span>
          </p>
        </div>
        <div v-if="store.traceResult.finished_goods_movements?.length" class="rounded border border-gray-200 bg-white p-4">
          <h3 class="mb-2 text-sm font-medium text-gray-700">Finished Goods Movements</h3>
          <table class="w-full text-sm">
            <tbody>
              <tr v-for="m in store.traceResult.finished_goods_movements" :key="m.id" class="border-b border-gray-100">
                <td class="py-1 pr-3">{{ m.occurred_on }}</td>
                <td class="py-1 pr-3 capitalize">{{ m.type }}</td>
                <td class="py-1 font-mono">{{ m.quantity }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
