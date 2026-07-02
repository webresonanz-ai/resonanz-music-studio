<template>
    <div class="fade-in-up">
        <!-- ══ PAGE HEADER ══════════════════════════════════════════════ -->
        <div class="content-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase text-primary fw-bold small mb-2">Batavia Madrigal Singers</p>
                    <h1 class="display-4 fw-bold mb-3">Upcoming Events</h1>
                    <p class="lead text-muted mb-0">
                        Stay updated with the Batavia Madrigal Singers rehearsals, concerts, and scheduled performances.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="bg-dark text-white rounded p-4 h-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-calendar2-week display-6 text-warning"></i>
                            <div>
                                <div class="fw-bold">BMS Schedule</div>
                                <div class="text-white-50 small">{{ filteredEvents.length }} upcoming schedule{{ filteredEvents.length === 1 ? '' : 's' }}</div>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg" @click="openAddModal" v-if="canManageSchedule">
                            <i class="bi bi-plus-lg me-2"></i> Add Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ FILTERS BAR ══════════════════════════════════════════════ -->
        <div class="content-card mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
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
                <div class="col-md-6 col-lg-8">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
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
            <div v-if="filteredEvents.length === 0" class="content-card py-5 text-center text-muted">
                <i class="bi bi-calendar-x display-2 mb-3 d-block text-secondary"></i>
                <h3 class="fw-bold mb-2">No upcoming events</h3>
                <p class="mb-0">Please check back later or modify your search filters.</p>
            </div>

            <div v-else class="row g-4">
                <div class="col-12 col-xl-6" v-for="event in filteredEvents" :key="event.id">
                    <div class="event-premium-card h-100" @click="openDetailModal(event)">
                        <div class="d-flex gap-4 align-items-start">
                            <!-- Calendar Sheet Widget -->
                            <div class="calendar-widget-sheet flex-shrink-0">
                                <div class="calendar-widget-header text-uppercase">
                                    {{ getMonthAbbreviation(event.date) }}
                                </div>
                                <div class="calendar-widget-body">
                                    <div class="calendar-widget-day">{{ getDayNumber(event.date) }}</div>
                                    <div class="calendar-widget-weekday text-uppercase text-muted">{{ getWeekdayAbbreviation(event.date) }}</div>
                                </div>
                            </div>

                            <!-- Event Details -->
                            <div class="flex-grow-1 min-width-0">
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
                            <div class="d-flex align-items-center gap-3 mb-3">
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

                            <div class="d-flex gap-4 text-muted small">
                                <span><i class="bi bi-clock me-1"></i> {{ selectedEvent.start_time }} - {{ selectedEvent.end_time }}</span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button v-if="canManageSchedule" class="btn btn-outline-primary" @click="openEditFromDetail">
                                <i class="bi bi-pencil me-2"></i> Edit
                            </button>
                            <button v-if="canManageSchedule" class="btn btn-outline-danger" @click="deleteFromDetail">
                                <i class="bi bi-trash me-2"></i> Delete
                            </button>
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
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
            
            return this.events
                .filter(event => {
                    // 1. Only show upcoming events (date >= today)
                    if (event.date < today) return false

                    // 2. Filter by schedule type pill
                    if (this.activeTypeFilter && event.type !== this.activeTypeFilter) return false

                    // 3. Filter by search query (title or description)
                    if (this.searchQuery) {
                        const q = this.searchQuery.toLowerCase()
                        const titleMatch = event.title?.toLowerCase().includes(q)
                        const descMatch = event.description?.toLowerCase().includes(q)
                        if (!titleMatch && !descMatch) return false
                    }

                    return true
                })
                .sort((a, b) => {
                    // Sort soonest first
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
            const [year, month, day] = dateStr.split('-').map(Number)
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
.filter-pill-btn {
    border-radius: 50px;
    padding: 0.35rem 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

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

/* Calendar Widget Badge */
.calendar-widget-sheet {
    width: 4.5rem;
    background: rgba(255, 253, 248, 0.9);
    border: 1px solid var(--hairline-color);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    text-align: center;
}

.calendar-widget-header {
    background: var(--accent-color);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0;
    letter-spacing: 0.05em;
}

.calendar-widget-body {
    padding: 0.4rem 0 0.5rem;
}

.calendar-widget-day {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-color);
    line-height: 1;
}

.calendar-widget-weekday {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    margin-top: 0.15rem;
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
    padding: 0.15rem 0.35rem;
    font-size: 0.75rem;
    border-radius: 4px;
}

:deep(.schedule-detail-modal) {
    background: var(--surface-color);
}

:deep(.schedule-detail-modal .modal-header) {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.16), rgba(200, 164, 93, 0.08));
    border-bottom: 1px solid var(--hairline-color);
}
</style>