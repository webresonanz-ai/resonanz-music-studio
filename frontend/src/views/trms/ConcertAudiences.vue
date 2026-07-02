<template>
  <div class="fade-in-up">
    <div class="content-card mb-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Concert</p>
          <h1 class="display-4 fw-bold mb-2">Registered Audiences</h1>
          <p class="lead text-muted mb-0">
            Review audience registrations that have been submitted for the concert.
          </p>
        </div>

        <router-link class="btn btn-primary btn-lg" to="/trms/concert/select">
          <i class="bi bi-person-plus me-2"></i>
          Add Audience
        </router-link>
      </div>
    </div>

    <div class="content-card">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
          <h2 class="h4 fw-bold mb-1">Audience List</h2>
          <p class="text-muted mb-0">{{ total }} registration{{ total === 1 ? "" : "s" }}</p>
        </div>

        <div class="d-flex gap-2 align-items-center">
          <div class="position-relative">
            <i
              class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2 text-muted"
            ></i>
            <input
              v-model="search"
              type="text"
              class="form-control ps-4"
              placeholder="Search by name or email..."
              style="width: 240px"
            />
            <span
              v-if="searchPending"
              class="position-absolute top-50 end-0 translate-middle-y me-2"
              title="Searching..."
            >
              <span
                class="spinner-border spinner-border-sm text-secondary"
                aria-hidden="true"
              ></span>
            </span>
          </div>
          <button
            class="btn btn-outline-primary"
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

      <div v-if="loading" class="py-5 text-center text-muted">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <div>Loading audiences...</div>
      </div>

      <div v-else-if="audiences.length" class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Contact</th>
              <th scope="col">Concert</th>
              <th scope="col" class="text-center">Tickets</th>
              <th scope="col">Registered</th>
              <th scope="col" class="text-center">Attendance</th>
              <th scope="col">Notes</th>
              <th scope="col" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="audience in audiences" :key="audience.id">
              <td class="fw-semibold">{{ audience.name }}</td>
              <td>
                <div>{{ audience.email }}</div>
                <div class="small text-muted">{{ audience.phone }}</div>
              </td>
              <td>{{ audience.concert_title }}</td>
              <td class="text-center">
                <span class="badge rounded-pill text-bg-warning">{{
                  audience.ticket_quantity
                }}</span>
              </td>
              <td>{{ formatDate(audience.created_at) }}</td>
              <td class="text-center">
                <span
                  v-if="audience.attended_at"
                  class="badge text-bg-success"
                  :title="formatDate(audience.attended_at)"
                >
                  <i class="bi bi-check-lg me-1"></i>{{ formatDate(audience.attended_at) }}
                </span>
                <span v-else class="badge text-bg-secondary">—</span>
              </td>
              <td class="text-muted">{{ audience.notes || "-" }}</td>
              <td class="text-center">
                <div class="btn-group btn-group-sm" role="group" aria-label="Audience actions">
                  <button
                    type="button"
                    class="btn btn-outline-success"
                    title="Download Ticket PDF"
                    @click="downloadTicketPdf(audience.id)"
                  >
                    <i class="bi bi-file-earmark-pdf"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-info"
                    title="Send Email"
                    @click="confirmResendEmail(audience)"
                  >
                    <i class="bi bi-envelope"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-primary"
                    title="Edit"
                    @click="openEditModal(audience)"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger"
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

        <nav
          v-if="lastPage > 1"
          class="d-flex align-items-center justify-content-between pt-3"
          aria-label="Audience pagination"
        >
          <div class="text-muted small">
            Showing {{ (currentPage - 1) * perPage + 1 }} to
            {{ Math.min(currentPage * perPage, total) }} of {{ total }} registration{{
              total === 1 ? "" : "s"
            }}
          </div>
          <div class="btn-group">
            <button
              class="btn btn-outline-primary"
              type="button"
              :disabled="currentPage <= 1 || loading"
              @click="goToPage(currentPage - 1)"
            >
              <i class="bi bi-chevron-left me-1"></i>
              Previous
            </button>
            <button
              class="btn btn-outline-primary"
              type="button"
              :disabled="currentPage >= lastPage || loading"
              @click="goToPage(currentPage + 1)"
            >
              Next
              <i class="bi bi-chevron-right ms-1"></i>
            </button>
          </div>
        </nav>
      </div>

      <div v-else class="py-5 text-center text-muted">
        <i class="bi bi-ticket-perforated display-1 d-block mb-3"></i>
        <h2 class="h4 fw-bold">{{ search ? "No matching audiences" : "No audiences yet" }}</h2>
        <p class="mb-4">
          {{
            search
              ? "Try adjusting your search keyword."
              : "Submitted concert registrations will appear here."
          }}
        </p>
        <router-link v-if="!search" class="btn btn-primary" to="/trms/concert/select">
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
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title fw-bold" id="editModalLabel">
                <i class="bi bi-pencil-square me-2"></i>
                Edit Audience
              </h5>
              <button
                type="button"
                class="btn-close"
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
                    <label class="form-label fw-semibold" for="edit-name"
                      >Name <span class="text-danger">*</span></label
                    >
                    <input
                      id="edit-name"
                      v-model="editForm.name"
                      type="text"
                      class="form-control"
                      required
                    />
                  </div>
                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold" for="edit-email"
                      >Email <span class="text-danger">*</span></label
                    >
                    <input
                      id="edit-email"
                      v-model="editForm.email"
                      type="email"
                      class="form-control"
                      required
                    />
                  </div>
                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold" for="edit-phone"
                      >Phone <span class="text-danger">*</span></label
                    >
                    <input
                      id="edit-phone"
                      v-model="editForm.phone"
                      type="text"
                      class="form-control"
                      required
                    />
                  </div>
                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold" for="edit-concert"
                      >Concert Title <span class="text-danger">*</span></label
                    >
                    <input
                      id="edit-concert"
                      v-model="editForm.concert_title"
                      type="text"
                      class="form-control"
                      required
                    />
                  </div>
                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold" for="edit-qty"
                      >Ticket Quantity <span class="text-danger">*</span></label
                    >
                    <input
                      id="edit-qty"
                      v-model.number="editForm.ticket_quantity"
                      type="number"
                      min="1"
                      class="form-control"
                      required
                    />
                  </div>
                  <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold" for="edit-notes">Notes</label>
                    <input
                      id="edit-notes"
                      v-model="editForm.notes"
                      type="text"
                      class="form-control"
                    />
                  </div>
                </div>
              </div>
              <div class="modal-footer flex-column flex-sm-row gap-2">
                <button
                  type="button"
                  class="btn btn-outline-secondary w-100 w-sm-auto"
                  @click="closeEditModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary w-100 w-sm-auto"
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
          <div class="modal-content">
            <div class="modal-header border-0">
              <h5 class="modal-title fw-bold text-danger" id="deleteModalLabel">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Delete Registration
              </h5>
              <button
                type="button"
                class="btn-close"
                aria-label="Close"
                @click="closeDeleteModal"
              ></button>
            </div>
            <div class="modal-body pt-0">
              <p class="mb-1">Are you sure you want to delete this registration?</p>
              <p class="fw-semibold mb-0">
                {{ deleteModal.audience?.name }} &mdash; {{ deleteModal.audience?.concert_title }}
              </p>
              <p class="text-muted small mt-1">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 flex-column flex-sm-row gap-2">
              <button
                type="button"
                class="btn btn-outline-secondary w-100 w-sm-auto"
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
          <div class="modal-content">
            <div class="modal-header border-0">
              <h5 class="modal-title fw-bold" id="emailModalLabel">
                <i class="bi bi-envelope-check me-2 text-info"></i>
                Resend Ticket Email
              </h5>
              <button
                type="button"
                class="btn-close"
                aria-label="Close"
                @click="closeEmailModal"
              ></button>
            </div>
            <div class="modal-body pt-0">
              <p class="mb-1">Resend the ticket email with PDF attachment to:</p>
              <p class="fw-semibold mb-0">{{ emailModal.audience?.name }}</p>
              <p class="text-muted small mt-1">{{ emailModal.audience?.email }}</p>
            </div>
            <div class="modal-footer border-0 flex-column flex-sm-row gap-2">
              <button
                type="button"
                class="btn btn-outline-secondary w-100 w-sm-auto"
                @click="closeEmailModal"
              >
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-info text-white w-100 w-sm-auto"
                :disabled="emailModal.loading"
                @click="executeResendEmail"
              >
                <span
                  v-if="emailModal.loading"
                  class="spinner-border spinner-border-sm me-2"
                  aria-hidden="true"
                ></span>
                <i v-else class="bi bi-send me-2"></i>
                Send Email
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
    <div v-if="emailModal.visible" class="modal-backdrop fade show"></div>
  </Teleport>
</template>

<script>
import { useTrmsStore } from "../../stores/api";

export default {
  name: "ConcertAudiences",
  setup() {
    return {
      trmsStore: useTrmsStore(),
    };
  },
  data() {
    return {
      loading: false,
      errorMessage: "",
      successMessage: "",
      page: 1,
      perPage: 10,
      search: "",
      searchPending: false,
      debounceTimer: null,
      // Edit modal
      editModal: {
        visible: false,
        loading: false,
        error: "",
        audience: null,
      },
      editForm: {
        name: "",
        email: "",
        phone: "",
        concert_title: "",
        ticket_quantity: 1,
        notes: "",
      },
      // Delete modal
      deleteModal: {
        visible: false,
        loading: false,
        audience: null,
      },
      // Resend email modal
      emailModal: {
        visible: false,
        loading: false,
        audience: null,
      },
    };
  },
  computed: {
    audiences() {
      return this.trmsStore.concertAudiences;
    },
    total() {
      return this.trmsStore.concertAudiencesMeta.total;
    },
    currentPage() {
      return this.trmsStore.concertAudiencesMeta.currentPage;
    },
    lastPage() {
      return this.trmsStore.concertAudiencesMeta.lastPage;
    },
  },
  watch: {
    search(val) {
      // Show pending spinner immediately so the user knows input was registered
      this.searchPending = true;
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.searchPending = false;
        this.page = 1; // reset to first page on new search
        this.fetchAudiences();
      }, 1500);
    },
  },
  mounted() {
    this.fetchAudiences();
  },
  methods: {
    async fetchAudiences() {
      this.loading = true;
      this.errorMessage = "";
      try {
        await this.trmsStore.fetchConcertAudiences({
          page: this.page,
          perPage: this.perPage,
          search: this.search,
        });
      } catch (error) {
        this.errorMessage = error.message || "Unable to load audiences.";
      } finally {
        this.loading = false;
      }
    },

    formatDate(value) {
      if (!value) return "-";
      return new Intl.DateTimeFormat("en", {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(new Date(value));
    },

    goToPage(page) {
      if (page < 1 || page > this.lastPage || this.loading) return;
      this.page = page;
      this.fetchAudiences();
    },

    showSuccess(msg) {
      this.successMessage = msg;
      setTimeout(() => {
        this.successMessage = "";
      }, 4000);
    },

    setBodyScroll(locked) {
      document.body.style.overflow = locked ? "hidden" : "";
    },

    downloadTicketPdf(id) {
      const apiRoot = import.meta.env.VITE_API_URL || "http://localhost:8000";
      const url = `${apiRoot.replace(/\/$/, "")}/api/trms/concert/ticket/${id}`;
      window.open(url, "_blank", "noopener,noreferrer");
    },

    // ── Edit ──────────────────────────────────────────────────────────
    openEditModal(audience) {
      this.editModal.audience = audience;
      this.editModal.error = "";
      this.editModal.loading = false;
      this.editForm = {
        name: audience.name || "",
        email: audience.email || "",
        phone: audience.phone || "",
        concert_title: audience.concert_title || "",
        ticket_quantity: audience.ticket_quantity || 1,
        notes: audience.notes || "",
      };
      this.editModal.visible = true;
      this.setBodyScroll(true);
    },

    closeEditModal() {
      this.editModal.visible = false;
      this.editModal.audience = null;
      this.setBodyScroll(false);
    },

    async submitEdit() {
      this.editModal.loading = true;
      this.editModal.error = "";
      try {
        await this.trmsStore.updateConcertAudience(this.editModal.audience.id, this.editForm);
        this.showSuccess("Registration updated successfully.");
        this.closeEditModal();
      } catch (error) {
        this.editModal.error = error.message || "Failed to update registration.";
      } finally {
        this.editModal.loading = false;
      }
    },

    // ── Delete ────────────────────────────────────────────────────────
    confirmDelete(audience) {
      this.deleteModal.audience = audience;
      this.deleteModal.loading = false;
      this.deleteModal.visible = true;
      this.setBodyScroll(true);
    },

    closeDeleteModal() {
      this.deleteModal.visible = false;
      this.deleteModal.audience = null;
      this.setBodyScroll(false);
    },

    async executeDelete() {
      this.deleteModal.loading = true;
      try {
        await this.trmsStore.deleteConcertAudience(this.deleteModal.audience.id);
        this.showSuccess("Registration deleted successfully.");
        this.closeDeleteModal();
        if (this.audiences.length === 0 && this.page > 1) {
          this.page -= 1;
          this.fetchAudiences();
        }
      } catch (error) {
        this.errorMessage = error.message || "Failed to delete registration.";
        this.closeDeleteModal();
      }
    },

    // ── Resend Email ──────────────────────────────────────────────────
    confirmResendEmail(audience) {
      this.emailModal.audience = audience;
      this.emailModal.loading = false;
      this.emailModal.visible = true;
      this.setBodyScroll(true);
    },

    closeEmailModal() {
      this.emailModal.visible = false;
      this.emailModal.audience = null;
      this.setBodyScroll(false);
    },

    async executeResendEmail() {
      this.emailModal.loading = true;
      try {
        await this.trmsStore.resendConcertEmail(this.emailModal.audience.id);
        this.showSuccess(`Ticket email resent to ${this.emailModal.audience.email}.`);
        this.closeEmailModal();
      } catch (error) {
        this.errorMessage = error.message || "Failed to resend email.";
        this.closeEmailModal();
      } finally {
        this.emailModal.loading = false;
      }
    },
  },
  beforeUnmount() {
    clearTimeout(this.debounceTimer);
    // Restore scroll if the component is destroyed while a modal is open
    this.setBodyScroll(false);
  },
};
</script>

<style scoped>
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
