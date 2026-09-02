<script setup>
import { onMounted, ref, computed } from 'vue'
import { useBmsStore } from '../../stores/api'

defineOptions({ name: 'BmsAttendance' })

const bmsStore = useBmsStore()

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')
const selectedConcertId = ref('')

const concerts = computed(() => bmsStore.attendanceConcerts)
const detail = computed(() => bmsStore.attendanceDetail)

async function loadConcerts() {
  loading.value = true
  error.value = ''
  try {
    const result = await bmsStore.fetchAttendanceConcerts()
    const list = result.concerts || result.all_concerts || []
    if (list.length && !selectedConcertId.value) {
      selectedConcertId.value = String(list[0].id)
      await loadDetail(selectedConcertId.value)
    }
  } catch (err) {
    error.value = err.message || 'Failed to load concerts'
  } finally {
    loading.value = false
  }
}

async function loadDetail(concertId) {
  loading.value = true
  error.value = ''
  try {
    await bmsStore.fetchAttendanceDetail(concertId)
  } catch (err) {
    error.value = err.message || 'Failed to load attendance'
  } finally {
    loading.value = false
  }
}

function onSelectConcert(event) {
  selectedConcertId.value = event.target.value
  loadDetail(selectedConcertId.value)
}

async function toggleAttendance(memberId, action) {
  saving.value = true
  error.value = ''
  try {
    await bmsStore.updateConcertRoster(detail.value?.concert_schedule_id, memberId, action)
    success.value = action === 'present' ? 'Marked present.' : 'Marked absent.'
    setTimeout(() => (success.value = ''), 2000)
  } catch (err) {
    error.value = err.message || 'Update failed'
  } finally {
    saving.value = false
  }
}

onMounted(loadConcerts)
</script>

<template>
  <div>
    <div class="mb-4">
      <h1 class="h3 mb-1">BMS Attendance</h1>
      <p class="text-white-50 mb-0 small">Concert roster & attendance tracking</p>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
    <div v-if="success" class="alert alert-success py-2" role="alert">
      <i class="bi bi-check-circle me-1"></i>{{ success }}
    </div>

    <div v-if="loading" class="d-flex justify-content-center py-5">
      <div class="spinner-border text-warning" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <template v-else>
      <div class="content-card mb-3">
        <label for="concert-select" class="form-label small text-uppercase text-white-50">Concert</label>
        <select
          id="concert-select"
          class="form-select bg-dark text-white border-secondary"
          style="max-width: 480px"
          :value="selectedConcertId"
          @change="onSelectConcert"
        >
          <option v-for="c in concerts" :key="c.id" :value="c.id">
            {{ c.title }}
          </option>
        </select>
      </div>

      <div class="content-card">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <h2 class="h5 mb-0">{{ detail?.title || 'Roster' }}</h2>
          <span v-if="detail" class="badge text-bg-warning">
            {{ (detail.present_count ?? '–') }} / {{ detail.roster?.length ?? '–' }} present
          </span>
        </div>

        <div v-if="!detail" class="text-center text-white-50 py-4">
          Select a concert to view its roster.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Member</th>
                <th>Voice</th>
                <th class="text-end">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="detail.roster?.length === 0">
                <td colspan="3" class="text-center text-white-50 py-4">No members in this roster.</td>
              </tr>
              <tr v-for="row in detail.roster" :key="row.member_id">
                <td class="fw-semibold">{{ row.member_name }}</td>
                <td>{{ row.voice || '—' }}</td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <button
                      type="button"
                      class="btn btn-outline-success"
                      :class="{ active: row.status === 'present' }"
                      :disabled="saving"
                      @click="toggleAttendance(row.member_id, 'present')"
                    >
                      <i class="bi bi-check-lg me-1"></i>Present
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger"
                      :class="{ active: row.status === 'absent' }"
                      :disabled="saving"
                      @click="toggleAttendance(row.member_id, 'absent')"
                    >
                      <i class="bi bi-x-lg me-1"></i>Absent
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>