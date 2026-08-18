import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useProductionStore } from '../store'
import CuttingView from '../views/CuttingView.vue'

describe('production store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchCutTickets() resolves and populates cutTickets from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, style: 'A1', color: 'BLK', status: 'draft' }], meta: { total: 1 } },
    })

    const store = useProductionStore()
    await store.fetchCutTickets()

    expect(api.get).toHaveBeenCalledWith('/cut-tickets', { params: {} })
    expect(store.cutTickets).toHaveLength(1)
    expect(store.cutTickets[0].style).toBe('A1')
  })

  it('finalizeCutTicket() posts to the finalize endpoint and replaces the ticket in state', async () => {
    const store = useProductionStore()
    store.cutTickets = [{ id: 1, status: 'draft' }]
    api.post.mockResolvedValueOnce({ data: { data: { id: 1, status: 'finalized' } } })

    await store.finalizeCutTicket(1)

    expect(api.post).toHaveBeenCalledWith('/cut-tickets/1/finalize')
    expect(store.cutTickets[0].status).toBe('finalized')
  })
})

describe('CuttingView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(CuttingView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
