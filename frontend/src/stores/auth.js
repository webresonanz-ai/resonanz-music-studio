import { defineStore } from 'pinia'
import { useApiStore } from './api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('resonanz-user') || 'null'),
    token: localStorage.getItem('resonanz-token') || '',
    loading: false
  }),

  actions: {
    async login(credentials) {
      this.loading = true
      try {
        const response = await useApiStore().post('/auth/login', credentials)
        this.setAuth(response)
        return response
      } finally {
        this.loading = false
      }
    },

    async register(payload) {
      this.loading = true
      try {
        return await useApiStore().post('/auth/register', payload)
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      if (!this.token) return null

      this.loading = true
      try {
        const response = await useApiStore().get('/auth/me')
        this.user = response.user
        localStorage.setItem('resonanz-user', JSON.stringify(this.user))
        return response.user
      } finally {
        this.loading = false
      }
    },

    setAuth(response) {
      this.token = response.token || ''
      this.user = response.user || null
      localStorage.setItem('resonanz-token', this.token)
      localStorage.setItem('resonanz-user', JSON.stringify(this.user))
    },

    logout() {
      this.token = ''
      this.user = null
      localStorage.removeItem('resonanz-token')
      localStorage.removeItem('resonanz-user')
    }
  }
})
