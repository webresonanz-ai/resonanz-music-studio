<template>
    <div class="fade-in-up">
        <!-- ══ PAGE HEADER ══════════════════════════════════════════════ -->
        <div class="content-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="attendance-eyebrow mb-2">Batavia Madrigal Singers</p>
                    <h1 class="display-5 fw-bold mb-3">Rehearsal Attendance</h1>
                    <p class="lead text-muted mb-0">
                        Select an upcoming concert, assign participating singers, and track their rehearsal attendance until performance day.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="stats-panel rounded p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-music-note-list display-6 text-warning"></i>
                            <div>
                                <div class="fw-bold">Concert Cycle</div>
                                <div class="text-white-50 small">
                                    {{ selectedConcert ? selectedConcert.title : 'No concert selected' }}
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="mini-stat">
                                <span class="mini-stat-val">{{ roster.length }}</span>
                                <span class="mini-stat-lbl">Singers</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-val">{{ rehearsals.length }}</span>
                                <span class="mini-stat-lbl">Rehearsals</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-stat-val">{{ attendanceRate }}%</span>
                                <span class="mini-stat-lbl">Present</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ CONCERT SELECTOR ═════════════════════════════════════════ -->
        <div class="content-card mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <h2 class="h5 fw-bold mb-0">
                    <i class="bi bi-calendar-event me-2 text-primary"></i>Select Concert
                </h2>
                <span class="text-muted small">{{ concerts.length }} upcoming concert{{ concerts.length === 1 ? '' : 's' }}</span>
            </div>

            <div v-if="loadingConcerts" class="text-center py-4">
                <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                <span class="text-muted ms-2">Loading concerts…</span>
            </div>

            <div v-else-if="concerts.length === 0" class="empty-hint py-4 text-center text-muted">
                <i class="bi bi-calendar-x display-4 d-block mb-2"></i>
                <p class="mb-0">No upcoming concert schedules found. Add a concert in <router-link to="/bms/events">Events</router-link> first.</p>
            </div>

            <div v-else class="concert-picker row g-3">
                <div
                    v-for="concert in concerts"
                    :key="concert.id"
                    class="col-12 col-md-6 col-xl-4"
                >
                    <button
                        type="button"
                        class="concert-pick-card w-100 text-start"
                        :class="{ active: selectedConcertId === concert.id }"
                        @click="selectConcert(concert.id)"
                    >
                        <div class="d-flex gap-3 align-items-start">
                            <div class="calendar-widget-sheet flex-shrink-0">
                                <div class="calendar-widget-header text-uppercase">{{ monthAbbr(concert.date) }}</div>
                                <div class="calendar-widget-body">
                                    <div class="calendar-widget-day">{{ dayNum(concert.date) }}</div>
                                </div>
                            </div>
                            <div class="min-width-0 flex-grow-1">
                                <span class="badge bg-warning text-dark mb-1">Concert</span>
                                <h3 class="h6 fw-bold mb-1 text-truncate">{{ concert.title }}</h3>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-clock me-1"></i>{{ formatTime(concert.start_time) }} – {{ formatTime(concert.end_time) }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <template v-if="selectedConcertId">
            <!-- ══ LOADING DETAIL ═════════════════════════════════════════ -->
            <div v-if="loadingDetail" class="content-card text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted mt-3">Loading roster &amp; rehearsals…</div>
            </div>

            <template v-else>
                <!-- ══ ROSTER ═════════════════════════════════════════════ -->
                <div class="content-card mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">
                                <i class="bi bi-people-fill me-2 text-primary"></i>Concert Singers
                            </h2>
                            <p class="text-muted small mb-0">Members participating in {{ selectedConcert?.title }}</p>
                        </div>
                        <button
                            v-if="canManage"
                            class="btn btn-primary btn-sm"
                            @click="showAddSingers = true"
                        >
                            <i class="bi bi-person-plus me-1"></i>Add Singers
                        </button>
                        <p v-else class="text-muted small mb-0">
                            <i class="bi bi-lock me-1"></i>Sign in as admin or manager to edit roster
                        </p>
                    </div>

                    <div v-if="roster.length === 0" class="empty-hint py-4 text-center text-muted">
                        <i class="bi bi-person-dash display-4 d-block mb-2"></i>
                        <p class="mb-0">No singers assigned yet. Add members from the BMS roster to this concert.</p>
                    </div>

                    <div v-else class="roster-grid">
                        <div
                            v-for="singer in roster"
                            :key="singer.member_id"
                            class="roster-chip"
                        >
                            <img
                                :src="singer.avatar || defaultAvatar"
                                :alt="singer.name"
                                class="roster-avatar"
                                @error="onImgError"
                            />
                            <div class="roster-info min-width-0">
                                <span class="roster-name">{{ singer.name }}</span>
                                <span class="roster-meta">{{ singer.role || singer.section || '–' }}</span>
                            </div>
                            <button
                                v-if="canManage"
                                type="button"
                                class="roster-remove"
                                title="Remove from concert"
                                @click="removeSinger(singer.member_id)"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ══ ATTENDANCE GRID ════════════════════════════════════ -->
                <div class="content-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">
                                <i class="bi bi-clipboard-check me-2 text-primary"></i>Rehearsal Attendance
                            </h2>
                            <p class="text-muted small mb-0">
                                Practice sessions on or before {{ formatDate(selectedConcert?.date) }}
                            </p>
                        </div>
                        <div class="legend d-flex flex-wrap gap-2">
                            <span v-for="item in statusLegend" :key="item.status" class="legend-item">
                                <span class="legend-dot" :class="'dot-' + item.status"></span>{{ item.label }}
                            </span>
                        </div>
                    </div>

                    <div v-if="roster.length === 0" class="empty-hint py-4 text-center text-muted">
                        <p class="mb-0">Add singers to the concert roster to begin tracking attendance.</p>
                    </div>

                    <div v-else-if="rehearsals.length === 0" class="empty-hint py-4 text-center text-muted">
                        <i class="bi bi-calendar2-minus display-4 d-block mb-2"></i>
                        <p class="mb-0">No practice schedules found before this concert. Add rehearsals in <router-link to="/bms/events">Events</router-link>.</p>
                    </div>

                    <div v-else class="attendance-scroll-wrap">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th class="sticky-col singer-col">Singer</th>
                                    <th
                                        v-for="reh in rehearsals"
                                        :key="reh.id"
                                        class="reh-col"
                                        :title="reh.title"
                                    >
                                        <span class="reh-date">{{ formatShortDate(reh.date) }}</span>
                                        <span class="reh-time">{{ formatTime(reh.start_time) }}</span>
                                    </th>
                                    <th class="summary-col">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="singer in roster" :key="singer.member_id">
                                    <td class="sticky-col singer-col">
                                        <div class="singer-cell">
                                            <img
                                                :src="singer.avatar || defaultAvatar"
                                                :alt="singer.name"
                                                class="singer-thumb"
                                                @error="onImgError"
                                            />
                                            <div class="min-width-0">
                                                <span class="singer-name">{{ singer.name }}</span>
                                                <span class="singer-role">{{ singer.role || '–' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        v-for="reh in rehearsals"
                                        :key="reh.id + '-' + singer.member_id"
                                        class="cell-col"
                                    >
                                        <button
                                            type="button"
                                            class="attendance-cell"
                                            :class="cellClass(singer.member_id, reh.id)"
                                            :disabled="!canManage || savingCell === cellKey(singer.member_id, reh.id)"
                                            :title="canManage ? 'Click to cycle status' : statusLabel(getStatus(singer.member_id, reh.id))"
                                            @click="cycleStatus(singer.member_id, reh.id)"
                                        >
                                            <span v-if="savingCell === cellKey(singer.member_id, reh.id)" class="spinner-border spinner-border-sm"></span>
                                            <i v-else-if="getStatus(singer.member_id, reh.id)" :class="statusIcon(getStatus(singer.member_id, reh.id))"></i>
                                            <span v-else class="cell-empty">–</span>
                                        </button>
                                    </td>
                                    <td class="summary-col">
                                        <span class="rate-badge" :class="rateClass(singer.member_id)">
                                            {{ singerRate(singer.member_id) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </template>

        <!-- ══ ADD SINGERS MODAL ════════════════════════════════════════ -->
        <Teleport to="body">
            <transition name="modal">
                <div v-if="showAddSingers" class="modal-overlay" @click.self="showAddSingers = false">
                    <div class="modal-sheet modal-sheet--lg" role="dialog" aria-modal="true" aria-label="Add singers">
                        <div class="modal-header-row">
                            <div>
                                <h5 class="modal-sheet-title mb-0">Add Singers to Concert</h5>
                                <p class="modal-sheet-sub mb-0">Select active members to join {{ selectedConcert?.title }}</p>
                            </div>
                            <button class="modal-close-btn" @click="showAddSingers = false" aria-label="Close">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="modal-search px-4 pt-3">
                            <div class="search-wrap">
                                <i class="bi bi-search search-icon"></i>
                                <input
                                    v-model="addSearch"
                                    type="text"
                                    class="search-input"
                                    placeholder="Search by name, role, section…"
                                />
                            </div>
                        </div>

                        <div class="modal-body-scroll px-4 pb-2">
                            <div v-if="availableToAdd.length === 0" class="text-center text-muted py-4">
                                All active members are already on this concert roster.
                            </div>
                            <div v-else class="add-list">
                                <label
                                    v-for="member in filteredAvailable"
                                    :key="member.id"
                                    class="add-row"
                                    :class="{ selected: pendingAdds.includes(member.id) }"
                                >
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :value="member.id"
                                        v-model="pendingAdds"
                                    />
                                    <img
                                        :src="member.avatar || defaultAvatar"
                                        :alt="member.name"
                                        class="add-avatar"
                                        @error="onImgError"
                                    />
                                    <div class="min-width-0 flex-grow-1">
                                        <span class="add-name">{{ member.name }}</span>
                                        <span class="add-meta">{{ member.role || member.section || '–' }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="modal-footer-row">
                            <button class="btn btn-secondary" @click="showAddSingers = false">Cancel</button>
                            <button
                                class="btn btn-primary"
                                :disabled="pendingAdds.length === 0 || addingSingers"
                                @click="confirmAddSingers"
                            >
                                <span v-if="addingSingers" class="spinner-border spinner-border-sm me-1"></span>
                                Add {{ pendingAdds.length || '' }} Singer{{ pendingAdds.length === 1 ? '' : 's' }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- Toast -->
        <div v-if="toast" class="attendance-toast" :class="'toast-' + toast.type">
            <i class="bi" :class="toast.type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle'"></i>
            {{ toast.message }}
        </div>
    </div>
</template>

<script>
import { mapActions } from 'pinia'
import { useBmsStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'

const DEFAULT_AVATAR = 'https://voca-land.sgp1.cdn.digitaloceanspaces.com/0/1757684222527/9465e2e8.jpg'
const STATUS_CYCLE = [null, 'present', 'late', 'absent', 'excused']

export default {
    name: 'Attendance',

    data() {
        return {
            defaultAvatar: DEFAULT_AVATAR,
            loadingConcerts: false,
            loadingDetail: false,
            concerts: [],
            selectedConcertId: null,
            detail: null,
            showAddSingers: false,
            addSearch: '',
            pendingAdds: [],
            addingSingers: false,
            savingCell: null,
            toast: null,
            toastTimer: null,
            statusLegend: [
                { status: 'present', label: 'Present' },
                { status: 'late', label: 'Late' },
                { status: 'absent', label: 'Absent' },
                { status: 'excused', label: 'Excused' }
            ]
        }
    },

    computed: {
        canManage() {
            const role = useAuthStore().user?.role?.toLowerCase()
            return role === 'admin' || role === 'manager'
        },
        selectedConcert() {
            return this.detail?.concert || this.concerts.find(c => c.id === this.selectedConcertId) || null
        },
        roster() {
            return this.detail?.roster || []
        },
        rehearsals() {
            return this.detail?.rehearsals || []
        },
        attendanceMap() {
            return this.detail?.attendance || {}
        },
        availableToAdd() {
            const rosterIds = new Set(this.roster.map(r => r.member_id))
            const members = this.detail?.available_members || []
            return members.filter(m => m.status === 'active' && !rosterIds.has(m.id))
        },
        filteredAvailable() {
            const q = this.addSearch.trim().toLowerCase()
            if (!q) return this.availableToAdd
            return this.availableToAdd.filter(m =>
                (m.name || '').toLowerCase().includes(q) ||
                (m.role || '').toLowerCase().includes(q) ||
                (m.section || '').toLowerCase().includes(q)
            )
        },
        attendanceRate() {
            if (!this.roster.length || !this.rehearsals.length) return 0
            let total = 0
            let present = 0
            for (const singer of this.roster) {
                for (const reh of this.rehearsals) {
                    const s = this.getStatus(singer.member_id, reh.id)
                    if (s) {
                        total++
                        if (s === 'present' || s === 'late') present++
                    }
                }
            }
            if (!total) return 0
            return Math.round((present / total) * 100)
        }
    },

    async mounted() {
        this.loadingConcerts = true
        try {
            const result = await this.fetchAttendanceConcerts()
            this.concerts = result.concerts || []
            if (this.concerts.length === 1) {
                await this.selectConcert(this.concerts[0].id)
            }
        } catch (err) {
            this.showToast(err.message || 'Failed to load concerts', 'error')
        } finally {
            this.loadingConcerts = false
        }
    },

    beforeUnmount() {
        if (this.toastTimer) clearTimeout(this.toastTimer)
        document.body.style.overflow = ''
    },

    watch: {
        showAddSingers(val) {
            document.body.style.overflow = val ? 'hidden' : ''
            if (!val) {
                this.pendingAdds = []
                this.addSearch = ''
            }
        }
    },

    methods: {
        ...mapActions(useBmsStore, [
            'fetchAttendanceConcerts',
            'fetchAttendanceDetail',
            'updateConcertRoster',
            'recordScheduleAttendance'
        ]),

        async selectConcert(id) {
            if (this.selectedConcertId === id && this.detail) return
            this.selectedConcertId = id
            this.loadingDetail = true
            try {
                this.detail = await this.fetchAttendanceDetail(id)
            } catch (err) {
                this.showToast(err.message || 'Failed to load attendance data', 'error')
                this.detail = null
            } finally {
                this.loadingDetail = false
            }
        },

        async removeSinger(memberId) {
            if (!this.canManage) return
            if (!confirm('Remove this singer from the concert roster?')) return
            try {
                await this.updateConcertRoster(this.selectedConcertId, memberId, 'remove')
                await this.selectConcert(this.selectedConcertId)
                this.showToast('Singer removed from roster')
            } catch (err) {
                this.showToast(err.message || 'Failed to remove singer', 'error')
            }
        },

        async confirmAddSingers() {
            if (!this.pendingAdds.length) return
            this.addingSingers = true
            try {
                for (const memberId of this.pendingAdds) {
                    await this.updateConcertRoster(this.selectedConcertId, memberId, 'add')
                }
                await this.selectConcert(this.selectedConcertId)
                this.showAddSingers = false
                this.showToast(`${this.pendingAdds.length} singer(s) added`)
            } catch (err) {
                this.showToast(err.message || 'Failed to add singers', 'error')
            } finally {
                this.addingSingers = false
            }
        },

        cellKey(memberId, scheduleId) {
            return `${memberId}-${scheduleId}`
        },

        getStatus(memberId, scheduleId) {
            return this.attendanceMap[`${scheduleId}-${memberId}`] || null
        },

        cellClass(memberId, scheduleId) {
            const s = this.getStatus(memberId, scheduleId)
            return s ? `cell-${s}` : 'cell-unmarked'
        },

        statusIcon(status) {
            const map = {
                present: 'bi-check-lg',
                late: 'bi-clock-history',
                absent: 'bi-x-lg',
                excused: 'bi-dash-lg'
            }
            return map[status] || ''
        },

        statusLabel(status) {
            if (!status) return 'Not recorded'
            return status.charAt(0).toUpperCase() + status.slice(1)
        },

        async cycleStatus(memberId, scheduleId) {
            if (!this.canManage) return
            const key = this.cellKey(memberId, scheduleId)
            const current = this.getStatus(memberId, scheduleId)
            const idx = STATUS_CYCLE.indexOf(current)
            const next = STATUS_CYCLE[(idx + 1) % STATUS_CYCLE.length]

            this.savingCell = key
            try {
                await this.recordScheduleAttendance({
                    concertScheduleId: this.selectedConcertId,
                    scheduleId,
                    memberId,
                    status: next
                })
                const mapKey = `${scheduleId}-${memberId}`
                if (next === null) {
                    delete this.detail.attendance[mapKey]
                } else {
                    this.detail.attendance[mapKey] = next
                }
            } catch (err) {
                this.showToast(err.message || 'Failed to save attendance', 'error')
            } finally {
                this.savingCell = null
            }
        },

        singerRate(memberId) {
            if (!this.rehearsals.length) return 0
            let marked = 0
            let present = 0
            for (const reh of this.rehearsals) {
                const s = this.getStatus(memberId, reh.id)
                if (s) {
                    marked++
                    if (s === 'present' || s === 'late') present++
                }
            }
            if (!marked) return 0
            return Math.round((present / marked) * 100)
        },

        rateClass(memberId) {
            const rate = this.singerRate(memberId)
            if (rate >= 80) return 'rate-good'
            if (rate >= 50) return 'rate-mid'
            return 'rate-low'
        },

        formatTime(value) {
            return String(value || '').slice(0, 5)
        },

        formatDate(dateStr) {
            if (!dateStr) return ''
            const [y, m, d] = dateStr.split('-').map(Number)
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            })
        },

        formatShortDate(dateStr) {
            if (!dateStr) return ''
            const [y, m, d] = dateStr.split('-').map(Number)
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
        },

        monthAbbr(dateStr) {
            if (!dateStr) return ''
            const [y, m, d] = dateStr.split('-').map(Number)
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', { month: 'short' }).toUpperCase()
        },

        dayNum(dateStr) {
            if (!dateStr) return ''
            return dateStr.split('-')[2]
        },

        onImgError(e) {
            e.target.src = DEFAULT_AVATAR
        },

        showToast(message, type = 'success') {
            this.toast = { message, type }
            if (this.toastTimer) clearTimeout(this.toastTimer)
            this.toastTimer = setTimeout(() => { this.toast = null }, 3500)
        }
    }
}
</script>

<style scoped>
.attendance-eyebrow {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--gold-color, #c8a45d);
}

.stats-panel {
    background: linear-gradient(135deg, #171b27 0%, #222838 100%);
    color: #fff;
    border: 1px solid rgba(200, 164, 93, 0.2);
}

.mini-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 64px;
}

.mini-stat-val {
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1.1;
}

.mini-stat-lbl {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(255, 255, 255, 0.55);
}

.concert-pick-card {
    background: var(--surface-color);
    border: 1px solid var(--hairline-color);
    border-radius: var(--radius-md, 8px);
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.concert-pick-card:hover,
.concert-pick-card.active {
    border-color: rgba(200, 164, 93, 0.55);
    box-shadow: var(--shadow-soft, 0 8px 24px rgba(19, 18, 16, 0.08));
}

.concert-pick-card.active {
    background: rgba(200, 164, 93, 0.08);
}

.calendar-widget-sheet {
    width: 3.5rem;
    background: rgba(255, 253, 248, 0.9);
    border: 1px solid var(--hairline-color);
    border-radius: 8px;
    overflow: hidden;
    text-align: center;
}

.calendar-widget-header {
    background: var(--accent-color);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.15rem 0;
}

.calendar-widget-body { padding: 0.25rem 0 0.35rem; }

.calendar-widget-day {
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1;
}

.roster-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.roster-chip {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.5rem 0.35rem 0.35rem;
    border: 1px solid var(--hairline-color);
    border-radius: 999px;
    background: rgba(255, 253, 248, 0.9);
    max-width: 220px;
}

.roster-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.roster-info {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.roster-name {
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.roster-meta {
    font-size: 0.68rem;
    color: var(--muted-color);
}

.roster-remove {
    border: 0;
    background: transparent;
    color: var(--muted-color);
    padding: 0.2rem;
    cursor: pointer;
    border-radius: 50%;
    line-height: 1;
    font-size: 0.7rem;
}

.roster-remove:hover {
    color: #c0392b;
    background: rgba(192, 57, 43, 0.08);
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    color: var(--muted-color);
    font-weight: 600;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
}

.dot-present { background: #4a7c59; }
.dot-late { background: #b8860b; }
.dot-absent { background: #c0392b; }
.dot-excused { background: #6f6a61; }

.attendance-scroll-wrap {
    overflow-x: auto;
    margin: 0 -0.25rem;
    padding-bottom: 0.5rem;
}

.attendance-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 600px;
}

.attendance-table th,
.attendance-table td {
    padding: 0.4rem;
    border-bottom: 1px solid var(--hairline-color);
    vertical-align: middle;
}

.attendance-table thead th {
    background: linear-gradient(135deg, rgba(200, 164, 93, 0.1), rgba(127, 36, 50, 0.05));
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted-color);
    text-align: center;
    white-space: nowrap;
}

.sticky-col {
    position: sticky;
    left: 0;
    z-index: 2;
    background: var(--surface-color, #fffdf8);
}

.singer-col {
    min-width: 180px;
    max-width: 200px;
    text-align: left;
}

.reh-col { min-width: 72px; }

.reh-date { display: block; font-size: 0.72rem; }
.reh-time { display: block; font-size: 0.65rem; font-weight: 500; opacity: 0.8; }

.singer-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.singer-thumb {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.singer-name {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.singer-role {
    display: block;
    font-size: 0.68rem;
    color: var(--muted-color);
}

.attendance-cell {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--hairline-color);
    background: rgba(255, 253, 248, 0.8);
    display: grid;
    place-items: center;
    margin: 0 auto;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.15s ease;
    padding: 0;
}

.attendance-cell:disabled { cursor: default; }

.attendance-cell:not(:disabled):hover {
    transform: scale(1.08);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cell-unmarked { color: var(--muted-color); }
.cell-empty { font-size: 0.75rem; }

.cell-present { background: rgba(74, 124, 89, 0.15); border-color: #4a7c59; color: #4a7c59; }
.cell-late { background: rgba(184, 134, 11, 0.15); border-color: #b8860b; color: #b8860b; }
.cell-absent { background: rgba(192, 57, 43, 0.12); border-color: #c0392b; color: #c0392b; }
.cell-excused { background: rgba(111, 106, 97, 0.12); border-color: #6f6a61; color: #6f6a61; }

.summary-col {
    text-align: center;
    min-width: 56px;
}

.rate-badge {
    display: inline-block;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
}

.rate-good { background: rgba(74, 124, 89, 0.15); color: #4a7c59; }
.rate-mid { background: rgba(184, 134, 11, 0.15); color: #b8860b; }
.rate-low { background: rgba(192, 57, 43, 0.12); color: #c0392b; }

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(10, 10, 15, 0.6);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}

.modal-sheet {
    background: var(--surface-color, #fffdf8);
    border-radius: 14px;
    border: 1px solid var(--hairline-color);
    box-shadow: 0 32px 72px rgba(10, 10, 15, 0.36);
    width: 100%;
    max-height: calc(100vh - 3rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.modal-sheet--lg { max-width: 560px; }

.modal-header-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--hairline-color);
}

.modal-sheet-title { font-size: 1rem; font-weight: 700; }
.modal-sheet-sub { font-size: 0.78rem; color: var(--muted-color); }

.modal-close-btn {
    border: 0;
    background: rgba(34, 29, 20, 0.06);
    border-radius: 8px;
    width: 34px;
    height: 34px;
    cursor: pointer;
    color: var(--muted-color);
}

.modal-body-scroll {
    overflow-y: auto;
    flex: 1;
    max-height: 360px;
}

.modal-footer-row {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--hairline-color);
}

.search-wrap { position: relative; }

.search-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted-color);
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 0.55rem 0.75rem 0.55rem 2.4rem;
    border: 1px solid var(--hairline-color);
    border-radius: 8px;
    background: rgba(255, 253, 248, 0.9);
    font-size: 0.9rem;
}

.add-list { display: flex; flex-direction: column; gap: 0.35rem; }

.add-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.5rem 0.65rem;
    border: 1px solid var(--hairline-color);
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
}

.add-row:hover,
.add-row.selected {
    background: rgba(200, 164, 93, 0.1);
    border-color: rgba(200, 164, 93, 0.4);
}

.add-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.add-name { display: block; font-weight: 700; font-size: 0.88rem; }
.add-meta { display: block; font-size: 0.72rem; color: var(--muted-color); }

.attendance-toast {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 1100;
    padding: 0.75rem 1.1rem;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    animation: slideUp 0.25s ease;
}

.toast-success { background: #4a7c59; color: #fff; }
.toast-error { background: #c0392b; color: #fff; }

@keyframes slideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-enter-active,
.modal-leave-active { transition: opacity 0.22s ease; }

.modal-enter-from,
.modal-leave-to { opacity: 0; }

@media (max-width: 767px) {
    .modal-overlay { padding: 0; align-items: flex-end; }
    .modal-sheet { border-radius: 16px 16px 0 0; max-height: 92vh; }
}
</style>
