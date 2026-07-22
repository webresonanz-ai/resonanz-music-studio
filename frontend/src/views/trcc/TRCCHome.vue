<template>
    <div class="fade-in-up">

        <!-- ══ FULL-SCREEN HERO ════════════════════════════════════════ -->
        <section class="trcc-hero-section" ref="heroSection">
            <div class="trcc-hero-bg" aria-hidden="true">
                <div class="trcc-hero-bg-gradient"></div>
                <div class="trcc-hero-bg-radial"></div>
            </div>

            <div class="trcc-hero-content" ref="heroContent" :style="heroContentStyle">
                <div class="trcc-hero-badge">
                    <i class="bi bi-music-note-beamed me-2"></i>
                    The Resonanz Children Choir
                </div>
                <h1 class="trcc-hero-title">Where Young Voices Shine</h1>
                <p class="trcc-hero-sub">
                    Nurturing young talents through choral excellence, discipline, and the joy of music — building confidence that lasts a lifetime.
                </p>
                <div class="trcc-hero-actions">
                    <router-link to="/trcc/achievements" class="trcc-hero-btn trcc-hero-btn--primary">
                        <i class="bi bi-trophy me-2"></i>Our Achievements
                    </router-link>
                    <router-link to="/trcc/contact" class="trcc-hero-btn trcc-hero-btn--outline">
                        <i class="bi bi-envelope me-2"></i>Join Us
                    </router-link>
                </div>
            </div>

            <div class="trcc-hero-scroll" aria-hidden="true" :style="heroScrollStyle">
                <div class="trcc-hero-scroll-mouse">
                    <div class="trcc-hero-scroll-dot"></div>
                </div>
                <span>Scroll</span>
            </div>
        </section>

        <!-- ══ STATS STRIP ═══════════════════════════════════════════════ -->
        <div class="stats-strip mb-4 reveal" ref="statsStrip">
            <div class="stat-block">
                <span class="stat-num">{{ countAchievements }}</span>
                <span class="stat-desc">Achievements</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">{{ countTestimonials }}</span>
                <span class="stat-desc">Testimonials</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">{{ countParts }}</span>
                <span class="stat-desc">Voice Parts</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">Est. 2021</span>
                <span class="stat-desc">Year Founded</span>
            </div>
        </div>

        <!-- ══ THREE-COLUMN CARDS ════════════════════════════════════════ -->
        <div class="row g-4">

            <!-- ── Achievements ─────────────────────────────────────────── -->
            <div class="col-12 col-lg-4 reveal reveal-delay-1">
                <div class="content-card bg-dark h-100">
                    <div class="section-header mb-4">
                        <div>
                            <h3 class="section-title mb-0">
                                <i class="bi bi-trophy me-2 text-warning"></i>Achievements
                            </h3>
                            <p class="section-sub mb-0">Honors &amp; competition highlights</p>
                        </div>
                        <router-link to="/trcc/achievements" class="btn btn-sm btn-outline-primary">
                            See All <i class="bi bi-arrow-right ms-1"></i>
                        </router-link>
                    </div>

                    <div v-if="loadingAchievements" class="text-center py-5">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <div class="text-muted small mt-2">Loading achievements…</div>
                    </div>

                    <div v-else-if="achievementList.length === 0" class="empty-state">
                        <i class="bi bi-trophy display-5 mb-3 text-secondary"></i>
                        <p class="mb-0 text-muted">No achievements recorded yet.</p>
                    </div>

                    <div v-else class="trcc-list">
                        <div
                            v-for="item in achievementList"
                            :key="item.id"
                            class="trcc-list-item"
                            @click="$router.push('/trcc/achievements')"
                            role="button"
                            tabindex="0"
                            @keydown.enter="$router.push('/trcc/achievements')"
                        >
                            <div class="trl-icon flex-shrink-0">
                                <i class="bi bi-award"></i>
                            </div>
                            <div class="trl-details flex-grow-1 min-width-0">
                                <h6 class="trl-title mb-0">{{ item.title }}</h6>
                                <p class="trl-desc text-muted mb-0">{{ item.description || '' }}</p>
                            </div>
                            <i class="bi bi-chevron-right text-muted flex-shrink-0 trl-chevron"></i>
                        </div>
                    </div>

                    <div v-if="!loadingAchievements && moreAchievements > 0" class="more-hint">
                        <router-link to="/trcc/achievements" class="text-primary small fw-semibold text-decoration-none">
                            <i class="bi bi-plus-circle me-1"></i>{{ moreAchievements }} more achievement{{ moreAchievements === 1 ? '' : 's' }}
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- ── Testimonials ─────────────────────────────────────────── -->
            <div class="col-12 col-lg-4 reveal reveal-delay-2">
                <div class="content-card bg-dark h-100">
                    <div class="section-header mb-4">
                        <div>
                            <h3 class="section-title mb-0">
                                <i class="bi bi-chat-quote me-2 text-primary"></i>Testimonials
                            </h3>
                            <p class="section-sub mb-0">Words from our community</p>
                        </div>
                        <router-link to="/trcc/testimonial" class="btn btn-sm btn-outline-primary">
                            See All <i class="bi bi-arrow-right ms-1"></i>
                        </router-link>
                    </div>

                    <div v-if="loadingTestimonials" class="text-center py-5">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <div class="text-muted small mt-2">Loading testimonials…</div>
                    </div>

                    <div v-else-if="testimonialList.length === 0" class="empty-state">
                        <i class="bi bi-chat-quote display-5 mb-3 text-secondary"></i>
                        <p class="mb-0 text-muted">No testimonials yet.</p>
                    </div>

                    <div v-else class="trcc-list">
                        <div
                            v-for="item in testimonialList"
                            :key="item.id"
                            class="testimonial-quote-card"
                        >
                            <i class="bi bi-quote quote-mark"></i>
                            <p class="quote-text mb-2">{{ item.quote || item.message || '' }}</p>
                            <div class="quote-author">
                                <strong>{{ item.name || 'Anonymous' }}</strong>
                                <span v-if="item.role" class="text-muted"> — {{ item.role }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!loadingTestimonials && moreTestimonials > 0" class="more-hint">
                        <router-link to="/trcc/testimonial" class="text-primary small fw-semibold text-decoration-none">
                            <i class="bi bi-plus-circle me-1"></i>{{ moreTestimonials }} more testimonial{{ moreTestimonials === 1 ? '' : 's' }}
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- ── Join Us ──────────────────────────────────────────────── -->
            <div class="col-12 col-lg-4 reveal reveal-delay-3">
                <div class="content-card bg-dark h-100 join-us-card">
                    <div class="section-header mb-4">
                        <div>
                            <h3 class="section-title mb-0">
                                <i class="bi bi-people me-2 text-success"></i>Join Us
                            </h3>
                            <p class="section-sub mb-0">Become part of the choir</p>
                        </div>
                    </div>

                    <div class="join-us-content">
                        <div class="join-icon-wrap">
                            <i class="bi bi-mic"></i>
                        </div>
                        <p class="join-desc">
                            We welcome young singers aged <strong>7–16</strong> who love to sing. No prior experience required — just passion and commitment!
                        </p>
                        <ul class="join-highlights">
                            <li>
                                <i class="bi bi-calendar-check text-primary me-2"></i>
                                Weekly rehearsals every Saturday
                            </li>
                            <li>
                                <i class="bi bi-music-note text-warning me-2"></i>
                                Vocal training &amp; music theory
                            </li>
                            <li>
                                <i class="bi bi-star text-success me-2"></i>
                                Performance opportunities
                            </li>
                            <li>
                                <i class="bi bi-people text-info me-2"></i>
                                Friendships that last a lifetime
                            </li>
                        </ul>
                        <router-link to="/trcc/contact" class="btn btn-gold w-100 mt-3">
                            <i class="bi bi-envelope me-2"></i>Contact Us to Join
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useTrccStore } from '../../stores/api'

const MAX_VISIBLE = 3

export default {
    name: 'TRCCHome',

    data() {
        return {
            loadingAchievements: false,
            loadingTestimonials: false,
            heroProgress: 0,
            countAchievements: 0,
            countTestimonials: 0,
            countParts: 0,
            _rafId: null,
            _counterRafId: null,
            _scrollHandler: null,
            _observers: [],
        }
    },

    computed: {
        ...mapState(useTrccStore, ['achievements', 'testimonials']),

        heroContentStyle() {
            const p = this.heroProgress
            const translateY = p * 60
            const scale = 1 - p * 0.04
            const opacity = 1 - p * 0.5
            return {
                transform: `translateY(${translateY}px) scale(${scale})`,
                opacity,
            }
        },

        heroScrollStyle() {
            const opacity = Math.max(0, 1 - this.heroProgress * 2)
            return { opacity }
        },

        achievementList() {
            if (!Array.isArray(this.achievements)) return []
            return this.achievements.slice(0, MAX_VISIBLE)
        },

        moreAchievements() {
            if (!Array.isArray(this.achievements)) return 0
            return Math.max(0, this.achievements.length - MAX_VISIBLE)
        },

        testimonialList() {
            if (!Array.isArray(this.testimonials)) return []
            return this.testimonials.slice(0, MAX_VISIBLE)
        },

        moreTestimonials() {
            if (!Array.isArray(this.testimonials)) return 0
            return Math.max(0, this.testimonials.length - MAX_VISIBLE)
        },
    },

    async mounted() {
        this.loadingAchievements = true
        this.loadingTestimonials = true
        try {
            await Promise.all([
                this.fetchAchievements(),
                this.fetchTestimonials(),
            ])
        } finally {
            this.loadingAchievements = false
            this.loadingTestimonials = false
        }

        this.$nextTick(() => {
            this.initScrollAnimations()
            this.initRevealObservers()
        })
    },

    beforeUnmount() {
        if (this._rafId) cancelAnimationFrame(this._rafId)
        if (this._counterRafId) cancelAnimationFrame(this._counterRafId)
        if (this._scrollHandler) {
            window.removeEventListener('scroll', this._scrollHandler, { passive: true })
        }
        this._observers.forEach(o => o.disconnect())
    },

    methods: {
        ...mapActions(useTrccStore, ['fetchAchievements', 'fetchTestimonials']),

        initScrollAnimations() {
            const hero = this.$refs.heroSection
            if (!hero) return

            this._scrollHandler = () => {
                if (this._rafId) cancelAnimationFrame(this._rafId)
                this._rafId = requestAnimationFrame(() => {
                    const rect = hero.getBoundingClientRect()
                    const offset = -rect.top
                    const maxScroll = rect.height * 0.6
                    this.heroProgress = Math.min(Math.max(offset / maxScroll, 0), 1)
                })
            }

            this._scrollHandler()

            window.addEventListener('scroll', this._scrollHandler, { passive: true })
        },

        initRevealObservers() {
            const els = this.$el.querySelectorAll('.reveal')
            if (!els.length) return

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('revealed')
                            observer.unobserve(entry.target)
                            if (entry.target === this.$refs.statsStrip) {
                                this.startCounterAnimation()
                            }
                        }
                    })
                },
                { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
            )

            els.forEach(el => observer.observe(el))
            this._observers.push(observer)
        },

        startCounterAnimation() {
            if (this._counterRafId) return
            const achLen = Array.isArray(this.achievements) ? this.achievements.length : 0
            const tesLen = Array.isArray(this.testimonials) ? this.testimonials.length : 0
            const targets = [achLen, tesLen, 2]
            const duration = 1200
            const start = performance.now()

            const step = (now) => {
                const elapsed = now - start
                const progress = Math.min(elapsed / duration, 1)
                const eased = 1 - Math.pow(1 - progress, 3)
                this.countAchievements = Math.round(eased * targets[0])
                this.countTestimonials = Math.round(eased * targets[1])
                this.countParts = Math.round(eased * targets[2])
                if (progress < 1) {
                    this._counterRafId = requestAnimationFrame(step)
                }
            }

            this._counterRafId = requestAnimationFrame(step)
        },
    }
}
</script>

<style scoped>
/* ══ Dark theme overrides ════════════════════════════════════════ */
.content-card.bg-dark {
    --surface-color: rgba(234, 220, 194, 0.04);
    --hairline-color: rgba(234, 220, 194, 0.08);
    --text-color: rgba(234, 220, 194, 0.85);
    --muted-color: rgba(234, 220, 194, 0.45);
    --ink-color: rgba(234, 220, 194, 0.92);
    color: rgba(234, 220, 194, 0.78);
}

.stats-strip {
    background: rgba(234, 220, 194, 0.03) !important;
    border-color: rgba(234, 220, 194, 0.08) !important;
}

.stat-sep {
    background: rgba(234, 220, 194, 0.12) !important;
}

.section-title {
    color: rgba(234, 220, 194, 0.85) !important;
}

.section-sub {
    color: rgba(234, 220, 194, 0.45) !important;
}

/* ══ FULL-SCREEN HERO ══════════════════════════════════════════════ */
.trcc-hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin: -1.5rem;
    margin-bottom: 1.5rem;
    padding: 2rem;
}

.trcc-hero-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.trcc-hero-bg-gradient {
    position: absolute;
    inset: 0;
    background: transparent;
}

.trcc-hero-bg-radial {
    position: absolute;
    inset: 0;
    background: transparent;
}

.trcc-hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.trcc-hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1.25rem;
    border: 1px solid rgba(200, 164, 93, 0.3);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(8px);
    color: var(--gold-color);
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 1.5rem;
}

.trcc-hero-title {
    font-size: clamp(2.5rem, 6vw, 5rem);
    font-weight: 800;
    color: var(--champagne-color);
    line-height: 1.05;
    letter-spacing: -0.02em;
    margin-bottom: 1.25rem;
    text-shadow: 0 2px 40px rgba(0, 0, 0, 0.4);
}

.trcc-hero-sub {
    font-size: clamp(1rem, 2vw, 1.25rem);
    color: rgba(234, 220, 194, 0.65);
    max-width: 560px;
    margin: 0 auto 2rem;
    line-height: 1.7;
}

.trcc-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.trcc-hero-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 2rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.25s ease;
    cursor: pointer;
}

.trcc-hero-btn--primary {
    background: linear-gradient(135deg, #d6b66c, #c8a45d);
    color: #17130a;
    border: none;
    box-shadow: 0 8px 28px rgba(200, 164, 93, 0.3);
}

.trcc-hero-btn--primary:hover {
    background: linear-gradient(135deg, #e1c47f, #b99245);
    transform: translateY(-3px);
    box-shadow: 0 12px 36px rgba(200, 164, 93, 0.4);
    color: #17130a;
}

.trcc-hero-btn--outline {
    border: 1px solid rgba(200, 164, 93, 0.4);
    color: var(--gold-color);
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(8px);
}

.trcc-hero-btn--outline:hover {
    border-color: var(--gold-color);
    color: #fffdf8;
    background: rgba(200, 164, 93, 0.15);
    transform: translateY(-3px);
}

/* Scroll indicator */
.trcc-hero-scroll {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: rgba(234, 220, 194, 0.3);
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    z-index: 2;
}

.trcc-hero-scroll-mouse {
    width: 22px;
    height: 34px;
    border: 2px solid rgba(234, 220, 194, 0.25);
    border-radius: 999px;
    position: relative;
}

.trcc-hero-scroll-dot {
    position: absolute;
    top: 6px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 8px;
    border-radius: 999px;
    background: var(--gold-color);
    animation: scrollDot 2s ease-in-out infinite;
}

@keyframes scrollDot {
    0%,
    100% {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
    50% {
        transform: translateX(-50%) translateY(10px);
        opacity: 0.3;
    }
}

.trcc-hero-scroll span {
    font-size: 0.6rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    opacity: 0.4;
}

/* ══ STATS STRIP ══════════════════════════════════════════════════ */
.stats-strip {
    display: flex;
    align-items: center;
    gap: 0;
    background: var(--surface-color);
    border: 1px solid var(--hairline-color);
    border-radius: var(--radius-md);
    padding: 1rem 1.5rem;
    flex-wrap: wrap;
    row-gap: 1rem;
}

.stat-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1 1 auto;
    min-width: 80px;
    text-align: center;
}

.stat-num {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--text-color);
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}

.stat-desc {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--muted-color, #888);
    font-weight: 600;
    margin-top: 0.15rem;
}

.stat-sep {
    width: 1px;
    height: 2.5rem;
    background: var(--hairline-color);
    flex-shrink: 0;
    align-self: center;
}

/* ══ SECTION HEADER ══════════════════════════════════════════════ */
.section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.section-title {
    font-size: 1.15rem;
    font-weight: 700;
}

.section-sub {
    font-size: 0.78rem;
    color: var(--muted-color, #888);
    margin-top: 0.15rem;
}

/* ══ ACHIEVEMENTS / LIST ITEMS ═══════════════════════════════════ */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
}

.trcc-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.trcc-list-item {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--hairline-color);
    border-radius: var(--radius-md, 10px);
    cursor: pointer;
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    background: var(--surface-color);
}

.trcc-list-item:hover {
    transform: translateX(4px);
    border-color: rgba(200, 164, 93, 0.4);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.trl-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(200, 164, 93, 0.12);
    display: grid;
    place-items: center;
    color: var(--gold-color);
    font-size: 1.1rem;
    flex-shrink: 0;
}

.trl-details {
    flex: 1 1 0;
    min-width: 0;
}

.trl-title {
    font-size: 0.88rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trl-desc {
    font-size: 0.74rem;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-top: 0.1rem;
}

.trl-chevron {
    font-size: 0.78rem;
    opacity: 0.4;
    transition: opacity 0.2s;
}

.trcc-list-item:hover .trl-chevron {
    opacity: 0.8;
}

.more-hint {
    margin-top: 0.85rem;
    text-align: center;
    padding: 0.6rem;
    border: 1px dashed var(--hairline-color);
    border-radius: var(--radius-md, 10px);
    transition: border-color 0.2s;
}

.more-hint:hover {
    border-color: rgba(200, 164, 93, 0.5);
}

/* ══ TESTIMONIALS ════════════════════════════════════════════════ */
.testimonial-quote-card {
    padding: 1rem;
    border: 1px solid var(--hairline-color);
    border-radius: var(--radius-md, 10px);
    background: var(--surface-color);
    position: relative;
    transition: border-color 0.18s ease;
}

.testimonial-quote-card:hover {
    border-color: rgba(200, 164, 93, 0.3);
}

.quote-mark {
    font-size: 1.5rem;
    color: rgba(200, 164, 93, 0.25);
    display: block;
    margin-bottom: 0.25rem;
    line-height: 1;
}

.quote-text {
    font-size: 0.85rem;
    color: rgba(234, 220, 194, 0.7);
    line-height: 1.6;
    font-style: italic;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.quote-author {
    font-size: 0.78rem;
    margin-top: 0.4rem;
    color: rgba(234, 220, 194, 0.5);
}

.quote-author strong {
    color: rgba(234, 220, 194, 0.8);
}

/* ══ JOIN US ═════════════════════════════════════════════════════ */
.join-us-card .join-us-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.join-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(200, 164, 93, 0.1);
    display: grid;
    place-items: center;
    font-size: 2rem;
    color: var(--gold-color);
    margin-bottom: 1rem;
}

.join-desc {
    font-size: 0.88rem;
    color: rgba(234, 220, 194, 0.6);
    line-height: 1.7;
    margin-bottom: 1.25rem;
    max-width: 320px;
}

.join-desc strong {
    color: rgba(234, 220, 194, 0.85);
}

.join-highlights {
    list-style: none;
    padding: 0;
    margin: 0 0 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    text-align: left;
    width: 100%;
}

.join-highlights li {
    font-size: 0.82rem;
    color: rgba(234, 220, 194, 0.5);
    display: flex;
    align-items: center;
}

.join-highlights li i {
    flex-shrink: 0;
    font-size: 0.9rem;
}

/* ══ SCROLL REVEAL ANIMATIONS ════════════════════════════════════ */
.reveal {
    opacity: 0;
    transform: translateY(36px);
    transition: opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}

.reveal.revealed {
    opacity: 1;
    transform: translateY(0);
}

.reveal-delay-1 {
    transition-delay: 0.1s;
}

.reveal-delay-2 {
    transition-delay: 0.2s;
}

.reveal-delay-3 {
    transition-delay: 0.3s;
}

/* Staggered stat number entrance */
.stats-strip .stat-num {
    transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1),
        opacity 0.5s ease;
    transform: scale(0.5);
    opacity: 0;
}

.stats-strip.revealed .stat-num {
    transform: scale(1);
    opacity: 1;
}

.stats-strip.revealed .stat-block:nth-child(1) .stat-num {
    transition-delay: 0.1s;
}

.stats-strip.revealed .stat-block:nth-child(3) .stat-num {
    transition-delay: 0.2s;
}

.stats-strip.revealed .stat-block:nth-child(5) .stat-num {
    transition-delay: 0.3s;
}

/* ══ HERO CONTENT TRANSITION ═════════════════════════════════════ */
.trcc-hero-content {
    will-change: transform, opacity;
}

.trcc-hero-scroll {
    will-change: opacity;
    transition: opacity 0.3s ease;
}

/* ══ RESPONSIVE ══════════════════════════════════════════════════ */
@media (max-width: 991.98px) {
    .trcc-hero-section {
        min-height: 90vh;
        margin: -1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem;
    }
}

@media (max-width: 767.98px) {
    .trcc-hero-section {
        min-height: 85vh;
        margin: -1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem 1rem;
    }

    .trcc-hero-badge {
        font-size: 0.7rem;
        padding: 0.4rem 1rem;
    }

    .trcc-hero-actions {
        flex-direction: column;
        align-items: center;
    }

    .trcc-hero-btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }

    .stats-strip {
        padding: 0.85rem 1rem;
    }

    .stat-num {
        font-size: 1.35rem;
    }
}

@media (max-width: 479.98px) {
    .trcc-hero-section {
        min-height: 90vh;
        margin: -1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem 1rem;
    }

    .trcc-hero-badge {
        font-size: 0.7rem;
        padding: 0.4rem 1rem;
    }

    .trcc-hero-actions {
        flex-direction: column;
        align-items: center;
    }

    .trcc-hero-btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }

    .trcc-hero-scroll {
        display: none;
    }

    .stat-sep {
        display: none;
    }

    .stats-strip {
        justify-content: center;
    }

    .stat-block {
        min-width: 70px;
    }

    .trcc-list-item {
        padding: 0.65rem 0.85rem;
        gap: 0.7rem;
    }

    .testimonial-quote-card {
        padding: 0.85rem;
    }
}
</style>
