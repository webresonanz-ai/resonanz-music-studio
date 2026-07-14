<template>
  <div class="fade-in-up">
    <div class="content-card bg-dark mb-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <p class="orders-eyebrow mb-1">Library</p>
          <h1 class="orders-title mb-0">My Orders</h1>
        </div>
        <router-link to="/library/sheet-music" class="btn btn-outline-gold btn-sm">
          <i class="bi bi-music-note-beamed me-1"></i>Browse Scores
        </router-link>
      </div>
    </div>

    <div v-if="loading" class="loading-state"><div class="loading-ring"></div><p>Loading orders…</p></div>

    <template v-else>
      <div v-if="orders.length === 0" class="empty-state content-card bg-dark">
        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
        <h5 class="mt-3 mb-1 text-champagne">No orders yet</h5>
        <p class="text-champagne-muted mb-3">You haven't purchased any sheet music yet.</p>
        <router-link to="/library/sheet-music" class="btn btn-gold btn-sm">
          <i class="bi bi-cart3 me-1"></i>Start Shopping
        </router-link>
      </div>

      <div v-else class="orders-page-list">
        <div v-for="order in orders" :key="order.id" class="order-card">
          <div class="order-card-head">
            <div class="order-card-head-left">
              <span class="order-id">{{ order.order_number }}</span>
              <span class="order-date">{{ formatDate(order.created_at) }}</span>
            </div>
            <span class="order-badge" :class="'order-badge--' + order.status">
              {{ statusLabel(order.status) }}
            </span>
          </div>

          <div class="order-card-body">
            <div v-for="item in order.items" :key="item.id" class="order-item-row">
              <div class="order-item-info">
                <span class="order-item-title">{{ item.title }}</span>
                <span class="order-item-composer">{{ item.composer }}</span>
              </div>
              <span class="order-item-price">Rp {{ formatPrice(item.price) }}</span>
            </div>
          </div>

          <div class="order-card-foot">
            <div class="order-total">
              <span class="order-total-label">Total</span>
              <span class="order-total-value">Rp {{ formatPrice(order.total_amount) }}</span>
            </div>
            <div class="order-actions">
              <button class="btn btn-sm btn-outline-gold" @click="toggleDetail(order)">
                <i class="bi bi-eye me-1"></i>Detail
              </button>
              <button v-if="order.status === 'pending_payment'" class="btn btn-sm btn-gold" @click="openPay(order)">
                <i class="bi bi-credit-card me-1"></i>Pay Now
              </button>
              <button v-if="order.status === 'pending_payment'" class="btn btn-sm btn-outline-danger" @click="openCancel(order)">
                <i class="bi bi-x-circle me-1"></i>Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="selectedOrder" class="modal-overlay" @click.self="selectedOrder = null">
          <div class="modal-sheet modal-sheet-dark" role="dialog" aria-modal="true">
            <button class="modal-close-btn modal-close-btn-dark" @click="selectedOrder = null" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="modal-header-row d-flex align-items-center gap-2 mb-3">
              <div class="modal-icon-wrap"><i class="bi bi-receipt"></i></div>
              <div>
                <h5 class="mb-0 text-champagne">Order {{ selectedOrder.order_number }}</h5>
                <p class="text-champagne-muted mb-0 small">{{ formatDate(selectedOrder.created_at) }} &middot; {{ statusLabel(selectedOrder.status) }}</p>
              </div>
            </div>
            <div class="order-detail-items">
              <div v-for="item in selectedOrder.items" :key="item.id" class="order-detail-row">
                <div class="order-detail-info">
                  <span class="order-detail-title">{{ item.title }}</span>
                  <span class="order-detail-muted">{{ item.composer }}</span>
                </div>
                <span class="order-detail-price">Rp {{ formatPrice(item.price) }}</span>
              </div>
            </div>
            <div class="order-detail-total">
              <span>Total</span>
              <strong>Rp {{ formatPrice(selectedOrder.total_amount) }}</strong>
            </div>
            <div v-if="selectedOrder.status === 'pending_payment'" class="order-detail-pay">
              <p class="mb-2 small text-champagne-muted">Complete your payment to download the sheet music.</p>
              <button class="btn btn-gold w-100" @click="selectedOrder = null; openPay(selectedOrder)">
                <i class="bi bi-credit-card me-1"></i>Pay Now
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="cancelTarget" class="modal-overlay" @click.self="cancelTarget = null">
          <div class="modal-sheet modal-sheet-dark modal-sheet--sm text-center" role="dialog" aria-modal="true">
            <div class="delete-icon-wrap"><i class="bi bi-x-circle" style="font-size:2rem;color:var(--gold-color)"></i></div>
            <h5 class="mt-3 mb-1 text-champagne">Cancel Order</h5>
            <p class="text-champagne-muted small mb-3">Cancel <strong style="color:rgba(234,220,194,0.92)">{{ cancelTarget.order_number }}</strong>? This cannot be undone.</p>
            <div class="d-flex gap-2 justify-content-center">
              <button class="btn btn-sm btn-outline-gold" @click="cancelTarget = null" :disabled="cancelling">Back</button>
              <button class="btn btn-sm btn-danger" @click="doCancel" :disabled="cancelling">
                <span v-if="cancelling" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-check2 me-1"></i>Yes, Cancel
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="payOrder" class="modal-overlay" @click.self="payOrder = null">
          <div class="modal-sheet modal-sheet-dark modal-sheet--sm text-center" role="dialog" aria-modal="true">
            <div class="pay-icon-wrap"><i class="bi bi-credit-card-2-front"></i></div>
            <h5 class="mt-3 mb-1 text-champagne">Complete Payment</h5>
            <p class="text-champagne-muted small mb-3">Pay securely via Midtrans</p>
            <div class="pay-details">
              <div class="pay-detail-row">
                <span class="pay-label">Order</span>
                <span class="pay-value">{{ payOrder.order_number }}</span>
              </div>
              <div class="pay-detail-row">
                <span class="pay-label">Amount</span>
                <span class="pay-value pay-amount">Rp {{ formatPrice(payOrder.total_amount) }}</span>
              </div>
            </div>
            <p class="text-champagne-muted small mt-3 mb-4">You will be redirected to the Midtrans payment page.</p>
            <div class="d-flex gap-2 justify-content-center">
              <button class="btn btn-outline-gold btn-sm" @click="payOrder = null">Cancel</button>
              <button class="btn btn-gold btn-sm" :disabled="paying" @click="doPay">
                <span v-if="paying" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-credit-card me-1"></i>Pay Now
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useCartStore } from '../../stores/cart'

export default {
  name: 'MyOrders',
  setup() {
    const cartStore = useCartStore()
    const orders = ref([])
    const loading = ref(true)
    const selectedOrder = ref(null)
    const payOrder = ref(null)
    const paying = ref(false)
    const cancelTarget = ref(null)
    const cancelling = ref(false)
    const pollTimer = ref(null)

    const hasPending = computed(() =>
      orders.value.some((o) => o.status === 'pending_payment')
    )

    const fetchOrders = async () => {
      loading.value = true
      try {
        const result = await cartStore.fetchOrders()
        orders.value = result
      } catch {
        orders.value = []
      } finally {
        loading.value = false
      }
    }

    const startPolling = () => {
      stopPolling()
      pollTimer.value = setInterval(async () => {
        await fetchOrders()
        if (!hasPending.value) stopPolling()
      }, 10000)
    }

    const stopPolling = () => {
      if (pollTimer.value) {
        clearInterval(pollTimer.value)
        pollTimer.value = null
      }
    }

    onMounted(async () => {
      await fetchOrders()
      if (hasPending.value) startPolling()
    })

    onUnmounted(stopPolling)

    const formatPrice = (val) => Number(val || 0).toLocaleString('id-ID')

    const formatDate = (d) => {
      if (!d) return ''
      return new Date(d).toLocaleDateString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
      })
    }

    const statusLabel = (s) => {
      const map = { pending_payment: 'Pending Payment', paid: 'Paid', cancelled: 'Cancelled' }
      return map[s] || s
    }

    const toggleDetail = (order) => { selectedOrder.value = order }
    const openPay = (order) => { payOrder.value = order }
    const openCancel = (order) => { cancelTarget.value = order }

    const doCancel = async () => {
      if (!cancelTarget.value) return
      cancelling.value = true
      try {
        await cartStore.cancelOrder(cancelTarget.value.id)
        cancelTarget.value = null
        await fetchOrders()
      } catch (err) {
        alert(err.message || 'Cancel failed')
      } finally {
        cancelling.value = false
      }
    }

    const doPay = async () => {
      if (!payOrder.value) return
      paying.value = true
      try {
        await cartStore.payWithSnap(payOrder.value.id)
        payOrder.value = null
        await fetchOrders()
        if (hasPending.value) startPolling()
      } catch (err) {
        console.error('Payment failed:', err)
        alert(err.message || 'Payment failed')
      } finally {
        paying.value = false
      }
    }

    return {
      orders, loading, selectedOrder, payOrder, paying,
      cancelTarget, cancelling,
      formatPrice, formatDate, statusLabel, hasPending,
      toggleDetail, openPay, openCancel, doCancel,
      fetchOrders, doPay,
    }
  },
}
</script>

<style scoped>
.orders-eyebrow { font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-color) }
.orders-title { font-size:clamp(1.4rem,4vw,2rem);font-weight:800;color:rgba(234,220,194,0.92) !important;letter-spacing:-.01em }

.loading-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 1rem;color:rgba(234,220,194,0.5);gap:1rem }
.loading-ring { width:42px;height:42px;border:3px solid rgba(200,164,93,.2);border-top-color:var(--gold-color);border-radius:50%;animation:spin .7s linear infinite }
@keyframes spin { to { transform:rotate(360deg) } }
.empty-state.content-card.bg-dark { text-align:center;padding:4rem 2rem;color:rgba(234,220,194,0.78) }
.empty-icon { display:inline-grid;place-items:center;width:72px;height:72px;border-radius:50%;background:rgba(200,164,93,.1);border:1px solid rgba(200,164,93,.2) }
.empty-icon i { font-size:2rem;color:var(--gold-color) }

.orders-page-list { display:grid;gap:1rem }
.order-card { background:rgba(26,31,48,0.5);border:1px solid rgba(234,220,194,0.08);border-radius:12px;overflow:hidden;transition:border-color .2s,box-shadow .2s }
.order-card:hover { border-color:rgba(200,164,93,.25);box-shadow:0 4px 20px rgba(0,0,0,.15) }

.order-card-head { display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.85rem 1rem;border-bottom:1px solid rgba(234,220,194,0.06) }
.order-card-head-left { display:flex;align-items:center;gap:.75rem;flex-wrap:wrap }
.order-id { font-size:.8rem;font-weight:800;color:rgba(234,220,194,0.92);font-family:monospace }
.order-date { font-size:.72rem;color:rgba(234,220,194,0.4) }
.order-badge { display:inline-flex;align-items:center;padding:.2rem .65rem;border-radius:999px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap }
.order-badge--pending_payment { background:rgba(200,164,93,.15);color:var(--gold-color) }
.order-badge--paid { background:rgba(74,124,89,.15);color:#4a7c59 }
.order-badge--cancelled { background:rgba(192,57,43,.12);color:#c0392b }

.order-card-body { padding:.5rem 1rem }
.order-item-row { display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.4rem 0;border-bottom:1px solid rgba(234,220,194,0.04) }
.order-item-row:last-child { border-bottom:none }
.order-item-info { display:flex;flex-direction:column;min-width:0 }
.order-item-title { font-size:.85rem;font-weight:600;color:rgba(234,220,194,0.85) }
.order-item-composer { font-size:.72rem;color:rgba(234,220,194,0.4) }
.order-item-price { font-size:.82rem;font-weight:600;color:var(--gold-color);white-space:nowrap;flex-shrink:0 }

.order-card-foot { display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.65rem 1rem;border-top:1px solid rgba(234,220,194,0.08);flex-wrap:wrap }
.order-total { display:flex;align-items:center;gap:.5rem }
.order-total-label { font-size:.8rem;color:rgba(234,220,194,0.5) }
.order-total-value { font-size:.95rem;font-weight:800;color:var(--gold-color) }
.order-actions { display:flex;gap:.5rem }

.order-detail-items { background:rgba(16,19,31,0.3);border-radius:8px;padding:.5rem .75rem }
.order-detail-row { display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.4rem 0;border-bottom:1px solid rgba(234,220,194,0.04) }
.order-detail-row:last-child { border-bottom:none }
.order-detail-info { display:flex;flex-direction:column }
.order-detail-title { font-size:.85rem;font-weight:600;color:rgba(234,220,194,0.85) }
.order-detail-muted { font-size:.72rem;color:rgba(234,220,194,0.4) }
.order-detail-price { font-size:.82rem;font-weight:600;color:var(--gold-color) }
.order-detail-total { display:flex;align-items:center;justify-content:space-between;padding:.65rem .25rem 0;font-size:.9rem;color:rgba(234,220,194,0.92) }
.order-detail-total strong { color:var(--gold-color);font-size:1rem }
.order-detail-pay { margin-top:.85rem;padding-top:.85rem;border-top:1px solid rgba(234,220,194,0.08) }

.pay-icon-wrap { display:grid;place-items:center;width:56px;height:56px;border-radius:50%;background:rgba(200,164,93,.12);margin:0 auto }
.pay-icon-wrap i { font-size:1.8rem;color:var(--gold-color) }
.pay-details { background:rgba(16,19,31,0.4);border-radius:8px;padding:.65rem .85rem;text-align:left }
.pay-detail-row { display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.3rem 0;font-size:.82rem }
.pay-label { color:rgba(234,220,194,0.45);font-weight:600;flex-shrink:0 }
.pay-value { color:rgba(234,220,194,0.85) }
.pay-amount { color:var(--gold-color);font-weight:800;font-size:.95rem }
.pay-account { font-family:monospace;font-weight:700;letter-spacing:.05em }

@media (max-width:575.98px) {
  .order-card-head { flex-direction:column;align-items:flex-start }
  .order-card-foot { flex-direction:column;align-items:stretch }
  .order-actions { justify-content:flex-end }
}

.modal-overlay { position:fixed;inset:0;z-index:1050;background:rgba(10,10,15,.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1rem;overflow-y:auto }
@media (min-width:576px) { .modal-overlay { padding:1.5rem } }
.modal-sheet { position:relative;border-radius:14px;border:1px solid var(--hairline-color);box-shadow:0 32px 72px rgba(10,10,15,.36),0 0 0 1px rgba(200,164,93,.1);width:100%;max-width:640px;padding:1.25rem;margin:auto }
@media (min-width:576px) { .modal-sheet { padding:1.75rem } }
.modal-sheet-dark { background:linear-gradient(135deg,rgba(26,31,48,0.98),rgba(17,20,32,0.98));border-color:rgba(234,220,194,0.1);box-shadow:0 32px 72px rgba(0,0,0,.5),0 0 0 1px rgba(200,164,93,.1) }
.modal-sheet--sm { max-width:400px;padding:1.5rem 1.25rem }
@media (min-width:576px) { .modal-sheet--sm { padding:2rem } }
.modal-close-btn { position:absolute;top:.75rem;right:.75rem;border:0;width:34px;height:34px;border-radius:8px;display:grid;place-items:center;font-size:.85rem;cursor:pointer;transition:background .18s,color .18s }
.modal-close-btn-dark { background:rgba(200,164,93,0.1);color:rgba(234,220,194,0.5) }
.modal-close-btn-dark:hover { background:var(--gold-color);color:#fff }
.modal-icon-wrap { display:grid;place-items:center;width:40px;height:40px;border-radius:10px;background:rgba(200,164,93,.12);border:1px solid rgba(200,164,93,.2);color:var(--gold-color);font-size:1.2rem;flex-shrink:0 }
.delete-icon-wrap { display:grid;place-items:center;width:56px;height:56px;border-radius:50%;background:rgba(192,57,43,.12);margin:0 auto }

.modal-enter-active,.modal-leave-active { transition:opacity .25s ease }
.modal-enter-active .modal-sheet,.modal-leave-active .modal-sheet { transition:transform .25s ease,opacity .25s ease }
.modal-enter-from,.modal-leave-to { opacity:0 }
.modal-enter-from .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }
.modal-leave-to .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }
</style>
