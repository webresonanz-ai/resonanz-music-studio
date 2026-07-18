<template>
  <div class="fade-in-up">

    <!-- ── Page header ──────────────────────────────────────────────────────── -->
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

    <!-- ── Loading schedule ──────────────────────────────────────────────────── -->
    <div v-if="loadingSchedule" class="content-card py-5 text-center text-muted">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <div>Loading concert details…</div>
    </div>

    <!-- ── Concert not found ─────────────────────────────────────────────────── -->
    <div v-else-if="!selectedConcert" class="content-card">
      <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>Concert not found. Please check the registration link.</span>
        <router-link class="btn btn-sm btn-outline-primary ms-auto" to="/trms/home">Back to Home</router-link>
      </div>
    </div>

    <!-- ── Registration closed ───────────────────────────────────────────────── -->
    <div v-else-if="!selectedConcert.is_open_register" class="content-card py-4 text-center">
      <i class="bi bi-lock-fill display-1 text-muted d-block mb-3 opacity-50"></i>
      <h2 class="h4 fw-bold mb-2">Registration is Closed</h2>
      <p class="text-muted mb-4">
        Registration for <strong>{{ selectedConcert.title }}</strong> is currently not open.<br />Please check back later.
      </p>
      <router-link class="btn btn-outline-primary" to="/trms/home">Back to Home</router-link>
    </div>

    <!-- ── LOGIN WALL ─────────────────────────────────────────────────────────
         Shown when concert is open but user is not logged in.
         Inline mini login so user doesn't lose the page context.
    ──────────────────────────────────────────────────────────────────────────── -->
    <div v-else-if="!authStore.user" class="content-card">
      <div class="login-wall">
        <div class="login-wall-icon">
          <i class="bi bi-person-lock"></i>
        </div>
        <h2 class="h4 fw-bold mb-1">Login Required</h2>
        <p class="text-muted mb-4">
          Kamu perlu login untuk memilih kursi dan mendaftar konser ini.
        </p>

        <!-- Inline login form -->
        <form class="login-wall-form" @submit.prevent="doLogin">
          <div v-if="loginError" class="alert alert-danger py-2 small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ loginError }}
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input v-model.trim="loginForm.email" type="email" class="form-control" autocomplete="email" required />
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input v-model="loginForm.password" type="password" class="form-control" autocomplete="current-password" required />
          </div>
          <button class="btn btn-primary w-100" type="submit" :disabled="authStore.loading">
            <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-box-arrow-in-right me-2"></i>
            {{ authStore.loading ? 'Logging in…' : 'Login &amp; Pilih Kursi' }}
          </button>
          <div class="text-center mt-3 small text-muted">
            Belum punya akun?
            <router-link to="/auth" class="fw-semibold">Daftar di sini</router-link>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Success confirmation ──────────────────────────────────────────────── -->
    <div v-else-if="registrationResults.length > 0" class="content-card">
      <div ref="confirmationCard" class="confirmation-card p-4 rounded">
        <div class="confirmation-header text-center mb-4">
          <div class="confirmation-logo mb-2">
            <img src="/logo_resonanz.webp" alt="Resonanz Logo" class="logo-resonanz" />
          </div>
          <div class="fw-bold text-uppercase small text-muted letter-spacing-2 mb-1">The Resonanz Music Studio</div>
          <h2 class="h4 fw-bold mb-0">Konfirmasi Registrasi</h2>
          <div class="text-success mt-1 small">
            <i class="bi bi-check-circle-fill me-1"></i>
            {{ registrationResults.length }} kursi berhasil dipesan.
          </div>
        </div>
        <div class="confirmation-divider my-3"></div>
        <div class="confirmation-details mb-4">
          <div class="row g-2">
            <div class="col-5 text-muted small">Konser</div>
            <div class="col-7 fw-semibold small">{{ registrationResults[0].concertTitle }}</div>
            <div class="col-5 text-muted small">Tanggal</div>
            <div class="col-7 fw-semibold small">{{ registrationResults[0].concertDate }}</div>
            <div class="col-5 text-muted small">Waktu</div>
            <div class="col-7 fw-semibold small">{{ registrationResults[0].concertTime }}</div>
            <div class="col-12"><hr class="my-2" /></div>
            <div class="col-5 text-muted small">Nama</div>
            <div class="col-7 fw-semibold small">{{ registrationResults[0].name }}</div>
            <div class="col-5 text-muted small">Email</div>
            <div class="col-7 fw-semibold small text-break">{{ registrationResults[0].email }}</div>
            <div class="col-5 text-muted small">Nomor HP</div>
            <div class="col-7 fw-semibold small">{{ registrationResults[0].phone }}</div>
            <div class="col-5 text-muted small">Kursi</div>
            <div class="col-7 small d-flex flex-wrap gap-1">
              <span v-for="r in registrationResults" :key="r.seatNumber" class="badge bg-warning text-dark fs-6">
                {{ r.seatNumber }}
              </span>
            </div>
            <div class="col-5 text-muted small">Terdaftar Pada</div>
            <div class="col-7 fw-semibold small">{{ registrationResults[0].registeredAt }}</div>
          </div>
        </div>
        <div class="confirmation-footer text-center pt-2 border-top">
          <p class="small text-muted mb-1">PDF tiket Anda telah dikirim ke alamat email Anda.</p>
          <p class="small text-danger mb-0">Jika Anda tidak menerima email, harap menunjukkan konfirmasi ini kepada narahubung.</p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-3 mt-4 justify-content-center">
        <button class="btn btn-success btn-lg" :disabled="screenshotLoading" @click="downloadScreenshot">
          <span v-if="screenshotLoading" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="bi bi-download me-2"></i>
          {{ screenshotLoading ? "Mengunduh..." : "Unduh Konfirmasi" }}
        </button>
        <router-link class="btn btn-outline-secondary btn-lg" to="/trms/home">
          <i class="bi bi-house me-2"></i>Kembali ke Beranda
        </router-link>
      </div>
      <div v-if="screenshotError" class="alert alert-danger mt-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ screenshotError }}
      </div>
    </div>

    <!-- ── Main flow: Seat picker + Form ─────────────────────────────────────── -->
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

      <!-- ══ STEP 1: Seat map ══════════════════════════════════════════════════ -->
      <div v-if="step === 1" class="content-card">
        <div v-if="loadingSeats" class="py-4 text-center text-muted">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
          Loading seat availability…
        </div>
        <template v-else>

          <!-- Header + legend -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">Pilih Tempat Duduk</h2>
            <div class="seat-legend d-flex flex-wrap gap-3 small">
              <span class="legend-item"><span class="legend-dot available"></span> Tersedia</span>
              <span class="legend-item"><span class="legend-dot held"></span> Ditahan</span>
              <span class="legend-item"><span class="legend-dot taken"></span> Terisi</span>
              <span class="legend-item"><span class="legend-dot selected"></span> Pilihan Anda</span>
            </div>
          </div>

          <!-- Section chips -->
          <div v-if="currentLayout" class="d-flex flex-wrap gap-2 mb-3">
            <span v-for="sec in currentLayout.sections" :key="sec.id" class="section-chip" :class="`section-${sec.color}`">
              {{ sec.label }}
            </span>
          </div>

          <!-- Hold expiry notice -->
          <div v-if="cart.length > 0 && holdExpiresAt" class="hold-notice mb-3">
            <i class="bi bi-clock me-1"></i>
            Kursi Anda ditahan hingga <strong>{{ formatTime(holdExpiresAt) }}</strong>.
            Selesaikan pembayaran sebelum waktu habis.
          </div>

          <!-- Stage bar -->
          <div class="stage-bar mb-4">
            <i class="bi bi-music-note-beamed me-2"></i> PANGGUNG
          </div>

          <!-- Custom layout (absolute positions) -->
          <div v-if="currentLayout && currentLayout.isCustom" class="seat-scroll">
            <div class="custom-seat-canvas" :style="customCanvasStyle">
              <template v-for="sec in layoutSections" :key="sec.id">
                <template v-for="rowDef in sec.rows" :key="rowDef.row">
                  <button
                    v-for="(col, si) in (rowDef._cols || [])"
                    :key="`${rowDef.row}-${col}`"
                    type="button"
                    class="seat-btn custom-seat"
                    :class="seatClasses(sec.color, rowDef.row, si + 1)"
                    :style="customSeatStyle(rowDef, col)"
                    :disabled="isTaken(rowDef.row, si + 1) || isHoldingByOther(rowDef.row, si + 1)"
                    :aria-label="`Seat ${rowDef.row}${si + 1}`"
                    :title="seatTitle(rowDef.row, si + 1)"
                    @click="toggleSeat(rowDef.row, si + 1)"
                  >{{ si + 1 }}</button>
                </template>
              </template>
            </div>
          </div>

          <!-- Preset layout (row-based) -->
          <div v-else class="seat-scroll">
            <div class="seat-grid">
              <template v-for="sec in layoutSections" :key="sec.id">
                <div class="section-divider">
                  <span class="section-label" :class="`section-${sec.color}`">{{ sec.label }}</span>
                </div>
                <div v-for="rowDef in sec.rows" :key="rowDef.row" class="seat-row">
                  <span class="seat-row-label">{{ rowDef.row }}</span>
                  <template v-for="s in rowDef.seats" :key="s">
                    <button
                      type="button"
                      class="seat-btn"
                      :class="seatClasses(sec.color, rowDef.row, s)"
                      :disabled="isTaken(rowDef.row, s) || isHoldingByOther(rowDef.row, s)"
                      :title="seatTitle(rowDef.row, s)"
                      @click="toggleSeat(rowDef.row, s)"
                    >{{ s }}</button>
                    <span v-if="rowDef.gap && rowDef.gap.includes(s)" class="aisle-gap"></span>
                  </template>
                </div>
              </template>
            </div>
          </div>

        </template>

        <!-- Cart + next button -->
        <div v-if="cart.length > 0" class="cart-bar mt-4 d-flex align-items-center justify-content-between flex-wrap gap-3 p-3 rounded">
          <div>
            <div class="fw-semibold mb-1">Kursi yang Dipilih</div>
            <div class="d-flex flex-wrap gap-1">
              <span
                v-for="seatId in cart"
                :key="seatId"
                class="badge bg-warning text-dark fs-6 d-inline-flex align-items-center gap-1"
              >
                {{ seatId }}
                <button type="button" class="btn-close btn-close-sm ms-1" aria-label="Remove" @click="removeSeat(seatId)"></button>
              </span>
            </div>
            <div v-if="holdError" class="text-danger small mt-1">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ holdError }}
            </div>
          </div>
          <button class="btn btn-primary btn-lg flex-shrink-0" type="button" @click="goToStep2">
            <i class="bi bi-arrow-right me-2"></i>Lanjut Isi Data ({{ cart.length }} kursi)
          </button>
        </div>

        <div v-else-if="holdError" class="alert alert-danger mt-3" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ holdError }}
        </div>

      </div>
      <!-- end step 1 -->

      <!-- ══ STEP 2: Registration form ════════════════════════════════════════ -->
      <div v-if="step === 2" class="content-card">

        <div class="selected-seat-bar d-flex align-items-center gap-3 mb-4 p-3 rounded flex-wrap">
          <i class="bi bi-grid-3x3-gap text-primary fs-4"></i>
          <div class="flex-grow-1">
            <div class="fw-semibold mb-1">Kursi yang Dipilih</div>
            <div class="d-flex flex-wrap gap-1">
              <span v-for="seatId in cart" :key="seatId" class="badge bg-warning text-dark fs-6">{{ seatId }}</span>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" @click="step = 1">
            <i class="bi bi-arrow-left me-1"></i>Ganti Kursi
          </button>
        </div>

        <!-- Hold expiry reminder -->
        <div v-if="holdExpiresAt" class="hold-notice mb-4">
          <i class="bi bi-clock me-1"></i>
          Kursi Anda ditahan hingga <strong>{{ formatTime(holdExpiresAt) }}</strong>.
          Selesaikan registrasi sebelum waktu habis.
        </div>

        <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ errorMessage }}</span>
        </div>

        <div v-for="(seat, idx) in cart" :key="seat" class="seat-form-block">
          <div class="seat-form-header">
            <span class="badge bg-warning text-dark me-2">{{ seat }}</span>
            <span class="fw-semibold small">Data Penumpang #{{ idx + 1 }}</span>
          </div>
          <div class="row g-3 mt-0">
            <div class="col-md-5">
              <label :for="`name-${idx}`" class="form-label">Nama Lengkap (Sesuai KTP)</label>
              <input :id="`name-${idx}`" v-model.trim="forms[idx].name" class="form-control" type="text" autocomplete="name" required />
              <div class="form-text">1 tiket hanya berlaku untuk 1 orang.</div>
            </div>
            <div class="col-md-4">
              <label :for="`email-${idx}`" class="form-label">Email</label>
              <input :id="`email-${idx}`" v-model.trim="forms[idx].email" class="form-control" type="email" autocomplete="email" required />
              <div class="form-text">Tiket dikirim ke email ini.</div>
            </div>
            <div class="col-md-3">
              <label :for="`phone-${idx}`" class="form-label">Nomor HP</label>
              <input :id="`phone-${idx}`" v-model.trim="forms[idx].phone" class="form-control" type="tel" autocomplete="tel" required />
            </div>
          </div>
        </div>

        <div class="d-flex gap-3 mt-4">
          <button class="btn btn-primary btn-lg" type="button" :disabled="loading" @click="submitRegistration">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-send-check me-2"></i>
            {{ loading ? "Mengirim..." : `Kirim ${cart.length} Registrasi` }}
          </button>
        </div>

      </div>
      <!-- end step 2 -->

    </template>
  </div>
</template>

<script>
import html2canvas from "html2canvas";
import { useTrmsStore } from "../../stores/api";
import { useBannerStore } from "../../stores/banner";
import { useAuthStore } from "../../stores/auth";

const MAX_SEATS = 5;

export default {
  name: "SeatedRegistration",
  setup() {
    return {
      trmsStore: useTrmsStore(),
      bannerStore: useBannerStore(),
      authStore: useAuthStore(),
    };
  },
  data() {
    return {
      MAX_SEATS,
      loadingSchedule: false,
      loadingSeats: false,
      errorMessage: "",
      selectedConcert: null,
      currentLayout: null,
      /** Seats unavailable to this user (confirmed + held by others). */
      takenSeats: [],
      blockedSeats: new Set(),
      /** Seat ids the current user currently holds. */
      cart: [],
      holdExpiresAt: null,
      holdError: "",
      step: 1,
      forms: [],
      loading: false,
      /** Populated after a successful submission – drives the confirmation card. */
      registrationResults: [],
      screenshotLoading: false,
      screenshotError: "",
      loginForm: { email: "", password: "" },
      loginError: "",
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
    layoutSections() {
      return this.currentLayout?.sections || [];
    },
    customCanvasStyle() {
      const cols = this.currentLayout?._gridCols || 10;
      return {
        gridTemplateColumns: `repeat(${cols}, 42px)`,
        gridAutoRows: "42px",
        gap: "6px",
      };
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
      immediate: false,
    },
    cart() {
      this.syncForms();
    },
    "authStore.user"(user) {
      // When the user logs in from the inline wall, reload their holds.
      if (user && this.selectedConcert) {
        this.loadSeats();
      }
    },
  },
  mounted() {
    this.loadSelectedConcert();
  },
  beforeUnmount() {
    this.bannerStore.clearBanner();
  },
  methods: {
    async loadSelectedConcert() {
      this.selectedConcert = null;
      this.currentLayout = null;

      if (!this.concertCodeParam) {
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

        if (this.selectedConcert && !this.selectedConcert.is_seat_assign) {
          // Not a seated concert – fall back to the standard registration form.
          this.$router.replace(`/concert-reg/${this.concertCodeParam}`);
          return;
        }

        if (this.selectedConcert) {
          await this.loadSeats();
        }
      } catch (error) {
        this.errorMessage = error.message || "Unable to load selected concert.";
      } finally {
        this.loadingSchedule = false;
      }
    },

    async loadSeats() {
      const scheduleId = this.selectedConcert?.id;
      if (!scheduleId) return;

      this.loadingSeats = true;
      this.holdError = "";
      try {
        // Layout
        if (this.selectedConcert.seat_layout_id) {
          try {
            this.currentLayout = await this.trmsStore.fetchCustomLayout(
              this.selectedConcert.seat_layout_id,
            );
          } catch (err) {
            console.warn("[SeatedRegistration] layout load failed:", err);
            this.currentLayout = null;
          }
        }

        // Blocked seats (confirmed + held by others) — public endpoint.
        const blocked = await this.trmsStore.fetchConcertSeats(scheduleId);
        const blockedList = Array.isArray(blocked) ? blocked : [];

        // This user's active holds (requires auth).
        let myHolds = [];
        if (this.authStore.user) {
          try {
            myHolds = await this.trmsStore.fetchMyHolds(scheduleId);
          } catch (err) {
            console.warn("[SeatedRegistration] my holds load failed:", err);
          }
        }

        const mySeatNumbers = (myHolds || []).map((h) => h.seat_number);
        this.cart = mySeatNumbers.slice();
        this.holdExpiresAt =
          myHolds && myHolds.length
            ? myHolds.reduce(
                (min, h) => (!min || h.expires_at < min ? h.expires_at : min),
                null,
              )
            : null;

        const mySet = new Set(mySeatNumbers);
        this.takenSeats = blockedList.filter((s) => !mySet.has(s));
        this.blockedSeats = new Set(this.takenSeats);
      } catch (error) {
        this.errorMessage = error.message || "Unable to load seat availability.";
      } finally {
        this.loadingSeats = false;
      }
    },

    seatId(rowLabel, seatNum) {
      return `${rowLabel}${seatNum}`;
    },

    isTaken(rowLabel, seatNum) {
      return this.blockedSeats.has(this.seatId(rowLabel, seatNum));
    },

    isHoldingByOther(rowLabel, seatNum) {
      return this.blockedSeats.has(this.seatId(rowLabel, seatNum));
    },

    seatClasses(color, rowLabel, seatNum) {
      const id = this.seatId(rowLabel, seatNum);
      const classes = [`section-${color}`];
      if (this.cart.includes(id)) {
        classes.push("selected");
      } else if (this.blockedSeats.has(id)) {
        classes.push("held");
      } else {
        classes.push("available");
      }
      return classes;
    },

    seatTitle(rowLabel, seatNum) {
      return `Kursi ${rowLabel}${seatNum}`;
    },

    customSeatStyle(rowDef, col) {
      return {
        gridColumn: col,
        gridRow: rowDef._gridRow,
      };
    },

    async toggleSeat(rowLabel, seatNum) {
      const id = this.seatId(rowLabel, seatNum);
      const scheduleId = this.selectedConcert?.id;
      if (!scheduleId) return;

      // Already selected → release it.
      if (this.cart.includes(id)) {
        await this.releaseSeat(id);
        return;
      }

      if (this.cart.length >= MAX_SEATS) {
        this.holdError = `Maksimal ${MAX_SEATS} kursi per transaksi.`;
        return;
      }

      this.holdError = "";
      try {
        const res = await this.trmsStore.holdSeat(scheduleId, id);
        this.cart.push(id);
        if (res?.expires_at) {
          this.holdExpiresAt = res.expires_at;
        }
      } catch (error) {
        this.holdError = error.message || "Kursi tidak dapat ditahan.";
      }
    },

    async removeSeat(id) {
      this.holdError = "";
      await this.releaseSeat(id);
    },

    async releaseSeat(id) {
      const scheduleId = this.selectedConcert?.id;
      const idx = this.cart.indexOf(id);
      if (idx !== -1) this.cart.splice(idx, 1);
      if (this.cart.length === 0) this.holdExpiresAt = null;

      if (!scheduleId) return;
      try {
        await this.trmsStore.releaseSeat(scheduleId, id);
      } catch (error) {
        this.holdError = error.message || "Gagal melepas kursi.";
      }
    },

    goToStep2() {
      if (this.cart.length === 0) return;
      this.errorMessage = "";
      this.syncForms();
      this.step = 2;
    },

    syncForms() {
      const next = [];
      for (let i = 0; i < this.cart.length; i++) {
        next.push(this.forms[i] || { name: "", email: "", phone: "" });
      }
      this.forms = next;
    },

    async submitRegistration() {
      if (!this.selectedConcert) {
        this.errorMessage = "Please select a concert before submitting registration.";
        return;
      }
      if (this.cart.length === 0) {
        this.errorMessage = "Pilih kursi terlebih dahulu.";
        return;
      }

      this.loading = true;
      this.errorMessage = "";

      const now = new Date();
      const localTimestamp = this.toLocalTimestamp(now);
      const results = [];

      try {
        for (let i = 0; i < this.cart.length; i++) {
          const seatId = this.cart[i];
          const form = this.forms[i] || { name: "", email: "", phone: "" };

          const payload = {
            name: form.name,
            email: form.email,
            phone: form.phone,
            concert_title: this.selectedConcert.title,
            schedule_id: this.selectedConcert.id,
            ticket_quantity: 1,
            notes: "Guest",
            seat_number: seatId,
            created_at: localTimestamp,
          };

          await this.trmsStore.submitConcertRegistration(payload);

          results.push({
            seatNumber: seatId,
            concertTitle: this.selectedConcert.title,
            concertDate: this.formatDate(this.selectedConcert.date),
            concertTime: this.concertTimeLabel,
            name: form.name,
            email: form.email,
            phone: form.phone,
            registeredAt: this.formatDatetime(now),
          });
        }

        this.registrationResults = results;
        this.step = 1;
        this.$nextTick(() => this.downloadScreenshot());
      } catch (error) {
        this.errorMessage = error.message || "Unable to submit registration.";
      } finally {
        this.loading = false;
      }
    },

    async doLogin() {
      this.loginError = "";
      try {
        await this.authStore.login({
          email: this.loginForm.email,
          password: this.loginForm.password,
        });
        this.loginForm = { email: "", password: "" };
        if (this.selectedConcert) {
          await this.loadSeats();
        }
      } catch (error) {
        this.loginError = error.message || "Login gagal. Periksa email dan password.";
      }
    },

    async downloadScreenshot() {
      this.screenshotLoading = true;
      this.screenshotError = "";

      try {
        const card = this.$refs.confirmationCard;
        if (!card) throw new Error("Confirmation card element not found.");

        const canvas = await html2canvas(card, {
          backgroundColor: "#ffffff",
          scale: 2,
          useCORS: true,
          logging: false,
        });

        const dataUrl = canvas.toDataURL("image/png");
        const link = document.createElement("a");
        const safeName = (this.registrationResults[0]?.name || "registration")
          .replace(/\s+/g, "_")
          .replace(/[^a-z0-9_-]/gi, "");
        link.href = dataUrl;
        link.download = `concert_registration_${safeName}.png`;
        link.click();
      } catch (error) {
        this.screenshotError = "Could not save screenshot. Please try again.";
        console.error("[SeatedRegistration] screenshot error:", error);
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
    toLocalTimestamp(date) {
      const p = (n) => String(n).padStart(2, "0");
      return `${date.getFullYear()}-${p(date.getMonth() + 1)}-${p(date.getDate())} ${p(date.getHours())}:${p(date.getMinutes())}:${p(date.getSeconds())}`;
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
      if (!value) return "";
      const str = String(value);
      // If this looks like a full datetime (contains space or T), parse it
      // as UTC and display in the user's local timezone.
      if (str.includes(" ") || str.includes("T")) {
        // PHP gmdate produces "Y-m-d H:i:s" — replace space with T and append Z
        // so the browser treats it as UTC, then formats to local time.
        const iso = str.replace(" ", "T").replace(/Z?$/, "Z");
        const date = new Date(iso);
        if (!isNaN(date.getTime())) {
          return date.toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
          });
        }
      }
      // Plain HH:mm[:ss] string — slice as-is
      return str.slice(0, 5);
    },
  },
};
</script>

<style scoped>
/* ─────────────────────────────────────────────────────────────────────────────
   STEP INDICATOR
───────────────────────────────────────────────────────────────────────────── */
.step-indicator {
  display: flex;
  align-items: center;
  gap: 0;
}

.step {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--muted-color);
  transition: color 0.2s;
}

.step-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  font-size: 0.8rem;
  font-weight: 700;
  border: 2px solid currentColor;
  background: transparent;
  flex-shrink: 0;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
}

.step-label {
  font-size: 0.85rem;
  font-weight: 600;
  white-space: nowrap;
}

.step.active {
  color: var(--gold-color);
}

.step.active .step-num {
  background: var(--gold-color);
  border-color: var(--gold-color);
  color: #fff;
}

.step.done {
  color: #198754;
}

.step.done .step-num {
  background: #198754;
  border-color: #198754;
  color: #fff;
}

.step-connector {
  flex: 1;
  height: 2px;
  min-width: 2rem;
  background: var(--hairline-color);
  margin: 0 0.75rem;
  border-radius: 2px;
}

/* ─────────────────────────────────────────────────────────────────────────────
   SEAT LEGEND
───────────────────────────────────────────────────────────────────────────── */
.seat-legend {
  font-size: 0.78rem;
  color: var(--muted-color);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.legend-dot {
  display: inline-block;
  width: 14px;
  height: 14px;
  border-radius: 3px;
  flex-shrink: 0;
}

.legend-dot.available {
  background: #e9ecef;
  border: 1px solid #ced4da;
}

.legend-dot.held {
  background: #fff3cd;
  border: 1px solid #ffc107;
}

.legend-dot.taken {
  background: #f5c6cb;
  border: 1px solid #dc3545;
}

.legend-dot.selected {
  background: var(--gold-color);
  border: 1px solid #a07840;
}

/* ─────────────────────────────────────────────────────────────────────────────
   SECTION CHIPS
───────────────────────────────────────────────────────────────────────────── */
.section-chip {
  display: inline-block;
  padding: 0.2rem 0.7rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border: 1.5px solid transparent;
}

/* ─────────────────────────────────────────────────────────────────────────────
   STAGE BAR
───────────────────────────────────────────────────────────────────────────── */
.stage-bar {
  text-align: center;
  padding: 0.55rem 1.5rem;
  background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
  color: var(--champagne-color);
  border-radius: var(--radius-md);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  user-select: none;
}

/* ─────────────────────────────────────────────────────────────────────────────
   SEAT SCROLL + GRID
───────────────────────────────────────────────────────────────────────────── */
.seat-scroll {
  overflow-x: auto;
  overflow-y: visible;
  padding-bottom: 0.5rem;
  /* smooth scrollbar */
  scrollbar-width: thin;
  scrollbar-color: var(--gold-color) transparent;
}

.seat-scroll::-webkit-scrollbar {
  height: 5px;
}

.seat-scroll::-webkit-scrollbar-thumb {
  background: var(--gold-color);
  border-radius: 3px;
}

/* preset (row-based) layout */
.seat-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: max-content;
}

.section-divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.section-divider::before,
.section-divider::after {
  content: "";
  flex: 1;
  height: 1px;
  background: var(--hairline-color);
}

.section-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  padding: 0.15rem 0.6rem;
  border-radius: 999px;
  white-space: nowrap;
}

.seat-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.seat-row-label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  min-width: 24px;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--muted-color);
  user-select: none;
}

.aisle-gap {
  display: inline-block;
  width: 16px;
  flex-shrink: 0;
}

/* custom (absolute/grid) layout */
.custom-seat-canvas {
  display: grid;
}

/* ─────────────────────────────────────────────────────────────────────────────
   SEAT BUTTONS
───────────────────────────────────────────────────────────────────────────── */
.seat-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 6px;
  border: 1.5px solid transparent;
  font-size: 0.7rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.1s, box-shadow 0.15s, background 0.15s;
  user-select: none;
  flex-shrink: 0;
  outline: none;
}

.seat-btn.custom-seat {
  width: 36px;
  height: 36px;
}

/* State: available */
.seat-btn.available {
  background: #f0ece4;
  border-color: #d9d4c9;
  color: var(--ink-color);
}

.seat-btn.available:hover:not(:disabled) {
  background: #e2ddd5;
  border-color: var(--gold-color);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(200, 164, 93, 0.3);
}

/* State: held by another */
.seat-btn.held {
  background: #fff3cd;
  border-color: #ffc107;
  color: #856404;
  cursor: not-allowed;
  opacity: 0.85;
}

/* State: taken / confirmed */
.seat-btn:disabled:not(.selected):not(.held) {
  background: #f5c6cb;
  border-color: #f1aeb5;
  color: #842029;
  cursor: not-allowed;
  opacity: 0.8;
}

/* State: selected by this user */
.seat-btn.selected {
  background: var(--gold-color);
  border-color: #a07840;
  color: #fff;
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.35);
  transform: translateY(-1px);
}

.seat-btn.selected:hover:not(:disabled) {
  background: #b8943a;
  transform: translateY(-2px);
}

/* Section tints — overlay a subtle hue per section color */
.seat-btn.section-blue.available   { background: #dbeafe; border-color: #93c5fd; color: #1e3a5f; }
.seat-btn.section-blue.available:hover:not(:disabled) { background: #bfdbfe; border-color: #3b82f6; }
.seat-btn.section-green.available  { background: #dcfce7; border-color: #86efac; color: #14532d; }
.seat-btn.section-green.available:hover:not(:disabled) { background: #bbf7d0; border-color: #22c55e; }
.seat-btn.section-red.available    { background: #fee2e2; border-color: #fca5a5; color: #7f1d1d; }
.seat-btn.section-red.available:hover:not(:disabled) { background: #fecaca; border-color: #ef4444; }
.seat-btn.section-purple.available { background: #ede9fe; border-color: #c4b5fd; color: #3b0764; }
.seat-btn.section-purple.available:hover:not(:disabled) { background: #ddd6fe; border-color: #8b5cf6; }
.seat-btn.section-orange.available { background: #ffedd5; border-color: #fdba74; color: #7c2d12; }
.seat-btn.section-orange.available:hover:not(:disabled) { background: #fed7aa; border-color: #f97316; }
.seat-btn.section-yellow.available { background: #fef9c3; border-color: #fde047; color: #713f12; }
.seat-btn.section-yellow.available:hover:not(:disabled) { background: #fef08a; border-color: #eab308; }
.seat-btn.section-teal.available   { background: #ccfbf1; border-color: #5eead4; color: #134e4a; }
.seat-btn.section-teal.available:hover:not(:disabled) { background: #99f6e4; border-color: #14b8a6; }
.seat-btn.section-pink.available   { background: #fce7f3; border-color: #f9a8d4; color: #831843; }
.seat-btn.section-pink.available:hover:not(:disabled) { background: #fbcfe8; border-color: #ec4899; }
.seat-btn.section-gold.available   { background: #fef3c7; border-color: #fcd34d; color: #78350f; }
.seat-btn.section-gold.available:hover:not(:disabled) { background: #fde68a; border-color: #f59e0b; }

/* Section chip colours to match */
.section-chip.section-blue   { background: #dbeafe; border-color: #93c5fd; color: #1e3a5f; }
.section-chip.section-green  { background: #dcfce7; border-color: #86efac; color: #14532d; }
.section-chip.section-red    { background: #fee2e2; border-color: #fca5a5; color: #7f1d1d; }
.section-chip.section-purple { background: #ede9fe; border-color: #c4b5fd; color: #3b0764; }
.section-chip.section-orange { background: #ffedd5; border-color: #fdba74; color: #7c2d12; }
.section-chip.section-yellow { background: #fef9c3; border-color: #fde047; color: #713f12; }
.section-chip.section-teal   { background: #ccfbf1; border-color: #5eead4; color: #134e4a; }
.section-chip.section-pink   { background: #fce7f3; border-color: #f9a8d4; color: #831843; }
.section-chip.section-gold   { background: #fef3c7; border-color: #fcd34d; color: #78350f; }

.section-label.section-blue   { background: #dbeafe; color: #1e3a5f; }
.section-label.section-green  { background: #dcfce7; color: #14532d; }
.section-label.section-red    { background: #fee2e2; color: #7f1d1d; }
.section-label.section-purple { background: #ede9fe; color: #3b0764; }
.section-label.section-orange { background: #ffedd5; color: #7c2d12; }
.section-label.section-yellow { background: #fef9c3; color: #713f12; }
.section-label.section-teal   { background: #ccfbf1; color: #134e4a; }
.section-label.section-pink   { background: #fce7f3; color: #831843; }
.section-label.section-gold   { background: #fef3c7; color: #78350f; }

/* ─────────────────────────────────────────────────────────────────────────────
   HOLD NOTICE
───────────────────────────────────────────────────────────────────────────── */
.hold-notice {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1rem;
  background: #fff8e1;
  border: 1px solid #ffe082;
  border-radius: var(--radius-md);
  font-size: 0.85rem;
  color: #5a4000;
}

/* ─────────────────────────────────────────────────────────────────────────────
   CART BAR (selected seats + CTA)
───────────────────────────────────────────────────────────────────────────── */
.cart-bar {
  background: linear-gradient(135deg, #fffdf5, #fdf7ea);
  border: 1.5px solid rgba(200, 164, 93, 0.35);
  box-shadow: 0 4px 20px rgba(200, 164, 93, 0.12);
}

.selected-seat-bar {
  background: linear-gradient(135deg, #f8f9fa, #eef0f3);
  border: 1.5px solid var(--hairline-color);
}

/* ─────────────────────────────────────────────────────────────────────────────
   SEAT FORM BLOCKS (step 2)
───────────────────────────────────────────────────────────────────────────── */
.seat-form-block {
  padding: 1.25rem;
  border: 1.5px solid var(--hairline-color);
  border-radius: var(--radius-md);
  margin-bottom: 1rem;
  background: var(--surface-color);
  transition: border-color 0.2s;
}

.seat-form-block:last-of-type {
  margin-bottom: 0;
}

.seat-form-block:focus-within {
  border-color: rgba(200, 164, 93, 0.5);
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.12);
}

.seat-form-header {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.6rem;
  border-bottom: 1px solid var(--hairline-color);
}

/* ─────────────────────────────────────────────────────────────────────────────
   LOGIN WALL
───────────────────────────────────────────────────────────────────────────── */
.login-wall {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem 1rem;
  max-width: 420px;
  margin: 0 auto;
}

.login-wall-icon {
  font-size: 3.5rem;
  color: var(--gold-color);
  margin-bottom: 1rem;
  line-height: 1;
}

.login-wall-form {
  width: 100%;
  text-align: left;
}

/* ─────────────────────────────────────────────────────────────────────────────
   CONFIRMATION CARD
───────────────────────────────────────────────────────────────────────────── */
.confirmation-card {
  background: #fff;
  border: 1.5px solid var(--hairline-color);
  max-width: 500px;
  margin: 0 auto;
}

.logo-resonanz {
  height: 48px;
  width: auto;
  object-fit: contain;
}

.confirmation-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--gold-color), transparent);
  opacity: 0.5;
}

/* ─────────────────────────────────────────────────────────────────────────────
   RESPONSIVE
───────────────────────────────────────────────────────────────────────────── */
@media (max-width: 575.98px) {
  .seat-btn {
    width: 30px;
    height: 30px;
    font-size: 0.62rem;
    border-radius: 4px;
  }

  .seat-btn.custom-seat {
    width: 30px;
    height: 30px;
  }

  .step-label {
    display: none;
  }

  .stage-bar {
    font-size: 0.7rem;
    padding: 0.45rem 0.75rem;
  }

  .cart-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .cart-bar .btn {
    width: 100%;
    justify-content: center;
  }

  .hold-notice {
    font-size: 0.78rem;
  }
}
</style>
