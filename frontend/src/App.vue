<template>
  <div id="app">
    <button
      @click="toggleSidebar"
      class="mobile-toggle"
      type="button"
      :aria-expanded="sidebarOpen"
      aria-label="Toggle navigation"
    >
      <i class="bi bi-list"></i>
    </button>
    
    <div v-if="sidebarOpen" class="sidebar-backdrop" @click="closeSidebar"></div>

    <AppSidebar :sidebar-open="sidebarOpen" @navigate="closeSidebar" />
    
    <div class="main-content">
      <AppNavbar />
      
      <div class="content-area mt-4">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
      
      <AppFooter />
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useNavigationStore } from './stores/navigation'
import AppSidebar from './components/AppSidebar.vue'
import AppNavbar from './components/AppNavbar.vue'
import AppFooter from './components/AppFooter.vue'

export default {
  name: 'App',
  components: {
    AppSidebar,
    AppNavbar,
    AppFooter
  },
  setup() {
    const sidebarOpen = ref(false)
    const route = useRoute()
    const navigationStore = useNavigationStore()
    
    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const closeSidebar = () => {
      sidebarOpen.value = false
    }

    watch(
      () => route.fullPath,
      (fullPath) => {
        const program = fullPath.split('/').filter(Boolean)[0]
        if (navigationStore.navItems[program]) {
          navigationStore.setActiveProgram(program)
        }
        closeSidebar()
      },
      { immediate: true }
    )
    
    return {
      sidebarOpen,
      toggleSidebar,
      closeSidebar
    }
  }
}
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.24s ease, transform 0.24s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
