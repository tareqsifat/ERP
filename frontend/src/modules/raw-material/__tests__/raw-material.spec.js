import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useRawMaterialStore } from '../store'
import RawMaterialListView from '../views/RawMaterialListView.vue'

describe('raw material store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, name: 'Cotton Jersey', category: 'fabric' }], meta: { total: 1 } },
    })

    const store = useRawMaterialStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/raw-materials', { params: {} })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].name).toBe('Cotton Jersey')
  })

  it('fetchPurchaseOrders() resolves and populates purchaseOrders', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, po_no: 'PO-2026-0001', status: 'ordered' }], meta: null },
    })

    const store = useRawMaterialStore()
    await store.fetchPurchaseOrders()

    expect(api.get).toHaveBeenCalledWith('/raw-material-purchase-orders', { params: {} })
    expect(store.purchaseOrders).toHaveLength(1)
  })
})

describe('RawMaterialListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(RawMaterialListView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
