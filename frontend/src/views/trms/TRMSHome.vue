<template>
  <div class="fade-in-up">
    <!-- ── Upcoming Concert Slideshow ──────────────────────────────── -->
    <div v-if="upcomingConcerts.length" class="concert-slider mb-4" aria-label="Upcoming concerts">
      <div class="concert-slider__track" :style="trackStyle">
        <div v-for="(concert, index) in upcomingConcerts" :key="concert.id" class="concert-slide">
          <!-- Banner image or fallback gradient -->
          <div
            class="concert-slide__bg"
            :style="concert.banner_url ? { backgroundImage: `url('${concert.banner_url}')` } : {}"
            :class="{ 'concert-slide__bg--fallback': !concert.banner_url }"
          ></div>

          <!-- Overlay content -->
          <div class="concert-slide__overlay">
            <div class="concert-slide__inner">
              <span class="badge bg-warning text-dark mb-3 px-3 py-2 fs-6">
                <i class="bi bi-music-note-beamed me-1"></i>Upcoming Concert
              </span>
              <h2 class="concert-slide__title">{{ concert.title }}</h2>
              <div class="concert-slide__meta">
                <span><i class="bi bi-calendar3 me-2"></i>{{ formatDate(concert.date) }}</span>
                <span class="mx-2 opacity-50">·</span>
                <span
                  ><i class="bi bi-clock me-2"></i>{{ formatTime(concert.start_time) }} –
                  {{ formatTime(concert.end_time) }}</span
                >
                <template v-if="concert.venue">
                  <span class="mx-2 opacity-50">·</span>
                  <span><i class="bi bi-geo-alt me-2"></i>{{ concert.venue }}</span>
                </template>
              </div>
              <p v-if="concert.description" class="concert-slide__desc">
                {{ concert.description }}
              </p>
              <a
                v-if="concert.is_open_register && concert.is_redirect_url && concert.redirect_url"
                :href="concert.redirect_url"
                class="btn btn-warning btn-lg mt-3 fw-bold"
                target="_blank"
                rel="noopener noreferrer"
              >
                <i class="bi bi-ticket-perforated me-2"></i>Register Now
              </a>
              <router-link
                v-else-if="concert.is_open_register && concert.concert_code"
                :to="
                  concert.is_seat_assign
                    ? `/concert-reg/${concert.concert_code}/seated`
                    : `/concert-reg/${concert.concert_code}`
                "
                class="btn btn-warning btn-lg mt-3 fw-bold"
              >
                <i class="bi bi-ticket-perforated me-2"></i>Register Now
              </router-link>
              <div v-else class="mt-3">
                <span class="badge bg-secondary text-white px-3 py-2 fs-6"
                  >Registration Closed</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dot indicators -->
      <div v-if="upcomingConcerts.length > 1" class="concert-slider__dots" aria-hidden="true">
        <button
          v-for="(_, i) in upcomingConcerts"
          :key="i"
          class="concert-slider__dot"
          :class="{ 'concert-slider__dot--active': activeSlide === i }"
          @click="goToSlide(i)"
          :aria-label="`Go to slide ${i + 1}`"
        ></button>
      </div>

      <!-- Prev / Next arrows (only if more than 1) -->
      <template v-if="upcomingConcerts.length > 1">
        <button
          class="concert-slider__arrow concert-slider__arrow--prev"
          @click="prevSlide"
          aria-label="Previous concert"
        >
          <i class="bi bi-chevron-left"></i>
        </button>
        <button
          class="concert-slider__arrow concert-slider__arrow--next"
          @click="nextSlide"
          aria-label="Next concert"
        >
          <i class="bi bi-chevron-right"></i>
        </button>
      </template>
    </div>

    <!-- ── Latest News ───────────────────────────────────────────────── -->
    <div v-if="latestNews.length" class="mb-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h4 fw-bold mb-0 text-warning">
          <i class="bi bi-newspaper me-2"></i>Latest News
        </h2>
        <router-link to="/trms/news" class="btn btn-outline-gold btn-sm">
          View All <i class="bi bi-arrow-right ms-1"></i>
        </router-link>
      </div>
      <div class="row row-cols-1 row-cols-md-3 g-3">
        <div v-for="article in latestNews" :key="article.id" class="col">
          <div class="card h-100 news-card">
            <div class="card-body d-flex flex-column">
              <div class="d-flex flex-wrap gap-1 mb-2">
                <span
                  class="program-logo-pill"
                  v-for="p in article.program_ids || [article.program_id || 'trms']"
                  :key="p"
                >
                  <img
                    :src="'/' + p + '_white.png'"
                    :alt="programLabel(p)"
                    class="program-logo-img"
                  />
                </span>
              </div>
              <h5 class="card-title text-champagne">{{ article.title }}</h5>
              <p class="card-text flex-grow-1 small text-champagne-muted">
                {{ truncateContent(article.content) }}
              </p>
              <div class="d-flex align-items-center justify-content-between mt-2">
                <small class="text-champagne-muted">
                  <i class="bi bi-calendar3 me-1"></i>{{ formatDate(article.published_at) }}
                </small>
                <router-link :to="'/trms/news'" class="btn btn-sm btn-outline-gold">
                  Read more <i class="bi bi-arrow-right ms-1"></i>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Existing content below ───────────────────────────────────── -->
    <div class="content-card bg-dark">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <h1 class="display-4 fw-bold mb-4 text-warning">The Resonanz Music Studio</h1>
          <p class="lead mb-4 text-champagne">
            Discover your musical potential with world-class instruction and state-of-the-art
            facilities.
          </p>
          <div class="d-flex gap-3">
            <router-link to="/trms/courses-fees" class="btn btn-primary btn-lg">
              Explore Courses
            </router-link>
            <router-link to="/trms/contact" class="btn btn-outline-gold btn-lg">
              Contact Us
            </router-link>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="bg-dark-card rounded-3 p-4 text-center">
            <i class="bi bi-music-note-beamed display-1 text-warning"></i>
            <h3 class="mt-3 text-champagne">Start Your Musical Journey Today</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-4 mb-4">
        <div class="content-card bg-dark h-100">
          <div class="text-center mb-3">
            <i class="bi bi-people-fill display-4 text-warning"></i>
          </div>
          <h4 class="text-center text-champagne fw-bold">Expert Teachers</h4>
          <p class="text-champagne-muted">
            Learn from internationally acclaimed musicians and educators.
          </p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="content-card bg-dark h-100">
          <div class="text-center mb-3">
            <i class="bi bi-building display-4 text-warning"></i>
          </div>
          <h4 class="text-center text-champagne fw-bold">World-Class Facilities</h4>
          <p class="text-champagne-muted">State-of-the-art studios and performance spaces.</p>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="content-card bg-dark h-100">
          <div class="text-center mb-3">
            <i class="bi bi-calendar-check display-4 text-warning"></i>
          </div>
          <h4 class="text-center text-champagne fw-bold">Flexible Schedules</h4>
          <p class="text-champagne-muted">Programs designed to fit your busy lifestyle.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useTrmsStore } from "../../stores/api";

const SLIDE_INTERVAL_MS = 5000;

export default {
  name: "TRMSHome",

  setup() {
    return { trmsStore: useTrmsStore() };
  },

  data() {
    return {
      activeSlide: 0,
      slideTimer: null,
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
    latestNews() {
      return this.trmsStore.news.slice(0, 3);
    },
    trackStyle() {
      return {
        transform: `translateX(-${this.activeSlide * 100}%)`,
      };
    },
  },

  async mounted() {
    // Load schedules and news (no-op if already cached in the store)
    try {
      await Promise.all([this.trmsStore.fetchSchedules(), this.trmsStore.fetchNews()]);
    } catch {
      // silently fail — the rest of the page still works
    }
    this.startAutoSlide();
  },

  beforeUnmount() {
    this.stopAutoSlide();
  },

  methods: {
    startAutoSlide() {
      if (this.upcomingConcerts.length <= 1) return;
      this.slideTimer = setInterval(() => {
        this.nextSlide();
      }, SLIDE_INTERVAL_MS);
    },

    stopAutoSlide() {
      clearInterval(this.slideTimer);
      this.slideTimer = null;
    },

    restartAutoSlide() {
      this.stopAutoSlide();
      this.startAutoSlide();
    },

    nextSlide() {
      this.activeSlide = (this.activeSlide + 1) % this.upcomingConcerts.length;
    },

    prevSlide() {
      this.activeSlide =
        (this.activeSlide - 1 + this.upcomingConcerts.length) % this.upcomingConcerts.length;
      this.restartAutoSlide();
    },

    goToSlide(i) {
      this.activeSlide = i;
      this.restartAutoSlide();
    },

    toDateKey(date) {
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    },

    formatDate(dateStr) {
      if (!dateStr) return "";
      const [y, m, d] = dateStr.split("-").map(Number);
      return new Date(y, m - 1, d).toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },

    formatTime(value) {
      return String(value || "").slice(0, 5);
    },

    programLabel(programId) {
      const map = { trms: "TRMS", bms: "BMS", jco: "JCO", trcc: "TRCC" };
      return map[programId] || (programId || "").toUpperCase();
    },

    programBadgeClass(programId) {
      const map = {
        trms: "bg-primary",
        bms: "bg-success",
        jco: "bg-warning text-dark",
        trcc: "bg-info text-dark",
      };
      return map[programId] || "bg-secondary";
    },

    truncateContent(text) {
      if (!text) return "";
      return text.length > 200 ? text.substring(0, 200) + "..." : text;
    },
  },
};
</script>

<style scoped>
/* ── Theme variables ──────────────────────────────────────────── */
.text-champagne {
  color: rgba(234, 220, 194, 0.88) !important;
}
.text-champagne-muted {
  color: rgba(234, 220, 194, 0.68) !important;
}
.text-gold {
  color: var(--gold-color, #c8a45d) !important;
}
.card-title {
  color: rgba(234, 220, 194, 0.92) !important;
}

/* ── Override Bootstrap utilities ─────────────────────────────── */
.text-muted {
  color: rgba(234, 220, 194, 0.6) !important;
}

/* ── bg-dark-card (replaces bg-light) ─────────────────────────── */
.bg-dark-card {
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: var(--radius-md, 8px);
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.1), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 12px 32px rgba(10, 10, 18, 0.25);
}

/* ── Slider container ─────────────────────────────────────────── */
.concert-slider {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  min-height: 320px;
  border: 1px solid rgba(234, 220, 194, 0.1);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 20px 44px rgba(10, 10, 18, 0.28);
}

/* ── Track (holds all slides side-by-side) ───────────────────── */
.concert-slider__track {
  display: flex;
  width: 100%;
  height: 100%;
  transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Individual slide ────────────────────────────────────────── */
.concert-slide {
  flex: 0 0 100%;
  width: 100%;
  min-height: 320px;
  position: relative;
  display: flex;
  align-items: flex-end;
}

/* Background image */
.concert-slide__bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  transition: transform 0.7s ease;
}

/* Fallback gradient when no banner_url */
.concert-slide__bg--fallback {
  background: linear-gradient(135deg, #1a1a2e 0%, #2d1b3d 40%, #7f2432 100%);
}

/* Dark gradient overlay for text legibility */
.concert-slide__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(10, 10, 20, 0.88) 0%,
    rgba(10, 10, 20, 0.55) 55%,
    rgba(10, 10, 20, 0.15) 100%
  );
  display: flex;
  align-items: flex-end;
}

/* Inner text block */
.concert-slide__inner {
  padding: 2rem 2.5rem;
  color: #fffdf8;
  max-width: 700px;
}

.concert-slide__title {
  font-size: clamp(1.5rem, 4vw, 2.5rem);
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
  color: var(--gold-color, #c8a45d);
}

.concert-slide__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  font-size: 0.95rem;
  color: rgba(234, 220, 194, 0.75);
  margin-bottom: 0.25rem;
}

.concert-slide__desc {
  font-size: 0.92rem;
  color: rgba(234, 220, 194, 0.6);
  margin-top: 0.5rem;
  margin-bottom: 0;
  max-width: 520px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* ── Dot indicators ──────────────────────────────────────────── */
.concert-slider__dots {
  position: absolute;
  bottom: 1rem;
  right: 1.5rem;
  display: flex;
  gap: 0.4rem;
  z-index: 10;
}

.concert-slider__dot {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 50%;
  border: none;
  background: rgba(234, 220, 194, 0.3);
  padding: 0;
  cursor: pointer;
  transition:
    background 0.25s ease,
    transform 0.25s ease;
}

.concert-slider__dot--active {
  background: var(--gold-color, #c8a45d);
  transform: scale(1.3);
  box-shadow: 0 0 10px rgba(200, 164, 93, 0.5);
}

/* ── Prev / Next arrows ──────────────────────────────────────── */
.concert-slider__arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
  background: rgba(10, 10, 18, 0.5);
  border: 1px solid rgba(234, 220, 194, 0.12);
  color: rgba(234, 220, 194, 0.8);
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease;
  backdrop-filter: blur(4px);
}

.concert-slider__arrow:hover {
  background: rgba(200, 164, 93, 0.18);
  border-color: rgba(200, 164, 93, 0.35);
  color: #fffdf8;
}

.concert-slider__arrow--prev {
  left: 0.75rem;
}
.concert-slider__arrow--next {
  right: 0.75rem;
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 575.98px) {
  .concert-slider {
    min-height: 240px;
  }

  .concert-slide {
    min-height: 240px;
  }

  .concert-slide__inner {
    padding: 1.25rem 1rem;
  }

  .concert-slide__title {
    font-size: 1.25rem;
  }

  .concert-slide__meta {
    font-size: 0.8rem;
  }

  .concert-slide__desc {
    display: none;
  }
}

/* ── News cards ──────────────────────────────────────────────── */
.news-card {
  opacity: 0;
  transform: translateY(30px);
  animation: newsFadeIn 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  transition:
    transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.35s ease,
    border-color 0.35s ease;
  border: 1px solid rgba(234, 220, 194, 0.1);
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%) !important;
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 12px 32px rgba(10, 10, 18, 0.25);
  border-radius: 10px;
  will-change: transform;
}

.news-card:hover {
  border-color: rgba(200, 164, 93, 0.3) !important;
}

.news-card:nth-child(1) {
  animation-delay: 0.05s;
}
.news-card:nth-child(2) {
  animation-delay: 0.15s;
}
.news-card:nth-child(3) {
  animation-delay: 0.25s;
}

@media (hover: hover) {
  .news-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow:
      0 1px 0 rgba(255, 255, 255, 0.06) inset,
      0 16px 40px rgba(10, 10, 18, 0.35);
    border-color: rgba(200, 164, 93, 0.35);
  }
}

.news-card:active {
  transform: scale(0.97);
  transition-duration: 0.1s;
}

@keyframes newsFadeIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.program-logo-pill {
  display: inline-flex;
  align-items: center;
  background: rgba(234, 220, 194, 0.1);
  border: 1px solid rgba(234, 220, 194, 0.08);
  border-radius: 4px;
  padding: 3px 8px;
  line-height: 1;
}

.program-logo-img {
  height: 14px;
  width: auto;
  display: block;
}
</style>
