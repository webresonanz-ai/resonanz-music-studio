<template>
  <div class="fade-in-up">
    <div class="content-card bg-dark mb-4">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <p class="text-uppercase text-warning fw-bold small mb-2">TRMS Concert</p>
          <h1 class="display-4 fw-bold mb-3 text-champagne">Select Concert</h1>
          <p class="lead text-champagne-muted mb-0">
            Choose an upcoming concert before completing the audience registration form.
          </p>
        </div>
        <div class="col-lg-5">
          <div class="bg-dark-card rounded-3 p-4 h-100">
            <div class="d-flex align-items-center gap-3">
              <i class="bi bi-calendar-event display-6 text-warning"></i>
              <div>
                <div class="fw-bold text-champagne">Upcoming Concerts</div>
                <div class="text-champagne-muted small">
                  {{ upcomingConcerts.length }} available schedule{{
                    upcomingConcerts.length === 1 ? "" : "s"
                  }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="content-card bg-dark">
      <div
        v-if="errorMessage"
        class="alert alert-danger d-flex align-items-center gap-2"
        role="alert"
      >
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ errorMessage }}</span>
      </div>

      <div v-if="loading" class="py-5 text-center">
        <div class="spinner-border text-warning mb-3" role="status"></div>
        <div class="text-champagne-muted">Loading concerts...</div>
      </div>

      <div v-else-if="upcomingConcerts.length" class="row g-3">
        <div class="col-md-6 col-xl-4" v-for="concert in upcomingConcerts" :key="concert.id">
          <button
            class="concert-option w-100 text-start"
            type="button"
            @click="selectConcert(concert)"
          >
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <span class="badge bg-warning text-dark">Concert</span>
              <i class="bi bi-arrow-right-circle text-warning fs-4"></i>
            </div>
            <h2 class="h5 fw-bold mb-2 text-champagne">{{ concert.title }}</h2>
            <div class="text-champagne-muted mb-3">{{ formatDate(concert.date) }}</div>
            <div class="d-flex align-items-center gap-2 text-champagne-muted small">
              <i class="bi bi-clock"></i>
              <span>{{ formatTime(concert.start_time) }} - {{ formatTime(concert.end_time) }}</span>
            </div>
            <p v-if="concert.description" class="small text-champagne-muted mt-3 mb-0">
              {{ concert.description }}
            </p>
          </button>
        </div>
      </div>

      <div v-else class="py-5 text-center">
        <i class="bi bi-calendar-x display-1 d-block mb-3 text-champagne-muted"></i>
        <h2 class="h4 fw-bold text-champagne">No upcoming concerts</h2>
        <p class="mb-0 text-champagne-muted">Concert schedules added from the Schedules page will appear here.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { useTrmsStore } from "../../stores/api";

export default {
  name: "ConcertSelection",
  setup() {
    return {
      trmsStore: useTrmsStore(),
    };
  },
  data() {
    return {
      loading: false,
      errorMessage: "",
    };
  },
  computed: {
    upcomingConcerts() {
      const todayKey = this.toDateKey(new Date());

      return this.trmsStore.schedules
        .filter((schedule) => schedule.type === "concert" && schedule.date >= todayKey)
        .sort((a, b) => {
          const dateCompare = a.date.localeCompare(b.date);
          return dateCompare || a.start_time.localeCompare(b.start_time);
        });
    },
  },
  async mounted() {
    await this.loadSchedules();
  },
  methods: {
    async loadSchedules() {
      this.loading = true;
      this.errorMessage = "";

      try {
        await this.trmsStore.fetchSchedules();
      } catch (error) {
        this.errorMessage = error.message || "Unable to load concert schedules.";
      } finally {
        this.loading = false;
      }
    },
    selectConcert(concert) {
      if (concert.is_redirect_url && concert.redirect_url) {
        window.open(concert.redirect_url, "_blank", "noopener,noreferrer");
        return;
      }
      if (!concert.concert_code) {
        this.errorMessage = "This concert does not have a concert code yet.";
        return;
      }
      if (concert.is_seat_assign) {
        this.$router.push(`/concert-reg/${concert.concert_code}/seated`);
        return;
      }
      this.$router.push(`/concert-reg/${concert.concert_code}`);
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
  border: 1px solid rgba(234, 220, 194, 0.1);
  border-radius: var(--radius-md, 8px);
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.06), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  padding: 1.25rem;
  color: rgba(234, 220, 194, 0.85);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
  cursor: pointer;
}

.concert-option:hover,
.concert-option:focus-visible {
  border-color: rgba(200, 164, 93, 0.4);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 12px 32px rgba(10, 10, 18, 0.25);
  transform: translateY(-3px);
}
</style>
