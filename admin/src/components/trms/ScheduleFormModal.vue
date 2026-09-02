<template>
    <Teleport to="body">
        <div class="modal fade" id="scheduleModal" tabindex="-1" ref="modalEl">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content sched-modal">
                    <div class="modal-header sched-modal-header">
                        <h5 class="modal-title sched-modal-title">
                            <i class="bi bi-calendar-plus me-2"></i>
                            {{ editingSchedule ? 'Edit Schedule' : 'Add Schedule' }}
                        </h5>
                        <button type="button" class="btn-close sched-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body sched-modal-body">
                        <div v-if="successMessage" class="alert sched-alert sched-alert-success d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>{{ successMessage }}</span>
                        </div>
                        <div v-if="errorMessage" class="alert sched-alert sched-alert-danger d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>{{ errorMessage }}</span>
                        </div>
                        <form @submit.prevent="handleSubmit">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="scheduleTitle" class="form-label sched-label">Title</label>
                                    <input
                                        id="scheduleTitle"
                                        v-model.trim="form.title"
                                        class="form-control sched-input"
                                        type="text"
                                        required
                                    >
                                </div>
                                <div class="col-md-4">
                                    <label for="scheduleType" class="form-label sched-label">Type</label>
                                    <select
                                        id="scheduleType"
                                        v-model="form.type"
                                        class="form-select sched-select"
                                        required
                                    >
                                        <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="scheduleDate" class="form-label sched-label">Date</label>
                                    <input
                                        id="scheduleDate"
                                        v-model="form.date"
                                        class="form-control sched-input"
                                        type="date"
                                        required
                                    >
                                </div>
                                <div class="col-md-3">
                                    <label for="scheduleStartTime" class="form-label sched-label">Start Time</label>
                                    <input
                                        id="scheduleStartTime"
                                        v-model="form.start_time"
                                        class="form-control sched-input"
                                        type="time"
                                        required
                                    >
                                </div>
                                <div class="col-md-3">
                                    <label for="scheduleEndTime" class="form-label sched-label">End Time</label>
                                    <input
                                        id="scheduleEndTime"
                                        v-model="form.end_time"
                                        class="form-control sched-input"
                                        type="time"
                                        required
                                    >
                                </div>
                                <div class="col-12">
                                    <label for="scheduleVenue" class="form-label sched-label">Venue</label>
                                    <input
                                        id="scheduleVenue"
                                        v-model.trim="form.venue"
                                        class="form-control sched-input"
                                        type="text"
                                        maxlength="150"
                                        placeholder="e.g. Aula Simfonia Jakarta"
                                    >
                                </div>
                                <div class="col-12">
                                    <label for="scheduleDescription" class="form-label sched-label">Description</label>
                                    <textarea
                                        id="scheduleDescription"
                                        v-model.trim="form.description"
                                        class="form-control sched-input sched-textarea"
                                        rows="3"
                                    ></textarea>
                                </div>
                                <div class="col-12 sched-concert-section" v-if="form.type === 'concert'">
                                    <label for="scheduleConcertCode" class="form-label sched-label">Concert Code</label>
                                    <input
                                        id="scheduleConcertCode"
                                        v-model.trim="form.concert_code"
                                        class="form-control sched-input text-uppercase"
                                        type="text"
                                        maxlength="50"
                                        placeholder="e.g. SDG"
                                    >
                                    <div class="form-text sched-form-text">Used as the QR code prefix for audience tickets.</div>
                                </div>
                                <div class="col-12 sched-concert-section" v-if="form.type === 'concert'">
                                    <label for="scheduleBannerUrl" class="form-label sched-label">Concert Banner</label>
                                    <ul class="nav nav-tabs sched-tabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button
                                                type="button"
                                                class="nav-link sched-tab-link"
                                                :class="{ active: bannerTab === 'upload' }"
                                                @click="bannerTab = 'upload'"
                                            >
                                                <i class="bi bi-upload me-1"></i> Upload
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button
                                                type="button"
                                                class="nav-link sched-tab-link"
                                                :class="{ active: bannerTab === 'url' }"
                                                @click="bannerTab = 'url'"
                                            >
                                                <i class="bi bi-link-45deg me-1"></i> URL
                                            </button>
                                        </li>
                                    </ul>
                                    <div v-if="bannerTab === 'upload'">
                                        <div
                                            class="sched-dropzone"
                                            :class="{ 'is-dragging': isDragging }"
                                            @dragover.prevent="isDragging = true"
                                            @dragleave.prevent="isDragging = false"
                                            @drop.prevent="handleBannerDrop"
                                            @click="$refs.bannerFileInput.click()"
                                        >
                                            <input
                                                ref="bannerFileInput"
                                                type="file"
                                                accept="image/jpeg,image/png,image/webp"
                                                class="d-none"
                                                @change="handleBannerFileChange"
                                            >
                                            <div v-if="!bannerUploading && !form.banner_url" class="text-center py-3">
                                                <i class="bi bi-image sched-dropzone-icon"></i>
                                                <span class="d-block small sched-dropzone-text">Click or drag & drop an image here</span>
                                                <span class="d-block small opacity-50 sched-dropzone-hint">JPEG, PNG, WebP &mdash; max 3 MB</span>
                                            </div>
                                            <div v-if="bannerUploading" class="text-center py-3">
                                                <span class="spinner-border spinner-border-sm me-2 sched-spinner" aria-hidden="true"></span>
                                                <span class="small">Uploading&hellip;</span>
                                            </div>
                                            <div v-if="!bannerUploading && form.banner_url" class="sched-preview-wrap">
                                                <img :src="form.banner_url" alt="Banner preview" class="sched-preview-img">
                                                <button
                                                    type="button"
                                                    class="sched-remove-btn"
                                                    @click.stop="clearBanner"
                                                    title="Remove banner"
                                                    aria-label="Remove banner"
                                                >
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div v-if="bannerUploadError" class="text-danger small mt-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>{{ bannerUploadError }}
                                        </div>
                                    </div>
                                    <div v-if="bannerTab === 'url'">
                                        <input
                                            id="scheduleBannerUrl"
                                            v-model.trim="form.banner_url"
                                            class="form-control sched-input"
                                            type="url"
                                            placeholder="https://example.com/banner.jpg"
                                        >
                                        <div v-if="form.banner_url" class="mt-2">
                                            <img
                                                :src="form.banner_url"
                                                alt="Banner preview"
                                                class="sched-url-preview"
                                                @error="bannerUrlBroken = true"
                                                @load="bannerUrlBroken = false"
                                            >
                                            <div v-if="bannerUrlBroken" class="text-warning small mt-1">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Could not load image from this URL.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text sched-form-text">Optional banner image shown on the homepage slideshow.</div>
                                </div>
                                <div class="col-md-6 sched-concert-section" v-if="form.type === 'concert'">
                                    <label for="scheduleAudienceCapacity" class="form-label sched-label">Audience Capacity</label>
                                    <input
                                        id="scheduleAudienceCapacity"
                                        v-model.number="form.audience_capacity"
                                        class="form-control sched-input"
                                        type="number"
                                        min="1"
                                        placeholder="e.g. 500"
                                    >
                                    <div class="form-text sched-form-text">Leave empty for unlimited registrations.</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center sched-concert-section" v-if="form.type === 'concert'">
                                    <div class="form-check form-switch">
                                        <input
                                            id="scheduleIsOpenRegister"
                                            v-model="form.is_open_register"
                                            class="form-check-input sched-switch"
                                            type="checkbox"
                                            role="switch"
                                            :disabled="!hasConcertCode"
                                        >
                                        <label for="scheduleIsOpenRegister" class="form-check-label sched-switch-label">
                                            Open for Registration
                                        </label>
                                        <div class="form-text sched-form-text mt-1">
                                            {{ hasConcertCode ? 'When enabled, the "Register Now" button appears publicly.' : 'Fill Concert Code first to open registration.' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 sched-concert-section" v-if="form.type === 'concert'">
                                    <div class="form-check form-switch">
                                        <input
                                            id="scheduleIsRedirectUrl"
                                            v-model="form.is_redirect_url"
                                            class="form-check-input sched-switch"
                                            type="checkbox"
                                            role="switch"
                                        >
                                        <label for="scheduleIsRedirectUrl" class="form-check-label sched-switch-label">
                                            Redirect to External URL
                                        </label>
                                        <div class="form-text sched-form-text mt-1">
                                            When enabled, the "Register Now" button will redirect visitors to the URL below instead of the internal registration page.
                                        </div>
                                    </div>
                                    <div class="mt-3" v-if="form.is_redirect_url">
                                        <label for="scheduleRedirectUrl" class="form-label sched-label">Redirect URL</label>
                                        <input
                                            id="scheduleRedirectUrl"
                                            v-model.trim="form.redirect_url"
                                            class="form-control sched-input"
                                            type="url"
                                            placeholder="https://example.com/register"
                                            required
                                        >
                                    </div>
                                </div>
                                <div class="col-12 sched-concert-section" v-if="form.type === 'concert' && !form.is_redirect_url">
                                    <div class="sched-seat-section">
                                        <div class="form-check form-switch mb-3">
                                            <input
                                                id="scheduleIsSeatAssign"
                                                v-model="form.is_seat_assign"
                                                class="form-check-input sched-switch"
                                                type="checkbox"
                                                role="switch"
                                            >
                                            <label for="scheduleIsSeatAssign" class="form-check-label sched-switch-label">
                                                <i class="bi bi-grid-3x3-gap me-1"></i> Seat Assignment
                                            </label>
                                            <div class="form-text sched-form-text mt-1">
                                                When enabled, guests choose a seat from a visual layout during registration.
                                            </div>
                                        </div>
                                        <div v-if="form.is_seat_assign">
                                            <label class="form-label sched-label fw-semibold small mb-2">Seating Layout</label>
                                            <ConcertLayoutPicker v-model="form.seat_layout_id" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label sched-label d-block">Programs / Collaborating Groups</label>
                                    <div class="sched-programs-wrap">
                                        <div class="form-check" v-for="prog in availablePrograms" :key="prog.id">
                                            <input
                                                :id="'prog-' + prog.id"
                                                class="form-check-input sched-check"
                                                type="checkbox"
                                                :value="prog.id"
                                                v-model="form.program_ids"
                                            >
                                            <label :for="'prog-' + prog.id" class="form-check-label sched-check-label select-none">
                                                {{ prog.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="sched-actions">
                                <button class="btn sched-btn-primary" type="submit" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-2 sched-spinner" aria-hidden="true"></span>
                                    <i v-else class="bi bi-check-circle me-2"></i>
                                    {{ loading ? 'Saving...' : (editingSchedule ? 'Update Schedule' : 'Add Schedule') }}
                                </button>
                                <button v-if="editingSchedule" class="btn sched-btn-danger" type="button" @click="handleDelete" :disabled="loading">
                                    <i class="bi bi-trash me-2"></i> Delete
                                </button>
                                <button class="btn sched-btn-ghost" type="button" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import { Modal } from 'bootstrap'
import { useTrmsStore } from '../../stores/api'
import ConcertLayoutPicker from './ConcertLayoutPicker.vue'

const ALL_TYPE_OPTIONS = [
    { value: 'lesson', label: 'Lesson' },
    { value: 'practice', label: 'Practice' },
    { value: 'concert', label: 'Concert' },
    { value: 'exam', label: 'Exam' },
    { value: 'other', label: 'Other' },
]

const ALL_PROGRAMS = [
    { id: 'trms', name: 'TRMS' },
    { id: 'bms', name: 'BMS' },
    { id: 'jco', name: 'JCO' },
    { id: 'trcc', name: 'TRCC' },
]

const emptyForm = () => ({
    title: '',
    type: 'lesson',
    date: '',
    start_time: '09:00',
    end_time: '10:00',
    venue: '',
    concert_code: '',
    description: '',
    banner_url: '',
    is_open_register: false,
    is_redirect_url: false,
    redirect_url: '',
    audience_capacity: null,
    is_seat_assign: false,
    seat_layout_id: null,
    program_ids: ['trms']
})

export default {
    name: 'ScheduleFormModal',
    components: { ConcertLayoutPicker },
    props: {
        loading: Boolean,
        successMessage: String,
        errorMessage: String,
        allowedTypes: { type: Array, default: null },
        allowedPrograms: { type: Array, default: null }
    },
    emits: ['submit', 'delete'],
    data() {
        return {
            form: emptyForm(),
            editingSchedule: null,
            modalInstance: null,
            bannerTab: 'upload',
            bannerUploading: false,
            bannerUploadError: '',
            bannerUrlBroken: false,
            isDragging: false
        }
    },
    computed: {
        typeOptions() {
            if (this.allowedTypes && this.allowedTypes.length) {
                return ALL_TYPE_OPTIONS.filter(t => this.allowedTypes.includes(t.value))
            }
            return ALL_TYPE_OPTIONS
        },
        availablePrograms() {
            if (this.allowedPrograms && this.allowedPrograms.length) {
                return ALL_PROGRAMS.filter(p => this.allowedPrograms.includes(p.id))
            }
            return ALL_PROGRAMS
        },
        hasConcertCode() {
            return this.form.type === 'concert' && String(this.form.concert_code || '').trim() !== ''
        }
    },
    watch: {
        hasConcertCode(value) {
            if (!value) {
                this.form.is_open_register = false
            }
        }
    },
    methods: {
        show() {
            const el = this.$refs.modalEl
            if (!el) return
            this.modalInstance = Modal.getOrCreateInstance(el)
            this.modalInstance.show()
        },
        hide() {
            if (this.modalInstance) {
                this.modalInstance.hide()
            }
        },
        openAdd(defaultProgram = 'trms') {
            this.editingSchedule = null
            this.form = emptyForm()
            if (this.allowedTypes && this.allowedTypes.length) {
                this.form.type = this.allowedTypes[0]
            }
            this.form.program_ids = [defaultProgram]
            this.form.date = new Date().toISOString().split('T')[0]
            this.bannerTab = 'upload'
            this.bannerUploadError = ''
            this.bannerUrlBroken = false
            this.show()
        },
        openEdit(schedule) {
            this.editingSchedule = schedule
            this.form = {
                ...schedule,
                program_ids: schedule.program_ids ? [...schedule.program_ids] : ['trms'],
                is_open_register: !!+schedule.is_open_register,
                is_redirect_url:  !!+schedule.is_redirect_url,
                is_seat_assign:   !!+schedule.is_seat_assign,
                seat_layout_id: schedule.seat_layout_id || null,
            }
            this.bannerTab = 'upload'
            this.bannerUploadError = ''
            this.bannerUrlBroken = false
            this.show()
        },
        openDay(dateKey, defaultProgram = 'trms') {
            this.editingSchedule = null
            this.form = emptyForm()
            if (this.allowedTypes && this.allowedTypes.length) {
                this.form.type = this.allowedTypes[0]
            }
            this.form.program_ids = [defaultProgram]
            this.form.date = dateKey
            this.bannerTab = 'upload'
            this.bannerUploadError = ''
            this.bannerUrlBroken = false
            this.show()
        },
        handleSubmit() {
            this.$emit('submit', {
                mode: this.editingSchedule ? 'edit' : 'add',
                data: { ...this.form },
                scheduleId: this.editingSchedule ? this.editingSchedule.id : null
            })
        },
        handleDelete() {
            if (!this.editingSchedule) return
            if (!confirm('Are you sure you want to delete this schedule?')) return
            this.$emit('delete', this.editingSchedule.id)
        },
        async handleBannerFileChange(event) {
            const file = event.target.files?.[0]
            if (file) await this.uploadBannerFile(file)
        },
        async handleBannerDrop(event) {
            this.isDragging = false
            const file = event.dataTransfer.files?.[0]
            if (file) await this.uploadBannerFile(file)
        },
        async uploadBannerFile(file) {
            const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
            if (!allowed.includes(file.type)) {
                this.bannerUploadError = 'Only JPEG, PNG, and WebP images are allowed.'
                return
            }
            if (file.size > 3 * 1024 * 1024) {
                this.bannerUploadError = 'File size exceeds the 3 MB limit.'
                return
            }
            this.bannerUploadError = ''
            this.bannerUploading = true
            try {
                const trmsStore = useTrmsStore()
                const result = await trmsStore.uploadScheduleBanner(file)
                this.form.banner_url = result.url
            } catch (err) {
                this.bannerUploadError = err.message || 'Upload failed.'
            } finally {
                this.bannerUploading = false
                if (this.$refs.bannerFileInput) {
                    this.$refs.bannerFileInput.value = ''
                }
            }
        },
        clearBanner() {
            this.form.banner_url = ''
            this.bannerUploadError = ''
            if (this.$refs.bannerFileInput) {
                this.$refs.bannerFileInput.value = ''
            }
        }
    },
    beforeUnmount() {
        if (this.modalInstance) {
            this.modalInstance.dispose()
        }
    }
}
</script>

<style scoped>
/* ── Modal shell ──────────────────────────────────────────── */
.modal-content {
    background: transparent;
}

.sched-modal {
    border: 1px solid rgba(234, 220, 194, 0.12);
    border-radius: 14px;
    background:
        linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
        linear-gradient(180deg, #1a1f30 0%, #111420 100%);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.04) inset,
        0 24px 56px rgba(8, 8, 14, 0.55);
    color: rgba(234, 220, 194, 0.85);
}

/* ── Header ───────────────────────────────────────────────── */
.sched-modal-header {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.2), rgba(200, 164, 93, 0.08));
    border-bottom: 1px solid rgba(234, 220, 194, 0.08);
    border-radius: 13px 13px 0 0;
    padding: 1rem 1.25rem;
}

.sched-modal-title {
    color: var(--gold-color, #c8a45d);
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
}

.sched-close {
    filter: brightness(0) invert(0.8);
    opacity: 0.6;
    transition: opacity 0.2s ease;
}

.sched-close:hover {
    opacity: 1;
}

/* ── Body ─────────────────────────────────────────────────── */
.sched-modal-body {
    padding: 1.35rem 1.25rem;
}

/* ── Alerts ───────────────────────────────────────────────── */
.sched-alert {
    border: 1px solid rgba(234, 220, 194, 0.1);
    border-radius: 10px;
    background: rgba(234, 220, 194, 0.06);
    color: rgba(234, 220, 194, 0.85);
    padding: 0.7rem 1rem;
    font-size: 0.875rem;
}

.sched-alert-success {
    border-color: rgba(76, 175, 125, 0.3);
    background: rgba(76, 175, 125, 0.1);
    color: #7cdbab;
}

.sched-alert-danger {
    border-color: rgba(224, 80, 80, 0.3);
    background: rgba(224, 80, 80, 0.1);
    color: #f08080;
}

/* ── Labels ───────────────────────────────────────────────── */
.sched-label {
    color: rgba(234, 220, 194, 0.75);
    font-weight: 600;
    font-size: 0.82rem;
    margin-bottom: 0.3rem;
}

/* ── Inputs / Selects / Textareas ─────────────────────────── */
.sched-input,
.sched-select,
.sched-textarea {
    background: rgba(10, 12, 22, 0.6);
    border: 1px solid rgba(234, 220, 194, 0.15);
    border-radius: 8px;
    color: #fffdf8;
    font-size: 0.9rem;
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease;
}

.sched-input:focus,
.sched-select:focus,
.sched-textarea:focus {
    border-color: rgba(200, 164, 93, 0.5);
    background: rgba(10, 12, 22, 0.75);
    box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.12);
    color: #fffdf8;
}

.sched-input::placeholder,
.sched-textarea::placeholder {
    color: rgba(234, 220, 194, 0.3);
}

.sched-select option {
    background: #1a1f30;
    color: #fffdf8;
}

/* ── Form text ────────────────────────────────────────────── */
.sched-form-text {
    color: rgba(234, 220, 194, 0.45);
    font-size: 0.78rem;
    margin-top: 0.25rem;
}

/* ── Concert sections ─────────────────────────────────────── */
.sched-concert-section {
    margin-top: 0.5rem;
}

/* ── Tabs ─────────────────────────────────────────────────── */
.sched-tabs {
    border-bottom: 1px solid rgba(234, 220, 194, 0.1);
    gap: 0;
    margin-bottom: 0.75rem;
}

.sched-tab-link {
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    color: rgba(234, 220, 194, 0.55);
    background: transparent;
    font-size: 0.82rem;
    font-weight: 500;
    padding: 0.45rem 0.9rem;
    transition:
        color 0.2s ease,
        border-color 0.2s ease;
}

.sched-tab-link:hover {
    color: rgba(234, 220, 194, 0.85);
    border-bottom-color: rgba(200, 164, 93, 0.3);
}

.sched-tab-link.active {
    color: var(--gold-color, #c8a45d);
    border-bottom-color: var(--gold-color, #c8a45d);
    background: transparent;
}

/* ── Dropzone ─────────────────────────────────────────────── */
.sched-dropzone {
    border: 2px dashed rgba(200, 164, 93, 0.25);
    border-radius: 10px;
    min-height: 120px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition:
        border-color 0.25s ease,
        background 0.25s ease;
    background: rgba(200, 164, 93, 0.04);
    overflow: hidden;
    position: relative;
}

.sched-dropzone:hover,
.sched-dropzone.is-dragging {
    border-color: var(--gold-color, #c8a45d);
    background: rgba(200, 164, 93, 0.08);
}

.sched-dropzone-icon {
    font-size: 1.75rem;
    color: rgba(200, 164, 93, 0.45);
    display: block;
    margin-bottom: 0.35rem;
}

.sched-dropzone-text {
    color: rgba(234, 220, 194, 0.55);
}

.sched-dropzone-hint {
    color: rgba(234, 220, 194, 0.3);
}

.sched-spinner {
    color: var(--gold-color, #c8a45d);
}

/* ── Preview ──────────────────────────────────────────────── */
.sched-preview-wrap {
    width: 100%;
    position: relative;
}

.sched-preview-img {
    display: block;
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border-radius: 8px;
}

.sched-remove-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    background: rgba(0, 0, 0, 0.55);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 1.75rem;
    height: 1.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.75rem;
    transition: background 0.2s ease;
}

.sched-remove-btn:hover {
    background: rgba(200, 0, 0, 0.75);
}

.sched-url-preview {
    display: block;
    width: 100%;
    max-height: 160px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid rgba(234, 220, 194, 0.1);
}

/* ── Seat section ─────────────────────────────────────────── */
.sched-seat-section {
    padding: 1rem;
    border-radius: 10px;
    border: 1px solid rgba(234, 220, 194, 0.08);
    background: rgba(234, 220, 194, 0.03);
}

/* ── Programs wrap ────────────────────────────────────────── */
.sched-programs-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border-radius: 10px;
    border: 1px solid rgba(234, 220, 194, 0.08);
    background: rgba(234, 220, 194, 0.03);
}

/* ── Switches / Checkboxes ────────────────────────────────── */
.sched-switch {
    cursor: pointer;
}

.sched-switch:checked {
    background-color: var(--gold-color, #c8a45d);
    border-color: var(--gold-color, #c8a45d);
}

.sched-switch:disabled {
    opacity: 0.35;
    cursor: not-allowed.
}

.sched-switch-label {
    color: rgba(234, 220, 194, 0.85);
    font-weight: 600;
    cursor: pointer.
}

.sched-check {
    cursor: pointer;
}

.sched-check:checked {
    background-color: var(--gold-color, #c8a45d);
    border-color: var(--gold-color, #c8a45d);
}

.sched-check-label {
    color: rgba(234, 220, 194, 0.8);
    cursor: pointer;
    font-size: 0.9rem;
}

/* ── Actions ──────────────────────────────────────────────── */
.sched-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

/* ── Buttons ──────────────────────────────────────────────── */
.sched-btn-primary {
    border: 1px solid #9d7d3b;
    color: #17130a;
    background: linear-gradient(180deg, #d6b66c 0%, var(--gold-color, #c8a45d) 100%);
    box-shadow: 0 12px 28px rgba(122, 94, 39, 0.24);
    font-weight: 700;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-size: 0.9rem;
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}

.sched-btn-primary:hover:not(:disabled) {
    border-color: #8f6e2f;
    color: #111;
    background: linear-gradient(180deg, #e1c47f 0%, #b99245 100%);
    transform: translateY(-2px);
    box-shadow: 0 16px 32px rgba(122, 94, 39, 0.35);
}

.sched-btn-primary:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.sched-btn-danger {
    border: 1px solid rgba(224, 80, 80, 0.35);
    color: #e05050;
    background: rgba(224, 80, 80, 0.08);
    font-weight: 600;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-size: 0.9rem;
    transition:
        transform 0.2s ease,
        background 0.2s ease,
        border-color 0.2s ease;
}

.sched-btn-danger:hover:not(:disabled) {
    border-color: rgba(224, 80, 80, 0.55);
    color: #f06060;
    background: rgba(224, 80, 80, 0.15);
    transform: translateY(-2px);
}

.sched-btn-danger:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.sched-btn-ghost {
    border: 1px solid rgba(234, 220, 194, 0.15);
    color: rgba(234, 220, 194, 0.7);
    background: transparent;
    font-weight: 500;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-size: 0.9rem;
    transition:
        transform 0.2s ease,
        background 0.2s ease,
        border-color 0.2s ease,
        color 0.2s ease;
}

.sched-btn-ghost:hover {
    border-color: rgba(234, 220, 194, 0.25);
    color: rgba(234, 220, 194, 0.9);
    background: rgba(234, 220, 194, 0.06);
    transform: translateY(-2px);
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 575.98px) {
    .sched-modal-body {
        padding: 1rem;
    }

    .sched-actions {
        flex-wrap: wrap;
    }

    .sched-actions .btn {
        flex: 1 1 auto;
        text-align: center;
    }

    .sched-programs-wrap {
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
    }
}
</style>