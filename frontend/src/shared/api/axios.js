import axios from 'axios'

// sdd.md §1: single Axios instance, interceptor for token refresh + 401 -> logout.
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  headers: {
    Accept: 'application/json',
  },
})

// Lazily imported to avoid a circular import between the axios instance
// and the Pinia auth store (the store also imports this instance).
let authStoreGetter = null
export function registerAuthStoreGetter(getter) {
  authStoreGetter = getter
}

api.interceptors.request.use((config) => {
  const auth = authStoreGetter?.()
  if (auth?.accessToken) {
    config.headers.Authorization = `Bearer ${auth.accessToken}`
  }
  return config
})

let refreshPromise = null

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const { config, response } = error
    const auth = authStoreGetter?.()

    if (!response || response.status !== 401 || !auth || config._retried) {
      return Promise.reject(error)
    }

    // Don't try to refresh using the token endpoint itself.
    if (config.url?.includes('/auth/token')) {
      auth.logout()
      return Promise.reject(error)
    }

    config._retried = true

    try {
      // Coalesce concurrent 401s into a single refresh call.
      refreshPromise ??= auth.refreshAccessToken().finally(() => {
        refreshPromise = null
      })
      await refreshPromise

      config.headers.Authorization = `Bearer ${auth.accessToken}`
      return api(config)
    } catch (refreshError) {
      auth.logout()
      return Promise.reject(refreshError)
    }
  },
)

export default api
