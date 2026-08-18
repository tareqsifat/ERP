import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module — main list view mounts without
// throwing, and the Pinia store's core action resolves against a mocked
// axios response.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { usePartyStore } from '../store'
import PartyListView from '../views/PartyListView.vue'

describe('party store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, name: 'LC Waikiki', type: 'buyer' }], meta: { total: 1 } },
    })

    const store = usePartyStore()
    await store.fetchList({ type: 'buyer' })

    expect(api.get).toHaveBeenCalledWith('/parties', { params: { type: 'buyer' } })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].name).toBe('LC Waikiki')
  })
})

describe('PartyListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(PartyListView, {
        props: { type: 'buyer' },
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
