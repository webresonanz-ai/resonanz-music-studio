<template>
    <Teleport to="body">
        <div class="modal fade" id="scheduleModal" tabindex="-1" ref="modalEl">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingSchedule ? 'Edit Schedule' : 'Add Schedule' }}</h5>
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
                        <form @submit.prevent="handleSubmit">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="scheduleTitle" class="form-label">Title</label>
                                    <input
                                        id="scheduleTitle"
                                        v-model.trim="form.title"
                                        class="form-control"
                                        type="text"
                                        required
                                    >
                                </div>
                                <div class="col-md-4">
                                    <label for="scheduleType" class="form-label">Type</label>
                                    <select
                                        id="scheduleType"
                                        v-model="form.type"
                                        class="form-select"
                                        required
                                    >
                                        <option value="lesson">Lesson</option>
                                        <option value="practice">Practice</option>
                                        <option value="concert">Concert</option>
                                        <option value="exam">Exam</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="scheduleDate" class="form-label">Date</label>
                                    <input
                                        id="scheduleDate"
                                        v-model="form.date"
                                        class="form-control"
                                        type="date"
                                        required
                                    >
                                </div>
                                <div class="col-md-3">
                                    <label for="scheduleStartTime" class="form-label">Start Time</label>
                                    <input
                                        id="scheduleStartTime"
                                        v-model="form.start_time"
                                        class="form-control"
                                        type="time"
                                        required
                                    >
                                </div>
                                <div class="col-md-3">
                                    <label for="scheduleEndTime" class="form-label">End Time</label>
                                    <input
                                        id="scheduleEndTime"
                                        v-model="form.end_time"
                                        class="form-control"
                                        type="time"
                                        required
                                    >
                                </div>
                                <div class="col-12">
                                    <label for="scheduleVenue" class="form-label">Venue</label>
                                    <input
                                        id="scheduleVenue"
                                        v-model.trim="form.venue"
                                        class="form-control"
                                        type="text"
                                        maxlength="150"
                                        placeholder="e.g. Aula Simfonia Jakarta"
                                    >
                                </div>
                                <div class="col-12">
                                    <label for="scheduleDescription" class="form-label">Description</label>
                                    <textarea
                                        id="scheduleDescription"
                                        v-model.trim="form.description"
                                        class="form-control"
                                        rows="3"
                                    ></textarea>
                                </div>
                                <div class="col-12" v-if="form.type === 'concert'">
                                    <label for="scheduleConcertCode" class="form-label">Concert Code</label>
                                    <input
                                        id="scheduleConcertCode"
                                        v-model.trim="form.concert_code"
                                        class="form-control text-uppercase"
                                        type="text"
                                        maxlength="50"
                                        placeholder="e.g. SDG"
                                    >
                                    <div class="form-text">Used as the QR code prefix for audience tickets.</div>
                                </div>
                                <div class="col-12" v-if="form.type === 'concert'">
                                    <label for="scheduleBannerUrl" class="form-label">Concert Banner URL</label>
                                    <!-- Upload tab or URL tab -->
                                    <ul class="nav nav-tabs nav-tabs-sm mb-2" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button
                                                type="button"
                                                class="nav-link py-1 px-3"
                                                :class="{ active: bannerTab === 'upload' }"
                                                @click="bannerTab = 'upload'"
                                            >
                                                <i class="bi bi-upload me-1"></i> Upload
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button
                                                type="button"
                                                class="nav-link py-1 px-3"
                                                :class="{ active: bannerTab === 'url' }"
                                                @click="bannerTab = 'url'"
                                            >
                                                <i class="bi bi-link-45deg me-1"></i> URL
                                            </button>
                                        </li>
                                    </ul>

                                    <!-- Upload file input -->
                                    <div v-if="bannerTab === 'upload'">
                                        <div
                                            class="banner-dropzone"
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
                                            <div v-if="!bannerUploading && !form.banner_url" class="text-center text-muted py-2">
                                                <i class="bi bi-image fs-2 d-block mb-1"></i>
                                                <span class="small">Click or drag &amp; drop an image here</span>
                                                <div class="small opacity-75">JPEG, PNG, WebP — max 3 MB</div>
                                            </div>
                                            <div v-if="bannerUploading" class="text-center py-2">
                                                <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                                                <span class="small">Uploading…</span>
                                            </div>
                                            <div v-if="!bannerUploading && form.banner_url" class="banner-preview-wrap">
                                                <img :src="form.banner_url" alt="Banner preview" class="banner-preview-img">
                                                <button
                                                    type="button"
                                                    class="banner-remove-btn"
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

                                    <!-- URL text input -->
                                    <div v-if="bannerTab === 'url'">
                                        <input
                                            id="scheduleBannerUrl"
                                            v-model.trim="form.banner_url"
                                            class="form-control"
                                            type="url"
                                            placeholder="https://example.com/banner.jpg"
                                        >
                                        <div v-if="form.banner_url" class="mt-2">
                                            <img
                                                :src="form.banner_url"
                                                alt="Banner preview"
                                                class="banner-url-preview"
                                                @error="bannerUrlBroken = true"
                                                @load="bannerUrlBroken = false"
                                            >
                                            <div v-if="bannerUrlBroken" class="text-warning small mt-1">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Could not load image from this URL.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-text">Optional banner image shown on the homepage slideshow.</div>
                                </div>
                                <div class="col-md-6" v-if="form.type === 'concert'">
                                    <label for="scheduleAudienceCapacity" class="form-label">Audience Capacity</label>
                                    <input
                                        id="scheduleAudienceCapacity"
                                        v-model.number="form.audience_capacity"
                                        class="form-control"
                                        type="number"
                                        min="1"
                                        placeholder="e.g. 500"
                                    >
                                    <div class="form-text">Leave empty for unlimited registrations.</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center" v-if="form.type === 'concert'">
                                    <div class="form-check form-switch">
                                        <input
                                            id="scheduleIsOpenRegister"
                                            v-model="form.is_open_register"
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            :disabled="!hasConcertCode"
                                        >
                                        <label for="scheduleIsOpenRegister" class="form-check-label fw-semibold">
                                            Open for Registration
                                        </label>
                                        <div class="form-text mt-1">
                                            {{ hasConcertCode ? 'When enabled, the "Register Now" button appears publicly.' : 'Fill Concert Code first to open registration.' }}
                                        </div>
                                    </div>
                                </div>
                                <!-- Seat Assignment toggle -->
                                <div class="col-12" v-if="form.type === 'concert'">
                                    <div class="p-3 rounded border border-secondary border-opacity-25 bg-light bg-opacity-10">
                                        <div class="form-check form-switch mb-3">
                                            <input
                                                id="scheduleIsSeatAssign"
                                                v-model="form.is_seat_assign"
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                            >
                                            <label for="scheduleIsSeatAssign" class="form-check-label fw-semibold">
                                                <i class="bi bi-grid-3x3-gap me-1"></i> Seat Assignment
                                            </label>
                                            <div class="form-text mt-1">
                                                When enabled, guests choose a seat from a visual layout during registration.
                                            </div>
                                        </div>
                                        <div v-if="form.is_seat_assign">
                                            <label class="form-label fw-semibold small mb-2">Seating Layout</label>
                                            <ConcertLayoutPicker v-model="form.seat_layout_id" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block">Programs / Collaborating Groups</label>
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
                            </div>

                            <div class="d-flex gap-3 mt-4">
                                <button class="btn btn-primary" type="submit" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                                    <i v-else class="bi bi-check-circle me-2"></i>
                                    {{ loading ? 'Saving...' : (editingSchedule ? 'Update Schedule' : 'Add Schedule') }}
                                </button>
                                <button v-if="editingSchedule" class="btn btn-outline-danger" type="button" @click="handleDelete" :disabled="loading">
                                    <i class="bi bi-trash me-2"></i> Delete
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
</template>

<script>
import { Modal } from 'bootstrap'
import { useTrmsStore } from '../../stores/api'
import ConcertLayoutPicker from './ConcertLayoutPicker.vue'

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
        errorMessage: String
    },
    emits: ['submit', 'delete'],
    data() {
        return {
            form: emptyForm(),
            editingSchedule: null,
            modalInstance: null,
            availablePrograms: [
                { id: 'trms', name: 'TRMS' },
                { id: 'bms', name: 'BMS' },
                { id: 'jco', name: 'JCO' },
                { id: 'trcc', name: 'TRCC' }
            ],
            bannerTab: 'upload',
            bannerUploading: false,
            bannerUploadError: '',
            bannerUrlBroken: false,
            isDragging: false
        }
    },
    computed: {
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
                // API returns these as "0"/"1" strings from MySQL — coerce to real booleans
                is_open_register: !!+schedule.is_open_register,
                is_seat_assign:   !!+schedule.is_seat_assign,
                // Ensure seat_layout_id is null when empty string
                seat_layout_id: schedule.seat_layout_id || null,
            }
            // If there's already a banner URL, default to upload tab so the preview shows
            this.bannerTab = schedule.banner_url ? 'upload' : 'upload'
            this.bannerUploadError = ''
            this.bannerUrlBroken = false
            this.show()
        },
        openDay(dateKey, defaultProgram = 'trms') {
            this.editingSchedule = null
            this.form = emptyForm()
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
                // Reset file input so the same file can be re-selected if needed
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
.modal-content {
    background: var(--surface-color);
}

.modal-header {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.16), rgba(200, 164, 93, 0.08));
    border-bottom: 1px solid var(--hairline-color);
}

/* Banner upload */
.nav-tabs-sm .nav-link {
    font-size: 0.82rem;
}

.banner-dropzone {
    border: 2px dashed rgba(127, 36, 50, 0.3);
    border-radius: 8px;
    min-height: 110px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.2s ease, background 0.2s ease;
    background: rgba(127, 36, 50, 0.03);
    overflow: hidden;
    position: relative;
}

.banner-dropzone:hover,
.banner-dropzone.is-dragging {
    border-color: var(--accent-color);
    background: rgba(127, 36, 50, 0.07);
}

.banner-preview-wrap {
    width: 100%;
    position: relative;
}

.banner-preview-img {
    display: block;
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border-radius: 6px;
}

.banner-remove-btn {
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
    transition: background 0.2s;
}

.banner-remove-btn:hover {
    background: rgba(200, 0, 0, 0.75);
}

.banner-url-preview {
    display: block;
    width: 100%;
    max-height: 160px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--hairline-color);
}
</style>
