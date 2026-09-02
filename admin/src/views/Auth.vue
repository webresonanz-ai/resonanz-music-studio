<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

defineOptions({ name: 'AdminAuth' })

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()

const form = ref({ email: '', password: '' })
const errorMessage = ref('')
const successMessage = ref('')

const unauthorized = route.query.unauthorized === '1'
if (unauthorized) {
  errorMessage.value = 'Your account does not have access to that page.'
}

async function submit() {
  errorMessage.value = ''
  try {
    const response = await authStore.login(form.value)
    if (response?.message) successMessage.value = response.message

    const redirect = route.query.redirect
    const fallback = '/trms/manager'
    await router.push(typeof redirect === 'string' ? redirect : fallback)
  } catch (error) {
    errorMessage.value = error.message || 'Login failed'
  }
}
</script>

<template>
  <div class="auth-wrapper d-flex align-items-center justify-content-center">
    <div class="auth-card fade-in-up">
      <div class="text-center mb-4">
        <img src="/logo_resonanz_bgwhite.webp" alt="Resonanz Logo" class="auth-logo" width="112" height="112" />
        <h1 class="h4 mt-3 mb-1 fw-bold">
          <span class="text-warning">Resonanz</span> Music Studio
        </h1>
        <p class="text-white-50 mb-0 small">Admin Portal Sign In</p>
      </div>

      <div v-if="errorMessage" class="alert alert-danger py-2" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>{{ errorMessage }}
      </div>
      <div v-if="successMessage" class="alert alert-success py-2" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ successMessage }}
      </div>

      <form @submit.prevent="submit">
        <div class="mb-3">
          <label for="email" class="form-label small text-uppercase text-white-50">Email</label>
          <input
            id="email"
            v-model.trim="form.email"
            type="email"
            class="form-control form-control-lg bg-dark text-white border-secondary"
            required
            autocomplete="email"
            placeholder="you@example.com"
          />
        </div>

        <div class="mb-4">
          <label for="password" class="form-label small text-uppercase text-white-50">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="form-control form-control-lg bg-dark text-white border-secondary"
            required
            autocomplete="current-password"
            placeholder="••••••••"
          />
        </div>

        <button type="submit" class="btn btn-gold btn-lg w-100" :disabled="authStore.loading">
          <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-box-arrow-in-right me-2"></i>
          Sign In
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.auth-wrapper {
  min-height: 100vh;
  padding: 1.5rem;
}

.auth-card {
  width: min(100%, 420px);
  border: 1px solid rgba(234, 220, 194, 0.16);
  border-radius: 16px;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.1), transparent 40%),
    linear-gradient(180deg, rgba(16, 19, 31, 0.92), rgba(29, 36, 51, 0.92));
  box-shadow: 0 24px 60px rgba(19, 18, 16, 0.4);
  padding: 2.25rem;
}

.auth-logo {
  border-radius: 14px;
}

.form-control {
  border-radius: 10px;
}
</style>