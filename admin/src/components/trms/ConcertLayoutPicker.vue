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
      <button type="button" class="btn btn-sm picker-btn-outline flex-shrink-0" @click="openPicker">
        <i class="bi bi-pencil me-1"></i>Change
      </button>
      <button type="button" class="btn btn-sm picker-btn-outline picker-btn-outline-danger flex-shrink-0" @click="clearLayout">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- ── Empty state ─────────────────────────────────────────────────────── -->
    <div v-else>
      <button type="button" class="btn picker-btn-ghost w-100 py-3" @click="openPicker">
        <i class="bi bi-grid-3x3-gap me-2"></i>Choose a Seating Layout&hellip;
      </button>
    </div>

    <!-- ── Picker modal (Teleported) ──────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="pickerOpen"
        class="picker-backdrop"
        @click.self="closePicker"
        role="dialog"
        aria-modal="true"
        aria-label="Choose seating layout"
      >
        <div class="picker-panel">
          <!-- Header -->
          <div class="picker-header">
            <h5 class="mb-0 fw-bold picker-header-title">
              <i class="bi bi-grid-3x3-gap me-2"></i>Choose Seating Layout
            </h5>
            <button type="button" class="btn-close picker-close" @click="closePicker" aria-label="Close"></button>
          </div>

          <div class="picker-body">
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
                      <div class="fw-semibold small layout-card-name">{{ layout.name }}</div>
                      <div class="layout-card-meta">{{ layout.venue }}</div>
                      <div class="mt-1 d-flex flex-wrap gap-1">
                        <span
                          v-for="sec in layout.sections"
                          :key="sec.id"
                          class="badge section-badge"
                          :class="`section-${sec.color}`"
                        >{{ sec.label }}</span>
                      </div>
                      <div class="layout-card-meta mt-1">
                        {{ layout.totalSeats }} seats
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Right: full preview -->
              <div class="col-lg-8">
                <div v-if="previewLayout" class="preview-panel">
                  <div class="preview-info mb-2">
                    <div class="preview-name">{{ previewLayout.name }}</div>
                    <div class="preview-desc">{{ previewLayout.description }}</div>
                  </div>

                  <!-- Stage bar -->
                  <div class="picker-stage-bar mb-3">
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

                  <div class="mt-3 picker-seat-total">
                    Total: <strong>{{ previewLayout.totalSeats }}</strong> kursi
                  </div>

                  <button
                    type="button"
                    class="btn picker-btn-primary mt-3"
                    @click="confirmLayout"
                  >
                    <i class="bi bi-check-circle me-2"></i>Use This Layout
                  </button>
                </div>

                <div v-else class="picker-empty-preview">
                  <div class="text-center py-5">
                    <i class="bi bi-arrow-left fs-1 d-block mb-2 picker-empty-icon"></i>
                    <span class="picker-empty-text">Select a layout to preview it here.</span>
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
import { CONCERT_LAYOUTS, getLayoutById, registerCustomLayout } from '../../data/concertLayouts.js'
import LayoutThumbnail from './LayoutThumbnail.vue'

export default {
  name: 'ConcertLayoutPicker',
  components: { LayoutThumbnail },

  props: {
    modelValue: { type: String, default: null },
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
      if (!this.modelValue) return null
      return getLayoutById(this.modelValue)
    },
  },

  methods: {
    async openPicker() {
      this.pickerOpen = true
      document.body.style.overflow = 'hidden'

      // Load any saved custom layouts from the DB into the registry + list
      try {
        const { useTrmsStore } = await import('../../stores/api.js')
        const store = useTrmsStore()
        const saved = await store.fetchAllCustomLayouts()
        for (const row of saved) {
          // Only fetch full data if not already in registry
          if (!getLayoutById(row.layout_key)) {
            try {
              const full = await store.fetchCustomLayout(row.layout_key)
              registerCustomLayout(full)
            } catch {
              // skip broken entries
            }
          }
        }
        this.layouts = [...CONCERT_LAYOUTS]
      } catch {
        // non-fatal: just show preset layouts
      }

      this.previewLayout = this.selectedLayout ?? CONCERT_LAYOUTS[0] ?? null
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
  border: 1px solid rgba(200, 164, 93, 0.2);
  border-radius: 10px;
  color: rgba(234, 220, 194, 0.85);
}

.selected-bar .text-muted {
  color: rgba(234, 220, 194, 0.5) !important;
}

/* ── Backdrop / modal shell ──────────────────────────────── */
.picker-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(10, 10, 18, 0.7);
  backdrop-filter: blur(6px);
  z-index: 1060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.picker-panel {
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 14px;
  width: 100%;
  max-width: 1100px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.04) inset,
    0 24px 56px rgba(8, 8, 14, 0.55);
  color: rgba(234, 220, 194, 0.85);
}

.picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.2), rgba(200, 164, 93, 0.08));
  border-bottom: 1px solid rgba(234, 220, 194, 0.08);
  flex-shrink: 0;
}

.picker-header-title {
  color: var(--gold-color, #c8a45d);
  font-weight: 700;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
}

.picker-close {
  filter: brightness(0) invert(0.8);
  opacity: 0.6;
  transition: opacity 0.2s ease;
}

.picker-close:hover {
  opacity: 1;
}

.picker-body {
  overflow-y: auto;
  flex: 1;
  padding: 1.25rem;
  min-height: 0;
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
  border: 1.5px solid rgba(234, 220, 194, 0.1);
  border-radius: 10px;
  background: rgba(234, 220, 194, 0.03);
  cursor: pointer;
  transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
  text-align: left;
  width: 100%;
  color: rgba(234, 220, 194, 0.85);
}

.layout-card:hover {
  border-color: rgba(200, 164, 93, 0.4);
  background: rgba(200, 164, 93, 0.06);
}

.layout-card.active {
  border-color: var(--gold-color, #c8a45d);
  background: rgba(127, 36, 50, 0.15);
  box-shadow: 0 0 0 2px rgba(200, 164, 93, 0.15);
}

.layout-card-thumb {
  width: 72px;
  height: 56px;
  flex-shrink: 0;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid rgba(234, 220, 194, 0.1);
  background: rgba(234, 220, 194, 0.04);
}

.layout-card-info {
  flex: 1;
  min-width: 0;
}

.layout-card-name {
  color: rgba(234, 220, 194, 0.85);
}

.layout-card-meta {
  color: rgba(234, 220, 194, 0.5);
  font-size: 0.72rem;
}

/* ── Section colour badges ───────────────────────────────── */
.section-badge,
.legend-chip .legend-dot {
  font-size: 0.65rem;
  padding: 0.15em 0.5em;
}

.section-gold  { background: rgba(200,164,93,0.18); color: #d4b06a; border: 1px solid rgba(200,164,93,0.35); }
.section-blue  { background: rgba(79,142,247,0.15); color: #7db0ff; border: 1px solid rgba(79,142,247,0.3); }
.section-green { background: rgba(76,175,125,0.15); color: #7ddaa5; border: 1px solid rgba(76,175,125,0.3); }
.section-red   { background: rgba(235,80,80,0.15);  color: #f08080; border: 1px solid rgba(235,80,80,0.3); }

/* ── Right: full preview ─────────────────────────────────── */
.preview-panel {
  padding: 0.5rem;
}

.preview-info {
  margin-bottom: 0.5rem;
}

.preview-name {
  font-weight: 700;
  color: var(--gold-color, #c8a45d);
  font-size: 1rem;
}

.preview-desc {
  color: rgba(234, 220, 194, 0.55);
  font-size: 0.82rem;
}

.picker-stage-bar {
  background: linear-gradient(90deg, rgba(127,36,50,0.2), rgba(200,164,93,0.15));
  border: 1px solid rgba(200,164,93,0.25);
  border-radius: 8px;
  text-align: center;
  padding: 0.45rem 1rem;
  font-weight: 700;
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  color: var(--gold-color, #c8a45d);
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
  color: var(--gold-color, #c8a45d);
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
.preview-seat.section-gold  { background: rgba(200,164,93,0.2);  border-color: rgba(200,164,93,0.4);  color: #d4b06a; }
.preview-seat.section-blue  { background: rgba(79,142,247,0.12); border-color: rgba(79,142,247,0.3);  color: #7db0ff; }
.preview-seat.section-green { background: rgba(76,175,125,0.12); border-color: rgba(76,175,125,0.3);  color: #7ddaa5; }
.preview-seat.section-red   { background: rgba(235,80,80,0.12);  border-color: rgba(235,80,80,0.3);   color: #f08080; }

.preview-aisle {
  width: 0.7rem;
  flex-shrink: 0;
}

.picker-seat-total {
  color: rgba(234, 220, 194, 0.55);
  font-size: 0.82rem;
}

.picker-empty-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  min-height: 300px;
}

.picker-empty-icon {
  color: rgba(234, 220, 194, 0.2);
}

.picker-empty-text {
  color: rgba(234, 220, 194, 0.35);
  font-size: 0.9rem;
}

/* ── Buttons ──────────────────────────────────────────────── */
.picker-btn-ghost {
  border: 1.5px solid rgba(234, 220, 194, 0.15);
  color: rgba(234, 220, 194, 0.7);
  background: transparent;
  font-weight: 600;
  border-radius: 10px;
  font-size: 0.9rem;
  transition:
    transform 0.2s ease,
    background 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.picker-btn-ghost:hover {
  border-color: rgba(200, 164, 93, 0.4);
  color: rgba(234, 220, 194, 0.9);
  background: rgba(200, 164, 93, 0.06);
  transform: translateY(-2px);
}

.picker-btn-outline {
  border: 1px solid rgba(234, 220, 194, 0.15);
  color: rgba(234, 220, 194, 0.7);
  background: transparent;
  font-weight: 500;
  border-radius: 8px;
  font-size: 0.82rem;
  transition:
    transform 0.2s ease,
    background 0.2s ease,
    border-color 0.2s ease;
}

.picker-btn-outline:hover {
  border-color: rgba(200, 164, 93, 0.4);
  color: rgba(234, 220, 194, 0.9);
  background: rgba(200, 164, 93, 0.08);
  transform: translateY(-1px);
}

.picker-btn-outline-danger:hover {
  border-color: rgba(224, 80, 80, 0.5) !important;
  color: #f06060 !important;
  background: rgba(224, 80, 80, 0.1) !important;
}

.picker-btn-primary {
  border: 1px solid #9d7d3b;
  color: #17130a;
  background: linear-gradient(180deg, #d6b66c 0%, var(--gold-color, #c8a45d) 100%);
  box-shadow: 0 12px 28px rgba(122, 94, 39, 0.24);
  font-weight: 700;
  border-radius: 8px;
  padding: 0.5rem 1.25rem;
  font-size: 0.9rem;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.picker-btn-primary:hover {
  border-color: #8f6e2f;
  color: #111;
  background: linear-gradient(180deg, #e1c47f 0%, #b99245 100%);
  transform: translateY(-2px);
  box-shadow: 0 16px 32px rgba(122, 94, 39, 0.35);
}
</style>