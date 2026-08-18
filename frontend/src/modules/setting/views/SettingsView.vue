<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import { useSettingStore } from '../store'

// PRD v1 §3.15/§4.13 — Currency, Notifications, System (multi-tab),
// Company Settings — four tabs over the same key/value store.
const store = useSettingStore()
const auth = useAuthStore()

const tabs = [
  { key: 'currency', label: 'Currency' },
  { key: 'notification', label: 'Notifications' },
  { key: 'system', label: 'System' },
  { key: 'company', label: 'Company' },
]
const active = ref('currency')
const forms = reactive({ currency: {}, notification: {}, system: {}, company: {} })
const savedMessage = ref('')
const errorMessage = ref('')

async function load() {
  await store.fetchAll()
  Object.assign(forms.currency, store.groups.currency)
  Object.assign(forms.notification, store.groups.notification)
  Object.assign(forms.system, store.groups.system)
  Object.assign(forms.company, store.groups.company)
}
onMounted(load)

async function save(group) {
  savedMessage.value = ''
  errorMessage.value = ''
  try {
    await store.updateGroup(group, forms[group])
    savedMessage.value = 'Saved.'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save these settings.'
  }
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">Settings</h1>

    <div class="mb-4 flex gap-1 border-b border-gray-200">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        class="rounded-t px-3 py-2 text-sm"
        :class="active === tab.key ? 'border-b-2 border-brand-600 font-medium text-brand-700' : 'text-gray-500 hover:text-gray-700'"
        @click="active = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <p v-if="savedMessage" class="mb-3 text-sm text-green-700">{{ savedMessage }}</p>
    <p v-if="errorMessage" role="alert" class="mb-3 text-sm text-red-600">{{ errorMessage }}</p>

    <form v-if="active === 'currency'" class="max-w-md space-y-3" @submit.prevent="save('currency')">
      <div>
        <label class="mb-1 block text-xs text-gray-500">Currency Code</label>
        <input v-model="forms.currency.code" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Symbol</label>
        <input v-model="forms.currency.symbol" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Format</label>
        <input v-model="forms.currency.format" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <button v-if="auth.can('setting.manage')" type="submit" class="rounded bg-brand-600 px-4 py-1.5 text-sm text-white">Save</button>
    </form>

    <form v-else-if="active === 'notification'" class="max-w-md space-y-3" @submit.prevent="save('notification')">
      <label class="flex items-center gap-2 text-sm">
        <input v-model="forms.notification.low_stock_alerts" type="checkbox" />
        Low stock alerts
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="forms.notification.order_status_alerts" type="checkbox" />
        Order status alerts
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="forms.notification.email_enabled" type="checkbox" />
        Email notifications enabled
      </label>
      <button v-if="auth.can('setting.manage')" type="submit" class="rounded bg-brand-600 px-4 py-1.5 text-sm text-white">Save</button>
    </form>

    <form v-else-if="active === 'system'" class="max-w-md space-y-3" @submit.prevent="save('system')">
      <div>
        <label class="mb-1 block text-xs text-gray-500">Date Format</label>
        <input v-model="forms.system.date_format" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Timezone</label>
        <input v-model="forms.system.timezone" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Items Per Page</label>
        <input v-model.number="forms.system.items_per_page" type="number" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Fiscal Year Start Month</label>
        <input v-model.number="forms.system.fiscal_year_start_month" type="number" min="1" max="12" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <button v-if="auth.can('setting.manage')" type="submit" class="rounded bg-brand-600 px-4 py-1.5 text-sm text-white">Save</button>
    </form>

    <form v-else-if="active === 'company'" class="max-w-md space-y-3" @submit.prevent="save('company')">
      <div>
        <label class="mb-1 block text-xs text-gray-500">Company Name</label>
        <input v-model="forms.company.name" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Address</label>
        <input v-model="forms.company.address" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Phone</label>
        <input v-model="forms.company.phone" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Email</label>
        <input v-model="forms.company.email" type="email" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <button v-if="auth.can('setting.manage')" type="submit" class="rounded bg-brand-600 px-4 py-1.5 text-sm text-white">Save</button>
    </form>

    <p v-if="!auth.can('setting.manage')" class="mt-4 text-xs text-gray-400">Read-only — you don't have permission to change settings.</p>
  </div>
</template>
