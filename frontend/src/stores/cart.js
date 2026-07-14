import { defineStore } from 'pinia'
import { useApiStore } from './api'
import { useAuthStore } from './auth'

const CART_KEY = 'resonanz-cart'

function loadCart() {
  try {
    return JSON.parse(localStorage.getItem(CART_KEY) || '[]')
  } catch {
    return []
  }
}

function saveCart(items) {
  localStorage.setItem(CART_KEY, JSON.stringify(items))
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: loadCart(),
  }),

  getters: {
    count: (state) => state.items.length,
    total: (state) => state.items.reduce((sum, i) => sum + i.price, 0),
    isEmpty: (state) => state.items.length === 0,
  },

  actions: {
    addItem(score) {
      const exists = this.items.find((i) => i.score_id === score.id)
      if (exists) return
      this.items.push({
        score_id: score.id,
        title: score.title,
        composer: score.composer,
        arranger: score.arranger || '',
        price: score.price || 0,
        thumbnail: score.thumbnail || '',
      })
      saveCart(this.items)
    },

    removeItem(scoreId) {
      this.items = this.items.filter((i) => i.score_id !== scoreId)
      saveCart(this.items)
    },

    clear() {
      this.items = []
      saveCart(this.items)
    },

    async checkout(payload = {}) {
      const authStore = useAuthStore()
      if (!authStore.token) {
        throw new Error('Please login first')
      }
      if (this.items.length === 0) {
        throw new Error('Cart is empty')
      }
      const api = useApiStore()
      const result = await api.post('/library/orders', {
        items: this.items.map((i) => ({ score_id: i.score_id })),
        buyer_name: payload.buyer_name || authStore.user?.name || '',
        buyer_email: payload.buyer_email || authStore.user?.email || '',
        notes: payload.notes || '',
      })
      if (result?.success) {
        this.clear()
      }
      return result
    },

    async fetchOrders() {
      const authStore = useAuthStore()
      if (!authStore.token) return []
      const api = useApiStore()
      const result = await api.get('/library/orders')
      return result?.data || []
    },

    async getSnapToken(orderId) {
      const api = useApiStore()
      const result = await api.post(`/library/orders/${orderId}/snap-token`, {})
      return result?.data || null
    },

    async cancelOrder(orderId) {
      const api = useApiStore()
      return api.post(`/library/orders/${orderId}/cancel`, {})
    },

    async payWithSnap(orderId) {
      const data = await this.getSnapToken(orderId)
      if (!data?.snap_token) throw new Error('Failed to get payment token')

      return new Promise((resolve, reject) => {
        const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY
        const isSandbox = import.meta.env.VITE_MIDTRANS_IS_SANDBOX === 'true'
        if (!clientKey) {
          reject(new Error('Midtrans client key not configured'))
          return
        }

        const snapSrc = isSandbox
          ? 'https://app.sandbox.midtrans.com/snap/snap.js'
          : 'https://app.midtrans.com/snap/snap.js'

        const loadSnap = () => {
          if (window.snap) {
            window.snap.pay(data.snap_token, {
              onSuccess: (result) => resolve(result),
              onPending: (result) => resolve(result),
              onError: (err) => reject(err),
              onClose: () => resolve({ closed: true }),
            })
          } else {
            const script = document.createElement('script')
            script.src = snapSrc
            script.setAttribute('data-client-key', clientKey)
            script.onload = () => {
              window.snap.pay(data.snap_token, {
                onSuccess: (result) => resolve(result),
                onPending: (result) => resolve(result),
                onError: (err) => reject(err),
                onClose: () => resolve({ closed: true }),
              })
            }
            script.onerror = () => reject(new Error('Failed to load Midtrans Snap'))
            document.head.appendChild(script)
          }
        }

        loadSnap()
      })
    },
  },
})
