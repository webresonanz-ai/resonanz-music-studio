<script setup>
import { onMounted, ref, computed } from 'vue'
import { useApiStore, useLibraryStore } from '../../stores/api'

const apiStore = useApiStore()
const libraryStore = useLibraryStore()

const loading = ref(true)
const error = ref('')
const orders = ref([])
const search = ref('')

const filteredOrders = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return orders.value
  return orders.value.filter((o) => {
    const haystack = [o.order_number, o.customer, o.status, o.title].join(' ').toLowerCase()
    return haystack.includes(q)
  })
})

const statusBadge = (status) => {
  const map = {
    pending: 'text-bg-warning',
    paid: 'text-bg-success',
    completed: 'text-bg-info',
    cancelled: 'text-bg-danger',
  }
  return map[String(status || '').toLowerCase()] || 'text-bg-secondary'
}

onMounted(async () => {
  try {
    const result = await apiStore.get('/library/orders')
    orders.value = result.data ?? result.orders ?? []
  } catch (err) {
    error.value = err.message || 'Failed to load orders'
  } finally {
    loading.value = false
  }

  libraryStore.fetchScores().catch(() => {})
})
</script>

<template>
  <div>
    <div class="mb-4">
      <h1 class="h3 mb-1">Orders Dashboard</h1>
      <p class="text-white-50 mb-0 small">Score library orders & transactions</p>
    </div>

    <div v-if="error" class="alert alert-warning" role="alert">
      <i class="bi bi-info-circle me-1"></i>Orders could not be loaded: {{ error }}
    </div>

    <div v-if="loading" class="d-flex justify-content-center py-5">
      <div class="spinner-border text-warning" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <template v-else>
      <div class="content-card mb-3">
        <div class="input-group" style="max-width: 420px">
          <span class="input-group-text bg-dark border-secondary text-white-50">
            <i class="bi bi-search"></i>
          </span>
          <input v-model="search" type="search" class="form-control bg-dark text-white border-secondary" placeholder="Search orders..." />
        </div>
      </div>

      <div class="content-card">
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Score</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredOrders.length === 0">
                <td colspan="5" class="text-center text-white-50 py-4">No orders found.</td>
              </tr>
              <tr v-for="order in filteredOrders" :key="order.id || order.order_number">
                <td class="fw-semibold">{{ order.order_number || `#${order.id}` }}</td>
                <td>{{ order.title || '—' }}</td>
                <td>{{ order.customer || '—' }}</td>
                <td>
                  <template v-if="order.amount">
                    {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(order.amount) }}
                  </template>
                  <template v-else>—</template>
                </td>
                <td>
                  <span class="badge" :class="statusBadge(order.status)">{{ order.status || '—' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>