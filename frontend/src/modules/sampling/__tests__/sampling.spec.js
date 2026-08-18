import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useSampleStore } from '../store'
import SampleListView from '../views/SampleListView.vue'

describe('sampling store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, order_id: 5, sample_type: 'pp' }], meta: { total: 1 } },
    })

    const store = useSampleStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/samples', { params: {} })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].sample_type).toBe('pp')
  })
})

describe('SampleListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(SampleListView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
