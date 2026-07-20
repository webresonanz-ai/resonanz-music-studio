<template>
  <div class="fade-in-up">
    <div class="ch-header">
      <div class="ch-header-top">
        <div class="ch-header-text">
          <p class="ch-breadcrumb">Library / Archive</p>
          <h1 class="ch-title">Concert History</h1>
          <p class="ch-desc">
            A journey through every performance — relive the music, the moments, and the memories.
          </p>
        </div>
        <div class="ch-header-actions" v-if="canManage">
          <button class="ch-add-btn" @click="openAddModal">
            <i class="bi bi-plus-lg"></i>
            <span class="ch-add-label">Add Concert</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-warning" role="status"></div>
    </div>

    <div v-else-if="error" class="content-card bg-dark text-center py-5">
      <i class="bi bi-exclamation-triangle display-4 text-champagne-muted mb-3 d-block"></i>
      <p class="text-champagne-muted">{{ error }}</p>
    </div>

    <div v-else-if="concerts.length === 0" class="content-card bg-dark text-center py-5">
      <i class="bi bi-music-note-beamed display-4 text-champagne-muted mb-3 d-block"></i>
      <p class="text-champagne-muted mb-0">No concert history yet.</p>
    </div>

    <div v-else class="row g-4">
      <div
        v-for="concert in concerts"
        :key="concert.id"
        class="col-12 col-sm-6 col-lg-4 col-xl-3"
      >
        <div class="ch-card" @click="goToDetail(concert.id)">
          <div
            class="ch-card-banner"
            :style="bannerStyle(concert.banner)"
          >
            <div class="ch-card-overlay"></div>
            <div class="ch-card-content">
              <h3 class="ch-card-title">{{ concert.title }}</h3>
              <p class="ch-card-date">{{ formatDate(concert.concert_date) }}</p>
            </div>
          </div>
          <div class="ch-card-actions" v-if="canManage" @click.stop>
            <button class="ch-action-btn" @click="openEditModal(concert)" title="Edit">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="ch-action-btn ch-action-btn-danger" @click="confirmDelete(concert)" title="Delete">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div class="modal fade" id="chFormModal" tabindex="-1" ref="formModalEl">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content ch-modal">
            <div class="modal-header">
              <h5 class="modal-title">{{ editingConcert ? 'Edit Concert' : 'Add Concert' }}</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div v-if="successMessage" class="alert alert-success d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ successMessage }}</span>
              </div>
              <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ errorMessage }}</span>
              </div>
              <form @submit.prevent="submitForm">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Title</label>
                    <input v-model.trim="form.title" class="form-control" type="text" required maxlength="200" placeholder="Concert title">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea v-model.trim="form.description" class="form-control" rows="4" placeholder="Concert description"></textarea>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Concert Date</label>
                    <input v-model="form.concert_date" class="form-control" type="date" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Banner Image</label>
                    <div v-if="form.banner" class="mb-2">
                      <img :src="form.banner" class="ch-preview-img" alt="Banner preview">
                    </div>
                    <input class="form-control" type="file" accept="image/jpeg,image/png,image/webp" @change="handleBannerUpload">
                    <small class="text-champagne-muted">JPEG, PNG, or WebP. Max 3 MB.</small>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">YouTube Link</label>
                    <input v-model.trim="form.youtube_link" class="form-control" type="url" placeholder="https://youtube.com/...">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Spotify Link</label>
                    <input v-model.trim="form.spotify_link" class="form-control" type="url" placeholder="https://open.spotify.com/...">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Apple Music Link</label>
                    <input v-model.trim="form.apple_music_link" class="form-control" type="url" placeholder="https://music.apple.com/...">
                  </div>
                </div>
                <div class="d-flex gap-3 mt-4">
                  <button class="btn btn-gold" type="submit" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check-circle me-2"></i>
                    {{ saving ? 'Saving...' : (editingConcert ? 'Update' : 'Create') }}
                  </button>
                  <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
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
import { useConcertHistoryStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'

const emptyForm = () => ({
  title: '',
  description: '',
  concert_date: '',
  banner: '',
  youtube_link: '',
  spotify_link: '',
  apple_music_link: '',
})

export default {
  name: 'ConcertHistory',
  computed: {
    ...mapState(useConcertHistoryStore, ['concerts', 'loading', 'error']),
    canManage() {
      const authStore = useAuthStore()
      const role = authStore.user?.role?.toLowerCase()
      return role === 'admin' || role === 'manager'
    },
  },
  data() {
    return {
      saving: false,
      successMessage: '',
      errorMessage: '',
      form: emptyForm(),
      editingConcert: null,
      formModalInstance: null,
    }
  },
  async mounted() {
    await this.fetchConcerts()
  },
  methods: {
    ...mapActions(useConcertHistoryStore, ['fetchConcerts', 'createConcert', 'updateConcert', 'deleteConcert', 'uploadBanner']),

    formatDate(dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr + 'T00:00:00')
      return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
    },

    bannerStyle(banner) {
      if (banner) {
        return { backgroundImage: `url(${banner})` }
      }
      return {}
    },

    goToDetail(id) {
      this.$router.push(`/library/concert-history/${id}`)
    },

    openAddModal() {
      this.editingConcert = null
      this.form = emptyForm()
      this.successMessage = ''
      this.errorMessage = ''
      this.showFormModal()
    },

    openEditModal(concert) {
      this.editingConcert = concert
      this.form = {
        title: concert.title || '',
        description: concert.description || '',
        concert_date: concert.concert_date || '',
        banner: concert.banner || '',
        youtube_link: concert.youtube_link || '',
        spotify_link: concert.spotify_link || '',
        apple_music_link: concert.apple_music_link || '',
      }
      this.successMessage = ''
      this.errorMessage = ''
      this.showFormModal()
    },

    async handleBannerUpload(e) {
      const file = e.target.files[0]
      if (!file) return
      try {
        const result = await this.uploadBanner(file)
        if (result?.url) {
          this.form.banner = result.url
        }
      } catch (err) {
        this.errorMessage = err.message || 'Banner upload failed'
      }
    },

    async submitForm() {
      this.saving = true
      this.successMessage = ''
      this.errorMessage = ''
      try {
        if (this.editingConcert) {
          await this.updateConcert(this.editingConcert.id, this.form)
          this.successMessage = 'Concert updated successfully.'
        } else {
          await this.createConcert(this.form)
          this.successMessage = 'Concert created successfully.'
        }
        setTimeout(() => this.hideFormModal(), 1000)
      } catch (err) {
        this.errorMessage = err.message || 'Unable to save concert.'
      } finally {
        this.saving = false
      }
    },

    confirmDelete(concert) {
      if (!confirm(`Delete "${concert.title}"? This cannot be undone.`)) return
      this.deleteConcertRecord(concert.id)
    },

    async deleteConcertRecord(id) {
      this.saving = true
      try {
        await this.deleteConcert(id)
      } catch (err) {
        alert(err.message || 'Unable to delete concert.')
      } finally {
        this.saving = false
      }
    },

    showFormModal() {
      const el = this.$refs.formModalEl
      if (!el) return
      this.formModalInstance = Modal.getOrCreateInstance(el)
      this.formModalInstance.show()
    },

    hideFormModal() {
      if (this.formModalInstance) {
        this.formModalInstance.hide()
      }
    },
  },
  beforeUnmount() {
    if (this.formModalInstance) this.formModalInstance.dispose()
  },
}
</script>

<style scoped>
.ch-header {
  position: relative;
  margin-bottom: 1.5rem;
  padding: 1.5rem 1.75rem;
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 14px;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.12), transparent 46%),
    linear-gradient(135deg, #10131f 0%, #202736 58%, #121722 100%);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.03) inset,
    0 20px 44px rgba(10, 10, 18, 0.28);
  overflow: hidden;
}

.ch-header::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 3px;
  background: linear-gradient(90deg, var(--accent-color, #7f2432), var(--gold-color, #c8a45d), rgba(234, 220, 194, 0.6));
  opacity: 0.8;
}

.ch-header-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.25rem;
}

.ch-header-text {
  flex: 1;
  min-width: 0;
}

.ch-breadcrumb {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--gold-color, #c8a45d);
  margin-bottom: 0.25rem;
}

.ch-title {
  font-size: clamp(1.4rem, 3.5vw, 2.2rem);
  font-weight: 800;
  color: #fffdf8;
  margin-bottom: 0.35rem;
  line-height: 1.2;
}

.ch-desc {
  font-size: 0.9rem;
  color: rgba(234, 220, 194, 0.55);
  margin-bottom: 0;
  max-width: 540px;
}

.ch-header-actions {
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.ch-add-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.6rem 1.35rem;
  border: 1px solid #9d7d3b;
  border-radius: 10px;
  color: #17130a;
  background: linear-gradient(180deg, #d6b66c 0%, var(--gold-color, #c8a45d) 100%);
  box-shadow:
    0 8px 24px rgba(122, 94, 39, 0.3),
    0 0 0 1px rgba(200, 164, 93, 0.2) inset;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
  position: relative;
  z-index: 1;
}

.ch-add-btn::after {
  content: "";
  position: absolute;
  inset: -2px;
  border-radius: 12px;
  background: radial-gradient(ellipse at center, rgba(200, 164, 93, 0.35), transparent 70%);
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: -1;
}

.ch-add-btn:hover::after {
  opacity: 1;
}

.ch-add-btn:hover {
  border-color: #8f6e2f;
  color: #111;
  background: linear-gradient(180deg, #e1c47f 0%, #b99245 100%);
  transform: translateY(-2px);
  box-shadow:
    0 14px 32px rgba(122, 94, 39, 0.4),
    0 0 0 1px rgba(200, 164, 93, 0.3) inset;
}

.ch-add-btn:active {
  transform: translateY(0);
  box-shadow:
    0 4px 12px rgba(122, 94, 39, 0.25),
    0 0 0 1px rgba(200, 164, 93, 0.2) inset;
}

.ch-add-btn i {
  font-size: 1.1rem;
}

.ch-card {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  background: #1a1f30;
  border: 1px solid rgba(234, 220, 194, 0.1);
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease,
    border-color 0.3s ease;
}

.ch-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 36px rgba(0, 0, 0, 0.4);
  border-color: rgba(200, 164, 93, 0.3);
}

.ch-card-banner {
  position: relative;
  height: 220px;
  background-size: cover;
  background-position: center;
  background-color: #10131f;
}

.ch-card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(16, 19, 31, 0.15) 0%, rgba(16, 19, 31, 0.85) 100%);
}

.ch-card-content {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1.25rem;
}

.ch-card-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #fffdf8;
  margin-bottom: 0.25rem;
  line-height: 1.3;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.ch-card-date {
  font-size: 0.8rem;
  color: var(--gold-color, #c8a45d);
  margin-bottom: 0;
  font-weight: 600;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
}

.ch-card-actions {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  display: flex;
  gap: 0.35rem;
  z-index: 2;
}

.ch-action-btn {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(234, 220, 194, 0.15);
  border-radius: 6px;
  background: rgba(16, 19, 31, 0.75);
  backdrop-filter: blur(4px);
  color: rgba(234, 220, 194, 0.8);
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.ch-action-btn:hover {
  background: rgba(200, 164, 93, 0.2);
  color: var(--gold-color, #c8a45d);
  border-color: rgba(200, 164, 93, 0.3);
}

.ch-action-btn-danger:hover {
  background: rgba(127, 36, 50, 0.3);
  color: #e05050;
  border-color: rgba(127, 36, 50, 0.3);
}

.ch-preview-img {
  max-height: 120px;
  border-radius: 8px;
  border: 1px solid rgba(234, 220, 194, 0.12);
}

:deep(.ch-modal) {
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  border: 1px solid rgba(234, 220, 194, 0.12);
  color: rgba(234, 220, 194, 0.85);
}

:deep(.ch-modal .modal-header) {
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.2), rgba(200, 164, 93, 0.08));
  border-bottom: 1px solid rgba(234, 220, 194, 0.08);
}

:deep(.ch-modal .modal-title) {
  color: var(--gold-color, #c8a45d);
  font-weight: 700;
}

:deep(.ch-modal .btn-close) {
  filter: brightness(0) invert(0.8);
  opacity: 0.6;
}

:deep(.ch-modal .btn-close:hover) {
  opacity: 1;
}

:deep(.ch-modal .form-control) {
  background: rgba(16, 19, 31, 0.6);
  border: 1px solid rgba(234, 220, 194, 0.12);
  color: #fffdf8;
}

:deep(.ch-modal .form-control:focus) {
  border-color: rgba(200, 164, 93, 0.4);
  box-shadow: 0 0 0 2px rgba(200, 164, 93, 0.1);
}

:deep(.ch-modal .form-label) {
  color: rgba(234, 220, 194, 0.7);
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
}

:deep(.ch-modal .form-control::placeholder) {
  color: rgba(234, 220, 194, 0.25);
}

:deep(.ch-modal .alert-success) {
  background: rgba(109, 129, 117, 0.2);
  border: 1px solid rgba(109, 129, 117, 0.3);
  color: #b5d1bf;
}

:deep(.ch-modal .alert-danger) {
  background: rgba(127, 36, 50, 0.2);
  border: 1px solid rgba(127, 36, 50, 0.3);
  color: #e08080;
}

@media (max-width: 767.98px) {
  .ch-header {
    padding: 1.15rem 1.15rem;
  }

  .ch-header-top {
    flex-direction: column;
    gap: 1rem;
  }

  .ch-header-actions {
    width: 100%;
  }

  .ch-add-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
