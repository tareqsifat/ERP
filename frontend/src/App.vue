<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/shared/stores/auth'
import AppLayout from '@/shared/layouts/AppLayout.vue'

const route = useRoute()
const auth = useAuthStore()

// The login screen (and any other route.meta.bare view) renders without
// the authenticated sidebar/topbar shell.
const useBareLayout = computed(() => route.meta.requiresAuth === false || !auth.isAuthenticated)
</script>

<template>
  <AppLayout v-if="!useBareLayout">
    <router-view />
  </AppLayout>
  <router-view v-else />
</template>
