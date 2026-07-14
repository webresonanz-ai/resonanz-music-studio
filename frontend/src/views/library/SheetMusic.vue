<template>
  <div class="fade-in-up">
    <div class="content-card bg-dark mb-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <p class="sheet-eyebrow mb-1">Library</p>
          <h1 class="sheet-title mb-0">Sheet Music</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <button class="btn btn-outline-gold btn-sm d-flex align-items-center gap-1 position-relative" @click="showCart = true">
            <i class="bi bi-cart3"></i><span class="d-none d-sm-inline">Cart</span>
            <span v-if="cartStore.count > 0" class="cart-badge">{{ cartStore.count }}</span>
          </button>
          <button class="btn btn-sm btn-outline-gold" @click="toggleOrders">
            <i class="bi bi-clock-history"></i>
          </button>
          <button class="btn btn-sm" :class="viewMode === 'grid' ? 'btn-gold' : 'btn-outline-gold'" @click="viewMode = 'grid'">
            <i class="bi bi-grid-3x3-gap-fill"></i>
          </button>
          <button class="btn btn-sm" :class="viewMode === 'list' ? 'btn-gold' : 'btn-outline-gold'" @click="viewMode = 'list'">
            <i class="bi bi-list-ul"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="filters-bar mb-4">
      <div class="row g-2">
        <div class="col-12 col-md-6">
          <div class="search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input v-model="search" type="text" class="form-control-dark search-input" placeholder="Search by title, composer, or arranger…" />
            <button v-if="search" class="search-clear" @click="search = ''" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterGenre" class="filter-select">
            <option value="">All Genres</option>
            <option v-for="g in genres" :key="g" :value="g">{{ g }}</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterComposer" class="filter-select">
            <option value="">All Composers</option>
            <option v-for="c in composers" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
      </div>
      <div class="filter-chips mt-2">
        <button class="filter-chip" :class="{ active: filterDifficulty === '' }" @click="filterDifficulty = ''">All Levels</button>
        <button v-for="d in difficulties" :key="d" class="filter-chip" :class="{ active: filterDifficulty === d }" @click="filterDifficulty = d">{{ d }}</button>
      </div>
    </div>

    <div v-if="loading" class="loading-state"><div class="loading-ring"></div><p>Loading sheet music…</p></div>

    <template v-else>
      <div v-if="filteredScores.length === 0" class="empty-state content-card bg-dark">
        <div class="empty-icon"><i class="bi bi-music-note"></i></div>
        <h5 class="mt-3 mb-1 text-champagne">No scores found</h5>
        <p class="text-champagne-muted mb-0">Try adjusting your filters.</p>
      </div>

      <div v-if="viewMode === 'grid'" class="scores-grid">
        <article v-for="(score, idx) in filteredScores" :key="score.id" class="score-card" :style="`animation-delay:${idx * 0.04}s`" @click="openDetail(score)">
          <div class="score-thumb-wrap">
            <img :src="score.thumbnail || defaultThumb" :alt="score.title" class="score-thumb" loading="lazy" />
            <div class="score-thumb-overlay"><i class="bi bi-eye-fill"></i></div>
            <span class="score-badge">{{ score.difficulty }}</span>
            <span v-if="score.price > 0" class="price-badge">Rp {{ formatPrice(score.price) }}</span>
          </div>
          <div class="score-body">
            <h6 class="score-title">{{ score.title }}</h6>
            <p class="score-composer">{{ score.composer }}</p>
            <div class="score-meta">
              <span class="score-meta-item"><i class="bi bi-tag"></i>{{ score.genre }}</span>
              <span class="score-meta-item"><i class="bi bi-file-earmark-text"></i>{{ score.pages }} pp</span>
            </div>
            <button v-if="score.price > 0" class="btn btn-gold btn-sm w-100 mt-2 add-to-cart-btn" @click.stop="addToCart(score)">
              <i class="bi bi-cart-plus me-1"></i>{{ isInCart(score.id) ? 'In Cart' : 'Add to Cart' }}
            </button>
          </div>
          <div v-if="isAdmin" class="score-actions" @click.stop>
            <button class="action-btn action-edit" title="Edit" @click="openEdit(score)"><i class="bi bi-pencil"></i></button>
            <button class="action-btn action-delete" title="Delete" @click="confirmDelete(score)"><i class="bi bi-trash3"></i></button>
          </div>
        </article>
      </div>

      <div v-if="viewMode === 'list'" class="scores-list content-card bg-dark p-0">
        <div class="list-header">
          <span class="lh-thumb"></span><span class="lh-title">Title</span><span class="lh-composer">Composer</span>
          <span class="lh-arranger">Arranger</span><span class="lh-genre">Genre</span><span class="lh-difficulty">Level</span>
          <span class="lh-price">Price</span><span v-if="isAdmin" class="lh-actions">Actions</span>
        </div>
        <div v-for="(score, idx) in filteredScores" :key="score.id" class="list-row" :style="`animation-delay:${idx * 0.03}s`" @click="openDetail(score)">
          <span class="lh-thumb"><img :src="score.thumbnail || defaultThumb" :alt="score.title" class="list-thumb" loading="lazy" /></span>
          <span class="lh-title"><span class="list-title">{{ score.title }}</span></span>
          <span class="lh-composer list-muted">{{ score.composer }}</span>
          <span class="lh-arranger list-muted">{{ score.arranger }}</span>
          <span class="lh-genre"><span class="genre-pill">{{ score.genre }}</span></span>
          <span class="lh-difficulty"><span class="diff-badge" :class="diffClass(score.difficulty)">{{ score.difficulty }}</span></span>
          <span class="lh-price"><span class="price-value">{{ score.price > 0 ? 'Rp ' + formatPrice(score.price) : 'FREE' }}</span></span>
          <span v-if="isAdmin" class="lh-actions list-actions" @click.stop>
            <button class="action-btn action-edit" title="Edit" @click="openEdit(score)"><i class="bi bi-pencil"></i></button>
            <button class="action-btn action-delete" title="Delete" @click="confirmDelete(score)"><i class="bi bi-trash3"></i></button>
          </span>
        </div>
      </div>

      <div v-if="filteredScores.length > 0" class="result-count">
        Showing {{ filteredScores.length }} score{{ filteredScores.length !== 1 ? 's' : '' }}
      </div>
    </template>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="selectedScore && !showForm && !deleteTarget" class="modal-overlay" @click.self="selectedScore = null">
          <div class="modal-sheet modal-sheet-dark" role="dialog" aria-modal="true">
            <button class="modal-close-btn modal-close-btn-dark" @click="selectedScore = null" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="d-flex flex-column flex-md-row gap-4">
              <div class="detail-thumb-wrap"><img :src="selectedScore.thumbnail || defaultThumb" :alt="selectedScore.title" class="detail-thumb" /></div>
              <div class="flex-fill">
                <h3 class="detail-title">{{ selectedScore.title }}</h3>
                <div class="detail-field"><span class="df-label">Composer</span><span class="df-value">{{ selectedScore.composer }}</span></div>
                <div class="detail-field"><span class="df-label">Arranger</span><span class="df-value">{{ selectedScore.arranger || '–' }}</span></div>
                <div class="detail-field"><span class="df-label">Genre</span><span class="df-value">{{ selectedScore.genre }}</span></div>
                <div class="detail-field"><span class="df-label">Difficulty</span><span class="df-value"><span class="diff-badge" :class="diffClass(selectedScore.difficulty)">{{ selectedScore.difficulty }}</span></span></div>
                <div class="detail-field"><span class="df-label">Pages</span><span class="df-value">{{ selectedScore.pages }}</span></div>
                <div class="detail-field"><span class="df-label">Price</span><span class="df-value"><strong class="price-highlight">{{ selectedScore.price > 0 ? 'Rp ' + formatPrice(selectedScore.price) : 'FREE' }}</strong></span></div>
                <div class="d-flex gap-2 mt-3 flex-wrap">
                  <button v-if="selectedScore.price > 0 && !isInCart(selectedScore.id)" class="btn btn-gold btn-sm" @click.stop="addToCart(selectedScore)"><i class="bi bi-cart-plus me-1"></i>Add to Cart</button>
                  <button v-if="isInCart(selectedScore.id)" class="btn btn-sm btn-outline-danger" @click.stop="removeFromCart(selectedScore.id)"><i class="bi bi-cart-dash me-1"></i>Remove</button>
                  <a v-if="selectedScore.file_url" :href="selectedScore.file_url" target="_blank" class="btn btn-outline-gold btn-sm"><i class="bi bi-eye me-1"></i>Preview</a>
                  <button v-if="authStore.token && isInCart(selectedScore.id)" class="btn btn-gold btn-sm" @click.stop="closeDetailAndCheckout"><i class="bi bi-credit-card me-1"></i>Checkout</button>
                </div>
                <div v-if="isAdmin" class="d-flex gap-2 mt-2">
                  <button class="btn btn-sm btn-outline-gold" @click="openEdit(selectedScore)"><i class="bi bi-pencil me-1"></i>Edit</button>
                  <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(selectedScore)"><i class="bi bi-trash3 me-1"></i>Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
          <div class="modal-sheet modal-sheet-dark modal-sheet--form" role="dialog" aria-modal="true">
            <button class="modal-close-btn modal-close-btn-dark" @click="closeForm" aria-label="Close"><i class="bi bi-x-lg"></i></button>

            <div class="modal-header-row d-flex align-items-center gap-3 mb-4">
              <div class="modal-icon-wrap"><i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i></div>
              <div>
                <h5 class="form-modal-title mb-1">{{ isEditing ? 'Edit Score' : 'Add Score' }}</h5>
                <p class="form-modal-subtitle mb-0">{{ isEditing ? 'Update the score details below' : 'Fill in the details for the new score' }}</p>
              </div>
            </div>

            <div v-if="formError" class="form-alert form-alert--error">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ formError }}
            </div>

            <form @submit.prevent="submitForm" class="score-form">
              <div class="form-section">
                <div class="form-section-head">
                  <i class="bi bi-info-circle"></i>
                  <span>Basic Information</span>
                </div>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">
                      Title <span class="text-danger">*</span>
                    </label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-music-note-beamed input-icon"></i>
                      <input v-model="form.title" type="text" class="form-input" placeholder="e.g. Ave Maria" required />
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Composer</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-person-fill input-icon"></i>
                      <input v-model="form.composer" type="text" class="form-input" placeholder="Composer name" />
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Arranger</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-person-fill input-icon"></i>
                      <input v-model="form.arranger" type="text" class="form-input" placeholder="Arranger name (optional)" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <div class="form-section-head">
                  <i class="bi bi-tags"></i>
                  <span>Classification</span>
                </div>
                <div class="row g-3">
                  <div class="col-12 col-sm-4">
                    <label class="form-label">Genre</label>
                    <div class="select-wrap">
                      <i class="bi bi-bookmark-fill select-icon"></i>
                      <select v-model="form.genre" class="form-select-custom">
                        <option value="">Select genre</option>
                        <option v-for="g in allGenres" :key="g" :value="g">{{ g }}</option>
                      </select>
                      <i class="bi bi-chevron-down select-chevron"></i>
                    </div>
                  </div>
                  <div class="col-12 col-sm-4">
                    <label class="form-label">Difficulty</label>
                    <div class="select-wrap">
                      <i class="bi bi-bar-chart-fill select-icon"></i>
                      <select v-model="form.difficulty" class="form-select-custom">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                      </select>
                      <i class="bi bi-chevron-down select-chevron"></i>
                    </div>
                  </div>
                  <div class="col-12 col-sm-4">
                    <label class="form-label">Pages</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-file-earmark-text input-icon"></i>
                      <input v-model.number="form.pages" type="number" min="0" class="form-input" placeholder="0" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <div class="form-section-head">
                  <i class="bi bi-currency-dollar"></i>
                  <span>Pricing & Media</span>
                </div>
                <div class="row g-3">
                  <div class="col-12 col-sm-6">
                    <label class="form-label">Price (Rp)</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-cash-stack input-icon"></i>
                      <input v-model.number="form.price" type="number" min="0" step="500" class="form-input" placeholder="0 = Free" />
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <label class="form-label">Thumbnail URL</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-image input-icon"></i>
                      <input v-model="form.thumbnail" type="url" class="form-input" placeholder="https://…" />
                    </div>
                  </div>
                  <div v-if="isAdmin" class="col-12">
                    <label class="form-label">PDF File</label>
                    <div class="pdf-dropzone" :class="{ 'has-pdf': form.file_url || pendingPdfFile }" @click="triggerPdfInput">
                      <input ref="pdfInput" type="file" accept=".pdf,application/pdf" class="pdf-input-hidden" @change="onPdfSelect" :disabled="uploadingPdf" />
                      <template v-if="uploadingPdf">
                        <div class="pdf-dropzone-status">
                          <span class="spinner-border spinner-border-sm me-2"></span>
                          <span>Uploading PDF…</span>
                        </div>
                      </template>
                      <template v-else-if="form.file_url">
                        <div class="pdf-dropzone-content">
                          <i class="bi bi-filetype-pdf pdf-dropzone-icon"></i>
                          <div class="pdf-dropzone-info">
                            <span class="pdf-dropzone-label">PDF uploaded</span>
                            <a :href="form.file_url" target="_blank" class="pdf-dropzone-link" @click.stop>View file</a>
                          </div>
                          <button type="button" class="pdf-dropzone-replace" @click.stop="removePdf">Replace</button>
                        </div>
                      </template>
                      <template v-else-if="pendingPdfFile">
                        <div class="pdf-dropzone-content">
                          <i class="bi bi-file-earmark-pdf pdf-dropzone-icon"></i>
                          <div class="pdf-dropzone-info">
                            <span class="pdf-dropzone-label">{{ pendingPdfFile.name }}</span>
                            <span class="pdf-dropzone-hint">Will upload on save</span>
                          </div>
                          <button type="button" class="pdf-dropzone-replace" @click.stop="clearPendingPdf">Remove</button>
                        </div>
                      </template>
                      <template v-else>
                        <div class="pdf-dropzone-placeholder">
                          <i class="bi bi-cloud-arrow-up pdf-dropzone-icon"></i>
                          <span class="pdf-dropzone-label">Click to upload PDF</span>
                          <span class="pdf-dropzone-hint">PDF files only, max 20 MB</span>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-footer">
                <button type="button" class="btn btn-outline-gold" @click="closeForm">Cancel</button>
                <button type="submit" class="btn btn-gold" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi" :class="isEditing ? 'bi-check2' : 'bi-plus-lg'"></i>
                  {{ isEditing ? 'Save Changes' : 'Add Score' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="deleteTarget" class="modal-overlay" @click.self="cancelDelete">
          <div class="modal-sheet modal-sheet-dark modal-sheet--sm text-center" role="dialog" aria-modal="true">
            <div class="delete-icon-wrap"><i class="bi bi-trash3-fill" style="font-size:2rem;color:var(--gold-color)"></i></div>
            <h5 class="mt-3 mb-1 text-champagne">Delete Score</h5>
            <p class="text-champagne-muted mb-4 small">Remove <strong style="color:rgba(234,220,194,0.92)">{{ deleteTarget.title }}</strong>? This cannot be undone.</p>
            <div class="d-flex gap-2 justify-content-center">
              <button class="btn btn-sm btn-outline-gold" @click="cancelDelete" :disabled="deleting">Cancel</button>
              <button class="btn btn-sm btn-danger" @click="doDelete" :disabled="deleting">
                <span v-if="deleting" class="spinner-border spinner-border-sm me-1"></span>Delete
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="cart-slide">
        <div v-if="showCart" class="cart-overlay" @click.self="showCart = false">
          <div class="cart-panel" @click.stop>
            <div class="cart-header">
              <h5 class="cart-title mb-0"><i class="bi bi-cart3 me-2"></i>Shopping Cart</h5>
              <button class="cart-close" @click="showCart = false"><i class="bi bi-x-lg"></i></button>
            </div>
            <div v-if="cartStore.isEmpty" class="cart-empty">
              <i class="bi bi-bag-x"></i>
              <p class="mt-2 mb-0">Your cart is empty</p>
              <small class="text-champagne-muted">Browse scores and add items to purchase.</small>
            </div>
            <div v-else class="cart-items">
              <div v-for="item in cartStore.items" :key="item.score_id" class="cart-item">
                <div class="cart-item-img">
                  <img :src="item.thumbnail || defaultThumb" :alt="item.title" />
                </div>
                <div class="cart-item-info">
                  <h6 class="cart-item-title">{{ item.title }}</h6>
                  <p class="cart-item-composer mb-0">{{ item.composer }}</p>
                  <span class="cart-item-price">Rp {{ formatPrice(item.price) }}</span>
                </div>
                <button class="cart-item-remove" @click="cartStore.removeItem(item.score_id)" title="Remove"><i class="bi bi-trash3"></i></button>
              </div>
              <div class="cart-total">
                <span>Total</span>
                <strong>Rp {{ formatPrice(cartStore.total) }}</strong>
              </div>
              <div class="cart-checkout">
                <label class="form-label small fw-bold text-champagne">Notes (optional)</label>
                <textarea v-model="checkoutNotes" class="form-control-dark form-control-sm mb-2" rows="2" placeholder="Anything to note?"></textarea>
                <button class="btn btn-gold w-100" :disabled="checkingOut" @click="doCheckout">
                  <span v-if="checkingOut" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-credit-card me-1"></i>Process to Payment
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="showOrders" class="modal-overlay" @click.self="showOrders = false">
          <div class="modal-sheet modal-sheet-dark modal-sheet--orders" role="dialog" aria-modal="true">
            <button class="modal-close-btn modal-close-btn-dark" @click="showOrders = false" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="modal-header-row d-flex align-items-center gap-2 mb-3">
              <div class="modal-icon-wrap"><i class="bi bi-clock-history"></i></div>
              <div><h5 class="mb-0 text-champagne">My Orders</h5><p class="text-champagne-muted mb-0 small">Your purchase history</p></div>
            </div>
            <div v-if="loadingOrders" class="text-center py-4">
              <div class="loading-ring mx-auto"></div>
              <p class="mt-2 text-champagne-muted small">Loading orders…</p>
            </div>
            <div v-else-if="orders.length === 0" class="text-center py-4">
              <i class="bi bi-inbox" style="font-size:2rem;color:rgba(234,220,194,0.3)"></i>
              <p class="mt-2 text-champagne-muted small">No orders yet</p>
            </div>
            <div v-else class="orders-list">
              <div v-for="order in orders" :key="order.id" class="order-card">
                <div class="order-header">
                  <span class="order-number">{{ order.order_number }}</span>
                  <span class="order-status" :class="'order-status--' + order.status">{{ order.status.replace('_', ' ') }}</span>
                </div>
                <div class="order-date">{{ formatDate(order.created_at) }}</div>
                <div v-if="order.items" class="order-items">
                  <div v-for="item in order.items" :key="item.id" class="order-item">
                    <span>{{ item.title }}</span>
                    <span class="text-champagne-muted">Rp {{ formatPrice(item.price) }}</span>
                  </div>
                </div>
                <div class="order-total-row">
                  <span>Total</span>
                  <strong>Rp {{ formatPrice(order.total_amount) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="checkoutResult" class="modal-overlay" @click.self="checkoutResult = null">
          <div class="modal-sheet modal-sheet-dark modal-sheet--sm text-center" role="dialog" aria-modal="true">
            <div class="success-icon-wrap"><i class="bi bi-check2-circle"></i></div>
            <h5 class="mt-3 mb-1 text-champagne">Order Created!</h5>
            <p class="text-champagne-muted mb-3 small">Your order has been placed successfully.</p>
            <div class="d-flex justify-content-center mb-3">
              <span class="order-number-lg">{{ checkoutResult.order_number }}</span>
            </div>
            <p class="text-champagne-muted small">Total: <strong class="price-highlight">Rp {{ formatPrice(checkoutResult.total_amount) }}</strong></p>
            <p class="text-champagne-muted small mb-4">Status: <span class="order-status order-status--pending_payment">Pending Payment</span></p>
            <button class="btn btn-gold btn-sm" @click="checkoutResult = null; showCart = false">Done</button>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script>
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLibraryStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'

const defaultThumb = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 260%22%3E%3Crect fill=%22%23f5f0e8%22 width=%22200%22 height=%22260%22/%3E%3Ctext x=%22100%22 y=%22140%22 text-anchor=%22middle%22 fill=%22%23c8a45d%22 font-size=%2248%22 font-family=%22serif%22%3E%E2%99%AA%3C/text%3E%3C/svg%3E'

const emptyForm = () => ({
  title: '', composer: '', arranger: '', genre: '', difficulty: 'Intermediate', pages: 0, price: 0, thumbnail: '', file_url: '',
})

export default {
  name: 'SheetMusic',
  setup() {
    const store = useLibraryStore()
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    const route = useRoute()
    const router = useRouter()
    const userRole = computed(() => authStore.user?.role?.toLowerCase())
    const isAdmin = computed(() => userRole.value === 'admin' || userRole.value === 'manager')

    const search = ref('')
    const filterGenre = ref('')
    const filterComposer = ref('')
    const filterDifficulty = ref('')
    const viewMode = ref('grid')
    const selectedScore = ref(null)

    const genres = computed(() => store.genres)
    const composers = computed(() => store.composers)
    const allGenres = ['Classical', 'Contemporary', 'Jazz', 'Pop', 'Sacred', 'Traditional']
    const difficulties = ['Beginner', 'Intermediate', 'Advanced']
    const loading = computed(() => store.loading)

    const filteredScores = computed(() => {
      const q = search.value.trim().toLowerCase()
      return store.scores.filter((s) => {
        const matchSearch = !q || s.title.toLowerCase().includes(q) || s.composer.toLowerCase().includes(q) || (s.arranger || '').toLowerCase().includes(q)
        const matchGenre = !filterGenre.value || s.genre === filterGenre.value
        const matchComposer = !filterComposer.value || s.composer === filterComposer.value
        const matchDiff = !filterDifficulty.value || s.difficulty === filterDifficulty.value
        return matchSearch && matchGenre && matchComposer && matchDiff
      })
    })

    const formatPrice = (val) => {
      return Number(val || 0).toLocaleString('id-ID')
    }

    const diffClass = (d) => ({ 'diff-beginner': d === 'Beginner', 'diff-intermediate': d === 'Intermediate', 'diff-advanced': d === 'Advanced' })

    if (!store.scores.length) store.fetchScores()

    onMounted(() => {
      if (route.query.cart === '1') {
        showCart.value = true
      }
    })

    const showForm = ref(false)
    const isEditing = ref(false)
    const editingId = ref(null)
    const form = ref(emptyForm())
    const formError = ref('')
    const submitting = ref(false)
    const deleteTarget = ref(null)
    const deleting = ref(false)
    const pdfInput = ref(null)
    const uploadingPdf = ref(false)
    const pendingPdfFile = ref(null)

    const openEdit = (score) => {
      isEditing.value = true; editingId.value = score.id; form.value = { title: score.title, composer: score.composer, arranger: score.arranger || '', genre: score.genre, difficulty: score.difficulty, pages: score.pages, price: score.price || 0, thumbnail: score.thumbnail || '', file_url: score.file_url || '' }; formError.value = ''; pendingPdfFile.value = null; if (pdfInput.value) pdfInput.value.value = ''; showForm.value = true
    }
    const closeForm = () => { showForm.value = false; formError.value = ''; uploadingPdf.value = false; pendingPdfFile.value = null }

    const triggerPdfInput = () => pdfInput.value?.click()

    const removePdf = () => {
      form.value.file_url = ''
      if (pdfInput.value) pdfInput.value.value = ''
    }

    const clearPendingPdf = () => {
      pendingPdfFile.value = null
      if (pdfInput.value) pdfInput.value.value = ''
    }

    const onPdfSelect = (e) => {
      const file = e.target.files?.[0]
      if (!file) return
      if (editingId.value) {
        uploadingPdf.value = true
        store.uploadScorePdf(editingId.value, file).then((r) => {
          if (r?.data?.file_url) form.value.file_url = r.data.file_url
        }).catch((err) => { formError.value = err.message || 'PDF upload failed' }).finally(() => { uploadingPdf.value = false; if (pdfInput.value) pdfInput.value.value = '' })
      } else {
        pendingPdfFile.value = file
      }
    }

    const validate = () => {
      if (!form.value.title.trim()) { formError.value = 'Title is required'; return false }
      return true
    }

    const submitForm = async () => {
      if (!validate()) return
      submitting.value = true; formError.value = ''
      try {
        if (isEditing.value) {
          await store.updateScore(editingId.value, form.value)
        } else {
          const result = await store.createScore(form.value)
          const newId = result?.data?.id || result?.id
          if (newId && pendingPdfFile.value) {
            uploadingPdf.value = true
            await store.uploadScorePdf(newId, pendingPdfFile.value).then((r) => {
              if (r?.data?.file_url) form.value.file_url = r.data.file_url
            })
            uploadingPdf.value = false; pendingPdfFile.value = null
          }
        }
        closeForm()
      } catch (err) { formError.value = err.message || 'Something went wrong' }
      finally { submitting.value = false }
    }

    const openDetail = (score) => { selectedScore.value = null; requestAnimationFrame(() => { selectedScore.value = score }) }
    const confirmDelete = (score) => { selectedScore.value = null; deleteTarget.value = score }
    const cancelDelete = () => { deleteTarget.value = null }
    const doDelete = async () => {
      deleting.value = true
      try { await store.deleteScore(deleteTarget.value.id); cancelDelete() }
      catch (err) { alert('Delete failed: ' + (err.message || 'Unknown error')); deleting.value = false }
    }

    const addToCart = (score) => {
      if (!authStore.token) {
        router.push('/auth?redirect=' + encodeURIComponent('/library/sheet-music'))
        return
      }
      cartStore.addItem(score)
    }

    const isInCart = (scoreId) => cartStore.items.some((i) => i.score_id === scoreId)

    const removeFromCart = (scoreId) => cartStore.removeItem(scoreId)

    const showCart = ref(false)
    const checkoutNotes = ref('')
    const checkingOut = ref(false)
    const checkoutResult = ref(null)

    const doCheckout = async () => {
      if (!authStore.token) {
        alert('Please login first.')
        return
      }
      checkingOut.value = true
      try {
        const result = await cartStore.checkout({ notes: checkoutNotes.value })
        if (result?.data) {
          checkoutResult.value = result.data
        }
      } catch (err) {
        alert(err.message || 'Checkout failed')
      } finally {
        checkingOut.value = false
      }
    }

    const closeDetailAndCheckout = () => {
      selectedScore.value = null
      showCart.value = true
    }

    const showOrders = ref(false)
    const orders = ref([])
    const loadingOrders = ref(false)

    const toggleOrders = async () => {
      if (!authStore.token) {
        alert('Please login first.')
        return
      }
      showOrders.value = true
      loadingOrders.value = true
      try {
        orders.value = await cartStore.fetchOrders()
      } catch (err) {
        orders.value = []
      } finally {
        loadingOrders.value = false
      }
    }

    const formatDate = (d) => {
      if (!d) return ''
      const date = new Date(d)
      return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
    }

    return {
      defaultThumb, store, authStore, cartStore, route, router, search, filterGenre, filterComposer, filterDifficulty, viewMode, selectedScore,
      genres, composers, allGenres, difficulties, loading, filteredScores, formatPrice, diffClass, isAdmin,
      showForm, isEditing, form, formError, submitting, deleteTarget, deleting,
      openEdit, openDetail, closeForm, submitForm, confirmDelete, cancelDelete, doDelete,
      pdfInput, uploadingPdf, pendingPdfFile, onPdfSelect, triggerPdfInput, removePdf, clearPendingPdf,
      addToCart, isInCart, removeFromCart,
      showCart, checkoutNotes, checkingOut, checkoutResult, doCheckout, closeDetailAndCheckout,
      showOrders, orders, loadingOrders, toggleOrders, formatDate,
    }
  },
}
</script>

<style scoped>
/* ── Dark overrides ── */
.content-card.bg-dark {
  color: rgba(234, 220, 194, 0.78);
}

/* ── Layout ── */
.sheet-eyebrow { font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-color) }
.sheet-title { font-size:clamp(1.4rem,4vw,2rem);font-weight:800;color:rgba(234,220,194,0.92) !important;letter-spacing:-.01em }

/* ── Cart badge ── */
.cart-badge { position:absolute;top:-6px;right:-8px;display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;border-radius:999px;background:var(--gold-color);color:#10131f;font-size:.65rem;font-weight:800;line-height:1;padding:0 5px }

/* ── Price badge on grid cards ── */
.price-badge { position:absolute;bottom:8px;right:8px;padding:.2rem .6rem;border-radius:999px;font-size:.7rem;font-weight:700;background:rgba(16,19,31,.8);color:var(--gold-color);backdrop-filter:blur(4px);border:1px solid rgba(200,164,93,.3) }

/* ── Filters ── */
.search-wrap { position:relative;display:flex;align-items:center }
.search-icon { position:absolute;left:.85rem;color:rgba(234,220,194,0.4);font-size:.9rem;pointer-events:none }
.search-input.form-control-dark { width:100%;padding:.6rem 2.5rem .6rem 2.4rem;background:rgba(26,31,48,0.6);border-color:rgba(234,220,194,0.1);color:rgba(234,220,194,0.78) }
.search-input.form-control-dark:focus { outline:none;border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.18) }
.search-clear { position:absolute;right:.7rem;border:0;background:transparent;color:rgba(234,220,194,0.4);cursor:pointer;padding:.1rem .3rem;font-size:.8rem;border-radius:4px;transition:color .15s }
.search-clear:hover { color:rgba(234,220,194,0.78) }
.filter-select { width:100%;padding:.55rem .85rem;border:1px solid rgba(234,220,194,0.1);border-radius:var(--radius-md);background:rgba(26,31,48,0.6);color:rgba(234,220,194,0.78);font-size:.85rem;cursor:pointer;transition:border-color .2s }
.filter-select:focus { outline:none;border-color:var(--gold-color) }
.filter-chips { display:flex;flex-wrap:wrap;gap:.4rem }
.filter-chip { display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .85rem;border:1px solid rgba(234,220,194,0.1);border-radius:999px;background:rgba(26,31,48,0.45);color:rgba(234,220,194,0.55);font-size:.8rem;font-weight:600;cursor:pointer;transition:all .18s ease;user-select:none }
.filter-chip:hover { border-color:var(--gold-color);color:rgba(234,220,194,0.92);background:rgba(200,164,93,.1) }
.filter-chip.active { border-color:var(--gold-color);background:rgba(200,164,93,.15);color:rgba(234,220,194,0.92) }

/* ── States ── */
.loading-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 1rem;color:rgba(234,220,194,0.5);gap:1rem }
.loading-ring { width:42px;height:42px;border:3px solid rgba(200,164,93,.2);border-top-color:var(--gold-color);border-radius:50%;animation:spin .7s linear infinite }
@keyframes spin { to { transform:rotate(360deg) } }
.empty-state.content-card.bg-dark { text-align:center;padding:4rem 2rem;color:rgba(234,220,194,0.78) }
.empty-icon { display:inline-grid;place-items:center;width:72px;height:72px;border-radius:50%;background:rgba(200,164,93,.1);border:1px solid rgba(200,164,93,.2) }
.empty-icon i { font-size:2rem;color:var(--gold-color) }

/* ── Grid ── */
.scores-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem }
@media (min-width:576px) { .scores-grid { grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem } }
@media (min-width:1200px) { .scores-grid { grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.5rem } }

.score-card { border-radius:var(--radius-md);border:1px solid rgba(234,220,194,0.08);background:rgba(26,31,48,0.5);box-shadow:0 2px 12px rgba(0,0,0,.2);overflow:hidden;display:flex;flex-direction:column;animation:fadeInUp .4s ease-out both;cursor:pointer;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease }
.score-card:active { transform:translateY(-1px) }
@media (hover:hover) {
  .score-card:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.3);border-color:rgba(200,164,93,.4) }
}

.score-thumb-wrap { position:relative;height:180px;overflow:hidden;background:rgba(26,31,48,0.3);flex-shrink:0 }
@media (min-width:576px) { .score-thumb-wrap { height:220px } }
.score-thumb { width:100%;height:100%;object-fit:cover;transition:transform .35s ease }
@media (hover:hover) { .score-card:hover .score-thumb { transform:scale(1.04) } }
.score-thumb-overlay { position:absolute;inset:0;display:grid;place-items:center;background:rgba(16,19,31,.45);color:#fff;font-size:1.4rem;opacity:0;transition:opacity .22s ease }
@media (hover:hover) { .score-card:hover .score-thumb-overlay { opacity:1 } }
.score-badge { position:absolute;top:10px;left:0;padding:.18rem .75rem .18rem .6rem;border-radius:0 999px 999px 0;font-size:.7rem;font-weight:700;color:#fff;letter-spacing:.05em;text-transform:uppercase;box-shadow:0 2px 8px rgba(0,0,0,.2);background:var(--accent-color) }
.score-body { padding:.75rem .85rem .5rem;flex:1 }
.score-title { margin:0;font-size:.92rem;font-weight:700;color:rgba(234,220,194,0.92);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.score-composer { font-size:.78rem;color:rgba(234,220,194,0.5);margin:.15rem 0 .6rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.score-meta { display:flex;gap:.6rem;flex-wrap:wrap }
.score-meta-item { display:inline-flex;align-items:center;gap:.25rem;font-size:.73rem;color:rgba(234,220,194,0.45) }
.score-meta-item i { font-size:.7rem }
.add-to-cart-btn { font-size:.78rem;padding:.28rem .5rem }
.score-actions { display:flex;border-top:1px solid rgba(234,220,194,0.08);padding:.45rem .65rem;gap:.35rem }

/* ── List ── */
.scores-list.content-card.bg-dark { overflow:hidden;color:rgba(234,220,194,0.78) }
.list-header { display:grid;grid-template-columns:48px 2fr 1.2fr 1.2fr 1fr .9fr .8fr;gap:.5rem;padding:.75rem 1rem;border-bottom:1px solid rgba(234,220,194,0.08);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(234,220,194,0.5) }
.list-row { display:grid;grid-template-columns:48px 2fr 1.2fr 1.2fr 1fr .9fr .8fr;gap:.5rem;padding:.6rem 1rem;align-items:center;cursor:pointer;animation:fadeInUp .3s ease-out both;transition:background .15s }
.list-row:hover { background:rgba(200,164,93,.06) }
.list-thumb { width:36px;height:48px;object-fit:cover;border-radius:4px;display:block }
.list-title { font-weight:600;font-size:.9rem;color:rgba(234,220,194,0.92);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.list-muted { font-size:.82rem;color:rgba(234,220,194,0.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.genre-pill { display:inline-block;padding:.15rem .5rem;border-radius:999px;font-size:.7rem;font-weight:600;background:rgba(200,164,93,.12);color:var(--gold-color);white-space:nowrap }
.diff-badge { display:inline-block;padding:.1rem .45rem;border-radius:999px;font-size:.7rem;font-weight:700;text-transform:uppercase;white-space:nowrap }
.diff-beginner { background:rgba(74,124,89,.15);color:#4a7c59 }
.diff-intermediate { background:rgba(200,164,93,.18);color:#9d7d3b }
.diff-advanced { background:rgba(192,57,43,.12);color:#c0392b }
.price-value { font-size:.8rem;font-weight:600;color:var(--gold-color) }
.result-count { text-align:center;margin-top:1.25rem;font-size:.8rem;color:rgba(234,220,194,0.5) }

.action-btn { flex:1;border:1px solid rgba(234,220,194,0.08);border-radius:6px;background:transparent;color:rgba(234,220,194,0.45);font-size:.82rem;padding:.32rem;cursor:pointer;transition:all .18s ease;line-height:1;display:inline-flex;align-items:center;justify-content:center }
.action-btn:hover { border-color:rgba(234,220,194,0.3);color:rgba(234,220,194,0.78);background:rgba(234,220,194,0.06) }
.action-btn.action-edit:hover { border-color:var(--gold-color);color:var(--gold-color);background:rgba(200,164,93,.08) }
.action-btn.action-delete:hover { border-color:#c0392b;color:#c0392b;background:rgba(192,57,43,.07) }
.list-actions { display:flex;gap:.25rem }
.list-actions .action-btn { flex:0 0 auto;padding:.2rem .45rem }

/* ── List responsive ── */
@media (max-width:767.98px) {
  .list-header { grid-template-columns:40px 2fr 1fr .7fr .7fr;font-size:.65rem;padding:.6rem .75rem }
  .list-row { grid-template-columns:40px 2fr 1fr .7fr .7fr;padding:.5rem .75rem }
  .lh-arranger,.lh-pages,.lh-difficulty { display:none }
  .list-thumb { width:30px;height:40px }
}
@media (max-width:575.98px) {
  .list-header { grid-template-columns:2fr 1fr .7fr;padding:.5rem .65rem }
  .list-row { grid-template-columns:2fr 1fr .7fr;padding:.45rem .65rem }
  .lh-thumb,.lh-genre { display:none }
}

/* ── Modals ── */
.modal-overlay { position:fixed;inset:0;z-index:1050;background:rgba(10,10,15,.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1rem;overflow-y:auto }
@media (min-width:576px) { .modal-overlay { padding:1.5rem } }
.modal-sheet { position:relative;border-radius:14px;border:1px solid var(--hairline-color);box-shadow:0 32px 72px rgba(10,10,15,.36),0 0 0 1px rgba(200,164,93,.1);width:100%;max-width:640px;padding:1.25rem;margin:auto }
@media (min-width:576px) { .modal-sheet { padding:1.75rem } }
.modal-sheet-dark { background:linear-gradient(135deg,rgba(26,31,48,0.98),rgba(17,20,32,0.98));border-color:rgba(234,220,194,0.1);box-shadow:0 32px 72px rgba(0,0,0,.5),0 0 0 1px rgba(200,164,93,.1) }
.modal-sheet--sm { max-width:400px;padding:1.5rem 1.25rem }
@media (min-width:576px) { .modal-sheet--sm { padding:2rem } }
.modal-sheet--orders { max-width:520px }
.modal-close-btn { position:absolute;top:.75rem;right:.75rem;border:0;width:34px;height:34px;border-radius:8px;display:grid;place-items:center;font-size:.85rem;cursor:pointer;transition:background .18s,color .18s }
.modal-close-btn-dark { background:rgba(200,164,93,0.1);color:rgba(234,220,194,0.5) }
.modal-close-btn-dark:hover { background:var(--gold-color);color:#fff }
.modal-icon-wrap { display:grid;place-items:center;width:40px;height:40px;border-radius:10px;background:rgba(200,164,93,.12);border:1px solid rgba(200,164,93,.2);color:var(--gold-color);font-size:1.2rem;flex-shrink:0 }

/* ── Detail modal ── */
.detail-thumb-wrap { flex-shrink:0;width:100%;max-width:180px;margin:0 auto 1rem }
@media (min-width:576px) { .detail-thumb-wrap { width:180px;margin:0 } }
.detail-thumb { width:100%;height:auto;aspect-ratio:5/7;object-fit:cover;border-radius:10px;display:block }
.detail-title { font-size:clamp(1.1rem,3vw,1.35rem);font-weight:800;color:rgba(234,220,194,0.92);margin-bottom:1rem;line-height:1.25 }
.detail-field { display:flex;align-items:baseline;gap:.5rem;padding:.4rem 0;border-bottom:1px solid rgba(234,220,194,0.08) }
.detail-field:last-child { border-bottom:none }
.df-label { font-size:.78rem;font-weight:600;color:rgba(234,220,194,0.5);min-width:80px;flex-shrink:0 }
.df-value { font-size:.9rem;color:rgba(234,220,194,0.78);word-break:break-word }
.price-highlight { color:var(--gold-color);font-size:1.05rem }
.pdf-link { color:var(--gold-color);text-decoration:none;display:inline-flex;align-items:center;gap:.25rem;font-weight:600;transition:color .15s }
.pdf-link:hover { color:rgba(234,220,194,0.92);text-decoration:underline }

/* ── Cart slide-in panel ── */
.cart-overlay { position:fixed;inset:0;z-index:1060;background:rgba(10,10,15,.5);backdrop-filter:blur(4px);display:flex;justify-content:flex-end }
.cart-panel { width:100%;max-width:400px;height:100%;background:linear-gradient(180deg,rgba(26,31,48,0.99),rgba(17,20,32,0.99));border-left:1px solid rgba(234,220,194,0.1);box-shadow:-8px 0 32px rgba(0,0,0,.4);display:flex;flex-direction:column }
.cart-header { display:flex;align-items:center;justify-content:space-between;padding:1.25rem;border-bottom:1px solid rgba(234,220,194,0.08) }
.cart-title { font-size:1.05rem;font-weight:700;color:rgba(234,220,194,0.92) }
.cart-close { border:0;width:34px;height:34px;border-radius:8px;background:rgba(200,164,93,0.1);color:rgba(234,220,194,0.5);display:grid;place-items:center;cursor:pointer;transition:background .18s,color .18s;font-size:.85rem }
.cart-close:hover { background:var(--gold-color);color:#fff }
.cart-empty { flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:rgba(234,220,194,0.35);gap:.5rem }
.cart-empty i { font-size:3rem }
.cart-items { flex:1;overflow-y:auto;padding:.75rem 1.25rem }
.cart-item { display:flex;align-items:center;gap:.85rem;padding:.75rem 0;border-bottom:1px solid rgba(234,220,194,0.06) }
.cart-item:last-child { border-bottom:none }
.cart-item-img { width:44px;height:60px;border-radius:6px;overflow:hidden;flex-shrink:0 }
.cart-item-img img { width:100%;height:100%;object-fit:cover }
.cart-item-info { flex:1;min-width:0 }
.cart-item-title { font-size:.85rem;font-weight:700;color:rgba(234,220,194,0.92);margin:0 0 .1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
.cart-item-composer { font-size:.73rem;color:rgba(234,220,194,0.45) }
.cart-item-price { font-size:.8rem;font-weight:600;color:var(--gold-color) }
.cart-item-remove { border:0;background:transparent;color:rgba(234,220,194,0.3);font-size:.9rem;padding:.3rem;cursor:pointer;transition:color .15s;flex-shrink:0 }
.cart-item-remove:hover { color:#c0392b }
.cart-total { display:flex;align-items:center;justify-content:space-between;padding:1rem 0;border-top:1px solid rgba(234,220,194,0.1);font-size:.95rem;color:rgba(234,220,194,0.92) }
.cart-total strong { color:var(--gold-color);font-size:1.05rem }
.cart-checkout { padding:.25rem 0 1rem }

/* ── Cart slide transition ── */
.cart-slide-enter-active,.cart-slide-leave-active { transition:opacity .25s ease }
.cart-slide-enter-active .cart-panel,.cart-slide-leave-active .cart-panel { transition:transform .3s ease }
.cart-slide-enter-from,.cart-slide-leave-to { opacity:0 }
.cart-slide-enter-from .cart-panel { transform:translateX(100%) }
.cart-slide-leave-to .cart-panel { transform:translateX(100%) }

/* ── Orders list ── */
.orders-list { max-height:60vh;overflow-y:auto }
.order-card { padding:.85rem 0;border-bottom:1px solid rgba(234,220,194,0.08) }
.order-card:last-child { border-bottom:none }
.order-header { display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.25rem }
.order-number { font-size:.8rem;font-weight:700;color:rgba(234,220,194,0.92);font-family:monospace }
.order-date { font-size:.7rem;color:rgba(234,220,194,0.4);margin-bottom:.5rem }
.order-status { display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:999px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em }
.order-status--pending_payment { background:rgba(200,164,93,.15);color:var(--gold-color) }
.order-status--paid { background:rgba(74,124,89,.15);color:#4a7c59 }
.order-status--cancelled { background:rgba(192,57,43,.12);color:#c0392b }
.order-items { padding:.25rem 0 }
.order-item { display:flex;align-items:center;justify-content:space-between;font-size:.78rem;color:rgba(234,220,194,0.65);padding:.15rem 0 }
.order-total-row { display:flex;align-items:center;justify-content:space-between;padding:.5rem 0 0;border-top:1px solid rgba(234,220,194,0.08);font-size:.85rem;color:rgba(234,220,194,0.92);margin-top:.25rem }
.order-total-row strong { color:var(--gold-color) }

/* ── Success modal ── */
.success-icon-wrap { display:grid;place-items:center;width:56px;height:56px;border-radius:50%;background:rgba(74,124,89,.15);margin:0 auto }
.success-icon-wrap i { font-size:2rem;color:#4a7c59 }
.order-number-lg { font-family:monospace;font-size:1rem;font-weight:800;color:var(--gold-color);background:rgba(200,164,93,.1);padding:.35rem 1rem;border-radius:8px;border:1px solid rgba(200,164,93,.2) }

/* ── Transitions ── */
.modal-enter-active,.modal-leave-active { transition:opacity .25s ease }
.modal-enter-active .modal-sheet,.modal-leave-active .modal-sheet { transition:transform .25s ease,opacity .25s ease }
.modal-enter-from,.modal-leave-to { opacity:0 }
.modal-enter-from .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }
.modal-leave-to .modal-sheet { transform:scale(.94) translateY(12px);opacity:0 }

@keyframes fadeInUp { from { opacity:0;transform:translateY(16px) } to { opacity:1;transform:translateY(0) } }

/* ── Form modal ── */
.modal-sheet--form { max-width:680px }
.form-modal-title { font-size:1.1rem;font-weight:800;color:rgba(234,220,194,0.92);letter-spacing:-.01em;line-height:1.2 }
.form-modal-subtitle { font-size:.82rem;color:rgba(234,220,194,0.5) }
.form-alert { display:flex;align-items:center;padding:.65rem .85rem;border-radius:8px;font-size:.82rem;font-weight:600;margin-bottom:1.25rem }
.form-alert--error { background:rgba(192,57,43,.12);border:1px solid rgba(192,57,43,.25);color:#e8a0a8 }

.score-form { display:flex;flex-direction:column;gap:1.25rem }
.form-section { background:rgba(26,31,48,0.4);border:1px solid rgba(234,220,194,0.06);border-radius:10px;padding:1rem 1.15rem 1.15rem }
.form-section-head { display:flex;align-items:center;gap:.55rem;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--gold-color);margin-bottom:.85rem;padding-bottom:.55rem;border-bottom:1px solid rgba(234,220,194,0.06) }
.form-section-head i { font-size:.85rem }

.form-label { display:block;font-size:.78rem;font-weight:700;color:rgba(234,220,194,0.78);margin-bottom:.35rem;letter-spacing:.01em }

.input-icon-wrap { position:relative;display:flex;align-items:center }
.input-icon { position:absolute;left:.75rem;color:rgba(234,220,194,0.3);font-size:.85rem;pointer-events:none;z-index:1 }
.form-input { width:100%;padding:.55rem .75rem .55rem 2.3rem;border:1px solid rgba(234,220,194,0.1);border-radius:8px;background:rgba(16,19,31,0.5);color:rgba(234,220,194,0.85);font-size:.85rem;transition:border-color .2s,box-shadow .2s,background .2s;outline:none }
.form-input:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.12);background:rgba(16,19,31,0.7) }
.form-input::placeholder { color:rgba(234,220,194,0.25) }
.form-input[type="number"] { -moz-appearance:textfield }
.form-input[type="number"]::-webkit-inner-spin-button,
.form-input[type="number"]::-webkit-outer-spin-button { -webkit-appearance:none;margin:0 }

.select-wrap { position:relative;display:flex;align-items:center }
.select-icon { position:absolute;left:.75rem;color:rgba(234,220,194,0.3);font-size:.8rem;pointer-events:none;z-index:1 }
.select-chevron { position:absolute;right:.75rem;color:rgba(234,220,194,0.3);font-size:.65rem;pointer-events:none;z-index:1 }
.form-select-custom { width:100%;padding:.55rem 2rem .55rem 2.3rem;border:1px solid rgba(234,220,194,0.1);border-radius:8px;background:rgba(16,19,31,0.5);color:rgba(234,220,194,0.85);font-size:.85rem;outline:none;cursor:pointer;transition:border-color .2s,box-shadow .2s,background .2s;-webkit-appearance:none;-moz-appearance:none;appearance:none }
.form-select-custom:focus { border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.12);background:rgba(16,19,31,0.7) }
.form-select-custom option { background:#1a1f30;color:rgba(234,220,194,0.85) }

.pdf-dropzone { position:relative;border:2px dashed rgba(234,220,194,0.12);border-radius:10px;padding:1rem;cursor:pointer;transition:border-color .2s,background .2s }
.pdf-dropzone:hover { border-color:rgba(200,164,93,.35);background:rgba(200,164,93,.04) }
.pdf-dropzone.has-pdf { border-style:solid;border-color:rgba(74,124,89,.25);background:rgba(74,124,89,.05) }
.pdf-input-hidden { display:none }
.pdf-dropzone-content { display:flex;align-items:center;gap:.85rem }
.pdf-dropzone-placeholder { display:flex;flex-direction:column;align-items:center;gap:.25rem;padding:.5rem 0 }
.pdf-dropzone-status { display:flex;align-items:center;gap:.5rem;justify-content:center;padding:.5rem 0;color:rgba(234,220,194,0.6);font-size:.82rem }
.pdf-dropzone-icon { font-size:1.5rem;color:var(--gold-color);flex-shrink:0 }
.pdf-dropzone-info { flex:1;min-width:0 }
.pdf-dropzone-label { display:block;font-size:.82rem;font-weight:600;color:rgba(234,220,194,0.78) }
.pdf-dropzone-hint { display:block;font-size:.72rem;color:rgba(234,220,194,0.4);margin-top:1px }
.pdf-dropzone-link { font-size:.78rem;color:var(--gold-color);text-decoration:none;font-weight:600 }
.pdf-dropzone-link:hover { text-decoration:underline }
.pdf-dropzone-replace { padding:.3rem .65rem;border:1px solid rgba(234,220,194,0.12);border-radius:6px;background:transparent;color:rgba(234,220,194,0.5);font-size:.75rem;font-weight:600;cursor:pointer;transition:all .18s;flex-shrink:0 }
.pdf-dropzone-replace:hover { border-color:var(--gold-color);color:var(--gold-color);background:rgba(200,164,93,.08) }

.form-footer { display:flex;gap:.75rem;justify-content:flex-end;padding-top:.25rem }
.form-footer .btn { padding:.5rem 1.25rem;font-size:.85rem;font-weight:700;border-radius:8px }

@media (max-width:575.98px) {
  .form-section { padding:.85rem .85rem 1rem }
  .form-footer { flex-direction:column-reverse }
  .form-footer .btn { width:100% }
}

/* ── Delete modal ── */
.delete-icon-wrap { display:grid;place-items:center;width:48px;height:48px;border-radius:50%;background:rgba(192,57,43,.1);margin:0 auto }
.delete-icon-wrap i { font-size:1.4rem;color:#c0392b }
</style>
