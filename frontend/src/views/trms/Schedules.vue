<template>
    <div class="fade-in-up">
        <div class="content-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Schedules</p>
                    <h1 class="display-4 fw-bold mb-3">Schedule Calendar</h1>
                    <p class="lead text-muted mb-0">
                        Manage your music studio classes, lessons, and events in one place.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="bg-dark text-white rounded p-4 h-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-calendar3 display-6 text-warning"></i>
                            <div>
                                <div class="fw-bold">Organize</div>
                                <div class="text-white-50 small">Lessons, practices & concerts</div>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg" @click="openAddModal" v-if="canAddSchedule">
                            <i class="bi bi-plus-lg me-2"></i> Add Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button class="btn btn-outline-light" @click="prevMonth">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <h2 class="mb-0 fw-bold">{{ monthYearLabel }}</h2>
                <button class="btn btn-outline-light" @click="nextMonth">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <div class="calendar-grid">
                <div class="calendar-header-row row g-0">
                    <div class="col" v-for="dayName in dayNames" :key="dayName">
                        <div class="calendar-header">{{ dayName }}</div>
                    </div>
                </div>
                <div class="calendar-body">
                    <div class="calendar-week row g-0" v-for="week in calendarWeeks" :key="week.key">
                        <div class="col calendar-day-cell"
                             v-for="day in week.days"
                             :key="day.dateKey"
                             :class="{
                                 'other-month': !day.isCurrentMonth,
                                 'today': day.isToday,
                                 'has-schedules': getSchedulesForDate(day.dateKey).length > 0
                             }"
                             @click="day.isCurrentMonth ? openDayModal(day.dateKey) : null">
                            <div class="day-number">{{ day.dayNumber }}</div>
                            <div class="schedule-chips">
                                <span class="schedule-chip"
                                      v-for="schedule in getSchedulesForDate(day.dateKey).slice(0, 3)"
                                      :key="schedule.id"
                                      @click.stop="openEditModal(schedule)">
                                    {{ schedule.title }}
                                </span>
                                <span class="more-schedules"
                                      v-if="getSchedulesForDate(day.dateKey).length > 3">
                                    +{{ getSchedulesForDate(day.dateKey).length - 3 }} more
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ScheduleFormModal
            ref="scheduleFormModal"
            :loading="loading"
            :success-message="successMessage"
            :error-message="errorMessage"
            @submit="submitSchedule"
            @delete="deleteSchedule"
        />

        <div class="modal fade" id="scheduleDetailModal" tabindex="-1" ref="scheduleDetailModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ selectedSchedule ? selectedSchedule.title : 'Schedule Detail' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" v-if="selectedSchedule">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="badge" :class="typeBadgeClass(selectedSchedule.type)">{{ typeLabel(selectedSchedule.type) }}</span>
                            <span class="text-muted">{{ formatDate(selectedSchedule.date) }}</span>
                        </div>
                        <p class="mb-3">{{ selectedSchedule.description || 'No description provided.' }}</p>
                        <div class="d-flex gap-4 text-muted small">
                            <span><i class="bi bi-clock me-1"></i> {{ selectedSchedule.start_time }} - {{ selectedSchedule.end_time }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-primary" @click="openEditFromDetail">
                            <i class="bi bi-pencil me-2"></i> Edit
                        </button>
                        <button class="btn btn-outline-danger" @click="deleteFromDetail">
                            <i class="bi bi-trash me-2"></i> Delete
                        </button>
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import { mapState, mapActions } from 'pinia'
import { useTrmsStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'
import ScheduleFormModal from '../../components/trms/ScheduleFormModal.vue'

export default {
    name: 'Schedules',
    components: {
        ScheduleFormModal
    },
    computed: {
        ...mapState(useTrmsStore, ['schedules']),
        canAddSchedule() {
            const authStore = useAuthStore()
            const role = authStore.user?.role?.toLowerCase()
            return role === 'admin' || role === 'manager'
        },
        monthYearLabel() {
            return this.currentMonth.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long'
            })
        },
        dayNames() {
            return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
        },
        calendarWeeks() {
            const weeks = []
            const year = this.currentMonth.getFullYear()
            const month = this.currentMonth.getMonth()
            const firstDay = new Date(year, month, 1)
            const lastDay = new Date(year, month + 1, 0)
            const startDayOfWeek = firstDay.getDay()
            const daysInMonth = lastDay.getDate()

            const prevMonthLastDay = new Date(year, month, 0).getDate()

            let dayCounter = 1
            let nextMonthDayCounter = 1

            const totalWeeks = Math.ceil((startDayOfWeek + daysInMonth) / 7)

            for (let w = 0; w < totalWeeks; w++) {
                const days = []
                for (let d = 0; d < 7; d++) {
                    const dayIndex = w * 7 + d
                    let dayNumber
                    let dateKey
                    let isCurrentMonth = true
                    let isToday = false

                    if (dayIndex < startDayOfWeek) {
                        dayNumber = prevMonthLastDay - startDayOfWeek + dayIndex + 1
                        const prevMonth = month === 0 ? 11 : month - 1
                        const prevYear = month === 0 ? year - 1 : year
                        dateKey = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-${String(dayNumber).padStart(2, '0')}`
                        isCurrentMonth = false
                    } else if (dayCounter > daysInMonth) {
                        dayNumber = nextMonthDayCounter
                        nextMonthDayCounter++
                        const nextMonth = month === 11 ? 0 : month + 1
                        const nextYear = month === 11 ? year + 1 : year
                        dateKey = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(dayNumber).padStart(2, '0')}`
                        isCurrentMonth = false
                    } else {
                        dayNumber = dayCounter
                        dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayNumber).padStart(2, '0')}`
                        dayCounter++
                        const today = new Date()
                        isToday = today.getDate() === dayNumber && today.getMonth() === month && today.getFullYear() === year
                    }

                    days.push({
                        dayNumber,
                        dateKey,
                        isCurrentMonth,
                        isToday
                    })
                }
                weeks.push({ key: `week-${w}`, days })
            }

            return weeks
        },
    },
    data() {
        return {
            currentMonth: new Date(),
            loading: false,
            successMessage: '',
            errorMessage: '',
            selectedSchedule: null,
            scheduleDetailModalInstance: null
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchSchedules()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useTrmsStore, { storeDeleteSchedule: 'deleteSchedule', fetchSchedules: 'fetchSchedules', createSchedule: 'createSchedule', updateSchedule: 'updateSchedule' }),
        prevMonth() {
            this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() - 1, 1)
        },
        nextMonth() {
            this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + 1, 1)
        },
        getSchedulesForDate(dateKey) {
            return this.schedules.filter(s => s.date === dateKey).sort((a, b) => a.start_time.localeCompare(b.start_time))
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
        openAddModal() {
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openAdd()
        },
        openDayModal(dateKey) {
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openDay(dateKey)
        },
        openEditModal(schedule) {
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openEdit(schedule)
        },
        openEditFromDetail() {
            this.hideDetailModal()
            setTimeout(() => {
                this.openEditModal(this.selectedSchedule)
            }, 300)
        },
        async submitSchedule(payload) {
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
                await this.fetchSchedules()
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
            if (!confirm('Are you sure you want to delete this schedule?')) return

            this.loading = true
            this.clearFormMessages()
            try {
                await this.storeDeleteSchedule(scheduleId)
                this.successMessage = 'Schedule deleted successfully.'
                await this.fetchSchedules()
                setTimeout(() => this.$refs.scheduleFormModal.hide(), 800)
            } catch (error) {
                this.errorMessage = error.message || 'Unable to delete schedule.'
            } finally {
                this.loading = false
            }
        },
        async deleteFromDetail() {
            if (!this.selectedSchedule) return
            if (!confirm('Are you sure you want to delete this schedule?')) return

            this.loading = true
            this.clearFormMessages()
            try {
                await this.storeDeleteSchedule(this.selectedSchedule.id)
                this.successMessage = 'Schedule deleted successfully.'
                await this.fetchSchedules()
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
            this.scheduleDetailModalInstance = new Modal(el)
            this.scheduleDetailModalInstance.show()
        },
        hideDetailModal() {
            const modal = this.scheduleDetailModalInstance
            if (modal) {
                modal.hide()
            }
        }
    }
}
</script>

<style scoped>
.calendar-grid {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.calendar-header-row {
    margin-bottom: 0;
}

.calendar-header {
    text-align: center;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 0.75rem 0.5rem;
    color: var(--accent-color);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.calendar-day-cell {
    min-height: 100px;
    border: 1px solid var(--hairline-color);
    padding: 0.5rem;
    cursor: pointer;
    transition: background 0.2s ease;
    background: rgba(255, 253, 248, 0.6);
}

.calendar-day-cell:hover {
    background: rgba(200, 164, 93, 0.1);
}

.calendar-day-cell.other-month {
    background: rgba(111, 106, 97, 0.08);
    color: var(--muted-color);
}

.calendar-day-cell.today .day-number {
    background: var(--accent-color);
    color: #fff;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.calendar-day-cell.has-schedules {
    background: rgba(200, 164, 93, 0.06);
}

.day-number {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
    display: inline-flex;
}

.schedule-chips {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.schedule-chip {
    font-size: 0.75rem;
    padding: 0.2rem 0.45rem;
    border-radius: 4px;
    background: rgba(127, 36, 50, 0.12);
    color: var(--accent-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background 0.2s ease;
}

.schedule-chip:hover {
    background: rgba(127, 36, 50, 0.24);
}

.more-schedules {
    font-size: 0.75rem;
    color: var(--muted-color);
    padding: 0.2rem 0.45rem;
}

@media (max-width: 767.98px) {
    .calendar-day-cell {
        min-height: 70px;
        padding: 0.35rem;
    }
    .schedule-chip {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
    }
    .day-number {
        font-size: 0.8rem;
    }
}
</style>
