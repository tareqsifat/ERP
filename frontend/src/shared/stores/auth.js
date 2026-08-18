import { defineStore } from 'pinia'
import api, { registerAuthStoreGetter } from '@/shared/api/axios'

const STORAGE_KEY = 'garments_erp.auth'

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => {
    const persisted = loadPersisted()
    return {
      accessToken: persisted?.accessToken ?? null,
      refreshToken: persisted?.refreshToken ?? null,
      user: persisted?.user ?? null,
      // Flattened role/permission names for cheap v-if checks in views.
      roles: persisted?.roles ?? [],
      permissions: persisted?.permissions ?? [],
    }
  },

  getters: {
    isAuthenticated: (state) => !!state.accessToken,
    hasRole: (state) => (role) => state.roles.includes(role),
    can: (state) => (permission) => state.permissions.includes(permission),
  },

  actions: {
    persist() {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
          accessToken: this.accessToken,
          refreshToken: this.refreshToken,
          user: this.user,
          roles: this.roles,
          permissions: this.permissions,
        }),
      )
    },

    applySession({ access_token, refresh_token, user, roles, permissions }) {
      this.accessToken = access_token
      this.refreshToken = refresh_token
      this.user = user
      this.roles = roles ?? []
      this.permissions = permissions ?? []
      this.persist()
    },

    // Modules/Auth's login endpoint proxies Passport's Password Grant
    // server-side (sdd.md §4) so the OAuth client secret never has to
    // ship inside frontend JS.
    async login(email, password) {
      const { data } = await api.post('/auth/login', { email, password })
      this.applySession(data.data)
      return data.data
    },

    async refreshAccessToken() {
      const { data } = await api.post('/auth/refresh', {
        refresh_token: this.refreshToken,
      })
      this.applySession(data.data)
      return data.data
    },

    async logout() {
      try {
        if (this.accessToken) {
          await api.post('/auth/logout')
        }
      } catch {
        // Even if the server call fails (token already invalid, network
        // blip), still clear local state below — logout must never get
        // the user stuck.
      } finally {
        this.accessToken = null
        this.refreshToken = null
        this.user = null
        this.roles = []
        this.permissions = []
        localStorage.removeItem(STORAGE_KEY)
      }
    },
  },
})

// Wire the axios interceptor (defined before Pinia exists) to this store,
// without a circular import at module-eval time.
registerAuthStoreGetter(() => {
  try {
    return useAuthStore()
  } catch {
    return null
  }
})
