<template>
    <div class="fade-in-up">
        <div class="content-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Concert</p>
                    <h1 class="display-4 fw-bold mb-3">Concert Registration</h1>
                    <p class="lead text-muted mb-0">
                        Reserve audience seats for the upcoming TRMS concert and keep the registration details ready for the front desk.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="bg-dark text-white rounded p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-ticket-perforated display-6 text-warning"></i>
                            <div>
                                <div class="fw-bold">Audience Pass</div>
                                <div class="text-white-50 small">Registration confirmation</div>
                            </div>
                        </div>
                        <p class="mb-0 text-white-50">
                            Each submission appears on the audiences page after the API saves it.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
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
                            required
                        >
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
                    <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                        <i v-else class="bi bi-send-check me-2"></i>
                        {{ loading ? 'Submitting...' : 'Submit Registration' }}
                    </button>

                    <router-link class="btn btn-outline-primary btn-lg" to="/trms/concert/audiences">
                        <i class="bi bi-people me-2"></i>
                        View Audiences
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { useTrmsStore } from '../../stores/api'

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
            successMessage: '',
            errorMessage: ''
        }
    },
    methods: {
        async submitRegistration() {
            this.loading = true
            this.successMessage = ''
            this.errorMessage = ''

            try {
                await this.trmsStore.submitConcertRegistration(this.form)
                this.successMessage = 'Registration submitted successfully.'
                this.form = emptyForm()
            } catch (error) {
                this.errorMessage = error.message || 'Unable to submit registration.'
            } finally {
                this.loading = false
            }
        }
    }
}
</script>
