import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useCostingStore } from '../store'
import CostingListView from '../views/CostingListView.vue'

describe('costing store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, order_id: 5, total_cost: '3200.00' }], meta: { total: 1 } },
    })

    const store = useCostingStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/costings', { params: {} })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].total_cost).toBe('3200.00')
  })
})

describe('CostingListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(CostingListView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
