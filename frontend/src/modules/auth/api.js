import api from '@/shared/api/axios'

export default {
  login(email, password) {
    return api.post('/auth/login', { email, password })
  },
  logout() {
    return api.post('/auth/logout')
  },
  me() {
    return api.get('/auth/me')
  },
}
