import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/shared/api/axios', () => ({
  default: { post: vi.fn(), get: vi.fn() },
  registerAuthStoreGetter: vi.fn(),
}))

import api from '@/shared/api/axios'
import { useAuthStore } from '../auth'

describe('auth store', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts unauthenticated', () => {
    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(false)
    expect(auth.user).toBeNull()
  })

  it('login() resolves and stores the session from the mocked API response', async () => {
    api.post.mockResolvedValueOnce({
      data: {
        data: {
          access_token: 'access-123',
          refresh_token: 'refresh-456',
          user: { id: 1, name: 'Admin User', email: 'admin@example.com' },
          roles: ['Admin'],
          permissions: ['order.create'],
        },
      },
    })

    const auth = useAuthStore()
    await auth.login('admin@example.com', 'secret')

    expect(api.post).toHaveBeenCalledWith('/auth/login', {
      email: 'admin@example.com',
      password: 'secret',
    })
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.accessToken).toBe('access-123')
    expect(auth.hasRole('Admin')).toBe(true)
    expect(auth.can('order.create')).toBe(true)
  })

  it('logout() clears the session even if the API call rejects', async () => {
    const auth = useAuthStore()
    auth.applySession({
      access_token: 'a',
      refresh_token: 'r',
      user: { id: 1, name: 'X' },
      roles: [],
      permissions: [],
    })
    api.post.mockRejectedValueOnce(new Error('network error'))

    await auth.logout()

    expect(auth.isAuthenticated).toBe(false)
    expect(auth.accessToken).toBeNull()
    expect(localStorage.getItem('garments_erp.auth')).toBeNull()
  })
})
