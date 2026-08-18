import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useBookingStore } from '../store'
import BookingListView from '../views/BookingListView.vue'

describe('booking store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates items from the mocked API response', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, order_id: 5, status: 'draft' }], meta: { total: 1 } },
    })

    const store = useBookingStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/bookings', { params: {} })
    expect(store.items).toHaveLength(1)
    expect(store.items[0].status).toBe('draft')
  })
})

describe('BookingListView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('mounts without throwing', () => {
    expect(() =>
      mount(BookingListView, {
        global: { stubs: { RouterLink: true } },
      }),
    ).not.toThrow()
  })
})
