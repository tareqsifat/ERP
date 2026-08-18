import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useHrmStore } from '../store'
import DesignationsView from '../views/DesignationsView.vue'
import EmployeesView from '../views/EmployeesView.vue'
import SalariesView from '../views/SalariesView.vue'

describe('hrm store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchEmployees() resolves and populates employees', async () => {
    api.get.mockResolvedValueOnce({ data: { data: [{ id: 1, full_name: 'Rahim Uddin' }] } })

    const store = useHrmStore()
    await store.fetchEmployees()

    expect(store.employees).toHaveLength(1)
  })

  it('paySalary() replaces the payment in place', async () => {
    const store = useHrmStore()
    store.salaryPayments = [{ id: 1, paid_amount: '0.00', due_amount: '12000.00' }]
    api.post.mockResolvedValueOnce({ data: { data: { id: 1, paid_amount: '5000.00', due_amount: '7000.00' } } })

    await store.paySalary(1, { amount: 5000, payment_method: 'cash' })

    expect(store.salaryPayments[0].due_amount).toBe('7000.00')
  })
})

describe('Hrm views', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  it('DesignationsView mounts without throwing', () => {
    expect(() => mount(DesignationsView, { global: { stubs: { RouterLink: true } } })).not.toThrow()
  })

  it('EmployeesView mounts without throwing', () => {
    expect(() => mount(EmployeesView, { global: { stubs: { RouterLink: true } } })).not.toThrow()
  })

  it('SalariesView mounts without throwing', () => {
    expect(() => mount(SalariesView, { global: { stubs: { RouterLink: true } } })).not.toThrow()
  })
})
