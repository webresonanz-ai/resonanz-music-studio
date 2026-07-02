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
                                    <label for="scheduleDescription" class="form-label">Description</label>
                                    <textarea
                                        id="scheduleDescription"
                                        v-model.trim="form.description"
                                        class="form-control"
                                        rows="3"
                                    ></textarea>
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

const emptyForm = () => ({
    title: '',
    type: 'lesson',
    date: '',
    start_time: '09:00',
    end_time: '10:00',
    description: '',
    program_ids: ['trms']
})

export default {
    name: 'ScheduleFormModal',
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
            ]
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
            this.show()
        },
        openEdit(schedule) {
            this.editingSchedule = schedule
            this.form = { 
                ...schedule,
                program_ids: schedule.program_ids ? [...schedule.program_ids] : ['trms']
            }
            this.show()
        },
        openDay(dateKey, defaultProgram = 'trms') {
            this.editingSchedule = null
            this.form = emptyForm()
            this.form.program_ids = [defaultProgram]
            this.form.date = dateKey
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
</style>
