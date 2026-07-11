<template>
  <div class="fade-in-up">
    <div class="content-card bg-dark mb-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <p class="sheet-eyebrow mb-1">Library</p>
          <h1 class="sheet-title mb-0">Sheet Music</h1>
        </div>
        <div class="d-flex gap-2">
          <button v-if="isAdmin" class="btn btn-gold d-flex align-items-center gap-2" @click="openCreate">
            <i class="bi bi-plus-lg"></i><span>Add Score</span>
          </button>
          <button class="btn btn-sm" :class="viewMode === 'grid' ? 'btn-gold' : 'btn-outline-gold'" @click="viewMode = 'grid'">
            <i class="bi bi-grid-3x3-gap-fill"></i>
          </button>
          <button class="btn btn-sm" :class="viewMode === 'list' ? 'btn-gold' : 'btn-outline-gold'" @click="viewMode = 'list'">
            <i class="bi bi-list-ul"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="filters-bar mb-4">
      <div class="row g-2">
        <div class="col-12 col-md-6">
          <div class="search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input v-model="search" type="text" class="form-control-dark search-input" placeholder="Search by title, composer, or arranger…" />
            <button v-if="search" class="search-clear" @click="search = ''" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterGenre" class="filter-select">
            <option value="">All Genres</option>
            <option v-for="g in genres" :key="g" :value="g">{{ g }}</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterComposer" class="filter-select">
            <option value="">All Composers</option>
            <option v-for="c in composers" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
      </div>
      <div class="filter-chips mt-2">
        <button class="filter-chip" :class="{ active: filterDifficulty === '' }" @click="filterDifficulty = ''">All Levels</button>
        <button v-for="d in difficulties" :key="d" class="filter-chip" :class="{ active: filterDifficulty === d }" @click="filterDifficulty = d">{{ d }}</button>
      </div>
    </div>

    <div v-if="loading" class="loading-state"><div class="loading-ring"></div><p>Loading sheet music…</p></div>

    <template v-else>
      <div v-if="filteredScores.length === 0" class="empty-state content-card bg-dark">
        <div class="empty-icon"><i class="bi bi-music-note"></i></div>
        <h5 class="mt-3 mb-1 text-champagne">No scores found</h5>
        <p class="text-champagne-muted mb-0">Try adjusting your filters.</p>
      </div>

      <div v-if="viewMode === 'grid'" class="scores-grid">
        <article v-for="(score, idx) in filteredScores" :key="score.id" class="score-card" :style="`animation-delay:${idx * 0.04}s`" @click="openDetail(score)">
          <div class="score-thumb-wrap">
            <img :src="score.thumbnail || defaultThumb" :alt="score.title" class="score-thumb" loading="lazy" />
            <div class="score-thumb-overlay"><i class="bi bi-eye-fill"></i></div>
            <span class="score-badge">{{ score.difficulty }}</span>
          </div>
          <div class="score-body">
            <h6 class="score-title">{{ score.title }}</h6>
            <p class="score-composer">{{ score.composer }}</p>
            <div class="score-meta">
              <span class="score-meta-item"><i class="bi bi-tag"></i>{{ score.genre }}</span>
              <span class="score-meta-item"><i class="bi bi-file-earmark-text"></i>{{ score.pages }} pp</span>
            </div>
          </div>
          <div v-if="isAdmin" class="score-actions" @click.stop>
            <button class="action-btn action-edit" title="Edit" @click="openEdit(score)"><i class="bi bi-pencil"></i></button>
            <button class="action-btn action-delete" title="Delete" @click="confirmDelete(score)"><i class="bi bi-trash3"></i></button>
          </div>
        </article>
      </div>

      <div v-if="viewMode === 'list'" class="scores-list content-card bg-dark p-0">
        <div class="list-header">
          <span class="lh-thumb"></span><span class="lh-title">Title</span><span class="lh-composer">Composer</span>
          <span class="lh-arranger">Arranger</span><span class="lh-genre">Genre</span><span class="lh-difficulty">Level</span>
          <span class="lh-pages">Pages</span><span v-if="isAdmin" class="lh-actions">Actions</span>
        </div>
        <div v-for="(score, idx) in filteredScores" :key="score.id" class="list-row" :style="`animation-delay:${idx * 0.03}s`" @click="openDetail(score)">
          <span class="lh-thumb"><img :src="score.thumbnail || defaultThumb" :alt="score.title" class="list-thumb" loading="lazy" /></span>
          <span class="lh-title"><span class="list-title">{{ score.title }}</span></span>
          <span class="lh-composer list-muted">{{ score.composer }}</span>
          <span class="lh-arranger list-muted">{{ score.arranger }}</span>
          <span class="lh-genre"><span class="genre-pill">{{ score.genre }}</span></span>
          <span class="lh-difficulty"><span class="diff-badge" :class="diffClass(score.difficulty)">{{ score.difficulty }}</span></span>
          <span class="lh-pages list-muted">{{ score.pages }}</span>
          <span v-if="isAdmin" class="lh-actions list-actions" @click.stop>
            <button class="action-btn action-edit" title="Edit" @click="openEdit(score)"><i class="bi bi-pencil"></i></button>
            <button class="action-btn action-delete" title="Delete" @click="confirmDelete(score)"><i class="bi bi-trash3"></i></button>
          </span>
        </div>
      </div>

      <div v-if="filteredScores.length > 0" class="result-count">
        Showing {{ filteredScores.length }} score{{ filteredScores.length !== 1 ? 's' : '' }}
      </div>
    </template>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="selectedScore && !showForm && !deleteTarget" class="modal-overlay" @click.self="selectedScore = null">
          <div class="modal-sheet modal-sheet-dark" role="dialog" aria-modal="true">
            <button class="modal-close-btn modal-close-btn-dark" @click="selectedScore = null" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="d-flex flex-column flex-md-row gap-4">
              <div class="detail-thumb-wrap"><img :src="selectedScore.thumbnail || defaultThumb" :alt="selectedScore.title" class="detail-thumb" /></div>
              <div class="flex-fill">
                <h3 class="detail-title">{{ selectedScore.title }}</h3>
                <div class="detail-field"><span class="df-label">Composer</span><span class="df-value">{{ selectedScore.composer }}</span></div>
                <div class="detail-field"><span class="df-label">Arranger</span><span class="df-value">{{ selectedScore.arranger || '–' }}</span></div>
                <div class="detail-field"><span class="df-label">Genre</span><span class="df-value">{{ selectedScore.genre }}</span></div>
                <div class="detail-field"><span class="df-label">Difficulty</span><span class="df-value"><span class="diff-badge" :class="diffClass(selectedScore.difficulty)">{{ selectedScore.difficulty }}</span></span></div>
                <div class="detail-field"><span class="df-label">Pages</span><span class="df-value">{{ selectedScore.pages }}</span></div>
                <div class="detail-field" v-if="selectedScore.file_url">
                  <span class="df-label">PDF</span>
                  <span class="df-value">
                    <a :href="selectedScore.file_url" target="_blank" class="pdf-link"><i class="bi bi-filetype-pdf me-1"></i>View PDF</a>
                  </span>
                </div>
                <div v-if="isAdmin" class="d-flex gap-2 mt-3">
                  <button class="btn btn-sm btn-outline-gold" @click="openEdit(selectedScore)"><i class="bi bi-pencil me-1"></i>Edit</button>
                  <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(selectedScore)"><i class="bi bi-trash3 me-1"></i>Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
          <div class="modal-sheet modal-sheet-dark" role="dialog" aria-modal="true">
            <button class="modal-close-btn modal-close-btn-dark" @click="closeForm" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="modal-header-row d-flex align-items-center gap-2 mb-3">
              <div class="modal-icon-wrap"><i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i></div>
              <div><h5 class="mb-0 text-champagne">{{ isEditing ? 'Edit Score' : 'Add Score' }}</h5><p class="text-champagne-muted mb-0 small">{{ isEditing ? 'Update the score details' : 'Fill in the details below' }}</p></div>
            </div>
            <div v-if="formError" class="alert alert-danger py-2 small" style="background:rgba(127,36,50,0.2);border-color:rgba(127,36,50,0.5);color:#e8a0a8">{{ formError }}</div>
            <form @submit.prevent="submitForm">
              <div class="row g-3">
                <div class="col-12"><label class="form-label small fw-bold text-champagne">Title <span class="text-danger">*</span></label><input v-model="form.title" type="text" class="form-control-dark form-control-sm" placeholder="Score title" required /></div>
                <div class="col-12 col-sm-6"><label class="form-label small fw-bold text-champagne">Composer</label><input v-model="form.composer" type="text" class="form-control-dark form-control-sm" placeholder="Composer name" /></div>
                <div class="col-12 col-sm-6"><label class="form-label small fw-bold text-champagne">Arranger</label><input v-model="form.arranger" type="text" class="form-control-dark form-control-sm" placeholder="Arranger name" /></div>
                <div class="col-12 col-sm-4"><label class="form-label small fw-bold text-champagne">Genre</label>
                  <select v-model="form.genre" class="form-control-dark form-select-sm">
                    <option value="">Select genre</option><option v-for="g in allGenres" :key="g" :value="g">{{ g }}</option>
                  </select>
                </div>
                <div class="col-12 col-sm-4"><label class="form-label small fw-bold text-champagne">Difficulty</label>
                  <select v-model="form.difficulty" class="form-control-dark form-select-sm">
                    <option value="Beginner">Beginner</option><option value="Intermediate">Intermediate</option><option value="Advanced">Advanced</option>
                  </select>
                </div>
                <div class="col-12 col-sm-4"><label class="form-label small fw-bold text-champagne">Pages</label><input v-model.number="form.pages" type="number" min="0" class="form-control-dark form-control-sm" /></div>
                <div class="col-12"><label class="form-label small fw-bold text-champagne">Thumbnail URL</label><input v-model="form.thumbnail" type="url" class="form-control-dark form-control-sm" placeholder="https://…" /></div>
                <div v-if="isAdmin" class="col-12">
                  <label class="form-label small fw-bold text-champagne">Upload PDF</label>
                  <input ref="pdfInput" type="file" accept=".pdf,application/pdf" class="form-control-dark form-control-sm" @change="onPdfSelect" :disabled="uploadingPdf" />
                  <div v-if="uploadingPdf" class="mt-1 small text-champagne-muted d-flex align-items-center gap-1"><span class="spinner-border spinner-border-sm"></span>Uploading PDF…</div>
                  <div v-else-if="form.file_url" class="mt-1 small"><a :href="form.file_url" target="_blank" class="pdf-link"><i class="bi bi-filetype-pdf me-1"></i>Current PDF</a></div>
                  <div v-else-if="pendingPdfFile" class="mt-1 small text-champagne-muted d-flex align-items-center gap-1"><i class="bi bi-paperclip"></i>{{ pendingPdfFile.name }} <span class="badge bg-warning text-dark">will upload on save</span></div>
                </div>
              </div>
              <div class="d-flex gap-2 justify-content-end mt-4">
                <button type="button" class="btn btn-sm btn-outline-gold" @click="closeForm">Cancel</button>
                <button type="submit" class="btn btn-sm btn-gold" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>{{ isEditing ? 'Save Changes' : 'Add Score' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="deleteTarget" class="modal-overlay" @click.self="cancelDelete">
          <div class="modal-sheet modal-sheet-dark modal-sheet--sm text-center" role="dialog" aria-modal="true">
            <div class="delete-icon-wrap"><i class="bi bi-trash3-fill" style="font-size:2rem;color:var(--gold-color)"></i></div>
            <h5 class="mt-3 mb-1 text-champagne">Delete Score</h5>
            <p class="text-champagne-muted mb-4 small">Remove <strong style="color:rgba(234,220,194,0.92)">{{ deleteTarget.title }}</strong>? This cannot be undone.</p>
            <div class="d-flex gap-2 justify-content-center">
              <button class="btn btn-sm btn-outline-gold" @click="cancelDelete" :disabled="deleting">Cancel</button>
              <button class="btn btn-sm btn-danger" @click="doDelete" :disabled="deleting">
                <span v-if="deleting" class="spinner-border spinner-border-sm me-1"></span>Delete
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script>
import { computed, ref } from 'vue'
import { useLibraryStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'

const defaultThumb = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 260%22%3E%3Crect fill=%22%23f5f0e8%22 width=%22200%22 height=%22260%22/%3E%3Ctext x=%22100%22 y=%22140%22 text-anchor=%22middle%22 fill=%22%23c8a45d%22 font-size=%2248%22 font-family=%22serif%22%3E%E2%99%AA%3C/text%3E%3C/svg%3E'

const emptyForm = () => ({
  title: '', composer: '', arranger: '', genre: '', difficulty: 'Intermediate', pages: 0, thumbnail: '', file_url: '',
})

export default {
  name: 'SheetMusic',
  setup() {
    const store = useLibraryStore()
    const authStore = useAuthStore()
    const userRole = computed(() => authStore.user?.role?.toLowerCase())
    const isAdmin = computed(() => userRole.value === 'admin' || userRole.value === 'manager')

    const search = ref('')
    const filterGenre = ref('')
    const filterComposer = ref('')
    const filterDifficulty = ref('')
    const viewMode = ref('grid')
    const selectedScore = ref(null)

    const genres = computed(() => store.genres)
    const composers = computed(() => store.composers)
    const allGenres = ['Classical', 'Contemporary', 'Jazz', 'Pop', 'Sacred', 'Traditional']
    const difficulties = ['Beginner', 'Intermediate', 'Advanced']
    const loading = computed(() => store.loading)

    const filteredScores = computed(() => {
      const q = search.value.trim().toLowerCase()
      return store.scores.filter((s) => {
        const matchSearch = !q || s.title.toLowerCase().includes(q) || s.composer.toLowerCase().includes(q) || (s.arranger || '').toLowerCase().includes(q)
        const matchGenre = !filterGenre.value || s.genre === filterGenre.value
        const matchComposer = !filterComposer.value || s.composer === filterComposer.value
        const matchDiff = !filterDifficulty.value || s.difficulty === filterDifficulty.value
        return matchSearch && matchGenre && matchComposer && matchDiff
      })
    })

    const diffClass = (d) => ({ 'diff-beginner': d === 'Beginner', 'diff-intermediate': d === 'Intermediate', 'diff-advanced': d === 'Advanced' })

    if (!store.scores.length) store.fetchScores()

    const showForm = ref(false)
    const isEditing = ref(false)
    const editingId = ref(null)
    const form = ref(emptyForm())
    const formError = ref('')
    const submitting = ref(false)
    const deleteTarget = ref(null)
    const deleting = ref(false)
    const pdfInput = ref(null)
    const uploadingPdf = ref(false)
    const pendingPdfFile = ref(null)

    const openCreate = () => {
      isEditing.value = false; editingId.value = null; form.value = emptyForm(); formError.value = ''; pendingPdfFile.value = null; if (pdfInput.value) pdfInput.value.value = ''; showForm.value = true
    }
    const openEdit = (score) => {
      isEditing.value = true; editingId.value = score.id; form.value = { title: score.title, composer: score.composer, arranger: score.arranger || '', genre: score.genre, difficulty: score.difficulty, pages: score.pages, thumbnail: score.thumbnail || '', file_url: score.file_url || '' }; formError.value = ''; pendingPdfFile.value = null; if (pdfInput.value) pdfInput.value.value = ''; showForm.value = true
    }
    const closeForm = () => { showForm.value = false; formError.value = ''; uploadingPdf.value = false; pendingPdfFile.value = null }

    const onPdfSelect = (e) => {
      const file = e.target.files?.[0]
      if (!file) return
      if (editingId.value) {
        uploadingPdf.value = true
        store.uploadScorePdf(editingId.value, file).then((r) => {
          if (r?.data?.file_url) form.value.file_url = r.data.file_url
        }).catch((err) => { formError.value = err.message || 'PDF upload failed' }).finally(() => { uploadingPdf.value = false; if (pdfInput.value) pdfInput.value.value = '' })
      } else {
        pendingPdfFile.value = file
      }
    }

    const validate = () => {
      if (!form.value.title.trim()) { formError.value = 'Title is required'; return false }
      return true
    }

    const submitForm = async () => {
      if (!validate()) return
      submitting.value = true; formError.value = ''
      try {
        if (isEditing.value) {
          await store.updateScore(editingId.value, form.value)
        } else {
          const result = await store.createScore(form.value)
          const newId = result?.data?.id || result?.id
          if (newId && pendingPdfFile.value) {
            uploadingPdf.value = true
            await store.uploadScorePdf(newId, pendingPdfFile.value).then((r) => {
              if (r?.data?.file_url) form.value.file_url = r.data.file_url
            })
            uploadingPdf.value = false; pendingPdfFile.value = null
          }
        }
        closeForm()
      } catch (err) { formError.value = err.message || 'Something went wrong' }
      finally { submitting.value = false }
    }

    const openDetail = (score) => { selectedScore.value = null; requestAnimationFrame(() => { selectedScore.value = score }) }
    const confirmDelete = (score) => { selectedScore.value = null; deleteTarget.value = score }
    const cancelDelete = () => { deleteTarget.value = null }
    const doDelete = async () => {
      deleting.value = true
      try { await store.deleteScore(deleteTarget.value.id); cancelDelete() }
      catch (err) { alert('Delete failed: ' + (err.message || 'Unknown error')); deleting.value = false }
    }

    return {
      defaultThumb, store, search, filterGenre, filterComposer, filterDifficulty, viewMode, selectedScore,
      genres, composers, allGenres, difficulties, loading, filteredScores, diffClass, isAdmin,
      showForm, isEditing, form, formError, submitting, deleteTarget, deleting,
      openCreate, openEdit, openDetail, closeForm, submitForm, confirmDelete, cancelDelete, doDelete,
      pdfInput, uploadingPdf, pendingPdfFile, onPdfSelect,
    }
  },
}
</script>

<style scoped>
/* ── Dark overrides ── */
.content-card.bg-dark {
  color: rgba(234, 220, 194, 0.78);
}

/* ── Layout ── */
.sheet-eyebrow { font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-color) }
.sheet-title { font-size:clamp(1.4rem,4vw,2rem);font-weight:800;color:rgba(234,220,194,0.92) !important;letter-spacing:-.01em }

/* ── Filters ── */
.search-wrap { position:relative;display:flex;align-items:center }
.search-icon { position:absolute;left:.85rem;color:rgba(234,220,194,0.4);font-size:.9rem;pointer-events:none }
.search-input.form-control-dark { width:100%;padding:.6rem 2.5rem .6rem 2.4rem;background:rgba(26,31,48,0.6);border-color:rgba(234,220,194,0.1);color:rgba(234,220,194,0.78) }
.search-input.form-control-dark:focus { outline:none;border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.18) }
.search-clear { position:absolute;right:.7rem;border:0;background:transparent;color:rgba(234,220,194,0.4);cursor:pointer;padding:.1rem .3rem;font-size:.8rem;border-radius:4px;transition:color .15s }
.search-clear:hover { color:rgba(234,220,194,0.78) }
.filter-select { width:100%;padding:.55rem .85rem;border:1px solid rgba(234,220,194,0.1);border-radius:var(--radius-md);background:rgba(26,31,48,0.6);color:rgba(234,220,194,0.78);font-size:.85rem;cursor:pointer;transition:border-color .2s }
.filter-select:focus { outline:none;border-color:var(--gold-color) }
.filter-chips { display:flex;flex-wrap:wrap;gap:.4rem }
.filter-chip { display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .85rem;border:1px solid rgba(234,220,194,0.1);border-radius:999px;background:rgba(26,31,48,0.45);color:rgba(234,220,194,0.55);font-size:.8rem;font-weight:600;cursor:pointer;transition:all .18s ease;user-select:none }
.filter-chip:hover { border-color:var(--gold-color);color:rgba(234,220,194,0.92);background:rgba(200,164,93,.1) }
.filter-chip.active { border-color:var(--gold-color);background:rgba(200,164,93,.15);color:rgba(234,220,194,0.92) }

/* ── States ── */
.loading-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 1rem;color:rgba(234,220,194,0.5);gap:1rem }
.loading-ring { width:42px;height:42px;border:3px solid rgba(200,164,93,.2);border-top-color:var(--gold-color);border-radius:50%;animation:spin .7s linear infinite }
@keyframes spin { to { transform:rotate(360deg) } }
.empty-state.content-card.bg-dark { text-align:center;padding:4rem 2rem;color:rgba(234,220,194,0.78) }
.empty-icon { display:inline-grid;place-items:center;width:72px;height:72px;border-radius:50%;background:rgba(200,164,93,.1);border:1px solid rgba(200,164,93,.2) }
.empty-icon i { font-size:2rem;color:var(--gold-color) }

/* ── Grid ── */
.scores-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem }
@media (min-width:576px) { .scores-grid { grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem } }
@media (min-width:1200px) { .scores-grid { grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.5rem } }

.score-card { border-radius:var(--radius-md);border:1px solid rgba(234,220,194,0.08);background:rgba(26,31,48,0.5);box-shadow:0 2px 12px rgba(0,0,0,.2);overflow:hidden;display:flex;flex-direction:column;animation:fadeInUp .4s ease-out both;cursor:pointer;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease }
.score-card:active { transform:translateY(-1px) }
@media (hover:hover) {
  .score-card:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.3);border-color:rgba(200,164,93,.4) }
}

.score-thumb-wrap { position:relative;height:180px;overflow:hidden;background:rgba(26,31,48,0.3);flex-shrink:0 }
@media (min-width:576px) { .score-thumb-wrap { height:220px } }
.score-thumb { width:100%;height:100%;object-fit:cover;transition:transform .35s ease }
@media (hover:hover) { .score-card:hover .score-thumb { transform:scale(1.04) } }
.score-thumb-overlay { position:absolute;inset:0;display:grid;place-items:center;background:rgba(16,19,31,.45);color:#fff;font-size:1.4rem;opacity:0;transition:opacity .22s ease }
@media (hover:hover) { .score-card:hover .score-thumb-overlay { opacity:1 } }
.score-badge { position:absolute;top:10px;left:0;padding:.18rem .75rem .18rem .6rem;border-radius:0 999px 999px 0;font-size:.7rem;font-weight:700;color:#fff;letter-spacing:.05em;text-transform:uppercase;box-shadow:0 2px 8px rgba(0,0,0,.2);background:var(--accent-color) }
.score-body { padding:.75rem .85rem .5rem;flex:1 }
.score-title { margin:0;font-size:.92rem;font-weight:700;color:rgba(234,220,194,0.92);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.score-composer { font-size:.78rem;color:rgba(234,220,194,0.5);margin:.15rem 0 .6rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.score-meta { display:flex;gap:.6rem;flex-wrap:wrap }
.score-meta-item { display:inline-flex;align-items:center;gap:.25rem;font-size:.73rem;color:rgba(234,220,194,0.45) }
.score-meta-item i { font-size:.7rem }
.score-actions { display:flex;border-top:1px solid rgba(234,220,194,0.08);padding:.45rem .65rem;gap:.35rem }

/* ── List ── */
.scores-list.content-card.bg-dark { overflow:hidden;color:rgba(234,220,194,0.78) }
.list-header { display:grid;grid-template-columns:48px 2fr 1.2fr 1.2fr 1fr .9fr .6fr;gap:.5rem;padding:.75rem 1rem;border-bottom:1px solid rgba(234,220,194,0.08);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(234,220,194,0.5) }
.list-row { display:grid;grid-template-columns:48px 2fr 1.2fr 1.2fr 1fr .9fr .6fr;gap:.5rem;padding:.6rem 1rem;align-items:center;cursor:pointer;animation:fadeInUp .3s ease-out both;transition:background .15s }
.list-row:hover { background:rgba(200,164,93,.06) }
.list-thumb { width:36px;height:48px;object-fit:cover;border-radius:4px;display:block }
.list-title { font-weight:600;font-size:.9rem;color:rgba(234,220,194,0.92);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.list-muted { font-size:.82rem;color:rgba(234,220,194,0.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.genre-pill { display:inline-block;padding:.15rem .5rem;border-radius:999px;font-size:.7rem;font-weight:600;background:rgba(200,164,93,.12);color:var(--gold-color);white-space:nowrap }
.diff-badge { display:inline-block;padding:.1rem .45rem;border-radius:999px;font-size:.7rem;font-weight:700;text-transform:uppercase;white-space:nowrap }
.diff-beginner { background:rgba(74,124,89,.15);color:#4a7c59 }
.diff-intermediate { background:rgba(200,164,93,.18);color:#9d7d3b }
.diff-advanced { background:rgba(192,57,43,.12);color:#c0392b }
.result-count { text-align:center;margin-top:1.25rem;font-size:.8rem;color:rgba(234,220,194,0.5) }

.action-btn { flex:1;border:1px solid rgba(234,220,194,0.08);border-radius:6px;background:transparent;color:rgba(234,220,194,0.45);font-size:.82rem;padding:.32rem;cursor:pointer;transition:all .18s ease;line-height:1;display:inline-flex;align-items:center;justify-content:center }
.action-btn:hover { border-color:rgba(234,220,194,0.3);color:rgba(234,220,194,0.78);background:rgba(234,220,194,0.06) }
.action-btn.action-edit:hover { border-color:var(--gold-color);color:var(--gold-color);background:rgba(200,164,93,.08) }
.action-btn.action-delete:hover { border-color:#c0392b;color:#c0392b;background:rgba(192,57,43,.07) }
.list-actions { display:flex;gap:.25rem }
.list-actions .action-btn { flex:0 0 auto;padding:.2rem .45rem }

/* ── List responsive (hide less important cols on small screens) ── */
@media (max-width:767.98px) {
  .list-header { grid-template-columns:40px 2fr 1fr .7fr;font-size:.65rem;padding:.6rem .75rem }
  .list-row { grid-template-columns:40px 2fr 1fr .7fr;padding:.5rem .75rem }
  .lh-arranger,.lh-pages,.lh-difficulty { display:none }
  .list-thumb { width:30px;height:40px }
}
@media (max-width:575.98px) {
  .list-header { grid-template-columns:2fr 1fr;padding:.5rem .65rem }
  .list-row { grid-template-columns:2fr 1fr;padding:.45rem .65rem }
  .lh-thumb,.lh-genre { display:none }
}

/* ── Modals ── */
.modal-overlay { position:fixed;inset:0;z-index:1050;background:rgba(10,10,15,.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1rem;overflow-y:auto }
@media (min-width:576px) { .modal-overlay { padding:1.5rem } }
.modal-sheet { position:relative;border-radius:14px;border:1px solid var(--hairline-color);box-shadow:0 32px 72px rgba(10,10,15,.36),0 0 0 1px rgba(200,164,93,.1);width:100%;max-width:640px;padding:1.25rem;margin:auto }
@media (min-width:576px) { .modal-sheet { padding:1.75rem } }
.modal-sheet-dark { background:linear-gradient(135deg,rgba(26,31,48,0.98),rgba(17,20,32,0.98));border-color:rgba(234,220,194,0.1);box-shadow:0 32px 72px rgba(0,0,0,.5),0 0 0 1px rgba(200,164,93,.1) }
.modal-sheet--sm { max-width:400px;padding:1.5rem 1.25rem }
@media (min-width:576px) { .modal-sheet--sm { padding:2rem } }
.modal-close-btn { position:absolute;top:.75rem;right:.75rem;border:0;width:34px;height:34px;border-radius:8px;display:grid;place-items:center;font-size:.85rem;cursor:pointer;transition:background .18s,color .18s }
.modal-close-btn-dark { background:rgba(200,164,93,0.1);color:rgba(234,220,194,0.5) }
.modal-close-btn-dark:hover { background:var(--gold-color);color:#fff }
.modal-icon-wrap { display:grid;place-items:center;width:40px;height:40px;border-radius:10px;background:rgba(200,164,93,.12);border:1px solid rgba(200,164,93,.2);color:var(--gold-color);font-size:1.2rem;flex-shrink:0 }

/* ── Detail modal ── */
.detail-thumb-wrap { flex-shrink:0;width:100%;max-width:180px;margin:0 auto 1rem }
@media (min-width:576px) { .detail-thumb-wrap { width:180px;margin:0 } }
.detail-thumb { width:100%;height:auto;aspect-ratio:5/7;object-fit:cover;border-radius:10px;display:block }
.detail-title { font-size:clamp(1.1rem,3vw,1.35rem);font-weight:800;color:rgba(234,220,194,0.92);margin-bottom:1rem;line-height:1.25 }
.detail-field { display:flex;align-items:baseline;gap:.5rem;padding:.4rem 0;border-bottom:1px solid rgba(234,220,194,0.08) }
.detail-field:last-child { border-bottom:none }
.df-label { font-size:.78rem;font-weight:600;color:rgba(234,220,194,0.5);min-width:80px;flex-shrink:0 }
.df-value { font-size:.9rem;color:rgba(234,220,194,0.78);word-break:break-word }
.pdf-link { color:var(--gold-color);text-decoration:none;display:inline-flex;align-items:center;gap:.25rem;font-weight:600;transition:color .15s }
.pdf-link:hover { color:rgba(234,220,194,0.92);text-decoration:underline }

/* ── Transitions ── */
.modal-enter-active,.modal-leave-active { transition:opacity .25s ease }
.modal-enter-active .modal-sheet,.modal-leave-active .modal-sheet { transition:transform .25s ease,opacity .25s ease }
.modal-enter-from,.modal-leave-to { opacity:0 }
.modal-enter-from .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }
.modal-leave-to .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }

@keyframes fadeInUp { from { opacity:0;transform:translateY(16px) } to { opacity:1;transform:translateY(0) } }

/* ── Delete modal ── */
.delete-icon-wrap { display:grid;place-items:center;width:48px;height:48px;border-radius:50%;background:rgba(192,57,43,.1);margin:0 auto }
.delete-icon-wrap i { font-size:1.4rem;color:#c0392b }
</style>
