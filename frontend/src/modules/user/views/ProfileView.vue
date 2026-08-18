<script setup>
import { reactive, ref } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import userApi from '../api'

// PRD v1 §3.16/§4.14 — My Profile: self-service view/edit of the
// logged-in user's own name/email/phone/password. Backend
// (GET /auth/me, PATCH /users/me) was already built in Phase 2 — this
// view is the only Phase 7 addition here (Setting/README.md).
const auth = useAuthStore()

const form = reactive({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
  phone: auth.user?.phone || '',
  password: '',
  password_confirmation: '',
})

const isSubmitting = ref(false)
const savedMessage = ref('')
const errorMessage = ref('')

async function handleSubmit() {
  savedMessage.value = ''
  errorMessage.value = ''
  isSubmitting.value = true
  try {
    const payload = { name: form.name, email: form.email, phone: form.phone }
    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }
    const { data } = await userApi.updateMe(payload)
    auth.user = { ...auth.user, name: data.data.name, email: data.data.email, phone: data.data.phone }
    auth.persist()
    form.password = ''
    form.password_confirmation = ''
    savedMessage.value = 'Profile updated.'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not update your profile.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="p-6">
    <h1 class="mb-4 text-lg font-semibold text-gray-900">My Profile</h1>

    <p v-if="savedMessage" class="mb-3 text-sm text-green-700">{{ savedMessage }}</p>
    <p v-if="errorMessage" role="alert" class="mb-3 text-sm text-red-600">{{ errorMessage }}</p>

    <form class="max-w-md space-y-3" @submit.prevent="handleSubmit">
      <div>
        <label class="mb-1 block text-xs text-gray-500">Full Name</label>
        <input v-model="form.name" type="text" required class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Email</label>
        <input v-model="form.email" type="email" required class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">Phone</label>
        <input v-model="form.phone" type="text" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-xs text-gray-500">New Password (leave blank to keep current)</label>
        <input v-model="form.password" type="password" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <div v-if="form.password">
        <label class="mb-1 block text-xs text-gray-500">Confirm New Password</label>
        <input v-model="form.password_confirmation" type="password" class="w-full rounded border border-gray-300 px-3 py-1.5 text-sm" />
      </div>
      <button type="submit" :disabled="isSubmitting" class="rounded bg-brand-600 px-4 py-2 text-sm text-white disabled:opacity-60">
        {{ isSubmitting ? 'Saving…' : 'Save Changes' }}
      </button>
    </form>
  </div>
</template>
