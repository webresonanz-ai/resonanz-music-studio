<template>
  <div class="fade-in-up">
    <div class="content-card mb-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <p class="costumes-eyebrow mb-1">Library</p>
          <h1 class="costumes-title mb-0">Costumes</h1>
        </div>
        <button v-if="isAdmin" class="btn btn-primary d-flex align-items-center gap-2" @click="openCreate">
          <i class="bi bi-plus-lg"></i><span>Add Costume</span>
        </button>
      </div>
    </div>

    <div class="filters-bar mb-4">
      <div class="row g-2">
        <div class="col-12 col-md-6">
          <div class="search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input v-model="search" type="text" class="search-input" placeholder="Search costumes by name, category, or notes…" />
            <button v-if="search" class="search-clear" @click="search = ''" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterCategory" class="filter-select">
            <option value="">All Categories</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterCondition" class="filter-select">
            <option value="">All Conditions</option>
            <option v-for="c in conditions" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-state"><div class="loading-ring"></div><p>Loading costumes…</p></div>

    <template v-else>
      <div v-if="filteredCostumes.length === 0" class="empty-state content-card">
        <div class="empty-icon"><i class="bi bi-person-badge"></i></div>
        <h5 class="mt-3 mb-1">No costumes found</h5>
        <p class="text-muted mb-0">Try adjusting your filters.</p>
      </div>

      <div class="costumes-grid">
        <article v-for="(item, idx) in filteredCostumes" :key="item.id" class="costume-card" :style="`animation-delay:${idx * 0.04}s`" @click="selectedCostume = item">
          <div class="costume-img-wrap">
            <img :src="item.image" :alt="item.name" class="costume-img" loading="lazy" />
            <div class="costume-img-overlay"><i class="bi bi-eye-fill"></i></div>
            <span class="costume-cat-badge">{{ item.category }}</span>
            <span class="costume-cond-dot" :class="condClass(item.condition)" :title="item.condition"></span>
          </div>
          <div class="costume-body">
            <h6 class="costume-name">{{ item.name }}</h6>
            <div class="costume-meta">
              <span class="costume-meta-item"><i class="bi bi-rulers"></i>{{ item.size }}</span>
              <span class="costume-meta-item" :class="condClass(item.condition)"><i class="bi bi-check-circle"></i>{{ item.condition }}</span>
            </div>
            <p v-if="item.notes" class="costume-notes">{{ item.notes }}</p>
            <p v-if="item.lastUsed" class="costume-date"><i class="bi bi-calendar3"></i>Last used: {{ item.lastUsed }}</p>
          </div>
          <div v-if="isAdmin" class="costume-actions" @click.stop>
            <button class="action-btn action-edit" title="Edit" @click="openEdit(item)"><i class="bi bi-pencil"></i></button>
            <button class="action-btn action-delete" title="Delete" @click="confirmDelete(item)"><i class="bi bi-trash3"></i></button>
          </div>
        </article>
      </div>

      <div v-if="filteredCostumes.length > 0" class="result-count">
        Showing {{ filteredCostumes.length }} costume{{ filteredCostumes.length !== 1 ? 's' : '' }}
      </div>
    </template>

    <Teleport to="body">
      <transition name="modal">
        <div v-if="selectedCostume && !showForm && !deleteTarget" class="modal-overlay" @click.self="selectedCostume = null">
          <div class="modal-sheet" role="dialog" aria-modal="true">
            <button class="modal-close-btn" @click="selectedCostume = null" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="d-flex flex-column flex-md-row gap-4">
              <div class="detail-img-wrap"><img :src="selectedCostume.image" :alt="selectedCostume.name" class="detail-img" /></div>
              <div class="flex-fill">
                <h3 class="detail-title">{{ selectedCostume.name }}</h3>
                <div class="detail-field"><span class="df-label">Category</span><span class="df-value">{{ selectedCostume.category }}</span></div>
                <div class="detail-field"><span class="df-label">Size</span><span class="df-value">{{ selectedCostume.size }}</span></div>
                <div class="detail-field"><span class="df-label">Condition</span><span class="df-value"><span class="cond-pill" :class="condClass(selectedCostume.condition)">{{ selectedCostume.condition }}</span></span></div>
                <div class="detail-field"><span class="df-label">Last Used</span><span class="df-value">{{ selectedCostume.lastUsed || 'Never' }}</span></div>
                <div class="detail-field" v-if="selectedCostume.notes"><span class="df-label">Notes</span><span class="df-value">{{ selectedCostume.notes }}</span></div>
                <div v-if="isAdmin" class="d-flex gap-2 mt-3">
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(selectedCostume)"><i class="bi bi-pencil me-1"></i>Edit</button>
                  <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(selectedCostume)"><i class="bi bi-trash3 me-1"></i>Delete</button>
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
          <div class="modal-sheet" role="dialog" aria-modal="true">
            <button class="modal-close-btn" @click="closeForm" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="modal-header-row d-flex align-items-center gap-2 mb-3">
              <div class="modal-icon-wrap"><i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i></div>
              <div><h5 class="mb-0">{{ isEditing ? 'Edit Costume' : 'Add Costume' }}</h5><p class="text-muted mb-0 small">{{ isEditing ? 'Update the costume details' : 'Fill in the details below' }}</p></div>
            </div>
            <div v-if="formError" class="alert alert-danger py-2 small">{{ formError }}</div>
            <form @submit.prevent="submitForm">
              <div class="row g-3">
                <div class="col-12"><label class="form-label small fw-bold">Name <span class="text-danger">*</span></label><input v-model="form.name" type="text" class="form-control form-control-sm" placeholder="Costume name" required /></div>
                <div class="col-6"><label class="form-label small fw-bold">Category</label><input v-model="form.category" type="text" class="form-control form-control-sm" placeholder="e.g. Tuxedo, Robe" /></div>
                <div class="col-6"><label class="form-label small fw-bold">Size</label><input v-model="form.size" type="text" class="form-control form-control-sm" placeholder="e.g. L, XL" /></div>
                <div class="col-4"><label class="form-label small fw-bold">Condition</label>
                  <select v-model="form.condition" class="form-select form-select-sm">
                    <option value="New">New</option><option value="Excellent">Excellent</option><option value="Good">Good</option><option value="Fair">Fair</option>
                  </select>
                </div>
                <div class="col-4"><label class="form-label small fw-bold">Last Used</label><input v-model="form.last_used" type="date" class="form-control form-control-sm" /></div>
                <div class="col-12"><label class="form-label small fw-bold">Image URL</label><input v-model="form.image" type="url" class="form-control form-control-sm" placeholder="https://…" /></div>
                <div class="col-12"><label class="form-label small fw-bold">Notes</label><textarea v-model="form.notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes"></textarea></div>
              </div>
              <div class="d-flex gap-2 justify-content-end mt-4">
                <button type="button" class="btn btn-sm btn-secondary" @click="closeForm">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>{{ isEditing ? 'Save Changes' : 'Add Costume' }}
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
          <div class="modal-sheet modal-sheet--sm text-center" role="dialog" aria-modal="true">
            <div class="delete-icon-wrap"><i class="bi bi-trash3-fill" style="font-size:2rem;color:var(--accent-color)"></i></div>
            <h5 class="mt-3 mb-1">Delete Costume</h5>
            <p class="text-muted mb-4 small">Remove <strong>{{ deleteTarget.name }}</strong>? This cannot be undone.</p>
            <div class="d-flex gap-2 justify-content-center">
              <button class="btn btn-sm btn-secondary" @click="cancelDelete" :disabled="deleting">Cancel</button>
              <button class="btn btn-sm btn-danger" @click="doDelete" :disabled="deleting">
                <span v-if="deleting" class="spinner-border spinner-border-sm me-1"></span>Delete
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script>
import { computed, ref } from 'vue'
import { useLibraryStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'LibraryCostumes',
  setup() {
    const store = useLibraryStore()
    const authStore = useAuthStore()
    const userRole = computed(() => authStore.user?.role?.toLowerCase())
    const isAdmin = computed(() => userRole.value === 'admin' || userRole.value === 'manager')

    const search = ref('')
    const filterCategory = ref('')
    const filterCondition = ref('')

    const categories = computed(() => store.costumeCategories)
    const conditions = ['New', 'Excellent', 'Good', 'Fair']
    const loading = computed(() => store.loading)

    const filteredCostumes = computed(() => {
      const q = search.value.trim().toLowerCase()
      return store.costumes.filter((c) => {
        const matchSearch = !q || c.name.toLowerCase().includes(q) || c.category.toLowerCase().includes(q) || (c.notes || '').toLowerCase().includes(q)
        const matchCat = !filterCategory.value || c.category === filterCategory.value
        const matchCond = !filterCondition.value || c.condition === filterCondition.value
        return matchSearch && matchCat && matchCond
      })
    })

    const condClass = (condition) => ({ 'cond-new': condition === 'New', 'cond-excellent': condition === 'Excellent', 'cond-good': condition === 'Good', 'cond-fair': condition === 'Fair' })

    if (!store.costumes.length) store.fetchCostumes()

    const selectedCostume = ref(null)

    const showForm = ref(false)
    const isEditing = ref(false)
    const editingId = ref(null)
    const form = ref({ name: '', category: '', size: '', condition: 'Good', last_used: '', image: '', notes: '' })
    const formError = ref('')
    const submitting = ref(false)
    const deleteTarget = ref(null)
    const deleting = ref(false)

    const openCreate = () => {
      isEditing.value = false; editingId.value = null
      form.value = { name: '', category: '', size: '', condition: 'Good', last_used: '', image: '', notes: '' }
      formError.value = ''; showForm.value = true
    }
    const openEdit = (item) => {
      isEditing.value = true; editingId.value = item.id
      form.value = { name: item.name, category: item.category, size: item.size, condition: item.condition, last_used: item.last_used || '', image: item.image || '', notes: item.notes || '' }
      formError.value = ''; showForm.value = true
    }
    const closeForm = () => { showForm.value = false; formError.value = '' }

    const validate = () => {
      if (!form.value.name.trim()) { formError.value = 'Name is required'; return false }
      return true
    }

    const submitForm = async () => {
      if (!validate()) return
      submitting.value = true; formError.value = ''
      try {
        const payload = { ...form.value }
        if (!payload.last_used) delete payload.last_used
        if (isEditing.value) await store.updateCostume(editingId.value, payload)
        else await store.createCostume(payload)
        closeForm()
      } catch (err) { formError.value = err.message || 'Something went wrong' }
      finally { submitting.value = false }
    }

    const confirmDelete = (item) => { selectedCostume.value = null; deleteTarget.value = item }
    const cancelDelete = () => { deleteTarget.value = null }
    const doDelete = async () => {
      deleting.value = true
      try { await store.deleteCostume(deleteTarget.value.id); cancelDelete() }
      catch (err) { alert('Delete failed: ' + (err.message || 'Unknown error')); deleting.value = false }
    }

    return {
      store, search, filterCategory, filterCondition, categories, conditions, loading, filteredCostumes, condClass, isAdmin,
      selectedCostume, showForm, isEditing, form, formError, submitting, deleteTarget, deleting,
      openCreate, openEdit, closeForm, submitForm, confirmDelete, cancelDelete, doDelete,
    }
  },
}
</script>

<style scoped>
.costumes-eyebrow { font-size:.75rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-color) }
.costumes-title { font-size:2rem;font-weight:800;color:var(--ink-color);letter-spacing:-.01em }
.search-wrap { position:relative;display:flex;align-items:center }
.search-icon { position:absolute;left:.85rem;color:var(--muted-color);font-size:.9rem;pointer-events:none }
.search-input { width:100%;padding:.6rem 2.5rem .6rem 2.4rem;border:1px solid var(--hairline-color);border-radius:var(--radius-md);background:rgba(255,253,248,.9);color:var(--ink-color);font-size:.9rem;transition:border-color .2s,box-shadow .2s }
.search-input:focus { outline:none;border-color:var(--gold-color);box-shadow:0 0 0 3px rgba(200,164,93,.18) }
.search-clear { position:absolute;right:.7rem;border:0;background:transparent;color:var(--muted-color);cursor:pointer;padding:.1rem .3rem;font-size:.8rem;border-radius:4px;transition:color .15s }
.search-clear:hover { color:var(--accent-color) }
.filter-select { width:100%;padding:.55rem .85rem;border:1px solid var(--hairline-color);border-radius:var(--radius-md);background:rgba(255,253,248,.9);color:var(--ink-color);font-size:.85rem;cursor:pointer;transition:border-color .2s }
.filter-select:focus { outline:none;border-color:var(--gold-color) }
.loading-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 0;color:var(--muted-color);gap:1rem }
.loading-ring { width:42px;height:42px;border:3px solid rgba(200,164,93,.2);border-top-color:var(--gold-color);border-radius:50%;animation:spin .7s linear infinite }
@keyframes spin { to { transform:rotate(360deg) } }
.empty-state { text-align:center;padding:4rem 2rem }
.empty-icon { display:inline-grid;place-items:center;width:72px;height:72px;border-radius:50%;background:rgba(200,164,93,.1);border:1px solid rgba(200,164,93,.2) }
.empty-icon i { font-size:2rem;color:var(--gold-color) }
.costumes-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem }
.costume-card { border-radius:var(--radius-md);border:1px solid var(--hairline-color);background:rgba(255,253,248,.96);box-shadow:0 2px 12px rgba(19,18,16,.06);overflow:hidden;display:flex;flex-direction:column;animation:fadeInUp .4s ease-out both;cursor:pointer;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease }
.costume-card:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(19,18,16,.13);border-color:rgba(200,164,93,.4) }
.costume-img-wrap { position:relative;height:240px;overflow:hidden;background:#ede8df;flex-shrink:0 }
.costume-img { width:100%;height:100%;object-fit:cover;transition:transform .35s ease }
.costume-card:hover .costume-img { transform:scale(1.04) }
.costume-img-overlay { position:absolute;inset:0;display:grid;place-items:center;background:rgba(16,19,31,.45);color:#fff;font-size:1.4rem;opacity:0;transition:opacity .22s ease }
.costume-card:hover .costume-img-overlay { opacity:1 }
.costume-cat-badge { position:absolute;bottom:10px;left:10px;padding:.2rem .7rem;border-radius:999px;font-size:.7rem;font-weight:700;text-transform:uppercase;background:rgba(16,19,31,.7);color:#fff;letter-spacing:.05em;backdrop-filter:blur(4px) }
.costume-cond-dot { position:absolute;top:10px;right:10px;width:12px;height:12px;border-radius:50%;border:2px solid rgba(255,255,255,.6);box-shadow:0 2px 6px rgba(0,0,0,.2) }
.costume-body { padding:.85rem .9rem;flex:1 }
.costume-name { margin:0 0 .35rem;font-size:.95rem;font-weight:700;color:var(--ink-color) }
.costume-meta { display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:.3rem }
.costume-meta-item { display:inline-flex;align-items:center;gap:.25rem;font-size:.78rem;color:var(--muted-color) }
.costume-meta-item i { font-size:.72rem }
.costume-notes { font-size:.78rem;color:var(--muted-color);font-style:italic;margin:.3rem 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden }
.costume-date { font-size:.73rem;color:var(--muted-color);margin:.3rem 0 0;display:flex;align-items:center;gap:.3rem }
.costume-date i { font-size:.7rem }
.costume-actions { display:flex;border-top:1px solid var(--hairline-color);padding:.45rem .65rem;gap:.35rem }
.cond-new { color:#2e7d32 }
.cond-excellent { color:#4a7c59 }
.cond-good { color:#9d7d3b }
.cond-fair { color:#c0392b }
.result-count { text-align:center;margin-top:1.25rem;font-size:.8rem;color:var(--muted-color) }
.action-btn { flex:1;border:1px solid var(--hairline-color);border-radius:6px;background:transparent;color:var(--muted-color);font-size:.82rem;padding:.32rem;cursor:pointer;transition:all .18s ease;line-height:1 }
.action-btn:hover { border-color:var(--ink-color);color:var(--ink-color);background:rgba(25,27,36,.06) }
.action-btn.action-edit:hover { border-color:var(--gold-color);color:var(--gold-color);background:rgba(200,164,93,.08) }
.action-btn.action-delete:hover { border-color:#c0392b;color:#c0392b;background:rgba(192,57,43,.07) }
.modal-overlay { position:fixed;inset:0;z-index:1050;background:rgba(10,10,15,.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1.5rem;overflow-y:auto }
.modal-sheet { position:relative;background:var(--surface-color,#fffdf8);border-radius:14px;border:1px solid var(--hairline-color);box-shadow:0 32px 72px rgba(10,10,15,.36),0 0 0 1px rgba(200,164,93,.1);width:100%;max-width:640px;padding:1.75rem }
.modal-sheet--sm { max-width:400px;padding:2rem;text-align:center }
.modal-close-btn { position:absolute;top:1rem;right:1rem;border:0;background:rgba(34,29,20,.08);color:var(--muted-color);width:34px;height:34px;border-radius:8px;display:grid;place-items:center;font-size:.85rem;cursor:pointer;transition:background .18s,color .18s }
.modal-close-btn:hover { background:var(--accent-color);color:#fff }
.detail-img-wrap { flex-shrink:0;width:220px }
.detail-img { width:100%;border-radius:8px }
.detail-title { font-size:1.35rem;font-weight:800;color:var(--ink-color);margin-bottom:1rem }
.detail-field { display:flex;align-items:baseline;gap:.5rem;padding:.4rem 0;border-bottom:1px solid var(--hairline-color) }
.detail-field:last-child { border-bottom:none }
.df-label { font-size:.78rem;font-weight:600;color:var(--muted-color);min-width:80px }
.df-value { font-size:.9rem;color:var(--ink-color) }
.cond-pill { display:inline-block;padding:.1rem .5rem;border-radius:999px;font-size:.75rem;font-weight:700 }
.cond-pill.cond-new { background:rgba(46,125,50,.12);color:#2e7d32 }
.cond-pill.cond-excellent { background:rgba(74,124,89,.12);color:#4a7c59 }
.cond-pill.cond-good { background:rgba(157,125,59,.12);color:#9d7d3b }
.cond-pill.cond-fair { background:rgba(192,57,43,.1);color:#c0392b }
.modal-icon-wrap { display:grid;place-items:center;width:40px;height:40px;border-radius:10px;background:rgba(200,164,93,.12);border:1px solid rgba(200,164,93,.2);color:var(--gold-color);font-size:1.2rem;flex-shrink:0 }
@media (max-width:575.98px) { .detail-img-wrap { width:100% } }
</style>
