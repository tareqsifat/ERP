<script setup>
import { useAuthStore } from '@/shared/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div>
    <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
    <p class="mt-2 text-sm text-gray-500">
      Welcome{{ auth.user?.name ? `, ${auth.user.name}` : '' }}. KPI cards and charts (PRD
      §4.1) land here in Phase 7.
    </p>
  </div>
</template>
