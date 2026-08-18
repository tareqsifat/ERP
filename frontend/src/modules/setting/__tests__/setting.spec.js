import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

vi.mock('@/shared/stores/auth', () => ({
  useAuthStore: () => ({ can: () => true, user: { name: 'Admin' } }),
}))

import api from '@/shared/api/axios'
import { useSettingStore } from '../store'
import SettingsView from '../views/SettingsView.vue'

describe('setting store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchAll() resolves and populates groups', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { currency: { code: 'BDT' }, notification: {}, system: {}, company: {} } } })

    const store = useSettingStore()
    await store.fetchAll()

    expect(api.get).toHaveBeenCalledWith('/settings')
    expect(store.groups.currency.code).toBe('BDT')
  })

  it('updateGroup() puts values under the group and replaces groups', async () => {
    api.put.mockResolvedValueOnce({ data: { data: { currency: { code: 'USD' }, notification: {}, system: {}, company: {} } } })

    const store = useSettingStore()
    await store.updateGroup('currency', { code: 'USD' })

    expect(api.put).toHaveBeenCalledWith('/settings', { group: 'currency', values: { code: 'USD' } })
    expect(store.groups.currency.code).toBe('USD')
  })
})

describe('SettingsView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: { currency: {}, notification: {}, system: {}, company: {} } } })
  })

  it('mounts without throwing', () => {
    expect(() => mount(SettingsView, { global: { stubs: { RouterLink: true } } })).not.toThrow()
  })
})
