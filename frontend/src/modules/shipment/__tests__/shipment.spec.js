import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useShipmentStore } from '../store'
import ShipmentListView from '../views/ShipmentListView.vue'

describe('shipment store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, invoice_no: 'SHIP-2026-0001', order_id: 5 }], meta: { total: 1 } },
    })

    const store = useShipmentStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/shipments', { params: {} })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].invoice_no).toBe('SHIP-2026-0001')
  })
})

describe('ShipmentListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(ShipmentListView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
