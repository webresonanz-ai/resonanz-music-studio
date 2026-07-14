<template>
  <div class="fade-in-up profile-page">
    <div class="profile-card">
      <div class="text-center mb-4">
        <h1 class="fw-bold profile-heading">My Profile</h1>
        <p class="profile-subtitle mb-0">Manage your profile and email verification</p>
      </div>

      <div v-if="message.text" class="alert mb-4 py-2 d-flex align-items-center gap-2"
        :class="message.type === 'success' ? 'alert-custom alert-custom-success' : 'alert-custom alert-custom-danger'">
        <i :class="message.type === 'success' ? 'bi bi-check-circle' : 'bi bi-exclamation-circle'"></i>
        <span>{{ message.text }}</span>
      </div>

      <div class="text-center mb-4">
        <div class="profile-avatar-wrap">
          <img :src="avatarPreview || defaultAvatar" :alt="profile.name"
            class="profile-avatar" @error="avatarError = true" />
          <label class="profile-avatar-overlay" title="Change avatar">
            <i class="bi bi-camera-fill"></i>
            <input type="file" accept="image/jpeg,image/png,image/webp" class="d-none"
              @change="onAvatarChange" />
          </label>
        </div>
      </div>

      <form @submit.prevent="saveProfile">
        <div class="mb-3">
          <label class="form-label profile-label">Name</label>
          <input v-model="profile.name" type="text" class="form-control form-control-dark" required />
        </div>

        <div class="mb-3">
          <label class="form-label profile-label">Username</label>
          <input v-model="profile.username" type="text" class="form-control form-control-dark"
            pattern="^[a-zA-Z0-9_]{3,50}$"
            title="3-50 characters, letters, numbers, and underscores only" />
          <small class="text-champagne-muted">3-50 characters, letters, numbers, and underscores</small>
        </div>

        <div class="mb-4">
          <label class="form-label profile-label">Email</label>
          <div class="d-flex align-items-center gap-2">
            <input :value="profile.email" type="email" class="form-control form-control-dark" readonly disabled />
            <span v-if="isEmailVerified" class="badge bg-success px-3 py-2 fs-6" title="Verified">
              <i class="bi bi-patch-check-fill me-1"></i> Verified
            </span>
            <span v-else class="badge bg-warning text-dark px-3 py-2 fs-6" title="Not verified">
              <i class="bi bi-exclamation-triangle-fill me-1"></i> Unverified
            </span>
          </div>
          <div v-if="!isEmailVerified" class="mt-2">
            <button type="button" class="btn btn-outline-gold btn-sm"
              :disabled="sendingVerification"
              @click="sendVerificationEmail">
              <span v-if="sendingVerification" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-envelope me-1"></i>
              Send verification email
            </button>
          </div>
        </div>

        <button class="btn btn-primary w-100 profile-btn" :disabled="saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
          Save Changes
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApiStore } from '../stores/api'
import { useAuthStore } from '../stores/auth'

const api = useApiStore()
const authStore = useAuthStore()
const route = useRoute()

const saving = ref(false)
const sendingVerification = ref(false)
const avatarError = ref(false)

const message = reactive({ text: '', type: '' })

const profile = reactive({
  name: '',
  username: '',
  email: '',
  avatar_url: null,
  email_verified_at: null,
})

const avatarPreview = ref(null)

const defaultAvatar = 'data:image/svg+xml,' + encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">'
  + '<rect width="120" height="120" rx="60" fill="#7f2432"/>'
  + '<text x="60" y="72" text-anchor="middle" fill="#c8a45d" font-size="48" font-weight="700" font-family="sans-serif">'
  + '?</text></svg>'
)

const isEmailVerified = computed(() => !!profile.email_verified_at)

const showMessage = (text, type = 'success') => {
  message.text = text
  message.type = type
  setTimeout(() => { message.text = '' }, 6000)
}

const loadProfile = async () => {
  try {
    const res = await api.get('/profile')
    Object.assign(profile, {
      name: res.user.name || '',
      username: res.user.username || '',
      email: res.user.email || '',
      avatar_url: res.user.avatar_url || null,
      email_verified_at: res.user.email_verified_at || null,
    })
    if (res.user.avatar_url) {
      avatarPreview.value = res.user.avatar_url
    }
    authStore.user = { ...authStore.user, ...res.user }
    localStorage.setItem('resonanz-user', JSON.stringify(authStore.user))
  } catch (err) {
    showMessage(err.message || 'Failed to load profile', 'error')
  }
}

const onAvatarChange = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    showMessage('File size exceeds the 2 MB limit', 'error')
    return
  }

  const validTypes = ['image/jpeg', 'image/png', 'image/webp']
  if (!validTypes.includes(file.type)) {
    showMessage('Only JPEG, PNG, and WebP images are allowed', 'error')
    return
  }

  const formData = new FormData()
  formData.append('avatar', file)

  try {
    const res = await api.postFormData('/profile/upload-avatar', formData)
    avatarPreview.value = res.url
    profile.avatar_url = res.url
    authStore.user = { ...authStore.user, avatar_url: res.url }
    localStorage.setItem('resonanz-user', JSON.stringify(authStore.user))
    showMessage('Avatar updated')
  } catch (err) {
    showMessage(err.message || 'Upload failed', 'error')
  }
}

const saveProfile = async () => {
  saving.value = true
  try {
    const payload = {}
    if (profile.name.trim()) payload.name = profile.name.trim()
    if (profile.username.trim()) payload.username = profile.username.trim()
    const res = await api.post('/profile/update', payload)
    Object.assign(profile, res.user)
    authStore.user = { ...authStore.user, ...res.user }
    localStorage.setItem('resonanz-user', JSON.stringify(authStore.user))
    showMessage('Profile updated')
  } catch (err) {
    showMessage(err.message || 'Failed to save profile', 'error')
  } finally {
    saving.value = false
  }
}

const sendVerificationEmail = async () => {
  sendingVerification.value = true
  try {
    const res = await api.post('/profile/send-verification', {})
    showMessage(res.message || 'Verification email sent')
  } catch (err) {
    showMessage(err.message || 'Failed to send verification email', 'error')
  } finally {
    sendingVerification.value = false
  }
}

onMounted(() => {
  if (route.query.verified) {
    showMessage(route.query.verified, 'success')
  } else if (route.query.verification_error) {
    showMessage(route.query.verification_error, 'error')
  }
  loadProfile()
})
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 3rem 1rem;
}

.profile-card {
  position: relative;
  width: min(100%, 520px);
  border-radius: 16px;
  padding: 2.5rem 2rem;
  border: 1px solid rgba(234, 220, 194, 0.12);
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.1), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 20px 60px rgba(10, 10, 18, 0.35);
  overflow: hidden;
}

.profile-heading {
  font-size: 1.35rem;
  color: var(--gold-color, #c8a45d);
  letter-spacing: 0.01em;
}

.profile-subtitle {
  font-size: 0.875rem;
  color: rgba(234, 220, 194, 0.6);
  margin-top: 0.35rem;
}

.profile-avatar-wrap {
  position: relative;
  display: inline-block;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid rgba(200, 164, 93, 0.3);
  cursor: pointer;
}

.profile-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.profile-avatar-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(16, 19, 31, 0.6);
  color: #fff;
  font-size: 1.5rem;
  opacity: 0;
  transition: opacity 0.2s;
  cursor: pointer;
}

.profile-avatar-wrap:hover .profile-avatar-overlay {
  opacity: 1;
}

.profile-label {
  color: rgba(234, 220, 194, 0.8) !important;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
}

.form-control-dark[disabled],
.form-control-dark[readonly] {
  opacity: 0.6;
  cursor: not-allowed;
}

.profile-btn {
  padding: 0.7rem !important;
  font-size: 0.95rem !important;
  border-radius: 10px !important;
  margin-top: 0.25rem;
}

.alert-custom {
  border-radius: 10px;
  font-size: 0.85rem;
}

.alert-custom-danger {
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid rgba(220, 53, 69, 0.2);
  color: #e86868;
}

.alert-custom-success {
  background: rgba(76, 175, 125, 0.1);
  border: 1px solid rgba(76, 175, 125, 0.2);
  color: #6bcf9a;
}

.badge.fs-6 {
  font-size: 0.8rem !important;
  white-space: nowrap;
}
</style>
