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
        cache: 'no-store'
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
