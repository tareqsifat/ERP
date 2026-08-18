import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useReportStore } from '../store'
import ReportSuiteView from '../views/ReportSuiteView.vue'

describe('report store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchSalesOrders() resolves and populates salesOrders', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { total_orders: 3, total_value: '900.00', by_status: [] } } })

    const store = useReportStore()
    await store.fetchSalesOrders({ from: '2026-01-01' })

    expect(api.get).toHaveBeenCalledWith('/reports/sales-orders', { params: { from: '2026-01-01' } })
    expect(store.salesOrders.total_orders).toBe(3)
  })

  it('lookupSerial() populates traceResult on success', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { serial: 'ABC-1', status: 'finished_goods' } } })

    const store = useReportStore()
    await store.lookupSerial('ABC-1')

    expect(store.traceResult.serial).toBe('ABC-1')
    expect(store.traceError).toBe('')
  })

  it('lookupSerial() sets traceError on 404', async () => {
    api.get.mockRejectedValueOnce({ response: { data: { message: 'No piece found for that serial.' } } })

    const store = useReportStore()
    await store.lookupSerial('NOPE')

    expect(store.traceResult).toBeNull()
    expect(store.traceError).toBe('No piece found for that serial.')
  })
})

describe('ReportSuiteView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: { total_orders: 0, total_value: '0.00', by_status: [] } } })
  })

  it('mounts without throwing', () => {
    expect(() => mount(ReportSuiteView, { global: { stubs: { RouterLink: true } } })).not.toThrow()
  })
})
