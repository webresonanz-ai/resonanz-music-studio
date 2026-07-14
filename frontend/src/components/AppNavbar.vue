<template>
  <nav class="main-navbar" role="navigation" aria-label="Main navigation">
    <div class="navbar-inner">
      <!-- Left: Hamburger (mobile sidebar) + Brand -->
      <div class="navbar-left">
        <button
          class="nav-sidebar-toggle"
          type="button"
          :aria-expanded="sidebarOpen"
          aria-label="Toggle sidebar"
          @click="$emit('toggle-sidebar')"
        >
          <i class="bi bi-list"></i>
        </button>

        <div class="navbar-brand">
          <span class="brand-icon">
            <i :class="'bi ' + navigationStore.activeProgramInfo.icon"></i>
          </span>
          <span class="brand-name">{{ navigationStore.activeProgramInfo.name }}</span>
          <span class="brand-sep">/</span>
          <span class="brand-sub">{{ navigationStore.activeProgramInfo.description }}</span>
        </div>
      </div>

      <!-- Center: Primary Nav Links (desktop, first 5) -->
      <ul class="navbar-links" role="list">
        <template v-for="item in primaryNavItems" :key="item.label">
          <!-- Dropdown -->
          <li
            v-if="item.children"
            class="nav-item has-dropdown"
            :class="{ 'is-open': openDropdown === item.label }"
            @mouseenter="onDropdownEnter(item.label)"
            @mouseleave="onDropdownLeave"
          >
            <button
              class="nav-link nav-link-btn"
              type="button"
              :aria-haspopup="true"
              :aria-expanded="openDropdown === item.label"
              @click="toggleDropdown(item.label)"
            >
              <i :class="'bi ' + item.icon"></i>
              <span>{{ item.label }}</span>
              <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <ul class="nav-dropdown" role="menu">
              <li v-for="child in item.children" :key="child.path" role="none">
                <router-link
                  :to="child.path"
                  class="dropdown-link"
                  role="menuitem"
                  @click="closeDropdown"
                >
                  <span class="dropdown-link-icon">
                    <i :class="'bi ' + child.icon"></i>
                  </span>
                  <span>{{ child.label }}</span>
                </router-link>
              </li>
            </ul>
          </li>
          <!-- Regular -->
          <li v-else class="nav-item">
            <router-link :to="item.path" class="nav-link">
              <i :class="'bi ' + item.icon"></i>
              <span>{{ item.label }}</span>
            </router-link>
          </li>
        </template>
      </ul>

      <!-- Right: Cart + Search + User -->
      <div class="navbar-right">
        <button
          v-if="isLibraryRoute"
          class="nav-cart-btn"
          type="button"
          aria-label="Shopping cart"
          @click="goToCart"
        >
          <i class="bi bi-cart3"></i>
          <span v-if="cartStore.count > 0" class="nav-cart-badge">{{ cartStore.count }}</span>
        </button>

        <div class="nav-search-wrap" :class="{ 'is-focused': searchFocused }">
          <i class="bi bi-search search-icon"></i>
          <input
            v-model="searchQuery"
            type="search"
            class="nav-search-input"
            placeholder="Search…"
            aria-label="Search"
            @focus="searchFocused = true"
            @blur="searchFocused = false"
          />
        </div>

        <!-- User menu -->
        <div class="nav-user" :class="{ 'is-open': userMenuOpen }">
          <button
            class="nav-user-btn"
            type="button"
            :aria-expanded="userMenuOpen"
            aria-label="User menu"
            @click="userMenuOpen = !userMenuOpen"
          >
            <span class="user-avatar">
              <img v-if="authStore.user?.avatar_url" :src="authStore.user.avatar_url" :alt="authStore.user.name" class="user-avatar-img" />
              <span v-else-if="authStore.user" class="user-initials">
                {{ getUserInitials(authStore.user.name) }}
              </span>
              <i v-else class="bi bi-person"></i>
            </span>
            <i class="bi bi-chevron-down nav-chevron"></i>
          </button>
          <ul class="nav-dropdown nav-dropdown-end" role="menu">
            <li v-if="authStore.user" class="dropdown-header">
              <span class="dropdown-header-avatar">
                <img v-if="authStore.user?.avatar_url" :src="authStore.user.avatar_url" :alt="authStore.user.name" class="dropdown-header-img" />
                <span v-else>{{ getUserInitials(authStore.user.name) }}</span>
              </span>
              <div>
                <div class="dropdown-header-name">{{ authStore.user.name }}</div>
                <div class="dropdown-header-role">{{ authStore.user.role }}</div>
              </div>
            </li>
            <li v-if="authStore.user" role="none">
              <router-link class="dropdown-link" to="/profile" @click="userMenuOpen = false">
                <span class="dropdown-link-icon"><i class="bi bi-person-gear"></i></span>
                <span>Profile</span>
              </router-link>
            </li>
            <li v-if="authStore.user" role="none">
              <router-link class="dropdown-link" to="/library/my-orders" @click="userMenuOpen = false">
                <span class="dropdown-link-icon"><i class="bi bi-receipt"></i></span>
                <span>My Orders</span>
              </router-link>
            </li>
            <li v-if="isComposerOrArranger" role="none">
              <router-link class="dropdown-link" to="/library/composer-dashboard" @click="userMenuOpen = false">
                <span class="dropdown-link-icon"><i class="bi bi-pencil-square"></i></span>
                <span>My Scores</span>
              </router-link>
            </li>
            <li v-if="isScoresManager" role="none">
              <router-link class="dropdown-link" to="/library/orders-dashboard" @click="userMenuOpen = false">
                <span class="dropdown-link-icon"><i class="bi bi-bar-chart-steps"></i></span>
                <span>Orders Dashboard</span>
              </router-link>
            </li>
            <li v-if="authStore.user" class="dropdown-sep" role="separator"></li>
            <li role="none">
              <button
                class="dropdown-link"
                type="button"
                role="menuitem"
                @click="
                  authStore.user ? logout() : router.push('/auth');
                  userMenuOpen = false;
                "
              >
                <span class="dropdown-link-icon">
                  <i
                    :class="authStore.user ? 'bi bi-box-arrow-right' : 'bi bi-box-arrow-in-right'"
                  ></i>
                </span>
                <span>{{ authStore.user ? "Logout" : "Login" }}</span>
              </button>
            </li>
          </ul>
        </div>

        <!-- Mobile menu toggle -->
        <button
          class="nav-mobile-toggle"
          type="button"
          :aria-expanded="mobileMenuOpen"
          aria-label="Toggle navigation menu"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <i :class="mobileMenuOpen ? 'bi bi-x-lg' : 'bi bi-grid'"></i>
        </button>
      </div>
    </div>

    <!-- Overflow nav row (desktop, items 6+) -->
    <div v-if="overflowNavItems.length" class="navbar-overflow-row">
      <ul class="navbar-overflow-links" role="list">
        <template v-for="item in overflowNavItems" :key="item.label">
          <li
            v-if="item.children"
            class="nav-item has-dropdown"
            :class="{ 'is-open': openDropdown === item.label }"
            @mouseenter="onDropdownEnter(item.label)"
            @mouseleave="onDropdownLeave"
          >
            <button
              class="nav-link nav-link-btn nav-link-sm"
              type="button"
              :aria-haspopup="true"
              :aria-expanded="openDropdown === item.label"
              @click="toggleDropdown(item.label)"
            >
              <i :class="'bi ' + item.icon"></i>
              <span>{{ item.label }}</span>
              <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <ul class="nav-dropdown" role="menu">
              <li v-for="child in item.children" :key="child.path" role="none">
                <router-link
                  :to="child.path"
                  class="dropdown-link"
                  role="menuitem"
                  @click="closeDropdown"
                >
                  <span class="dropdown-link-icon">
                    <i :class="'bi ' + child.icon"></i>
                  </span>
                  <span>{{ child.label }}</span>
                </router-link>
              </li>
            </ul>
          </li>
          <li v-else class="nav-item">
            <router-link :to="item.path" class="nav-link nav-link-sm">
              <i :class="'bi ' + item.icon"></i>
              <span>{{ item.label }}</span>
            </router-link>
          </li>
        </template>
      </ul>
    </div>

    <!-- Mobile nav menu -->
    <div
      class="navbar-mobile-menu"
      :class="{ 'is-open': mobileMenuOpen }"
      aria-hidden="!mobileMenuOpen"
    >
      <template v-for="item in filteredNavItems" :key="item.label">
        <div v-if="item.children" class="mobile-nav-group">
          <button
            class="mobile-nav-group-header"
            type="button"
            :aria-expanded="openMobileGroup === item.label"
            @click="toggleMobileGroup(item.label)"
          >
            <span class="mobile-nav-icon"><i :class="'bi ' + item.icon"></i></span>
            <span>{{ item.label }}</span>
            <i
              class="bi bi-chevron-down mobile-chevron"
              :class="{ rotated: openMobileGroup === item.label }"
            ></i>
          </button>
          <div class="mobile-nav-children" :class="{ 'is-open': openMobileGroup === item.label }">
            <router-link
              v-for="child in item.children"
              :key="child.path"
              :to="child.path"
              class="mobile-nav-child"
              @click="closeMobileMenu"
            >
              <span class="mobile-nav-icon sm"><i :class="'bi ' + child.icon"></i></span>
              <span>{{ child.label }}</span>
            </router-link>
          </div>
        </div>
        <router-link v-else :to="item.path" class="mobile-nav-link" @click="closeMobileMenu">
          <span class="mobile-nav-icon"><i :class="'bi ' + item.icon"></i></span>
          <span>{{ item.label }}</span>
        </router-link>
      </template>
    </div>
  </nav>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useNavigationStore } from "../stores/navigation";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";
import { useRouter, useRoute } from "vue-router";

export default {
  name: "AppNavbar",
  props: {
    sidebarOpen: { type: Boolean, default: false },
  },
  emits: ["toggle-sidebar"],
  setup() {
    const navigationStore = useNavigationStore();
    const authStore = useAuthStore();
    const cartStore = useCartStore();
    const router = useRouter();
    const route = useRoute();

    const isLibraryRoute = computed(() => route.path.startsWith('/library'))
    const isComposerOrArranger = computed(() => ['composer', 'arranger'].includes(authStore.user?.role?.toLowerCase()))
    const isScoresManager = computed(() => ['admin', 'manager', 'manager_scores'].includes(authStore.user?.role?.toLowerCase()))

    const goToCart = () => {
      if (!authStore.token) {
        router.push('/auth')
        return
      }
      router.push('/library/sheet-music?cart=1')
    }

    const canSee = (item) => {
      if (!item.roles) return true;
      const role = authStore.user?.role?.toLowerCase();
      return !!role && item.roles.includes(role);
    };

    const filteredNavItems = computed(() =>
      navigationStore.currentNavItems
        .map((item) => {
          if (!item.children) return canSee(item) ? item : null;
          const visibleChildren = item.children.filter(canSee);
          if (!visibleChildren.length) return null;
          return { ...item, children: visibleChildren };
        })
        .filter(Boolean),
    );

    const PRIMARY_LIMIT = 4;
    const primaryNavItems = computed(() => filteredNavItems.value.slice(0, PRIMARY_LIMIT));
    const overflowNavItems = computed(() => filteredNavItems.value.slice(PRIMARY_LIMIT));

    const openDropdown = ref(null);
    const openMobileGroup = ref(null);
    const userMenuOpen = ref(false);
    const mobileMenuOpen = ref(false);
    const searchFocused = ref(false);
    const searchQuery = ref("");
    let leaveTimer = null;

    const isDesktop = () => window.innerWidth >= 992;

    const onDropdownEnter = (label) => {
      if (!isDesktop()) return;
      clearTimeout(leaveTimer);
      openDropdown.value = label;
    };
    const onDropdownLeave = () => {
      if (!isDesktop()) return;
      leaveTimer = setTimeout(() => {
        openDropdown.value = null;
      }, 120);
    };
    const toggleDropdown = (label) => {
      openDropdown.value = openDropdown.value === label ? null : label;
    };
    const closeDropdown = () => {
      openDropdown.value = null;
    };

    const toggleMobileGroup = (label) => {
      openMobileGroup.value = openMobileGroup.value === label ? null : label;
    };
    const closeMobileMenu = () => {
      mobileMenuOpen.value = false;
      openMobileGroup.value = null;
    };

    const getUserInitials = (name) => {
      if (!name) return "?";
      return name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
    };

    const logout = () => {
      authStore.logout();
      router.push("/auth");
    };

    const handleOutsideClick = (e) => {
      if (!e.target.closest(".has-dropdown") && !e.target.closest(".nav-user")) {
        openDropdown.value = null;
        userMenuOpen.value = false;
      }
    };

    onMounted(() => document.addEventListener("click", handleOutsideClick));
    onUnmounted(() => {
      document.removeEventListener("click", handleOutsideClick);
      clearTimeout(leaveTimer);
    });

    return {
      navigationStore,
      authStore,
      cartStore,
      router,
      route,
      filteredNavItems,
      primaryNavItems,
      overflowNavItems,
      openDropdown,
      openMobileGroup,
      userMenuOpen,
      mobileMenuOpen,
      searchFocused,
      searchQuery,
      onDropdownEnter,
      onDropdownLeave,
      toggleDropdown,
      closeDropdown,
      toggleMobileGroup,
      closeMobileMenu,
      getUserInitials,
      logout,
      isLibraryRoute,
      isComposerOrArranger, isScoresManager,
      goToCart,
    };
  },
};
</script>

<style scoped>
/* ── Navbar shell ─────────────────────────────────────────── */
.main-navbar {
  position: sticky;
  top: 0;
  z-index: 900;
  border: 1px solid rgba(234, 220, 194, 0.18);
  border-radius: 12px;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.14) 0%, transparent 46%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 20px 44px rgba(10, 10, 18, 0.28);
  backdrop-filter: blur(12px);
}

/* ── Inner row ────────────────────────────────────────────── */
.navbar-inner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1rem;
  min-height: 60px;
}

/* ── Left ─────────────────────────────────────────────────── */
.navbar-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-shrink: 0;
}

.nav-sidebar-toggle {
  display: none;
  width: 36px;
  height: 36px;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(234, 220, 194, 0.18);
  border-radius: 8px;
  color: rgba(234, 220, 194, 0.8);
  background: rgba(234, 220, 194, 0.06);
  font-size: 1.25rem;
  cursor: pointer;
  transition:
    background 0.2s,
    color 0.2s;
}
.nav-sidebar-toggle:hover {
  color: #fff;
  background: rgba(200, 164, 93, 0.16);
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  color: #fffdf8;
  white-space: nowrap;
}

.brand-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.7), rgba(200, 164, 93, 0.25));
  border: 1px solid rgba(200, 164, 93, 0.25);
  color: var(--gold-color, #c8a45d);
  font-size: 1rem;
  flex-shrink: 0;
}

.brand-name {
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--gold-color, #c8a45d);
  letter-spacing: 0.02em;
}

.brand-sep {
  color: rgba(234, 220, 194, 0.3);
  font-weight: 300;
}

.brand-sub {
  font-size: 0.78rem;
  color: rgba(234, 220, 194, 0.55);
  font-weight: 400;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 180px;
}

/* ── Center nav links ─────────────────────────────────────── */
.navbar-links {
  display: flex;
  align-items: center;
  gap: 0.15rem;
  list-style: none;
  margin: 0 auto;
  padding: 0;
  flex-wrap: nowrap;
}

.nav-item {
  position: relative;
}

.nav-link,
.nav-link-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.38rem;
  padding: 0.48rem 0.78rem;
  border: none;
  border-radius: 8px;
  color: rgba(219, 189, 113, 0.82);
  background: transparent;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  cursor: pointer;
  text-decoration: none;
  transition:
    background 0.18s,
    color 0.18s;
  position: relative;
}

.nav-link::after {
  content: "";
  position: absolute;
  bottom: 4px;
  left: 50%;
  transform: translateX(-50%) scaleX(0);
  width: calc(100% - 1.2rem);
  height: 2px;
  border-radius: 999px;
  background: var(--gold-color, #c8a45d);
  transition: transform 0.22s ease;
  transform-origin: center;
}

.nav-link:hover,
.nav-link-btn:hover {
  color: #fffdf8;
  background: rgba(234, 220, 194, 0.08);
}

.nav-link.router-link-active,
.nav-link.router-link-exact-active {
  color: #fffdf8 !important;
  background: rgba(127, 36, 50, 0.28);
  font-weight: 600;
}

.nav-link.router-link-active::after,
.nav-link.router-link-exact-active::after {
  transform: translateX(-50%) scaleX(1);
}

.nav-chevron {
  font-size: 0.65rem;
  transition: transform 0.22s ease;
  opacity: 0.65;
}

.has-dropdown.is-open .nav-chevron {
  transform: rotate(180deg);
}

/* ── Dropdown ─────────────────────────────────────────────── */
.nav-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  min-width: 210px;
  padding: 0.4rem;
  list-style: none;
  border: 1px solid rgba(234, 220, 194, 0.14);
  border-radius: 10px;
  background: #161b28;
  box-shadow: 0 20px 48px rgba(8, 8, 14, 0.5);
  opacity: 0;
  visibility: hidden;
  transform: translateY(-6px);
  transition:
    opacity 0.2s ease,
    transform 0.2s ease,
    visibility 0.2s;
  z-index: 1050;
}

.nav-dropdown-end {
  left: auto;
  right: 0;
}

.has-dropdown.is-open .nav-dropdown,
.nav-user.is-open .nav-dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-link {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  padding: 0.52rem 0.7rem;
  border: none;
  border-radius: 7px;
  color: rgba(234, 220, 194, 0.78);
  background: transparent;
  font-size: 0.855rem;
  text-decoration: none;
  cursor: pointer;
  transition:
    background 0.15s,
    color 0.15s;
}

.dropdown-link:hover {
  color: #fffdf8;
  background: rgba(234, 220, 194, 0.09);
}

.dropdown-link.router-link-active {
  color: var(--gold-color, #c8a45d);
  background: rgba(127, 36, 50, 0.22);
}

.dropdown-link-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background: rgba(234, 220, 194, 0.08);
  border: 1px solid rgba(234, 220, 194, 0.1);
  font-size: 0.8rem;
  flex-shrink: 0;
  color: var(--gold-color, #c8a45d);
}

.dropdown-header {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0.7rem 0.55rem;
}

.dropdown-header-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7f2432, #c8a45d);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
  overflow: hidden;
}

.dropdown-header-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.dropdown-header-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fffdf8;
}

.dropdown-header-role {
  font-size: 0.75rem;
  color: rgba(234, 220, 194, 0.52);
  text-transform: capitalize;
}

.dropdown-sep {
  height: 1px;
  background: rgba(234, 220, 194, 0.1);
  margin: 0.25rem 0.5rem;
}

/* ── Cart button ──────────────────────────────────────────── */
.nav-cart-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid rgba(234, 220, 194, 0.18);
  border-radius: 8px;
  color: rgba(219, 189, 113, 0.82);
  background: rgba(234, 220, 194, 0.06);
  font-size: 1.1rem;
  cursor: pointer;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.nav-cart-btn:hover {
  color: #fffdf8;
  background: rgba(200, 164, 93, 0.16);
  border-color: rgba(200, 164, 93, 0.3);
}
.nav-cart-badge {
  position: absolute;
  top: -4px;
  right: -6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  background: var(--gold-color, #c8a45d);
  color: #10131f;
  font-size: 0.6rem;
  font-weight: 800;
  line-height: 1;
  padding: 0 4px;
}

/* ── Right: search + user ─────────────────────────────────── */
.navbar-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.nav-search-wrap {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  width: 160px;
  padding: 0.3rem 0.65rem;
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 8px;
  background: rgba(234, 220, 194, 0.06);
  transition:
    width 0.28s ease,
    border-color 0.2s,
    background 0.2s;
}

.nav-search-wrap.is-focused {
  width: 220px;
  border-color: rgba(200, 164, 93, 0.38);
  background: rgba(234, 220, 194, 0.1);
}

.search-icon {
  color: rgba(234, 220, 194, 0.45);
  font-size: 0.8rem;
  flex-shrink: 0;
}

.nav-search-input {
  flex: 1;
  border: none;
  background: transparent;
  color: #fffdf8;
  font-size: 0.83rem;
  outline: none;
  min-width: 0;
}

.nav-search-input::placeholder {
  color: rgba(234, 220, 194, 0.4);
}

.nav-search-input::-webkit-search-cancel-button {
  display: none;
}

/* ── User button ──────────────────────────────────────────── */
.nav-user {
  position: relative;
}

.nav-user-btn {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.28rem 0.7rem 0.28rem 0.28rem;
  border: 1px solid rgba(234, 220, 194, 0.18);
  border-radius: 999px;
  color: rgba(234, 220, 194, 0.88);
  background: rgba(234, 220, 194, 0.07);
  font-size: 0.855rem;
  cursor: pointer;
  transition:
    background 0.18s,
    border-color 0.18s;
}

.nav-user-btn:hover {
  background: rgba(234, 220, 194, 0.13);
  border-color: rgba(200, 164, 93, 0.34);
  color: #fffdf8;
}

.user-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7f2432, #c8a45d);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
  overflow: hidden;
}

.user-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.user-name {
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.83rem;
}

/* ── Mobile toggle ────────────────────────────────────────── */
.nav-mobile-toggle {
  display: none;
  width: 36px;
  height: 36px;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(234, 220, 194, 0.18);
  border-radius: 8px;
  color: rgba(234, 220, 194, 0.8);
  background: rgba(234, 220, 194, 0.06);
  font-size: 1.1rem;
  cursor: pointer;
  transition:
    background 0.2s,
    color 0.2s;
}

.nav-mobile-toggle:hover {
  color: #fff;
  background: rgba(200, 164, 93, 0.16);
}

/* ── Mobile menu ──────────────────────────────────────────── */
.navbar-mobile-menu {
  display: none;
  flex-direction: column;
  gap: 0.15rem;
  overflow: hidden;
  max-height: 0;
  padding: 0 0.75rem;
  transition:
    max-height 0.32s ease,
    padding 0.32s ease;
  border-top: 1px solid rgba(234, 220, 194, 0.1);
}

.navbar-mobile-menu.is-open {
  max-height: 600px;
  padding: 0.65rem 0.75rem 0.85rem;
}

.mobile-nav-link,
.mobile-nav-group-header {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  width: 100%;
  padding: 0.62rem 0.75rem;
  border: none;
  border-radius: 8px;
  color: rgba(219, 189, 113, 0.85);
  background: transparent;
  font-size: 0.9rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition:
    background 0.18s,
    color 0.18s;
}

.mobile-nav-link:hover,
.mobile-nav-group-header:hover {
  color: #fffdf8;
  background: rgba(234, 220, 194, 0.08);
}

.mobile-nav-link.router-link-active,
.mobile-nav-link.router-link-exact-active {
  color: var(--gold-color, #c8a45d);
  background: rgba(127, 36, 50, 0.24);
  font-weight: 600;
}

.mobile-nav-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 7px;
  background: rgba(234, 220, 194, 0.08);
  border: 1px solid rgba(234, 220, 194, 0.1);
  font-size: 0.85rem;
  flex-shrink: 0;
  color: var(--gold-color, #c8a45d);
}

.mobile-nav-icon.sm {
  width: 24px;
  height: 24px;
  font-size: 0.75rem;
  border-radius: 5px;
}

.mobile-chevron {
  margin-left: auto;
  font-size: 0.7rem;
  transition: transform 0.22s ease;
  opacity: 0.6;
}

.mobile-chevron.rotated {
  transform: rotate(180deg);
}

.mobile-nav-group-header {
  justify-content: flex-start;
}

.mobile-nav-children {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  overflow: hidden;
  max-height: 0;
  padding-left: 1rem;
  transition: max-height 0.28s ease;
}

.mobile-nav-children.is-open {
  max-height: 400px;
}

.mobile-nav-child {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.65rem;
  border-radius: 7px;
  color: rgba(219, 189, 113, 0.75);
  font-size: 0.855rem;
  text-decoration: none;
  transition:
    background 0.15s,
    color 0.15s;
}

.mobile-nav-child:hover {
  color: #fffdf8;
  background: rgba(234, 220, 194, 0.07);
}

.mobile-nav-child.router-link-active {
  color: var(--gold-color, #c8a45d);
}

/* ── Overflow nav row (desktop items 6+) ─────────────────── */
.navbar-overflow-row {
  border-top: 1px solid rgba(234, 220, 194, 0.1);
  padding: 0.3rem 0.85rem 0.38rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.navbar-overflow-links {
  display: flex;
  align-items: center;
  gap: 0.1rem;
  list-style: none;
  margin: 0;
  padding: 0;
  flex-wrap: wrap;
}

/* Slightly more compact variant for overflow row */
.nav-link-sm {
  padding: 0.35rem 0.65rem !important;
  font-size: 0.835rem !important;
}

/* ── Responsive breakpoints ───────────────────────────────── */
@media (max-width: 991.98px) {
  .navbar-links {
    display: none;
  }
  .navbar-overflow-row {
    display: none;
  }
  .nav-mobile-toggle {
    display: flex;
  }
  .navbar-mobile-menu {
    display: flex;
  }
  .brand-sub {
    display: none;
  }
  .brand-sep {
    display: none;
  }
  .nav-search-wrap {
    width: 130px;
  }
  .nav-search-wrap.is-focused {
    width: 170px;
  }
}

@media (max-width: 767.98px) {
  .nav-sidebar-toggle {
    display: flex;
  }
  .nav-search-wrap {
    display: none;
  }
  .user-name {
    display: none;
  }
}

@media (max-width: 575.98px) {
  .navbar-inner {
    padding: 0.5rem 0.65rem;
  }
  .brand-name {
    font-size: 0.85rem;
  }
}
</style>
