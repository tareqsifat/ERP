import api from '@/shared/api/axios'

// Axios calls scoped to the Hrm module (sdd.md §2) — PRD v1 §3.11/§7.5.
export default {
  designations: {
    list() {
      return api.get('/designations')
    },
    create(payload) {
      return api.post('/designations', payload)
    },
    update(id, payload) {
      return api.put(`/designations/${id}`, payload)
    },
    remove(id) {
      return api.delete(`/designations/${id}`)
    },
  },

  employees: {
    list(params = {}) {
      return api.get('/employees', { params })
    },
    create(payload) {
      return api.post('/employees', payload)
    },
    update(id, payload) {
      return api.put(`/employees/${id}`, payload)
    },
    remove(id) {
      return api.delete(`/employees/${id}`)
    },
  },

  salaries: {
    list(params = {}) {
      return api.get('/salaries', { params })
    },
    open(payload) {
      return api.post('/salaries/open', payload)
    },
    pay(id, payload) {
      return api.post(`/salaries/${id}/pay`, payload)
    },
  },
}
