<template>
  <div class="fade-in-up">
    <div class="content-card mb-4">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Concert</p>
          <h1 class="display-4 fw-bold mb-3">
            {{ selectedConcert ? selectedConcert.title : "Concert Registration" }}
          </h1>
          <p class="lead text-muted mb-0">
            {{
              selectedConcert
                ? concertScheduleLabel
                : "Select a concert first or reserve audience seats for the upcoming TRMS concert."
            }}
          </p>
          <p v-if="selectedConcert" class="lead text-muted mt-2 mb-0">
            <a
              :href="`https://wa.me/628118747755`"
              target="_blank"
              rel="noopener noreferrer"
              class="text-decoration-none"
            >
              <i class="bi bi-person-badge me-1"></i>Narahubung: +62 811-8747-755
            </a>
          </p>
        </div>
        <div class="col-lg-5">
          <div class="bg-dark text-white rounded p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <i class="bi bi-ticket-perforated display-6 text-warning"></i>
              <div>
                <div class="fw-bold">
                  {{ selectedConcert ? "Konser yang Dipilih" : "Audience Pass" }}
                </div>
                <div class="text-white-50 small">
                  {{ selectedConcert ? concertTimeLabel : "Konfirmasi registrasi" }}
                </div>
              </div>
            </div>
            <p class="mb-0 text-white-50">
              {{
                selectedConcert
                  ? selectedConcert.description ||
                    "Complete the form below to save this audience registration."
                  : "Each submission appears on the audiences page after the API saves it."
              }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="content-card">
      <div v-if="loadingSchedule" class="py-4 text-center text-muted">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <div>Loading selected concert...</div>
      </div>

      <div
        v-else-if="concertCodeParam && !selectedConcert"
        class="alert alert-warning d-flex align-items-center gap-2"
        role="alert"
      >
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>Concert not found. Please choose another upcoming concert.</span>
        <router-link class="btn btn-sm btn-outline-primary ms-auto" to="/trms/concert/select"
          >Select Concert</router-link
        >
      </div>

      <!-- Registration closed notice -->
      <div
        v-else-if="selectedConcert && !selectedConcert.is_open_register"
        class="py-4 text-center"
      >
        <i class="bi bi-lock-fill display-1 text-muted d-block mb-3 opacity-50"></i>
        <h2 class="h4 fw-bold mb-2">Registration is Closed</h2>
        <p class="text-muted mb-4">
          Registration for <strong>{{ selectedConcert.title }}</strong> is currently not open.<br />Please
          check back later.
        </p>
        <router-link class="btn btn-outline-primary" to="/trms/home">Back to Home</router-link>
      </div>

      <!-- Success confirmation card (shown after submit) -->
      <div v-else-if="registrationResult" class="registration-confirmation">
        <!-- Capturable card -->
        <div ref="confirmationCard" class="confirmation-card p-4 rounded">
          <div class="confirmation-header text-center mb-4">
            <div class="confirmation-logo mb-2">
              <img src="/logo_resonanz.png" alt="Resonanz Logo" class="logo-resonanz" />
            </div>
            <div class="fw-bold text-uppercase small text-muted letter-spacing-2 mb-1">
              The Resonanz Music Studio
            </div>
            <h2 class="h4 fw-bold mb-0">Konfirmasi Registrasi</h2>
            <div class="text-success mt-1 small">
              <i class="bi bi-check-circle-fill me-1"></i>Kursi Anda telah dipesan.
            </div>
          </div>

          <div class="confirmation-divider my-3"></div>

          <div class="confirmation-details mb-4">
            <div class="row g-2">
              <div class="col-5 text-muted small">Konser</div>
              <div class="col-7 fw-semibold small">{{ registrationResult.concertTitle }}</div>

              <div class="col-5 text-muted small">Tanggal</div>
              <div class="col-7 fw-semibold small">{{ registrationResult.concertDate }}</div>

              <div class="col-5 text-muted small">Waktu</div>
              <div class="col-7 fw-semibold small">{{ registrationResult.concertTime }}</div>

              <div class="col-12"><hr class="my-2" /></div>

              <div class="col-5 text-muted small">Nama</div>
              <div class="col-7 fw-semibold small">{{ registrationResult.name }}</div>

              <div class="col-5 text-muted small">Email</div>
              <div class="col-7 fw-semibold small text-break">{{ registrationResult.email }}</div>

              <div class="col-5 text-muted small">Nomor HP</div>
              <div class="col-7 fw-semibold small">{{ registrationResult.phone }}</div>

              <div class="col-5 text-muted small">Terdaftar Pada</div>
              <div class="col-7 fw-semibold small">{{ registrationResult.registeredAt }}</div>
            </div>
          </div>

          <div class="confirmation-footer text-center pt-2 border-top">
            <p class="small text-muted mb-1">PDF tiket Anda telah dikirim ke alamat email Anda.</p>
            <p class="small text-danger mb-0">
              Jika Anda tidak menerima email, harap menunjukkan konfirmasi ini kepada narahubung
              untuk mendapatkan bantuan.
            </p>
          </div>
        </div>

        <!-- Action buttons (excluded from screenshot) -->
        <div class="d-flex flex-wrap gap-3 mt-4 justify-content-center no-screenshot">
          <button
            class="btn btn-success btn-lg"
            :disabled="screenshotLoading"
            @click="downloadScreenshot"
          >
            <span
              v-if="screenshotLoading"
              class="spinner-border spinner-border-sm me-2"
              aria-hidden="true"
            ></span>
            <i v-else class="bi bi-download me-2"></i>
            {{ screenshotLoading ? "Mengunduh..." : "Unduh Konfirmasi" }}
          </button>
          <router-link class="btn btn-outline-secondary btn-lg" to="/trms/home">
            <i class="bi bi-house me-2"></i>Kembali ke Beranda
          </router-link>
        </div>

        <div
          v-if="screenshotError"
          class="alert alert-danger mt-3 d-flex align-items-center gap-2"
          role="alert"
        >
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ screenshotError }}</span>
        </div>
      </div>

      <!-- Registration form -->
      <form v-else @submit.prevent="submitRegistration">
        <div
          v-if="errorMessage"
          class="alert alert-danger d-flex align-items-center gap-2"
          role="alert"
        >
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ errorMessage }}</span>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label for="audienceName" class="form-label">Nama Lengkap (Sesuai KTP)</label>
            <input
              id="audienceName"
              v-model.trim="form.name"
              class="form-control"
              type="text"
              autocomplete="name"
              required
            />
            <div class="form-text">
              1 tiket hanya berlaku untuk 1 orang. Mohon tuliskan nama sesuai KTP agar dapat
              digunakan sebagai bukti masuk.
            </div>
          </div>

          <div class="col-md-6">
            <label for="audienceEmail" class="form-label">Email</label>
            <input
              id="audienceEmail"
              v-model.trim="form.email"
              class="form-control"
              type="email"
              autocomplete="email"
              required
            />
            <div class="form-text">
              Pastikan alamat email Anda valid. Tiket Anda akan dikirim ke alamat email tersebut.
            </div>
          </div>

          <div class="col-12">
            <label for="audiencePhone" class="form-label">Nomor HP</label>
            <input
              id="audiencePhone"
              v-model.trim="form.phone"
              class="form-control"
              type="tel"
              autocomplete="tel"
              required
            />
          </div>
        </div>

        <div class="d-flex gap-3 mt-4">
          <button
            class="btn btn-primary btn-lg"
            type="submit"
            :disabled="
              loading ||
              loadingSchedule ||
              (concertCodeParam && !selectedConcert) ||
              (selectedConcert && !selectedConcert.is_open_register)
            "
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
import html2canvas from "html2canvas";
import { useTrmsStore } from "../../stores/api";
import { useBannerStore } from "../../stores/banner";

const emptyForm = () => ({
  name: "",
  email: "",
  phone: "",
  ticket_quantity: 1,
  concert_title: "TRMS Concert",
  notes: "Guest",
});

export default {
  name: "ConcertRegistration",
  setup() {
    return {
      trmsStore: useTrmsStore(),
      bannerStore: useBannerStore(),
    };
  },
  data() {
    return {
      form: emptyForm(),
      loading: false,
      loadingSchedule: false,
      errorMessage: "",
      selectedConcert: null,
      /** Populated after a successful submission – drives the confirmation card */
      registrationResult: null,
      screenshotLoading: false,
      screenshotError: "",
    };
  },
  computed: {
    concertCodeParam() {
      return this.$route.params.concertCode || "";
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
  watch: {
    concertCodeParam() {
      this.loadSelectedConcert();
    },
    // Update banner whenever selectedConcert changes
    selectedConcert: {
      handler(concert) {
        if (concert?.banner_url) {
          this.bannerStore.setBanner(concert.banner_url);
        } else {
          this.bannerStore.clearBanner();
        }
      },
      immediate: false,
    },
  },
  mounted() {
    this.loadSelectedConcert();
  },
  beforeUnmount() {
    // Clean up banner when leaving the page
    this.bannerStore.clearBanner();
  },
  methods: {
    async loadSelectedConcert() {
      this.selectedConcert = null;

      if (!this.concertCodeParam) {
        this.form.concert_title = emptyForm().concert_title;
        return;
      }

      this.loadingSchedule = true;
      this.errorMessage = "";

      try {
        await this.trmsStore.fetchSchedules();

        const todayKey = this.toDateKey(new Date());
        this.selectedConcert = this.trmsStore.schedules
          .filter((schedule) => schedule.type === "concert" && schedule.date >= todayKey)
          .sort((a, b) => {
            const dateCompare = a.date.localeCompare(b.date);
            return dateCompare || a.start_time.localeCompare(b.start_time);
          })
          .find(
            (schedule) =>
              String(schedule.concert_code || "").toUpperCase() ===
              String(this.concertCodeParam).toUpperCase(),
          );

        if (this.selectedConcert) {
          this.form.concert_title = this.selectedConcert.title;
        }
      } catch (error) {
        this.errorMessage = error.message || "Unable to load selected concert.";
      } finally {
        this.loadingSchedule = false;
      }
    },

    async submitRegistration() {
      this.loading = true;
      this.errorMessage = "";

      try {
        if (!this.selectedConcert) {
          this.errorMessage = "Please select a concert before submitting registration.";
          return;
        }

        const now = new Date();
        const localTimestamp = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")} ${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(now.getSeconds()).padStart(2, "0")}`;

        const payload = {
          name: this.form.name,
          email: this.form.email,
          phone: this.form.phone,
          concert_title: this.selectedConcert.title,
          schedule_id: this.selectedConcert.id,
          ticket_quantity: 1,
          notes: "Guest",
          created_at: localTimestamp,
        };

        const response = await this.trmsStore.submitConcertRegistration(payload);

        // Build the confirmation object shown on the card
        this.registrationResult = {
          id: response?.id ?? "—",
          name: payload.name,
          email: payload.email,
          phone: payload.phone,
          concertTitle: this.selectedConcert.title,
          concertDate: this.formatDate(this.selectedConcert.date),
          concertTime: this.concertTimeLabel,
          registeredAt: this.formatDatetime(now),
        };

        // Reset the form for potential next use
        this.form = {
          ...emptyForm(),
          concert_title: this.selectedConcert.title,
        };

        // Wait for the confirmation card to render, then auto-download
        this.$nextTick(() => {
          this.downloadScreenshot();
        });
      } catch (error) {
        this.errorMessage = error.message || "Unable to submit registration.";
      } finally {
        this.loading = false;
      }
    },

    /**
     * Captures the confirmation card as a PNG and triggers a browser download.
     */
    async downloadScreenshot() {
      this.screenshotLoading = true;
      this.screenshotError = "";

      try {
        const card = this.$refs.confirmationCard;
        if (!card) throw new Error("Confirmation card element not found.");

        const canvas = await html2canvas(card, {
          backgroundColor: "#ffffff",
          scale: 2, // retina-quality
          useCORS: true,
          logging: false,
        });

        const dataUrl = canvas.toDataURL("image/png");
        const link = document.createElement("a");
        const safeName = (this.registrationResult?.name || "registration")
          .replace(/\s+/g, "_")
          .replace(/[^a-z0-9_\-]/gi, "");
        link.href = dataUrl;
        link.download = `concert_registration_${safeName}.png`;
        link.click();
      } catch (error) {
        this.screenshotError = "Could not save screenshot. Please try again.";
        console.error("[ConcertRegistration] screenshot error:", error);
      } finally {
        this.screenshotLoading = false;
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
    formatDatetime(date) {
      return date.toLocaleString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    formatTime(value) {
      return String(value || "").slice(0, 5);
    },
  },
};
</script>

<style scoped>
/* ── Confirmation card ─────────────────────────────────────────────── */
.confirmation-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  max-width: 480px;
  margin: 0 auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.confirmation-header .confirmation-logo {
  width: 48px;
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

.confirmation-divider {
  border-top: 2px dashed #e0e0e0;
}

.letter-spacing-2 {
  letter-spacing: 0.12em;
}

.logo-resonanz {
  width: auto;
  height: 100%;
}
</style>
