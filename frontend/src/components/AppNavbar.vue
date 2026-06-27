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
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <template v-for="item in navigationStore.currentNavItems" :key="item.label">
                        <!-- Dropdown for items with children -->
                        <li v-if="item.children" class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i :class="'bi ' + item.icon"></i>
                                {{ item.label }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li v-for="child in item.children" :key="child.path">
                                    <router-link :to="child.path" class="dropdown-item">
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

                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle nav-user-btn"
                            data-bs-toggle="dropdown" type="button" aria-label="User menu">
                            <i class="bi bi-person-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            <li v-if="authStore.user" class="dropdown-item-text text-white-50 px-3 py-2">
                                <div class="fw-semibold">{{ authStore.user.name }}</div>
                                <div class="small">{{ authStore.user.role }}</div>
                            </li>
                            <li v-if="authStore.user">
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <button class="dropdown-item" type="button"
                                    @click="authStore.user ? logout() : router.push('/auth')">
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
import { useNavigationStore } from '../stores/navigation'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

export default {
    name: 'AppNavbar',
    setup() {
        const navigationStore = useNavigationStore()
        const authStore = useAuthStore()
        const router = useRouter()

        const logout = () => {
            authStore.logout()
            router.push('/auth')
        }

        return {
            navigationStore,
            authStore,
            logout,
            router
        }
    }
}
</script>
