<template>
  <div class="fade-in-up">
    <header class="dash-header content-card bg-dark mb-4">
      <div class="dash-header-inner">
        <div class="dash-header-left">
          <div class="dash-badge">
            <i class="bi bi-pencil-square"></i>
            <span>Composer / Arranger</span>
          </div>
          <h1 class="dash-title">My Scores</h1>
          <p class="dash-subtitle" v-if="userName">
            Welcome back, <strong>{{ userName }}</strong> &mdash; you have <strong>{{ myScores.length }}</strong> score{{ myScores.length !== 1 ? 's' : '' }} published
          </p>
        </div>
        <button class="btn btn-gold dash-add-btn" @click="openCreate">
          <i class="bi bi-plus-lg"></i><span>Add Score</span>
        </button>
      </div>
    </header>

    <div class="dash-stats row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon stat-icon--total"><i class="bi bi-music-note"></i></div>
          <div class="stat-body">
            <span class="stat-value">{{ myScores.length }}</span>
            <span class="stat-label">Total Scores</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon stat-icon--paid"><i class="bi bi-cash-stack"></i></div>
          <div class="stat-body">
            <span class="stat-value">{{ paidCount }}</span>
            <span class="stat-label">Paid</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon stat-icon--free"><i class="bi bi-gift"></i></div>
          <div class="stat-body">
            <span class="stat-value">{{ freeCount }}</span>
            <span class="stat-label">Free</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon stat-icon--pdf"><i class="bi bi-filetype-pdf"></i></div>
          <div class="stat-body">
            <span class="stat-value">{{ pdfCount }}</span>
            <span class="stat-label">With PDF</span>
          </div>
        </div>
      </div>
    </div>

    <div class="dash-toolbar mb-3">
      <div class="dash-toolbar-row">
        <div class="dash-search-wrap">
          <i class="bi bi-search dash-search-icon"></i>
          <input v-model="search" type="text" class="dash-search-input" placeholder="Search your scores…" />
          <button v-if="search" class="dash-search-clear" @click="search = ''" aria-label="Clear"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="dash-toolbar-filters">
          <div class="dash-filter-group">
            <select v-model="filterGenre" class="dash-select">
              <option value="">All Genres</option>
              <option v-for="g in genres" :key="g" :value="g">{{ g }}</option>
            </select>
          </div>
          <div class="dash-filter-group">
            <select v-model="filterDifficulty" class="dash-select">
              <option value="">All Levels</option>
              <option v-for="d in difficulties" :key="d" :value="d">{{ d }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-state"><div class="loading-ring"></div><p>Loading your scores…</p></div>

    <template v-else>
      <div v-if="filteredScores.length === 0" class="empty-state content-card bg-dark">
        <div class="empty-icon"><i class="bi" :class="myScores.length === 0 ? 'bi-music-note-beamed' : 'bi-search'"></i></div>
        <h5 class="mt-3 mb-1">{{ myScores.length === 0 ? 'No scores yet' : 'No matches' }}</h5>
        <p class="dash-empty-desc">{{ myScores.length === 0 ? 'Click the Add Score button above to publish your first work.' : 'Try a different search or filter.' }}</p>
        <button v-if="myScores.length === 0" class="btn btn-gold mt-2" @click="openCreate"><i class="bi bi-plus-lg me-1"></i>Create Your First Score</button>
      </div>

      <template v-else>
        <div class="dash-table-wrap content-card bg-dark p-0">
          <div class="dt-header">
            <span class="dt-th dt-thumb"></span>
            <span class="dt-th dt-title">Title</span>
            <span class="dt-th dt-composer">Composer</span>
            <span class="dt-th dt-arranger">Arranger</span>
            <span class="dt-th dt-genre">Genre</span>
            <span class="dt-th dt-difficulty">Level</span>
            <span class="dt-th dt-price">Price</span>
            <span class="dt-th dt-orders">Orders</span>
            <span class="dt-th dt-status">Status</span>
            <span class="dt-th dt-actions"></span>
          </div>
          <div v-for="(score, idx) in filteredScores" :key="score.id" class="dt-row" :style="`animation-delay:${idx * 0.035}s`">
            <span class="dt-td dt-thumb">
              <img :src="score.thumbnail || defaultThumb" :alt="score.title" class="dt-thumb-img" loading="lazy" />
            </span>
            <span class="dt-td dt-title-cell">
              <span class="dt-score-title">{{ score.title }}</span>
              <span class="dt-score-composer-mobile">{{ score.composer }}</span>
            </span>
            <span class="dt-td dt-composer dt-text-muted">{{ score.composer }}</span>
            <span class="dt-td dt-arranger dt-text-muted">{{ score.arranger || '\u2013' }}</span>
            <span class="dt-td dt-genre"><span class="dash-pill">{{ score.genre }}</span></span>
            <span class="dt-td dt-difficulty"><span class="dash-diff" :class="diffClass(score.difficulty)">{{ score.difficulty }}</span></span>
            <span class="dt-td dt-price"><span class="dash-price">{{ score.price > 0 ? 'Rp ' + formatPrice(score.price) : 'FREE' }}</span></span>
            <span class="dt-td dt-orders"><span class="dash-orders">{{ score.order_count }}</span></span>
            <span class="dt-td dt-status">
              <span class="dash-status" :class="score.file_url ? 'dash-status--published' : 'dash-status--draft'">
                <i class="bi" :class="score.file_url ? 'bi-check-circle-fill' : 'bi-clock-fill'"></i>
                {{ score.file_url ? 'Published' : 'Draft' }}
              </span>
            </span>
            <span class="dt-td dt-actions-cell">
              <button class="dash-action dash-action--edit" title="Edit" @click="openEdit(score)"><i class="bi bi-pencil"></i></button>
              <button class="dash-action dash-action--delete" title="Delete" @click="confirmDelete(score)"><i class="bi bi-trash3"></i></button>
            </span>
          </div>
        </div>

        <div class="dash-total-row">
          Showing {{ filteredScores.length }} of {{ myScores.length }} score{{ myScores.length !== 1 ? 's' : '' }}
        </div>
      </template>
    </template>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
          <div class="modal-sheet modal-sheet-dark modal-sheet--form" role="dialog" aria-modal="true">
            <button class="modal-close-btn modal-close-btn-dark" @click="closeForm" aria-label="Close"><i class="bi bi-x-lg"></i></button>

            <div class="modal-header-row d-flex align-items-center gap-3 mb-4">
              <div class="modal-icon-wrap"><i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i></div>
              <div>
                <h5 class="form-modal-title mb-1">{{ isEditing ? 'Edit Score' : 'Add Score' }}</h5>
                <p class="form-modal-subtitle mb-0">{{ isEditing ? 'Update the score details below' : 'Fill in the details for the new score' }}</p>
              </div>
            </div>

            <div v-if="formError" class="form-alert form-alert--error">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ formError }}
            </div>

            <form @submit.prevent="submitForm" class="score-form">
              <div class="form-section">
                <div class="form-section-head">
                  <i class="bi bi-info-circle"></i>
                  <span>Basic Information</span>
                </div>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-music-note-beamed input-icon"></i>
                      <input v-model="form.title" type="text" class="form-input" placeholder="e.g. Ave Maria" required />
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Composer</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-person-fill input-icon"></i>
                      <input v-model="form.composer" type="text" class="form-input" placeholder="Composer name" />
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Arranger</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-person-fill input-icon"></i>
                      <input v-model="form.arranger" type="text" class="form-input" placeholder="Arranger name (optional)" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <div class="form-section-head">
                  <i class="bi bi-tags"></i>
                  <span>Classification</span>
                </div>
                <div class="row g-3">
                  <div class="col-12 col-sm-4">
                    <label class="form-label">Genre</label>
                    <div class="select-wrap">
                      <i class="bi bi-bookmark-fill select-icon"></i>
                      <select v-model="form.genre" class="form-select-custom">
                        <option value="">Select genre</option>
                        <option v-for="g in allGenres" :key="g" :value="g">{{ g }}</option>
                      </select>
                      <i class="bi bi-chevron-down select-chevron"></i>
                    </div>
                  </div>
                  <div class="col-12 col-sm-4">
                    <label class="form-label">Difficulty</label>
                    <div class="select-wrap">
                      <i class="bi bi-bar-chart-fill select-icon"></i>
                      <select v-model="form.difficulty" class="form-select-custom">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                      </select>
                      <i class="bi bi-chevron-down select-chevron"></i>
                    </div>
                  </div>
                  <div class="col-12 col-sm-4">
                    <label class="form-label">Pages</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-file-earmark-text input-icon"></i>
                      <input v-model.number="form.pages" type="number" min="0" class="form-input" placeholder="0" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <div class="form-section-head">
                  <i class="bi bi-currency-dollar"></i>
                  <span>Pricing & Media</span>
                </div>
                <div class="row g-3">
                  <div class="col-12 col-sm-6">
                    <label class="form-label">Price (Rp)</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-cash-stack input-icon"></i>
                      <input v-model.number="form.price" type="number" min="0" step="500" class="form-input" placeholder="0 = Free" />
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <label class="form-label">Thumbnail URL</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-image input-icon"></i>
                      <input v-model="form.thumbnail" type="url" class="form-input" placeholder="https://&hellip;" />
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">PDF File</label>
                    <div class="pdf-dropzone" :class="{ 'has-pdf': form.file_url || pendingPdfFile }" @click="triggerPdfInput">
                      <input ref="pdfInput" type="file" accept=".pdf,application/pdf" class="pdf-input-hidden" @change="onPdfSelect" :disabled="uploadingPdf" />
                      <template v-if="uploadingPdf">
                        <div class="pdf-dropzone-status">
                          <span class="spinner-border spinner-border-sm me-2"></span>
                          <span>Uploading PDF&hellip;</span>
                        </div>
                      </template>
                      <template v-else-if="form.file_url">
                        <div class="pdf-dropzone-content">
                          <i class="bi bi-filetype-pdf pdf-dropzone-icon"></i>
                          <div class="pdf-dropzone-info">
                            <span class="pdf-dropzone-label">PDF uploaded</span>
                            <a :href="form.file_url" target="_blank" class="pdf-dropzone-link" @click.stop>View file</a>
                          </div>
                          <button type="button" class="pdf-dropzone-replace" @click.stop="removePdf">Replace</button>
                        </div>
                      </template>
                      <template v-else-if="pendingPdfFile">
                        <div class="pdf-dropzone-content">
                          <i class="bi bi-file-earmark-pdf pdf-dropzone-icon"></i>
                          <div class="pdf-dropzone-info">
                            <span class="pdf-dropzone-label">{{ pendingPdfFile.name }}</span>
                            <span class="pdf-dropzone-hint">Will upload on save</span>
                          </div>
                          <button type="button" class="pdf-dropzone-replace" @click.stop="clearPendingPdf">Remove</button>
                        </div>
                      </template>
                      <template v-else>
                        <div class="pdf-dropzone-placeholder">
                          <i class="bi bi-cloud-arrow-up pdf-dropzone-icon"></i>
                          <span class="pdf-dropzone-label">Click to upload PDF</span>
                          <span class="pdf-dropzone-hint">PDF files only, max 20 MB</span>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-footer">
                <button type="button" class="btn btn-outline-gold" @click="closeForm">Cancel</button>
                <button type="submit" class="btn btn-gold" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi" :class="isEditing ? 'bi-check2' : 'bi-plus-lg'"></i>
                  {{ isEditing ? 'Save Changes' : 'Add Score' }}
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
            <button class="modal-close-btn modal-close-btn-dark" @click="cancelDelete" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="delete-icon-wrap"><i class="bi bi-exclamation-triangle"></i></div>
            <h5 class="mt-3 mb-1 text-champagne">Delete Score?</h5>
            <p class="text-champagne-muted small mb-3">This action cannot be undone.</p>
            <div class="d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-outline-gold" @click="cancelDelete" :disabled="deleting">Cancel</button>
              <button type="button" class="btn btn-danger" @click="doDelete" :disabled="deleting">
                <span v-if="deleting" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-trash3 me-1"></i>Delete
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
  title: '', composer: '', arranger: '', genre: '', difficulty: 'Intermediate', pages: 0, price: 0, thumbnail: '', file_url: '',
})

export default {
  name: 'ComposerDashboard',
  setup() {
    const store = useLibraryStore()
    const authStore = useAuthStore()

    const userId = computed(() => authStore.user?.id || 0)
    const userName = computed(() => authStore.user?.name || '')
    const userNameLower = computed(() => userName.value.toLowerCase())

    const myScores = computed(() =>
      store.scores.filter((s) => {
        if (s.created_by) return s.created_by === userId.value
        const composer = (s.composer || '').toLowerCase()
        const arranger = (s.arranger || '').toLowerCase()
        return composer === userNameLower.value || arranger === userNameLower.value
      })
    )

    const paidCount = computed(() => myScores.value.filter((s) => s.price > 0).length)
    const freeCount = computed(() => myScores.value.filter((s) => !s.price || s.price === 0).length)
    const pdfCount = computed(() => myScores.value.filter((s) => s.file_url).length)

    const search = ref('')
    const filterGenre = ref('')
    const filterDifficulty = ref('')

    const allGenres = ['Classical', 'Contemporary', 'Jazz', 'Pop', 'Sacred', 'Traditional']
    const difficulties = ['Beginner', 'Intermediate', 'Advanced']
    const loading = computed(() => store.loading)
    const genres = computed(() => store.genres)

    const filteredScores = computed(() => {
      const q = search.value.trim().toLowerCase()
      return myScores.value.filter((s) => {
        const matchSearch = !q || s.title.toLowerCase().includes(q) || (s.composer || '').toLowerCase().includes(q) || (s.arranger || '').toLowerCase().includes(q)
        const matchGenre = !filterGenre.value || s.genre === filterGenre.value
        const matchDiff = !filterDifficulty.value || s.difficulty === filterDifficulty.value
        return matchSearch && matchGenre && matchDiff
      })
    })

    const formatPrice = (val) => Number(val || 0).toLocaleString('id-ID')

    const diffClass = (d) => ({ 'dash-diff--beginner': d === 'Beginner', 'dash-diff--intermediate': d === 'Intermediate', 'dash-diff--advanced': d === 'Advanced' })

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
      isEditing.value = false; editingId.value = null
      form.value = { ...emptyForm(), composer: userName.value, arranger: userName.value }
      formError.value = ''; pendingPdfFile.value = null
      if (pdfInput.value) pdfInput.value.value = ''
      showForm.value = true
    }

    const openEdit = (score) => {
      isEditing.value = true; editingId.value = score.id
      form.value = {
        title: score.title, composer: score.composer, arranger: score.arranger || '',
        genre: score.genre, difficulty: score.difficulty, pages: score.pages,
        price: score.price || 0, thumbnail: score.thumbnail || '', file_url: score.file_url || '',
      }
      formError.value = ''; pendingPdfFile.value = null
      if (pdfInput.value) pdfInput.value.value = ''
      showForm.value = true
    }

    const closeForm = () => { showForm.value = false; formError.value = ''; uploadingPdf.value = false; pendingPdfFile.value = null }

    const triggerPdfInput = () => pdfInput.value?.click()

    const removePdf = () => {
      form.value.file_url = ''
      if (pdfInput.value) pdfInput.value.value = ''
    }

    const clearPendingPdf = () => {
      pendingPdfFile.value = null
      if (pdfInput.value) pdfInput.value.value = ''
    }

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

    const confirmDelete = (score) => { deleteTarget.value = score }
    const cancelDelete = () => { deleteTarget.value = null }
    const doDelete = async () => {
      deleting.value = true
      try { await store.deleteScore(deleteTarget.value.id); cancelDelete() }
      catch (err) { alert('Delete failed: ' + (err.message || 'Unknown error')); deleting.value = false }
    }

    return {
      defaultThumb, store, authStore, userName,
      search, filterGenre, filterDifficulty, loading, myScores, genres, allGenres, difficulties,
      filteredScores, formatPrice, diffClass,
      paidCount, freeCount, pdfCount,
      showForm, isEditing, form, formError, submitting, deleteTarget, deleting,
      openCreate, openEdit, closeForm, submitForm, confirmDelete, cancelDelete, doDelete,
      pdfInput, uploadingPdf, pendingPdfFile, onPdfSelect, triggerPdfInput, removePdf, clearPendingPdf,
    }
  },
}
</script>

<style scoped>
/* ── Header ── */
.dash-header { position:relative;overflow:hidden }
.dash-header::before { content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 0% 100%,rgba(200,164,93,.06) 0%,transparent 70%);pointer-events:none }
.dash-header-inner { position:relative;display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap }
.dash-badge { display:inline-flex;align-items:center;gap:.4rem;padding:.25rem .7rem .25rem .55rem;border-radius:999px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--gold-color);background:rgba(200,164,93,.1);border:1px solid rgba(200,164,93,.15);margin-bottom:.5rem }
.dash-badge i { font-size:.75rem }
.dash-title { font-size:clamp(1.35rem,4vw,1.75rem);font-weight:800;color:rgba(234,220,194,0.92);letter-spacing:-.01em;line-height:1.2;margin:0 }
.dash-subtitle { font-size:.85rem;color:rgba(234,220,194,0.5);margin:.3rem 0 0 }
.dash-subtitle strong { color:var(--gold-color);font-weight:700 }
.dash-add-btn { flex-shrink:0 }

/* ── Stats ── */
.dash-stats { margin-bottom:1.25rem }
.stat-card { display:flex;align-items:center;gap:.85rem;padding:.85rem 1rem;border-radius:10px;background:rgba(26,31,48,0.45);border:1px solid rgba(234,220,194,0.06);transition:border-color .2s,transform .2s,box-shadow .2s }
@media (hover:hover) { .stat-card:hover { border-color:rgba(200,164,93,.2);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.15) } }
.stat-icon { width:40px;height:40px;border-radius:10px;display:grid;place-items:center;font-size:1.15rem;flex-shrink:0 }
.stat-icon--total { background:rgba(200,164,93,.1);color:var(--gold-color) }
.stat-icon--paid { background:rgba(74,124,89,.12);color:#4a7c59 }
.stat-icon--free { background:rgba(52,152,219,.1);color:#3498db }
.stat-icon--pdf { background:rgba(231,76,60,.08);color:#e74c3c }
.stat-body { display:flex;flex-direction:column;line-height:1.2 }
.stat-value { font-size:1.3rem;font-weight:800;color:rgba(234,220,194,0.92);letter-spacing:-.02em }
.stat-label { font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:rgba(234,220,194,0.4) }

/* ── Toolbar ── */
.dash-toolbar { margin-bottom:1rem }
.dash-toolbar-row { display:flex;gap:.75rem;align-items:center;flex-wrap:wrap }
.dash-search-wrap { position:relative;flex:1;min-width:200px }
.dash-search-icon { position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:rgba(234,220,194,0.3);font-size:.85rem;pointer-events:none;z-index:1 }
.dash-search-input { width:100%;padding:.58rem .85rem .58rem 2.4rem;border:1px solid rgba(234,220,194,0.08);border-radius:8px;background:rgba(26,31,48,0.4);color:rgba(234,220,194,0.78);font-size:.88rem;outline:none;transition:border-color .2s,box-shadow .2s,background .2s }
.dash-search-input:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.1);background:rgba(26,31,48,0.6) }
.dash-search-input::placeholder { color:rgba(234,220,194,0.25) }
.dash-search-clear { position:absolute;right:.7rem;top:50%;transform:translateY(-50%);border:0;background:transparent;color:rgba(234,220,194,0.3);font-size:.6rem;padding:.3rem;cursor:pointer;transition:color .15s;z-index:1 }
.dash-search-clear:hover { color:rgba(234,220,194,0.78) }
.dash-toolbar-filters { display:flex;gap:.5rem;flex-wrap:wrap }
.dash-filter-group { min-width:130px }
.dash-select { width:100%;padding:.58rem .75rem;border:1px solid rgba(234,220,194,0.08);border-radius:8px;background:rgba(26,31,48,0.4);color:rgba(234,220,194,0.78);font-size:.85rem;outline:none;cursor:pointer;transition:border-color .2s,background .2s }
.dash-select:focus { border-color:var(--gold-color);background:rgba(26,31,48,0.6) }

/* ── Loading ── */
.loading-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem 1rem;color:rgba(234,220,194,0.5) }
.loading-ring { width:40px;height:40px;border:3px solid rgba(200,164,93,.15);border-top-color:var(--gold-color);border-radius:50%;animation:spin .8s linear infinite;margin-bottom:1rem }
@keyframes spin { to { transform:rotate(360deg) } }

/* ── Empty ── */
.empty-state { text-align:center;padding:3rem 1rem }
.empty-icon { display:grid;place-items:center;width:60px;height:60px;border-radius:50%;background:rgba(200,164,93,.08);margin:0 auto }
.empty-icon i { font-size:1.75rem;color:var(--gold-color) }
.dash-empty-desc { font-size:.85rem;color:rgba(234,220,194,0.5);max-width:320px;margin:0 auto }

/* ── Table ── */
.dash-table-wrap.content-card.bg-dark { overflow:hidden;color:rgba(234,220,194,0.78);border-radius:10px }
.dt-header { display:grid;grid-template-columns:44px 2.2fr 1.4fr 1.4fr 1fr .85fr .55fr .75fr .85fr 50px;gap:.4rem;padding:.65rem 1rem;border-bottom:1px solid rgba(234,220,194,0.08);font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(234,220,194,0.45);background:rgba(16,19,31,0.3);align-items:center }
.dt-th { white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.dt-row { display:grid;grid-template-columns:44px 2.2fr 1.4fr 1.4fr 1fr .85fr .55fr .75fr .85fr 50px;gap:.4rem;padding:.55rem 1rem;align-items:center;animation:fadeInUp .35s ease-out both;transition:background .15s;border-bottom:1px solid rgba(234,220,194,0.04) }
.dt-row:last-child { border-bottom:none }
@media (hover:hover) { .dt-row:hover { background:rgba(200,164,93,.04) } }
.dt-td { min-width:0 }
.dt-thumb-img { width:32px;height:42px;object-fit:cover;border-radius:4px;display:block }
.dt-score-title { font-weight:600;font-size:.88rem;color:rgba(234,220,194,0.92);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block }
.dt-score-composer-mobile { display:none;font-size:.72rem;color:rgba(234,220,194,0.4);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.dt-text-muted { font-size:.8rem;color:rgba(234,220,194,0.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.dash-pill { display:inline-block;padding:.1rem .5rem;border-radius:999px;font-size:.68rem;font-weight:600;background:rgba(200,164,93,.1);color:var(--gold-color);white-space:nowrap }

/* ── Difficulty badges ── */
.dash-diff { display:inline-block;padding:.08rem .42rem;border-radius:999px;font-size:.65rem;font-weight:700;text-transform:uppercase;white-space:nowrap }
.dash-diff--beginner { background:rgba(74,124,89,.13);color:#4a7c59 }
.dash-diff--intermediate { background:rgba(200,164,93,.15);color:#9d7d3b }
.dash-diff--advanced { background:rgba(192,57,43,.1);color:#c0392b }

/* ── Price ── */
.dash-price { font-size:.78rem;font-weight:600;color:var(--gold-color);white-space:nowrap }
.dash-orders { font-size:.78rem;font-weight:700;color:rgba(234,220,194,0.78);text-align:center;display:block }

/* ── Status ── */
.dash-status { display:inline-flex;align-items:center;gap:.3rem;padding:.1rem .5rem;border-radius:999px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.02em;white-space:nowrap }
.dash-status--published { background:rgba(74,124,89,.12);color:#4a7c59 }
.dash-status--published i { font-size:.6rem }
.dash-status--draft { background:rgba(200,164,93,.1);color:var(--gold-color) }
.dash-status--draft i { font-size:.6rem }

/* ── Actions ── */
.dt-actions-cell { display:flex;gap:.2rem;justify-content:flex-end }
.dash-action { border:1px solid rgba(234,220,194,0.06);border-radius:6px;background:transparent;color:rgba(234,220,194,0.35);font-size:.8rem;padding:.28rem .45rem;cursor:pointer;transition:all .18s ease;line-height:1;display:inline-flex;align-items:center;justify-content:center }
.dash-action:hover { border-color:rgba(234,220,194,0.25);color:rgba(234,220,194,0.7);background:rgba(234,220,194,0.04) }
.dash-action--edit:hover { border-color:var(--gold-color);color:var(--gold-color);background:rgba(200,164,93,.06) }
.dash-action--delete:hover { border-color:#c0392b;color:#c0392b;background:rgba(192,57,43,.05) }

/* ── Total row ── */
.dash-total-row { text-align:center;margin-top:1rem;font-size:.78rem;color:rgba(234,220,194,0.4);letter-spacing:.02em }

/* ── Responsive: tablet ── */
@media (max-width:991.98px) {
  .dt-header { grid-template-columns:38px 2fr 1.2fr .55fr .8fr .8fr 42px;font-size:.6rem;padding:.55rem .75rem }
  .dt-row { grid-template-columns:38px 2fr 1.2fr .55fr .8fr .8fr 42px;padding:.5rem .75rem }
  .dt-arranger,.dt-genre { display:none }
  .dt-thumb-img { width:28px;height:38px }
}

/* ── Responsive: mobile ── */
@media (max-width:767.98px) {
  .dt-header { display:none }
  .dt-row {
    display:grid;
    grid-template-columns:40px 1fr auto;
    gap:.6rem .75rem;
    padding:.6rem .75rem;
    align-items:center;
    border-bottom:1px solid rgba(234,220,194,0.05);
  }
  .dt-thumb { grid-row:1/3;align-self:center }
  .dt-thumb-img { width:36px;height:48px;border-radius:4px }
  .dt-title-cell { display:flex;flex-direction:column;min-width:0;justify-content:center;gap:1px }
  .dt-score-composer-mobile { display:block }
  .dt-composer,.dt-arranger,.dt-genre { display:none }
  .dt-difficulty,.dt-price,.dt-status { display:none }
  .dt-actions-cell { grid-row:1/3;display:flex;flex-direction:column;gap:.25rem;align-self:center }
  .dash-action { padding:.25rem .35rem;font-size:.75rem }
}

@media (max-width:575.98px) {
  .dash-toolbar-filters { width:100% }
  .dash-filter-group { flex:1;min-width:0 }
  .stat-card { padding:.65rem .75rem;gap:.65rem }
  .stat-icon { width:34px;height:34px;font-size:1rem }
  .stat-value { font-size:1.1rem }
  .stat-label { font-size:.62rem }
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

/* ── Transitions ── */
.modal-enter-active,.modal-leave-active { transition:opacity .25s ease }
.modal-enter-active .modal-sheet,.modal-leave-active .modal-sheet { transition:transform .25s ease,opacity .25s ease }
.modal-enter-from,.modal-leave-to { opacity:0 }
.modal-enter-from .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }
.modal-leave-to .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }

@keyframes fadeInUp { from { opacity:0;transform:translateY(16px) } to { opacity:1;transform:translateY(0) } }

/* ── Form modal ── */
.modal-sheet--form { max-width:680px }
.form-modal-title { font-size:1.1rem;font-weight:800;color:rgba(234,220,194,0.92);letter-spacing:-.01em;line-height:1.2 }
.form-modal-subtitle { font-size:.82rem;color:rgba(234,220,194,0.5) }
.form-alert { display:flex;align-items:center;padding:.65rem .85rem;border-radius:8px;font-size:.82rem;font-weight:600;margin-bottom:1.25rem }
.form-alert--error { background:rgba(192,57,43,.12);border:1px solid rgba(192,57,43,.25);color:#e8a0a8 }

.score-form { display:flex;flex-direction:column;gap:1.25rem }
.form-section { background:rgba(26,31,48,0.4);border:1px solid rgba(234,220,194,0.06);border-radius:10px;padding:1rem 1.15rem 1.15rem }
.form-section-head { display:flex;align-items:center;gap:.55rem;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--gold-color);margin-bottom:.85rem;padding-bottom:.55rem;border-bottom:1px solid rgba(234,220,194,0.06) }
.form-section-head i { font-size:.85rem }

.form-label { display:block;font-size:.78rem;font-weight:700;color:rgba(234,220,194,0.78);margin-bottom:.35rem;letter-spacing:.01em }

.input-icon-wrap { position:relative;display:flex;align-items:center }
.input-icon { position:absolute;left:.75rem;color:rgba(234,220,194,0.3);font-size:.85rem;pointer-events:none;z-index:1 }
.form-input { width:100%;padding:.55rem .75rem .55rem 2.3rem;border:1px solid rgba(234,220,194,0.1);border-radius:8px;background:rgba(16,19,31,0.5);color:rgba(234,220,194,0.85);font-size:.85rem;transition:border-color .2s,box-shadow .2s,background .2s;outline:none }
.form-input:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.12);background:rgba(16,19,31,0.7) }
.form-input::placeholder { color:rgba(234,220,194,0.25) }
.form-input[type="number"] { -moz-appearance:textfield }
.form-input[type="number"]::-webkit-inner-spin-button,
.form-input[type="number"]::-webkit-outer-spin-button { -webkit-appearance:none;margin:0 }

.select-wrap { position:relative;display:flex;align-items:center }
.select-icon { position:absolute;left:.75rem;color:rgba(234,220,194,0.3);font-size:.8rem;pointer-events:none;z-index:1 }
.select-chevron { position:absolute;right:.75rem;color:rgba(234,220,194,0.3);font-size:.65rem;pointer-events:none;z-index:1 }
.form-select-custom { width:100%;padding:.55rem 2rem .55rem 2.3rem;border:1px solid rgba(234,220,194,0.1);border-radius:8px;background:rgba(16,19,31,0.5);color:rgba(234,220,194,0.85);font-size:.85rem;outline:none;cursor:pointer;transition:border-color .2s,box-shadow .2s,background .2s;-webkit-appearance:none;-moz-appearance:none;appearance:none }
.form-select-custom:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.12);background:rgba(16,19,31,0.7) }
.form-select-custom option { background:#1a1f30;color:rgba(234,220,194,0.85) }

.pdf-dropzone { position:relative;border:2px dashed rgba(234,220,194,0.12);border-radius:10px;padding:1rem;cursor:pointer;transition:border-color .2s,background .2s }
.pdf-dropzone:hover { border-color:rgba(200,164,93,.35);background:rgba(200,164,93,.04) }
.pdf-dropzone.has-pdf { border-style:solid;border-color:rgba(74,124,89,.25);background:rgba(74,124,89,.05) }
.pdf-input-hidden { display:none }
.pdf-dropzone-content { display:flex;align-items:center;gap:.85rem }
.pdf-dropzone-placeholder { display:flex;flex-direction:column;align-items:center;gap:.25rem;padding:.5rem 0 }
.pdf-dropzone-status { display:flex;align-items:center;gap:.5rem;justify-content:center;padding:.5rem 0;color:rgba(234,220,194,0.6);font-size:.82rem }
.pdf-dropzone-icon { font-size:1.5rem;color:var(--gold-color);flex-shrink:0 }
.pdf-dropzone-info { flex:1;min-width:0 }
.pdf-dropzone-label { display:block;font-size:.82rem;font-weight:600;color:rgba(234,220,194,0.78) }
.pdf-dropzone-hint { display:block;font-size:.72rem;color:rgba(234,220,194,0.4);margin-top:1px }
.pdf-dropzone-link { font-size:.78rem;color:var(--gold-color);text-decoration:none;font-weight:600 }
.pdf-dropzone-link:hover { text-decoration:underline }
.pdf-dropzone-replace { padding:.3rem .65rem;border:1px solid rgba(234,220,194,0.12);border-radius:6px;background:transparent;color:rgba(234,220,194,0.5);font-size:.75rem;font-weight:600;cursor:pointer;transition:all .18s;flex-shrink:0 }
.pdf-dropzone-replace:hover { border-color:var(--gold-color);color:var(--gold-color);background:rgba(200,164,93,.08) }

.form-footer { display:flex;gap:.75rem;justify-content:flex-end;padding-top:.25rem }
.form-footer .btn { padding:.5rem 1.25rem;font-size:.85rem;font-weight:700;border-radius:8px }

@media (max-width:575.98px) {
  .form-section { padding:.85rem .85rem 1rem }
  .form-footer { flex-direction:column-reverse }
  .form-footer .btn { width:100% }
}

/* ── Delete modal ── */
.delete-icon-wrap { display:grid;place-items:center;width:48px;height:48px;border-radius:50%;background:rgba(192,57,43,.1);margin:0 auto }
.delete-icon-wrap i { font-size:1.4rem;color:#c0392b }
</style>
