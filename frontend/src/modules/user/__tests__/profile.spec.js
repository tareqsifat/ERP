import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test — PRD v1 §3.16/§4.14 My Profile.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

vi.mock('@/shared/stores/auth', () => ({
  useAuthStore: () => ({ user: { name: 'Admin', email: 'admin@example.com', phone: '' }, persist: vi.fn() }),
}))

import ProfileView from '../views/ProfileView.vue'

describe('ProfileView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('mounts without throwing and pre-fills the current user', () => {
    const wrapper = mount(ProfileView, { global: { stubs: { RouterLink: true } } })
    expect(wrapper.find('input[type="email"]').element.value).toBe('admin@example.com')
  })
})
