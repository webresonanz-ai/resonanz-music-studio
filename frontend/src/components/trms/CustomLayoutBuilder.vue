<template>
  <div class="clb" @click="handleCanvasClick" @mousemove.prevent="handleMouseMove" @mouseleave="cursorCell = null">

    <!-- ══ TOOLBAR ════════════════════════════════════════ -->
    <div class="clb-toolbar" @click.stop>

      <!-- Seat-type palette -->
      <div class="clb-palette">
        <div class="clb-palette-label">Seat Type</div>
        <div class="clb-palette-chips">
          <button
            v-for="(t, i) in SEAT_TYPES"
            :key="t.id"
            type="button"
            class="clb-chip"
            :class="[`st-${t.color}`, { active: tool === t.id }]"
            @click="setTool(t.id)"
            :title="`${t.label} — Ctrl+${i + 1}`"
          >
            <span class="clb-chip-dot"></span>
            {{ t.label }}
            <kbd>Ctrl+{{ i + 1 }}</kbd>
          </button>
          <button
            type="button"
            class="clb-chip st-erase"
            :class="{ active: tool === 'eraser' }"
            @click="setTool('eraser')"
            title="Eraser — Ctrl+0"
          >
            <i class="bi bi-eraser-fill"></i>
            Eraser
            <kbd>Ctrl+0</kbd>
          </button>
          <button
            type="button"
            class="clb-chip st-select"
            :class="{ active: tool === 'select' }"
            @click="setTool('select')"
            title="Select — S"
          >
            <i class="bi bi-cursor-fill"></i>
            Select
            <kbd>S</kbd>
          </button>
        </div>
      </div>

      <!-- Divider -->
      <div class="clb-tool-divider"></div>

      <!-- Align tools (enabled only when ≥ 2 seats selected) -->
      <div class="clb-align">
        <div class="clb-palette-label">Align <span v-if="selection.size > 0">({{ selection.size }} sel)</span></div>
        <div class="clb-align-groups">
          <div class="clb-align-group">
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 2" @click="align('left')" title="Align Left">
              <i class="bi bi-align-start"></i>
            </button>
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 2" @click="align('col')" title="Center Columns">
              <i class="bi bi-align-center"></i>
            </button>
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 2" @click="align('right')" title="Align Right">
              <i class="bi bi-align-end"></i>
            </button>
          </div>
          <div class="clb-align-group">
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 2" @click="align('top')" title="Align Top">
              <i class="bi bi-align-top"></i>
            </button>
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 2" @click="align('row')" title="Center Rows">
              <i class="bi bi-align-middle"></i>
            </button>
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 2" @click="align('bottom')" title="Align Bottom">
              <i class="bi bi-align-bottom"></i>
            </button>
          </div>
          <div class="clb-align-group">
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 3" @click="distribute('h')" title="Distribute Horizontally">
              <i class="bi bi-distribute-horizontal"></i>
            </button>
            <button type="button" class="clb-icon-btn" :disabled="selection.size < 3" @click="distribute('v')" title="Distribute Vertically">
              <i class="bi bi-distribute-vertical"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Divider -->
      <div class="clb-tool-divider"></div>

      <!-- Actions -->
      <div class="clb-actions">
        <button type="button" class="clb-icon-btn" @click="undo" :disabled="history.length === 0" title="Undo (Ctrl+Z)">
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
        <button type="button" class="clb-icon-btn danger" @click="clearAll" :disabled="seats.length === 0" title="Clear All">
          <i class="bi bi-trash3"></i>
        </button>
      </div>
    </div>

    <!-- ══ STATUS BAR ══════════════════════════════════════ -->
    <div class="clb-status" @click.stop>
      <span v-if="tool && tool !== 'select'">
        <i class="bi bi-pencil-fill me-1"></i>
        <strong>{{ getToolLabel(tool) }}</strong> — click grid cells to place seats
      </span>
      <span v-else-if="tool === 'select'">
        <i class="bi bi-cursor-fill me-1"></i>
        Click seats to select · Ctrl+click for multi-select · Delete to remove
      </span>
      <span v-else class="text-muted">Pick a seat type or press Ctrl+1–4</span>
      <span class="clb-status-count">{{ seats.length }} seat{{ seats.length !== 1 ? 's' : '' }}</span>
    </div>

    <!-- ══ STAGE LABEL ════════════════════════════════════ -->
    <div class="clb-stage" @click.stop>
      <i class="bi bi-music-note-beamed me-2"></i>PANGGUNG / STAGE
    </div>

    <!-- ══ CANVAS ═════════════════════════════════════════ -->
    <div class="clb-canvas-outer" @click.stop ref="canvasOuter">
      <div class="clb-canvas" ref="canvas" :style="canvasStyle">

        <!-- Column headers -->
        <div class="clb-header-row">
          <div class="clb-corner"></div>
          <div
            v-for="col in cols"
            :key="col"
            class="clb-col-hdr"
          >{{ col }}</div>
        </div>

        <!-- Data rows -->
        <div
          v-for="row in rows"
          :key="row"
          class="clb-row"
        >
          <!-- Row label -->
          <div class="clb-row-hdr">{{ rowLabel(row - 1) }}</div>

          <!-- Cells -->
          <div
            v-for="col in cols"
            :key="col"
            class="clb-cell"
            :class="getCellClass(row, col)"
            @click.stop="handleCellClick(row, col)"
            @mouseenter="handleCellHover(row, col)"
          >
            <span v-if="getSeat(row, col)" class="clb-seat-label">
              {{ getSeat(row, col).num }}
            </span>
          </div>
        </div>

      </div>
    </div>

    <!-- ══ GRID SIZE CONTROLS ═════════════════════════════ -->
    <div class="clb-grid-controls" @click.stop>
      <div class="clb-grid-ctrl-group">
        <label>Rows</label>
        <div class="clb-stepper">
          <button type="button" @click="adjustGrid('rows', -1)" :disabled="gridRows <= 3"><i class="bi bi-dash"></i></button>
          <span>{{ gridRows }}</span>
          <button type="button" @click="adjustGrid('rows', 1)" :disabled="gridRows >= 30"><i class="bi bi-plus"></i></button>
        </div>
      </div>
      <div class="clb-grid-ctrl-group">
        <label>Columns</label>
        <div class="clb-stepper">
          <button type="button" @click="adjustGrid('cols', -1)" :disabled="gridCols <= 3"><i class="bi bi-dash"></i></button>
          <span>{{ gridCols }}</span>
          <button type="button" @click="adjustGrid('cols', 1)" :disabled="gridCols >= 40"><i class="bi bi-plus"></i></button>
        </div>
      </div>
      <div class="clb-grid-hint">
        <i class="bi bi-lightbulb me-1"></i>
        Hold Ctrl+drag to select multiple seats
      </div>
    </div>

    <!-- ══ SAVE PANEL ════════════════════════════════════ -->
    <div class="clb-save-panel" @click.stop>
      <div class="clb-save-fields">
        <input
          v-model="layoutName"
          type="text"
          class="form-control form-control-sm clb-input"
          placeholder="Layout name (e.g. Main Hall)"
          style="max-width: 220px;"
        />
        <input
          v-model="venueName"
          type="text"
          class="form-control form-control-sm clb-input"
          placeholder="Venue name"
          style="max-width: 180px;"
        />
      </div>
      <div class="clb-save-btns">
        <div v-if="saveError" class="clb-save-error">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ saveError }}
        </div>
        <button type="button" class="btn btn-sm clb-btn-ghost" @click="$emit('cancel')" :disabled="saving">
          <i class="bi bi-x me-1"></i>Cancel
        </button>
        <button type="button" class="btn btn-sm clb-btn-primary" @click="saveLayout" :disabled="seats.length === 0 || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-1 clb-spinner" role="status"></span>
          <i v-else class="bi bi-check-circle me-1"></i>
          {{ saving ? 'Saving…' : 'Use This Layout' }}
        </button>
      </div>
    </div>

  </div>
</template>

<script>
import { useTrmsStore } from '../../stores/api.js'

const SEAT_TYPES = [
  { id: 'vvip',    label: 'VVIP',    color: 'gold'  },
  { id: 'vip',     label: 'VIP',     color: 'blue'  },
  { id: 'regular', label: 'Regular', color: 'green' },
  { id: 'premium', label: 'Premium', color: 'red'   },
]

const CELL_PX = 42 // cell size in pixels

export default {
  name: 'CustomLayoutBuilder',
  emits: ['save', 'cancel'],

  setup() {
    return { trmsStore: useTrmsStore() }
  },

  data() {
    return {
      SEAT_TYPES,
      tool: null,
      seats: [],
      selection: new Set(),
      cursorCell: null,
      isDragging: false,
      history: [],
      gridRows: 12,
      gridCols: 20,
      layoutName: '',
      venueName: '',
      saving: false,
      saveError: '',
    }
  },

  computed: {
    rows() {
      return Array.from({ length: this.gridRows }, (_, i) => i + 1)
    },
    cols() {
      return Array.from({ length: this.gridCols }, (_, i) => i + 1)
    },
    canvasStyle() {
      const colW = CELL_PX
      const rowH = CELL_PX
      const headerW = 32
      const headerH = 24
      return {
        gridTemplateColumns: `${headerW}px repeat(${this.gridCols}, ${colW}px)`,
        gridTemplateRows: `${headerH}px repeat(${this.gridRows}, ${rowH}px)`,
        width: `${headerW + this.gridCols * colW}px`,
      }
    },
    seatMap() {
      // Fast lookup map: 'r3c7' -> seat
      const map = {}
      for (const s of this.seats) map[s.key] = s
      return map
    },
  },

  mounted() {
    document.addEventListener('keydown', this.onKey)
    document.addEventListener('mouseup', this.onMouseUp)
  },

  beforeUnmount() {
    document.removeEventListener('keydown', this.onKey)
    document.removeEventListener('mouseup', this.onMouseUp)
  },

  methods: {
    rowLabel(index) {
      let label = ''
      let n = index
      do {
        label = String.fromCharCode(65 + (n % 26)) + label
        n = Math.floor(n / 26) - 1
      } while (n >= 0)
      return label
    },

    cellKey(row, col) {
      return `r${row}c${col}`
    },

    getSeat(row, col) {
      return this.seatMap[this.cellKey(row, col)] || null
    },

    getCellClass(row, col) {
      const key = this.cellKey(row, col)
      const seat = this.seatMap[key]
      const isHover = this.cursorCell && this.cursorCell.row === row && this.cursorCell.col === col
      const classes = []
      if (seat) {
        classes.push('has-seat', `st-${seat.color}`)
        if (this.selection.has(key)) classes.push('selected')
      } else {
        classes.push('empty')
        if (isHover && this.tool && this.tool !== 'eraser' && this.tool !== 'select') {
          classes.push('hover-place', `st-hover-${this.getCurrentColor()}`)
        }
        if (isHover && this.tool === 'eraser') classes.push('hover-erase')
      }
      return classes
    },

    setTool(id) {
      this.tool = id
      if (id !== 'select') this.selection = new Set()
    },

    getToolLabel(toolId) {
      if (toolId === 'eraser') return 'Eraser'
      if (toolId === 'select') return 'Select'
      return SEAT_TYPES.find((t) => t.id === toolId)?.label ?? toolId
    },

    getCurrentColor() {
      return SEAT_TYPES.find((t) => t.id === this.tool)?.color ?? 'blue'
    },

    // ── Keyboard ─────────────────────────────────────────
    onKey(e) {
      if (e.ctrlKey && e.key >= '1' && e.key <= '4') {
        e.preventDefault()
        const idx = parseInt(e.key) - 1
        if (SEAT_TYPES[idx]) this.setTool(SEAT_TYPES[idx].id)
      } else if (e.ctrlKey && e.key === '0') {
        e.preventDefault()
        this.setTool('eraser')
      } else if (e.key === 's' && !e.ctrlKey && !e.metaKey) {
        this.setTool('select')
      } else if (e.key === 'Escape') {
        this.tool = null
        this.selection = new Set()
      } else if ((e.key === 'Delete' || e.key === 'Backspace') && this.selection.size > 0) {
        e.preventDefault()
        this.pushHistory()
        this.seats = this.seats.filter((s) => !this.selection.has(s.key))
        this.selection = new Set()
        this.reNumber()
      } else if (e.ctrlKey && e.key === 'z') {
        e.preventDefault()
        this.undo()
      } else if (e.ctrlKey && e.key === 'a') {
        e.preventDefault()
        this.tool = 'select'
        this.selection = new Set(this.seats.map((s) => s.key))
      }
    },

    onMouseUp() {
      this.isDragging = false
    },

    // ── Canvas interaction ────────────────────────────────
    handleCanvasClick(e) {
      // Handled by cell clicks
    },

    handleMouseMove(e) {
      // Nothing needed at canvas level
    },

    handleCellHover(row, col) {
      this.cursorCell = { row, col }
      // Paint-drag: if mouse is pressed and we're in paint/eraser mode
      if (this.isDragging && this.tool && this.tool !== 'select') {
        this.applyTool(row, col)
      }
    },

    handleCellClick(row, col) {
      if (!this.tool) return
      if (this.tool === 'select') {
        this.handleSelectClick(row, col)
      } else {
        this.pushHistory()
        this.applyTool(row, col)
        this.isDragging = true
      }
    },

    applyTool(row, col) {
      const key = this.cellKey(row, col)
      if (this.tool === 'eraser') {
        const idx = this.seats.findIndex((s) => s.key === key)
        if (idx !== -1) {
          this.seats.splice(idx, 1)
          this.selection.delete(key)
          this.reNumber()
        }
      } else {
        // Place seat (avoid duplicates)
        if (!this.seatMap[key]) {
          const seatType = SEAT_TYPES.find((t) => t.id === this.tool)
          this.seats.push({
            key,
            row,
            col,
            type: this.tool,
            color: seatType.color,
            num: this.seats.length + 1,
          })
        }
      }
    },

    handleSelectClick(row, col) {
      const key = this.cellKey(row, col)
      const hasSeat = !!this.seatMap[key]
      if (!hasSeat) {
        this.selection = new Set()
        return
      }
      const newSel = new Set(this.selection)
      if (newSel.has(key)) {
        newSel.delete(key)
      } else {
        newSel.add(key)
      }
      this.selection = newSel
    },

    reNumber() {
      // Re-assign seat numbers left-to-right, top-to-bottom
      const sorted = [...this.seats].sort((a, b) =>
        a.row !== b.row ? a.row - b.row : a.col - b.col
      )
      sorted.forEach((s, i) => (s.num = i + 1))
    },

    // ── History ───────────────────────────────────────────
    pushHistory() {
      this.history.push(JSON.parse(JSON.stringify(this.seats)))
      if (this.history.length > 50) this.history.shift()
    },

    undo() {
      if (this.history.length === 0) return
      this.seats = this.history.pop()
      this.selection = new Set()
    },

    clearAll() {
      if (this.seats.length === 0) return
      this.pushHistory()
      this.seats = []
      this.selection = new Set()
    },

    adjustGrid(dim, delta) {
      if (dim === 'rows') {
        const newR = Math.max(3, Math.min(30, this.gridRows + delta))
        // Remove seats outside bounds
        if (delta < 0) {
          this.pushHistory()
          this.seats = this.seats.filter((s) => s.row <= newR)
        }
        this.gridRows = newR
      } else {
        const newC = Math.max(3, Math.min(40, this.gridCols + delta))
        if (delta < 0) {
          this.pushHistory()
          this.seats = this.seats.filter((s) => s.col <= newC)
        }
        this.gridCols = newC
      }
    },

    // ── Alignment ─────────────────────────────────────────
    align(direction) {
      if (this.selection.size < 2) return
      const selected = this.seats.filter((s) => this.selection.has(s.key))
      this.pushHistory()

      if (direction === 'left') {
        const minCol = Math.min(...selected.map((s) => s.col))
        selected.forEach((s) => { s.col = minCol; s.key = this.cellKey(s.row, s.col) })
      } else if (direction === 'right') {
        const maxCol = Math.max(...selected.map((s) => s.col))
        selected.forEach((s) => { s.col = maxCol; s.key = this.cellKey(s.row, s.col) })
      } else if (direction === 'col') {
        const avgCol = Math.round(selected.reduce((sum, s) => sum + s.col, 0) / selected.length)
        selected.forEach((s) => { s.col = avgCol; s.key = this.cellKey(s.row, s.col) })
      } else if (direction === 'top') {
        const minRow = Math.min(...selected.map((s) => s.row))
        selected.forEach((s) => { s.row = minRow; s.key = this.cellKey(s.row, s.col) })
      } else if (direction === 'bottom') {
        const maxRow = Math.max(...selected.map((s) => s.row))
        selected.forEach((s) => { s.row = maxRow; s.key = this.cellKey(s.row, s.col) })
      } else if (direction === 'row') {
        const avgRow = Math.round(selected.reduce((sum, s) => sum + s.row, 0) / selected.length)
        selected.forEach((s) => { s.row = avgRow; s.key = this.cellKey(s.row, s.col) })
      }

      // Dedup: remove seats that now share a key with another
      const keys = new Set()
      this.seats = this.seats.filter((s) => {
        if (keys.has(s.key)) return false
        keys.add(s.key)
        return true
      })
      this.reNumber()
    },

    distribute(dir) {
      if (this.selection.size < 3) return
      const selected = this.seats.filter((s) => this.selection.has(s.key))
      this.pushHistory()

      if (dir === 'h') {
        selected.sort((a, b) => a.col - b.col)
        const minC = selected[0].col
        const maxC = selected[selected.length - 1].col
        const step = (maxC - minC) / (selected.length - 1)
        selected.forEach((s, i) => {
          s.col = Math.round(minC + i * step)
          s.key = this.cellKey(s.row, s.col)
        })
      } else {
        selected.sort((a, b) => a.row - b.row)
        const minR = selected[0].row
        const maxR = selected[selected.length - 1].row
        const step = (maxR - minR) / (selected.length - 1)
        selected.forEach((s, i) => {
          s.row = Math.round(minR + i * step)
          s.key = this.cellKey(s.row, s.col)
        })
      }

      const keys = new Set()
      this.seats = this.seats.filter((s) => {
        if (keys.has(s.key)) return false
        keys.add(s.key)
        return true
      })
      this.reNumber()
    },

    // ── Save ──────────────────────────────────────────────
    async saveLayout() {
      if (this.seats.length === 0) return

      // Convert grid seats → standard section/row format
      const byType = {}
      SEAT_TYPES.forEach((t) => (byType[t.id] = {}))

      for (const seat of this.seats) {
        if (!byType[seat.type]) continue
        if (!byType[seat.type][seat.row]) byType[seat.type][seat.row] = []
        byType[seat.type][seat.row].push(seat.col)
      }

      const sections = []
      let rowLetterBase = 0

      for (const seatType of SEAT_TYPES) {
        const rowMap = byType[seatType.id]
        const rowNums = Object.keys(rowMap).map(Number).sort((a, b) => a - b)
        if (rowNums.length === 0) continue

        const sectionRows = []
        for (const rowNum of rowNums) {
          const cols = rowMap[rowNum].sort((a, b) => a - b)
          const gapAfter = []
          for (let i = 0; i < cols.length - 1; i++) {
            if (cols[i + 1] - cols[i] > 1) gapAfter.push(i + 1)
          }
          sectionRows.push({
            row: this.rowLabel(rowLetterBase),
            seats: cols.length,
            gap: gapAfter,
            _gridRow: rowNum,   // original grid row number (1-based)
            _cols: cols,        // original grid column numbers (1-based)
          })
          rowLetterBase++
        }

        sections.push({
          id: seatType.id,
          label: seatType.label,
          color: seatType.color,
          rows: sectionRows,
        })
      }

      const totalSeats = sections.reduce(
        (sum, s) => sum + s.rows.reduce((rs, r) => rs + r.seats, 0), 0
      )

      const layout = {
        id: `custom-${Date.now()}`,
        name: this.layoutName.trim() || 'Custom Layout',
        venue: this.venueName.trim() || 'Custom Venue',
        description: `Custom layout · ${totalSeats} seats`,
        totalSeats,
        isCustom: true,
        _gridCols: this.gridCols,  // canvas column count for CSS grid sizing
        sections,
      }

      this.saving = true
      this.saveError = ''
      try {
        await this.trmsStore.saveCustomLayout(layout)
        this.$emit('save', layout)
      } catch (err) {
        this.saveError = err.message || 'Failed to save layout. Please try again.'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════
   CUSTOM LAYOUT BUILDER — Dark luxurious theme
   ═══════════════════════════════════════════════════════ */
.clb {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  height: 100%;
  min-height: 0;
  color: rgba(234, 220, 194, 0.85);
}

/* ── Toolbar ─────────────────────────────────────────── */
.clb-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 0.75rem 1.25rem;
  padding: 0.75rem 1rem;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  border: 1px solid rgba(234, 220, 194, 0.1);
  border-radius: 10px;
  flex-shrink: 0;
}

.clb-tool-divider {
  width: 1px;
  align-self: stretch;
  background: rgba(234, 220, 194, 0.12);
  margin: 0 0.25rem;
}

/* ── Palette ─────────────────────────────────────────── */
.clb-palette-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: rgba(234, 220, 194, 0.5);
  margin-bottom: 0.4rem;
}

.clb-palette-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.clb-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.65rem;
  border-radius: 8px;
  border: 1.5px solid transparent;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  background: rgba(234, 220, 194, 0.05);
  line-height: 1;
  color: rgba(234, 220, 194, 0.7);
}

.clb-chip-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  flex-shrink: 0;
}

.clb-chip kbd {
  font-size: 0.6rem;
  padding: 0.1em 0.3em;
  background: rgba(234, 220, 194, 0.1);
  border-radius: 3px;
  font-family: inherit;
  color: inherit;
  opacity: 0.6;
}

/* Seat-type colours */
.clb-chip.st-gold  { border-color: rgba(200,164,93,0.3);  color: #d4b06a; }
.clb-chip.st-blue  { border-color: rgba(79,142,247,0.25); color: #7db0ff; }
.clb-chip.st-green { border-color: rgba(76,175,125,0.25); color: #7ddaa5; }
.clb-chip.st-red   { border-color: rgba(235,80,80,0.25);  color: #f08080; }
.clb-chip.st-erase { border-color: rgba(234,220,194,0.15); color: rgba(234,220,194,0.6); }
.clb-chip.st-select{ border-color: rgba(79,142,247,0.2);  color: #7db0ff; }

.clb-chip.st-gold .clb-chip-dot  { background: #c8a45d; }
.clb-chip.st-blue .clb-chip-dot  { background: #4f8ef7; }
.clb-chip.st-green .clb-chip-dot { background: #4caf7d; }
.clb-chip.st-red .clb-chip-dot   { background: #eb5050; }

.clb-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(0,0,0,0.3);
  background: rgba(234, 220, 194, 0.08);
}

.clb-chip.active.st-gold  { background: rgba(200,164,93,0.18); border-color: #c8a45d; box-shadow: 0 0 0 3px rgba(200,164,93,0.2); }
.clb-chip.active.st-blue  { background: rgba(79,142,247,0.12); border-color: #4f8ef7; box-shadow: 0 0 0 3px rgba(79,142,247,0.15); }
.clb-chip.active.st-green { background: rgba(76,175,125,0.12); border-color: #4caf7d; box-shadow: 0 0 0 3px rgba(76,175,125,0.15); }
.clb-chip.active.st-red   { background: rgba(235,80,80,0.12);  border-color: #eb5050; box-shadow: 0 0 0 3px rgba(235,80,80,0.15); }
.clb-chip.active.st-erase { background: rgba(234,220,194,0.08); border-color: rgba(234,220,194,0.3); box-shadow: 0 0 0 3px rgba(234,220,194,0.1); }
.clb-chip.active.st-select{ background: rgba(79,142,247,0.1);  border-color: #4f8ef7; box-shadow: 0 0 0 3px rgba(79,142,247,0.12); }

/* ── Align group ─────────────────────────────────────── */
.clb-align-groups {
  display: flex;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.clb-align-group {
  display: flex;
  gap: 1px;
  background: rgba(234, 220, 194, 0.08);
  border-radius: 6px;
  padding: 2px;
}

.clb-icon-btn {
  padding: 0.3rem 0.5rem;
  border: none;
  background: rgba(234, 220, 194, 0.04);
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
  color: rgba(234, 220, 194, 0.6);
  transition: background 0.13s, color 0.13s;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.clb-icon-btn:hover:not(:disabled) {
  background: rgba(79, 142, 247, 0.12);
  color: #7db0ff;
}

.clb-icon-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.clb-icon-btn.danger:hover:not(:disabled) {
  background: rgba(235, 80, 80, 0.12);
  color: #f08080;
}

/* ── Actions ─────────────────────────────────────────── */
.clb-actions {
  display: flex;
  gap: 0.4rem;
  align-items: flex-end;
  padding-top: 1.15rem;
}

/* ── Status bar ──────────────────────────────────────── */
.clb-status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.45rem 0.85rem;
  background: rgba(200, 164, 93, 0.05);
  border: 1px solid rgba(200, 164, 93, 0.12);
  border-radius: 8px;
  font-size: 0.78rem;
  flex-shrink: 0;
  color: rgba(234, 220, 194, 0.7);
}

.clb-status .text-muted {
  color: rgba(234, 220, 194, 0.4) !important;
}

.clb-status-count {
  font-weight: 700;
  font-size: 0.8rem;
  opacity: 0.6;
  color: rgba(234, 220, 194, 0.5);
}

/* ── Stage bar ───────────────────────────────────────── */
.clb-stage {
  background: linear-gradient(90deg, rgba(127,36,50,0.2), rgba(200,164,93,0.15));
  border: 1px solid rgba(200,164,93,0.25);
  border-radius: 8px;
  text-align: center;
  padding: 0.45rem 1rem;
  font-weight: 700;
  font-size: 0.78rem;
  letter-spacing: 0.12em;
  color: var(--gold-color, #c8a45d);
  text-transform: uppercase;
  flex-shrink: 0;
}

/* ── Canvas wrapper ──────────────────────────────────── */
.clb-canvas-outer {
  flex: 1;
  min-height: 240px;
  overflow: auto;
  border: 1.5px solid rgba(234, 220, 194, 0.1);
  border-radius: 10px;
  background: rgba(10, 12, 20, 0.6);
  -webkit-overflow-scrolling: touch;
}

/* ── Canvas grid ─────────────────────────────────────── */
.clb-canvas {
  display: grid;
  padding: 8px;
  gap: 0;
  min-width: max-content;
}

/* Header row — spans all grid columns via CSS grid auto placement */
.clb-header-row {
  display: contents;
}

.clb-corner {
  /* top-left spacer */
}

.clb-col-hdr {
  text-align: center;
  font-size: 0.6rem;
  font-weight: 700;
  color: rgba(234, 220, 194, 0.35);
  padding-bottom: 2px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.clb-row {
  display: contents;
}

.clb-row-hdr {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--gold-color, #c8a45d);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ── Cell ────────────────────────────────────────────── */
.clb-cell {
  width: 42px;
  height: 42px;
  border: 1.5px solid rgba(200,164,93,0.1);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: crosshair;
  transition: background 0.1s, border-color 0.1s, transform 0.1s;
  font-size: 0.65rem;
  font-weight: 600;
  user-select: none;
  position: relative;
}

/* Empty hover previews */
.clb-cell.hover-place         { background: rgba(234,220,194,0.06); }
.clb-cell.st-hover-gold       { background: rgba(200,164,93,0.2) !important; border-color: #c8a45d !important; }
.clb-cell.st-hover-blue       { background: rgba(79,142,247,0.13) !important; border-color: #4f8ef7 !important; }
.clb-cell.st-hover-green      { background: rgba(76,175,125,0.13) !important; border-color: #4caf7d !important; }
.clb-cell.st-hover-red        { background: rgba(235,80,80,0.13) !important; border-color: #eb5050 !important; }
.clb-cell.hover-erase         { background: rgba(235,80,80,0.1); border-color: #eb5050; cursor: not-allowed; }

/* Occupied seats */
.clb-cell.has-seat { cursor: pointer; }
.clb-cell.has-seat:hover { transform: scale(1.08); z-index: 2; }

.clb-cell.st-gold  { background: rgba(200,164,93,0.2);  border-color: rgba(200,164,93,0.5);  color: #d4b06a; }
.clb-cell.st-blue  { background: rgba(79,142,247,0.12); border-color: rgba(79,142,247,0.35); color: #7db0ff; }
.clb-cell.st-green { background: rgba(76,175,125,0.12); border-color: rgba(76,175,125,0.35); color: #7ddaa5; }
.clb-cell.st-red   { background: rgba(235,80,80,0.12);  border-color: rgba(235,80,80,0.35);  color: #f08080; }

/* Selection ring */
.clb-cell.selected {
  outline: 3px solid var(--gold-color, #c8a45d);
  outline-offset: -2px;
  z-index: 5;
}

.clb-seat-label {
  pointer-events: none;
  line-height: 1;
}

/* ── Grid controls ───────────────────────────────────── */
.clb-grid-controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  padding: 0.6rem 1rem;
  background: rgba(200, 164, 93, 0.04);
  border: 1px solid rgba(200, 164, 93, 0.12);
  border-radius: 8px;
  flex-shrink: 0;
}

.clb-grid-ctrl-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.clb-grid-ctrl-group label {
  font-size: 0.75rem;
  font-weight: 600;
  margin: 0;
  color: rgba(234, 220, 194, 0.5);
}

.clb-stepper {
  display: flex;
  align-items: center;
  gap: 0;
  border: 1px solid rgba(234, 220, 194, 0.1);
  border-radius: 6px;
  overflow: hidden;
}

.clb-stepper button {
  width: 28px;
  height: 28px;
  border: none;
  background: rgba(234, 220, 194, 0.06);
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.13s;
  color: rgba(234, 220, 194, 0.6);
}

.clb-stepper button:hover:not(:disabled) {
  background: rgba(200, 164, 93, 0.15);
  color: var(--gold-color, #c8a45d);
}

.clb-stepper button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.clb-stepper span {
  padding: 0 0.55rem;
  font-size: 0.8rem;
  font-weight: 700;
  min-width: 2rem;
  text-align: center;
  background: rgba(234, 220, 194, 0.03);
  line-height: 28px;
  border-left: 1px solid rgba(234, 220, 194, 0.1);
  border-right: 1px solid rgba(234, 220, 194, 0.1);
  color: rgba(234, 220, 194, 0.8);
}

.clb-grid-hint {
  font-size: 0.72rem;
  color: rgba(234, 220, 194, 0.4);
  margin-left: auto;
}

/* ── Save panel ──────────────────────────────────────── */
.clb-save-panel {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(234, 220, 194, 0.08);
  flex-shrink: 0;
}

.clb-save-fields {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  flex: 1;
}

.clb-save-btns {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.clb-save-error {
  font-size: 0.75rem;
  color: #f08080;
  background: rgba(235, 80, 80, 0.1);
  border: 1px solid rgba(235, 80, 80, 0.25);
  border-radius: 6px;
  padding: 0.3rem 0.6rem;
}

/* ── Inputs ──────────────────────────────────────────── */
.clb-input {
  background: rgba(234, 220, 194, 0.06);
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 8px;
  color: #fffdf8;
  font-size: 0.85rem;
  transition:
    border-color 0.2s ease,
    background 0.2s ease,
    box-shadow 0.2s ease;
}

.clb-input:focus {
  border-color: rgba(200, 164, 93, 0.4);
  background: rgba(234, 220, 194, 0.09);
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.1);
  color: #fffdf8;
}

.clb-input::placeholder {
  color: rgba(234, 220, 194, 0.3);
}

/* ── Buttons ──────────────────────────────────────────── */
.clb-btn-primary {
  border: 1px solid #9d7d3b;
  color: #17130a;
  background: linear-gradient(180deg, #d6b66c 0%, var(--gold-color, #c8a45d) 100%);
  box-shadow: 0 8px 20px rgba(122, 94, 39, 0.24);
  font-weight: 700;
  border-radius: 8px;
  padding: 0.45rem 1rem;
  font-size: 0.82rem;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.clb-btn-primary:hover:not(:disabled) {
  border-color: #8f6e2f;
  color: #111;
  background: linear-gradient(180deg, #e1c47f 0%, #b99245 100%);
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(122, 94, 39, 0.35);
}

.clb-btn-primary:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.clb-btn-ghost {
  border: 1px solid rgba(234, 220, 194, 0.15);
  color: rgba(234, 220, 194, 0.65);
  background: transparent;
  font-weight: 500;
  border-radius: 8px;
  padding: 0.45rem 1rem;
  font-size: 0.82rem;
  transition:
    transform 0.2s ease,
    background 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.clb-btn-ghost:hover {
  border-color: rgba(234, 220, 194, 0.25);
  color: rgba(234, 220, 194, 0.85);
  background: rgba(234, 220, 194, 0.06);
  transform: translateY(-2px);
}

.clb-spinner {
  color: #17130a;
}
</style>
