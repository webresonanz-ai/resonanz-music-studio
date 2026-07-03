<template>
    <div class="fade-in-up">

        <!-- ── Upcoming Concert Slideshow ──────────────────────────────── -->
        <div v-if="upcomingConcerts.length" class="concert-slider mb-4" aria-label="Upcoming concerts">
            <div class="concert-slider__track" :style="trackStyle">
                <div
                    v-for="(concert, index) in upcomingConcerts"
                    :key="concert.id"
                    class="concert-slide"
                >
                    <!-- Banner image or fallback gradient -->
                    <div
                        class="concert-slide__bg"
                        :style="concert.banner_url
                            ? { backgroundImage: `url('${concert.banner_url}')` }
                            : {}"
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
                                <span><i class="bi bi-clock me-2"></i>{{ formatTime(concert.start_time) }} – {{ formatTime(concert.end_time) }}</span>
                            </div>
                            <p v-if="concert.description" class="concert-slide__desc">{{ concert.description }}</p>
                            <router-link
                                :to="`/trms/concert/registration/${slugify(concert.title)}`"
                                class="btn btn-warning btn-lg mt-3 fw-bold"
                            >
                                <i class="bi bi-ticket-perforated me-2"></i>Register Now
                            </router-link>
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
                <button class="concert-slider__arrow concert-slider__arrow--prev" @click="prevSlide" aria-label="Previous concert">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="concert-slider__arrow concert-slider__arrow--next" @click="nextSlide" aria-label="Next concert">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </template>
        </div>

        <!-- ── Existing content below ───────────────────────────────────── -->
        <div class="content-card">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">The Resonanz Music Studio</h1>
                    <p class="lead text-muted mb-4">
                        Discover your musical potential with world-class instruction and state-of-the-art facilities.
                    </p>
                    <div class="d-flex gap-3">
                        <router-link to="/trms/courses-fees" class="btn btn-primary btn-lg">
                            Explore Courses
                        </router-link>
                        <router-link to="/trms/contact" class="btn btn-outline-primary btn-lg">
                            Contact Us
                        </router-link>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="bg-light rounded-3 p-4 text-center">
                        <i class="bi bi-music-note-beamed display-1 text-primary"></i>
                        <h3 class="mt-3">Start Your Musical Journey Today</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="content-card h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-people-fill display-4 text-primary"></i>
                    </div>
                    <h4 class="text-center">Expert Teachers</h4>
                    <p class="text-muted">Learn from internationally acclaimed musicians and educators.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="content-card h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-building display-4 text-primary"></i>
                    </div>
                    <h4 class="text-center">World-Class Facilities</h4>
                    <p class="text-muted">State-of-the-art studios and performance spaces.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="content-card h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-calendar-check display-4 text-primary"></i>
                    </div>
                    <h4 class="text-center">Flexible Schedules</h4>
                    <p class="text-muted">Programs designed to fit your busy lifestyle.</p>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { useTrmsStore } from '../../stores/api'

const SLIDE_INTERVAL_MS = 10000

export default {
    name: 'TRMSHome',

    setup() {
        return { trmsStore: useTrmsStore() }
    },

    data() {
        return {
            activeSlide: 0,
            slideTimer: null
        }
    },

    computed: {
        upcomingConcerts() {
            const todayKey = this.toDateKey(new Date())
            return this.trmsStore.schedules
                .filter(s => s.type === 'concert' && s.date >= todayKey)
                .sort((a, b) => {
                    const d = a.date.localeCompare(b.date)
                    return d || a.start_time.localeCompare(b.start_time)
                })
        },
        trackStyle() {
            return {
                transform: `translateX(-${this.activeSlide * 100}%)`
            }
        }
    },

    async mounted() {
        // Load schedules (no-op if already cached in the store)
        try {
            await this.trmsStore.fetchSchedules()
        } catch {
            // silently fail — the rest of the page still works
        }
        this.startAutoSlide()
    },

    beforeUnmount() {
        this.stopAutoSlide()
    },

    methods: {
        startAutoSlide() {
            if (this.upcomingConcerts.length <= 1) return
            this.slideTimer = setInterval(() => {
                this.nextSlide()
            }, SLIDE_INTERVAL_MS)
        },

        stopAutoSlide() {
            clearInterval(this.slideTimer)
            this.slideTimer = null
        },

        restartAutoSlide() {
            this.stopAutoSlide()
            this.startAutoSlide()
        },

        nextSlide() {
            this.activeSlide = (this.activeSlide + 1) % this.upcomingConcerts.length
        },

        prevSlide() {
            this.activeSlide = (this.activeSlide - 1 + this.upcomingConcerts.length) % this.upcomingConcerts.length
            this.restartAutoSlide()
        },

        goToSlide(i) {
            this.activeSlide = i
            this.restartAutoSlide()
        },

        toDateKey(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
        },

        formatDate(dateStr) {
            if (!dateStr) return ''
            const [y, m, d] = dateStr.split('-').map(Number)
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        },

        formatTime(value) {
            return String(value || '').slice(0, 5)
        },

        slugify(title) {
            return String(title || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
        }
    }
}
</script>

<style scoped>
/* ── Slider container ─────────────────────────────────────────── */
.concert-slider {
    position: relative;
    border-radius: var(--radius-md, 0.75rem);
    overflow: hidden;
    /* height is driven by the slides themselves */
    min-height: 320px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
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
    color: #fff;
    max-width: 700px;
}

.concert-slide__title {
    font-size: clamp(1.5rem, 4vw, 2.5rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}

.concert-slide__meta {
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0.25rem;
}

.concert-slide__desc {
    font-size: 0.92rem;
    color: rgba(255, 255, 255, 0.7);
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
    background: rgba(255, 255, 255, 0.4);
    padding: 0;
    cursor: pointer;
    transition: background 0.25s ease, transform 0.25s ease;
}

.concert-slider__dot--active {
    background: #ffc107;
    transform: scale(1.3);
}

/* ── Prev / Next arrows ──────────────────────────────────────── */
.concert-slider__arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    background: rgba(0, 0, 0, 0.35);
    border: none;
    color: #fff;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background 0.2s ease;
}

.concert-slider__arrow:hover {
    background: rgba(0, 0, 0, 0.65);
}

.concert-slider__arrow--prev { left: 0.75rem; }
.concert-slider__arrow--next { right: 0.75rem; }

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
</style>
