import { defineStore } from 'pinia'
import hrmApi from './api'

// Pinia store for the Hrm module (sdd.md §2).
export const useHrmStore = defineStore('hrm', {
  state: () => ({
    designations: [],
    employees: [],
    salaryPayments: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchDesignations() {
      const { data } = await hrmApi.designations.list()
      this.designations = data.data
      return this.designations
    },

    async fetchEmployees(params = {}) {
      this.loading = true
      try {
        const { data } = await hrmApi.employees.list(params)
        this.employees = data.data
        return this.employees
      } finally {
        this.loading = false
      }
    },

    async fetchSalaries(params = {}) {
      this.loading = true
      try {
        const { data } = await hrmApi.salaries.list(params)
        this.salaryPayments = data.data
        return this.salaryPayments
      } finally {
        this.loading = false
      }
    },

    async openSalaryMonth(payload) {
      const { data } = await hrmApi.salaries.open(payload)
      const index = this.salaryPayments.findIndex((p) => p.id === data.data.id)
      if (index === -1) this.salaryPayments.unshift(data.data)
      return data.data
    },

    async paySalary(id, payload) {
      const { data } = await hrmApi.salaries.pay(id, payload)
      const index = this.salaryPayments.findIndex((p) => p.id === id)
      if (index !== -1) this.salaryPayments[index] = data.data
      return data.data
    },
  },
})
