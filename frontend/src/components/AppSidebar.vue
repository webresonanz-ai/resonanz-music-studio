<template>
  <aside class="sidebar" :class="{ open: sidebarOpen }" aria-label="Program navigation">
    <div class="sidebar-header">
      <img src="/logo_resonanz_bgwhite.webp" alt="Resonanz Logo" class="sidebar-logo" width="128" height="128" />
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
        <img
          v-if="item.img"
          :src="item.img"
          style="width: auto; height: 3rem"
          alt=""
          class="sidebar-icon"
        />
        <i
          v-else
          :class="'bi ' + item.icon + ' sidebar-icon'"
          style="
            display: flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: 3rem;
            font-size: 2rem;
            color: var(--gold-color);
          "
        ></i>
        <span class="sidebar-item-content">
          <span class="sidebar-item-title">{{ item.name }}</span>
          <span class="sidebar-item-desc">{{ item.description }}</span>
        </span>
        <i class="bi bi-chevron-right ms-auto"></i>
      </button>
    </div>

    <div class="sidebar-footer p-3 mt-auto">
      <div class="text-white-50 small text-center">
        <p class="mb-1">&copy; 2026 The Resonanz Music Studio</p>
        <p class="mb-0">Empowering Through Music</p>
      </div>
    </div>
  </aside>
</template>

<script>
import { useNavigationStore } from "../stores/navigation";
import { useRouter } from "vue-router";

export default {
  name: "AppSidebar",
  emits: ["navigate"],
  props: {
    sidebarOpen: {
      type: Boolean,
      default: false,
    },
  },
  setup(_, { emit }) {
    const navigationStore = useNavigationStore();
    const router = useRouter();

    const changeProgram = (programId) => {
      navigationStore.setActiveProgram(programId);
      if (programId === "art-director") {
        navigationStore.setStandalonePage("art-director");
        emit("navigate");
        return;
      }
      router.push(`/${programId}/home`);
      emit("navigate");
    };

    return {
      navigationStore,
      changeProgram,
    };
  },
};
</script>

<style scoped>
.sidebar-logo {
  max-width: 8rem;
  height: auto;
  border-radius: 10px;
}
</style>
