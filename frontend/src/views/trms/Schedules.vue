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
                        <button class="btn btn-primary btn-lg" @click="openAddModal" v-if="canManageSchedule">
                            <i class="bi bi-plus-lg me-2"></i> Add Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="calendar-toolbar">
                <button class="btn btn-outline-secondary calendar-nav-btn" @click="prevMonth" aria-label="Previous month">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <h2 class="calendar-month-label">{{ monthYearLabel }}</h2>
                <button class="btn btn-outline-secondary calendar-nav-btn" @click="goToToday" aria-label="Go to today">
                    <i class="bi bi-calendar-check"></i>
                </button>
                <button class="btn btn-outline-secondary calendar-nav-btn" @click="nextMonth" aria-label="Next month">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <div class="calendar-scroll">
                <div class="calendar-grid">
                    <div class="calendar-weekdays">
                        <div class="calendar-header" v-for="(dayName, index) in dayNames" :key="dayName">
                            <span class="calendar-header-full">{{ dayName }}</span>
                            <span class="calendar-header-short">{{ dayNamesShort[index] }}</span>
                        </div>
                    </div>
                    <div class="calendar-body">
                        <div class="calendar-week" v-for="week in calendarWeeks" :key="week.key">
                            <div class="calendar-day-cell"
                                 v-for="day in week.days"
                                 :key="day.dateKey"
                                 :class="{
                                     'other-month': !day.isCurrentMonth,
                                     'today': day.isToday,
                                     'has-schedules': getSchedulesForDate(day.dateKey).length > 0,
                                     'read-only': !canManageSchedule
                                 }"
                                 @click="handleDayClick(day)">
                                <div class="day-number-wrap">
                                    <span class="day-number">{{ day.dayNumber }}</span>
                                </div>
                                <div class="schedule-dots" v-if="getSchedulesForDate(day.dateKey).length > 0">
                                    <span class="schedule-dot"
                                          v-for="schedule in getSchedulesForDate(day.dateKey).slice(0, 3)"
                                          :key="schedule.id"
                                          :class="scheduleDotClass(schedule.type)"
                                          @click.stop="handleScheduleClick(schedule)"></span>
                                    <span class="schedule-dot schedule-dot-more"
                                          v-if="getSchedulesForDate(day.dateKey).length > 3"></span>
                                </div>
                                <div class="schedule-chips">
                                    <span class="schedule-chip"
                                          v-for="schedule in getSchedulesForDate(day.dateKey).slice(0, 3)"
                                          :key="schedule.id"
                                          :class="{ 'schedule-chip-readonly': !canManageSchedule }"
                                          @click.stop="handleScheduleClick(schedule)">
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
        </div>

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
                            <h5 class="modal-title">{{ selectedSchedule ? selectedSchedule.title : 'Schedule Detail' }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" v-if="selectedSchedule">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="badge" :class="typeBadgeClass(selectedSchedule.type)">{{ typeLabel(selectedSchedule.type) }}</span>
                                <span class="text-muted">{{ formatDate(selectedSchedule.date) }}</span>
                            </div>
                            <p class="mb-3">{{ selectedSchedule.description || 'No description provided.' }}</p>
                            <div class="mb-3" v-if="selectedSchedule.venue">
                                <span class="text-muted small d-block mb-1">Venue</span>
                                <span><i class="bi bi-geo-alt me-1"></i>{{ selectedSchedule.venue }}</span>
                            </div>
                            <div class="mb-3" v-if="selectedSchedule.program_ids && selectedSchedule.program_ids.length > 0">
                                <span class="text-muted small d-block mb-1">Programs / Collaborating Groups</span>
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="progId in selectedSchedule.program_ids" :key="progId" class="badge bg-dark bg-opacity-50 border border-secondary border-opacity-25 text-uppercase">
                                        {{ getProgramName(progId) }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex gap-4 text-muted small">
                                <span><i class="bi bi-clock me-1"></i> {{ selectedSchedule.start_time }} - {{ selectedSchedule.end_time }}</span>
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
        canManageSchedule() {
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
        dayNamesShort() {
            return ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
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
        goToToday() {
            this.currentMonth = new Date()
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
        getProgramName(progId) {
            const map = {
                trms: 'TRMS',
                bms: 'BMS',
                jco: 'JCO',
                trcc: 'TRCC'
            }
            return map[progId] || progId.toUpperCase()
        },
        scheduleDotClass(type) {
            const map = {
                lesson: 'schedule-dot-lesson',
                practice: 'schedule-dot-practice',
                concert: 'schedule-dot-concert',
                exam: 'schedule-dot-exam',
                other: 'schedule-dot-other'
            }
            return map[type] || 'schedule-dot-other'
        },
        openAddModal() {
            if (!this.canManageSchedule) return
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openAdd()
        },
        handleDayClick(day) {
            if (!day.isCurrentMonth) return

            if (this.canManageSchedule) {
                this.openDayModal(day.dateKey)
                return
            }

            const schedules = this.getSchedulesForDate(day.dateKey)
            if (schedules.length > 0) {
                this.openDetailModal(schedules[0])
            }
        },
        handleScheduleClick(schedule) {
            if (this.canManageSchedule) {
                this.openEditModal(schedule)
                return
            }
            this.openDetailModal(schedule)
        },
        openDayModal(dateKey) {
            if (!this.canManageSchedule) return
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openDay(dateKey)
        },
        openEditModal(schedule) {
            if (!this.canManageSchedule) return
            this.clearFormMessages()
            this.$refs.scheduleFormModal.openEdit(schedule)
        },
        openDetailModal(schedule) {
            this.selectedSchedule = schedule
            this.showDetailModal()
        },
        openEditFromDetail() {
            if (!this.canManageSchedule) return
            this.hideDetailModal()
            setTimeout(() => {
                this.openEditModal(this.selectedSchedule)
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
            if (!this.canManageSchedule) return
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
            if (!this.canManageSchedule) return
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
.calendar-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.calendar-month-label {
    margin: 0;
    font-weight: 700;
    font-size: clamp(1.1rem, 3vw, 1.75rem);
    text-align: center;
    flex: 1;
    line-height: 1.3;
}

.calendar-nav-btn {
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.calendar-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.calendar-grid {
    display: flex;
    flex-direction: column;
    min-width: 280px;
}

.calendar-weekdays,
.calendar-week {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}

.calendar-header {
    text-align: center;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 0.65rem 0.25rem;
    color: var(--accent-color);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border-bottom: 2px solid rgba(127, 36, 50, 0.15);
}

.calendar-header-short {
    display: none;
}

.calendar-day-cell {
    min-height: 6.5rem;
    border: 1px solid var(--hairline-color);
    border-top: none;
    padding: 0.45rem 0.35rem 0.5rem;
    cursor: pointer;
    transition: background 0.2s ease, box-shadow 0.2s ease;
    background: rgba(255, 253, 248, 0.6);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.calendar-day-cell:hover {
    background: rgba(200, 164, 93, 0.1);
    z-index: 1;
    box-shadow: inset 0 0 0 1px rgba(200, 164, 93, 0.35);
}

.calendar-day-cell.other-month {
    background: rgba(111, 106, 97, 0.08);
    color: var(--muted-color);
    cursor: default;
}

.calendar-day-cell.other-month:hover {
    background: rgba(111, 106, 97, 0.08);
    box-shadow: none;
}

.calendar-day-cell.read-only {
    cursor: default;
}

.calendar-day-cell.read-only:hover {
    background: rgba(255, 253, 248, 0.6);
    box-shadow: none;
}

.calendar-day-cell.read-only.has-schedules {
    cursor: pointer;
}

.calendar-day-cell.read-only.has-schedules:hover {
    background: rgba(200, 164, 93, 0.1);
    box-shadow: inset 0 0 0 1px rgba(200, 164, 93, 0.25);
}

.calendar-day-cell.has-schedules {
    background: rgba(200, 164, 93, 0.06);
}

.day-number-wrap {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-bottom: 0.35rem;
}

.day-number {
    width: 1.75rem;
    height: 1.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.calendar-day-cell.today .day-number {
    background: var(--accent-color);
    color: #fff;
    border-radius: 50%;
}

.schedule-dots {
    display: none;
    justify-content: center;
    align-items: center;
    gap: 0.2rem;
    flex-wrap: wrap;
    width: 100%;
    margin-top: auto;
    padding-top: 0.15rem;
}

.schedule-dot {
    width: 0.4rem;
    height: 0.4rem;
    border-radius: 50%;
    flex-shrink: 0;
}

.schedule-dot-lesson { background: #0d6efd; }
.schedule-dot-practice { background: #198754; }
.schedule-dot-concert { background: var(--gold-color); }
.schedule-dot-exam { background: #dc3545; }
.schedule-dot-other { background: var(--muted-color); }
.schedule-dot-more { background: var(--muted-color); opacity: 0.55; }

.schedule-chips {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    width: 100%;
    margin-top: 0.15rem;
}

.schedule-chip {
    font-size: 0.72rem;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    background: rgba(127, 36, 50, 0.12);
    color: var(--accent-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background 0.2s ease;
    text-align: left;
}

.schedule-chip:hover {
    background: rgba(127, 36, 50, 0.24);
}

.schedule-chip-readonly {
    cursor: pointer;
}

.schedule-chip-readonly:hover {
    background: rgba(127, 36, 50, 0.18);
}

.more-schedules {
    font-size: 0.7rem;
    color: var(--muted-color);
    padding: 0.15rem 0.4rem;
    text-align: center;
}

@media (max-width: 767.98px) {
    .calendar-header-full {
        display: none;
    }

    .calendar-header-short {
        display: inline;
    }

    .calendar-header {
        font-size: 0.7rem;
        padding: 0.5rem 0.15rem;
    }

    .calendar-day-cell {
        min-height: 3.75rem;
        padding: 0.3rem 0.15rem 0.35rem;
    }

    .day-number {
        width: 1.5rem;
        height: 1.5rem;
        font-size: 0.78rem;
    }

    .schedule-dots {
        display: flex;
    }

    .schedule-chips {
        display: none;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    .calendar-day-cell {
        min-height: 5.5rem;
    }

    .schedule-chip {
        font-size: 0.68rem;
        padding: 0.15rem 0.3rem;
    }
}

@media (min-width: 992px) {
    .calendar-day-cell {
        min-height: 7rem;
    }
}

:deep(.schedule-detail-modal) {
    background: var(--surface-color);
}

:deep(.schedule-detail-modal .modal-header) {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.16), rgba(200, 164, 93, 0.08));
    border-bottom: 1px solid var(--hairline-color);
}
</style>
