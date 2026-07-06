<template>
  <div id="app">
    <div v-if="sidebarOpen && !hideShellNav" class="sidebar-backdrop" @click="closeSidebar"></div>

    <AppSidebar v-if="!hideShellNav" :sidebar-open="sidebarOpen" @navigate="closeSidebar" />

    <div class="main-content" :class="{ 'main-content-full': hideShellNav }">
      <AppNavbar v-if="!hideShellNav" :sidebar-open="sidebarOpen" @toggle-sidebar="toggleSidebar" />

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
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useNavigationStore } from './stores/navigation'
import { useBannerStore } from './stores/banner'
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
    const bannerStore = useBannerStore()
    const hideShellNav = computed(() => route.meta.hideShellNav === true)

    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const closeSidebar = () => {
      sidebarOpen.value = false
    }

    // Apply / remove the silhouette background on <body> whenever the banner URL changes
    watch(
      () => bannerStore.url,
      (url) => {
        if (url) {
          document.body.style.setProperty('--concert-banner-url', `url('${url}')`)
          document.body.classList.add('has-concert-banner')
        } else {
          document.body.style.removeProperty('--concert-banner-url')
          document.body.classList.remove('has-concert-banner')
        }
      },
      { immediate: true }
    )

    // Clear banner whenever navigating away from concert registration pages
    watch(
      () => route.fullPath,
      (fullPath) => {
        const program = fullPath.split('/').filter(Boolean)[0]
        if (navigationStore.navItems[program]) {
          navigationStore.setActiveProgram(program)
        }
        closeSidebar()

        if (!fullPath.includes('/concert-reg')) {
          bannerStore.clearBanner()
        }
      },
      { immediate: true }
    )

    return {
      sidebarOpen,
      hideShellNav,
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

.main-content-full {
  margin-left: 0;
}

/* ── Concert banner silhouette — applied to <body> ─────────────────
   The banner store sets --concert-banner-url and adds .has-concert-banner.
   Using a ::before pseudo-element means the real body background
   (gradient + grid texture defined in custom.css) is fully replaced
   while this class is active.
──────────────────────────────────────────────────────────────────── */
body.has-concert-banner {
  /* Override the default warm-gradient body background */
  background: #0a0a12 !important;
  transition: background 0.8s ease;
}

body.has-concert-banner::after {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;

  background-image: var(--concert-banner-url);
  background-size: cover;
  background-position: center top;
  background-repeat: no-repeat;

  /* Silhouette filter stack */
  filter: grayscale(100%) brightness(15%) blur(4px) sepia(35%);

  /* Slightly upscaled to hide blur fringe at edges */
  transform: scale(1.05);

  /* Smooth fade-in */
  animation: bannerFadeIn 0.9s ease forwards;
}

@keyframes bannerFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}
</style>
