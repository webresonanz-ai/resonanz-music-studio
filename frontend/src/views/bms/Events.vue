<template>
    <div class="fade-in-up">
        <!-- ══ PAGE HEADER ══════════════════════════════════════════════ -->
        <div class="content-card bg-dark mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-7">
                    <p class="text-uppercase text-warning fw-bold small mb-2">Batavia Madrigal Singers</p>
                    <h1 class="display-4 fw-bold mb-3 text-champagne">Upcoming Events</h1>
                    <p class="lead text-champagne-muted mb-0">
                        Stay updated with the Batavia Madrigal Singers rehearsals, concerts, and scheduled performances.
                    </p>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="schedule-header-card h-100">
                        <div class="schedule-header-inner">
                            <div class="d-flex align-items-center gap-3">
                                <div class="schedule-icon-circle flex-shrink-0">
                                    <i class="bi bi-calendar2-week display-6 text-warning"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-bold fs-5">BMS Schedule</div>
                                    <div class="text-white-50 small">{{ filteredEvents.length }} upcoming schedule{{ filteredEvents.length === 1 ? '' : 's' }}</div>
                                </div>
                            </div>
                            <button class="btn btn-gold btn-lg mt-3 mt-lg-0 w-100 w-lg-auto" @click="openAddModal" v-if="canManageSchedule">
                                <i class="bi bi-plus-lg me-2"></i> Add Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ FILTERS BAR ══════════════════════════════════════════════ -->
        <div class="content-card bg-dark mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="input-group search-group">
                        <span class="input-group-text bg-transparent border-end-0 border-secondary border-opacity-25 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input
                            type="text"
                            v-model="searchQuery"
                            class="form-control border-start-0 border-secondary border-opacity-25 bg-transparent"
                            placeholder="Search by title, description..."
                        >
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-8">
                    <div class="filter-pills-scroll d-flex flex-wrap gap-2">
                        <button
                            class="btn btn-sm filter-pill-btn"
                            :class="{ 'btn-primary': activeTypeFilter === '', 'btn-outline-secondary': activeTypeFilter !== '' }"
                            @click="activeTypeFilter = ''"
                        >
                            All Types
                        </button>
                        <button
                            v-for="type in eventTypes"
                            :key="type"
                            class="btn btn-sm filter-pill-btn"
                            :class="{ 'btn-primary': activeTypeFilter === type, 'btn-outline-secondary': activeTypeFilter !== type }"
                            @click="activeTypeFilter = type"
                        >
                            {{ typeLabel(type) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ EVENTS LIST / CARD GRID ══════════════════════════════════ -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="text-muted mt-3">Loading upcoming events...</div>
        </div>

        <div v-else>
            <div v-if="filteredEvents.length === 0" class="content-card bg-dark py-5 text-center text-champagne-muted">
                <div class="empty-state-icon">
                    <i class="bi bi-calendar-x display-2 mb-3 text-secondary"></i>
                </div>
                <h3 class="fw-bold mb-2 text-champagne">No upcoming events</h3>
                <p class="mb-0 text-champagne-muted">Please check back later or modify your search filters.</p>
            </div>

            <div v-else class="row g-4">
                <div class="col-12 col-xl-6" v-for="event in filteredEvents" :key="event.id">
                    <div class="event-premium-card h-100" @click="openDetailModal(event)">
                        <div class="event-card-inner d-flex flex-column flex-sm-row gap-3 gap-sm-4 align-items-start">
                            <!-- Calendar Sheet Widget -->
                            <div class="calendar-widget-sheet flex-shrink-0 mx-auto mx-sm-0">
                                <div class="calendar-widget-header text-uppercase">
                                    {{ getMonthAbbreviation(event.date) }}
                                </div>
                                <div class="calendar-widget-body">
                                    <div class="calendar-widget-day">{{ getDayNumber(event.date) }}</div>
                                    <div class="calendar-widget-weekday text-uppercase text-muted">{{ getWeekdayAbbreviation(event.date) }}</div>
                                </div>
                            </div>

                            <!-- Event Details -->
                            <div class="flex-grow-1 min-width-0 event-details-col">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge" :class="typeBadgeClass(event.type)">{{ typeLabel(event.type) }}</span>
                                    <span class="text-muted small">
                                        <i class="bi bi-clock me-1"></i>
                                        {{ formatTime(event.start_time) }} - {{ formatTime(event.end_time) }}
                                    </span>
                                </div>
                                <h3 class="h4 fw-bold mb-2 text-truncate-custom">{{ event.title }}</h3>
                                <p class="text-muted small mb-3 text-description-truncate">
                                    {{ event.description || 'No description provided.' }}
                                </p>

                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-auto pt-2 border-top border-secondary border-opacity-10">
                                    <!-- Collaborators list -->
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        <span class="text-muted small me-1">Programs:</span>
                                        <span
                                            v-for="progId in event.program_ids"
                                            :key="progId"
                                            class="badge bg-secondary text-uppercase small-badge"
                                        >
                                            {{ getProgramName(progId) }}
                                        </span>
                                    </div>

                                    <!-- Actions (If manager/admin) -->
                                    <div class="d-flex gap-1" v-if="canManageSchedule" @click.stop>
                                        <button class="btn btn-outline-primary btn-xs" @click="openEditModal(event)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-xs" @click="deleteSchedule(event.id)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MODALS ═══════════════════════════════════════════════════ -->
        <ScheduleFormModal
            ref="scheduleFormModal"
            :loading="loading"
            :success-message="successMessage"
            :error-message="errorMessage"
            @submit="submitSchedule"
            @delete="deleteSchedule"
        />

        <Teleport to="body">
            <div class="modal fade" id="scheduleDetailModal" tabindex="-1" ref="scheduleDetailModal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content schedule-detail-modal">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ selectedEvent ? selectedEvent.title : 'Event Detail' }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" v-if="selectedEvent">
                            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                <span class="badge" :class="typeBadgeClass(selectedEvent.type)">{{ typeLabel(selectedEvent.type) }}</span>
                                <span class="text-muted">{{ formatDate(selectedEvent.date) }}</span>
                            </div>
                            <p class="mb-3">{{ selectedEvent.description || 'No description provided.' }}</p>

                            <div class="mb-3" v-if="selectedEvent.program_ids && selectedEvent.program_ids.length > 0">
                                <span class="text-muted small d-block mb-1">Programs / Collaborating Groups</span>
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="progId in selectedEvent.program_ids" :key="progId" class="badge bg-dark bg-opacity-50 border border-secondary border-opacity-25 text-uppercase">
                                        {{ getProgramName(progId) }}
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex gap-4 text-muted small flex-wrap">
                                <span><i class="bi bi-clock me-1"></i> {{ formatTime(selectedEvent.start_time) }} - {{ formatTime(selectedEvent.end_time) }}</span>
                            </div>
                        </div>
                        <div class="modal-footer flex-wrap gap-2">
                            <button v-if="canManageSchedule" class="btn btn-outline-primary" @click="openEditFromDetail">
                                <i class="bi bi-pencil me-2"></i> Edit
                            </button>
                            <button v-if="canManageSchedule" class="btn btn-outline-danger" @click="deleteFromDetail">
                                <i class="bi bi-trash me-2"></i> Delete
                            </button>
                            <button class="btn btn-secondary ms-auto" type="button" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import { mapState, mapActions } from 'pinia'
import { useBmsStore, useTrmsStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'
import ScheduleFormModal from '../../components/trms/ScheduleFormModal.vue'

export default {
    name: 'Events',
    components: {
        ScheduleFormModal
    },
    data() {
        return {
            loading: false,
            successMessage: '',
            errorMessage: '',
            searchQuery: '',
            activeTypeFilter: '',
            selectedEvent: null,
            scheduleDetailModalInstance: null,
            eventTypes: ['concert', 'practice', 'lesson', 'exam', 'other']
        }
    },
    computed: {
        ...mapState(useBmsStore, ['events']),
        canManageSchedule() {
            const authStore = useAuthStore()
            const role = authStore.user?.role?.toLowerCase()
            return role === 'admin' || role === 'manager'
        },
        filteredEvents() {
            if (!this.events || !Array.isArray(this.events)) return []

            const today = new Date().toISOString().split('T')[0]
            const internalTypes = ['practice', 'rehearsal']

            return this.events
                .filter(event => {
                    if (event.date < today) return false
                    // Hide BMS-only practice/rehearsal schedules — those belong in Attendance only
                    const isBms = Array.isArray(event.program_ids) && event.program_ids.includes('bms')
                    if (isBms && internalTypes.includes(event.type)) return false
                    if (this.activeTypeFilter && event.type !== this.activeTypeFilter) return false
                    if (this.searchQuery) {
                        const q = this.searchQuery.toLowerCase()
                        const titleMatch = event.title?.toLowerCase().includes(q)
                        const descMatch = event.description?.toLowerCase().includes(q)
                        if (!titleMatch && !descMatch) return false
                    }
                    return true
                })
                .sort((a, b) => {
                    const dateCompare = a.date.localeCompare(b.date)
                    return dateCompare || a.start_time.localeCompare(b.start_time)
                })
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchEvents()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useBmsStore, ['fetchEvents']),
        ...mapActions(useTrmsStore, { storeDeleteSchedule: 'deleteSchedule', createSchedule: 'createSchedule', updateSchedule: 'updateSchedule' }),

        formatTime(value) {
            return String(value || '').slice(0, 5)
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
        getMonthAbbreviation(dateStr) {
            if (!dateStr) return ''
            const [year, month, day] = dateStr.split('-').map(Number)
            return new Date(year, month - 1, day).toLocaleDateString('id-ID', { month: 'short' }).toUpperCase()
        },
        getDayNumber(dateStr) {
            if (!dateStr) return ''
            const [, , day] = dateStr.split('-').map(Number)
            return day
        },
        getWeekdayAbbreviation(dateStr) {
            if (!dateStr) return ''
            const [year, month, day] = dateStr.split('-').map(Number)
            return new Date(year, month - 1, day).toLocaleDateString('id-ID', { weekday: 'short' }).toUpperCase()
        },
        typeBadgeClass(type) {
            const map = {
                lesson: 'bg-primary',
                practice: 'bg-success',
                concert: 'bg-warning text-dark',
                exam: 'bg-danger',
                other: 'bg-secondary'
            }
            return map[type] || 'bg-secondary'
        },
        typeLabel(type) {
            const map = {
                lesson: 'Lesson',
                practice: 'Practice',
                concert: 'Concert',
                exam: 'Exam',
                other: 'Other'
            }
            return map[type] || type
        },
        getProgramName(progId) {
            const map = {
                trms: 'TRMS',
                bms: 'BMS',
                jco: 'JCO',
                trcc: 'TRCC'
            }
            return map[progId] || progId.toUpperCase()
        },
        openAddModal() {
            if (!this.canManageSchedule) return
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openAdd('bms')
        },
        openEditModal(event) {
            if (!this.canManageSchedule) return
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openEdit(event)
        },
        openDetailModal(event) {
            this.selectedEvent = event
            this.showDetailModal()
        },
        openEditFromDetail() {
            if (!this.canManageSchedule) return
            this.hideDetailModal()
            setTimeout(() => {
                this.openEditModal(this.selectedEvent)
            }, 300)
        },
        async submitSchedule(payload) {
            if (!this.canManageSchedule) return
            this.loading = true
            this.clearFormMessages()
            try {
                if (payload.mode === 'edit') {
                    await this.updateSchedule(payload.scheduleId, payload.data)
                    this.successMessage = 'Schedule updated successfully.'
                } else {
                    await this.createSchedule(payload.data)
                    this.successMessage = 'Schedule added successfully.'
                }
                await this.fetchEvents()
                if (payload.mode === 'add') {
                    setTimeout(() => this.$refs.scheduleFormModal.hide(), 800)
                }
            } catch (error) {
                this.errorMessage = error.message || 'Unable to save schedule.'
            } finally {
                this.loading = false
            }
        },
        async deleteSchedule(scheduleId) {
            if (!this.canManageSchedule) return
            if (!confirm('Are you sure you want to delete this schedule?')) return

            this.loading = true
            this.clearFormMessages()
            try {
                await this.storeDeleteSchedule(scheduleId)
                this.successMessage = 'Schedule deleted successfully.'
                await this.fetchEvents()
                setTimeout(() => this.$refs.scheduleFormModal.hide(), 800)
            } catch (error) {
                this.errorMessage = error.message || 'Unable to delete schedule.'
            } finally {
                this.loading = false
            }
        },
        async deleteFromDetail() {
            if (!this.canManageSchedule) return
            if (!this.selectedEvent) return
            if (!confirm('Are you sure you want to delete this schedule?')) return

            this.loading = true
            this.clearFormMessages()
            try {
                await this.storeDeleteSchedule(this.selectedEvent.id)
                this.successMessage = 'Schedule deleted successfully.'
                await this.fetchEvents()
                this.hideDetailModal()
            } catch (error) {
                this.errorMessage = error.message || 'Unable to delete schedule.'
            } finally {
                this.loading = false
            }
        },
        clearFormMessages() {
            this.successMessage = ''
            this.errorMessage = ''
        },
        showDetailModal() {
            const el = this.$refs.scheduleDetailModal
            if (!el) return
            this.scheduleDetailModalInstance = Modal.getOrCreateInstance(el)
            this.scheduleDetailModalInstance.show()
        },
        hideDetailModal() {
            if (this.scheduleDetailModalInstance) {
                this.scheduleDetailModalInstance.hide()
            }
        }
    },
    beforeUnmount() {
        if (this.scheduleDetailModalInstance) {
            this.scheduleDetailModalInstance.dispose()
        }
    }
}
</script>

<style scoped>
/* ══ Dark theme overrides ════════════════════════════════════════ */
.content-card.bg-dark {
    --surface-color: rgba(234, 220, 194, 0.04);
    --hairline-color: rgba(234, 220, 194, 0.08);
    --text-color: rgba(234, 220, 194, 0.85);
    --muted-color: rgba(234, 220, 194, 0.45);
    --ink-color: rgba(234, 220, 194, 0.92);
}

.search-group {
    border-color: rgba(234, 220, 194, 0.12) !important;
    background: rgba(234, 220, 194, 0.03) !important;
}

.search-group .form-control {
    color: rgba(234, 220, 194, 0.75) !important;
}

.search-group .form-control::placeholder {
    color: rgba(234, 220, 194, 0.3) !important;
}

.search-group .input-group-text {
    color: rgba(234, 220, 194, 0.4) !important;
}

.filter-pill-btn.btn-outline-secondary {
    border-color: rgba(234, 220, 194, 0.15) !important;
    color: rgba(234, 220, 194, 0.6) !important;
}

.filter-pill-btn.btn-outline-secondary:hover {
    border-color: rgba(200, 164, 93, 0.4) !important;
    color: #c8a45d !important;
    background: rgba(200, 164, 93, 0.08) !important;
}

.event-premium-card {
    background: rgba(234, 220, 194, 0.03) !important;
    border-color: rgba(234, 220, 194, 0.08) !important;
}

.event-premium-card:hover {
    border-color: rgba(200, 164, 93, 0.35) !important;
}

.event-premium-card h3 {
    color: rgba(234, 220, 194, 0.85) !important;
}

.event-premium-card .text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.calendar-widget-sheet {
    background: rgba(26, 31, 48, 0.95) !important;
    border-color: rgba(234, 220, 194, 0.1) !important;
}

.calendar-widget-header {
    background: #7f2432 !important;
    color: #fff !important;
}

.calendar-widget-day {
    color: rgba(234, 220, 194, 0.9) !important;
}

.calendar-widget-weekday {
    color: rgba(234, 220, 194, 0.4) !important;
}

.event-premium-card .border-top {
    border-top-color: rgba(234, 220, 194, 0.06) !important;
}

.event-premium-card .btn-outline-primary {
    border-color: rgba(200, 164, 93, 0.25) !important;
    color: #c8a45d !important;
}

.event-premium-card .btn-outline-primary:hover {
    background: rgba(200, 164, 93, 0.1) !important;
    border-color: rgba(200, 164, 93, 0.4) !important;
}

.event-premium-card .btn-outline-danger {
    border-color: rgba(220, 53, 69, 0.25) !important;
}

.schedule-detail-modal {
    background: #1a1f30 !important;
    border: 1px solid rgba(234, 220, 194, 0.12) !important;
}

.schedule-detail-modal .modal-header {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.2), rgba(200, 164, 93, 0.08)) !important;
    border-bottom: 1px solid rgba(234, 220, 194, 0.08) !important;
}

.schedule-detail-modal .modal-header .modal-title {
    color: rgba(234, 220, 194, 0.85) !important;
}

.schedule-detail-modal .modal-body {
    color: rgba(234, 220, 194, 0.7) !important;
}

.schedule-detail-modal .modal-body .text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.schedule-detail-modal .modal-footer {
    border-top: 1px solid rgba(234, 220, 194, 0.08) !important;
}

.schedule-detail-modal .btn-close-white {
    filter: brightness(0) invert(0.8) !important;
}

.schedule-detail-modal .btn-secondary {
    background: rgba(234, 220, 194, 0.08) !important;
    border-color: rgba(234, 220, 194, 0.12) !important;
    color: rgba(234, 220, 194, 0.7) !important;
}

.empty-state-icon {
    background: rgba(234, 220, 194, 0.06) !important;
}

.loading-state div.text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

/* ══ RESPONSIVE HEADER CARD ═══════════════════════════════════════ */
.schedule-header-card {
    background: var(--primary-color);
    border: 1px solid rgba(234, 220, 194, 0.16);
    border-radius: var(--radius-md);
    box-shadow: 0 8px 24px rgba(13, 13, 18, 0.18);
    overflow: hidden;
}

.schedule-header-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent-color), var(--gold-color));
    opacity: 0;
    transition: opacity 0.24s ease;
}

.schedule-header-card:hover::before {
    opacity: 1;
}

.schedule-header-inner {
    position: relative;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, rgba(200, 164, 93, 0.12), transparent 40%);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.schedule-icon-circle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(234, 220, 194, 0.18);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

/* ══ FILTER PILLS ══════════════════════════════════════════════════ */
.filter-pills-scroll {
    overflow-x: auto;
    padding-bottom: 0.35rem;
    scrollbar-width: thin;
    scrollbar-color: var(--gold-color) transparent;
}

.filter-pills-scroll::-webkit-scrollbar {
    height: 4px;
}

.filter-pills-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.filter-pills-scroll::-webkit-scrollbar-thumb {
    background: var(--gold-color);
    border-radius: 999px;
}

.filter-pill-btn {
    border-radius: 50px;
    padding: 0.4rem 1.1rem;
    font-weight: 600;
    font-size: 0.82rem;
    letter-spacing: 0.01em;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.filter-pill-btn:hover {
    transform: translateY(-1px);
}

.search-group {
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid var(--hairline-color);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-group:focus-within {
    border-color: var(--gold-color);
    box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.12);
}

/* ══ EVENT CARD ═══════════════════════════════════════════════════ */
.event-premium-card {
    background: var(--surface-color);
    border: 1px solid var(--hairline-color);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.event-premium-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-soft);
    border-color: rgba(200, 164, 93, 0.4);
}

.event-card-inner {
    align-items: stretch;
}

.event-details-col {
    display: flex;
    flex-direction: column;
}

/* Calendar Widget Badge */
.calendar-widget-sheet {
    width: 5rem;
    background: rgba(255, 253, 248, 0.95);
    border: 1px solid var(--hairline-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    text-align: center;
    align-self: flex-start;
}

.calendar-widget-header {
    background: var(--accent-color);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0;
    letter-spacing: 0.08em;
}

.calendar-widget-body {
    padding: 0.5rem 0 0.6rem;
}

.calendar-widget-day {
    font-size: 1.9rem;
    font-weight: 800;
    color: var(--text-color);
    line-height: 1;
}

.calendar-widget-weekday {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    margin-top: 0.2rem;
}

.text-truncate-custom {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.text-description-truncate {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 2.25rem;
}

.small-badge {
    font-size: 0.65rem;
    padding: 0.15rem 0.35rem;
    border-radius: 3px;
}

.btn-xs {
    padding: 0.2rem 0.4rem;
    font-size: 0.78rem;
    border-radius: 6px;
    line-height: 1;
}

/* Empty state */
.empty-state-icon {
    display: inline-block;
    padding: 1rem;
    border-radius: 50%;
    background: rgba(108, 117, 125, 0.08);
    margin-bottom: 0.5rem;
}

:deep(.schedule-detail-modal) {
    background: var(--surface-color);
}

:deep(.schedule-detail-modal .modal-header) {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.16), rgba(200, 164, 93, 0.08));
    border-bottom: 1px solid var(--hairline-color);
}

/* ══ RESPONSIVE BREAKPOINTS ═══════════════════════════════════════ */
@media (max-width: 991.98px) {
    .event-premium-card {
        padding: 1.25rem;
    }

    .calendar-widget-sheet {
        width: 4.75rem;
    }

    .calendar-widget-day {
        font-size: 1.7rem;
    }

    .schedule-header-inner {
        padding: 1rem 1.25rem;
    }
}

@media (max-width: 575.98px) {
    .event-premium-card {
        padding: 1rem;
    }

    .calendar-widget-sheet {
        width: 4.5rem;
        align-self: center;
    }

    .calendar-widget-day {
        font-size: 1.5rem;
    }

    .calendar-widget-weekday {
        font-size: 0.6rem;
    }

    .calendar-widget-header {
        font-size: 0.65rem;
    }

    .filter-pill-btn {
        padding: 0.3rem 0.85rem;
        font-size: 0.78rem;
    }

    .btn-lg {
        --bs-btn-padding-y: 0.55rem;
        --bs-btn-padding-x: 0.75rem;
        --bs-btn-font-size: 0.95rem;
    }

    .schedule-header-inner {
        padding: 0.9rem 1rem;
    }

    .schedule-icon-circle {
        width: 44px;
        height: 44px;
    }

    .schedule-icon-circle i {
        font-size: 1.4rem;
    }

    :deep(.schedule-detail-modal .modal-footer) {
        flex-direction: column;
    }

    :deep(.schedule-detail-modal .modal-footer .btn) {
        width: 100%;
    }

    :deep(.schedule-detail-modal .modal-footer .ms-auto) {
        margin-left: 0 !important;
    }
}

@media (max-width: 400px) {
    .calendar-widget-sheet {
        width: 4rem;
    }

    .calendar-widget-day {
        font-size: 1.3rem;
    }

    .event-premium-card {
        padding: 0.85rem;
    }
}
</style>
