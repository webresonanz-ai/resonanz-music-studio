<template>
  <div class="fade-in-up">
    <div class="content-card bg-dark mb-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <p class="od-eyebrow mb-1">Library</p>
          <h1 class="od-title mb-0">Orders Dashboard</h1>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-gold btn-sm" @click="activeTab = 'orders'" :class="activeTab === 'orders' ? 'btn-gold' : ''">
            <i class="bi bi-receipt me-1"></i>Orders
          </button>
          <button class="btn btn-outline-gold btn-sm" @click="activeTab = 'shares'" :class="activeTab === 'shares' ? 'btn-gold' : ''">
            <i class="bi bi-pie-chart me-1"></i>Profit Shares
          </button>
          <button class="btn btn-outline-gold btn-sm" @click="activeTab = 'profit'; loadCreatorProfit(null)" :class="activeTab === 'profit' ? 'btn-gold' : ''">
            <i class="bi bi-cash-coin me-1"></i>Creator Profit
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-state"><div class="loading-ring"></div><p>Loading…</p></div>

    <template v-else-if="activeTab === 'orders'">
      <div class="od-filters mb-3">
        <div class="row g-2 align-items-center">
          <div class="col-12 col-md-4">
            <input v-model="orderSearch" type="text" class="form-control-dark" placeholder="Search order #, buyer, title…" />
          </div>
          <div class="col-6 col-md-2">
            <select v-model="statusFilter" class="filter-select">
              <option value="">All Status</option>
              <option value="pending_payment">Pending</option>
              <option value="paid">Paid</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <span class="od-count">{{ filteredOrders.length }} order{{ filteredOrders.length !== 1 ? 's' : '' }}</span>
          </div>
        </div>
      </div>

      <div v-if="filteredOrders.length === 0" class="empty-state content-card bg-dark text-center py-5">
        <i class="bi bi-inbox" style="font-size:2rem;color:rgba(234,220,194,0.3)"></i>
        <p class="mt-2 text-champagne-muted small">No orders found</p>
      </div>

      <div v-else class="od-table-wrap content-card bg-dark p-0">
        <div class="od-header">
          <span class="od-th od-th-order">Order</span>
          <span class="od-th od-th-buyer">Buyer</span>
          <span class="od-th od-th-items">Items</span>
          <span class="od-th od-th-total">Total</span>
          <span class="od-th od-th-status">Status</span>
          <span class="od-th od-th-payment">Payment</span>
          <span class="od-th od-th-date">Date</span>
        </div>
        <div v-for="order in filteredOrders" :key="order.id" class="od-row">
          <span class="od-td od-td-order">
            <span class="od-order-num">{{ order.order_number }}</span>
          </span>
          <span class="od-td od-td-buyer">
            <span class="od-buyer-name">{{ order.buyer_name || order.user_name }}</span>
            <span class="od-buyer-email">{{ order.buyer_email || order.user_email }}</span>
          </span>
          <span class="od-td od-td-items">
            <div v-for="item in (order.items || [])" :key="item.id" class="od-item-line">
              <span class="od-item-title">{{ item.title }}</span>
              <span class="od-item-composer">{{ item.composer }}</span>
            </div>
          </span>
          <span class="od-td od-td-total">
            <span class="od-amount">Rp {{ formatPrice(order.total_amount) }}</span>
          </span>
          <span class="od-td od-td-status">
            <span class="od-badge" :class="'od-badge--' + order.status">{{ statusLabel(order.status) }}</span>
          </span>
          <span class="od-td od-td-payment">
            <span class="od-payment-type">{{ order.payment_type || '-' }}</span>
            <span v-if="order.transaction_id" class="od-trans-id" :title="order.transaction_id">{{ order.transaction_id.slice(0, 12) }}…</span>
          </span>
          <span class="od-td od-td-date">
            <span class="od-date">{{ formatDate(order.created_at) }}</span>
          </span>
        </div>
      </div>
    </template>

    <template v-else-if="activeTab === 'shares'">
      <div class="shares-section">
        <div class="content-card bg-dark mb-3">
          <h5 class="shares-heading mb-0"><i class="bi bi-pie-chart me-2"></i>Composer / Arranger Profit Shares</h5>
          <p class="shares-subtitle mb-0">Set the percentage split between the company and the creator for each composer/arranger.</p>
        </div>

        <div v-if="creators.length === 0" class="empty-state content-card bg-dark text-center py-4">
          <i class="bi bi-people" style="font-size:2rem;color:rgba(234,220,194,0.3)"></i>
          <p class="mt-2 text-champagne-muted small">No composers or arrangers found</p>
        </div>

        <div v-else class="shares-grid">
          <div v-for="creator in creators" :key="creator.id" class="share-card">
            <div class="share-card-head">
              <div class="share-avatar"><i class="bi bi-person-fill"></i></div>
              <div class="share-info">
                <span class="share-name">{{ creator.name }}</span>
                <span class="share-role">{{ creator.role }}</span>
              </div>
              <button class="share-edit-btn" @click="editShare(creator)" title="Edit">
                <i class="bi bi-pencil"></i>
              </button>
            </div>
            <div v-if="editingUserId === creator.id" class="share-form">
              <div class="row g-2">
                <div class="col-6">
                  <label class="share-label">Creator %</label>
                  <input v-model.number="editCreatorShare" type="number" min="0" max="100" class="share-input" />
                </div>
                <div class="col-6">
                  <label class="share-label">Company %</label>
                  <input v-model.number="editCompanyShare" type="number" min="0" max="100" class="share-input" />
                </div>
              </div>
              <div class="share-form-actions">
                <button class="btn btn-sm btn-outline-gold" @click="editingUserId = null">Cancel</button>
                <button class="btn btn-sm btn-gold" :disabled="savingShare" @click="saveShare(creator.id)">
                  <span v-if="savingShare" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-check2 me-1"></i>Save
                </button>
              </div>
            </div>
            <div v-else class="share-display">
              <div class="share-bar-wrap">
                <div class="share-bar">
                  <div class="share-bar-fill share-bar--creator" :style="{ width: creatorShare(creator) + '%' }">
                    <span v-if="creatorShare(creator) > 15">{{ creatorShare(creator) }}%</span>
                  </div>
                  <div class="share-bar-fill share-bar--company" :style="{ width: companyShare(creator) + '%' }">
                    <span v-if="companyShare(creator) > 15">{{ companyShare(creator) }}%</span>
                  </div>
                </div>
              </div>
              <div class="share-legend">
                <span class="share-legend-item"><span class="share-dot share-dot--creator"></span>Creator {{ creatorShare(creator) }}%</span>
                <span class="share-legend-item"><span class="share-dot share-dot--company"></span>Company {{ companyShare(creator) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="activeTab === 'profit'">
      <div class="profit-section">
        <div class="content-card bg-dark mb-3">
          <h5 class="shares-heading mb-0"><i class="bi bi-cash-coin me-2"></i>Creator Profit</h5>
          <p class="shares-subtitle mb-0">Select a composer or arranger to view their total profit from paid orders.</p>
        </div>

        <div class="profit-select-wrap content-card bg-dark mb-3">
          <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
              <select v-model="selectedCreatorId" class="filter-select" @change="loadCreatorProfit(selectedCreatorId)">
                <option value="">— Select a creator —</option>
                <option v-for="c in creators" :key="c.id" :value="c.id">{{ c.name }} ({{ c.role }})</option>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <span v-if="profitData" class="od-count">
                {{ profitData.items.length }} score{{ profitData.items.length !== 1 ? 's' : '' }} sold
              </span>
            </div>
          </div>
        </div>

        <div v-if="profitLoading" class="loading-state"><div class="loading-ring"></div><p>Loading…</p></div>

        <div v-else-if="!profitData" class="empty-state content-card bg-dark text-center py-5">
          <i class="bi bi-person-up" style="font-size:2rem;color:rgba(234,220,194,0.3)"></i>
          <p class="mt-2 text-champagne-muted small">Select a creator above to see their profit breakdown</p>
        </div>

        <template v-else>
          <div class="profit-summary content-card bg-dark mb-3">
            <div class="profit-summary-grid">
              <div class="profit-stat">
                <span class="profit-stat-label">Creator</span>
                <span class="profit-stat-value">{{ profitData.creator.name }}</span>
                <span class="profit-stat-role">{{ profitData.creator.role }}</span>
              </div>
              <div class="profit-stat">
                <span class="profit-stat-label">Split</span>
                <span class="profit-stat-value">{{ profitData.creator_share }}% Creator / {{ profitData.company_share }}% Company</span>
                <div class="profit-bar-mini mt-1">
                  <div class="profit-bar-mini-fill profit-bar-mini--creator" :style="{ width: profitData.creator_share + '%' }"></div>
                  <div class="profit-bar-mini-fill profit-bar-mini--company" :style="{ width: profitData.company_share + '%' }"></div>
                </div>
              </div>
              <div class="profit-stat profit-stat--total">
                <span class="profit-stat-label">Gross Revenue</span>
                <span class="profit-stat-value profit-stat-value--gold">Rp {{ formatPrice(profitData.totals.gross_revenue) }}</span>
              </div>
              <div class="profit-stat profit-stat--total">
                <span class="profit-stat-label">Creator Profit</span>
                <span class="profit-stat-value profit-stat-value--creator">Rp {{ formatPrice(profitData.totals.creator_profit) }}</span>
              </div>
              <div class="profit-stat profit-stat--total">
                <span class="profit-stat-label">Company Profit</span>
                <span class="profit-stat-value profit-stat-value--company">Rp {{ formatPrice(profitData.totals.company_profit) }}</span>
              </div>
            </div>
          </div>

          <div class="profit-payout-card content-card bg-dark mb-3">
            <div class="profit-payout-header">
              <h6 class="profit-payout-title"><i class="bi bi-wallet2 me-2"></i>Payout Tracking</h6>
            </div>
            <div class="profit-payout-body">
              <div class="payout-stats">
                <div class="payout-stat">
                  <span class="payout-stat-label">Total Profit</span>
                  <span class="payout-stat-value">Rp {{ formatPrice(profitData.totals.creator_profit) }}</span>
                </div>
                <div class="payout-stat">
                  <span class="payout-stat-label">Paid Out</span>
                  <span class="payout-stat-value payout-stat-value--paid">Rp {{ formatPrice(profitData.total_paid_out) }}</span>
                </div>
                <div class="payout-stat">
                  <span class="payout-stat-label">Balance</span>
                  <span class="payout-stat-value" :class="profitData.balance > 0 ? 'payout-stat-value--due' : 'payout-stat-value--settled'">
                    Rp {{ formatPrice(profitData.balance) }}
                  </span>
                </div>
              </div>
              <button v-if="showPayoutForm !== selectedCreatorId" class="btn btn-sm btn-gold mt-2" @click="openPayoutForm">
                <i class="bi bi-check2-circle me-1"></i>Mark as Paid
              </button>
              <div v-else class="payout-form mt-2">
                <div class="row g-2">
                  <div class="col-12 col-md-4">
                    <label class="payout-form-label">Amount (Rp)</label>
                    <input v-model.number="payoutAmount" type="number" min="0" class="payout-input" placeholder="0" />
                  </div>
                  <div class="col-12 col-md-5">
                    <label class="payout-form-label">Notes (optional)</label>
                    <input v-model="payoutNotes" type="text" class="payout-input" placeholder="e.g. July 2026 payout" />
                  </div>
                  <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-sm btn-outline-gold flex-fill" @click="showPayoutForm = null">Cancel</button>
                    <button class="btn btn-sm btn-gold flex-fill" :disabled="savingPayout || !payoutAmount || payoutAmount <= 0" @click="recordPayout">
                      <span v-if="savingPayout" class="spinner-border spinner-border-sm me-1"></span>
                      <span v-else><i class="bi bi-check2 me-1"></i>Save</span>
                    </button>
                  </div>
                </div>
              </div>
              <button v-if="payoutHistory.length > 0" class="btn btn-sm btn-link payout-toggle mt-2" @click="showHistory = !showHistory">
                <i class="bi bi-clock-history me-1"></i>{{ showHistory ? 'Hide' : 'Show' }} payout history ({{ payoutHistory.length }})
              </button>
              <div v-if="showHistory && payoutHistory.length > 0" class="payout-history mt-2">
                <div v-for="ph in payoutHistory" :key="ph.id" class="payout-history-item">
                  <div class="payout-history-left">
                    <span class="payout-history-amount">Rp {{ formatPrice(ph.amount) }}</span>
                    <span v-if="ph.notes" class="payout-history-notes">{{ ph.notes }}</span>
                  </div>
                  <div class="payout-history-right">
                    <span class="payout-history-date">{{ formatDate(ph.paid_at) }}</span>
                    <span class="payout-history-by">{{ ph.paid_by_name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="od-table-wrap content-card bg-dark p-0">
            <div class="od-header">
              <span class="od-th od-th-order">Title</span>
              <span class="od-th od-th-buyer">Composer / Arranger</span>
              <span class="od-th od-th-total">Units Sold</span>
              <span class="od-th od-th-total">Gross</span>
              <span class="od-th od-th-status">Creator Profit</span>
              <span class="od-th od-th-payment">Company Profit</span>
            </div>
            <div v-for="item in profitData.items" :key="item.score_id" class="od-row">
              <span class="od-td od-td-order">
                <span class="od-order-num">{{ item.title }}</span>
              </span>
              <span class="od-td od-td-buyer">
                <span class="od-buyer-name">{{ item.composer }}</span>
                <span v-if="item.arranger" class="od-buyer-email">{{ item.arranger }}</span>
              </span>
              <span class="od-td od-td-total">
                <span class="od-amount">{{ item.units_sold }}</span>
              </span>
              <span class="od-td od-td-total">
                <span class="od-amount">Rp {{ formatPrice(item.gross_revenue) }}</span>
              </span>
              <span class="od-td od-td-status">
                <span class="od-badge od-badge--paid">Rp {{ formatPrice(item.creator_profit) }}</span>
              </span>
              <span class="od-td od-td-payment">
                <span class="od-payment-type">Rp {{ formatPrice(item.company_profit) }}</span>
              </span>
            </div>
          </div>
        </template>
      </div>
    </template>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useApiStore } from '../../stores/api'

export default {
  name: 'OrdersDashboard',
  setup() {
    const api = useApiStore()
    const loading = ref(true)
    const activeTab = ref('orders')
    const orders = ref([])
    const shares = ref([])
    const creators = ref([])
    const orderSearch = ref('')
    const statusFilter = ref('')
    const editingUserId = ref(null)
    const editCreatorShare = ref(40)
    const editCompanyShare = ref(60)
    const savingShare = ref(false)
    const selectedCreatorId = ref('')
    const profitData = ref(null)
    const profitLoading = ref(false)
    const showPayoutForm = ref(null)
    const payoutAmount = ref(0)
    const payoutNotes = ref('')
    const savingPayout = ref(false)
    const payoutHistory = ref([])
    const showHistory = ref(false)

    const findShare = (userId) => shares.value.find((s) => s.user_id === userId)

    const creatorShare = (creator) => {
      const s = findShare(creator.id)
      return s ? parseFloat(s.creator_share) : 40
    }

    const companyShare = (creator) => {
      const s = findShare(creator.id)
      return s ? parseFloat(s.company_share) : 60
    }

    const filteredOrders = computed(() => {
      const q = orderSearch.value.trim().toLowerCase()
      return orders.value.filter((o) => {
        const matchSearch = !q ||
          o.order_number.toLowerCase().includes(q) ||
          (o.buyer_name || '').toLowerCase().includes(q) ||
          (o.user_name || '').toLowerCase().includes(q) ||
          (o.items || []).some((i) => i.title.toLowerCase().includes(q))
        const matchStatus = !statusFilter.value || o.status === statusFilter.value
        return matchSearch && matchStatus
      })
    })

    const fetchData = async () => {
      loading.value = true
      try {
        const [ordersRes, sharesRes] = await Promise.all([
          api.get('/library/admin/orders'),
          api.get('/library/admin/profit-shares'),
        ])
        orders.value = ordersRes?.data || []
        shares.value = sharesRes?.data || []
        creators.value = sharesRes?.creators || []
      } catch (err) {
        console.error(err)
      } finally {
        loading.value = false
      }
    }

    onMounted(fetchData)

    const editShare = (creator) => {
      const s = findShare(creator.id)
      editCreatorShare.value = s ? parseFloat(s.creator_share) : 40
      editCompanyShare.value = s ? parseFloat(s.company_share) : 60
      editingUserId.value = creator.id
    }

    const saveShare = async (userId) => {
      savingShare.value = true
      try {
        await api.post('/library/admin/profit-shares', {
          user_id: userId,
          creator_share: editCreatorShare.value,
          company_share: editCompanyShare.value,
        })
        editingUserId.value = null
        const res = await api.get('/library/admin/profit-shares')
        shares.value = res?.data || []
        creators.value = res?.creators || []
      } catch (err) {
        alert(err.message || 'Failed to save')
      } finally {
        savingShare.value = false
      }
    }

    const loadCreatorProfit = async (userId) => {
      if (!userId) {
        profitData.value = null
        payoutHistory.value = []
        return
      }
      profitLoading.value = true
      try {
        const [profitRes, historyRes] = await Promise.all([
          api.get(`/library/admin/creator-profit/${userId}`),
          api.get(`/library/admin/creator-payouts/${userId}`),
        ])
        profitData.value = profitRes?.data || null
        payoutHistory.value = historyRes?.data || []
      } catch {
        profitData.value = null
        payoutHistory.value = []
      } finally {
        profitLoading.value = false
      }
    }

    const openPayoutForm = () => {
      showPayoutForm.value = selectedCreatorId.value
      payoutAmount.value = profitData.value?.balance || 0
      payoutNotes.value = ''
    }

    const recordPayout = async () => {
      if (!payoutAmount.value || payoutAmount.value <= 0) return
      savingPayout.value = true
      try {
        await api.post('/library/admin/creator-payout', {
          user_id: selectedCreatorId.value,
          amount: payoutAmount.value,
          notes: payoutNotes.value,
        })
        showPayoutForm.value = null
        await loadCreatorProfit(selectedCreatorId.value)
      } catch (err) {
        alert(err.message || 'Failed to record payout')
      } finally {
        savingPayout.value = false
      }
    }

    const formatPrice = (val) => Number(val || 0).toLocaleString('id-ID')

    const formatDate = (d) => {
      if (!d) return ''
      return new Date(d).toLocaleDateString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
      })
    }

    const statusLabel = (s) => {
      const map = { pending_payment: 'Pending', paid: 'Paid', cancelled: 'Cancelled' }
      return map[s] || s
    }

    return {
      loading, activeTab, orders, shares, creators,
      orderSearch, statusFilter, filteredOrders,
      editingUserId, editCreatorShare, editCompanyShare, savingShare,
      creatorShare, companyShare, editShare, saveShare,
      selectedCreatorId, profitData, profitLoading, loadCreatorProfit,
      showPayoutForm, payoutAmount, payoutNotes, savingPayout, payoutHistory, showHistory,
      openPayoutForm, recordPayout,
      formatPrice, formatDate, statusLabel,
    }
  },
}
</script>

<style scoped>
.od-eyebrow { font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-color) }
.od-title { font-size:clamp(1.4rem,4vw,2rem);font-weight:800;color:rgba(234,220,194,0.92) !important;letter-spacing:-.01em }

.loading-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 1rem;color:rgba(234,220,194,0.5);gap:1rem }
.loading-ring { width:42px;height:42px;border:3px solid rgba(200,164,93,.2);border-top-color:var(--gold-color);border-radius:50%;animation:spin .7s linear infinite }
@keyframes spin { to { transform:rotate(360deg) } }

.od-filters .form-control-dark { padding:.55rem .85rem;background:rgba(26,31,48,0.6);border-color:rgba(234,220,194,0.1);color:rgba(234,220,194,0.78);border-radius:8px;width:100%;outline:none;transition:border-color .2s }
.od-filters .form-control-dark:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.18) }
.filter-select { width:100%;padding:.55rem .85rem;border:1px solid rgba(234,220,194,0.1);border-radius:8px;background:rgba(26,31,48,0.6);color:rgba(234,220,194,0.78);font-size:.85rem;cursor:pointer;outline:none }
.filter-select:focus { border-color:var(--gold-color) }
.od-count { display:block;padding:.55rem 0;font-size:.82rem;color:rgba(234,220,194,0.5);font-weight:600 }

.od-table-wrap.content-card.bg-dark { overflow:hidden;border-radius:10px }
.od-header { display:grid;grid-template-columns:1.2fr 1.2fr 2fr .8fr .8fr .9fr .9fr;gap:.5rem;padding:.65rem 1rem;border-bottom:1px solid rgba(234,220,194,0.08);font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(234,220,194,0.45);background:rgba(16,19,31,0.3);align-items:center }
.od-th { white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.od-row { display:grid;grid-template-columns:1.2fr 1.2fr 2fr .8fr .8fr .9fr .9fr;gap:.5rem;padding:.55rem 1rem;align-items:start;border-bottom:1px solid rgba(234,220,194,0.04);transition:background .15s;font-size:.82rem }
.od-row:last-child { border-bottom:none }
.od-row:hover { background:rgba(200,164,93,.04) }
.od-td { min-width:0;padding:.1rem 0 }

.od-order-num { font-family:monospace;font-weight:700;font-size:.8rem;color:rgba(234,220,194,0.92);display:block }
.od-buyer-name { display:block;font-weight:600;color:rgba(234,220,194,0.85);font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.od-buyer-email { display:block;font-size:.7rem;color:rgba(234,220,194,0.4);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }

.od-item-line { display:flex;flex-direction:column;padding:.15rem 0;border-bottom:1px solid rgba(234,220,194,0.04) }
.od-item-line:last-child { border-bottom:none }
.od-item-title { font-size:.8rem;font-weight:600;color:rgba(234,220,194,0.85);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.od-item-composer { font-size:.68rem;color:rgba(234,220,194,0.4) }

.od-amount { font-weight:700;color:var(--gold-color);font-size:.85rem;white-space:nowrap }

.od-badge { display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:999px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap }
.od-badge--pending_payment { background:rgba(200,164,93,.15);color:var(--gold-color) }
.od-badge--paid { background:rgba(74,124,89,.15);color:#4a7c59 }
.od-badge--cancelled { background:rgba(192,57,43,.12);color:#c0392b }

.od-payment-type { display:block;font-size:.75rem;color:rgba(234,220,194,0.6);text-transform:capitalize }
.od-trans-id { display:block;font-size:.62rem;color:rgba(234,220,194,0.3);font-family:monospace;margin-top:1px }

.od-date { font-size:.75rem;color:rgba(234,220,194,0.5);white-space:nowrap }

@media (max-width:991.98px) {
  .od-header { grid-template-columns:1.2fr 1.2fr 1.5fr .7fr .7fr;font-size:.6rem;padding:.55rem .75rem }
  .od-row { grid-template-columns:1.2fr 1.2fr 1.5fr .7fr .7fr;padding:.5rem .75rem;font-size:.78rem }
  .od-th-items,.od-th-payment { display:none }
  .od-td-items,.od-td-payment { display:none }
}
@media (max-width:575.98px) {
  .od-header { grid-template-columns:1.4fr .8fr .8fr .7fr;padding:.45rem .6rem }
  .od-row { grid-template-columns:1.4fr .8fr .8fr .7fr;padding:.4rem .6rem }
  .od-th-buyer,.od-th-date { display:none }
  .od-td-buyer,.od-td-date { display:none }
}

.shares-heading { font-size:1rem;font-weight:700;color:rgba(234,220,194,0.92) }
.shares-subtitle { font-size:.8rem;color:rgba(234,220,194,0.5);margin-top:.25rem }

.shares-grid { display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(340px,1fr)) }

.share-card { background:rgba(26,31,48,0.5);border:1px solid rgba(234,220,194,0.08);border-radius:12px;overflow:hidden;transition:border-color .2s }
.share-card:hover { border-color:rgba(200,164,93,.2) }

.share-card-head { display:flex;align-items:center;gap:.75rem;padding:.85rem 1rem;border-bottom:1px solid rgba(234,220,194,0.06) }
.share-avatar { width:36px;height:36px;border-radius:50%;background:rgba(200,164,93,.1);display:grid;place-items:center;color:var(--gold-color);font-size:1rem;flex-shrink:0 }
.share-info { flex:1;min-width:0 }
.share-name { display:block;font-size:.88rem;font-weight:700;color:rgba(234,220,194,0.92) }
.share-role { display:block;font-size:.7rem;color:rgba(234,220,194,0.4);text-transform:capitalize }
.share-edit-btn { border:1px solid rgba(234,220,194,0.1);border-radius:6px;background:transparent;color:rgba(234,220,194,0.4);width:32px;height:32px;display:grid;place-items:center;cursor:pointer;transition:all .18s;flex-shrink:0 }
.share-edit-btn:hover { border-color:var(--gold-color);color:var(--gold-color);background:rgba(200,164,93,.08) }

.share-form { padding:.85rem 1rem;background:rgba(16,19,31,0.3) }
.share-label { display:block;font-size:.7rem;font-weight:700;color:rgba(234,220,194,0.5);margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.05em }
.share-input { width:100%;padding:.45rem .65rem;border:1px solid rgba(234,220,194,0.1);border-radius:6px;background:rgba(16,19,31,0.5);color:rgba(234,220,194,0.85);font-size:.82rem;outline:none;transition:border-color .2s;text-align:center }
.share-input:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.12) }
.share-form-actions { display:flex;gap:.5rem;justify-content:flex-end;margin-top:.65rem }

.share-display { padding:.75rem 1rem 1rem }
.share-bar-wrap { margin-bottom:.5rem }
.share-bar { display:flex;height:24px;border-radius:6px;overflow:hidden;background:rgba(234,220,194,0.06) }
.share-bar-fill { display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;transition:width .3s ease;min-width:0 }
.share-bar--creator { background:#4a7c59 }
.share-bar--company { background:var(--gold-color) }
.share-legend { display:flex;gap:1rem;font-size:.72rem;color:rgba(234,220,194,0.5) }
.share-legend-item { display:inline-flex;align-items:center;gap:.35rem }
.share-dot { width:8px;height:8px;border-radius:50%;display:inline-block }
.share-dot--creator { background:#4a7c59 }
.share-dot--company { background:var(--gold-color) }

.empty-state.content-card.bg-dark { color:rgba(234,220,194,0.78) }

.profit-select-wrap { padding:1rem }
.profit-summary { padding:1.2rem }
.profit-summary-grid { display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(180px,1fr)) }
.profit-stat { display:flex;flex-direction:column;gap:.15rem }
.profit-stat--total { text-align:right }
.profit-stat-label { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(234,220,194,0.4) }
.profit-stat-value { font-size:1rem;font-weight:700;color:rgba(234,220,194,0.92) }
.profit-stat-value--gold { color:var(--gold-color);font-size:1.1rem }
.profit-stat-value--creator { color:#4a7c59;font-size:1.1rem }
.profit-stat-value--company { color:var(--gold-color);font-size:1.1rem }
.profit-stat-role { font-size:.75rem;color:rgba(234,220,194,0.4);text-transform:capitalize }
.profit-bar-mini { display:flex;height:8px;border-radius:4px;overflow:hidden;background:rgba(234,220,194,0.06);max-width:160px }
.profit-bar-mini-fill { height:100%;transition:width .3s }
.profit-bar-mini--creator { background:#4a7c59 }
.profit-bar-mini--company { background:var(--gold-color) }

.profit-payout-card { overflow:hidden }
.profit-payout-header { padding:.75rem 1rem;border-bottom:1px solid rgba(234,220,194,0.06);background:rgba(16,19,31,0.3) }
.profit-payout-title { font-size:.85rem;font-weight:700;color:rgba(234,220,194,0.92);margin:0 }
.profit-payout-body { padding:1rem }
.payout-stats { display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:.25rem }
.payout-stat { display:flex;flex-direction:column;gap:.1rem }
.payout-stat-label { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:rgba(234,220,194,0.4) }
.payout-stat-value { font-size:1.1rem;font-weight:700;color:rgba(234,220,194,0.92) }
.payout-stat-value--paid { color:#c0392b }
.payout-stat-value--due { color:#e67e22 }
.payout-stat-value--settled { color:#4a7c59 }
.payout-form { background:rgba(16,19,31,0.4);padding:.85rem;border-radius:8px;border:1px solid rgba(234,220,194,0.06) }
.payout-form-label { display:block;font-size:.68rem;font-weight:700;color:rgba(234,220,194,0.5);margin-bottom:.2rem;text-transform:uppercase;letter-spacing:.04em }
.payout-input { width:100%;padding:.45rem .65rem;border:1px solid rgba(234,220,194,0.1);border-radius:6px;background:rgba(16,19,31,0.5);color:rgba(234,220,194,0.85);font-size:.82rem;outline:none;transition:border-color .2s }
.payout-input:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.12) }
.payout-toggle { color:rgba(234,220,194,0.5);text-decoration:none;font-size:.78rem;padding:0 }
.payout-toggle:hover { color:var(--gold-color) }
.payout-history { display:flex;flex-direction:column;gap:.35rem;background:rgba(16,19,31,0.3);border-radius:8px;padding:.65rem .85rem;max-height:240px;overflow-y:auto }
.payout-history-item { display:flex;justify-content:space-between;align-items:center;gap:.5rem;padding:.35rem 0;border-bottom:1px solid rgba(234,220,194,0.04);font-size:.8rem }
.payout-history-item:last-child { border-bottom:none }
.payout-history-left { display:flex;flex-direction:column;gap:.05rem }
.payout-history-amount { font-weight:700;color:rgba(234,220,194,0.85) }
.payout-history-notes { font-size:.72rem;color:rgba(234,220,194,0.45) }
.payout-history-right { text-align:right;display:flex;flex-direction:column;gap:.05rem;flex-shrink:0 }
.payout-history-date { font-size:.72rem;color:rgba(234,220,194,0.45);white-space:nowrap }
.payout-history-by { font-size:.68rem;color:rgba(234,220,194,0.3);white-space:nowrap }
</style>
