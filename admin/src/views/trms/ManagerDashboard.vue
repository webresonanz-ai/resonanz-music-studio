<script setup>
import { onMounted, ref, computed } from 'vue'
import { useTrmsStore } from '../../stores/api'
import ScheduleFormModal from '../../components/trms/ScheduleFormModal.vue'

const trmsStore = useTrmsStore()

const loading = ref(true)
const error = ref('')
const activeTab = ref('schedules')

const PROGRAM_OPTIONS = [
  { id: 'trms', name: 'TRMS' },
  { id: 'bms', name: 'BMS' },
  { id: 'jco', name: 'JCO' },
  { id: 'trcc', name: 'TRCC' },
]

const emptyNewsForm = () => ({
  title: '',
  content: '',
  program_ids: ['trms'],
  published_at: new Date().toISOString().split('T')[0],
})

const schedules = computed(() => trmsStore.schedules)
const news = computed(() => trmsStore.news)

const sortedSchedules = computed(() =>
  [...schedules.value].sort((a, b) => {
    if (a.date !== b.date) return a.date.localeCompare(b.date)
    return (a.start_time || '').localeCompare(b.start_time || '')
  })
)

const sortedNews = computed(() =>
  [...news.value].sort((a, b) => (b.published_at || '').localeCompare(a.published_at || ''))
)

const scheduleFormModal = ref(null)
const scheduleSaving = ref(false)
const scheduleSuccessMessage = ref('')
const scheduleErrorMessage = ref('')

const showNewsModal = ref(false)
const editingNews = ref(null)
const newsSaving = ref(false)
const newsSuccessMessage = ref('')
const newsErrorMessage = ref('')
const newsForm = ref(emptyNewsForm())

function formatDate(dateStr) {
  if (!dateStr) return ''
  const [year, month, day] = dateStr.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function typeLabel(type) {
  const map = { lesson: 'Lesson', practice: 'Practice', concert: 'Concert', exam: 'Exam', other: 'Other' }
  return map[type] || type
}

function programLabel(programId) {
  const map = { trms: 'TRMS', bms: 'BMS', jco: 'JCO', trcc: 'TRCC' }
  return map[programId] || (programId || '').toUpperCase()
}

function openAddSchedule() {
  scheduleSuccessMessage.value = ''
  scheduleErrorMessage.value = ''
  scheduleFormModal.value?.openAdd()
}

function openEditSchedule(schedule) {
  scheduleSuccessMessage.value = ''
  scheduleErrorMessage.value = ''
  scheduleFormModal.value?.openEdit(schedule)
}

async function submitSchedule(payload) {
  scheduleSaving.value = true
  scheduleSuccessMessage.value = ''
  scheduleErrorMessage.value = ''
  try {
    if (payload.mode === 'edit') {
      await trmsStore.updateSchedule(payload.scheduleId, payload.data)
      scheduleSuccessMessage.value = 'Schedule updated successfully.'
    } else {
      await trmsStore.createSchedule(payload.data)
      scheduleSuccessMessage.value = 'Schedule added successfully.'
    }
    await trmsStore.fetchSchedules()
    if (payload.mode === 'add') {
      setTimeout(() => scheduleFormModal.value?.hide(), 800)
    }
  } catch (err) {
    scheduleErrorMessage.value = err.message || 'Unable to save schedule.'
  } finally {
    scheduleSaving.value = false
  }
}

function deleteScheduleItem(schedule) {
  if (!window.confirm(`Delete schedule "${schedule.title}"?`)) return
  deleteScheduleConfirm(schedule.id)
}

async function deleteScheduleFromModal(scheduleId) {
  await deleteScheduleConfirm(scheduleId)
}

async function deleteScheduleConfirm(id) {
  scheduleSaving.value = true
  scheduleSuccessMessage.value = ''
  scheduleErrorMessage.value = ''
  try {
    await trmsStore.deleteSchedule(id)
    scheduleSuccessMessage.value = 'Schedule deleted successfully.'
    await trmsStore.fetchSchedules()
  } catch (err) {
    scheduleErrorMessage.value = err.message || 'Unable to delete schedule.'
  } finally {
    scheduleSaving.value = false
  }
}

function openAddNews() {
  editingNews.value = null
  newsForm.value = emptyNewsForm()
  newsSuccessMessage.value = ''
  newsErrorMessage.value = ''
  showNewsModal.value = true
}

function openEditNews(article) {
  editingNews.value = article
  newsForm.value = {
    title: article.title || '',
    content: article.content || '',
    program_ids: article.program_ids?.length ? [...article.program_ids] : [article.program_id || 'trms'],
    published_at: article.published_at ? article.published_at.split(' ')[0] : '',
  }
  newsSuccessMessage.value = ''
  newsErrorMessage.value = ''
  showNewsModal.value = true
}

async function submitNews() {
  newsSaving.value = true
  newsSuccessMessage.value = ''
  newsErrorMessage.value = ''
  try {
    if (editingNews.value) {
      await trmsStore.updateNews(editingNews.value.id, newsForm.value)
      newsSuccessMessage.value = 'News updated successfully.'
    } else {
      await trmsStore.createNews(newsForm.value)
      newsSuccessMessage.value = 'News published successfully.'
    }
    await trmsStore.fetchNews()
    setTimeout(() => {
      showNewsModal.value = false
      newsSuccessMessage.value = ''
    }, 800)
  } catch (err) {
    newsErrorMessage.value = err.message || 'Unable to save news article.'
  } finally {
    newsSaving.value = false
  }
}

async function deleteNewsItem(article) {
  if (!window.confirm(`Delete news "${article.title}"?`)) return
  newsSaving.value = true
  newsErrorMessage.value = ''
  try {
    await trmsStore.deleteNews(article.id)
    await trmsStore.fetchNews()
  } catch (err) {
    newsErrorMessage.value = err.message || 'Unable to delete news article.'
  } finally {
    newsSaving.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([trmsStore.fetchSchedules(), trmsStore.fetchNews()])
  } catch (err) {
    error.value = err.message || 'Failed to load dashboard data'
  } finally {
    loading.value = false
  }
})
</script>

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
          <button class="btn btn-sm" :class="activeTab === 'schedules' ? 'btn-gold' : 'btn-outline-gold'" type="button" @click="activeTab = 'schedules'">
            <i class="bi bi-calendar3 me-1"></i> Schedules
            <span class="tab-badge ms-1">{{ schedules.length }}</span>
          </button>
          <button class="btn btn-sm" :class="activeTab === 'news' ? 'btn-gold' : 'btn-outline-gold'" type="button" @click="activeTab = 'news'">
            <i class="bi bi-newspaper me-1"></i> News
            <span class="tab-badge ms-1">{{ news.length }}</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>

    <!-- Schedules tab -->
    <div v-if="activeTab === 'schedules' && !loading" class="fade-in-up">
      <div class="content-card bg-dark">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
          <div>
            <h2 class="h4 fw-bold mb-1" style="color: #fffdf8">All Schedules</h2>
            <p class="mb-0 text-champagne-muted">{{ schedules.length }} schedule{{ schedules.length === 1 ? '' : 's' }}</p>
          </div>
          <button class="btn btn-gold" type="button" @click="openAddSchedule">
            <i class="bi bi-plus-lg me-2"></i>Add Schedule
          </button>
        </div>

        <div v-if="schedules.length === 0" class="text-center py-5">
          <i class="bi bi-calendar-x d-block fs-1 text-white-50 mb-3"></i>
          <h3 class="h5 text-white fw-bold">No schedules found</h3>
          <p class="text-champagne-muted">Create your first schedule to get started.</p>
          <button class="btn btn-gold mt-2" type="button" @click="openAddSchedule">
            <i class="bi bi-plus-lg me-2"></i>Add Schedule
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
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
              <tr v-for="s in sortedSchedules" :key="s.id">
                <td class="fw-semibold">{{ s.title }}</td>
                <td>
                  <span class="type-badge" :class="'type-badge--' + s.type">{{ typeLabel(s.type) }}</span>
                </td>
                <td class="text-champagne-muted"><i class="bi bi-calendar3 me-1 opacity-50"></i>{{ formatDate(s.date) }}</td>
                <td class="text-champagne-muted"><i class="bi bi-clock me-1 opacity-50"></i>{{ s.start_time }} – {{ s.end_time }}</td>
                <td class="text-champagne-muted">{{ s.venue || '—' }}</td>
                <td class="text-center text-nowrap">
                  <button class="btn btn-sm btn-outline-gold me-1" type="button" title="Edit" @click="openEditSchedule(s)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" type="button" title="Delete" @click="deleteScheduleItem(s)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- News tab -->
    <div v-if="activeTab === 'news' && !loading" class="fade-in-up">
      <div class="content-card bg-dark">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
          <div>
            <h2 class="h4 fw-bold mb-1" style="color: #fffdf8">All News</h2>
            <p class="mb-0 text-champagne-muted">{{ news.length }} article{{ news.length === 1 ? '' : 's' }}</p>
          </div>
          <button class="btn btn-gold" type="button" @click="openAddNews">
            <i class="bi bi-plus-lg me-2"></i>Add News
          </button>
        </div>

        <div v-if="news.length === 0" class="text-center py-5">
          <i class="bi bi-newspaper d-block fs-1 text-white-50 mb-3"></i>
          <h3 class="h5 text-white fw-bold">No news articles found</h3>
          <p class="text-champagne-muted">Publish your first news article.</p>
          <button class="btn btn-gold mt-2" type="button" @click="openAddNews">
            <i class="bi bi-plus-lg me-2"></i>Add News
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Programs</th>
                <th>Published</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="article in sortedNews" :key="article.id">
                <td class="fw-semibold">{{ article.title }}</td>
                <td>
                  <span v-for="p in (article.program_ids?.length ? article.program_ids : [article.program_id || 'trms'])" :key="p" class="prog-badge" :class="'prog-badge--' + p">
                    {{ programLabel(p) }}
                  </span>
                </td>
                <td class="text-champagne-muted"><i class="bi bi-calendar3 me-1 opacity-50"></i>{{ formatDate(article.published_at) }}</td>
                <td class="text-center text-nowrap">
                  <button class="btn btn-sm btn-outline-gold me-1" type="button" title="Edit" @click="openEditNews(article)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" type="button" title="Delete" @click="deleteNewsItem(article)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="loading" class="d-flex justify-content-center py-5">
      <div class="spinner-border text-warning" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <ScheduleFormModal
        ref="scheduleFormModal"
        :loading="scheduleSaving"
        :success-message="scheduleSuccessMessage"
        :error-message="scheduleErrorMessage"
        @submit="submitSchedule"
        @delete="deleteScheduleFromModal"
    />

    <!-- News form modal -->
    <div v-if="showNewsModal" class="modal fade show d-block" tabindex="-1" @click.self="showNewsModal = false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title text-warning fw-bold">
              <i class="bi bi-newspaper me-2"></i>
              {{ editingNews ? 'Edit News' : 'Add News' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showNewsModal = false"></button>
          </div>
          <div class="modal-body">
            <div v-if="newsSuccessMessage" class="alert alert-success d-flex align-items-center gap-2" role="alert">
              <i class="bi bi-check-circle-fill"></i><span>{{ newsSuccessMessage }}</span>
            </div>
            <div v-if="newsErrorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
              <i class="bi bi-exclamation-triangle-fill"></i><span>{{ newsErrorMessage }}</span>
            </div>
            <form @submit.prevent="submitNews">
              <div class="mb-3">
                <label for="newsTitle" class="form-label small text-uppercase text-white-50">Title</label>
                <input id="newsTitle" v-model.trim="newsForm.title" type="text" maxlength="150" class="form-control bg-dark text-white border-secondary" required placeholder="News title" />
              </div>
              <div class="mb-3">
                <label for="newsContent" class="form-label small text-uppercase text-white-50">Content</label>
                <textarea id="newsContent" v-model.trim="newsForm.content" rows="5" class="form-control bg-dark text-white border-secondary" required placeholder="Article content..."></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label small text-uppercase text-white-50 d-block">Programs</label>
                <div class="d-flex flex-wrap gap-4 p-3 rounded border border-secondary border-opacity-25">
                  <div class="form-check" v-for="prog in PROGRAM_OPTIONS" :key="prog.id">
                    <input :id="'news-prog-' + prog.id" class="form-check-input" type="checkbox" :value="prog.id" v-model="newsForm.program_ids" />
                    <label :for="'news-prog-' + prog.id" class="form-check-label text-champagne-muted">{{ prog.name }}</label>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="newsDate" class="form-label small text-uppercase text-white-50">Published Date</label>
                <input id="newsDate" v-model="newsForm.published_at" type="date" class="form-control bg-dark text-white border-secondary" required />
              </div>
              <div class="d-flex gap-3">
                <button class="btn btn-gold" type="submit" :disabled="newsSaving">
                  <span v-if="newsSaving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                  <i v-else class="bi bi-check-circle me-2"></i>
                  {{ newsSaving ? 'Saving...' : (editingNews ? 'Update' : 'Publish') }}
                </button>
                <button class="btn btn-outline-secondary" type="button" @click="showNewsModal = false">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showNewsModal" class="modal-backdrop fade show"></div>
  </div>
</template>

<style scoped>
.text-champagne {
  color: var(--champagne-color);
}

.text-champagne-muted {
  color: rgba(234, 220, 194, 0.55);
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

.type-badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  font-size: 0.64rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-radius: 20px;
  border: 1px solid transparent;
}

.type-badge--lesson {
  color: #8bb9fe;
  background: rgba(110, 168, 254, 0.12);
  border-color: rgba(110, 168, 254, 0.2);
}

.type-badge--practice {
  color: #8bcfad;
  background: rgba(117, 183, 152, 0.12);
  border-color: rgba(117, 183, 152, 0.2);
}

.type-badge--concert {
  color: #ffe08a;
  background: rgba(255, 218, 106, 0.12);
  border-color: rgba(255, 218, 106, 0.2);
}

.type-badge--exam {
  color: #f09aa2;
  background: rgba(234, 134, 143, 0.12);
  border-color: rgba(234, 134, 143, 0.2);
}

.type-badge--other {
  color: rgba(234, 220, 194, 0.6);
  background: rgba(234, 220, 194, 0.06);
  border-color: rgba(234, 220, 194, 0.1);
}

.prog-badge {
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

.prog-badge--trms {
  color: #8bb9fe;
  background: rgba(110, 168, 254, 0.1);
  border-color: rgba(110, 168, 254, 0.16);
}

.prog-badge--bms {
  color: #8bcfad;
  background: rgba(117, 183, 152, 0.1);
  border-color: rgba(117, 183, 152, 0.16);
}

.prog-badge--jco {
  color: #ffe08a;
  background: rgba(255, 218, 106, 0.1);
  border-color: rgba(255, 218, 106, 0.16);
}

.prog-badge--trcc {
  color: #8ae3f5;
  background: rgba(110, 223, 246, 0.1);
  border-color: rgba(110, 223, 246, 0.16);
}

@media (prefers-reduced-motion: reduce) {
  .fade-in-up {
    animation: none;
  }
}
</style>