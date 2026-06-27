<template>
    <nav class="navbar navbar-expand-xl main-navbar">
        <div class="container-fluid">
            <div class="navbar-brand text-white d-flex align-items-center">
                <i :class="'bi ' + navigationStore.activeProgramInfo.icon + ' me-2'"></i>
                <span>{{ navigationStore.activeProgramInfo.name }}</span>
            </div>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Toggle section navigation"
            >
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
                        <input type="text" class="form-control bg-transparent border-0 text-white"
                            placeholder="Search" aria-label="Search">
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle nav-user-btn" data-bs-toggle="dropdown" type="button" aria-label="User menu">
                            <i class="bi bi-person-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
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

export default {
    name: 'AppNavbar',
    setup() {
        const navigationStore = useNavigationStore()

        return {
            navigationStore
        }
    }
}
</script>
