<template>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-card-glow"></div>
            <div class="text-center mb-4">
                <div class="auth-logo mb-3">
                    <img src="/logo_resonanz_bgwhite.png" alt="Resonanz Logo" class="auth-logo-img" />
                </div>
                <h2 class="fw-bold auth-heading">{{ isLogin ? 'Welcome back' : 'Create your account' }}</h2>
                <p class="auth-subtitle mb-0">
                    {{ isLogin ? 'Sign in to continue to Resonanz.' : 'Register with your role and start exploring.' }}
                </p>
            </div>

            <form @submit.prevent="submitForm">
                <div v-if="!isLogin" class="mb-3">
                    <label class="form-label auth-label">Full name</label>
                    <input v-model="form.name" type="text" class="form-control form-control-dark" required />
                </div>

                <div class="mb-3">
                    <label class="form-label auth-label">Email</label>
                    <input v-model="form.email" type="email" class="form-control form-control-dark" required />
                </div>

                <div class="mb-3">
                    <label class="form-label auth-label">Password</label>
                    <input v-model="form.password" type="password" class="form-control form-control-dark" minlength="6" required />
                </div>

                <div v-if="errorMessage" class="alert alert-custom alert-custom-danger py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>{{ errorMessage }}</span>
                </div>
                <div v-if="successMessage" class="alert alert-custom alert-custom-success py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle"></i>
                    <span>{{ successMessage }}</span>
                </div>

                <button class="btn btn-primary w-100 auth-btn" :disabled="authStore.loading">
                    <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    {{ isLogin ? 'Login' : 'Register' }}
                </button>
            </form>

            <div class="text-center mt-4 auth-toggle">
                <button class="auth-toggle-btn" type="button" @click="toggleMode">
                    {{ isLogin ? "Don't have an account? " : 'Already have an account? ' }}
                    <span class="auth-toggle-link">{{ isLogin ? 'Create one' : 'Sign in' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const isLogin = ref(true)
const errorMessage = ref('')
const successMessage = ref('')

const form = reactive({
    name: '',
    email: '',
    password: ''
})

const toggleMode = () => {
    isLogin.value = !isLogin.value
    errorMessage.value = ''
    successMessage.value = ''
}

const submitForm = async () => {
    errorMessage.value = ''
    successMessage.value = ''

    try {
        if (isLogin.value) {
            await authStore.login(form)
            successMessage.value = 'Login successful. Redirecting...'
        } else {
            await authStore.register(form)
            successMessage.value = 'Account created successfully. You can now log in.'
            isLogin.value = true
            form.name = ''
            form.password = ''
        }

        setTimeout(() => router.push('/trms/home'), 600)
    } catch (error) {
        errorMessage.value = error.message || 'Authentication failed'
    }
}
</script>

<style scoped>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.auth-card {
    position: relative;
    width: min(100%, 440px);
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

.auth-card-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(ellipse at 30% 20%, rgba(200, 164, 93, 0.05) 0%, transparent 50%);
    pointer-events: none;
}

.auth-logo {
    display: flex;
    justify-content: center;
}

.auth-logo-img {
    max-width: 7rem;
    height: auto;
    border-radius: 10px;
}

.auth-heading {
    font-size: 1.35rem;
    color: var(--gold-color, #c8a45d);
    letter-spacing: 0.01em;
}

.auth-subtitle {
    font-size: 0.875rem;
    color: rgba(234, 220, 194, 0.6);
    margin-top: 0.35rem;
}

.auth-label {
    color: rgba(234, 220, 194, 0.8) !important;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
}

.form-control-dark {
    background: rgba(234, 220, 194, 0.06) !important;
    border: 1px solid rgba(234, 220, 194, 0.15) !important;
    color: rgba(234, 220, 194, 0.88) !important;
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    font-size: 0.9rem;
    transition:
        border-color 0.2s,
        box-shadow 0.2s,
        background 0.2s;
}

.form-control-dark:focus {
    border-color: rgba(200, 164, 93, 0.4) !important;
    box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.1) !important;
    background: rgba(234, 220, 194, 0.08) !important;
}

.form-control-dark::placeholder {
    color: rgba(234, 220, 194, 0.3);
}

.auth-btn {
    padding: 0.7rem !important;
    font-size: 0.95rem !important;
    border-radius: 10px !important;
    margin-top: 0.25rem;
}

.alert-custom {
    border-radius: 10px;
    font-size: 0.85rem;
    margin-bottom: 0;
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

.auth-toggle {
    position: relative;
    z-index: 1;
}

.auth-toggle-btn {
    background: none;
    border: none;
    color: rgba(234, 220, 194, 0.55);
    font-size: 0.85rem;
    cursor: pointer;
    padding: 0;
    transition: color 0.2s;
}

.auth-toggle-btn:hover {
    color: rgba(234, 220, 194, 0.85);
}

.auth-toggle-link {
    color: var(--gold-color, #c8a45d);
    font-weight: 600;
    transition: color 0.2s;
}

.auth-toggle-link:hover {
    color: #dfc280;
    text-decoration: underline;
}
</style>
