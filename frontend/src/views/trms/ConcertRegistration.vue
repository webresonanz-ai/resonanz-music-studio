<template>
    <div class="fade-in-up">
        <div class="content-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Concert</p>
                    <h1 class="display-4 fw-bold mb-3">{{ selectedConcert ? selectedConcert.title : 'Concert Registration' }}</h1>
                    <p class="lead text-muted mb-0">
                        {{ selectedConcert ? concertScheduleLabel : 'Select a concert first or reserve audience seats for the upcoming TRMS concert.' }}
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="bg-dark text-white rounded p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-ticket-perforated display-6 text-warning"></i>
                            <div>
                                <div class="fw-bold">{{ selectedConcert ? 'Selected Concert' : 'Audience Pass' }}</div>
                                <div class="text-white-50 small">{{ selectedConcert ? concertTimeLabel : 'Registration confirmation' }}</div>
                            </div>
                        </div>
                        <p class="mb-0 text-white-50">
                            {{ selectedConcert ? selectedConcert.description || 'Complete the form below to save this audience registration.' : 'Each submission appears on the audiences page after the API saves it.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div v-if="loadingSchedule" class="py-4 text-center text-muted">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div>Loading selected concert...</div>
            </div>

            <div v-else-if="concertTitleParam && !selectedConcert" class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Concert not found. Please choose another upcoming concert.</span>
                <router-link class="btn btn-sm btn-outline-primary ms-auto" to="/trms/concert/select">Select Concert</router-link>
            </div>

            <form @submit.prevent="submitRegistration">
                <div v-if="successMessage" class="alert alert-success d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ successMessage }}</span>
                </div>

                <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ errorMessage }}</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="audienceName" class="form-label">Full Name</label>
                        <input
                            id="audienceName"
                            v-model.trim="form.name"
                            class="form-control"
                            type="text"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="audienceEmail" class="form-label">Email</label>
                        <input
                            id="audienceEmail"
                            v-model.trim="form.email"
                            class="form-control"
                            type="email"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="audiencePhone" class="form-label">Phone</label>
                        <input
                            id="audiencePhone"
                            v-model.trim="form.phone"
                            class="form-control"
                            type="tel"
                            autocomplete="tel"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="ticketQuantity" class="form-label">Ticket Quantity</label>
                        <input
                            id="ticketQuantity"
                            v-model.number="form.ticket_quantity"
                            class="form-control"
                            type="number"
                            min="1"
                            max="20"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label for="concertTitle" class="form-label">Concert Title</label>
                        <input
                            id="concertTitle"
                            v-model.trim="form.concert_title"
                            class="form-control"
                            type="text"
                            placeholder="TRMS Concert"
                            :readonly="!!selectedConcert"
                            required
                        >
                        <div v-if="selectedConcert" class="form-text">
                            {{ concertScheduleLabel }} · {{ concertTimeLabel }}
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="audienceNotes" class="form-label">Notes</label>
                        <textarea
                            id="audienceNotes"
                            v-model.trim="form.notes"
                            class="form-control"
                            rows="4"
                            placeholder="Seat preference, guest names, or other details"
                        ></textarea>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button class="btn btn-primary btn-lg" type="submit" :disabled="loading || loadingSchedule || (concertTitleParam && !selectedConcert)">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                        <i v-else class="bi bi-send-check me-2"></i>
                        {{ loading ? 'Submitting...' : 'Submit Registration' }}
                    </button>

                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { useTrmsStore } from '../../stores/api'

const slugifyTitle = (title) => String(title || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')

const emptyForm = () => ({
    name: '',
    email: '',
    phone: '',
    ticket_quantity: 1,
    concert_title: 'TRMS Concert',
    notes: ''
})

export default {
    name: 'ConcertRegistration',
    setup() {
        return {
            trmsStore: useTrmsStore()
        }
    },
    data() {
        return {
            form: emptyForm(),
            loading: false,
            loadingSchedule: false,
            successMessage: '',
            errorMessage: '',
            selectedConcert: null
        }
    },
    computed: {
        concertTitleParam() {
            return this.$route.params.concertTitle || ''
        },
        concertScheduleLabel() {
            if (!this.selectedConcert) return ''
            return this.formatDate(this.selectedConcert.date)
        },
        concertTimeLabel() {
            if (!this.selectedConcert) return ''
            return `${this.formatTime(this.selectedConcert.start_time)} - ${this.formatTime(this.selectedConcert.end_time)}`
        }
    },
    watch: {
        concertTitleParam() {
            this.loadSelectedConcert()
        }
    },
    mounted() {
        this.loadSelectedConcert()
    },
    methods: {
        async loadSelectedConcert() {
            this.selectedConcert = null

            if (!this.concertTitleParam) {
                this.form.concert_title = emptyForm().concert_title
                return
            }

            this.loadingSchedule = true
            this.errorMessage = ''

            try {
                await this.trmsStore.fetchSchedules()

                const todayKey = this.toDateKey(new Date())
                this.selectedConcert = this.trmsStore.schedules
                    .filter(schedule => schedule.type === 'concert' && schedule.date >= todayKey)
                    .sort((a, b) => {
                        const dateCompare = a.date.localeCompare(b.date)
                        return dateCompare || a.start_time.localeCompare(b.start_time)
                    })
                    .find(schedule => slugifyTitle(schedule.title) === this.concertTitleParam)

                if (this.selectedConcert) {
                    this.form.concert_title = this.selectedConcert.title
                }
            } catch (error) {
                this.errorMessage = error.message || 'Unable to load selected concert.'
            } finally {
                this.loadingSchedule = false
            }
        },
        async submitRegistration() {
            this.loading = true
            this.successMessage = ''
            this.errorMessage = ''

            try {
                if (this.selectedConcert) {
                    this.form.concert_title = this.selectedConcert.title
                }

                await this.trmsStore.submitConcertRegistration(this.form)
                this.successMessage = 'Registration submitted successfully.'
                this.form = {
                    ...emptyForm(),
                    concert_title: this.selectedConcert ? this.selectedConcert.title : emptyForm().concert_title
                }
            } catch (error) {
                this.errorMessage = error.message || 'Unable to submit registration.'
            } finally {
                this.loading = false
            }
        },
        toDateKey(date) {
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            const day = String(date.getDate()).padStart(2, '0')
            return `${year}-${month}-${day}`
        },
        formatDate(dateStr) {
            if (!dateStr) return ''
            const [year, month, day] = dateStr.split('-').map(Number)
            return new Date(year, month - 1, day).toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        },
        formatTime(value) {
            return String(value || '').slice(0, 5)
        }
    }
}
</script>
