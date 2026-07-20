<template>
  <div class="fade-in-up">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-warning" role="status"></div>
    </div>

    <div v-else-if="error" class="content-card bg-dark text-center py-5">
      <i class="bi bi-exclamation-triangle display-4 text-champagne-muted mb-3 d-block"></i>
      <p class="text-champagne-muted mb-3">{{ error }}</p>
      <router-link to="/library/concert-history" class="btn btn-outline-gold">
        <i class="bi bi-arrow-left me-2"></i> Back to Concert History
      </router-link>
    </div>

    <template v-else-if="concert">
      <div class="chd-back">
        <router-link to="/library/concert-history" class="chd-back-link">
          <i class="bi bi-arrow-left"></i> Back to Concert History
        </router-link>
      </div>

      <div class="chd-hero">
        <div
          class="chd-hero-banner"
          :style="bannerStyle"
        >
          <div class="chd-hero-overlay"></div>
          <div class="chd-hero-content">
            <h1 class="chd-title">{{ concert.title }}</h1>
            <p class="chd-date">{{ formatDate(concert.concert_date) }}</p>
          </div>
        </div>
      </div>

      <div class="chd-body">
        <div class="chd-description" v-if="concert.description">
          <h3 class="chd-section-title">About This Concert</h3>
          <p class="chd-desc-text">{{ concert.description }}</p>
        </div>

        <div class="chd-links-wrap" v-if="hasLinks">
          <h3 class="chd-section-title">Listen & Watch</h3>
          <div class="chd-links">
            <a
              v-if="concert.youtube_link"
              :href="concert.youtube_link"
              target="_blank"
              rel="noopener noreferrer"
              class="chd-link chd-link-youtube"
              title="Watch on YouTube"
            >
              <i class="bi bi-youtube"></i>
              <span>YouTube</span>
            </a>
            <a
              v-if="concert.spotify_link"
              :href="concert.spotify_link"
              target="_blank"
              rel="noopener noreferrer"
              class="chd-link chd-link-spotify"
              title="Listen on Spotify"
            >
              <i class="bi bi-spotify"></i>
              <span>Spotify</span>
            </a>
            <a
              v-if="concert.apple_music_link"
              :href="concert.apple_music_link"
              target="_blank"
              rel="noopener noreferrer"
              class="chd-link chd-link-apple"
              title="Listen on Apple Music"
            >
              <i class="bi bi-apple"></i>
              <span>Apple Music</span>
            </a>
          </div>
        </div>

        <div class="chd-actions" v-if="canManage">
          <button class="btn btn-outline-gold" @click="openEditModal">
            <i class="bi bi-pencil me-2"></i> Edit
          </button>
          <button class="btn btn-outline-danger" @click="confirmDelete">
            <i class="bi bi-trash me-2"></i> Delete
          </button>
        </div>
      </div>
    </template>

    <Teleport to="body">
      <div class="modal fade" id="chdFormModal" tabindex="-1" ref="formModalEl">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content ch-modal">
            <div class="modal-header">
              <h5 class="modal-title">Edit Concert</h5>
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
                    {{ saving ? 'Saving...' : 'Update' }}
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

export default {
  name: 'ConcertHistoryDetail',
  computed: {
    ...mapState(useConcertHistoryStore, ['currentConcert', 'loading', 'error']),
    concert() {
      return this.currentConcert
    },
    canManage() {
      const authStore = useAuthStore()
      const role = authStore.user?.role?.toLowerCase()
      return role === 'admin' || role === 'manager'
    },
    hasLinks() {
      return !!(this.concert?.youtube_link || this.concert?.spotify_link || this.concert?.apple_music_link)
    },
    bannerStyle() {
      if (this.concert?.banner) {
        return { backgroundImage: `url(${this.concert.banner})` }
      }
      return {}
    },
  },
  data() {
    return {
      saving: false,
      successMessage: '',
      errorMessage: '',
      form: {
        title: '',
        description: '',
        concert_date: '',
        banner: '',
        youtube_link: '',
        spotify_link: '',
        apple_music_link: '',
      },
      formModalInstance: null,
    }
  },
  async mounted() {
    const id = this.$route.params.id
    if (id) {
      try {
        await this.fetchConcert(id)
      } catch {
        // error handled by store
      }
    }
  },
  methods: {
    ...mapActions(useConcertHistoryStore, ['fetchConcert', 'updateConcert', 'deleteConcert', 'uploadBanner']),

    formatDate(dateStr) {
      if (!dateStr) return ''
      const d = new Date(dateStr + 'T00:00:00')
      return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
    },

    openEditModal() {
      this.form = {
        title: this.concert.title || '',
        description: this.concert.description || '',
        concert_date: this.concert.concert_date || '',
        banner: this.concert.banner || '',
        youtube_link: this.concert.youtube_link || '',
        spotify_link: this.concert.spotify_link || '',
        apple_music_link: this.concert.apple_music_link || '',
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
        await this.updateConcert(this.concert.id, this.form)
        this.successMessage = 'Concert updated successfully.'
        setTimeout(() => this.hideFormModal(), 1000)
      } catch (err) {
        this.errorMessage = err.message || 'Unable to update concert.'
      } finally {
        this.saving = false
      }
    },

    confirmDelete() {
      if (!confirm(`Delete "${this.concert.title}"? This cannot be undone.`)) return
      this.deleteRecord()
    },

    async deleteRecord() {
      this.saving = true
      try {
        await this.deleteConcert(this.concert.id)
        this.$router.push('/library/concert-history')
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
.chd-back {
  margin-bottom: 1rem;
}

.chd-back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: rgba(234, 220, 194, 0.6);
  font-size: 0.9rem;
  text-decoration: none;
  transition: color 0.2s;
}

.chd-back-link:hover {
  color: var(--gold-color, #c8a45d);
}

.chd-hero {
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(234, 220, 194, 0.12);
}

.chd-hero-banner {
  position: relative;
  height: 340px;
  background-size: cover;
  background-position: center;
  background-color: #10131f;
}

.chd-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(16, 19, 31, 0.2) 0%, rgba(16, 19, 31, 0.9) 100%);
}

.chd-hero-content {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 2rem;
}

.chd-title {
  font-size: clamp(1.6rem, 4vw, 2.6rem);
  font-weight: 800;
  color: #fffdf8;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
}

.chd-date {
  font-size: 1rem;
  color: var(--gold-color, #c8a45d);
  font-weight: 600;
  margin-bottom: 0;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.4);
}

.chd-body {
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 14px;
  padding: 1.75rem;
}

.chd-section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--gold-color, #c8a45d);
  margin-bottom: 0.75rem;
}

.chd-description {
  margin-bottom: 1.5rem;
}

.chd-desc-text {
  color: rgba(234, 220, 194, 0.78);
  line-height: 1.7;
  font-size: 0.95rem;
  white-space: pre-wrap;
}

.chd-links-wrap {
  margin-bottom: 1.5rem;
}

.chd-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.chd-link {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  transition: transform 0.2s, box-shadow 0.2s;
  border: 1px solid rgba(234, 220, 194, 0.1);
  color: rgba(234, 220, 194, 0.85);
}

.chd-link:hover {
  transform: translateY(-3px);
}

.chd-link i {
  font-size: 1.4rem;
}

.chd-link-youtube {
  background: rgba(255, 0, 0, 0.1);
  border-color: rgba(255, 0, 0, 0.2);
}

.chd-link-youtube:hover {
  background: rgba(255, 0, 0, 0.18);
  box-shadow: 0 6px 20px rgba(255, 0, 0, 0.15);
  color: #ff4444;
}

.chd-link-spotify {
  background: rgba(30, 215, 96, 0.1);
  border-color: rgba(30, 215, 96, 0.2);
}

.chd-link-spotify:hover {
  background: rgba(30, 215, 96, 0.18);
  box-shadow: 0 6px 20px rgba(30, 215, 96, 0.15);
  color: #1ed760;
}

.chd-link-apple {
  background: rgba(251, 55, 80, 0.08);
  border-color: rgba(251, 55, 80, 0.15);
}

.chd-link-apple:hover {
  background: rgba(251, 55, 80, 0.15);
  box-shadow: 0 6px 20px rgba(251, 55, 80, 0.12);
  color: #fb3750;
}

.chd-actions {
  display: flex;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(234, 220, 194, 0.08);
}

.ch-preview-img {
  max-height: 120px;
  border-radius: 8px;
  border: 1px solid rgba(234, 220, 194, 0.12);
}

@media (max-width: 767.98px) {
  .chd-hero-banner {
    height: 240px;
  }

  .chd-hero-content {
    padding: 1.25rem;
  }

  .chd-body {
    padding: 1.25rem;
  }

  .chd-links {
    flex-direction: column;
  }

  .chd-link {
    width: 100%;
    justify-content: center;
  }
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
</style>
