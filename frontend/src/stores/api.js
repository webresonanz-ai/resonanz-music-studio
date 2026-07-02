import { defineStore } from 'pinia'

const API_ROOT = import.meta.env.VITE_API_URL || 'http://localhost:8000'
const API_BASE = `${API_ROOT.replace(/\/$/, '')}/api`

export const useApiStore = defineStore('api', {
  state: () => ({
    loading: false,
    error: null
  }),

  actions: {
    async request(endpoint, options = {}) {
      this.loading = true
      this.error = null

      try {
        const token = localStorage.getItem('resonanz-token') || ''
        const response = await fetch(`${API_BASE}${endpoint}`, {
          headers: {
            'Content-Type': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...options.headers
          },
          cache: 'no-store',
          ...options
        })

        const responseText = await response.text()
        let data

        try {
          data = responseText ? JSON.parse(responseText) : {}
        } catch {
          data = { error: 'API returned an invalid response' }
        }

        if (!response.ok) {
          throw new Error(data.error || 'API request failed')
        }

        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async get(endpoint) {
      return this.request(endpoint, { method: 'GET' })
    },

    async post(endpoint, data) {
      return this.request(endpoint, {
        method: 'POST',
        body: JSON.stringify(data)
      })
    }
  }
})

export const useTrmsStore = defineStore('trms', {
  state: () => ({
    teachers: [],
    courses: [],
    news: [],
    schedules: [],
    concertAudiences: [],
    concertAudiencesMeta: {
      total: 0,
      perPage: 10,
      currentPage: 1,
      lastPage: 1,
    }
  }),

  actions: {
    async fetchTeachers() {
      this.teachers = await useApiStore().get('/trms/teachers')
    },

    async fetchCourses() {
      this.courses = await useApiStore().get('/trms/courses')
    },

    async fetchNews() {
      this.news = await useApiStore().get('/trms/news')
    },

    async fetchSchedules() {
      this.schedules = await useApiStore().get('/trms/schedule')
    },

    async createSchedule(data) {
      return useApiStore().post('/trms/schedule', data)
    },

    async updateSchedule(id, data) {
      return useApiStore().post(`/trms/schedule/${id}`, data)
    },

    async deleteSchedule(id) {
      return useApiStore().post(`/trms/schedule/${id}/delete`, {})
    },

    async fetchConcertAudiences(params = {}) {
      const query = new URLSearchParams()
      if (params.page) query.set('page', params.page)
      if (params.perPage) query.set('per_page', params.perPage)
      const qs = query.toString()
      const response = await useApiStore().get('/trms/concert/audiences' + (qs ? `?${qs}` : ''))
      this.concertAudiences = response.data
      this.concertAudiencesMeta = {
        total: response.total,
        perPage: response.per_page,
        currentPage: response.current_page,
        lastPage: response.last_page,
      }
      return response
    },

    async submitContact(form) {
      return useApiStore().post('/trms/contact', form)
    },

    async submitConcertRegistration(form) {
      return useApiStore().post('/trms/concert/registration', form)
    }
  }
})

export const useBmsStore = defineStore('bms', {
  state: () => ({
    events: [],
    members: [],
    attendance: []
  }),

  actions: {
    async fetchEvents() {
      this.events = await useApiStore().get('/bms/events')
    },

    async fetchMembers() {
      this.members = await useApiStore().get('/bms/members')
    },

    async createMember(data) {
      const result = await useApiStore().post('/bms/members', data)
      if (result?.data) {
        this.members.push(result.data)
      }
      return result
    },

    async updateMember(id, data) {
      const result = await useApiStore().post(`/bms/members/${id}`, data)
      if (result?.data) {
        const idx = this.members.findIndex(m => m.id === id)
        if (idx !== -1) this.members.splice(idx, 1, result.data)
      }
      return result
    },

    async deleteMember(id) {
      const result = await useApiStore().post(`/bms/members/${id}/delete`, {})
      if (result?.success) {
        this.members = this.members.filter(m => m.id !== id)
      }
      return result
    },

    async fetchAttendance() {
      this.attendance = await useApiStore().get('/bms/attendance')
    },

    async recordAttendance(data) {
      return useApiStore().post('/bms/attendance', data)
    }
  }
})

export const useJcoStore = defineStore('jco', {
  state: () => ({
    orchestraMembers: [],
    concerts: [],
    gallery: []
  }),

  actions: {
    async fetchOrchestraMembers() {
      this.orchestraMembers = await useApiStore().get('/jco/orchestra/members')
    },

    async fetchConcerts() {
      this.concerts = await useApiStore().get('/jco/concerts')
    },

    async fetchGallery() {
      this.gallery = await useApiStore().get('/jco/gallery')
    },

    async submitContact(form) {
      return useApiStore().post('/jco/contact', form)
    }
  }
})

export const useTrccStore = defineStore('trcc', {
  state: () => ({
    achievements: [],
    testimonials: []
  }),

  actions: {
    async fetchAchievements() {
      this.achievements = await useApiStore().get('/trcc/achievements')
    },

    async fetchTestimonials() {
      this.testimonials = await useApiStore().get('/trcc/testimonials')
    },

    async submitContact(form) {
      return useApiStore().post('/trcc/contact', form)
    }
  }
})
