<template>
    <div class="auth-page">
        <div class="auth-card shadow-lg">
            <div class="text-center mb-4">
                <h2 class="fw-bold">{{ isLogin ? 'Welcome back' : 'Create your account' }}</h2>
                <p class="text-muted mb-0">
                    {{ isLogin ? 'Sign in to continue to Resonanz.' : 'Register with your role and start exploring.' }}
                </p>
            </div>

            <form @submit.prevent="submitForm">
                <div v-if="!isLogin" class="mb-3">
                    <label class="form-label">Full name</label>
                    <input v-model="form.name" type="text" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input v-model="form.email" type="email" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input v-model="form.password" type="password" class="form-control" minlength="6" required />
                </div>

                <div v-if="errorMessage" class="alert alert-danger py-2">{{ errorMessage }}</div>
                <div v-if="successMessage" class="alert alert-success py-2">{{ successMessage }}</div>

                <button class="btn btn-primary w-100" :disabled="authStore.loading">
                    {{ isLogin ? 'Login' : 'Register' }}
                </button>
            </form>

            <div class="text-center mt-3">
                <button class="btn btn-link p-0" type="button" @click="toggleMode">
                    {{ isLogin ? 'Create an account' : 'Already have an account?' }}
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
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.auth-card {
    width: min(100%, 460px);
    background: white;
    border-radius: 1rem;
    padding: 2rem;
}
</style>
