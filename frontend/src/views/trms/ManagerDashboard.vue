<template>
    <div class="fade-in-up">
        <div class="content-card bg-dark mb-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <p class="text-uppercase text-warning fw-bold small mb-2">TRMS Manager</p>
                    <h1 class="display-4 fw-bold mb-2 text-champagne">Manager Dashboard</h1>
                    <p class="lead text-champagne-muted mb-0">
                        Create, edit, and manage schedules and news in one place.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button class="btn btn-sm" :class="activeTab === 'schedules' ? 'btn-gold' : 'btn-outline-gold'" @click="activeTab = 'schedules'">
                        <i class="bi bi-calendar3 me-1"></i> Schedules
                        <span class="tab-badge ms-1">{{ schedules.length }}</span>
                    </button>
                    <button class="btn btn-sm" :class="activeTab === 'news' ? 'btn-gold' : 'btn-outline-gold'" @click="activeTab = 'news'">
                        <i class="bi bi-newspaper me-1"></i> News
                        <span class="tab-badge ms-1">{{ news.length }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="activeTab === 'schedules' && !loading" class="fade-in-up">
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
                    <p class="mng-empty-text">Create your first schedule to get started.</p>
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

        <div v-if="activeTab === 'news' && !loading" class="fade-in-up">
            <div class="content-card bg-dark">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1" style="color: #fffdf8">All News</h2>
                        <p class="mb-0" style="color: rgba(234, 220, 194, 0.5)">{{ news.length }} article{{ news.length === 1 ? '' : 's' }}</p>
                    </div>
                    <button class="btn btn-primary" @click="openAddNews">
                        <i class="bi bi-plus-lg me-2"></i>Add News
                    </button>
                </div>

                <div v-if="news.length === 0" class="mng-empty">
                    <div class="mng-empty-icon"><i class="bi bi-newspaper"></i></div>
                    <h3 class="mng-empty-title">No news articles found</h3>
                    <p class="mng-empty-text">Publish your first news article.</p>
                    <button class="btn btn-primary" @click="openAddNews">
                        <i class="bi bi-plus-lg me-2"></i>Add News
                    </button>
                </div>

                <div v-else class="table-responsive">
                    <table class="mng-tbl">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Programs</th>
                                <th>Published</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="article in sortedNews" :key="article.id" class="mng-row">
                                <td data-label="Title">
                                    <span class="mng-cell-title">{{ article.title }}</span>
                                </td>
                                <td data-label="Programs">
                                    <span class="mng-prog-badge" :class="'mng-prog--' + p" v-for="p in (article.program_ids || [article.program_id || 'trms'])" :key="p">
                                        {{ programLabel(p) }}
                                    </span>
                                </td>
                                <td data-label="Published">
                                    <span class="mng-cell-meta"><i class="bi bi-calendar3 me-1"></i>{{ formatDate(article.published_at) }}</span>
                                </td>
                                <td data-label="Actions" class="text-center">
                                    <div class="mng-actions">
                                        <button type="button" class="mng-action mng-action--edit" title="Edit" @click="openEditNews(article)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="mng-action mng-action--delete" title="Delete" @click="deleteNewsItem(article)">
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

        <div v-if="loading" class="py-5 text-center">
            <div class="spinner-border text-warning mb-3" role="status"></div>
            <div class="text-champagne-muted">{{ activeTab === 'schedules' ? 'Loading schedules...' : 'Loading news...' }}</div>
        </div>

        <ScheduleFormModal
            ref="scheduleFormModal"
            :loading="scheduleSaving"
            :success-message="scheduleSuccessMessage"
            :error-message="scheduleErrorMessage"
            @submit="submitSchedule"
            @delete="deleteScheduleFromModal"
        />

        <Teleport to="body">
            <div class="modal fade" id="mngNewsFormModal" tabindex="-1" ref="newsFormModalEl">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content modal-content-dark">
                        <div class="modal-header modal-header-dark">
                            <h5 class="modal-title fw-bold text-warning">
                                <i class="bi bi-newspaper me-2"></i>
                                {{ editingNews ? 'Edit News' : 'Add News' }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div v-if="newsSuccessMessage" class="alert alert-success d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>{{ newsSuccessMessage }}</span>
                            </div>
                            <div v-if="newsErrorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>{{ newsErrorMessage }}</span>
                            </div>
                            <form @submit.prevent="submitNewsForm">
                                <div class="mb-3">
                                    <label for="mngNewsTitle" class="form-label fw-semibold text-champagne">Title</label>
                                    <input
                                        id="mngNewsTitle"
                                        v-model.trim="newsForm.title"
                                        class="form-control form-control-dark"
                                        type="text"
                                        required
                                        maxlength="150"
                                        placeholder="News title"
                                    >
                                </div>
                                <div class="mb-3">
                                    <label for="mngNewsContent" class="form-label fw-semibold text-champagne">Content</label>
                                    <textarea
                                        id="mngNewsContent"
                                        v-model.trim="newsForm.content"
                                        class="form-control form-control-dark"
                                        rows="5"
                                        required
                                        placeholder="Article content..."
                                    ></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-champagne d-block">Programs</label>
                                    <div class="d-flex flex-wrap gap-4 p-3 rounded border border-secondary border-opacity-25">
                                        <div class="form-check" v-for="prog in availablePrograms" :key="prog.id">
                                            <input
                                                :id="'mng-prog-' + prog.id"
                                                class="form-check-input"
                                                type="checkbox"
                                                :value="prog.id"
                                                v-model="newsForm.program_ids"
                                            >
                                            <label :for="'mng-prog-' + prog.id" class="form-check-label select-none text-champagne-muted">
                                                {{ prog.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="mngNewsDate" class="form-label fw-semibold text-champagne">Published Date</label>
                                    <input
                                        id="mngNewsDate"
                                        v-model="newsForm.published_at"
                                        class="form-control form-control-dark"
                                        type="date"
                                        required
                                    >
                                </div>
                                <div class="d-flex gap-3">
                                    <button class="btn btn-primary" type="submit" :disabled="newsSaving">
                                        <span v-if="newsSaving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                                        <i v-else class="bi bi-check-circle me-2"></i>
                                        {{ newsSaving ? 'Saving...' : (editingNews ? 'Update' : 'Publish') }}
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
                                        Cancel
                                    </button>
                                </div>
                            </form>
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

const emptyNewsForm = () => ({
    title: '',
    content: '',
    program_ids: ['trms'],
    published_at: new Date().toISOString().split('T')[0]
})

export default {
    name: 'ManagerDashboard',
    components: { ScheduleFormModal },
    computed: {
        ...mapState(useTrmsStore, ['schedules', 'news']),
        canManage() {
            const authStore = useAuthStore()
            const role = authStore.user?.role?.toLowerCase()
            return role === 'admin' || role === 'manager'
        },
        sortedSchedules() {
            return [...this.schedules].sort((a, b) => {
                if (a.date !== b.date) return a.date.localeCompare(b.date)
                return a.start_time.localeCompare(b.start_time)
            })
        },
        sortedNews() {
            return [...this.news].sort((a, b) => {
                const dateA = a.published_at || ''
                const dateB = b.published_at || ''
                return dateB.localeCompare(dateA)
            })
        }
    },
    data() {
        return {
            activeTab: 'schedules',
            loading: false,

            scheduleSaving: false,
            scheduleSuccessMessage: '',
            scheduleErrorMessage: '',

            newsSaving: false,
            newsSuccessMessage: '',
            newsErrorMessage: '',
            newsForm: emptyNewsForm(),
            editingNews: null,
            newsFormModalInstance: null,

            availablePrograms: [
                { id: 'trms', name: 'TRMS' },
                { id: 'bms', name: 'BMS' },
                { id: 'jco', name: 'JCO' },
                { id: 'trcc', name: 'TRCC' }
            ]
        }
    },
    async mounted() {
        await this.loadData()
    },
    methods: {
        ...mapActions(useTrmsStore, [
            'fetchSchedules',
            'fetchNews',
            'createSchedule',
            'updateSchedule',
            'storeDeleteSchedule',
            'createNews',
            'updateNews',
            'deleteNews'
        ]),

        async loadData() {
            this.loading = true
            try {
                await Promise.all([this.fetchSchedules(), this.fetchNews()])
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
                practice: 'Practice',
                concert: 'Concert',
                exam: 'Exam',
                other: 'Other'
            }
            return map[type] || type
        },

        programLabel(programId) {
            const map = { trms: 'TRMS', bms: 'BMS', jco: 'JCO', trcc: 'TRCC' }
            return map[programId] || (programId || '').toUpperCase()
        },

        openAddSchedule() {
            this.scheduleSuccessMessage = ''
            this.scheduleErrorMessage = ''
            this.$refs.scheduleFormModal.openAdd()
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
                if (payload.mode === 'edit') {
                    await this.updateSchedule(payload.scheduleId, payload.data)
                    this.scheduleSuccessMessage = 'Schedule updated successfully.'
                } else {
                    await this.createSchedule(payload.data)
                    this.scheduleSuccessMessage = 'Schedule added successfully.'
                }
                await this.fetchSchedules()
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
                await this.storeDeleteSchedule(id)
                this.scheduleSuccessMessage = 'Schedule deleted successfully.'
                await this.fetchSchedules()
            } catch (error) {
                this.scheduleErrorMessage = error.message || 'Unable to delete schedule.'
            } finally {
                this.scheduleSaving = false
            }
        },

        openAddNews() {
            this.editingNews = null
            this.newsForm = emptyNewsForm()
            this.newsSuccessMessage = ''
            this.newsErrorMessage = ''
            this.showNewsFormModal()
        },

        openEditNews(article) {
            this.editingNews = article
            this.newsForm = {
                title: article.title || '',
                content: article.content || '',
                program_ids: [...(article.program_ids || [article.program_id || 'trms'])],
                published_at: article.published_at ? article.published_at.split(' ')[0] : ''
            }
            this.newsSuccessMessage = ''
            this.newsErrorMessage = ''
            this.showNewsFormModal()
        },

        async submitNewsForm() {
            this.newsSaving = true
            this.newsSuccessMessage = ''
            this.newsErrorMessage = ''
            try {
                if (this.editingNews) {
                    await this.updateNews(this.editingNews.id, this.newsForm)
                    this.newsSuccessMessage = 'News updated successfully.'
                } else {
                    await this.createNews(this.newsForm)
                    this.newsSuccessMessage = 'News published successfully.'
                }
                await this.fetchNews()
                setTimeout(() => this.hideNewsFormModal(), 1000)
            } catch (error) {
                this.newsErrorMessage = error.message || 'Unable to save news article.'
            } finally {
                this.newsSaving = false
            }
        },

        deleteNewsItem(article) {
            if (!confirm(`Delete news "${article.title}"?`)) return
            this.deleteNewsConfirm(article.id)
        },

        async deleteNewsConfirm(id) {
            this.newsSaving = true
            try {
                await this.deleteNews(id)
                await this.fetchNews()
            } catch (error) {
                alert(error.message || 'Unable to delete news article.')
            } finally {
                this.newsSaving = false
            }
        },

        showNewsFormModal() {
            const el = this.$refs.newsFormModalEl
            if (!el) return
            this.newsFormModalInstance = Modal.getOrCreateInstance(el)
            this.newsFormModalInstance.show()
        },

        hideNewsFormModal() {
            if (this.newsFormModalInstance) {
                this.newsFormModalInstance.hide()
            }
        }
    },
    beforeUnmount() {
        if (this.newsFormModalInstance) this.newsFormModalInstance.dispose()
    }
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   TABLE — fully self-contained, no :deep() overrides needed
   ═══════════════════════════════════════════════════════════════ */

/* ── Table shell ─────────────────────────────────────── */
.mng-tbl {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
    color: rgba(234, 220, 194, 0.85);
}

/* ── Header ──────────────────────────────────────────── */
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

/* ── Rows ────────────────────────────────────────────── */
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

/* ── Cells ───────────────────────────────────────────── */
.mng-tbl tbody td {
    padding: 0.75rem 0.85rem;
    border: none;
    vertical-align: middle;
    color: rgba(234, 220, 194, 0.6);
    font-size: 0.85rem;
}

/* ── Title cell ──────────────────────────────────────── */
.mng-cell-title {
    font-weight: 600;
    color: #fffdf8;
}

/* ── Meta cell (date/time/venue) ─────────────────────── */
.mng-cell-meta {
    color: rgba(234, 220, 194, 0.55);
    font-size: 0.82rem;
    white-space: nowrap;
}

.mng-cell-meta i {
    opacity: 0.45;
    font-size: 0.72rem;
}

/* ── Type badges ─────────────────────────────────────── */
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

/* ── Program badges ──────────────────────────────────── */
.mng-prog-badge {
    display: inline-block;
    padding: 0.18rem 0.5rem;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-radius: 20px;
    border: 1px solid transparent;
    margin-right: 0.25rem;
}

.mng-prog--trms {
    color: #8bb9fe;
    background: rgba(110, 168, 254, 0.1);
    border-color: rgba(110, 168, 254, 0.16);
}

.mng-prog--bms {
    color: #8bcfad;
    background: rgba(117, 183, 152, 0.1);
    border-color: rgba(117, 183, 152, 0.16);
}

.mng-prog--jco {
    color: #ffe08a;
    background: rgba(255, 218, 106, 0.1);
    border-color: rgba(255, 218, 106, 0.16);
}

.mng-prog--trcc {
    color: #8ae3f5;
    background: rgba(110, 223, 246, 0.1);
    border-color: rgba(110, 223, 246, 0.16);
}

/* ── Action buttons ──────────────────────────────────── */
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

/* ── Tab badge ───────────────────────────────────────── */
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

/* ── Empty state ─────────────────────────────────────── */
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

/* ═══════════════════════════════════════════════════════════════
   RESPONSIVE — card layout on mobile (< 768px)
   ═══════════════════════════════════════════════════════════════ */

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
