<template>
  <div id="app">
    <LoadingScreen :visible="showLoading" />
    <CookieConsent :visible="showCookieConsent" @accept="acceptCookies" @decline="declineCookies" />
    <NotificationPrompt :visible="showNotificationPrompt" @allow="allowNotifications" @dismiss="dismissNotifications" />

    <ArtDirector v-if="navigationStore.standalonePage === 'art-director'" @close="closeStandalone" />

    <template v-if="navigationStore.standalonePage !== 'art-director'">
      <div v-if="sidebarOpen && !hideShellNav" class="sidebar-backdrop" @click="closeSidebar"></div>

      <AppSidebar v-if="!hideShellNav" :sidebar-open="sidebarOpen" @navigate="closeSidebar" />

      <div class="main-content" :class="[{ 'main-content-full': hideShellNav }, mainContentBgClass]">
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
    </template>
  </div>
</template>

<script>
import { computed, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNavigationStore } from './stores/navigation'
import { useBannerStore } from './stores/banner'
import AppSidebar from './components/AppSidebar.vue'
import AppNavbar from './components/AppNavbar.vue'
import AppFooter from './components/AppFooter.vue'
import ArtDirector from './views/ArtDirector.vue'
import LoadingScreen from './components/LoadingScreen.vue'
import CookieConsent from './components/CookieConsent.vue'
import NotificationPrompt from './components/NotificationPrompt.vue'

export default {
  name: 'App',
  components: {
    AppSidebar,
    AppNavbar,
    AppFooter,
    ArtDirector,
    LoadingScreen,
    CookieConsent,
    NotificationPrompt
  },
  setup() {
    const sidebarOpen = ref(false)
    const showLoading = ref(true)
    const showCookieConsent = ref(false)
    const showNotificationPrompt = ref(false)
    const route = useRoute()
    const router = useRouter()
    const navigationStore = useNavigationStore()
    const bannerStore = useBannerStore()
    const hideShellNav = computed(() => route.meta.hideShellNav === true)
    const mainContentBgClass = computed(() => {
      return route.path === '/bms/home' ? 'main-content-bms-home' : ''
    })

    onMounted(() => {
      router.isReady().then(() => {
        const MIN_DISPLAY = 800
        const startTime = Date.now()
        const elapsed = Date.now() - startTime
        const remaining = Math.max(0, MIN_DISPLAY - elapsed)
        setTimeout(() => {
          showLoading.value = false
        }, 50)
      })
    })

    watch(showLoading, (val) => {
      if (!val) {
        setTimeout(checkCookieConsent, 600)
      }
    })

    const checkCookieConsent = () => {
      const consent = localStorage.getItem('resonanz_cookie_consent')
      if (!consent) {
        showCookieConsent.value = true
      }
    }

    const acceptCookies = () => {
      localStorage.setItem('resonanz_cookie_consent', 'accepted')
      showCookieConsent.value = false
      setTimeout(checkNotificationPrompt, 1200)
    }

    const declineCookies = () => {
      localStorage.setItem('resonanz_cookie_consent', 'declined')
      showCookieConsent.value = false
    }

    const checkNotificationPrompt = () => {
      if (!('Notification' in window)) return
      if (Notification.permission === 'granted' || Notification.permission === 'denied') return
      if (localStorage.getItem('resonanz_notification_prompted')) return
      showNotificationPrompt.value = true
    }

    const allowNotifications = async () => {
      showNotificationPrompt.value = false
      try {
        const permission = await Notification.requestPermission()
        if (permission === 'granted') {
          localStorage.setItem('resonanz_notification_prompted', 'allowed')
          new Notification('The Resonanz Music Studio', {
            body: "Thank you! You'll now receive updates about events and news.",
            icon: '/logo_resonanz.webp'
          })
        } else {
          localStorage.setItem('resonanz_notification_prompted', 'denied')
        }
      } catch {
        localStorage.setItem('resonanz_notification_prompted', 'denied')
      }
    }

    const dismissNotifications = () => {
      localStorage.setItem('resonanz_notification_prompted', 'dismissed')
      showNotificationPrompt.value = false
    }

    const toggleSidebar = () => {
      sidebarOpen.value = !sidebarOpen.value
    }

    const closeSidebar = () => {
      sidebarOpen.value = false
    }

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

    const closeStandalone = () => {
      navigationStore.standalonePage = null
      navigationStore.setActiveProgram('trms')
      router.push('/trms/home')
    }

    return {
      sidebarOpen,
      showLoading,
      showCookieConsent,
      showNotificationPrompt,
      hideShellNav,
      mainContentBgClass,
      navigationStore,
      toggleSidebar,
      closeSidebar,
      closeStandalone,
      acceptCookies,
      declineCookies,
      allowNotifications,
      dismissNotifications
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

/* Concert banner silhouette */
body.has-concert-banner {
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
  filter: grayscale(100%) brightness(15%) blur(4px) sepia(35%);
  transform: scale(1.05);
  animation: bannerFadeIn 0.9s ease forwards;
}

@keyframes bannerFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* BMS Home — full main-content background */
.main-content-bms-home {
  background:
    linear-gradient(180deg, rgba(10, 12, 20, 0.78) 0%, rgba(10, 12, 20, 0.25) 35%, rgba(10, 12, 20, 0.35) 65%, rgba(10, 12, 20, 0.82) 100%),
    url('/bms_bg.jpg') no-repeat center center / cover fixed;
}
</style>
