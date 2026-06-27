import { defineStore } from 'pinia'

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'

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
        const response = await fetch(`${API_BASE}${endpoint}`, {
          headers: {
            'Content-Type': 'application/json',
            ...options.headers
          },
          ...options
        })
        
        const data = await response.json()
        
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
    schedules: []
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

    async submitContact(form) {
      return useApiStore().post('/trms/contact', form)
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