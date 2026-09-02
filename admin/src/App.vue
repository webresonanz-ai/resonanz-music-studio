<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminSidebar from './components/AdminSidebar.vue'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()

const sidebarOpen = ref(false)

watch(
  () => route.fullPath,
  () => {
    sidebarOpen.value = false
  }
)

function handleLogout() {
  authStore.logout()
  router.push('/auth')
}
</script>

<template>
  <div class="app-shell">
    <AdminSidebar :sidebar-open="sidebarOpen" @navigate="sidebarOpen = false" />

    <div class="sidebar-backdrop" :class="{ 'd-block': sidebarOpen }" @click="sidebarOpen = false"></div>

    <main class="main-content">
      <nav class="navbar main-navbar" aria-label="Top bar">
        <div class="container-fluid">
          <button
            class="navbar-toggler d-lg-none border-0 text-white-50"
            type="button"
            aria-label="Toggle navigation"
            @click="sidebarOpen = !sidebarOpen"
          >
            <i class="bi bi-list fs-2"></i>
          </button>

          <span class="navbar-brand fs-5 fw-bold mb-0 d-none d-sm-inline-block">
            <span class="text-warning">Resonanz</span> Admin
          </span>

          <ul class="navbar-nav ms-auto flex-row align-items-center gap-2">
            <li class="nav-item dropdown">
              <button
                class="btn btn-sm btn-outline-gold d-flex align-items-center gap-2"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-sm-inline">{{ authStore.user?.name || 'User' }}</span>
                <i class="bi bi-chevron-down small"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow">
                <li class="dropdown-header text-uppercase small">{{ authStore.user?.role }}</li>
                <li>
                  <button class="dropdown-item text-danger" type="button" @click="handleLogout">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                  </button>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>

      <div class="content-area mt-3">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>
  </div>
</template>

<style scoped>
.main-navbar {
  border: 1px solid var(--hairline-color);
  border-radius: 12px;
  background: rgba(16, 19, 31, 0.72);
  backdrop-filter: blur(10px);
  padding: 0.5rem 0.9rem;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>