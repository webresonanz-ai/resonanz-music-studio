<template>
    <div class="fade-in-up">
        <div class="content-card mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Concert</p>
                    <h1 class="display-4 fw-bold mb-2">Registered Audiences</h1>
                    <p class="lead text-muted mb-0">
                        Review audience registrations that have been submitted for the concert.
                    </p>
                </div>

                <router-link class="btn btn-primary btn-lg" to="/trms/concert/select">
                    <i class="bi bi-person-plus me-2"></i>
                    Add Audience
                </router-link>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1">Audience List</h2>
                    <p class="text-muted mb-0">{{ audiences.length }} registration{{ audiences.length === 1 ? '' : 's' }}</p>
                </div>

                <button class="btn btn-outline-primary" type="button" :disabled="loading" @click="fetchAudiences">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    <i v-else class="bi bi-arrow-clockwise me-2"></i>
                    Refresh
                </button>
            </div>

            <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ errorMessage }}</span>
            </div>

            <div v-if="loading" class="py-5 text-center text-muted">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div>Loading audiences...</div>
            </div>

            <div v-else-if="audiences.length" class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Concert</th>
                            <th scope="col" class="text-center">Tickets</th>
                            <th scope="col">Registered</th>
                            <th scope="col">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="audience in audiences" :key="audience.id">
                            <td class="fw-semibold">{{ audience.name }}</td>
                            <td>
                                <div>{{ audience.email }}</div>
                                <div class="small text-muted">{{ audience.phone }}</div>
                            </td>
                            <td>{{ audience.concert_title }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill text-bg-warning">{{ audience.ticket_quantity }}</span>
                            </td>
                            <td>{{ formatDate(audience.created_at) }}</td>
                            <td class="text-muted">{{ audience.notes || '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                <nav v-if="lastPage > 1" class="d-flex align-items-center justify-content-between pt-3" aria-label="Audience pagination">
                    <div class="text-muted small">
                        Showing {{ (currentPage - 1) * perPage + 1 }}
                        to {{ Math.min(currentPage * perPage, total) }}
                        of {{ total }} registration{{ total === 1 ? '' : 's' }}
                    </div>
                    <div class="btn-group">
                        <button
                            class="btn btn-outline-primary"
                            type="button"
                            :disabled="currentPage <= 1 || loading"
                            @click="goToPage(currentPage - 1)"
                        >
                            <i class="bi bi-chevron-left me-1"></i>
                            Previous
                        </button>
                        <button
                            class="btn btn-outline-primary"
                            type="button"
                            :disabled="currentPage >= lastPage || loading"
                            @click="goToPage(currentPage + 1)"
                        >
                            Next
                            <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </nav>
            </div>

            <div v-else class="py-5 text-center text-muted">
                <i class="bi bi-ticket-perforated display-1 d-block mb-3"></i>
                <h2 class="h4 fw-bold">No audiences yet</h2>
                <p class="mb-4">Submitted concert registrations will appear here.</p>
                <router-link class="btn btn-primary" to="/trms/concert/select">
                    <i class="bi bi-person-plus me-2"></i>
                    Register First Audience
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
import { useTrmsStore } from '../../stores/api'

export default {
    name: 'ConcertAudiences',
    setup() {
        return {
            trmsStore: useTrmsStore()
        }
    },
    data() {
        return {
            loading: false,
            errorMessage: '',
            page: 1,
            perPage: 10
        }
    },
    computed: {
        audiences() {
            return this.trmsStore.concertAudiences
        },
        total() {
            return this.trmsStore.concertAudiencesMeta.total
        },
        currentPage() {
            return this.trmsStore.concertAudiencesMeta.currentPage
        },
        lastPage() {
            return this.trmsStore.concertAudiencesMeta.lastPage
        }
    },
    mounted() {
        this.fetchAudiences()
    },
    methods: {
        async fetchAudiences() {
            this.loading = true
            this.errorMessage = ''

            try {
                await this.trmsStore.fetchConcertAudiences({
                    page: this.page,
                    perPage: this.perPage
                })
            } catch (error) {
                this.errorMessage = error.message || 'Unable to load audiences.'
            } finally {
                this.loading = false
            }
        },
        formatDate(value) {
            if (!value) {
                return '-'
            }

            return new Intl.DateTimeFormat('en', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }).format(new Date(value))
        },
        goToPage(page) {
            if (page < 1 || page > this.lastPage || this.loading) {
                return
            }

            this.page = page
            this.fetchAudiences()
        }
    }
}
</script>
