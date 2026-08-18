import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useSubcontractStore } from '../store'
import SubcontractOutwardView from '../views/SubcontractOutwardView.vue'
import SubcontractInwardView from '../views/SubcontractInwardView.vue'
import SubcontractLedgerView from '../views/SubcontractLedgerView.vue'

describe('subcontract store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList() resolves and populates orders, split by direction', async () => {
    api.get.mockResolvedValueOnce({
      data: {
        data: [
          { id: 1, direction: 'outward', subcontract_no: 'SC-2026-0001' },
          { id: 2, direction: 'inward', subcontract_no: 'SC-2026-0002' },
        ],
        meta: { total: 2 },
      },
    })

    const store = useSubcontractStore()
    await store.fetchList()

    expect(api.get).toHaveBeenCalledWith('/subcontract-orders', { params: {} })
    expect(store.outwardOrders).toHaveLength(1)
    expect(store.inwardOrders).toHaveLength(1)
  })

  it('fetchLedger() resolves and populates ledgerEntries', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: [{ id: 1, type: 'issue_value', amount: '60.00' }] },
    })

    const store = useSubcontractStore()
    await store.fetchLedger(5)

    expect(api.get).toHaveBeenCalledWith('/subcontract-orders/5/ledger')
    expect(store.ledgerEntries).toHaveLength(1)
  })

  it('dispatchBack() replaces the order in place', async () => {
    const store = useSubcontractStore()
    store.orders = [{ id: 1, direction: 'inward', status: 'open' }]
    api.post.mockResolvedValueOnce({ data: { data: { id: 1, direction: 'inward', status: 'closed' } } })

    await store.dispatchBack(1)

    expect(store.orders[0].status).toBe('closed')
  })
})

describe('Subcontract views', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('SubcontractOutwardView mounts without throwing', () => {
    expect(() =>
      mount(SubcontractOutwardView, { global: { stubs: { RouterLink: true } } }),
    ).not.toThrow()
  })

  it('SubcontractInwardView mounts without throwing', () => {
    expect(() =>
      mount(SubcontractInwardView, { global: { stubs: { RouterLink: true } } }),
    ).not.toThrow()
  })

  it('SubcontractLedgerView mounts without throwing', () => {
    expect(() =>
      mount(SubcontractLedgerView, { global: { stubs: { RouterLink: true } } }),
    ).not.toThrow()
  })
})
