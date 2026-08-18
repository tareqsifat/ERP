import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useFinishedGoodsStore } from '../store'
import FinishedGoodsStockView from '../views/FinishedGoodsStockView.vue'

describe('finished goods store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchStock() resolves and populates stock from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ location_id: 1, order_id: 1, style: 'A1', color: 'BLK', size: 'M', quantity: 5 }], meta: null },
    })

    const store = useFinishedGoodsStore()
    await store.fetchStock()

    expect(api.get).toHaveBeenCalledWith('/finished-goods/stock', { params: {} })
    expect(store.stock).toHaveLength(1)
    expect(store.stock[0].quantity).toBe(5)
  })
})

describe('FinishedGoodsStockView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(FinishedGoodsStockView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
