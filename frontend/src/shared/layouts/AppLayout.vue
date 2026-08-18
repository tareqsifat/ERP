<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/shared/stores/auth'
import { navSections } from './navConfig'

const router = useRouter()
const auth = useAuthStore()

const visibleSections = computed(() =>
  navSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => router.hasRoute(item.routeName)),
    }))
    .filter((section) => section.items.length > 0),
)

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex h-full min-h-screen bg-gray-50">
    <aside class="w-64 shrink-0 overflow-y-auto border-r border-gray-200 bg-white">
      <div class="px-4 py-4 text-lg font-semibold text-brand-700">Garments ERP</div>
      <nav class="px-2 pb-8">
        <div v-for="section in visibleSections" :key="section.label" class="mb-4">
          <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
            {{ section.label }}
          </p>
          <router-link
            v-for="item in section.items"
            :key="item.routeName"
            :to="{ name: item.routeName }"
            class="block rounded px-2 py-1.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700"
            active-class="bg-brand-50 text-brand-700 font-medium"
          >
            {{ item.label }}
          </router-link>
        </div>
        <router-link
          v-if="router.hasRoute('dashboard')"
          :to="{ name: 'dashboard' }"
          class="block rounded px-2 py-1.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700"
        >
          Dashboard
        </router-link>
      </nav>
    </aside>

    <div class="flex flex-1 flex-col">
      <header class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-3">
        <span class="text-sm text-gray-500">{{ auth.user?.name }}</span>
        <button
          type="button"
          class="rounded bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200"
          @click="handleLogout"
        >
          Log out
        </button>
      </header>
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
