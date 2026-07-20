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
        const method = options.method || 'GET'
        const response = await fetch(`${API_BASE}${endpoint}`, {
          headers: {
            'Content-Type': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...options.headers
          },
          cache: method === 'GET' ? 'default' : 'no-store',
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
    },

    async postFormData(endpoint, formData) {
      // For multipart/form-data uploads — do NOT set Content-Type so the browser
      // sets the correct boundary automatically.
      this.loading = true
      this.error = null
      try {
        const token = localStorage.getItem('resonanz-token') || ''
        const response = await fetch(`${API_BASE}${endpoint}`, {
          method: 'POST',
          headers: (token ? { Authorization: `Bearer ${token}` } : {}),
          cache: 'no-store',
          body: formData
        })
        const responseText = await response.text()
        let data
        try {
          data = responseText ? JSON.parse(responseText) : {}
        } catch {
          data = { error: 'API returned an invalid response' }
        }
        if (!response.ok) {
          throw new Error(data.error || 'Upload failed')
        }
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchBlob(endpoint) {
      const token = localStorage.getItem('resonanz-token') || ''
      const API_ROOT = import.meta.env.VITE_API_URL || 'http://localhost:8000'
      const base = `${API_ROOT.replace(/\/$/, '')}/api`

      const response = await fetch(`${base}${endpoint}`, {
        method: 'GET',
        headers: (token ? { Authorization: `Bearer ${token}` } : {}),
        cache: 'default'
      })

      if (!response.ok) {
        const text = await response.text()
        let msg = 'Request failed'
        try { msg = JSON.parse(text).error || msg } catch {}
        throw new Error(msg)
      }

      return response.blob()
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

    async fetchNews(params = {}) {
      const query = new URLSearchParams()
      if (params.program_id) query.set('program_id', params.program_id)
      const qs = query.toString()
      this.news = await useApiStore().get('/trms/news' + (qs ? `?${qs}` : ''))
    },

    async createNews(data) {
      const result = await useApiStore().post('/trms/news', data)
      if (result?.data) {
        this.news.unshift(result.data)
      }
      return result
    },

    async updateNews(id, data) {
      const result = await useApiStore().post(`/trms/news/${id}`, data)
      if (result?.data) {
        const idx = this.news.findIndex(n => n.id === id)
        if (idx !== -1) this.news.splice(idx, 1, result.data)
      }
      return result
    },

    async deleteNews(id) {
      const result = await useApiStore().post(`/trms/news/${id}/delete`, {})
      if (result?.success) {
        this.news = this.news.filter(n => n.id !== id)
      }
      return result
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

    async uploadScheduleBanner(file) {
      const formData = new FormData()
      formData.append('banner', file)
      return useApiStore().postFormData('/trms/upload/banner', formData)
    },

async fetchConcertAudiences(params = {}) {
       const query = new URLSearchParams()
       if (params.page) query.set('page', params.page)
       if (params.perPage) query.set('per_page', params.perPage)
       if (params.search) query.set('search', params.search)
       if (params.concert) query.set('concert', params.concert)
       if (params.notes) query.set('notes', params.notes)
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

    async fetchConcertAudienceConcerts() {
      const response = await useApiStore().get('/trms/concert/audiences/concerts')
      return response.data ?? []
    },

    async submitContact(form) {
      return useApiStore().post('/trms/contact', form)
    },

    async submitConcertRegistration(form) {
      return useApiStore().post('/trms/concert/registration', form)
    },

    async updateConcertAudience(id, data) {
      const result = await useApiStore().post(`/trms/concert/audiences/${id}`, data)
      if (result?.data) {
        const idx = this.concertAudiences.findIndex(a => a.id === id)
        if (idx !== -1) this.concertAudiences.splice(idx, 1, result.data)
      }
      return result
    },

    async deleteConcertAudience(id) {
      const result = await useApiStore().post(`/trms/concert/audiences/${id}/delete`, {})
      if (result?.success) {
        this.concertAudiences = this.concertAudiences.filter(a => a.id !== id)
        this.concertAudiencesMeta.total = Math.max(0, this.concertAudiencesMeta.total - 1)
      }
      return result
    },

    async resendConcertEmail(id) {
      return useApiStore().post(`/trms/concert/audiences/${id}/resend-email`, {})
    },

    async scanConcertRegistration(payload) {
      // payload: { qr_code: '...' } or { reg_number: '...' }
      return useApiStore().post('/trms/concert/scan', payload)
    },

    async fetchConcertSeats(scheduleId) {
      const response = await useApiStore().get(`/trms/concert/seats/${scheduleId}`)
      return response.data ?? []
    },

    async saveCustomLayout(layout) {
      return useApiStore().post('/trms/seat-layouts', layout)
    },

    async fetchCustomLayout(layoutKey) {
      return useApiStore().get(`/trms/seat-layouts/${encodeURIComponent(layoutKey)}`)
    },

    async fetchAllCustomLayouts() {
      const response = await useApiStore().get('/trms/seat-layouts')
      return response.data ?? []
    },

    async holdSeat(scheduleId, seatNumber) {
      return useApiStore().post('/trms/seat-holds', { schedule_id: scheduleId, seat_number: seatNumber })
    },

    async releaseSeat(scheduleId, seatNumber) {
      return useApiStore().post('/trms/seat-holds/release', { schedule_id: scheduleId, seat_number: seatNumber })
    },

    async fetchMyHolds(scheduleId) {
      const response = await useApiStore().get(`/trms/seat-holds/${scheduleId}`)
      return response.data ?? []
    }
  }
})

export const useBmsStore = defineStore('bms', {
  state: () => ({
    events: [],
    members: [],
    attendanceConcerts: [],
    attendanceDetail: null
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

    async fetchAttendanceConcerts() {
      const result = await useApiStore().get('/bms/attendance/concerts')
      this.attendanceConcerts = result.concerts || result.all_concerts || []
      return result
    },

    async fetchAttendanceDetail(concertId) {
      const result = await useApiStore().get(`/bms/attendance/concerts/${concertId}`)
      this.attendanceDetail = result
      return result
    },

    async updateConcertRoster(concertScheduleId, memberId, action) {
      const result = await useApiStore().post('/bms/attendance/roster', {
        concert_schedule_id: concertScheduleId,
        member_id: memberId,
        action
      })
      if (result?.roster && this.attendanceDetail) {
        this.attendanceDetail.roster = result.roster
      }
      return result
    },

    async recordScheduleAttendance({ concertScheduleId, scheduleId, memberId, status }) {
      return useApiStore().post('/bms/attendance/record', {
        concert_schedule_id: concertScheduleId,
        schedule_id: scheduleId,
        member_id: memberId,
        status
      })
    },

    async fetchAttendanceByDate(concertId, date) {
      return useApiStore().get(`/bms/attendance/concerts/${concertId}/by-date/${date}`)
    },

    async updateConcertRehearsals(concertScheduleId, rehearsalIds) {
      const result = await useApiStore().post('/bms/attendance/rehearsals', {
        concert_schedule_id: concertScheduleId,
        rehearsal_ids: rehearsalIds
      })
      if (result?.rehearsals && this.attendanceDetail) {
        this.attendanceDetail.rehearsals = result.rehearsals
        this.attendanceDetail.linked_rehearsal_ids = rehearsalIds
      }
      return result
    },

    async linkRehearsal(concertScheduleId, rehearsalId, action = 'link') {
      const result = await useApiStore().post('/bms/attendance/rehearsals', {
        concert_schedule_id: concertScheduleId,
        rehearsal_id: rehearsalId,
        action
      })
      if (result?.rehearsals && this.attendanceDetail) {
        this.attendanceDetail.rehearsals = result.rehearsals
        this.attendanceDetail.linked_rehearsal_ids = result.rehearsals.map(r => r.id)
      }
      return result
    },

    async recordBulkAttendance({ concertScheduleId, scheduleId, presentMemberIds, markAbsent = false }) {
      return useApiStore().post('/bms/attendance/record/bulk', {
        concert_schedule_id: concertScheduleId,
        schedule_id: scheduleId,
        present_member_ids: presentMemberIds,
        mark_absent: markAbsent
      })
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

export const useLibraryStore = defineStore('library', {
  state: () => ({
    scores: [],
    costumes: [],
    costumeMeta: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
    costumeGroups: [],
    loading: false,
    error: null,
  }),

  getters: {
    genres: (state) => [...new Set(state.scores.map((s) => s.genre))].sort(),
    composers: (state) => [...new Set(state.scores.map((s) => s.composer))].sort(),
    arrangers: (state) => [...new Set(state.scores.map((s) => s.arranger))].sort(),
  },

  actions: {
    async fetchScores() {
      this.loading = true
      this.error = null
      try {
        this.scores = await useApiStore().get('/library/scores')
      } catch (err) {
        this.error = err.message
      } finally {
        this.loading = false
      }
    },

    async createScore(data) {
      const result = await useApiStore().post('/library/scores', data)
      if (result?.data) {
        this.scores.push(result.data)
      }
      return result
    },

    async updateScore(id, data) {
      const result = await useApiStore().post(`/library/scores/${id}`, data)
      if (result?.data) {
        const idx = this.scores.findIndex((s) => s.id === id)
        if (idx !== -1) this.scores.splice(idx, 1, result.data)
      }
      return result
    },

    async deleteScore(id) {
      const result = await useApiStore().post(`/library/scores/${id}/delete`, {})
      if (result?.success) {
        this.scores = this.scores.filter((s) => s.id !== id)
      }
      return result
    },

    async uploadScorePdf(id, file) {
      const formData = new FormData()
      formData.append('pdf', file)
      const result = await useApiStore().postFormData(`/library/scores/${id}/upload-pdf`, formData)
      if (result?.data) {
        const idx = this.scores.findIndex((s) => s.id === id)
        if (idx !== -1) this.scores.splice(idx, 1, result.data)
      }
      return result
    },

    async fetchCostumes(params = {}) {
      this.loading = true
      this.error = null
      try {
        const query = new URLSearchParams()
        if (params.page) query.set('page', params.page)
        if (params.per_page) query.set('per_page', params.per_page)
        if (params.search) query.set('search', params.search)
        if (params.group_category) query.set('group_category', params.group_category)
        if (params.type) query.set('type', params.type)
        const qs = query.toString()
        const result = await useApiStore().get(`/library/costumes${qs ? '?' + qs : ''}`)
        this.costumes = result.data || []
        this.costumeMeta = {
          current_page: result.current_page || 1,
          last_page: result.last_page || 1,
          total: result.total || 0,
          per_page: result.per_page || 20,
        }
        if (result.groups) this.costumeGroups = result.groups
      } catch (err) {
        this.error = err.message
      } finally {
        this.loading = false
      }
    },

    async createCostume(data) {
      const result = await useApiStore().post('/library/costumes', data)
      if (result?.data) {
        this.costumes.push(result.data)
      }
      return result
    },

    async updateCostume(id, data) {
      const result = await useApiStore().post(`/library/costumes/${id}`, data)
      if (result?.data) {
        const idx = this.costumes.findIndex((c) => c.id === id)
        if (idx !== -1) {
          this.costumes.splice(idx, 1, result.data)
        }
      }
      return result
    },

    async deleteCostume(id) {
      const result = await useApiStore().post(`/library/costumes/${id}/delete`, {})
      if (result?.success) {
        this.costumes = this.costumes.filter((c) => c.id !== id)
      }
      return result
    },
  },
})

export const useConcertHistoryStore = defineStore('concertHistory', {
  state: () => ({
    concerts: [],
    currentConcert: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetchConcerts() {
      this.loading = true
      this.error = null
      try {
        this.concerts = await useApiStore().get('/concert-history')
      } catch (err) {
        this.error = err.message
      } finally {
        this.loading = false
      }
    },

    async fetchConcert(id) {
      this.loading = true
      this.error = null
      try {
        this.currentConcert = await useApiStore().get(`/concert-history/${id}`)
        return this.currentConcert
      } catch (err) {
        this.error = err.message
        throw err
      } finally {
        this.loading = false
      }
    },

    async createConcert(data) {
      const result = await useApiStore().post('/concert-history', data)
      if (result?.data) {
        this.concerts.unshift(result.data)
      }
      return result
    },

    async updateConcert(id, data) {
      const result = await useApiStore().post(`/concert-history/${id}`, data)
      if (result?.data) {
        const idx = this.concerts.findIndex(c => c.id === id)
        if (idx !== -1) this.concerts.splice(idx, 1, result.data)
        if (this.currentConcert?.id === id) this.currentConcert = result.data
      }
      return result
    },

    async deleteConcert(id) {
      const result = await useApiStore().post(`/concert-history/${id}/delete`, {})
      if (result?.success) {
        this.concerts = this.concerts.filter(c => c.id !== id)
      }
      return result
    },

    async uploadBanner(file) {
      const formData = new FormData()
      formData.append('banner', file)
      return useApiStore().postFormData('/concert-history/upload/banner', formData)
    },
  },
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
