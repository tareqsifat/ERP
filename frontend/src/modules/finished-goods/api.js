import api from '@/shared/api/axios'

// Axios calls scoped to the Finished Goods module (sdd.md §2) — PRD v2
// §3.20. Read-only: stock only ever changes as a side effect of QC pass
// (Production), Stock Transfer (Location), or Shipment — see backend
// Modules/FinishedGoods/README.md.
export default {
  stock(params = {}) {
    return api.get('/finished-goods/stock', { params })
  },
  movements(params = {}) {
    return api.get('/finished-goods/movements', { params })
  },
}
