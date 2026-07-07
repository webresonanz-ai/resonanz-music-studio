<template>
  <div class="fade-in-up">
    <!-- ── Page header ─────────────────────────────────────────────────────── -->
    <div class="content-card mb-4">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <p class="text-uppercase text-primary fw-bold small mb-2">TRMS Concert</p>
          <h1 class="display-4 fw-bold mb-3">
            {{ selectedConcert ? selectedConcert.title : "Seated Registration" }}
          </h1>
          <p class="lead text-muted mb-0">
            {{ selectedConcert ? concertScheduleLabel : "Choose your seat and register for the concert." }}
          </p>
          <p v-if="selectedConcert" class="lead text-muted mt-2 mb-0">
            <a href="https://wa.me/628118747755" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
              <i class="bi bi-person-badge me-1"></i>Narahubung: +62 811-8747-755
            </a>
          </p>
        </div>
        <div class="col-lg-5">
          <div class="bg-dark text-white rounded p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <i class="bi bi-grid-3x3-gap display-6 text-warning"></i>
              <div>
                <div class="fw-bold">{{ selectedConcert ? "Konser yang Dipilih" : "Seat Selection" }}</div>
                <div class="text-white-50 small">{{ selectedConcert ? concertTimeLabel : "Pick your preferred seat" }}</div>
              </div>
            </div>
            <p class="mb-0 text-white-50">
              {{ selectedConcert ? (selectedConcert.description || "Select a seat then fill in your details.") : "Loading concert information…" }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Loading ──────────────────────────────────────────────────────────── -->
    <div v-if="loadingSchedule" class="content-card py-5 text-center text-muted">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <div>Loading concert details…</div>
    </div>

    <!-- ── Concert not found ────────────────────────────────────────────────── -->
    <div v-else-if="!selectedConcert" class="content-card">
      <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>Concert not found. Please check the registration link.</span>
        <router-link class="btn btn-sm btn-outline-primary ms-auto" to="/trms/home">Back to Home</router-link>
      </div>
    </div>

    <!-- ── Registration closed ──────────────────────────────────────────────── -->
    <div v-else-if="!selectedConcert.is_open_register" class="content-card py-4 text-center">
      <i class="bi bi-lock-fill display-1 text-muted d-block mb-3 opacity-50"></i>
      <h2 class="h4 fw-bold mb-2">Registration is Closed</h2>
      <p class="text-muted mb-4">
        Registration for <strong>{{ selectedConcert.title }}</strong> is currently not open.<br />Please check back later.
      </p>
      <router-link class="btn btn-outline-primary" to="/trms/home">Back to Home</router-link>
    </div>

    <!-- ── Success confirmation ──────────────────────────────────────────────── -->
    <div v-else-if="registrationResult" class="content-card">
      <div ref="confirmationCard" class="confirmation-card p-4 rounded">
        <div class="confirmation-header text-center mb-4">
          <div class="confirmation-logo mb-2">
            <img src="/logo_resonanz.png" alt="Resonanz Logo" class="logo-resonanz" />
          </div>
          <div class="fw-bold text-uppercase small text-muted letter-spacing-2 mb-1">The Resonanz Music Studio</div>
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
            <div class="col-5 text-muted small">Kursi</div>
            <div class="col-7 small">
              <span class="badge bg-warning text-dark fs-6">{{ registrationResult.seatNumber }}</span>
            </div>
            <div class="col-5 text-muted small">Terdaftar Pada</div>
            <div class="col-7 fw-semibold small">{{ registrationResult.registeredAt }}</div>
          </div>
        </div>
        <div class="confirmation-footer text-center pt-2 border-top">
          <p class="small text-muted mb-1">PDF tiket Anda telah dikirim ke alamat email Anda.</p>
          <p class="small text-danger mb-0">Jika Anda tidak menerima email, harap menunjukkan konfirmasi ini kepada narahubung.</p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-3 mt-4 justify-content-center">
        <button class="btn btn-success btn-lg" :disabled="screenshotLoading" @click="downloadScreenshot">
          <span v-if="screenshotLoading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
          <i v-else class="bi bi-download me-2"></i>
          {{ screenshotLoading ? "Mengunduh..." : "Unduh Konfirmasi" }}
        </button>
        <router-link class="btn btn-outline-secondary btn-lg" to="/trms/home">
          <i class="bi bi-house me-2"></i>Kembali ke Beranda
        </router-link>
      </div>
      <div v-if="screenshotError" class="alert alert-danger mt-3 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>{{ screenshotError }}</span>
      </div>
    </div>


    <!-- ── Main content: Seat picker + Form ─────────────────────────────────── -->
    <template v-else>
      <!-- Step indicator -->
      <div class="content-card mb-3 py-3 px-4">
        <div class="step-indicator">
          <div class="step" :class="{ active: step === 1, done: step > 1 }">
            <span class="step-num">1</span>
            <span class="step-label">Pilih Kursi</span>
          </div>
          <div class="step-connector"></div>
          <div class="step" :class="{ active: step === 2 }">
            <span class="step-num">2</span>
            <span class="step-label">Isi Data</span>
          </div>
        </div>
      </div>

      <!-- STEP 1: Seat layout ──────────────────────────────────────────────── -->
      <div v-if="step === 1" class="content-card">
        <div v-if="loadingSeats" class="py-4 text-center text-muted">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
          Loading seat availability…
        </div>
        <template v-else>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">Pilih Tempat Duduk</h2>
            <div class="seat-legend d-flex flex-wrap gap-3 small">
              <span class="legend-item"><span class="legend-dot available"></span> Tersedia</span>
              <span class="legend-item"><span class="legend-dot taken"></span> Terisi</span>
              <span class="legend-item"><span class="legend-dot selected"></span> Pilihan Anda</span>
            </div>
          </div>
          <!-- Section colour legend -->
          <div v-if="currentLayout" class="d-flex flex-wrap gap-2 mb-3">
            <span
              v-for="sec in currentLayout.sections"
              :key="sec.id"
              class="section-chip"
              :class="`section-${sec.color}`"
            >{{ sec.label }}</span>
          </div>

          <!-- Stage indicator -->
          <div class="stage-bar mb-4">
            <i class="bi bi-music-note-beamed me-2"></i> PANGGUNG
          </div>

          <!-- Seat grid -->
          <div class="seat-scroll">
            <div class="seat-grid">
              <template v-for="sec in layoutSections" :key="sec.id">
                <!-- Section label -->
                <div class="section-divider">
                  <span class="section-label" :class="`section-${sec.color}`">{{ sec.label }}</span>
                </div>
                <div v-for="rowDef in sec.rows" :key="rowDef.row" class="seat-row">
                  <span class="seat-row-label">{{ rowDef.row }}</span>
                  <template v-for="s in rowDef.seats" :key="s">
                    <button
                      type="button"
                      class="seat-btn"
                      :class="[
                        `section-${sec.color}`,
                        {
                          'seat-taken':    isTaken(rowDef.row, s),
                          'seat-selected': isSelected(rowDef.row, s),
                          'seat-available': !isTaken(rowDef.row, s) && !isSelected(rowDef.row, s)
                        }
                      ]"
                      :disabled="isTaken(rowDef.row, s)"
                      :aria-label="`Seat ${rowDef.row}${s}${isTaken(rowDef.row, s) ? ' (taken)' : ''}`"
                      :title="isTaken(rowDef.row, s) ? 'Terisi' : `${rowDef.row}${s}`"
                      @click="selectSeat(rowDef.row, s)"
                    >{{ s }}</button>
                    <span v-if="rowDef.gap && rowDef.gap.includes(s)" class="aisle-gap"></span>
                  </template>
                </div>
              </template>
            </div>
          </div>

            <div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
              <span v-if="selectedSeat" class="badge bg-warning text-dark fs-6 me-2">
                <i class="bi bi-check-circle me-1"></i>Kursi {{ selectedSeat }}
              </span>
              <span class="text-muted small">
                {{ takenSeats.length }} / {{ currentLayout ? currentLayout.totalSeats : '—' }} kursi terisi
              </span>
            </div>
            <button
              class="btn btn-primary"
              :disabled="!selectedSeat"
              @click="goToStep2"
            >
              Lanjutkan <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        </template>
      </div>

      <!-- STEP 2: Registration form ────────────────────────────────────────── -->
      <div v-if="step === 2" class="content-card">
        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded selected-seat-bar">
          <i class="bi bi-grid-3x3-gap text-primary fs-4"></i>
          <div>
            <div class="fw-semibold">Kursi yang Dipilih</div>
            <span class="badge bg-warning text-dark fs-5">{{ selectedSeat }}</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" @click="step = 1">
            <i class="bi bi-arrow-left me-1"></i>Ganti Kursi
          </button>
        </div>

        <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ errorMessage }}</span>
        </div>

        <form @submit.prevent="submitRegistration">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="seatName" class="form-label">Nama Lengkap (Sesuai KTP)</label>
              <input id="seatName" v-model.trim="form.name" class="form-control" type="text" autocomplete="name" required />
              <div class="form-text">1 tiket hanya berlaku untuk 1 orang.</div>
            </div>
            <div class="col-md-6">
              <label for="seatEmail" class="form-label">Email</label>
              <input id="seatEmail" v-model.trim="form.email" class="form-control" type="email" autocomplete="email" required />
              <div class="form-text">Tiket akan dikirim ke alamat email ini.</div>
            </div>
            <div class="col-12">
              <label for="seatPhone" class="form-label">Nomor HP</label>
              <input id="seatPhone" v-model.trim="form.phone" class="form-control" type="tel" autocomplete="tel" required />
            </div>
          </div>
          <div class="d-flex gap-3 mt-4">
            <button class="btn btn-primary btn-lg" type="submit" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
              <i v-else class="bi bi-send-check me-2"></i>
              {{ loading ? "Mengirim..." : "Kirim Registrasi" }}
            </button>
          </div>
        </form>
      </div>
    </template>

  </div>
</template>

<script>
import html2canvas from "html2canvas";
import { useTrmsStore } from "../../stores/api";
import { useBannerStore } from "../../stores/banner";
import { getLayoutById } from "../../data/concertLayouts.js";

const emptyForm = () => ({ name: "", email: "", phone: "" });

export default {
  name: "SeatedRegistration",
  setup() {
    return {
      trmsStore: useTrmsStore(),
      bannerStore: useBannerStore(),
    };
  },
  data() {
    return {
      step: 1,
      selectedConcert: null,
      takenSeats: [],
      selectedSeat: null,
      form: emptyForm(),
      loading: false,
      loadingSchedule: false,
      loadingSeats: false,
      errorMessage: "",
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
      return `${this.formatTime(this.selectedConcert.start_time)} – ${this.formatTime(this.selectedConcert.end_time)}`;
    },
    currentLayout() {
      return this.selectedConcert?.seat_layout_id
        ? getLayoutById(this.selectedConcert.seat_layout_id)
        : null;
    },
    layoutSections() {
      return this.currentLayout?.sections ?? [];
    },
  },
  watch: {
    concertCodeParam() {
      this.loadSelectedConcert();
    },
    selectedConcert: {
      handler(concert) {
        if (concert?.banner_url) {
          this.bannerStore.setBanner(concert.banner_url);
        } else {
          this.bannerStore.clearBanner();
        }
      },
    },
  },
  async mounted() {
    await this.loadSelectedConcert();
  },
  beforeUnmount() {
    this.bannerStore.clearBanner();
  },
  methods: {
    rowLabel(index) {
      let label = "";
      let n = index;
      do {
        label = String.fromCharCode(65 + (n % 26)) + label;
        n = Math.floor(n / 26) - 1;
      } while (n >= 0);
      return label;
    },
    seatId(row, seatNum) {
      return `${row}${seatNum}`;
    },
    isTaken(row, seatNum) {
      return this.takenSeats.includes(this.seatId(row, seatNum));
    },
    isSelected(row, seatNum) {
      return this.selectedSeat === this.seatId(row, seatNum);
    },
    selectSeat(row, seatNum) {
      const id = this.seatId(row, seatNum);
      this.selectedSeat = this.selectedSeat === id ? null : id;
    },
    goToStep2() {
      if (!this.selectedSeat) return;
      this.errorMessage = "";
      this.step = 2;
    },
    async loadSelectedConcert() {
      this.selectedConcert = null;
      this.takenSeats = [];
      this.selectedSeat = null;
      this.step = 1;

      if (!this.concertCodeParam) return;

      this.loadingSchedule = true;
      try {
        await this.trmsStore.fetchSchedules();
        const todayKey = this.toDateKey(new Date());
        this.selectedConcert = this.trmsStore.schedules
          .filter((s) => s.type === "concert" && s.date >= todayKey)
          .find(
            (s) =>
              String(s.concert_code || "").toUpperCase() ===
              String(this.concertCodeParam).toUpperCase()
          ) || null;

        // If concert found but NOT seat-assigned, redirect back to normal reg
        if (this.selectedConcert && !this.selectedConcert.is_seat_assign) {
          this.$router.replace(`/concert-reg/${this.concertCodeParam}`);
          return;
        }

        if (this.selectedConcert?.is_open_register) {
          await this.loadSeats();
        }
      } finally {
        this.loadingSchedule = false;
      }
    },
    async loadSeats() {
      if (!this.selectedConcert) return;
      this.loadingSeats = true;
      try {
        this.takenSeats = await this.trmsStore.fetchConcertSeats(this.selectedConcert.id);
      } catch {
        this.takenSeats = [];
      } finally {
        this.loadingSeats = false;
      }
    },
    async submitRegistration() {
      this.loading = true;
      this.errorMessage = "";
      try {
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
          seat_number: this.selectedSeat,
          created_at: localTimestamp,
        };

        const response = await this.trmsStore.submitConcertRegistration(payload);

        this.registrationResult = {
          id: response?.id ?? "—",
          name: payload.name,
          email: payload.email,
          phone: payload.phone,
          seatNumber: this.selectedSeat,
          concertTitle: this.selectedConcert.title,
          concertDate: this.formatDate(this.selectedConcert.date),
          concertTime: this.concertTimeLabel,
          registeredAt: this.formatDatetime(now),
        };

        this.form = emptyForm();

        this.$nextTick(() => {
          this.downloadScreenshot();
        });
      } catch (error) {
        this.errorMessage = error.message || "Unable to submit registration.";
        // Refresh seats in case the chosen seat was just taken by someone else
        await this.loadSeats();
      } finally {
        this.loading = false;
      }
    },
    async downloadScreenshot() {
      this.screenshotLoading = true;
      this.screenshotError = "";
      try {
        const card = this.$refs.confirmationCard;
        if (!card) throw new Error("Confirmation card not found.");
        const canvas = await html2canvas(card, {
          backgroundColor: "#ffffff",
          scale: 2,
          useCORS: true,
          logging: false,
        });
        const dataUrl = canvas.toDataURL("image/png");
        const link = document.createElement("a");
        const safeName = (this.registrationResult?.name || "registration")
          .replace(/\s+/g, "_")
          .replace(/[^a-z0-9_-]/gi, "");
        link.href = dataUrl;
        link.download = `concert_ticket_${safeName}_${this.registrationResult?.seatNumber || ""}.png`;
        link.click();
      } catch (error) {
        this.screenshotError = "Could not save screenshot. Please try again.";
      } finally {
        this.screenshotLoading = false;
      }
    },
    toDateKey(date) {
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    },
    formatDate(dateStr) {
      if (!dateStr) return "";
      const [y, m, d] = dateStr.split("-").map(Number);
      return new Date(y, m - 1, d).toLocaleDateString("id-ID", {
        weekday: "long", year: "numeric", month: "long", day: "numeric",
      });
    },
    formatDatetime(date) {
      return date.toLocaleString("id-ID", {
        day: "2-digit", month: "long", year: "numeric",
        hour: "2-digit", minute: "2-digit",
      });
    },
    formatTime(value) {
      return String(value || "").slice(0, 5);
    },
  },
};
</script>

<style scoped>
/* ── Step indicator ──────────────────────────────────────── */
.step-indicator {
  display: flex;
  align-items: center;
  gap: 0;
}

.step {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  opacity: 0.45;
}

.step.active,
.step.done {
  opacity: 1;
}

.step-num {
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  background: var(--bs-secondary);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
}

.step.active .step-num {
  background: var(--bs-primary);
}

.step.done .step-num {
  background: var(--bs-success);
}

.step-label {
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
}

.step-connector {
  flex: 1;
  height: 2px;
  background: var(--hairline-color);
  margin: 0 0.75rem;
  min-width: 2rem;
}

/* ── Stage bar ───────────────────────────────────────────── */
.stage-bar {
  background: linear-gradient(90deg, rgba(127, 36, 50, 0.18), rgba(200, 164, 93, 0.18));
  border: 1px solid rgba(200, 164, 93, 0.35);
  border-radius: 6px;
  text-align: center;
  padding: 0.5rem 1rem;
  font-weight: 700;
  font-size: 0.8rem;
  letter-spacing: 0.12em;
  color: var(--accent-color);
  text-transform: uppercase;
}

/* ── Seat grid ───────────────────────────────────────────── */
.seat-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 0.5rem;
}

.seat-grid {
  display: inline-flex;
  flex-direction: column;
  gap: 6px;
  min-width: max-content;
}

.seat-row {
  display: flex;
  align-items: center;
  gap: 5px;
}

.seat-row-label {
  width: 1.5rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--accent-color);
  text-align: center;
  flex-shrink: 0;
}

.seat-btn {
  width: 2.1rem;
  height: 2.1rem;
  border-radius: 5px;
  border: 1.5px solid transparent;
  font-size: 0.65rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, transform 0.1s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  padding: 0;
}

.seat-available {
  background: rgba(13, 110, 253, 0.07);
  border-color: rgba(13, 110, 253, 0.3);
  color: #0d6efd;
}

.seat-available:hover {
  background: rgba(13, 110, 253, 0.2);
  border-color: #0d6efd;
  transform: scale(1.12);
}

/* Section-coloured available seats */
.seat-btn.section-gold.seat-available  { background: rgba(200,164,93,0.12); border-color: rgba(200,164,93,0.4); color: #7a5c00; }
.seat-btn.section-blue.seat-available  { background: rgba(13,110,253,0.08); border-color: rgba(13,110,253,0.3); color: #0a4bbf; }
.seat-btn.section-green.seat-available { background: rgba(25,135,84,0.08);  border-color: rgba(25,135,84,0.3);  color: #0e5c38; }
.seat-btn.section-red.seat-available   { background: rgba(220,53,69,0.08);  border-color: rgba(220,53,69,0.3);  color: #8b1a25; }

.seat-btn.section-gold.seat-available:hover  { background: rgba(200,164,93,0.28); border-color: #c8a45d; transform: scale(1.12); }
.seat-btn.section-blue.seat-available:hover  { background: rgba(13,110,253,0.22); border-color: #0d6efd; transform: scale(1.12); }
.seat-btn.section-green.seat-available:hover { background: rgba(25,135,84,0.22);  border-color: #198754; transform: scale(1.12); }
.seat-btn.section-red.seat-available:hover   { background: rgba(220,53,69,0.22);  border-color: #dc3545; transform: scale(1.12); }

.seat-taken {
  background: rgba(108, 117, 125, 0.15);
  border-color: rgba(108, 117, 125, 0.2);
  color: #aaa;
  cursor: not-allowed;
  opacity: 0.55;
}

.seat-selected {
  background: var(--gold-color, #c8a45d);
  border-color: var(--accent-color, #7f2432);
  color: #fff;
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(200, 164, 93, 0.5);
}

/* ── Section divider row ─────────────────────────────────── */
.section-divider {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 10px 0 4px;
}

.section-divider::before,
.section-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--hairline-color, #e0e0e0);
}

.section-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  padding: 0.15em 0.6em;
  border-radius: 20px;
  text-transform: uppercase;
  white-space: nowrap;
}

.section-gold  { background: rgba(200,164,93,0.18); color:#7a5c00; border:1px solid rgba(200,164,93,0.4); }
.section-blue  { background: rgba(13,110,253,0.12); color:#0a4bbf; border:1px solid rgba(13,110,253,0.3); }
.section-green { background: rgba(25,135,84,0.12);  color:#0e5c38; border:1px solid rgba(25,135,84,0.3); }
.section-red   { background: rgba(220,53,69,0.12);  color:#8b1a25; border:1px solid rgba(220,53,69,0.3); }

/* ── Aisle gap ───────────────────────────────────────────── */
.aisle-gap {
  width: 0.9rem;
  flex-shrink: 0;
}

/* ── Section chip (legend) ───────────────────────────────── */
.section-chip {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2em 0.65em;
  border-radius: 20px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

/* ── Legend ──────────────────────────────────────────────── */
.seat-legend {
  align-items: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.legend-dot {
  width: 0.85rem;
  height: 0.85rem;
  border-radius: 3px;
  border: 1.5px solid transparent;
  display: inline-block;
}

.legend-dot.available {
  background: rgba(13, 110, 253, 0.1);
  border-color: rgba(13, 110, 253, 0.35);
}

.legend-dot.taken {
  background: rgba(108, 117, 125, 0.2);
  border-color: rgba(108, 117, 125, 0.25);
  opacity: 0.55;
}

.legend-dot.selected {
  background: var(--gold-color, #c8a45d);
  border-color: var(--accent-color, #7f2432);
}

/* ── Selected seat bar ───────────────────────────────────── */
.selected-seat-bar {
  background: rgba(var(--bs-warning-rgb), 0.08);
  border: 1px solid rgba(var(--bs-warning-rgb), 0.25);
}

/* ── Confirmation card ───────────────────────────────────── */
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

.logo-resonanz {
  width: auto;
  height: 100%;
}

.confirmation-divider {
  border-top: 2px dashed #e0e0e0;
}

.letter-spacing-2 {
  letter-spacing: 0.12em;
}
</style>
