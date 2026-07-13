<template>
  <div class="fade-in-up">

    <div class="content-card bg-dark mb-4">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <p class="text-uppercase text-warning fw-bold small mb-2">TRMS Concert</p>
          <h1 class="display-4 fw-bold mb-3 text-champagne">
            {{ selectedConcert ? selectedConcert.title : "Invitation Registration" }}
          </h1>
          <p class="lead text-champagne-muted mb-0">
            {{
              selectedConcert
                ? concertScheduleLabel
                : "Select a concert then register audiences with invitation access."
            }}
          </p>
        </div>
        <div class="col-lg-5">
          <div class="bg-dark-card rounded-3 p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <i class="bi bi-ticket-perforated display-6 text-warning"></i>
              <div>
                <div class="fw-bold text-champagne">
                  {{ selectedConcert ? "Konser yang Dipilih" : "Invitation Pass" }}
                </div>
                <div class="text-champagne-muted small">
                  {{ selectedConcert ? concertTimeLabel : "Register audiences with invitation access" }}
                </div>
              </div>
            </div>
            <p class="mb-0 text-champagne-muted">
              Only admin and manager can access this page.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!selectedConcert" class="content-card bg-dark">
      <div v-if="loadingSchedule" class="py-5 text-center">
        <div class="spinner-border text-warning mb-3" role="status"></div>
        <div class="text-champagne-muted">Loading concerts...</div>
      </div>

      <div
        v-else-if="errorMessage && !selectedConcert"
        class="alert alert-themed alert-danger d-flex align-items-center gap-2"
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
            class="concert-card w-100 text-start"
            type="button"
            @click="pickConcert(concert)"
          >
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <span class="concert-badge">Concert</span>
              <i class="bi bi-arrow-right-circle concert-arrow"></i>
            </div>
            <h2 class="concert-title">{{ concert.title }}</h2>
            <div class="concert-date">{{ formatDate(concert.date) }}</div>
            <div class="concert-time">
              <i class="bi bi-clock me-1"></i>
              <span>{{ formatTime(concert.start_time) }} - {{ formatTime(concert.end_time) }}</span>
            </div>
            <p v-if="concert.description" class="concert-desc mt-3 mb-0">
              {{ concert.description }}
            </p>
          </button>
        </div>
      </div>

      <div v-else class="py-5 text-center">
        <i class="bi bi-calendar-x empty-icon d-block mb-3"></i>
        <h2 class="h4 fw-bold text-champagne">No upcoming concerts</h2>
        <p class="text-champagne-muted mb-0">Concert schedules added from the Schedules page will appear here.</p>
      </div>
    </div>

    <div v-else class="content-card bg-dark">
      <div
        class="d-flex align-items-center justify-content-between gap-3 mb-4 p-3 rounded selected-bar"
      >
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-calendar-check text-warning fs-4"></i>
          <div>
            <div class="fw-semibold text-champagne">{{ selectedConcert.title }}</div>
            <div class="text-champagne-muted small">
              {{ formatDate(selectedConcert.date) }} &middot;
              {{ formatTime(selectedConcert.start_time) }} - {{ formatTime(selectedConcert.end_time) }}
            </div>
          </div>
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-gold flex-shrink-0"
          @click="changeConcert"
        >
          <i class="bi bi-arrow-left-circle me-1"></i>Ganti Konser
        </button>
      </div>

      <form @submit.prevent="submitRegistration">
        <div
          v-if="errorMessage"
          class="alert alert-themed alert-danger d-flex align-items-center gap-2"
          role="alert"
        >
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ errorMessage }}</span>
        </div>

        <div
          v-if="successMessage"
          class="alert alert-themed alert-success d-flex align-items-center gap-2"
          role="alert"
        >
          <i class="bi bi-check-circle-fill"></i>
          <span>{{ successMessage }}</span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label for="inviteeName" class="form-label text-champagne">Nama Lengkap</label>
            <input
              id="inviteeName"
              v-model.trim="form.name"
              class="form-control form-control-dark"
              type="text"
              autocomplete="name"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="inviteeEmail" class="form-label text-champagne">Email</label>
            <input
              id="inviteeEmail"
              v-model.trim="form.email"
              class="form-control form-control-dark"
              type="email"
              autocomplete="email"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="inviteePhone" class="form-label text-champagne">Nomor HP</label>
            <input
              id="inviteePhone"
              v-model.trim="form.phone"
              class="form-control form-control-dark"
              type="tel"
              autocomplete="tel"
              required
            />
          </div>

          <div class="col-md-6">
            <label for="inviteeTickets" class="form-label text-champagne">Jumlah Tiket</label>
            <input
              id="inviteeTickets"
              v-model.number="form.ticket_quantity"
              class="form-control form-control-dark"
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
.concert-card {
  min-height: 100%;
  border: 1px solid rgba(234, 220, 194, 0.1);
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(200, 164, 93, 0.04), transparent 50%),
              linear-gradient(180deg, #1a1f30, #111420);
  padding: 1.25rem;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
  color: inherit;
}

.concert-card:hover,
.concert-card:focus-visible {
  border-color: rgba(200, 164, 93, 0.35);
  box-shadow: 0 4px 20px rgba(200, 164, 93, 0.1);
  transform: translateY(-3px);
  outline: none;
}

.concert-badge {
  display: inline-block;
  padding: 0.2rem 0.65rem;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #1a1f30;
  background: #c8a45d;
  border-radius: 6px;
}

.concert-arrow {
  font-size: 1.4rem;
  color: rgba(200, 164, 93, 0.4);
  transition: color 0.2s, transform 0.2s;
}

.concert-card:hover .concert-arrow {
  color: #c8a45d;
  transform: translateX(3px);
}

.concert-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: rgba(234, 220, 194, 0.9);
  margin-bottom: 0.4rem;
}

.concert-date {
  color: rgba(234, 220, 194, 0.5);
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.concert-time {
  display: flex;
  align-items: center;
  color: rgba(234, 220, 194, 0.35);
  font-size: 0.78rem;
}

.concert-desc {
  color: rgba(234, 220, 194, 0.35);
  font-size: 0.8rem;
  line-height: 1.4;
}

.empty-icon {
  font-size: 3rem;
  color: rgba(234, 220, 194, 0.15);
}

.selected-bar {
  background: linear-gradient(135deg, rgba(200, 164, 93, 0.06), transparent);
  border: 1px solid rgba(200, 164, 93, 0.12);
}
</style>
