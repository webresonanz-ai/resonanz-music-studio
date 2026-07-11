<template>
    <div class="fade-in-up">

        <!-- ══ HERO ══════════════════════════════════════════════════════ -->
        <div class="bms-hero content-card bg-dark mb-4">
            <div class="bms-hero-inner">
                <p class="text-uppercase fw-bold small mb-2" style="color: var(--gold-color); letter-spacing: 0.12em;">
                    <i class="bi bi-music-note-beamed me-1"></i> Batavia Madrigal Singers
                </p>
                <h1 class="display-4 fw-bold mb-3 text-champagne">Where Voices Unite</h1>
                <p class="lead mb-4 text-champagne-muted" style="max-width: 560px;">
                    A premier professional choir dedicated to the art of choral singing — from classical masterworks to contemporary compositions.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <router-link to="/bms/events" class="btn btn-primary btn-lg">
                        <i class="bi bi-calendar-event me-2"></i>View All Events
                    </router-link>
                    <router-link to="/bms/members" class="btn btn-outline-gold btn-lg">
                        <i class="bi bi-people me-2"></i>Our Singers
                    </router-link>
                </div>
            </div>
            <!-- decorative accent -->
            <div class="bms-hero-accent" aria-hidden="true">
                <i class="bi bi-music-note-list"></i>
            </div>
        </div>

        <!-- ══ STATS STRIP ═══════════════════════════════════════════════ -->
        <div class="stats-strip mb-4">
            <div class="stat-block">
                <span class="stat-num">{{ activeMembers }}</span>
                <span class="stat-desc">Active Singers</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">{{ publicEvents.length }}</span>
                <span class="stat-desc">Upcoming Events</span>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-block">
                <span class="stat-num">{{ voiceParts.length }}</span>
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
            <div class="col-12 col-lg-7">
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
            <div class="col-12 col-lg-5">
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
        }
    },

    computed: {
        ...mapState(useBmsStore, ['events', 'members']),

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
    },

    methods: {
        ...mapActions(useBmsStore, ['fetchEvents', 'fetchMembers']),

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

/* ══ HERO ════════════════════════════════════════════════════════ */
.bms-hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(
        135deg,
        rgba(127, 36, 50, 0.10) 0%,
        rgba(200, 164, 93, 0.06) 60%,
        transparent 100%
    );
}

.bms-hero-inner {
    position: relative;
    z-index: 1;
    padding: 0.5rem 0;
}

.bms-hero-accent {
    position: absolute;
    right: -1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 9rem;
    opacity: 0.04;
    line-height: 1;
    pointer-events: none;
    user-select: none;
    color: var(--gold-color, #c8a45d);
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

/* ══ RESPONSIVE ══════════════════════════════════════════════════ */
@media (max-width: 767.98px) {
    .bms-hero-accent {
        font-size: 6rem;
        right: -0.5rem;
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
