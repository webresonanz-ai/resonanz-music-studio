<template>
    <nav class="navbar navbar-expand-xl main-navbar">
        <div class="container-fluid">
            <div class="navbar-brand text-white d-flex align-items-center">
                <i :class="'bi ' + navigationStore.activeProgramInfo.icon + ' me-2'"></i>
                <span>{{ navigationStore.activeProgramInfo.name }}</span>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle section navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-xl-0">
                    <template v-for="item in filteredNavItems" :key="item.label">
                        <!-- Dropdown for items with children -->
                        <li
                            v-if="item.children"
                            class="nav-item dropdown"
                            :class="{ show: openDropdown === item.label }"
                            @mouseenter="onDropdownEnter(item.label)"
                            @mouseleave="onDropdownLeave"
                        >
                            <a
                                class="nav-link dropdown-toggle"
                                href="#"
                                role="button"
                                :aria-expanded="openDropdown === item.label"
                                @click.prevent="toggleDropdown(item.label)"
                            >
                                <i :class="'bi ' + item.icon"></i>
                                {{ item.label }}
                            </a>
                            <ul
                                class="dropdown-menu dropdown-menu-dark"
                                :class="{ show: openDropdown === item.label }"
                            >
                                <li v-for="child in item.children" :key="child.path">
                                    <router-link
                                        :to="child.path"
                                        class="dropdown-item"
                                        @click="closeDropdown"
                                    >
                                        <i :class="'bi ' + child.icon + ' me-2'"></i>
                                        {{ child.label }}
                                    </router-link>
                                </li>
                            </ul>
                        </li>
                        <!-- Regular nav item -->
                        <li v-else class="nav-item">
                            <router-link :to="item.path" class="nav-link">
                                <i :class="'bi ' + item.icon"></i>
                                {{ item.label }}
                            </router-link>
                        </li>
                    </template>
                </ul>

                <!-- Search and User -->
                <div class="navbar-tools d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm nav-search">
                        <span class="input-group-text bg-transparent border-0 text-white-50">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control bg-transparent border-0 text-white" placeholder="Search"
                            aria-label="Search">
                    </div>

                    <div class="dropdown" :class="{ show: userMenuOpen }">
                        <button
                            class="btn btn-outline-light btn-sm dropdown-toggle nav-user-btn"
                            type="button"
                            aria-label="User menu"
                            :aria-expanded="userMenuOpen"
                            @click="userMenuOpen = !userMenuOpen"
                        >
                            <i class="bi bi-person-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" :class="{ show: userMenuOpen }">
                            <li v-if="authStore.user" class="dropdown-item-text text-white-50 px-3 py-2">
                                <div class="fw-semibold">{{ authStore.user.name }}</div>
                                <div class="small">{{ authStore.user.role }}</div>
                            </li>
                            <li v-if="authStore.user">
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <button class="dropdown-item" type="button"
                                    @click="authStore.user ? logout() : router.push('/auth'); userMenuOpen = false">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    {{ authStore.user ? 'Logout' : 'Login' }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useNavigationStore } from '../stores/navigation'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

export default {
    name: 'AppNavbar',
    setup() {
        const navigationStore = useNavigationStore()
        const authStore = useAuthStore()
        const router = useRouter()

        // Returns true if the current user is allowed to see an item
        const canSee = (item) => {
            if (!item.roles) return true
            const role = authStore.user?.role?.toLowerCase()
            return !!role && item.roles.includes(role)
        }

        // Filtered nav items — strips restricted items and collapses empty parent dropdowns
        const filteredNavItems = computed(() => {
            return navigationStore.currentNavItems
                .map(item => {
                    if (!item.children) return canSee(item) ? item : null
                    const visibleChildren = item.children.filter(canSee)
                    if (!visibleChildren.length) return null
                    return { ...item, children: visibleChildren }
                })
                .filter(Boolean)
        })

        // Track which nav dropdown is open (by label)
        const openDropdown = ref(null)
        const userMenuOpen = ref(false)
        let leaveTimer = null

        const isDesktop = () => window.innerWidth >= 1200 // xl breakpoint

        const onDropdownEnter = (label) => {
            if (!isDesktop()) return
            clearTimeout(leaveTimer)
            openDropdown.value = label
        }

        const onDropdownLeave = () => {
            if (!isDesktop()) return
            leaveTimer = setTimeout(() => {
                openDropdown.value = null
            }, 100)
        }

        const toggleDropdown = (label) => {
            openDropdown.value = openDropdown.value === label ? null : label
        }

        const closeDropdown = () => {
            openDropdown.value = null
        }

        const logout = () => {
            authStore.logout()
            router.push('/auth')
        }

        // Close dropdowns when clicking outside
        const handleOutsideClick = (e) => {
            if (!e.target.closest('.nav-item.dropdown') && !e.target.closest('.navbar-tools .dropdown')) {
                openDropdown.value = null
                userMenuOpen.value = false
            }
        }

        onMounted(() => document.addEventListener('click', handleOutsideClick))
        onUnmounted(() => {
            document.removeEventListener('click', handleOutsideClick)
            clearTimeout(leaveTimer)
        })

        return {
            navigationStore,
            authStore,
            logout,
            router,
            filteredNavItems,
            openDropdown,
            userMenuOpen,
            onDropdownEnter,
            onDropdownLeave,
            toggleDropdown,
            closeDropdown,
        }
    }
}
</script>
