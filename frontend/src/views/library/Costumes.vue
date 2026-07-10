<template>
  <div class="fade-in-up">
    <div class="content-card mb-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <p class="costumes-eyebrow mb-1">Library</p>
          <h1 class="costumes-title mb-0">Costumes</h1>
        </div>
        <button
          v-if="isAdmin"
          class="btn btn-primary d-flex align-items-center gap-2"
          @click="openCreate"
        >
          <i class="bi bi-plus-lg"></i><span>Add Costume</span>
        </button>
      </div>
    </div>

    <div class="filters-bar mb-4">
      <div class="row g-2">
        <div class="col-12 col-md-6">
          <div class="search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input
              v-model="search"
              type="text"
              class="search-input"
              placeholder="Search costumes by name, group, or description…"
            />
            <button
              v-if="search"
              class="search-clear"
              @click="search = ''"
              aria-label="Clear search"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterCategory" class="filter-select">
            <option value="">All Categories</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <select v-model="filterType" class="filter-select">
            <option value="">All Types</option>
            <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="!store.costumes.length && loading" class="loading-state">
      <div class="loading-ring"></div>
      <p>Loading costumes…</p>
    </div>

    <template v-if="store.costumes.length || !loading">
      <div v-if="store.error" class="error-banner">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ store.error }}
      </div>

      <div v-if="!store.costumes.length" class="empty-state content-card">
        <div class="empty-icon"><i class="bi bi-person-badge"></i></div>
        <h5 class="mt-3 mb-1">No costumes found</h5>
        <p class="text-muted mb-0">Try adjusting your filters.</p>
      </div>

      <div v-if="store.costumes.length > 0" class="grid-wrapper" :key="page">
        <div v-if="loading" class="grid-loading-overlay"><div class="loading-ring"></div></div>
        <div class="costumes-grid">
        <article
          v-for="(item, idx) in store.costumes"
          :key="item.id"
          class="costume-card"
          :style="`animation-delay:${idx * 0.04}s`"
          @click="selectedCostume = item"
        >
          <div class="costume-img-wrap">
            <img :src="imageUrl(item.image)" :alt="item.name" class="costume-img" loading="lazy" />
            <div class="costume-img-overlay"><i class="bi bi-eye-fill"></i></div>
            <span class="costume-cat-badge">{{ item.group_category }}</span>
            <span class="costume-type-badge">{{ item.type }}</span>
          </div>
          <div class="costume-body">
            <h6 class="costume-name">{{ item.name }}</h6>
            <div class="costume-meta">
              <span class="costume-meta-item"
                ><i class="bi bi-upc-scan"></i>{{ item.costume_code }}</span
              >
              <span class="costume-meta-item"
                ><i class="bi bi-tag"></i>{{ item.group_category }}</span
              >
              <span v-if="item.gender" class="costume-meta-item costume-gender"
                ><i class="bi bi-person"></i>{{ item.gender }}</span
              >
            </div>
            <p v-if="item.description" class="costume-notes">{{ item.description }}</p>
          </div>
          <div v-if="isAdmin" class="costume-actions" @click.stop>
            <button class="action-btn action-edit" title="Edit" @click="openEdit(item)">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="action-btn action-delete" title="Delete" @click="confirmDelete(item)">
              <i class="bi bi-trash3"></i>
            </button>
          </div>
        </article>
      </div>
      </div>

      <div v-if="store.costumeMeta.total > 0" class="pagination-bar">
        <div class="result-count">
          Page {{ store.costumeMeta.current_page }} of {{ store.costumeMeta.last_page }} &mdash;
          {{ store.costumeMeta.total }} costume{{ store.costumeMeta.total !== 1 ? "s" : "" }}
        </div>
        <div class="pagination-controls">
          <button class="page-btn" :disabled="page <= 1" @click="goToPage(page - 1)" aria-label="Previous page">
            <i class="bi bi-chevron-left"></i>
          </button>
          <template v-for="n in visiblePages" :key="n">
            <span v-if="n === '...'" class="page-ellipsis">&hellip;</span>
            <button v-else class="page-btn" :class="{ active: n === page }" @click="goToPage(n)">{{ n }}</button>
          </template>
          <button class="page-btn" :disabled="page >= store.costumeMeta.last_page" @click="goToPage(page + 1)" aria-label="Next page">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </template>
    <div v-if="loading && store.costumes.length" class="loading-overlay-bottom">
      <div class="loading-ring"></div>
    </div>

    <Teleport to="body">
      <transition name="modal">
        <div
          v-if="selectedCostume && !showForm && !deleteTarget"
          class="modal-overlay"
          @click.self="selectedCostume = null"
        >
          <div class="modal-sheet" role="dialog" aria-modal="true">
            <button class="modal-close-btn" @click="selectedCostume = null" aria-label="Close">
              <i class="bi bi-x-lg"></i>
            </button>
            <div class="d-flex flex-column flex-md-row gap-4">
              <div class="detail-img-wrap">
                <img
                  :src="imageUrl(selectedCostume.image)"
                  :alt="selectedCostume.name"
                  class="detail-img"
                />
              </div>
              <div class="flex-fill">
                <h3 class="detail-title">{{ selectedCostume.name }}</h3>
                <div class="detail-field">
                  <span class="df-label">Code</span
                  ><span class="df-value">{{ selectedCostume.costume_code }}</span>
                </div>
                <div class="detail-field">
                  <span class="df-label">Type</span
                  ><span class="df-value"
                    ><span class="type-pill">{{ selectedCostume.type }}</span></span
                  >
                </div>
                <div class="detail-field" v-if="selectedCostume.gender">
                  <span class="df-label">Gender</span
                  ><span class="df-value"><span class="gender-pill">{{ selectedCostume.gender }}</span></span>
                </div>
                <div class="detail-field">
                  <span class="df-label">Group</span
                  ><span class="df-value">{{ selectedCostume.group_category }}</span>
                </div>
                <div class="detail-field">
                  <span class="df-label">Rack</span
                  ><span class="df-value">{{ selectedCostume.rack_id }}</span>
                </div>
                <div class="detail-field" v-if="selectedCostume.description">
                  <span class="df-label">Description</span
                  ><span class="df-value">{{ selectedCostume.description }}</span>
                </div>
                <div v-if="isAdmin" class="d-flex gap-2 mt-3">
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(selectedCostume)">
                    <i class="bi bi-pencil me-1"></i>Edit
                  </button>
                  <button
                    class="btn btn-sm btn-outline-danger"
                    @click="confirmDelete(selectedCostume)"
                  >
                    <i class="bi bi-trash3 me-1"></i>Delete
                  </button>
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
            <button class="modal-close-btn" @click="closeForm" aria-label="Close">
              <i class="bi bi-x-lg"></i>
            </button>
            <div class="modal-header-row d-flex align-items-center gap-2 mb-3">
              <div class="modal-icon-wrap">
                <i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              </div>
              <div>
                <h5 class="mb-0">{{ isEditing ? "Edit Costume" : "Add Costume" }}</h5>
                <p class="text-muted mb-0 small">
                  {{ isEditing ? "Update the costume details" : "Fill in the details below" }}
                </p>
              </div>
            </div>
            <div v-if="formError" class="alert alert-danger py-2 small">{{ formError }}</div>
            <form @submit.prevent="submitForm">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label small fw-bold"
                    >Name <span class="text-danger">*</span></label
                  ><input
                    v-model="form.name"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Costume name"
                    required
                  />
                </div>
                <div class="col-4">
                  <label class="form-label small fw-bold">Code</label
                  ><input
                    v-model="form.costume_code"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="e.g. K.1.001"
                  />
                </div>
                <div class="col-4">
                  <label class="form-label small fw-bold">Type</label>
                  <select v-model="form.type" class="form-select form-select-sm">
                    <option value="costume">Costume</option>
                    <option value="accessory">Accessory</option>
                  </select>
                </div>
                <div class="col-4">
                  <label class="form-label small fw-bold">Gender</label>
                  <select v-model="form.gender" class="form-select form-select-sm">
                    <option value="">—</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="unisex">Unisex</option>
                  </select>
                </div>
                <div class="col-4">
                  <label class="form-label small fw-bold">Group Category</label
                  ><input
                    v-model="form.group_category"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="e.g. BMS, TRMS"
                  />
                </div>
                <div class="col-4">
                  <label class="form-label small fw-bold">Rack ID</label
                  ><input
                    v-model="form.rack_id"
                    type="number"
                    class="form-control form-control-sm"
                    placeholder="0"
                  />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Google Drive File ID</label
                  ><input
                    v-model="form.image"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Google Drive file ID"
                  />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Description</label
                  ><textarea
                    v-model="form.description"
                    class="form-control form-control-sm"
                    rows="2"
                    placeholder="Optional description"
                  ></textarea>
                </div>
              </div>
              <div class="d-flex gap-2 justify-content-end mt-4">
                <button type="button" class="btn btn-sm btn-secondary" @click="closeForm">
                  Cancel
                </button>
                <button type="submit" class="btn btn-sm btn-primary" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span
                  >{{ isEditing ? "Save Changes" : "Add Costume" }}
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
            <div class="delete-icon-wrap">
              <i class="bi bi-trash3-fill" style="font-size: 2rem; color: var(--accent-color)"></i>
            </div>
            <h5 class="mt-3 mb-1">Delete Costume</h5>
            <p class="text-muted mb-4 small">
              Remove <strong>{{ deleteTarget.name }}</strong
              >? This cannot be undone.
            </p>
            <div class="d-flex gap-2 justify-content-center">
              <button class="btn btn-sm btn-secondary" @click="cancelDelete" :disabled="deleting">
                Cancel
              </button>
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
import { computed, ref, watch } from "vue";
import { useLibraryStore } from "../../stores/api";
import { useAuthStore } from "../../stores/auth";

export default {
  name: "LibraryCostumes",
  setup() {
    const store = useLibraryStore();
    const authStore = useAuthStore();
    const userRole = computed(() => authStore.user?.role?.toLowerCase());
    const isAdmin = computed(() => userRole.value === "admin" || userRole.value === "manager");

    const search = ref("");
    const filterCategory = ref("");
    const filterType = ref("");
    const page = ref(1);

    const categories = computed(() => store.costumeGroups);
    const types = ["costume", "accessory"];
    const loading = computed(() => store.loading);

    const imageUrl = (id) => (id ? `https://lh3.googleusercontent.com/d/${id}=w1200?authuser=0` : "");

    let searchTimer = null;
    const fetchData = () => {
      if (searchTimer) clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        store.fetchCostumes({
          page: page.value,
          per_page: 20,
          search: search.value.trim(),
          group_category: filterCategory.value || undefined,
          type: filterType.value || undefined,
        });
      }, search.value ? 300 : 0);
    };

    watch([search, filterCategory, filterType], () => {
      page.value = 1;
      fetchData();
    });

    watch(page, () => {
      fetchData();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    const visiblePages = computed(() => {
      const lp = store.costumeMeta.last_page;
      const cp = page.value;
      if (lp <= 7) return Array.from({ length: lp }, (_, i) => i + 1);
      const pages = [];
      pages.push(1);
      if (cp > 3) pages.push("...");
      for (let i = Math.max(2, cp - 1); i <= Math.min(lp - 1, cp + 1); i++) pages.push(i);
      if (cp < lp - 2) pages.push("...");
      pages.push(lp);
      return pages;
    });

    const goToPage = (n) => {
      if (n >= 1 && n <= store.costumeMeta.last_page) page.value = n;
    };

    if (!store.costumes.length) store.fetchCostumes({ per_page: 20 });

    const selectedCostume = ref(null);

    const showForm = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const form = ref({
      name: "",
      costume_code: "",
      type: "costume",
      gender: "",
      group_category: "",
      rack_id: 0,
      image: "",
      description: "",
    });
    const formError = ref("");
    const submitting = ref(false);
    const deleteTarget = ref(null);
    const deleting = ref(false);

    const openCreate = () => {
      isEditing.value = false;
      editingId.value = null;
      form.value = {
        name: "",
        costume_code: "",
        type: "costume",
        gender: "",
        group_category: "",
        rack_id: 0,
        image: "",
        description: "",
      };
      formError.value = "";
      showForm.value = true;
    };
    const openEdit = (item) => {
      isEditing.value = true;
      editingId.value = item.id;
      form.value = {
        name: item.name,
        costume_code: item.costume_code || "",
        type: item.type || "costume",
        gender: item.gender || "",
        group_category: item.group_category || "",
        rack_id: item.rack_id || 0,
        image: item.image || "",
        description: item.description || "",
      };
      formError.value = "";
      showForm.value = true;
    };
    const closeForm = () => {
      showForm.value = false;
      formError.value = "";
    };

    const validate = () => {
      if (!form.value.name.trim()) {
        formError.value = "Name is required";
        return false;
      }
      return true;
    };

    const submitForm = async () => {
      if (!validate()) return;
      submitting.value = true;
      formError.value = "";
      try {
        const payload = { ...form.value, rack_id: Number(form.value.rack_id) || 0 };
        if (isEditing.value) await store.updateCostume(editingId.value, payload);
        else await store.createCostume(payload);
        closeForm();
        fetchData();
      } catch (err) {
        formError.value = err.message || "Something went wrong";
      } finally {
        submitting.value = false;
      }
    };

    const confirmDelete = (item) => {
      selectedCostume.value = null;
      deleteTarget.value = item;
    };
    const cancelDelete = () => {
      deleteTarget.value = null;
    };
    const doDelete = async () => {
      deleting.value = true;
      try {
        await store.deleteCostume(deleteTarget.value.id);
        cancelDelete();
        fetchData();
      } catch (err) {
        alert("Delete failed: " + (err.message || "Unknown error"));
        deleting.value = false;
      }
    };

    return {
      store,
      search,
      filterCategory,
      filterType,
      page,
      categories,
      types,
      loading,
      imageUrl,
      isAdmin,
      visiblePages,
      goToPage,
      selectedCostume,
      showForm,
      isEditing,
      form,
      formError,
      submitting,
      deleteTarget,
      deleting,
      openCreate,
      openEdit,
      closeForm,
      submitForm,
      confirmDelete,
      cancelDelete,
      doDelete,
    };
  },
};
</script>

<style scoped>
.costumes-eyebrow {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold-color);
}
.costumes-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink-color);
  letter-spacing: -0.01em;
}
.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 0.85rem;
  color: var(--muted-color);
  font-size: 0.9rem;
  pointer-events: none;
}
.search-input {
  width: 100%;
  padding: 0.6rem 2.5rem 0.6rem 2.4rem;
  border: 1px solid var(--hairline-color);
  border-radius: var(--radius-md);
  background: rgba(255, 253, 248, 0.9);
  color: var(--ink-color);
  font-size: 0.9rem;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}
.search-input:focus {
  outline: none;
  border-color: var(--gold-color);
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.18);
}
.search-clear {
  position: absolute;
  right: 0.7rem;
  border: 0;
  background: transparent;
  color: var(--muted-color);
  cursor: pointer;
  padding: 0.1rem 0.3rem;
  font-size: 0.8rem;
  border-radius: 4px;
  transition: color 0.15s;
}
.search-clear:hover {
  color: var(--accent-color);
}
.filter-select {
  width: 100%;
  padding: 0.55rem 0.85rem;
  border: 1px solid var(--hairline-color);
  border-radius: var(--radius-md);
  background: rgba(255, 253, 248, 0.9);
  color: var(--ink-color);
  font-size: 0.85rem;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2390887b'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  padding-right: 2rem;
}
.filter-select:hover { border-color: rgba(200,164,93,0.5) }
.filter-select:focus {
  outline: none;
  border-color: var(--gold-color);
  box-shadow: 0 0 0 3px rgba(200,164,93,0.12);
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 0;
  color: var(--muted-color);
  gap: 1rem;
}
.loading-ring {
  width: 42px;
  height: 42px;
  border: 3px solid rgba(200, 164, 93, 0.2);
  border-top-color: var(--gold-color);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}
.empty-icon {
  display: inline-grid;
  place-items: center;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(200, 164, 93, 0.1);
  border: 1px solid rgba(200, 164, 93, 0.2);
}
.empty-icon i {
  font-size: 2rem;
  color: var(--gold-color);
}
.error-banner {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-md);
  background: #fef0f0;
  border: 1px solid #f5c6cb;
  color: #b33c3c;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}
@keyframes fadeInUp { from { opacity:0;transform:translateY(16px) } to { opacity:1;transform:translateY(0) } }
.grid-wrapper {
  position: relative;
  animation: gridFadeIn 0.35s ease-out;
}
@keyframes gridFadeIn { from { opacity:0;transform:translateY(8px) } to { opacity:1;transform:translateY(0) } }
.grid-loading-overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(255,253,248,0.55);
  backdrop-filter: blur(2px);
  border-radius: var(--radius-md);
  z-index: 2;
}
.loading-overlay-bottom {
  display: flex;
  justify-content: center;
  padding: 1.5rem 0;
}
.costumes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.25rem;
}
.costume-card {
  border-radius: var(--radius-md);
  border: 1px solid var(--hairline-color);
  background: rgba(255, 253, 248, 0.96);
  box-shadow: 0 2px 12px rgba(19, 18, 16, 0.06);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  animation: fadeInUp 0.4s ease-out both;
  cursor: pointer;
  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease,
    border-color 0.22s ease;
}
.costume-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(19, 18, 16, 0.13), 0 0 0 1px rgba(200, 164, 93, 0.25);
  border-color: rgba(200, 164, 93, 0.3);
}
.costume-img-wrap {
  position: relative;
  height: 240px;
  overflow: hidden;
  background: #ede8df;
  flex-shrink: 0;
}
.costume-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s ease;
}
.costume-card:hover .costume-img {
  transform: scale(1.04);
}
.costume-img-wrap::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 40%;
  background: linear-gradient(transparent, rgba(16,19,31,0.35));
  pointer-events: none;
}
.costume-img-overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(16, 19, 31, 0.45);
  color: #fff;
  font-size: 1.4rem;
  opacity: 0;
  transition: opacity 0.22s ease;
  z-index: 1;
}
.costume-card:hover .costume-img-overlay {
  opacity: 1;
}
.costume-cat-badge {
  position: absolute;
  bottom: 10px;
  left: 10px;
  padding: 0.2rem 0.7rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  background: rgba(16, 19, 31, 0.7);
  color: #fff;
  letter-spacing: 0.05em;
  backdrop-filter: blur(4px);
  z-index: 2;
}
.costume-type-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  background: rgba(200, 164, 93, 0.85);
  color: #fff;
  letter-spacing: 0.04em;
  backdrop-filter: blur(4px);
  z-index: 2;
}
.costume-body {
  padding: 0.85rem 0.9rem;
  flex: 1;
}
.costume-name {
  margin: 0 0 0.35rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ink-color);
}
.costume-meta {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  margin-bottom: 0.3rem;
}
.costume-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.78rem;
  color: var(--muted-color);
}
.costume-meta-item i {
  font-size: 0.72rem;
}
.costume-notes {
  font-size: 0.78rem;
  color: var(--muted-color);
  font-style: italic;
  margin: 0.3rem 0 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.costume-actions {
  display: flex;
  border-top: 1px solid var(--hairline-color);
  padding: 0.45rem 0.65rem;
  gap: 0.35rem;
}
.costume-gender i { font-size:0.7rem }
.gender-pill { display:inline-block;padding:.1rem .5rem;border-radius:999px;font-size:.75rem;font-weight:700;background:rgba(90,140,200,.12);color:#4a7c9d;text-transform:capitalize }
.type-pill {
  display: inline-block;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  background: rgba(200, 164, 93, 0.15);
  color: var(--gold-color);
  text-transform: capitalize;
}
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding: 0.75rem 0.5rem;
  border-top: 1px solid var(--hairline-color);
}
.result-count {
  font-size: 0.8rem;
  color: var(--muted-color);
}
.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}
.page-btn {
  min-width: 34px;
  height: 34px;
  border: 1px solid var(--hairline-color);
  border-radius: 6px;
  background: rgba(255, 253, 248, 0.9);
  color: var(--ink-color);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: all 0.15s ease;
  user-select: none;
}
.page-btn:hover:not(:disabled):not(.active) {
  border-color: var(--gold-color);
  color: var(--gold-color);
  background: rgba(200, 164, 93, 0.08);
}
.page-btn.active {
  background: var(--gold-color);
  border-color: var(--gold-color);
  color: #fff;
  box-shadow: 0 2px 8px rgba(200,164,93,0.3);
}
.page-btn:disabled {
  opacity: 0.35;
  cursor: default;
}
.page-ellipsis {
  padding: 0 0.2rem;
  color: var(--muted-color);
  font-size: 0.85rem;
}
.action-btn {
  flex: 1;
  border: 1px solid var(--hairline-color);
  border-radius: 6px;
  background: transparent;
  color: var(--muted-color);
  font-size: 0.82rem;
  padding: 0.32rem;
  cursor: pointer;
  transition: all 0.18s ease;
  line-height: 1;
}
.action-btn:hover {
  border-color: var(--ink-color);
  color: var(--ink-color);
  background: rgba(25, 27, 36, 0.06);
}
.action-btn.action-edit:hover {
  border-color: var(--gold-color);
  color: var(--gold-color);
  background: rgba(200, 164, 93, 0.08);
}
.action-btn.action-delete:hover {
  border-color: #c0392b;
  color: #c0392b;
  background: rgba(192, 57, 43, 0.07);
}
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1050;
  background: rgba(10, 10, 15, 0.6);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  overflow-y: auto;
}
.modal-sheet {
  position: relative;
  background: var(--surface-color, #fffdf8);
  border-radius: 14px;
  border: 1px solid var(--hairline-color);
  box-shadow:
    0 32px 72px rgba(10, 10, 15, 0.36),
    0 0 0 1px rgba(200, 164, 93, 0.1);
  width: 100%;
  max-width: 640px;
  padding: 1.75rem;
}
.modal-sheet--sm {
  max-width: 400px;
  padding: 2rem;
  text-align: center;
}
.modal-close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  border: 0;
  background: rgba(34, 29, 20, 0.08);
  color: var(--muted-color);
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  font-size: 0.85rem;
  cursor: pointer;
  transition:
    background 0.18s,
    color 0.18s;
}
.modal-close-btn:hover {
  background: var(--accent-color);
  color: #fff;
}
.detail-img-wrap {
  flex-shrink: 0;
  width: 220px;
}
.detail-img {
  width: 100%;
  border-radius: 8px;
}
.detail-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--ink-color);
  margin-bottom: 1rem;
}
.detail-field {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  padding: 0.4rem 0;
  border-bottom: 1px solid var(--hairline-color);
}
.detail-field:last-child {
  border-bottom: none;
}
.df-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--muted-color);
  min-width: 80px;
}
.df-value {
  font-size: 0.9rem;
  color: var(--ink-color);
}
.modal-icon-wrap {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(200, 164, 93, 0.12);
  border: 1px solid rgba(200, 164, 93, 0.2);
  color: var(--gold-color);
  font-size: 1.2rem;
  flex-shrink: 0;
}
@media (max-width: 767.98px) {
  .costumes-title { font-size:1.6rem }
  .filters-bar .col-6 { flex:0 0 50%;max-width:50% }
  .search-input { font-size:0.85rem;padding:0.55rem 2.2rem 0.55rem 2.2rem }
}
@media (max-width: 575.98px) {
  .detail-img-wrap { width:100% }
  .costumes-grid { grid-template-columns:1fr;gap:1rem }
  .costume-img-wrap { height:200px }
  .pagination-bar { flex-direction:column;align-items:center;gap:0.5rem;text-align:center }
  .pagination-controls .page-btn:not(.active):not(:first-child):not(:last-child) { display:none }
  .pagination-controls .page-ellipsis { display:none }
  .costumes-title { font-size:1.35rem }
  .modal-sheet { padding:1.25rem;border-radius:12px;margin:0.5rem }
}
@media (max-width: 400px) {
  .filters-bar .col-6 { flex:0 0 100%;max-width:100% }
}
</style>
