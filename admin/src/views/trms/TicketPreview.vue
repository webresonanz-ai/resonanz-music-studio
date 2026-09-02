<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

defineOptions({ name: 'TicketPreview' })

const route = useRoute()
const router = useRouter()

const ticket = computed(() => ({
  id: route.params.id,
  regNumber: route.query.reg || route.params.id,
  guest: route.query.name || 'Guest',
  concert: route.query.concert || 'Resonanz Concert',
  date: route.query.date || '—',
  venue: route.query.venue || 'Aula Simfonia Jakarta',
  seat: route.query.seat || '—',
}))

function printTicket() {
  window.print()
}
</script>

<template>
  <div class="ticket-page">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div>
        <h1 class="h3 mb-1">Ticket Preview</h1>
        <p class="text-white-50 mb-0 small">Preview of the guest admission ticket</p>
      </div>
      <div>
        <button class="btn btn-outline-gold me-2" type="button" @click="router.back()">
          <i class="bi bi-arrow-left me-2"></i>Back
        </button>
        <button class="btn btn-gold" type="button" @click="printTicket">
          <i class="bi bi-printer me-2"></i>Print
        </button>
      </div>
    </div>

    <div class="ticket-card mx-auto">
      <div class="ticket-header">
        <img src="/logo_resonanz_bgwhite.webp" alt="Resonanz Logo" width="56" height="56" class="ticket-logo" />
        <div>
          <div class="ticket-brand">RESONANZ</div>
          <div class="ticket-sub">Concert Admission Ticket</div>
        </div>
        <div class="ticket-reg">#{{ ticket.regNumber }}</div>
      </div>

      <div class="ticket-body">
        <div class="ticket-guest">{{ ticket.guest }}</div>
        <div class="ticket-concert">{{ ticket.concert }}</div>

        <div class="row g-3 mt-2">
          <div class="col-6">
            <div class="ticket-label">Date</div>
            <div class="ticket-value">{{ ticket.date }}</div>
          </div>
          <div class="col-6">
            <div class="ticket-label">Seat</div>
            <div class="ticket-value">{{ ticket.seat }}</div>
          </div>
          <div class="col-12">
            <div class="ticket-label">Venue</div>
            <div class="ticket-value">{{ ticket.venue }}</div>
          </div>
        </div>
      </div>

      <div class="ticket-ticket">
        <div class="perf">&bull;</div>
        <div class="ticket-zone text-center">
          <i class="bi bi-qr-code fs-1"></i>
          <div class="ticket-label mt-1">SCAN AT DOOR</div>
        </div>
        <div class="perf">&bull;</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ticket-page {
  padding-bottom: 2rem;
}

.ticket-card {
  width: min(100%, 480px);
  border-radius: 16px;
  overflow: hidden;
  background: #fffdf8;
  color: #191b24;
  box-shadow: 0 24px 60px rgba(19, 18, 16, 0.35);
}

.ticket-header {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  padding: 1.1rem 1.4rem;
  background: linear-gradient(180deg, #0f111b, #232b40);
  color: #fff;
}

.ticket-logo {
  border-radius: 10px;
}

.ticket-brand {
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: var(--gold-color);
  line-height: 1;
}

.ticket-sub {
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.7);
}

.ticket-reg {
  margin-left: auto;
  font-size: 0.78rem;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.75);
}

.ticket-body {
  padding: 1.4rem;
}

.ticket-guest {
  font-size: 1.5rem;
  font-weight: 800;
}

.ticket-concert {
  color: #7f2432;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.ticket-label {
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #6f6a61;
}

.ticket-value {
  font-weight: 700;
}

.ticket-ticket {
  display: flex;
  align-items: center;
  gap: 1rem;
  border-top: 2px dashed #d8d2c5;
  padding: 1.2rem 1.4rem;
  background: rgba(245, 241, 233, 0.7);
}

.perf {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  border-radius: 999px;
  background: #1a1f30;
  color: var(--gold-color);
  display: grid;
  place-items: center;
  font-size: 1.1rem;
}

.ticket-zone {
  flex: 1;
}

@media print {
  .ticket-card {
    box-shadow: none;
  }
}
</style>