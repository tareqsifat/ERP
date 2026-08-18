import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'

// sdd.md §6: Vitest smoke test per module.
vi.mock('@/shared/api/axios', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useAccountingStore } from '../store'
import BankAccountsView from '../views/BankAccountsView.vue'
import CashView from '../views/CashView.vue'
import ChequesView from '../views/ChequesView.vue'
import CategoryView from '../views/CategoryView.vue'
import VoucherView from '../views/VoucherView.vue'
import TransactionsView from '../views/TransactionsView.vue'
import PartyLedgerView from '../views/PartyLedgerView.vue'
import CashbookView from '../views/CashbookView.vue'
import DuesView from '../views/DuesView.vue'
import LossProfitView from '../views/LossProfitView.vue'

describe('accounting store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchBanks() resolves and populates banks', async () => {
    api.get.mockResolvedValueOnce({ data: { data: [{ id: 1, bank_name: 'ABC Bank', balance: '500.00' }] } })

    const store = useAccountingStore()
    await store.fetchBanks()

    expect(api.get).toHaveBeenCalledWith('/bank-accounts')
    expect(store.banks).toHaveLength(1)
  })

  it('fetchVouchers() resolves and populates vouchers', async () => {
    api.get.mockResolvedValueOnce({ data: { data: [{ id: 1, voucher_no: 'CR-2026-0001' }] } })

    const store = useAccountingStore()
    await store.fetchVouchers({ type: 'credit' })

    expect(store.vouchers).toHaveLength(1)
  })

  it('fetchLossProfit() resolves and populates lossProfit', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { total_sale: '1000.00', total_profit: '400.00' } } })

    const store = useAccountingStore()
    await store.fetchLossProfit({ year: 2026 })

    expect(store.lossProfit.total_sale).toBe('1000.00')
  })
})

describe('Accounting views', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: null } })
  })

  const cases = [
    ['BankAccountsView', BankAccountsView, {}],
    ['CashView', CashView, {}],
    ['ChequesView', ChequesView, {}],
    ['CategoryView (income)', CategoryView, { kind: 'income' }],
    ['VoucherView (credit)', VoucherView, { type: 'credit' }],
    ['TransactionsView', TransactionsView, {}],
    ['PartyLedgerView', PartyLedgerView, {}],
    ['CashbookView', CashbookView, {}],
    ['DuesView', DuesView, {}],
    ['LossProfitView', LossProfitView, {}],
  ]

  for (const [name, component, props] of cases) {
    it(`${name} mounts without throwing`, () => {
      expect(() =>
        mount(component, { props, global: { stubs: { RouterLink: true } } }),
      ).not.toThrow()
    })
  }
})
