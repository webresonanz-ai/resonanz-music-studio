<template>
  <div class="layout-picker">

    <!-- ── Currently selected summary ─────────────────────────────────────── -->
    <div v-if="selectedLayout" class="selected-bar d-flex align-items-center gap-3 p-3 rounded mb-3">
      <i class="bi bi-grid-3x3-gap fs-4 text-warning"></i>
      <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold small">{{ selectedLayout.name }}</div>
        <div class="text-muted" style="font-size:0.75rem;">
          {{ selectedLayout.venue }} &middot; {{ selectedLayout.totalSeats }} seats
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" @click="openPicker">
        <i class="bi bi-pencil me-1"></i>Change
      </button>
      <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" @click="clearLayout">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- ── Empty state ─────────────────────────────────────────────────────── -->
    <div v-else>
      <button type="button" class="btn btn-outline-primary w-100 py-3" @click="openPicker">
        <i class="bi bi-grid-3x3-gap me-2"></i>Choose a Seating Layout…
      </button>
    </div>

    <!-- ── Picker modal (Teleported) ──────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="pickerOpen"
        class="layout-picker-backdrop"
        @click.self="closePicker"
        role="dialog"
        aria-modal="true"
        aria-label="Choose seating layout"
      >
        <div class="layout-picker-panel">
          <!-- Header -->
          <div class="layout-picker-header">
            <h5 class="mb-0 fw-bold">
              <i class="bi bi-grid-3x3-gap me-2"></i>Choose Seating Layout
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closePicker" aria-label="Close"></button>
          </div>

          <div class="layout-picker-body">
            <div class="row g-3">

              <!-- Left: layout cards list -->
              <div class="col-lg-4">
                <div class="layout-list">
                  <button
                    v-for="layout in layouts"
                    :key="layout.id"
                    type="button"
                    class="layout-card"
                    :class="{ active: previewLayout && previewLayout.id === layout.id }"
                    @click="previewLayout = layout"
                  >
                    <div class="layout-card-thumb">
                      <LayoutThumbnail :layout="layout" />
                    </div>
                    <div class="layout-card-info">
                      <div class="fw-semibold small">{{ layout.name }}</div>
                      <div class="text-muted" style="font-size:0.72rem;">{{ layout.venue }}</div>
                      <div class="mt-1 d-flex flex-wrap gap-1">
                        <span
                          v-for="sec in layout.sections"
                          :key="sec.id"
                          class="badge section-badge"
                          :class="`section-${sec.color}`"
                        >{{ sec.label }}</span>
                      </div>
                      <div class="text-muted mt-1" style="font-size:0.7rem;">
                        {{ layout.totalSeats }} seats
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Right: full preview -->
              <div class="col-lg-8">
                <div v-if="previewLayout" class="layout-preview-panel">
                  <div class="layout-preview-header mb-2">
                    <div class="fw-bold">{{ previewLayout.name }}</div>
                    <div class="text-muted small">{{ previewLayout.description }}</div>
                  </div>

                  <!-- Stage bar -->
                  <div class="stage-bar mb-3">
                    <i class="bi bi-music-note-beamed me-2"></i>PANGGUNG / STAGE
                  </div>

                  <!-- Section legend -->
                  <div class="d-flex flex-wrap gap-2 mb-3">
                    <span
                      v-for="sec in previewLayout.sections"
                      :key="sec.id"
                      class="legend-chip"
                      :class="`section-${sec.color}`"
                    >
                      <span class="legend-dot"></span>{{ sec.label }}
                    </span>
                  </div>

                  <!-- Seat grid -->
                  <div class="preview-seat-scroll">
                    <div class="preview-seat-grid">
                      <template v-for="sec in previewLayout.sections" :key="sec.id">
                        <div
                          v-for="rowDef in sec.rows"
                          :key="rowDef.row"
                          class="preview-row"
                        >
                          <span class="preview-row-label">{{ rowDef.row }}</span>
                          <template v-for="s in rowDef.seats" :key="s">
                            <span
                              class="preview-seat"
                              :class="`section-${sec.color}`"
                            >{{ s }}</span>
                            <span
                              v-if="rowDef.gap && rowDef.gap.includes(s)"
                              class="preview-aisle"
                            ></span>
                          </template>
                        </div>
                      </template>
                    </div>
                  </div>

                  <div class="mt-3 text-muted small">
                    Total: <strong>{{ previewLayout.totalSeats }}</strong> kursi
                  </div>

                  <button
                    type="button"
                    class="btn btn-primary mt-3"
                    @click="confirmLayout"
                  >
                    <i class="bi bi-check-circle me-2"></i>Use This Layout
                  </button>
                </div>

                <div v-else class="d-flex align-items-center justify-content-center h-100 text-muted">
                  <div class="text-center py-5">
                    <i class="bi bi-arrow-left fs-1 d-block mb-2 opacity-50"></i>
                    Select a layout to preview it here.
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script>
import { CONCERT_LAYOUTS, getLayoutById } from '../../data/concertLayouts.js'
import LayoutThumbnail from './LayoutThumbnail.vue'

export default {
  name: 'ConcertLayoutPicker',
  components: { LayoutThumbnail },

  props: {
    /** The currently-saved layout id (bound with v-model) */
    modelValue: {
      type: String,
      default: null,
    },
  },
  emits: ['update:modelValue'],

  data() {
    return {
      layouts: CONCERT_LAYOUTS,
      pickerOpen: false,
      previewLayout: null,
    }
  },

  computed: {
    selectedLayout() {
      return this.modelValue ? getLayoutById(this.modelValue) : null
    },
  },

  methods: {
    openPicker() {
      // Pre-select currently saved layout in preview panel
      this.previewLayout = this.selectedLayout ?? CONCERT_LAYOUTS[0] ?? null
      this.pickerOpen = true
      document.body.style.overflow = 'hidden'
    },
    closePicker() {
      this.pickerOpen = false
      document.body.style.overflow = ''
    },
    confirmLayout() {
      if (!this.previewLayout) return
      this.$emit('update:modelValue', this.previewLayout.id)
      this.closePicker()
    },
    clearLayout() {
      this.$emit('update:modelValue', null)
    },
  },
}
</script>

<style scoped>
/* ── Selected bar ────────────────────────────────────────── */
.selected-bar {
  background: rgba(200, 164, 93, 0.08);
  border: 1px solid rgba(200, 164, 93, 0.3);
}

/* ── Backdrop / modal shell ──────────────────────────────── */
.layout-picker-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  z-index: 1060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.layout-picker-panel {
  background: var(--surface-color, #fff);
  border-radius: 12px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}

.layout-picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.18), rgba(200, 164, 93, 0.1));
  border-bottom: 1px solid var(--hairline-color, #e0e0e0);
  flex-shrink: 0;
}

.layout-picker-body {
  overflow-y: auto;
  flex: 1;
  padding: 1.25rem;
}

/* ── Layout list (left column) ───────────────────────────── */
.layout-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.layout-card {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1.5px solid var(--hairline-color, #e0e0e0);
  border-radius: 8px;
  background: rgba(255, 253, 248, 0.7);
  cursor: pointer;
  transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
  text-align: left;
  width: 100%;
}

.layout-card:hover {
  border-color: rgba(200, 164, 93, 0.5);
  background: rgba(200, 164, 93, 0.06);
}

.layout-card.active {
  border-color: var(--accent-color, #7f2432);
  background: rgba(127, 36, 50, 0.06);
  box-shadow: 0 0 0 2px rgba(127, 36, 50, 0.12);
}

.layout-card-thumb {
  width: 72px;
  height: 56px;
  flex-shrink: 0;
  border-radius: 5px;
  overflow: hidden;
  border: 1px solid var(--hairline-color, #e0e0e0);
}

.layout-card-info {
  flex: 1;
  min-width: 0;
}

/* ── Section colour badges ───────────────────────────────── */
.section-badge,
.legend-chip .legend-dot {
  font-size: 0.65rem;
  padding: 0.15em 0.5em;
}

.section-gold  { background: rgba(200,164,93,0.18); color: #7a5c00; border: 1px solid rgba(200,164,93,0.4); }
.section-blue  { background: rgba(13,110,253,0.12); color: #0a4bbf; border: 1px solid rgba(13,110,253,0.3); }
.section-green { background: rgba(25,135,84,0.12);  color: #0e5c38; border: 1px solid rgba(25,135,84,0.3); }
.section-red   { background: rgba(220,53,69,0.12);  color: #8b1a25; border: 1px solid rgba(220,53,69,0.3); }

/* ── Right: full preview ─────────────────────────────────── */
.layout-preview-panel {
  padding: 0.5rem;
}

.stage-bar {
  background: linear-gradient(90deg, rgba(127,36,50,0.14), rgba(200,164,93,0.14));
  border: 1px solid rgba(200,164,93,0.3);
  border-radius: 6px;
  text-align: center;
  padding: 0.4rem 1rem;
  font-weight: 700;
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  color: var(--accent-color, #7f2432);
  text-transform: uppercase;
}

.legend-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  padding: 0.2em 0.6em;
  border-radius: 20px;
  font-weight: 600;
}

.legend-chip .legend-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: currentColor;
  display: inline-block;
  opacity: 0.7;
  padding: 0;
}

/* ── Preview seat grid ───────────────────────────────────── */
.preview-seat-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 4px;
}

.preview-seat-grid {
  display: inline-flex;
  flex-direction: column;
  gap: 4px;
  min-width: max-content;
}

.preview-row {
  display: flex;
  align-items: center;
  gap: 3px;
}

.preview-row-label {
  width: 1.3rem;
  font-size: 0.65rem;
  font-weight: 700;
  text-align: center;
  color: var(--accent-color, #7f2432);
  flex-shrink: 0;
}

.preview-seat {
  width: 1.6rem;
  height: 1.4rem;
  border-radius: 3px;
  border: 1px solid transparent;
  font-size: 0.55rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* colour the seats by section */
.preview-seat.section-gold  { background: rgba(200,164,93,0.18); border-color: rgba(200,164,93,0.45); color:#7a5c00; }
.preview-seat.section-blue  { background: rgba(13,110,253,0.1);  border-color: rgba(13,110,253,0.3);  color:#0a4bbf; }
.preview-seat.section-green { background: rgba(25,135,84,0.1);   border-color: rgba(25,135,84,0.3);   color:#0e5c38; }
.preview-seat.section-red   { background: rgba(220,53,69,0.1);   border-color: rgba(220,53,69,0.3);   color:#8b1a25; }

.preview-aisle {
  width: 0.7rem;
  flex-shrink: 0;
}
</style>
