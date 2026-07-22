<template>
    <div class="fade-in-up">

        <!-- ══ FULL-SCREEN HERO ════════════════════════════════════════ -->
        <section class="bms-hero-section" ref="heroSection">
            <div class="bms-hero-bg" aria-hidden="true">
                <div class="bms-hero-bg-gradient"></div>
                <div class="bms-hero-bg-radial"></div>
            </div>

            <div class="bms-hero-content" ref="heroContent" :style="heroContentStyle">
                <div class="bms-hero-badge">
                    <i class="bi bi-music-note-beamed me-2"></i>
                    Batavia Madrigal Singers
                </div>
                <h1 class="bms-hero-title">Where Voices Unite</h1>
                <p class="bms-hero-sub">
                    A premier professional choir dedicated to the art of choral singing — from classical masterworks to contemporary masterpieces.
                </p>
                <div class="bms-hero-actions">
                    <router-link to="/bms/events" class="bms-hero-btn bms-hero-btn--primary">
                        <i class="bi bi-calendar-event me-2"></i>View All Events
                    </router-link>
                    <router-link to="/bms/members" class="bms-hero-btn bms-hero-btn--outline">
                        <i class="bi bi-people me-2"></i>Our Singers
                    </router-link>
                </div>
            </div>

            <div class="bms-hero-scroll" aria-hidden="true" :style="heroScrollStyle">
                <div class="bms-hero-scroll-mouse">
                    <div class="bms-hero-scroll-dot"></div>
                </div>
                <span>Scroll</span>
            </div>
        </section>

        <!-- ══ STATS STRIP ═══════════════════════════════════════════════ -->
        <div class="stats-strip mb-4 reveal" ref="statsStrip">
            <div class="stat-block">
                <span class="stat-num">{{ countActive }}</span>
                <span class="stat-desc">Active Singers</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">{{ countEvents }}</span>
                <span class="stat-desc">Upcoming Events</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">{{ countParts }}</span>
                <span class="stat-desc">Voice Parts</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">Est. 1994</span>
                <span class="stat-desc">Year Founded</span>
            </div>
        </div>

        <!-- ══ TWO-COLUMN MAIN ════════════════════════════════════════════ -->
        <div class="row g-4">

            <!-- ── Upcoming Events ───────────────────────────────────────── -->
            <div class="col-12 col-lg-7 reveal reveal-delay-1">
                <div class="content-card bg-dark h-100">
                    <div class="section-header mb-4">
                        <div>
                            <h3 class="section-title mb-0">
                                <i class="bi bi-calendar-event me-2 text-primary"></i>Upcoming Events
                            </h3>
                            <p class="section-sub mb-0">Rehearsals, concerts &amp; performances</p>
                        </div>
                        <router-link to="/bms/events" class="btn btn-sm btn-outline-primary">
                            See All <i class="bi bi-arrow-right ms-1"></i>
                        </router-link>
                    </div>

                    <!-- Loading -->
                    <div v-if="loadingEvents" class="text-center py-5">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <div class="text-muted small mt-2">Loading events…</div>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="upcomingEvents.length === 0" class="empty-event-state">
                        <i class="bi bi-calendar-x display-5 mb-3 text-secondary"></i>
                        <p class="mb-0 text-muted">No upcoming events scheduled.</p>
                    </div>

                    <!-- Event list -->
                    <div v-else class="upcoming-list">
                        <div
                            v-for="event in upcomingEvents"
                            :key="event.id"
                            class="upcoming-item"
                            @click="$router.push('/bms/events')"
                            role="button"
                            tabindex="0"
                            @keydown.enter="$router.push('/bms/events')"
                        >
                            <!-- Calendar widget -->
                            <div class="mini-cal flex-shrink-0">
                                <div class="mini-cal-month">{{ getMonthAbbr(event.date) }}</div>
                                <div class="mini-cal-day">{{ getDayNum(event.date) }}</div>
                                <div class="mini-cal-weekday">{{ getWeekdayAbbr(event.date) }}</div>
                            </div>

                            <!-- Details -->
                            <div class="upcoming-details flex-grow-1 min-width-0">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="badge" :class="typeBadgeClass(event.type)">
                                        {{ typeLabel(event.type) }}
                                    </span>
                                    <span class="text-muted" style="font-size: 0.78rem;">
                                        <i class="bi bi-clock me-1"></i>
                                        {{ formatTime(event.start_time) }} – {{ formatTime(event.end_time) }}
                                    </span>
                                </div>
                                <h6 class="upcoming-title mb-0">{{ event.title }}</h6>
                                <p class="upcoming-desc text-muted mb-0">
                                    {{ event.description || 'No description provided.' }}
                                </p>
                            </div>

                            <i class="bi bi-chevron-right text-muted flex-shrink-0 upcoming-chevron"></i>
                        </div>
                    </div>

                    <!-- "More" indicator -->
                    <div v-if="!loadingEvents && moreEventsCount > 0" class="more-events-hint">
                        <router-link to="/bms/events" class="text-primary small fw-semibold text-decoration-none">
                            <i class="bi bi-plus-circle me-1"></i>{{ moreEventsCount }} more event{{ moreEventsCount === 1 ? '' : 's' }}
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- ── Our Singers ────────────────────────────────────────────── -->
            <div class="col-12 col-lg-5 reveal reveal-delay-2">
                <div class="content-card bg-dark h-100">
                    <div class="section-header mb-4">
                        <div>
                            <h3 class="section-title mb-0">
                                <i class="bi bi-people me-2 text-warning"></i>Our Singers
                            </h3>
                            <p class="section-sub mb-0">The voices behind the music</p>
                        </div>
                        <router-link to="/bms/members" class="btn btn-sm btn-outline-primary">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </router-link>
                    </div>

                    <!-- Voice-part breakdown -->
                    <div class="voice-parts-grid mb-4">
                        <div
                            v-for="part in voiceParts"
                            :key="part.label"
                            class="voice-part-card"
                            :style="`--vp-color: ${part.color}`"
                        >
                            <div class="vp-icon">
                                <i :class="part.icon"></i>
                            </div>
                            <div class="vp-info">
                                <span class="vp-count">{{ part.count }}</span>
                                <span class="vp-label">{{ part.label }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="singers-description">
                        <p class="mb-3">
                            BMS is composed of <strong>professional singers</strong> with extensive backgrounds in classical training, vocal performance, and choral arts — united by a shared passion for ensemble excellence.
                        </p>
                        <ul class="singers-highlights">
                            <li>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Vocalists trained in classical &amp; contemporary repertoire
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Alumni of conservatories &amp; leading music institutions
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Multi-awarded in national &amp; international competitions
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Diverse voice parts: Sopran, Alto, Tenor &amp; Bass
                            </li>
                        </ul>
                    </div>

                    <!-- Members stat footer -->
                    <div class="members-stat-footer">
                        <div class="msf-item">
                            <span class="msf-num">{{ activeMembers }}</span>
                            <span class="msf-desc">Active</span>
                        </div>
                        <div class="msf-divider"></div>
                        <div class="msf-item">
                            <span class="msf-num">{{ passiveMembers }}</span>
                            <span class="msf-desc">Passive</span>
                        </div>
                        <div class="msf-divider"></div>
                        <div class="msf-item">
                            <span class="msf-num">{{ totalMembers }}</span>
                            <span class="msf-desc">Total</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useBmsStore } from '../../stores/api'

const VOICE_PART_META = [
    { role: 'Sopran', label: 'Sopran', icon: 'bi bi-music-note', color: '#e67fa0' },
    { role: 'Alto',   label: 'Alto',   icon: 'bi bi-music-note', color: '#c8a45d' },
    { role: 'Tenor',  label: 'Tenor',  icon: 'bi bi-music-note', color: '#5b9bd5' },
    { role: 'Bass',   label: 'Bass',   icon: 'bi bi-music-note', color: '#7c5cbf' },
]

const MAX_EVENTS = 4

export default {
    name: 'BMSHome',

    data() {
        return {
            loadingEvents: false,
            heroProgress: 0,
            countActive: 0,
            countEvents: 0,
            countParts: 0,
            _rafId: null,
            _counterRafId: null,
            _scrollHandler: null,
            _observers: [],
        }
    },

    computed: {
        ...mapState(useBmsStore, ['events', 'members']),

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

        publicEvents() {
            if (!this.events || !Array.isArray(this.events)) return []
            const today = new Date().toISOString().split('T')[0]
            const internalTypes = ['practice', 'rehearsal']
            return this.events.filter(e => {
                if (e.date < today) return false
                const isBms = Array.isArray(e.program_ids) && e.program_ids.includes('bms')
                if (isBms && internalTypes.includes(e.type)) return false
                return true
            }).sort((a, b) => a.date.localeCompare(b.date) || a.start_time.localeCompare(b.start_time))
        },

        upcomingEvents() {
            return this.publicEvents.slice(0, MAX_EVENTS)
        },

        moreEventsCount() {
            return Math.max(0, this.publicEvents.length - MAX_EVENTS)
        },

        activeMembers() {
            if (!this.members || !Array.isArray(this.members)) return 0
            return this.members.filter(m => m.status === 'active').length
        },

        passiveMembers() {
            if (!this.members || !Array.isArray(this.members)) return 0
            return this.members.filter(m => m.status === 'passive').length
        },

        totalMembers() {
            return Array.isArray(this.members) ? this.members.length : 0
        },

        voiceParts() {
            return VOICE_PART_META.map(vp => ({
                ...vp,
                count: Array.isArray(this.members)
                    ? this.members.filter(m => m.role === vp.role && m.status === 'active').length
                    : 0
            })).filter(vp => vp.count > 0)
        }
    },

    async mounted() {
        this.loadingEvents = true
        try {
            await Promise.all([
                this.fetchEvents(),
                this.fetchMembers(),
            ])
        } finally {
            this.loadingEvents = false
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
        ...mapActions(useBmsStore, ['fetchEvents', 'fetchMembers']),

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
            const targets = [this.activeMembers, this.publicEvents.length, this.voiceParts.length]
            const duration = 1200
            const start = performance.now()

            const step = (now) => {
                const elapsed = now - start
                const progress = Math.min(elapsed / duration, 1)
                const eased = 1 - Math.pow(1 - progress, 3)
                this.countActive = Math.round(eased * targets[0])
                this.countEvents = Math.round(eased * targets[1])
                this.countParts = Math.round(eased * targets[2])
                if (progress < 1) {
                    this._counterRafId = requestAnimationFrame(step)
                }
            }

            this._counterRafId = requestAnimationFrame(step)
        },

        formatTime(val) {
            return String(val || '').slice(0, 5)
        },

        getMonthAbbr(dateStr) {
            if (!dateStr) return ''
            const [y, m, d] = dateStr.split('-').map(Number)
            return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short' }).toUpperCase()
        },

        getDayNum(dateStr) {
            if (!dateStr) return ''
            return parseInt(dateStr.split('-')[2], 10)
        },

        getWeekdayAbbr(dateStr) {
            if (!dateStr) return ''
            const [y, m, d] = dateStr.split('-').map(Number)
            return new Date(y, m - 1, d).toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase()
        },

        typeBadgeClass(type) {
            const map = {
                lesson:   'bg-primary',
                practice: 'bg-success',
                concert:  'bg-warning text-dark',
                exam:     'bg-danger',
                other:    'bg-secondary'
            }
            return map[type] || 'bg-secondary'
        },

        typeLabel(type) {
            const map = {
                lesson:   'Lesson',
                practice: 'Practice',
                concert:  'Concert',
                exam:     'Exam',
                other:    'Other'
            }
            return map[type] || type
        }
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

.upcoming-item {
    background: rgba(234, 220, 194, 0.03) !important;
    border-color: rgba(234, 220, 194, 0.08) !important;
}

.upcoming-title {
    color: rgba(234, 220, 194, 0.85) !important;
}

.upcoming-desc {
    color: rgba(234, 220, 194, 0.45) !important;
}

.mini-cal {
    background: rgba(26, 31, 48, 0.95) !important;
    border-color: rgba(234, 220, 194, 0.1) !important;
}

.mini-cal-month {
    background: #7f2432 !important;
}

.mini-cal-day {
    color: rgba(234, 220, 194, 0.9) !important;
}

.mini-cal-weekday {
    color: rgba(234, 220, 194, 0.4) !important;
}

.more-events-hint {
    border-color: rgba(234, 220, 194, 0.08) !important;
}

.voice-part-card {
    border-color: rgba(234, 220, 194, 0.08) !important;
    background: rgba(234, 220, 194, 0.03) !important;
}

.singers-description {
    color: rgba(234, 220, 194, 0.5) !important;
}

.singers-description strong {
    color: rgba(234, 220, 194, 0.85) !important;
}

.singers-highlights li {
    color: rgba(234, 220, 194, 0.5) !important;
}

.members-stat-footer {
    border-top-color: rgba(234, 220, 194, 0.08) !important;
}

.msf-desc {
    color: rgba(234, 220, 194, 0.4) !important;
}

.msf-divider {
    background: rgba(234, 220, 194, 0.12) !important;
}

/* ══ FULL-SCREEN HERO ══════════════════════════════════════════════ */
.bms-hero-section {
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

.bms-hero-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.bms-hero-bg-gradient {
    position: absolute;
    inset: 0;
    background: transparent;
}

.bms-hero-bg-radial {
    position: absolute;
    inset: 0;
    background: transparent;
}

.bms-hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.bms-hero-badge {
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

.bms-hero-title {
    font-size: clamp(2.5rem, 6vw, 5rem);
    font-weight: 800;
    color: var(--champagne-color);
    line-height: 1.05;
    letter-spacing: -0.02em;
    margin-bottom: 1.25rem;
    text-shadow: 0 2px 40px rgba(0,0,0,0.4);
}

.bms-hero-sub {
    font-size: clamp(1rem, 2vw, 1.25rem);
    color: rgba(234, 220, 194, 0.65);
    max-width: 560px;
    margin: 0 auto 2rem;
    line-height: 1.7;
}

.bms-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.bms-hero-btn {
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

.bms-hero-btn--primary {
    background: linear-gradient(135deg, #d6b66c, #c8a45d);
    color: #17130a;
    border: none;
    box-shadow: 0 8px 28px rgba(200, 164, 93, 0.3);
}

.bms-hero-btn--primary:hover {
    background: linear-gradient(135deg, #e1c47f, #b99245);
    transform: translateY(-3px);
    box-shadow: 0 12px 36px rgba(200, 164, 93, 0.4);
    color: #17130a;
}

.bms-hero-btn--outline {
    border: 1px solid rgba(200, 164, 93, 0.4);
    color: var(--gold-color);
    background: rgba(255,255,255, 0.8);
    backdrop-filter: blur(8px);
}

.bms-hero-btn--outline:hover {
    border-color: var(--gold-color);
    color: #fffdf8;
    background: rgba(200, 164, 93, 0.15);
    transform: translateY(-3px);
}

/* Scroll indicator */
.bms-hero-scroll {
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

.bms-hero-scroll-mouse {
    width: 22px;
    height: 34px;
    border: 2px solid rgba(234, 220, 194, 0.25);
    border-radius: 999px;
    position: relative;
}

.bms-hero-scroll-dot {
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
    0%, 100% { transform: translateX(-50%) translateY(0); opacity: 1; }
    50% { transform: translateX(-50%) translateY(10px); opacity: 0.3; }
}

.bms-hero-scroll span {
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

/* ══ UPCOMING EVENTS ═════════════════════════════════════════════ */
.empty-event-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
}

.upcoming-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--hairline-color);
    border-radius: var(--radius-md, 10px);
    cursor: pointer;
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    background: var(--surface-color);
}

.upcoming-item:hover {
    transform: translateX(4px);
    border-color: rgba(200, 164, 93, 0.4);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

/* Mini Calendar Widget */
.mini-cal {
    width: 4rem;
    border-radius: 8px;
    overflow: hidden;
    text-align: center;
    border: 1px solid var(--hairline-color);
    background: rgba(255,255,255,0.95);
    flex-shrink: 0;
}

.mini-cal-month {
    background: var(--accent-color, #7f2432);
    color: #fff;
    font-size: 0.62rem;
    font-weight: 700;
    padding: 0.2rem 0;
    letter-spacing: 0.06em;
}

.mini-cal-day {
    font-size: 1.55rem;
    font-weight: 800;
    color: var(--text-color);
    line-height: 1.15;
    padding-top: 0.25rem;
}

.mini-cal-weekday {
    font-size: 0.58rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: var(--muted-color, #888);
    padding-bottom: 0.3rem;
}

.upcoming-details {
    flex: 1 1 0;
    min-width: 0;
}

.upcoming-title {
    font-size: 0.9rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.upcoming-desc {
    font-size: 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-top: 0.15rem;
}

.upcoming-chevron {
    font-size: 0.78rem;
    opacity: 0.4;
    transition: opacity 0.2s;
}

.upcoming-item:hover .upcoming-chevron {
    opacity: 0.8;
}

.more-events-hint {
    margin-top: 0.85rem;
    text-align: center;
    padding: 0.6rem;
    border: 1px dashed var(--hairline-color);
    border-radius: var(--radius-md, 10px);
    transition: border-color 0.2s;
}

.more-events-hint:hover {
    border-color: rgba(200, 164, 93, 0.5);
}

/* ══ OUR SINGERS ══════════════════════════════════════════════════ */
.voice-parts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.6rem;
}

.voice-part-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--hairline-color);
    border-radius: 10px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--vp-color) 8%, transparent), transparent);
    border-left: 3px solid var(--vp-color);
    transition: transform 0.18s ease;
}

.voice-part-card:hover {
    transform: translateY(-2px);
}

.vp-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--vp-color) 15%, transparent);
    display: grid;
    place-items: center;
    color: var(--vp-color);
    font-size: 1rem;
    flex-shrink: 0;
}

.vp-info {
    display: flex;
    flex-direction: column;
}

.vp-count {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--vp-color);
    line-height: 1;
}

.vp-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted-color, #888);
    margin-top: 0.1rem;
}

.singers-description {
    font-size: 0.875rem;
    color: var(--muted-color, #888);
    line-height: 1.65;
    margin-bottom: 1.25rem;
}

.singers-description strong {
    color: var(--text-color);
}

.singers-highlights {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.singers-highlights li {
    font-size: 0.82rem;
    color: var(--muted-color, #888);
    display: flex;
    align-items: flex-start;
    gap: 0.25rem;
}

.singers-highlights li i {
    flex-shrink: 0;
    margin-top: 2px;
    font-size: 0.78rem;
}

/* Members stat footer */
.members-stat-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--hairline-color);
}

.msf-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    text-align: center;
}

.msf-num {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--gold-color, #c8a45d);
    line-height: 1;
}

.msf-desc {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--muted-color, #888);
    font-weight: 600;
    margin-top: 0.2rem;
}

.msf-divider {
    width: 1px;
    height: 2rem;
    background: var(--hairline-color);
    flex-shrink: 0;
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

.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }

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

.stats-strip.revealed .stat-block:nth-child(1) .stat-num { transition-delay: 0.1s; }
.stats-strip.revealed .stat-block:nth-child(3) .stat-num { transition-delay: 0.2s; }
.stats-strip.revealed .stat-block:nth-child(5) .stat-num { transition-delay: 0.3s; }

/* ══ HERO CONTENT TRANSITION ═════════════════════════════════════ */
.bms-hero-content {
    will-change: transform, opacity;
}

.bms-hero-scroll {
    will-change: opacity;
    transition: opacity 0.3s ease;
}

/* ══ RESPONSIVE ══════════════════════════════════════════════════ */
@media (max-width: 991.98px) {
    .bms-hero-section {
        min-height: 90vh;
        margin: -1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem;
    }

}

@media (max-width: 767.98px) {
    .bms-hero-section {
        min-height: 85vh;
        margin: -1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem 1rem;
    }

    .bms-hero-badge {
        font-size: 0.7rem;
        padding: 0.4rem 1rem;
    }

    .bms-hero-actions {
        flex-direction: column;
        align-items: center;
    }

    .bms-hero-btn {
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

    .voice-parts-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 479.98px) {
    .bms-hero-section {
        min-height: 90vh;
        margin: -1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem 1rem;
    }

    .bms-hero-badge {
        font-size: 0.7rem;
        padding: 0.4rem 1rem;
    }

    .bms-hero-actions {
        flex-direction: column;
        align-items: center;
    }

    .bms-hero-btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }

    .bms-hero-scroll {
        display: none;
    }

    .voice-parts-grid {
        grid-template-columns: 1fr 1fr;
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

    .upcoming-item {
        padding: 0.75rem 0.85rem;
        gap: 0.75rem;
    }

    .mini-cal {
        width: 3.5rem;
    }

    .mini-cal-day {
        font-size: 1.3rem;
    }
}
</style>
