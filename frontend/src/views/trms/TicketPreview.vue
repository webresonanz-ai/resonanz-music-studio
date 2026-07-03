<template>
  <div class="ticket-preview-page">
    <div class="content-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="fw-bold mb-1">Concert Ticket Preview</h2>
          <p class="text-muted mb-0">Registration ID: {{ id }}</p>
        </div>
        <button class="btn btn-outline-secondary" @click="router.back()">
          <i class="bi bi-arrow-left me-2"></i>Back
        </button>
      </div>

      <!-- Loading / waiting for auth -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading ticket...</span>
        </div>
        <p class="text-muted mt-3">Loading ticket PDF...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ errorMessage }}
      </div>

      <!-- PDF Viewer -->
      <div v-else-if="pdfUrl" class="pdf-container">
        <iframe
          :src="pdfUrl"
          class="pdf-iframe"
          title="Concert Ticket PDF"
        ></iframe>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const apiStore = useApiStore()
const authStore = useAuthStore()

const id = route.params.id
const pdfUrl = ref(null)
const loading = ref(true)   // start as true — we're either waiting for auth or fetching
const errorMessage = ref('')
const loaded = ref(false)   // guard against calling loadTicket more than once

async function loadTicket() {
  if (loaded.value) return
  loaded.value = true

  const role = authStore.user?.role?.toLowerCase()
  if (role !== 'admin' && role !== 'manager') {
    errorMessage.value = 'You do not have permission to view tickets.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const blob = await apiStore.fetchBlob(`/trms/concert/ticket/${id}`)
    pdfUrl.value = URL.createObjectURL(blob)
  } catch (error) {
    errorMessage.value = error.message || 'Failed to load ticket PDF'
  } finally {
    loading.value = false
  }
}

// Watch authStore.user — handles both:
//   1. user already in store when component mounts (immediate: true)
//   2. user arrives slightly later after fetchMe() resolves
watch(
  () => authStore.user,
  (user) => {
    if (user && !loaded.value) {
      loadTicket()
    } else if (!user && !authStore.loading && !loaded.value) {
      // auth finished loading but still no user — not logged in
      errorMessage.value = 'You must be logged in to view tickets.'
      loading.value = false
      loaded.value = true
    }
  },
  { immediate: true }
)

// Fallback: if authStore.loading is false and user is already set on mount
// the watch with immediate:true covers this, but also handle the case
// where authStore is done loading but watch fires before we check loading state
watch(
  () => authStore.loading,
  (isLoading) => {
    if (!isLoading && !loaded.value) {
      if (authStore.user) {
        loadTicket()
      } else {
        errorMessage.value = 'You must be logged in to view tickets.'
        loading.value = false
        loaded.value = true
      }
    }
  }
)

onBeforeUnmount(() => {
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
  }
})
</script>

<style scoped>
.ticket-preview-page {
  min-height: 70vh;
}

.pdf-container {
  width: 100%;
  height: 80vh;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  overflow: hidden;
  background: #f8f9fa;
}

.pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

@media (max-width: 768px) {
  .pdf-container {
    height: 60vh;
  }
}
</style>
