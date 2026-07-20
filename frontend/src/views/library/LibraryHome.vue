<template>
  <div class="fade-in-up">
    <div class="content-card bg-dark mb-4">
      <p class="eyebrow mb-1">Library</p>
      <h1 class="page-title mb-0" style="color: rgba(234, 220, 194, 0.92) !important;">Welcome to the Music Library</h1>
    </div>

    <div class="content-card bg-dark mb-4" v-if="concerts.length > 0">
      <div class="ch-section-header">
        <div>
          <p class="ch-section-eyebrow">Archive</p>
          <h2 class="ch-section-title">Concert History</h2>
        </div>
        <router-link to="/library/concert-history" class="ch-see-all">
          See All <i class="bi bi-arrow-right"></i>
        </router-link>
      </div>
      <div class="ch-marquee-wrap">
        <div class="ch-marquee-track" ref="track">
          <div
            class="ch-marquee-item"
            v-for="concert in concerts"
            :key="concert.id"
            @click="goToDetail(concert.id)"
          >
            <div
              class="ch-marquee-banner"
              :style="{ backgroundImage: concert.banner ? `url(${concert.banner})` : 'none' }"
            >
              <div class="ch-marquee-overlay"></div>
              <span class="ch-marquee-label">{{ concert.title }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-12 col-md-6">
        <router-link to="/library/sheet-music" class="text-decoration-none">
          <div class="content-card bg-dark library-card h-100">
            <div class="library-icon-wrap">
              <i class="bi bi-music-note"></i>
            </div>
            <h4 class="mt-3 mb-1 text-champagne">Sheet Music</h4>
            <p class="text-champagne-muted mb-0">
              Browse and search scores by genre, composer, arranger, and title.
            </p>
          </div>
        </router-link>
      </div>
      <div class="col-12 col-md-6">
        <router-link to="/library/costumes" class="text-decoration-none">
          <div class="content-card bg-dark library-card h-100">
            <div class="library-icon-wrap">
              <i class="bi bi-person-badge"></i>
            </div>
            <h4 class="mt-3 mb-1 text-champagne">Costumes</h4>
            <p class="text-champagne-muted mb-0">
              Browse the costume collection with previews and condition info.
            </p>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useConcertHistoryStore } from '../../stores/api'

export default {
  name: 'LibraryHome',
  computed: {
    ...mapState(useConcertHistoryStore, ['concerts']),
  },
  data() {
    return {
      scrollOffset: 0,
      animationId: null,
      marqueeSpeed: 0.8,
    }
  },
  async mounted() {
    await this.fetchConcerts()
    this.$nextTick(() => this.startMarquee())
  },
  beforeUnmount() {
    this.stopMarquee()
  },
  methods: {
    ...mapActions(useConcertHistoryStore, ['fetchConcerts']),
    goToDetail(id) {
      this.$router.push(`/library/concert-history/${id}`)
    },
    startMarquee() {
      this.stopMarquee()
      const tick = () => {
        const track = this.$refs.track
        if (!track || this.concerts.length === 0) {
          this.animationId = requestAnimationFrame(tick)
          return
        }
        const first = track.firstElementChild
        if (!first) {
          this.animationId = requestAnimationFrame(tick)
          return
        }
        const gap = 16
        const firstW = first.offsetWidth + gap
        this.scrollOffset += this.marqueeSpeed
        if (this.scrollOffset >= firstW) {
          track.appendChild(first)
          this.scrollOffset -= firstW
          first.animate([
            { opacity: 0, transform: 'scale(0.88)', offset: 0 },
            { opacity: 1, transform: 'scale(1)', offset: 1 },
          ], { duration: 700, easing: 'ease-out' })
        }
        track.style.transform = `translateX(-${this.scrollOffset}px)`
        this.animationId = requestAnimationFrame(tick)
      }
      this.animationId = requestAnimationFrame(tick)
    },
    stopMarquee() {
      if (this.animationId) {
        cancelAnimationFrame(this.animationId)
        this.animationId = null
      }
    },
  },
}
</script>

<style scoped>
.content-card.bg-dark {
  color: rgba(234, 220, 194, 0.78);
}

.library-card {
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
  border: 1px solid rgba(234, 220, 194, 0.08);
}

.library-card:hover {
  border-color: rgba(200, 164, 93, 0.35);
  box-shadow: 0 4px 20px rgba(200, 164, 93, 0.08);
  transform: translateY(-3px);
}

.eyebrow {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold-color, #c8a45d);
}
.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink-color, #191b24);
  letter-spacing: -0.01em;
}
.library-card {
  cursor: pointer;
  text-align: center;
  padding: 3rem 2rem;
}
.library-icon-wrap {
  display: inline-grid;
  place-items: center;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(200, 164, 93, 0.12);
  border: 2px solid rgba(200, 164, 93, 0.2);
}
.library-icon-wrap i {
  font-size: 2rem;
  color: var(--gold-color, #c8a45d);
}

.ch-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.ch-section-eyebrow {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(234, 220, 194, 0.45);
  margin-bottom: 0.1rem;
}

.ch-section-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #fffdf8;
  margin-bottom: 0;
}

.ch-see-all {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--gold-color, #c8a45d);
  text-decoration: none;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  transition: color 0.2s;
}

.ch-see-all:hover {
  color: #eadcc2;
}

.ch-marquee-wrap {
  overflow: hidden;
  mask-image: linear-gradient(90deg, transparent 0%, #000 4%, #000 96%, transparent 100%);
  -webkit-mask-image: linear-gradient(90deg, transparent 0%, #000 4%, #000 96%, transparent 100%);
}

.ch-marquee-track {
  display: flex;
  gap: 1rem;
  width: max-content;
  will-change: transform;
}

.ch-marquee-track:hover {
  cursor: pointer;
}

.ch-marquee-item {
  flex-shrink: 0;
  cursor: pointer;
  transition: transform 0.25s ease;
}

.ch-marquee-item:hover {
  transform: translateY(-4px);
}

.ch-marquee-banner {
  position: relative;
  width: 260px;
  height: 160px;
  border-radius: 10px;
  background-size: cover;
  background-position: center;
  background-color: #10131f;
  overflow: hidden;
  border: 1px solid rgba(234, 220, 194, 0.08);
  transition: border-color 0.25s;
}

.ch-marquee-item:hover .ch-marquee-banner {
  border-color: rgba(200, 164, 93, 0.3);
}

.ch-marquee-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(16, 19, 31, 0.1) 0%, rgba(16, 19, 31, 0.85) 100%);
}

.ch-marquee-label {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0.85rem 1rem;
  color: #fffdf8;
  font-size: 0.9rem;
  font-weight: 700;
  line-height: 1.3;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
}


</style>
