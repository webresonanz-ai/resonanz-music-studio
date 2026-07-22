<template>
    <div class="fade-in-up">
        <div class="content-card bg-dark mb-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <p class="text-uppercase text-warning fw-bold small mb-2">BMS Manager</p>
                    <h1 class="display-4 fw-bold mb-2 text-champagne">Singers Manager Dashboard</h1>
                    <p class="lead text-champagne-muted mb-0">
                        Manage rehearsal schedules and track singer attendance.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button class="btn btn-sm" :class="activeTab === 'rehearsal' ? 'btn-gold' : 'btn-outline-gold'" @click="activeTab = 'rehearsal'">
                        <i class="bi bi-calendar3 me-1"></i> Rehearsal
                        <span class="tab-badge ms-1">{{ schedules.length }}</span>
                    </button>
                    <button class="btn btn-sm" :class="activeTab === 'attendance' ? 'btn-gold' : 'btn-outline-gold'" @click="activeTab = 'attendance'">
                        <i class="bi bi-clipboard-check me-1"></i> Attendance
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ REHEARSAL TAB ═══════════════════════════════════════ -->
        <div v-if="activeTab === 'rehearsal' && !loading" class="fade-in-up">
            <div class="content-card bg-dark">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1" style="color: #fffdf8">All Schedules</h2>
                        <p class="mb-0" style="color: rgba(234, 220, 194, 0.5)">{{ schedules.length }} schedule{{ schedules.length === 1 ? '' : 's' }}</p>
                    </div>
                    <button class="btn btn-primary" @click="openAddSchedule">
                        <i class="bi bi-plus-lg me-2"></i>Add Schedule
                    </button>
                </div>

                <div v-if="schedules.length === 0" class="mng-empty">
                    <div class="mng-empty-icon"><i class="bi bi-calendar-x"></i></div>
                    <h3 class="mng-empty-title">No schedules found</h3>
                    <p class="mng-empty-text">Create your first rehearsal or practice schedule.</p>
                    <button class="btn btn-primary" @click="openAddSchedule">
                        <i class="bi bi-plus-lg me-2"></i>Add Schedule
                    </button>
                </div>

                <div v-else class="table-responsive">
                    <table class="mng-tbl">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Venue</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in sortedSchedules" :key="s.id" class="mng-row">
                                <td data-label="Title">
                                    <span class="mng-cell-title">{{ s.title }}</span>
                                </td>
                                <td data-label="Type">
                                    <span class="mng-badge" :class="'mng-badge--' + s.type">{{ typeLabel(s.type) }}</span>
                                </td>
                                <td data-label="Date">
                                    <span class="mng-cell-meta"><i class="bi bi-calendar3 me-1"></i>{{ formatDate(s.date) }}</span>
                                </td>
                                <td data-label="Time">
                                    <span class="mng-cell-meta"><i class="bi bi-clock me-1"></i>{{ s.start_time }} – {{ s.end_time }}</span>
                                </td>
                                <td data-label="Venue">
                                    <span class="mng-cell-meta">{{ s.venue || '—' }}</span>
                                </td>
                                <td data-label="Actions" class="text-center">
                                    <div class="mng-actions">
                                        <button type="button" class="mng-action mng-action--edit" title="Edit" @click="openEditSchedule(s)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="mng-action mng-action--delete" title="Delete" @click="deleteScheduleItem(s)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="activeTab === 'rehearsal' && loading" class="py-5 text-center">
            <div class="spinner-border text-warning mb-3" role="status"></div>
            <div class="text-champagne-muted">Loading schedules...</div>
        </div>

        <!-- ══ ATTENDANCE TAB ══════════════════════════════════════ -->
        <div v-if="activeTab === 'attendance'" class="fade-in-up">
            <AttendanceView />
        </div>

        <ScheduleFormModal
            ref="scheduleFormModal"
            :loading="scheduleSaving"
            :success-message="scheduleSuccessMessage"
            :error-message="scheduleErrorMessage"
            :allowed-types="['practice']"
            :allowed-programs="['bms']"
            @submit="submitSchedule"
            @delete="deleteScheduleFromModal"
        />
    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useBmsStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'
import ScheduleFormModal from '../../components/trms/ScheduleFormModal.vue'
import AttendanceView from './Attendance.vue'

export default {
    name: 'SingersManagerDashboard',
    components: { ScheduleFormModal, AttendanceView },
    computed: {
        ...mapState(useBmsStore, ['events']),
        schedules() {
            return this.events.filter(e => e.type === 'practice')
        },
        canManage() {
            const authStore = useAuthStore()
            const role = authStore.user?.role?.toLowerCase()
            return role === 'admin' || role === 'singers_manager'
        },
        sortedSchedules() {
            return [...this.schedules].sort((a, b) => {
                if (a.date !== b.date) return a.date.localeCompare(b.date)
                return a.start_time.localeCompare(b.start_time)
            })
        }
    },
    data() {
        return {
            activeTab: 'rehearsal',
            loading: false,
            scheduleSaving: false,
            scheduleSuccessMessage: '',
            scheduleErrorMessage: '',
        }
    },
    async mounted() {
        await this.loadData()
    },
    methods: {
        ...mapActions(useBmsStore, [
            'fetchEvents',
            'createBmsSchedule',
            'updateBmsSchedule',
            'deleteBmsSchedule'
        ]),

        async loadData() {
            this.loading = true
            try {
                await this.fetchEvents()
            } finally {
                this.loading = false
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return ''
            const [year, month, day] = dateStr.split('-').map(Number)
            return new Date(year, month - 1, day).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            })
        },

        typeLabel(type) {
            const map = {
                lesson: 'Lesson',
                practice: 'Rehearsal',
                concert: 'Concert',
                exam: 'Exam',
                other: 'Other'
            }
            return map[type] || type
        },

        openAddSchedule() {
            this.scheduleSuccessMessage = ''
            this.scheduleErrorMessage = ''
            this.$refs.scheduleFormModal.openAdd('bms')
        },

        openEditSchedule(schedule) {
            this.scheduleSuccessMessage = ''
            this.scheduleErrorMessage = ''
            this.$refs.scheduleFormModal.openEdit(schedule)
        },

        async submitSchedule(payload) {
            this.scheduleSaving = true
            this.scheduleSuccessMessage = ''
            this.scheduleErrorMessage = ''
            try {
                const data = { ...payload.data, program_ids: ['bms'] }
                if (payload.mode === 'edit') {
                    await this.updateBmsSchedule(payload.scheduleId, data)
                    this.scheduleSuccessMessage = 'Schedule updated successfully.'
                } else {
                    await this.createBmsSchedule(data)
                    this.scheduleSuccessMessage = 'Schedule added successfully.'
                }
                await this.fetchEvents()
                if (payload.mode === 'add') {
                    setTimeout(() => this.$refs.scheduleFormModal.hide(), 800)
                }
            } catch (error) {
                this.scheduleErrorMessage = error.message || 'Unable to save schedule.'
            } finally {
                this.scheduleSaving = false
            }
        },

        deleteScheduleItem(schedule) {
            if (!confirm(`Delete schedule "${schedule.title}"?`)) return
            this.deleteScheduleConfirm(schedule.id)
        },

        async deleteScheduleFromModal(scheduleId) {
            await this.deleteScheduleConfirm(scheduleId)
        },

        async deleteScheduleConfirm(id) {
            this.scheduleSaving = true
            this.scheduleSuccessMessage = ''
            this.scheduleErrorMessage = ''
            try {
                await this.deleteBmsSchedule(id)
                this.scheduleSuccessMessage = 'Schedule deleted successfully.'
                await this.fetchEvents()
            } catch (error) {
                this.scheduleErrorMessage = error.message || 'Unable to delete schedule.'
            } finally {
                this.scheduleSaving = false
            }
        }
    }
}
</script>

<style scoped>
.mng-tbl {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
    color: rgba(234, 220, 194, 0.85);
}

.mng-tbl thead {
    border-bottom: 1px solid rgba(234, 220, 194, 0.08);
}

.mng-tbl thead th {
    padding: 0.7rem 0.85rem;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #c8a45d;
    background: rgba(200, 164, 93, 0.06);
    border: none;
    white-space: nowrap;
    text-align: left;
}

.mng-row {
    transition: background 0.15s ease;
}

.mng-row:nth-child(even) {
    background: rgba(234, 220, 194, 0.025);
}

.mng-row:hover {
    background: rgba(200, 164, 93, 0.07);
}

.mng-row:not(:last-child) {
    border-bottom: 1px solid rgba(234, 220, 194, 0.05);
}

.mng-tbl tbody td {
    padding: 0.75rem 0.85rem;
    border: none;
    vertical-align: middle;
    color: rgba(234, 220, 194, 0.6);
    font-size: 0.85rem;
}

.mng-cell-title {
    font-weight: 600;
    color: #fffdf8;
}

.mng-cell-meta {
    color: rgba(234, 220, 194, 0.55);
    font-size: 0.82rem;
    white-space: nowrap;
}

.mng-cell-meta i {
    opacity: 0.45;
    font-size: 0.72rem;
}

.mng-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    font-size: 0.64rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border-radius: 20px;
    border: 1px solid transparent;
}

.mng-badge--lesson {
    color: #8bb9fe;
    background: rgba(110, 168, 254, 0.12);
    border-color: rgba(110, 168, 254, 0.2);
}

.mng-badge--practice {
    color: #8bcfad;
    background: rgba(117, 183, 152, 0.12);
    border-color: rgba(117, 183, 152, 0.2);
}

.mng-badge--concert {
    color: #ffe08a;
    background: rgba(255, 218, 106, 0.12);
    border-color: rgba(255, 218, 106, 0.2);
}

.mng-badge--exam {
    color: #f09aa2;
    background: rgba(234, 134, 143, 0.12);
    border-color: rgba(234, 134, 143, 0.2);
}

.mng-badge--other {
    color: rgba(234, 220, 194, 0.6);
    background: rgba(234, 220, 194, 0.06);
    border-color: rgba(234, 220, 194, 0.1);
}

.mng-actions {
    display: inline-flex;
    gap: 0.3rem;
}

.mng-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border: 1px solid rgba(234, 220, 194, 0.1);
    border-radius: 6px;
    background: rgba(234, 220, 194, 0.04);
    color: rgba(234, 220, 194, 0.5);
    font-size: 0.78rem;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}

.mng-action:hover {
    transform: translateY(-1px);
}

.mng-action--edit:hover {
    color: #c8a45d;
    background: rgba(200, 164, 93, 0.12);
    border-color: rgba(200, 164, 93, 0.35);
}

.mng-action--delete:hover {
    color: #f06060;
    background: rgba(224, 80, 80, 0.12);
    border-color: rgba(224, 80, 80, 0.35);
}

.tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    font-size: 0.65rem;
    font-weight: 800;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.25);
    color: inherit;
    line-height: 1;
}

.mng-empty {
    padding: 3rem 1.5rem;
    text-align: center;
}

.mng-empty-icon {
    font-size: 2.8rem;
    color: rgba(234, 220, 194, 0.15);
    margin-bottom: 0.85rem;
}

.mng-empty-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: rgba(234, 220, 194, 0.7);
    margin-bottom: 0.35rem;
}

.mng-empty-text {
    color: rgba(234, 220, 194, 0.4);
    font-size: 0.85rem;
    margin-bottom: 1.25rem;
}

@media (max-width: 767.98px) {
    .mng-tbl thead {
        display: none;
    }

    .mng-tbl,
    .mng-tbl tbody,
    .mng-tbl tr,
    .mng-tbl td {
        display: block;
    }

    .mng-row {
        padding: 0.85rem 1rem;
        margin-bottom: 0.75rem;
        border: 1px solid rgba(234, 220, 194, 0.1);
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(200, 164, 93, 0.04), transparent 50%),
                    rgba(26, 31, 48, 0.5);
    }

    .mng-row:nth-child(even) {
        background: linear-gradient(135deg, rgba(200, 164, 93, 0.04), transparent 50%),
                    rgba(26, 31, 48, 0.5);
    }

    .mng-row:hover {
        background: linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
                    rgba(26, 31, 48, 0.5);
    }

    .mng-row:not(:last-child) {
        border-bottom: 1px solid rgba(234, 220, 194, 0.1);
    }

    .mng-tbl tbody td {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(234, 220, 194, 0.04);
    }

    .mng-tbl tbody td:last-child {
        border-bottom: none;
    }

    .mng-tbl tbody td::before {
        content: attr(data-label);
        flex-shrink: 0;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #c8a45d;
        min-width: 80px;
    }

    .mng-tbl tbody td:last-child::before {
        align-self: flex-start;
        margin-top: 0.35rem;
    }

    .mng-tbl tbody td[data-label="Actions"] {
        flex-wrap: wrap;
        padding-top: 0.5rem;
    }
}
</style>
