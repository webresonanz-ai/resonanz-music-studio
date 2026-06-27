<template>
    <aside class="sidebar" :class="{ open: sidebarOpen }" aria-label="Program navigation">
        <div class="sidebar-header">
            <h2><i class="bi bi-soundwave me-2"></i>Resonanz</h2>
            <p>Music Foundation</p>
        </div>

        <div class="sidebar-menu">
            <button
                v-for="item in navigationStore.sidebarItems"
                :key="item.id"
                class="sidebar-item"
                :class="{ active: navigationStore.activeProgram === item.id }"
                type="button"
                @click="changeProgram(item.id)"
            >
                <i :class="'bi ' + item.icon"></i>
                <span class="sidebar-item-content">
                    <span class="sidebar-item-title">{{ item.name }}</span>
                    <span class="sidebar-item-desc">{{ item.description }}</span>
                </span>
                <i class="bi bi-chevron-right ms-auto"></i>
            </button>
        </div>

        <div class="sidebar-footer p-3 mt-auto">
            <div class="text-white-50 small text-center">
                <p class="mb-1">&copy; 2026 Resonanz Music Foundation</p>
                <p class="mb-0">Empowering Through Music</p>
            </div>
        </div>
    </aside>
</template>

<script>
import { useNavigationStore } from '../stores/navigation'
import { useRouter } from 'vue-router'

export default {
    name: 'AppSidebar',
    emits: ['navigate'],
    props: {
        sidebarOpen: {
            type: Boolean,
            default: false
        }
    },
    setup(_, { emit }) {
        const navigationStore = useNavigationStore()
        const router = useRouter()

        const changeProgram = (programId) => {
            navigationStore.setActiveProgram(programId)
            router.push(`/${programId}/home`)
            emit('navigate')
        }

        return {
            navigationStore,
            changeProgram
        }
    }
}
</script>
