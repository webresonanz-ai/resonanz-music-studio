<template>
  <!--
    Renders a tiny birds-eye thumbnail of the layout using SVG.
    Each section row is drawn as a single thin coloured rectangle.
    Aisle gaps become small white gaps in the rectangle.
  -->
  <svg
    :viewBox="`0 0 ${W} ${H}`"
    :width="W"
    :height="H"
    xmlns="http://www.w3.org/2000/svg"
    style="display:block;width:100%;height:100%;"
    aria-hidden="true"
  >
    <!-- Stage bar at top -->
    <rect x="4" y="2" :width="W - 8" height="6" rx="2" fill="rgba(127,36,50,0.25)" />

    <!-- Seat rows -->
    <template v-for="(item, idx) in rowItems" :key="idx">
      <rect
        :x="item.x"
        :y="item.y"
        :width="item.w"
        height="ROW_H"
        rx="1"
        :fill="item.fill"
        :fill-opacity="0.7"
      />
    </template>
  </svg>
</template>

<script>
const W = 72
const H = 56
const ROW_H = 3
const ROW_GAP = 1.5
const SECTION_GAP = 4
const SIDE_PAD = 6
const TOP_PAD = 12   // below the stage bar

// Colour map matching the section-colour tokens
const FILL_MAP = {
  gold:  '#c8a45d',
  blue:  '#0d6efd',
  green: '#198754',
  red:   '#dc3545',
}

export default {
  name: 'LayoutThumbnail',
  props: {
    layout: { type: Object, required: true },
  },
  data() {
    return { W, H, ROW_H }
  },
  computed: {
    rowItems() {
      const items = []
      let y = TOP_PAD

      for (let si = 0; si < this.layout.sections.length; si++) {
        const sec = this.layout.sections[si]
        const fill = FILL_MAP[sec.color] ?? '#888'

        // Scale row width based on section seats relative to max section seats
        const maxSeats = Math.max(
          ...this.layout.sections.flatMap((s) => s.rows.map((r) => r.seats))
        )

        for (const rowDef of sec.rows) {
          const ratio = rowDef.seats / maxSeats
          const w = (W - SIDE_PAD * 2) * ratio
          const x = SIDE_PAD + ((W - SIDE_PAD * 2) - w) / 2

          // Remaining space — stop drawing if we exceed height
          if (y + ROW_H > H - 2) break

          items.push({ x, y, w, fill })
          y += ROW_H + ROW_GAP
        }

        y += SECTION_GAP
      }

      return items
    },
  },
}
</script>
