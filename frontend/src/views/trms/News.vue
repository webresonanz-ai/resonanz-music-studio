<template>
    <div class="fade-in-up">
        <div class="content-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase text-primary fw-bold small mb-2">News Hub</p>
                    <h1 class="display-4 fw-bold mb-3">Latest News</h1>
                    <p class="lead text-muted mb-0">
                        Stay updated with the latest announcements across all programs.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="bg-dark text-white rounded p-4 h-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-newspaper display-6 text-warning"></i>
                            <div>
                                <div class="fw-bold">Announcements</div>
                                <div class="text-white-50 small">TRMS, BMS, JCO &amp; TRCC</div>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg" @click="openAddModal" v-if="canManage">
                            <i class="bi bi-plus-lg me-2"></i> Add News
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h2 class="h4 fw-bold mb-1">All News</h2>
                    <p class="text-muted mb-0">{{ news.length }} article{{ news.length === 1 ? '' : 's' }}</p>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <label for="programFilter" class="text-muted small mb-0 text-nowrap">Program:</label>
                    <select
                        id="programFilter"
                        v-model="filterProgram"
                        class="form-select form-select-sm"
                        style="width: auto"
                    >
                        <option value="">All Programs</option>
                        <option v-for="p in availablePrograms" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
            </div>

            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
            </div>

            <div v-else-if="filteredNews.length === 0" class="text-center py-5">
                <i class="bi bi-newspaper display-1 text-muted mb-3 d-block"></i>
                <p class="text-muted">No news articles found.</p>
            </div>

            <div v-else class="row row-cols-1 row-cols-md-2 g-4">
                <div v-for="article in filteredNews" :key="article.id" class="col">
                    <div class="card h-100 news-card">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge" :class="programBadgeClass(p)" v-for="p in (article.program_ids || [article.program_id || 'trms'])" :key="p">
                                        {{ programLabel(p) }}
                                    </span>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0" v-if="canManage">
                                    <button class="btn btn-sm btn-outline-primary border-0" @click="openEditModal(article)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger border-0" @click="confirmDelete(article)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h5 class="card-title">{{ article.title }}</h5>
                            <p class="card-text flex-grow-1">{{ truncateContent(article.content) }}</p>
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>{{ formatDate(article.published_at) }}
                                </small>
                                <button class="btn btn-sm btn-outline-secondary" @click="openDetailModal(article)">
                                    Read more <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div class="modal fade" id="newsFormModal" tabindex="-1" ref="formModalEl">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ editingArticle ? 'Edit News' : 'Add News' }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div v-if="successMessage" class="alert alert-success d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>{{ successMessage }}</span>
                            </div>
                            <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>{{ errorMessage }}</span>
                            </div>
                            <form @submit.prevent="submitForm">
                                <div class="mb-3">
                                    <label for="newsTitle" class="form-label">Title</label>
                                    <input
                                        id="newsTitle"
                                        v-model.trim="form.title"
                                        class="form-control"
                                        type="text"
                                        required
                                        maxlength="150"
                                        placeholder="News title"
                                    >
                                </div>
                                <div class="mb-3">
                                    <label for="newsContent" class="form-label">Content</label>
                                    <textarea
                                        id="newsContent"
                                        v-model.trim="form.content"
                                        class="form-control"
                                        rows="5"
                                        required
                                        placeholder="Article content..."
                                    ></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label d-block">Programs</label>
                                    <div class="d-flex flex-wrap gap-4 p-3 rounded border border-secondary border-opacity-25 bg-light bg-opacity-10">
                                        <div class="form-check" v-for="prog in availablePrograms" :key="prog.id">
                                            <input
                                                :id="'prog-' + prog.id"
                                                class="form-check-input"
                                                type="checkbox"
                                                :value="prog.id"
                                                v-model="form.program_ids"
                                            >
                                            <label :for="'prog-' + prog.id" class="form-check-label select-none">
                                                {{ prog.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="newsDate" class="form-label">Published Date</label>
                                    <input
                                        id="newsDate"
                                        v-model="form.published_at"
                                        class="form-control"
                                        type="date"
                                        required
                                    >
                                </div>
                                <div class="d-flex gap-3">
                                    <button class="btn btn-primary" type="submit" :disabled="saving">
                                        <span v-if="saving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                                        <i v-else class="bi bi-check-circle me-2"></i>
                                        {{ saving ? 'Saving...' : (editingArticle ? 'Update' : 'Publish') }}
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div class="modal fade" id="newsDetailModal" tabindex="-1" ref="detailModalEl">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ detailArticle ? detailArticle.title : '' }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" v-if="detailArticle">
                            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                                <span class="badge" :class="programBadgeClass(p)" v-for="p in (detailArticle.program_ids || [detailArticle.program_id || 'trms'])" :key="p">
                                    {{ programLabel(p) }}
                                </span>
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>{{ formatDate(detailArticle.published_at) }}
                                </small>
                            </div>
                            <p class="mb-0" style="white-space: pre-wrap">{{ detailArticle.content }}</p>
                        </div>
                        <div class="modal-footer">
                            <button v-if="canManage && detailArticle" class="btn btn-outline-primary" @click="openEditFromDetail" :disabled="saving">
                                <i class="bi bi-pencil me-2"></i> Edit
                            </button>
                            <button v-if="canManage && detailArticle" class="btn btn-outline-danger" @click="deleteFromDetail" :disabled="saving">
                                <i class="bi bi-trash me-2"></i> Delete
                            </button>
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import { Modal } from 'bootstrap'
import { mapState, mapActions } from 'pinia'
import { useTrmsStore } from '../../stores/api'
import { useAuthStore } from '../../stores/auth'

const emptyForm = () => ({
    title: '',
    content: '',
    program_ids: ['trms'],
    published_at: new Date().toISOString().split('T')[0]
})

export default {
    name: 'News',
    computed: {
        ...mapState(useTrmsStore, ['news']),
        canManage() {
            const authStore = useAuthStore()
            const role = authStore.user?.role?.toLowerCase()
            return role === 'admin' || role === 'manager'
        },
        filteredNews() {
            if (!this.filterProgram) return this.news
            return this.news.filter(a => {
                const ids = a.program_ids || [a.program_id || 'trms']
                return ids.includes(this.filterProgram)
            })
        }
    },
    data() {
        return {
            loading: false,
            saving: false,
            successMessage: '',
            errorMessage: '',
            filterProgram: '',
            form: emptyForm(),
            editingArticle: null,
            detailArticle: null,
            formModalInstance: null,
            detailModalInstance: null,
            availablePrograms: [
                { id: 'trms', name: 'TRMS' },
                { id: 'bms', name: 'BMS' },
                { id: 'jco', name: 'JCO' },
                { id: 'trcc', name: 'TRCC' }
            ]
        }
    },
    async mounted() {
        this.loading = true
        try {
            await this.fetchNews()
        } finally {
            this.loading = false
        }
    },
    methods: {
        ...mapActions(useTrmsStore, ['fetchNews', 'createNews', 'updateNews', 'deleteNews']),

        formatDate(date) {
            if (!date) return ''
            return new Date(date).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        },

        truncateContent(text) {
            if (!text) return ''
            return text.length > 200 ? text.substring(0, 200) + '...' : text
        },

        programLabel(programId) {
            const map = { trms: 'TRMS', bms: 'BMS', jco: 'JCO', trcc: 'TRCC' }
            return map[programId] || (programId || '').toUpperCase()
        },

        programBadgeClass(programId) {
            const map = {
                trms: 'bg-primary',
                bms: 'bg-success',
                jco: 'bg-warning text-dark',
                trcc: 'bg-info text-dark'
            }
            return map[programId] || 'bg-secondary'
        },

        openAddModal() {
            this.editingArticle = null
            this.form = emptyForm()
            this.successMessage = ''
            this.errorMessage = ''
            this.showFormModal()
        },

        openEditModal(article) {
            this.editingArticle = article
            this.form = {
                title: article.title || '',
                content: article.content || '',
                program_ids: [...(article.program_ids || [article.program_id || 'trms'])],
                published_at: article.published_at ? article.published_at.split(' ')[0] : ''
            }
            this.successMessage = ''
            this.errorMessage = ''
            this.showFormModal()
        },

        async submitForm() {
            this.saving = true
            this.successMessage = ''
            this.errorMessage = ''
            try {
                if (this.editingArticle) {
                    await this.updateNews(this.editingArticle.id, this.form)
                    this.successMessage = 'News updated successfully.'
                } else {
                    await this.createNews(this.form)
                    this.successMessage = 'News published successfully.'
                }
                setTimeout(() => this.hideFormModal(), 1000)
            } catch (error) {
                this.errorMessage = error.message || 'Unable to save news article.'
            } finally {
                this.saving = false
            }
        },

        confirmDelete(article) {
            if (!confirm('Are you sure you want to delete this news article?')) return
            this.deleteArticle(article.id)
        },

        async deleteArticle(id) {
            this.saving = true
            try {
                await this.deleteNews(id)
            } catch (error) {
                alert(error.message || 'Unable to delete news article.')
            } finally {
                this.saving = false
            }
        },

        openDetailModal(article) {
            this.detailArticle = article
            this.showDetailModal()
        },

        openEditFromDetail() {
            const article = this.detailArticle
            this.hideDetailModal()
            setTimeout(() => this.openEditModal(article), 300)
        },

        deleteFromDetail() {
            if (!this.detailArticle) return
            if (!confirm('Are you sure you want to delete this news article?')) return
            const id = this.detailArticle.id
            this.hideDetailModal()
            this.deleteArticle(id)
        },

        showFormModal() {
            const el = this.$refs.formModalEl
            if (!el) return
            this.formModalInstance = Modal.getOrCreateInstance(el)
            this.formModalInstance.show()
        },

        hideFormModal() {
            if (this.formModalInstance) {
                this.formModalInstance.hide()
            }
        },

        showDetailModal() {
            const el = this.$refs.detailModalEl
            if (!el) return
            this.detailModalInstance = Modal.getOrCreateInstance(el)
            this.detailModalInstance.show()
        },

        hideDetailModal() {
            if (this.detailModalInstance) {
                this.detailModalInstance.hide()
            }
        }
    },
    beforeUnmount() {
        if (this.formModalInstance) this.formModalInstance.dispose()
        if (this.detailModalInstance) this.detailModalInstance.dispose()
    }
}
</script>

<style scoped>
.news-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.news-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

:deep(.modal-content) {
    background: var(--surface-color);
}

:deep(.modal-header) {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.16), rgba(200, 164, 93, 0.08));
    border-bottom: 1px solid var(--hairline-color);
}
</style>
