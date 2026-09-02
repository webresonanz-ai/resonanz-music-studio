<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useApiStore, useTrmsStore } from '../../stores/api'

const trmsStore = useTrmsStore()

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const page = ref(1)
const perPage = ref(10)
const search = ref('')
const searchPending = ref(false)
const debounceTimer = ref(null)
const filterConcert = ref('')
const filterNotes = ref('')
const filterAttended = ref('')
const concertOptions = ref([])

const editModal = ref({
  visible: false,
  loading: false,
  error: '',
  audience: null,
})
const editForm = ref({
  name: '',
  email: '',
  phone: '',
  concert_title: '',
  ticket_quantity: 1,
  notes: '',
})
const deleteModal = ref({
  visible: false,
  loading: false,
  audience: null,
})
const emailModal = ref({
  visible: false,
  loading: false,
  audience: null,
})
const attendModal = ref({
  visible: false,
  loading: false,
  audience: null,
})

const audiences = computed(() => trmsStore.concertAudiences)
const total = computed(() => trmsStore.concertAudiencesMeta.total)
const currentPage = computed(() => trmsStore.concertAudiencesMeta.currentPage)
const lastPage = computed(() => trmsStore.concertAudiencesMeta.lastPage)

watch(search, () => {
  searchPending.value = true
  clearTimeout(debounceTimer.value)
  debounceTimer.value = setTimeout(() => {
    searchPending.value = false
    page.value = 1
    fetchAudiences()
  }, 1500)
})

onMounted(() => {
  fetchConcertOptions()
  fetchAudiences()
})

onBeforeUnmount(() => {
  clearTimeout(debounceTimer.value)
  setBodyScroll(false)
})

async function fetchAudiences() {
  loading.value = true
  errorMessage.value = ''
  try {
    await trmsStore.fetchConcertAudiences({
      page: page.value,
      perPage: perPage.value,
      search: search.value,
      concert: filterConcert.value,
      notes: filterNotes.value,
      attended: filterAttended.value,
    })
  } catch (error) {
    errorMessage.value = error.message || 'Unable to load audiences.'
  } finally {
    loading.value = false
  }
}

async function fetchConcertOptions() {
  try {
    concertOptions.value = await trmsStore.fetchConcertAudienceConcerts()
  } catch {
    // non-critical — filter will just be empty
  }
}

function onFilterChange() {
  page.value = 1
  fetchAudiences()
}

function formatDate(value) {
  if (!value) return '-'
  return new Intl.DateTimeFormat('en', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function goToPage(targetPage) {
  if (targetPage < 1 || targetPage > lastPage.value || loading.value) return
  page.value = targetPage
  fetchAudiences()
}

function onPerPageChange() {
  page.value = 1
  fetchAudiences()
}

function showSuccess(msg) {
  successMessage.value = msg
  setTimeout(() => {
    successMessage.value = ''
  }, 4000)
}

function setBodyScroll(locked) {
  document.body.style.overflow = locked ? 'hidden' : ''
}

async function downloadTicketPdf(id) {
  try {
    const blob = await useApiStore().fetchBlob(`/trms/concert/ticket/${id}`)
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `ticket_${id}.pdf`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  } catch (error) {
    errorMessage.value = error.message || 'Failed to download ticket PDF.'
  }
}

function openAttendModal(audience) {
  attendModal.value.audience = audience
  attendModal.value.loading = false
  attendModal.value.visible = true
}

function closeAttendModal() {
  attendModal.value.visible = false
  attendModal.value.audience = null
}

async function executeAttend() {
  attendModal.value.loading = true
  try {
    const result = await trmsStore.scanConcertRegistration({
      reg_number: attendModal.value.audience.id,
    })
    if (result?.data) {
      const idx = trmsStore.concertAudiences.findIndex(
        a => a.id === attendModal.value.audience.id
      )
      if (idx !== -1) {
        trmsStore.concertAudiences[idx] = result.data
      }
    }
    showSuccess(`${attendModal.value.audience.name} marked as attended.`)
    closeAttendModal()
  } catch (error) {
    errorMessage.value = error.message || 'Failed to mark as attended.'
  } finally {
    attendModal.value.loading = false
  }
}

function openEditModal(audience) {
  editModal.value.audience = audience
  editModal.value.error = ''
  editModal.value.loading = false
  editForm.value = {
    name: audience.name || '',
    email: audience.email || '',
    phone: audience.phone || '',
    concert_title: audience.concert_title || '',
    ticket_quantity: audience.ticket_quantity || 1,
    notes: audience.notes || '',
  }
  editModal.value.visible = true
  setBodyScroll(true)
}

function closeEditModal() {
  editModal.value.visible = false
  editModal.value.audience = null
  setBodyScroll(false)
}

async function submitEdit() {
  editModal.value.loading = true
  editModal.value.error = ''
  try {
    await trmsStore.updateConcertAudience(editModal.value.audience.id, editForm.value)
    showSuccess('Registration updated successfully.')
    closeEditModal()
  } catch (error) {
    editModal.value.error = error.message || 'Failed to update registration.'
  } finally {
    editModal.value.loading = false
  }
}

function confirmDelete(audience) {
  deleteModal.value.audience = audience
  deleteModal.value.loading = false
  deleteModal.value.visible = true
  setBodyScroll(true)
}

function closeDeleteModal() {
  deleteModal.value.visible = false
  deleteModal.value.audience = null
  setBodyScroll(false)
}

async function executeDelete() {
  deleteModal.value.loading = true
  try {
    await trmsStore.deleteConcertAudience(deleteModal.value.audience.id)
    showSuccess('Registration deleted successfully.')
    closeDeleteModal()
    if (audiences.value.length === 0 && page.value > 1) {
      page.value -= 1
      fetchAudiences()
    }
  } catch (error) {
    errorMessage.value = error.message || 'Failed to delete registration.'
    closeDeleteModal()
  }
}

function confirmResendEmail(audience) {
  emailModal.value.audience = audience
  emailModal.value.loading = false
  emailModal.value.visible = true
  setBodyScroll(true)
}

function closeEmailModal() {
  emailModal.value.visible = false
  emailModal.value.audience = null
  setBodyScroll(false)
}

async function executeResendEmail() {
  emailModal.value.loading = true
  try {
    await trmsStore.resendConcertEmail(emailModal.value.audience.id)
    showSuccess(`Ticket email resent to ${emailModal.value.audience.email}.`)
    closeEmailModal()
    await fetchAudiences()
  } catch (error) {
    errorMessage.value = error.message || 'Failed to resend email.'
    closeEmailModal()
  } finally {
    emailModal.value.loading = false
  }
}
</script>

<template>
  <div>
    <div class="fade-in-up">
      <div class="content-card bg-dark mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div>
            <p class="text-uppercase text-warning fw-bold small mb-2">TRMS Concert</p>
            <h1 class="h3 fw-bold mb-1 text-champagne">Registered Audiences</h1>
            <p class="text-champagne-muted mb-0">
              Review audience registrations that have been submitted for the concert.
            </p>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <router-link class="btn btn-gold" to="/trms/concert/invitation-reg">
              <i class="bi bi-person-plus me-2"></i>
              Register Audience
            </router-link>
            <router-link class="btn btn-outline-gold" to="/trms/concert/scan">
              <i class="bi bi-qr-code-scan me-2"></i>
              Scan Check-in
            </router-link>
          </div>
        </div>
      </div>

      <div class="content-card bg-dark">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
          <div>
            <h2 class="h5 fw-bold mb-1 text-champagne">Audience List</h2>
            <p class="text-champagne-muted mb-0">
              {{ total }} registration{{ total === 1 ? '' : 's' }}
            </p>
          </div>

          <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="position-relative">
              <i
                class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2 text-champagne-muted"
              ></i>
              <input
                v-model="search"
                type="text"
                class="form-control form-control-dark ps-4 tbl-search-input"
                placeholder="Search by name or email..."
              />
              <span
                v-if="searchPending"
                class="position-absolute top-50 end-0 translate-middle-y me-2"
                title="Searching..."
              >
                <span
                  class="spinner-border spinner-border-sm text-warning"
                  aria-hidden="true"
                ></span>
              </span>
            </div>

            <select
              v-model="filterConcert"
              class="form-select form-select-dark form-select-sm"
              style="width: auto; max-width: 200px"
              :disabled="loading"
              aria-label="Filter by concert"
              @change="onFilterChange"
            >
              <option value="">All Concerts</option>
              <option v-for="c in concertOptions" :key="c" :value="c">{{ c }}</option>
            </select>

            <select
              v-model="filterNotes"
              class="form-select form-select-dark form-select-sm"
              style="width: auto"
              :disabled="loading"
              aria-label="Filter by type"
              @change="onFilterChange"
            >
              <option value="">All Types</option>
              <option value="Guest">Guest</option>
              <option value="Invitation">Invitation</option>
            </select>

            <select
              v-model="filterAttended"
              class="form-select form-select-dark form-select-sm"
              style="width: auto"
              :disabled="loading"
              aria-label="Filter by attendance"
              @change="onFilterChange"
            >
              <option value="">All Attendance</option>
              <option value="attend">Attend</option>
              <option value="not_attend">Not Attend</option>
            </select>

            <div class="d-flex align-items-center gap-2">
              <label for="per-page-select" class="text-champagne-muted small mb-0 text-nowrap"
                >Rows per page:</label
              >
              <select
                id="per-page-select"
                v-model.number="perPage"
                class="form-select form-select-dark form-select-sm"
                style="width: auto"
                :disabled="loading"
                @change="onPerPageChange"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>
            <button
              class="btn btn-outline-gold"
              type="button"
              :disabled="loading"
              @click="fetchAudiences"
            >
              <span
                v-if="loading"
                class="spinner-border spinner-border-sm me-2"
                aria-hidden="true"
              ></span>
              <i v-else class="bi bi-arrow-clockwise me-2"></i>
              Refresh
            </button>
          </div>
        </div>

        <div
          v-if="errorMessage"
          class="alert alert-danger d-flex align-items-center gap-2"
          role="alert"
        >
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ errorMessage }}</span>
        </div>

        <div
          v-if="successMessage"
          class="alert alert-success d-flex align-items-center gap-2"
          role="alert"
        >
          <i class="bi bi-check-circle-fill"></i>
          <span>{{ successMessage }}</span>
        </div>

        <div v-if="loading" class="py-5 text-center">
          <div class="spinner-border text-warning mb-3" role="status"></div>
          <div class="text-champagne-muted">Loading audiences...</div>
        </div>

        <div v-else-if="audiences.length" class="table-responsive">
          <table class="table tbl align-middle mb-0">
            <thead>
              <tr>
                <th scope="col" class="text-center" style="width: 40px"></th>
                <th scope="col">Name</th>
                <th scope="col">Concert Title</th>
                <th scope="col" class="text-center">Seat</th>
                <th scope="col" class="text-center">Attended</th>
                <th scope="col">Notes</th>
                <th scope="col" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="audience in audiences" :key="audience.id" class="tbl-row">
                <td class="text-center" data-label="">
                  <button
                    v-if="!audience.attended_at"
                    type="button"
                    class="tbl-action-btn tbl-action-btn--check"
                    title="Mark as attended"
                    @click="openAttendModal(audience)"
                  >
                    <i class="bi bi-check-circle"></i>
                  </button>
                  <span v-else class="tbl-checked-icon">
                    <i class="bi bi-check-circle-fill text-success"></i>
                  </span>
                </td>
                <td data-label="Name">
                  <span class="tbl-name">{{ audience.name }}</span>
                </td>
                <td class="tbl-cell" data-label="Concert">{{ audience.concert_title }}</td>
                <td class="text-center" data-label="Seat">
                  <span v-if="audience.seat_number" class="tbl-seat">{{ audience.seat_number }}</span>
                  <span v-else class="tbl-cell text-center" style="opacity: 0.3">—</span>
                </td>
                <td class="text-center" data-label="Attended">
                  <span
                    v-if="audience.attended_at"
                    class="tbl-badge tbl-badge-checked"
                    :title="'Checked in ' + formatDate(audience.attended_at)"
                  >
                    <i class="bi bi-check-lg me-1"></i>{{ formatDate(audience.attended_at) }}
                  </span>
                  <span v-else class="tbl-badge tbl-badge-pending">
                    <i class="bi bi-clock me-1"></i>Pending
                  </span>
                </td>
                <td class="tbl-cell" data-label="Notes">{{ audience.notes || '—' }}</td>
                <td class="text-center" data-label="Actions">
                  <div class="tbl-actions">
                    <button
                      type="button"
                      class="tbl-action-btn tbl-action-btn--pdf"
                      title="Download Ticket PDF"
                      @click="downloadTicketPdf(audience.id)"
                    >
                      <i class="bi bi-file-earmark-pdf"></i>
                    </button>
                    <button
                      type="button"
                      class="tbl-action-btn tbl-action-btn--email"
                      :disabled="audience.send_email_status === 'sent'"
                      :title="
                        audience.send_email_status === 'sent' ? 'Email already sent' : 'Send Email'
                      "
                      @click="confirmResendEmail(audience)"
                    >
                      <i class="bi bi-envelope"></i>
                    </button>
                    <button
                      type="button"
                      class="tbl-action-btn tbl-action-btn--edit"
                      title="Edit"
                      @click="openEditModal(audience)"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      type="button"
                      class="tbl-action-btn tbl-action-btn--delete"
                      title="Delete"
                      @click="confirmDelete(audience)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <nav v-if="lastPage > 1" class="tbl-pagination" aria-label="Audience pagination">
            <div class="tbl-pagination-info">
              Showing {{ (currentPage - 1) * perPage + 1 }} –
              {{ Math.min(currentPage * perPage, total) }} of {{ total }}
            </div>
            <div class="tbl-pagination-btns">
              <button
                class="tbl-page-btn"
                type="button"
                :disabled="currentPage <= 1 || loading"
                @click="goToPage(currentPage - 1)"
              >
                <i class="bi bi-chevron-left"></i>
              </button>
              <span class="tbl-page-indicator">{{ currentPage }} / {{ lastPage }}</span>
              <button
                class="tbl-page-btn"
                type="button"
                :disabled="currentPage >= lastPage || loading"
                @click="goToPage(currentPage + 1)"
              >
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          </nav>
        </div>

        <div v-else class="tbl-empty">
          <div class="tbl-empty-icon">
            <i class="bi bi-ticket-perforated"></i>
          </div>
          <h3 class="tbl-empty-title">
            {{ search ? 'No matching audiences' : 'No audiences yet' }}
          </h3>
          <p class="tbl-empty-text">
            {{
              search
                ? 'Try adjusting your search keyword or filters.'
                : 'Submitted concert registrations will appear here.'
            }}
          </p>
          <router-link v-if="!search" class="btn btn-gold" to="/trms/concert/invitation-reg">
            <i class="bi bi-person-plus me-2"></i>
            Register First Audience
          </router-link>
        </div>
      </div>
    </div>

    <!-- ── Edit Modal ──────────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="editModal.visible"
          class="modal fade show d-block"
          tabindex="-1"
          role="dialog"
          aria-modal="true"
          aria-labelledby="editModalLabel"
          @click.self="closeEditModal"
        >
          <div
            class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"
            role="document"
          >
            <div class="modal-content modal-content-dark">
              <div class="modal-header modal-header-dark">
                <h5 class="modal-title fw-bold text-warning" id="editModalLabel">
                  <i class="bi bi-pencil-square me-2"></i>
                  Edit Audience
                </h5>
                <button
                  type="button"
                  class="btn-close btn-close-white"
                  aria-label="Close"
                  @click="closeEditModal"
                ></button>
              </div>
              <form @submit.prevent="submitEdit">
                <div class="modal-body">
                  <div v-if="editModal.error" class="alert alert-danger mb-3">
                    {{ editModal.error }}
                  </div>

                  <div class="row g-3">
                    <div class="col-12 col-sm-6">
                      <label class="form-label fw-semibold text-champagne" for="edit-name"
                        >Name <span class="text-danger">*</span></label
                      >
                      <input
                        id="edit-name"
                        v-model="editForm.name"
                        type="text"
                        class="form-control form-control-dark"
                        required
                      />
                    </div>
                    <div class="col-12 col-sm-6">
                      <label class="form-label fw-semibold text-champagne" for="edit-email"
                        >Email <span class="text-danger">*</span></label
                      >
                      <input
                        id="edit-email"
                        v-model="editForm.email"
                        type="email"
                        class="form-control form-control-dark"
                        required
                      />
                    </div>
                    <div class="col-12 col-sm-6">
                      <label class="form-label fw-semibold text-champagne" for="edit-phone"
                        >Phone <span class="text-danger">*</span></label
                      >
                      <input
                        id="edit-phone"
                        v-model="editForm.phone"
                        type="text"
                        class="form-control form-control-dark"
                        required
                      />
                    </div>
                    <div class="col-12 col-sm-6">
                      <label class="form-label fw-semibold text-champagne" for="edit-concert"
                        >Concert Title <span class="text-danger">*</span></label
                      >
                      <input
                        id="edit-concert"
                        v-model="editForm.concert_title"
                        type="text"
                        class="form-control form-control-dark"
                        required
                      />
                    </div>
                    <div class="col-12 col-sm-6">
                      <label class="form-label fw-semibold text-champagne" for="edit-qty"
                        >Ticket Quantity <span class="text-danger">*</span></label
                      >
                      <input
                        id="edit-qty"
                        v-model.number="editForm.ticket_quantity"
                        type="number"
                        min="1"
                        class="form-control form-control-dark"
                        required
                      />
                    </div>
                    <div class="col-12 col-sm-6">
                      <label class="form-label fw-semibold text-champagne" for="edit-notes"
                        >Notes</label
                      >
                      <input
                        id="edit-notes"
                        v-model="editForm.notes"
                        type="text"
                        class="form-control form-control-dark"
                      />
                    </div>
                  </div>
                </div>
                <div class="modal-footer modal-footer-dark flex-column flex-sm-row gap-2">
                  <button
                    type="button"
                    class="btn btn-outline-gold w-100 w-sm-auto"
                    @click="closeEditModal"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="btn btn-gold w-100 w-sm-auto"
                    :disabled="editModal.loading"
                  >
                    <span
                      v-if="editModal.loading"
                      class="spinner-border spinner-border-sm me-2"
                      aria-hidden="true"
                    ></span>
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
      <div v-if="editModal.visible" class="modal-backdrop fade show"></div>
    </Teleport>

    <!-- ── Delete Confirm Modal ────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="deleteModal.visible"
          class="modal fade show d-block"
          tabindex="-1"
          role="dialog"
          aria-modal="true"
          aria-labelledby="deleteModalLabel"
          @click.self="closeDeleteModal"
        >
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-dark">
              <div class="modal-header modal-header-dark border-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteModalLabel">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  Delete Registration
                </h5>
                <button
                  type="button"
                  class="btn-close btn-close-white"
                  aria-label="Close"
                  @click="closeDeleteModal"
                ></button>
              </div>
              <div class="modal-body pt-0">
                <p class="mb-1 text-champagne-muted">
                  Are you sure you want to delete this registration?
                </p>
                <p class="fw-semibold mb-0 text-champagne">
                  {{ deleteModal.audience?.name }} &mdash; {{ deleteModal.audience?.concert_title }}
                </p>
                <p class="text-champagne-muted small mt-1">This action cannot be undone.</p>
              </div>
              <div class="modal-footer modal-footer-dark border-0 flex-column flex-sm-row gap-2">
                <button
                  type="button"
                  class="btn btn-outline-gold w-100 w-sm-auto"
                  @click="closeDeleteModal"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  class="btn btn-danger w-100 w-sm-auto"
                  :disabled="deleteModal.loading"
                  @click="executeDelete"
                >
                  <span
                    v-if="deleteModal.loading"
                    class="spinner-border spinner-border-sm me-2"
                    aria-hidden="true"
                  ></span>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
      <div v-if="deleteModal.visible" class="modal-backdrop fade show"></div>
    </Teleport>

    <!-- ── Resend Email Confirm Modal ──────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="emailModal.visible"
          class="modal fade show d-block"
          tabindex="-1"
          role="dialog"
          aria-modal="true"
          aria-labelledby="emailModalLabel"
          @click.self="closeEmailModal"
        >
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-dark">
              <div class="modal-header modal-header-dark border-0">
                <h5 class="modal-title fw-bold text-warning" id="emailModalLabel">
                  <i class="bi bi-envelope-check me-2"></i>
                  Resend Ticket Email
                </h5>
                <button
                  type="button"
                  class="btn-close btn-close-white"
                  aria-label="Close"
                  @click="closeEmailModal"
                ></button>
              </div>
              <div class="modal-body pt-0">
                <p class="mb-1 text-champagne-muted">
                  Resend the ticket email with PDF attachment to:
                </p>
                <p class="fw-semibold mb-0 text-champagne">{{ emailModal.audience?.name }}</p>
                <p class="text-champagne-muted small mt-1">{{ emailModal.audience?.email }}</p>
              </div>
              <div class="modal-footer modal-footer-dark border-0 flex-column flex-sm-row gap-2">
                <button
                  type="button"
                  class="btn btn-outline-gold w-100 w-sm-auto"
                  @click="closeEmailModal"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  class="btn btn-info text-white w-100 w-sm-auto"
                  :disabled="
                    emailModal.loading || emailModal.audience?.send_email_status === 'sent'
                  "
                  @click="executeResendEmail"
                >
                  <span
                    v-if="emailModal.loading"
                    class="spinner-border spinner-border-sm me-2"
                    aria-hidden="true"
                  ></span>
                  <i v-else class="bi bi-send me-2"></i>
                  {{
                    emailModal.audience?.send_email_status === 'sent' ? 'Email Sent' : 'Send Email'
                  }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
      <div v-if="emailModal.visible" class="modal-backdrop fade show"></div>
    </Teleport>

    <!-- ── Mark Attended Confirm Modal ──────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="attendModal.visible"
          class="modal fade show d-block"
          tabindex="-1"
          role="dialog"
          aria-modal="true"
          aria-labelledby="attendModalLabel"
          @click.self="closeAttendModal"
        >
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-dark">
              <div class="modal-header modal-header-dark border-0">
                <h5 class="modal-title fw-bold text-success" id="attendModalLabel">
                  <i class="bi bi-check-circle-fill me-2"></i>
                  Mark as Attended
                </h5>
                <button
                  type="button"
                  class="btn-close btn-close-white"
                  aria-label="Close"
                  @click="closeAttendModal"
                ></button>
              </div>
              <div class="modal-body pt-0">
                <p class="mb-1 text-champagne-muted">
                  Confirm that this guest has attended the concert?
                </p>
                <p class="fw-semibold mb-0 text-champagne">
                  {{ attendModal.audience?.name }} &mdash; {{ attendModal.audience?.concert_title }}
                </p>
              </div>
              <div class="modal-footer modal-footer-dark border-0 flex-column flex-sm-row gap-2">
                <button
                  type="button"
                  class="btn btn-outline-gold w-100 w-sm-auto"
                  @click="closeAttendModal"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  class="btn btn-success w-100 w-sm-auto"
                  :disabled="attendModal.loading"
                  @click="executeAttend"
                >
                  <span
                    v-if="attendModal.loading"
                    class="spinner-border spinner-border-sm me-2"
                    aria-hidden="true"
                  ></span>
                  Mark Attended
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
      <div v-if="attendModal.visible" class="modal-backdrop fade show"></div>
    </Teleport>
  </div>
</template>

<style scoped>
/* ── Text helpers ──────────────────────────────────────────────── */
.text-champagne {
  color: rgba(234, 220, 194, 0.92);
}

.text-champagne-muted {
  color: rgba(234, 220, 194, 0.5);
}

/* ── Modal dark theme ──────────────────────────────────────────── */
.modal-content-dark {
  border: 1px solid rgba(234, 220, 194, 0.12) !important;
  border-radius: 12px !important;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%) !important;
  box-shadow: 0 20px 48px rgba(8, 8, 14, 0.5) !important;
  color: rgba(234, 220, 194, 0.85);
}

.modal-header-dark {
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.2), rgba(200, 164, 93, 0.08)) !important;
  border-bottom: 1px solid rgba(234, 220, 194, 0.08) !important;
  border-radius: 11px 11px 0 0 !important;
  padding: 1rem 1.25rem !important;
}

.modal-footer-dark {
  border-top: 1px solid rgba(234, 220, 194, 0.08) !important;
  padding: 0.85rem 1.25rem !important;
}

/* ── Dark form controls ────────────────────────────────────────── */
.form-control-dark {
  background: rgba(234, 220, 194, 0.06) !important;
  border: 1px solid rgba(234, 220, 194, 0.15) !important;
  color: rgba(234, 220, 194, 0.88) !important;
}

.form-control-dark:focus {
  border-color: rgba(200, 164, 93, 0.4) !important;
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.1) !important;
  background: rgba(234, 220, 194, 0.08) !important;
}

.form-control-dark::placeholder {
  color: rgba(234, 220, 194, 0.35);
}

.form-select-dark {
  background-color: rgba(234, 220, 194, 0.06) !important;
  border: 1px solid rgba(234, 220, 194, 0.15) !important;
  color: rgba(234, 220, 194, 0.88) !important;
}

.form-select-dark:focus {
  border-color: rgba(200, 164, 93, 0.4) !important;
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.1) !important;
}

.form-select-dark option {
  background: #1a1f30 !important;
  color: rgba(234, 220, 194, 0.88) !important;
}

/* ── Table ──────────────────────────────────────────────────────── */
:deep(table.tbl) {
  color: rgba(234, 220, 194, 0.78);
  font-size: 0.85rem;
}

:deep(table.tbl thead th) {
  padding: 1rem 0.85rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #c8a45d;
  border-bottom: 1px solid rgba(200, 164, 93, 0.18);
  background: linear-gradient(180deg, rgba(200, 164, 93, 0.06), transparent);
  white-space: nowrap;
}

:deep(table.tbl thead th:first-child) {
  padding-left: 1.25rem;
}

:deep(table.tbl thead th:last-child) {
  padding-right: 1.25rem;
}

:deep(table.tbl tbody tr) {
  transition: background 0.2s;
}

:deep(table.tbl tbody tr:hover) {
  background: rgba(200, 164, 93, 0.06);
}

:deep(table.tbl tbody tr td) {
  padding: 0.85rem 0.85rem;
  border-bottom: 1px solid rgba(234, 220, 194, 0.04);
  background: transparent;
  vertical-align: middle;
}

:deep(table.tbl tbody tr td:first-child) {
  padding-left: 1.25rem;
}

:deep(table.tbl tbody tr td:last-child) {
  padding-right: 1.25rem;
}

:deep(table.tbl tbody tr:last-child td) {
  border-bottom: none;
}

:deep(.tbl-name) {
  font-weight: 600;
  color: rgba(234, 220, 194, 0.92);
}

:deep(.tbl-cell) {
  color: rgba(234, 220, 194, 0.55);
}

/* ── Seat number ──────────────────────────────────────────────── */
:deep(.tbl-seat) {
  display: inline-block;
  padding: 0.18rem 0.55rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: rgba(234, 220, 194, 0.75);
  background: rgba(200, 164, 93, 0.07);
  border: 1px solid rgba(200, 164, 93, 0.12);
  border-radius: 6px;
}

/* ── Badges ────────────────────────────────────────────────────── */
:deep(.tbl-badge) {
  display: inline-block;
  padding: 0.22rem 0.65rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 20px;
}

:deep(.tbl-badge-checked) {
  color: #4caf7d;
  background: rgba(76, 175, 125, 0.1);
  border: 1px solid rgba(76, 175, 125, 0.18);
}

:deep(.tbl-badge-pending) {
  color: rgba(234, 220, 194, 0.4);
  background: rgba(234, 220, 194, 0.05);
  border: 1px solid rgba(234, 220, 194, 0.08);
}

/* ── Action buttons ────────────────────────────────────────────── */
:deep(.tbl-actions) {
  display: inline-flex;
  gap: 0.35rem;
}

:deep(.tbl-action-btn) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid rgba(234, 220, 194, 0.1);
  border-radius: 8px;
  background: rgba(234, 220, 194, 0.04);
  color: rgba(234, 220, 194, 0.5);
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
}

:deep(.tbl-action-btn:hover) {
  transform: translateY(-1px);
}

:deep(.tbl-action-btn:disabled) {
  opacity: 0.3;
  cursor: not-allowed;
  transform: none !important;
}

:deep(.tbl-action-btn--pdf:hover) {
  color: #4caf7d;
  border-color: rgba(76, 175, 125, 0.35);
  background: rgba(76, 175, 125, 0.1);
}

:deep(.tbl-action-btn--email:hover) {
  color: #5bc0de;
  border-color: rgba(91, 192, 222, 0.35);
  background: rgba(91, 192, 222, 0.1);
}

:deep(.tbl-action-btn--edit:hover) {
  color: #c8a45d;
  border-color: rgba(200, 164, 93, 0.35);
  background: rgba(200, 164, 93, 0.1);
}

:deep(.tbl-action-btn--check:hover) {
  color: #4caf7d;
  border-color: rgba(76, 175, 125, 0.35);
  background: rgba(76, 175, 125, 0.1);
}

:deep(.tbl-action-btn--delete:hover) {
  color: #e05050;
  border-color: rgba(220, 53, 69, 0.35);
  background: rgba(220, 53, 69, 0.1);
}

.tbl-checked-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
}

/* ── Pagination ────────────────────────────────────────────────── */
:deep(.tbl-pagination) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(234, 220, 194, 0.06);
  margin-top: 0.25rem;
}

:deep(.tbl-pagination-info) {
  color: rgba(234, 220, 194, 0.4);
  font-size: 0.78rem;
}

:deep(.tbl-pagination-btns) {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

:deep(.tbl-page-btn) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 1px solid rgba(234, 220, 194, 0.08);
  border-radius: 8px;
  background: rgba(234, 220, 194, 0.04);
  color: rgba(234, 220, 194, 0.5);
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
}

:deep(.tbl-page-btn:hover:not(:disabled)) {
  border-color: rgba(200, 164, 93, 0.3);
  color: #c8a45d;
  background: rgba(200, 164, 93, 0.08);
  transform: translateY(-1px);
}

:deep(.tbl-page-btn:disabled) {
  opacity: 0.25;
  cursor: not-allowed;
}

:deep(.tbl-page-indicator) {
  color: rgba(234, 220, 194, 0.35);
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0 0.25rem;
}

/* ── Search input ──────────────────────────────────────────────── */
.tbl-search-input {
  width: 240px;
}

@media (max-width: 768px) {
  .tbl-search-input {
    width: 100%;
  }
}

/* ── Empty state ───────────────────────────────────────────────── */
:deep(.tbl-empty) {
  padding: 3.5rem 1.5rem;
  text-align: center;
}

:deep(.tbl-empty-icon i) {
  font-size: 3rem;
  color: rgba(234, 220, 194, 0.15);
  margin-bottom: 1rem;
}

:deep(.tbl-empty-title) {
  font-size: 1.1rem;
  font-weight: 700;
  color: rgba(234, 220, 194, 0.78);
  margin-bottom: 0.4rem;
}

:deep(.tbl-empty-text) {
  color: rgba(234, 220, 194, 0.4);
  font-size: 0.88rem;
  margin-bottom: 1.5rem;
}

/* ── Mobile responsive: card layout ──────────────────────────── */
@media (max-width: 768px) {
  :deep(table.tbl) thead {
    display: none;
  }

  :deep(table.tbl),
  :deep(table.tbl) tbody,
  :deep(table.tbl) tr,
  :deep(table.tbl) td {
    display: block;
  }

  :deep(table.tbl) tbody tr {
    position: relative;
    padding: 0.85rem 1rem;
    margin-bottom: 0.75rem;
    border: 1px solid rgba(234, 220, 194, 0.1);
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(200, 164, 93, 0.04), transparent 50%),
                rgba(26, 31, 48, 0.5);
  }

  :deep(table.tbl) tbody tr td {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.4rem 0;
    border-bottom: 1px solid rgba(234, 220, 194, 0.04);
  }

  :deep(table.tbl) tbody tr td:last-child {
    border-bottom: none;
  }

  :deep(table.tbl) tbody tr td:first-child {
    padding-left: 0;
  }

  :deep(table.tbl) tbody tr td:last-child {
    padding-right: 0;
  }

  :deep(table.tbl) tbody tr td::before {
    content: attr(data-label);
    flex-shrink: 0;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #c8a45d;
    min-width: 80px;
  }

  :deep(table.tbl) tbody tr td:last-child::before {
    align-self: flex-start;
    margin-top: 0.35rem;
  }

  :deep(.tbl-name) {
    font-size: 0.92rem;
  }

  :deep(.tbl-actions) {
    gap: 0.5rem;
  }

  :deep(table.tbl) tbody tr td[data-label="Actions"] {
    flex-wrap: wrap;
    padding-top: 0.5rem;
  }

  :deep(.tbl-pagination) {
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
  }
}

/* ── Alert overrides ────────────────────────────────────────────── */
:deep(.alert) {
  background: rgba(234, 220, 194, 0.06) !important;
  border: 1px solid rgba(234, 220, 194, 0.1) !important;
  color: rgba(234, 220, 194, 0.85) !important;
}

:deep(.alert-danger) {
  border-color: rgba(220, 53, 69, 0.3) !important;
  background: rgba(220, 53, 69, 0.1) !important;
  color: #e05050 !important;
}

:deep(.alert-success) {
  border-color: rgba(76, 175, 125, 0.3) !important;
  background: rgba(76, 175, 125, 0.1) !important;
  color: #4caf7d !important;
}

/* Full-viewport modal overlay */
:global(.modal.show) {
  z-index: 1055;
}
:global(.modal-backdrop.show) {
  z-index: 1050;
}

/* Buttons go full-width on xs, auto on sm+ */
@media (min-width: 576px) {
  .w-sm-auto {
    width: auto !important;
  }
}
</style>