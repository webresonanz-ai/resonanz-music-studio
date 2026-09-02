<script setup>
import { computed, onMounted, ref } from 'vue'
import { useLibraryStore } from '../../stores/api'

const libraryStore = useLibraryStore()

const loading = ref(true)
const error = ref('')

const search = ref('')
const genreFilter = ref('')
const composerFilter = ref('')
const arrangerFilter = ref('')

const showModal = ref(false)
const editingId = ref(null)
const form = ref({ title: '', composer: '', arranger: '', genre: '', year: '', notes: '' })
const pdfFiles = ref(null)
const saving = ref(false)

const filteredScores = computed(() => {
  const q = search.value.trim().toLowerCase()
  return libraryStore.scores.filter((s) => {
    if (q) {
      const haystack = [s.title, s.composer, s.arranger, s.genre].join(' ').toLowerCase()
      if (!haystack.includes(q)) return false
    }
    if (genreFilter.value && s.genre !== genreFilter.value) return false
    if (composerFilter.value && s.composer !== composerFilter.value) return false
    if (arrangerFilter.value && s.arranger !== arrangerFilter.value) return false
    return true
  })
})

function openCreate() {
  editingId.value = null
  form.value = { title: '', composer: '', arranger: '', genre: '', year: '', notes: '' }
  pdfFiles.value = null
  showModal.value = true
}

function openEdit(score) {
  editingId.value = score.id
  form.value = {
    title: score.title || '',
    composer: score.composer || '',
    arranger: score.arranger || '',
    genre: score.genre || '',
    year: score.year || '',
    notes: score.notes || '',
  }
  pdfFiles.value = null
  showModal.value = true
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) {
      await libraryStore.updateScore(editingId.value, form.value)
    } else {
      await libraryStore.createScore(form.value)
    }
    if (pdfFiles.value?.length) {
      const targetId = editingId.value || libraryStore.scores[libraryStore.scores.length - 1].id
      await libraryStore.uploadScorePdf(targetId, pdfFiles.value[0])
    }
    showModal.value = false
  } catch (err) {
    error.value = err.message || 'Save failed'
  } finally {
    saving.value = false
  }
}

async function removeScore(score) {
  if (!window.confirm(`Delete score "${score.title}"?`)) return
  error.value = ''
  try {
    await libraryStore.deleteScore(score.id)
  } catch (err) {
    error.value = err.message || 'Delete failed'
  }
}

onMounted(async () => {
  try {
    await libraryStore.fetchScores()
  } catch (err) {
    error.value = err.message || 'Failed to load scores'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div>
        <h1 class="h3 mb-1">Composer Dashboard</h1>
        <p class="text-white-50 mb-0 small">Score library management</p>
      </div>
      <button class="btn btn-gold" type="button" @click="openCreate">
        <i class="bi bi-plus-lg me-2"></i>Add Score
      </button>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>

    <div v-if="loading" class="d-flex justify-content-center py-5">
      <div class="spinner-border text-warning" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <template v-else>
      <div class="content-card mb-3">
        <div class="row g-2">
          <div class="col-12 col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary text-white-50">
                <i class="bi bi-search"></i>
              </span>
              <input v-model="search" type="search" class="form-control bg-dark text-white border-secondary" placeholder="Search scores..." />
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select v-model="genreFilter" class="form-select bg-dark text-white border-secondary">
              <option value="">All genres</option>
              <option v-for="g in libraryStore.genres" :key="g" :value="g">{{ g }}</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <select v-model="composerFilter" class="form-select bg-dark text-white border-secondary">
              <option value="">All composers</option>
              <option v-for="c in libraryStore.composers" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <select v-model="arrangerFilter" class="form-select bg-dark text-white border-secondary">
              <option value="">All arrangers</option>
              <option v-for="a in libraryStore.arrangers" :key="a" :value="a">{{ a }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="content-card">
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Composer</th>
                <th>Arranger</th>
                <th>Genre</th>
                <th>Year</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredScores.length === 0">
                <td colspan="6" class="text-center text-white-50 py-4">No scores found.</td>
              </tr>
              <tr v-for="score in filteredScores" :key="score.id">
                <td class="fw-semibold">{{ score.title }}</td>
                <td>{{ score.composer || '—' }}</td>
                <td>{{ score.arranger || '—' }}</td>
                <td><span class="badge text-bg-secondary">{{ score.genre || '—' }}</span></td>
                <td>{{ score.year || '—' }}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-gold me-1" type="button" @click="openEdit(score)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" type="button" @click="removeScore(score)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" @click.self="showModal = false">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingId ? 'Edit Score' : 'Add Score' }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="showModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small text-uppercase text-white-50">Title</label>
                <input v-model.trim="form.title" type="text" class="form-control bg-dark text-white border-secondary" required />
              </div>
              <div class="col-md-6">
                <label class="form-label small text-uppercase text-white-50">Composer</label>
                <input v-model.trim="form.composer" type="text" class="form-control bg-dark text-white border-secondary" />
              </div>
              <div class="col-md-6">
                <label class="form-label small text-uppercase text-white-50">Arranger</label>
                <input v-model.trim="form.arranger" type="text" class="form-control bg-dark text-white border-secondary" />
              </div>
              <div class="col-md-6">
                <label class="form-label small text-uppercase text-white-50">Genre</label>
                <input v-model.trim="form.genre" type="text" class="form-control bg-dark text-white border-secondary" />
              </div>
              <div class="col-md-6">
                <label class="form-label small text-uppercase text-white-50">Year</label>
                <input v-model.trim="form.year" type="text" class="form-control bg-dark text-white border-secondary" />
              </div>
              <div class="col-12">
                <label class="form-label small text-uppercase text-white-50">Notes</label>
                <textarea v-model.trim="form.notes" rows="2" class="form-control bg-dark text-white border-secondary"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label small text-uppercase text-white-50">PDF (optional)</label>
                <input ref="pdfInput" type="file" accept=".pdf" class="form-control bg-dark text-white border-secondary" @change="pdfFiles = $event.target.files" />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showModal = false">Cancel</button>
            <button type="button" class="btn btn-gold" :disabled="saving || !form.title" @click="save">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showModal" class="modal-backdrop fade show"></div>
  </div>
</template>