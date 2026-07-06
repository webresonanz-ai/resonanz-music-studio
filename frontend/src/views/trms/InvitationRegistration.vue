<template>
  <div class="fade-in-up">

    <!-- ── Page header ───────────────────────────────────────────────────── -->
    <div class="content-card mb-4">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Concert</p>
          <h1 class="display-4 fw-bold mb-3">
            {{ selectedConcert ? selectedConcert.title : "Invitation Registration" }}
          </h1>
          <p class="lead text-muted mb-0">
            {{
              selectedConcert
                ? concertScheduleLabel
                : "Select a concert then register audiences with invitation access."
            }}
          </p>
        </div>
        <div class="col-lg-5">
          <div class="bg-dark text-white rounded p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <i class="bi bi-ticket-perforated display-6 text-warning"></i>
              <div>
                <div class="fw-bold">
                  {{ selectedConcert ? "Konser yang Dipilih" : "Invitation Pass" }}
                </div>
                <div class="text-white-50 small">
                  {{ selectedConcert ? concertTimeLabel : "Register audiences with invitation access" }}
                </div>
              </div>
            </div>
            <p class="mb-0 text-white-50">
              Only admin and manager can access this page.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Step 1 : Concert picker ────────────────────────────────────────── -->
    <div v-if="!selectedConcert" class="content-card">
      <div v-if="loadingSchedule" class="py-5 text-center text-muted">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <div>Loading concerts...</div>
      </div>

      <div
        v-else-if="errorMessage && !selectedConcert"
        class="alert alert-danger d-flex align-items-center gap-2"
        role="alert"
      >
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ errorMessage }}</span>
      </div>

      <div v-else-if="upcomingConcerts.length" class="row g-3">
        <div
          v-for="concert in upcomingConcerts"
          :key="concert.id"
          class="col-md-6 col-xl-4"
        >
          <button
            class="concert-option w-100 text-start"
            type="button"
            @click="pickConcert(concert)"
          >
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <span class="badge bg-warning text-dark">Concert</span>
              <i class="bi bi-arrow-right-circle text-primary fs-4"></i>
            </div>
            <h2 class="h5 fw-bold mb-2">{{ concert.title }}</h2>
            <div class="text-muted mb-3">{{ formatDate(concert.date) }}</div>
            <div class="d-flex align-items-center gap-2 text-muted small">
              <i class="bi bi-clock"></i>
              <span>{{ formatTime(concert.start_time) }} - {{ formatTime(concert.end_time) }}</span>
            </div>
            <p v-if="concert.description" class="small text-muted mt-3 mb-0">
              {{ concert.description }}
            </p>
          </button>
        </div>
      </div>

      <div v-else class="py-5 text-center text-muted">
        <i class="bi bi-calendar-x display-1 d-block mb-3"></i>
        <h2 class="h4 fw-bold">No upcoming concerts</h2>
        <p class="mb-0">Concert schedules added from the Schedules page will appear here.</p>
      </div>
    </div>

    <!-- ── Step 2 : Registration form ────────────────────────────────────── -->
    <div v-else class="content-card">
      <!-- Selected concert summary + change button -->
      <div class="d-flex align-items-center justify-content-between gap-3 mb-4 p-3 rounded selected-concert-bar">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-calendar-check text-primary fs-4"></i>
          <div>
            <div class="fw-semibold">{{ selectedConcert.title }}</div>
            <div class="text-muted small">
              {{ formatDate(selectedConcert.date) }} &middot;
              {{ formatTime(selectedConcert.start_time) }} - {{ formatTime(selectedConcert.end_time) }}
            </div>
          </div>
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary flex-shrink-0"
          @click="changeConcert"
        >
          <i class="bi bi-arrow-left-circle me-1"></i>Ganti Konser
        </button>
      </div>

      <form @submit.prevent="submitRegistration">
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

        <div class="row g-3">
          <div class="col-md-6">
            <label for="inviteeName" class="form-label">Nama Lengkap</label>
            <input
              id="inviteeName"
              v-model.trim="form.name"
              class="form-control"
              type="text"
              autocomplete="name"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="inviteeEmail" class="form-label">Email</label>
            <input
              id="inviteeEmail"
              v-model.trim="form.email"
              class="form-control"
              type="email"
              autocomplete="email"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="inviteePhone" class="form-label">Nomor HP</label>
            <input
              id="inviteePhone"
              v-model.trim="form.phone"
              class="form-control"
              type="tel"
              autocomplete="tel"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="inviteeTickets" class="form-label">Jumlah Tiket</label>
            <input
              id="inviteeTickets"
              v-model.number="form.ticket_quantity"
              class="form-control"
              type="number"
              min="1"
              required
            />
          </div>
        </div>

        <div class="d-flex gap-3 mt-4">
          <button
            class="btn btn-primary btn-lg"
            type="submit"
            :disabled="loading"
          >
            <span
              v-if="loading"
              class="spinner-border spinner-border-sm me-2"
              aria-hidden="true"
            ></span>
            <i v-else class="bi bi-send-check me-2"></i>
            {{ loading ? "Mengirim..." : "Kirim Registrasi" }}
          </button>
        </div>
      </form>
    </div>

  </div>
</template>

<script>
import { useTrmsStore } from "../../stores/api";

const emptyForm = () => ({
  name: "",
  email: "",
  phone: "",
  ticket_quantity: 1,
});

export default {
  name: "InvitationRegistration",
  setup() {
    return {
      trmsStore: useTrmsStore(),
    };
  },
  data() {
    return {
      form: emptyForm(),
      loading: false,
      loadingSchedule: false,
      errorMessage: "",
      successMessage: "",
      selectedConcert: null,
    };
  },
  computed: {
    upcomingConcerts() {
      const todayKey = this.toDateKey(new Date());
      return this.trmsStore.schedules
        .filter((s) => s.type === "concert" && s.date >= todayKey)
        .sort((a, b) => {
          const d = a.date.localeCompare(b.date);
          return d || a.start_time.localeCompare(b.start_time);
        });
    },
    concertScheduleLabel() {
      if (!this.selectedConcert) return "";
      return this.formatDate(this.selectedConcert.date);
    },
    concertTimeLabel() {
      if (!this.selectedConcert) return "";
      return `${this.formatTime(this.selectedConcert.start_time)} - ${this.formatTime(this.selectedConcert.end_time)}`;
    },
  },
  async mounted() {
    await this.loadSchedules();
  },
  methods: {
    async loadSchedules() {
      this.loadingSchedule = true;
      this.errorMessage = "";
      try {
        await this.trmsStore.fetchSchedules();
      } catch (error) {
        this.errorMessage = error.message || "Unable to load concert schedules.";
      } finally {
        this.loadingSchedule = false;
      }
    },

    pickConcert(concert) {
      this.selectedConcert = concert;
      this.errorMessage = "";
      this.successMessage = "";
      this.form = emptyForm();
    },

    changeConcert() {
      this.selectedConcert = null;
      this.errorMessage = "";
      this.successMessage = "";
      this.form = emptyForm();
    },

    async submitRegistration() {
      this.loading = true;
      this.errorMessage = "";
      this.successMessage = "";

      try {
        const now = new Date();
        const localTimestamp = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")} ${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(now.getSeconds()).padStart(2, "0")}`;

        const payload = {
          name: this.form.name,
          email: this.form.email,
          phone: this.form.phone,
          concert_title: this.selectedConcert.title,
          schedule_id: this.selectedConcert.id,
          ticket_quantity: this.form.ticket_quantity,
          notes: "Invitation",
          created_at: localTimestamp,
        };

        await this.trmsStore.submitConcertRegistration(payload);
        this.successMessage = `Registrasi berhasil dikirim untuk ${payload.name}.`;
        this.form = emptyForm();
      } catch (error) {
        this.errorMessage = error.message || "Unable to submit registration.";
      } finally {
        this.loading = false;
      }
    },

    toDateKey(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    formatDate(dateStr) {
      if (!dateStr) return "";
      const [year, month, day] = dateStr.split("-").map(Number);
      return new Date(year, month - 1, day).toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },
    formatTime(value) {
      return String(value || "").slice(0, 5);
    },
  },
};
</script>

<style scoped>
.concert-option {
  min-height: 100%;
  border: 1px solid var(--hairline-color);
  border-radius: var(--radius-md);
  background: rgba(255, 253, 248, 0.72);
  padding: 1.25rem;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.concert-option:hover,
.concert-option:focus-visible {
  border-color: rgba(200, 164, 93, 0.52);
  box-shadow: var(--shadow-soft);
  transform: translateY(-3px);
}

.selected-concert-bar {
  background: rgba(var(--bs-primary-rgb), 0.05);
  border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
}
</style>
