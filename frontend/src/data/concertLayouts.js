// Concert Seating Layout Registry
//
// Each layout entry:
//   id          - unique key saved in schedules.seat_layout_id
//   name        - display name shown in the picker
//   venue       - suggested venue name
//   description - short description shown in preview panel
//   totalSeats  - pre-computed total seat count
//   sections    - array of seating sections (VVIP, VIP, Regular, etc.)
//
// Each section:
//   id    - unique within this layout
//   label - display label, e.g. "VVIP"
//   color - one of: gold | blue | green | red
//   rows  - array of row definitions
//
// Each row definition:
//   row   - row letter shown to the left, e.g. "A"
//   seats - number of seats (labelled 1..n)
//   gap   - optional array of seat numbers after which an aisle gap is inserted
//           e.g. [7] means a visual gap appears after seat 7

function buildRows(startLetter, count, seatsPerRow, gapAfter) {
  var rows = []
  for (var i = 0; i < count; i++) {
    var letter = String.fromCharCode(startLetter.charCodeAt(0) + i)
    rows.push({ row: letter, seats: seatsPerRow, gap: gapAfter || [] })
  }
  return rows
}

var aulaSifoniaStandard = {
  id: 'aula-simfonia-standard',
  name: 'Aula Simfonia \u2014 Standard',
  venue: 'Aula Simfonia Jakarta',
  description: '3 sections (VVIP / VIP / Regular) with centre aisle. 246 seats total.',
  totalSeats: 246,
  sections: [
    {
      id: 'vvip',
      label: 'VVIP',
      color: 'gold',
      rows: buildRows('A', 3, 14, [7])
    },
    {
      id: 'vip',
      label: 'VIP',
      color: 'blue',
      rows: buildRows('D', 4, 20, [10])
    },
    {
      id: 'regular',
      label: 'Regular',
      color: 'green',
      rows: buildRows('H', 7, 24, [12])
    }
  ]
}

export var CONCERT_LAYOUTS = [
  aulaSifoniaStandard
]

export function getLayoutById(id) {
  for (var i = 0; i < CONCERT_LAYOUTS.length; i++) {
    if (CONCERT_LAYOUTS[i].id === id) return CONCERT_LAYOUTS[i]
  }
  return null
}

export function getAllSeats(layout) {
  var seats = []
  for (var si = 0; si < layout.sections.length; si++) {
    var section = layout.sections[si]
    for (var ri = 0; ri < section.rows.length; ri++) {
      var rowDef = section.rows[ri]
      for (var s = 1; s <= rowDef.seats; s++) {
        seats.push(rowDef.row + s)
      }
    }
  }
  return seats
}
