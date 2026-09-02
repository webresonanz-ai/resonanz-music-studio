<template>
  <aside class="sidebar" :class="{ open: sidebarOpen }" aria-label="Admin navigation">
    <div class="sidebar-header">
      <img src="/logo_resonanz_bgwhite.webp" alt="Resonanz Logo" class="sidebar-logo" width="128" height="128" />
    </div>

    <div class="sidebar-menu">
      <template v-for="section in visibleSections" :key="section.id">
        <div class="sidebar-section-title">
          <i :class="'bi ' + section.icon"></i>
          <span>{{ section.name }}</span>
        </div>
        <router-link
          v-for="item in section.items"
          :key="item.path"
          :to="item.path"
          class="sidebar-item sidebar-item-link"
          :class="{ active: route.path === item.path }"
          @click="emit('navigate')"
        >
          <i :class="'bi ' + item.icon + ' sidebar-icon'" aria-hidden="true"></i>
          <span class="sidebar-item-content">
            <span class="sidebar-item-title">{{ item.label }}</span>
          </span>
        </router-link>
      </template>
    </div>

    <div class="sidebar-footer p-3 mt-auto">
      <div class="text-white-50 small text-center">
        <p class="mb-1">&copy; 2026 The Resonanz Music Studio</p>
        <p class="mb-0">Admin Portal</p>
      </div>
    </div>
  </aside>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useNavigationStore } from '../stores/navigation'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'AdminSidebar',
  emits: ['navigate'],
  props: {
    sidebarOpen: {
      type: Boolean,
      default: false,
    },
  },
  setup(_, { emit }) {
    const navigationStore = useNavigationStore()
    const authStore = useAuthStore()
    const route = useRoute()

    const visibleSections = computed(() => {
      const role = authStore.user?.role?.toLowerCase()
      return navigationStore.sections
        .map((section) => ({
          ...section,
          items: section.items.filter((item) => !item.roles || item.roles.includes(role)),
        }))
        .filter((section) => section.items.length > 0)
    })

    return {
      navigationStore,
      route,
      visibleSections,
      emit,
    }
  },
}
</script>

<style scoped>
.sidebar-logo {
  max-width: 8rem;
  height: auto;
  border-radius: 10px;
}

.sidebar-section-title {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 1.1rem 0.9rem 0.4rem;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: rgba(234, 220, 194, 0.4);
}

.sidebar-section-title i {
  font-size: 0.8rem;
  color: var(--gold-color);
}

.sidebar-item-link {
  text-decoration: none;
  min-height: 52px;
}

.sidebar-item-link .sidebar-icon {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  place-items: center;
  border-radius: 8px;
  border: 1px solid rgba(234, 220, 194, 0.12);
  background: rgba(234, 220, 194, 0.08);
  font-size: 1rem;
  color: var(--gold-color);
}
</style>