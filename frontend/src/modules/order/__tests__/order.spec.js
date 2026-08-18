import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useOrderStore } from '../store'
import OrderListView from '../views/OrderListView.vue'

describe('order store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, order_no: '0000001', grand_total: '100.00' }], meta: { total: 1 } },
    })

    const store = useOrderStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/orders', { params: {} })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].order_no).toBe('0000001')
  })
})

describe('OrderListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(OrderListView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
