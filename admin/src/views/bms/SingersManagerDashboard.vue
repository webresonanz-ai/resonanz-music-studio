<script setup>
import { onMounted, ref, computed } from 'vue'
import { useBmsStore } from '../../stores/api'

const bmsStore = useBmsStore()

const loading = ref(true)
const error = ref('')

const stats = computed(() => [
  { label: 'Members', value: bmsStore.members.length, icon: 'bi-people-fill', href: '/bms/attendance' },
  { label: 'Events', value: bmsStore.events.length, icon: 'bi-calendar2-week', href: '/bms/attendance' },
  { label: 'Attendance Concerts', value: bmsStore.attendanceConcerts.length, icon: 'bi-music-note-beamed', href: '/bms/attendance' },
])

onMounted(async () => {
  try {
    await Promise.all([
      bmsStore.fetchMembers(),
      bmsStore.fetchEvents().catch(() => {}),
      bmsStore.fetchAttendanceConcerts(),
    ])
  } catch (err) {
    error.value = err.message || 'Failed to load dashboard data'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="mb-4">
      <h1 class="h3 mb-1">Singers Manager Dashboard</h1>
      <p class="text-white-50 mb-0 small">Band & singers management overview</p>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>

    <div v-if="loading" class="d-flex justify-content-center py-5">
      <div class="spinner-border text-warning" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else class="row g-3">
      <div v-for="stat in stats" :key="stat.label" class="col-12 col-sm-6 col-lg-4">
        <router-link :to="stat.href" class="content-card text-decoration-none d-flex align-items-center gap-3 h-100">
          <div class="stat-icon">
            <i :class="'bi ' + stat.icon"></i>
          </div>
          <div>
            <div class="stat-value display-4 fw-bold">{{ stat.value }}</div>
            <div class="text-uppercase small text-muted">{{ stat.label }}</div>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stat-icon {
  display: grid;
  width: 56px;
  height: 56px;
  flex: 0 0 56px;
  place-items: center;
  border-radius: 14px;
  border: 1px solid rgba(200, 164, 93, 0.35);
  background: linear-gradient(180deg, rgba(200, 164, 93, 0.2), rgba(200, 164, 93, 0.05));
  color: var(--gold-color);
  font-size: 1.6rem;
}

.stat-value {
  font-size: 2.4rem !important;
  line-height: 1;
  color: var(--ink-color);
}
</style>