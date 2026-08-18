import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useLocationStore } from '../store'
import LocationListView from '../views/LocationListView.vue'

describe('location store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, name: 'Main Store', type: 'store' }], meta: { total: 1 } },
    })

    const store = useLocationStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/locations', { params: {} })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].name).toBe('Main Store')
  })

  it('fetchTransfers() resolves and populates transfers', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, transfer_no: 'ST-2026-0001', status: 'dispatched' }], meta: null },
    })

    const store = useLocationStore()
    await store.fetchTransfers()

    expect(api.get).toHaveBeenCalledWith('/stock-transfers', { params: {} })
    expect(store.transfers).toHaveLength(1)
  })
})

describe('LocationListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(LocationListView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
