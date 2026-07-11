<template>
    <div class="fade-in-up">
        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="content-card bg-dark mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase text-warning fw-bold small mb-2">TRMS Concert</p>
                    <h1 class="display-4 fw-bold mb-3 text-champagne">Scan Registration</h1>
                    <p class="lead text-champagne-muted mb-0">
                        Verify audience registrations by scanning the QR code on their ticket or entering the registration number manually.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="bg-dark-card rounded-3 p-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-qr-code-scan display-6 text-warning"></i>
                            <div>
                                <div class="fw-bold text-champagne">Quick Verification</div>
                                <div class="text-champagne-muted small">Camera scan or manual entry</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- ── Left: Input panel ──────────────────────────────────── -->
            <div class="col-lg-5">
                <div class="content-card bg-dark h-100">
                    <!-- Mode tabs -->
                    <ul class="nav nav-pills nav-pills-gold mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: mode === 'camera' }"
                                type="button"
                                role="tab"
                                @click="switchMode('camera')"
                            >
                                <i class="bi bi-camera me-2"></i>Camera
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                :class="{ active: mode === 'manual' }"
                                type="button"
                                role="tab"
                                @click="switchMode('manual')"
                            >
                                <i class="bi bi-keyboard me-2"></i>Manual
                            </button>
                        </li>
                    </ul>

                    <!-- Camera mode -->
                    <div v-if="mode === 'camera'">
                        <div class="scanner-wrapper mb-3 position-relative rounded overflow-hidden bg-dark">
                            <video
                                ref="videoEl"
                                class="w-100 d-block"
                                autoplay
                                muted
                                playsinline
                                aria-label="Camera viewfinder"
                            ></video>
                            <canvas ref="canvasEl" class="d-none"></canvas>

                            <!-- Scan overlay frame -->
                            <div class="scan-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center pointer-events-none">
                                <div class="scan-frame"></div>
                            </div>

                            <!-- Scanning indicator -->
                            <div v-if="cameraActive && !cameraError" class="position-absolute bottom-0 start-0 w-100 text-center pb-2">
                                <span class="badge bg-dark bg-opacity-75 text-white small">
                                    <span class="spinner-grow spinner-grow-sm me-1 text-warning" aria-hidden="true"></span>
                                    Scanning…
                                </span>
                            </div>
                        </div>

                        <div v-if="cameraError" class="alert alert-danger d-flex gap-2 align-items-start">
                            <i class="bi bi-camera-video-off flex-shrink-0 mt-1"></i>
                            <div>{{ cameraError }}</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button
                                v-if="!cameraActive"
                                type="button"
                                class="btn btn-primary"
                                :disabled="scanning"
                                @click="startCamera"
                            >
                                <i class="bi bi-camera me-2"></i>Start Camera
                            </button>
                            <button
                                v-else
                                type="button"
                                class="btn btn-outline-gold"
                                @click="stopCamera"
                            >
                                <i class="bi bi-stop-circle me-2"></i>Stop Camera
                            </button>
                        </div>
                    </div>

                    <!-- Manual mode -->
                    <div v-if="mode === 'manual'">
                        <form @submit.prevent="submitManual">
                            <div class="mb-3">
                                <label for="manualInput" class="form-label fw-semibold text-champagne">
                                    QR Code or Registration Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-dark"><i class="bi bi-search"></i></span>
                                    <input
                                        id="manualInput"
                                        ref="manualInputEl"
                                        v-model.trim="manualValue"
                                        type="text"
                                        class="form-control form-control-dark form-control-lg"
                                        placeholder="e.g. SDG_42_… or 42"
                                        autocomplete="off"
                                        autofocus
                                        @keydown.enter.prevent="submitManual"
                                    />
                                </div>
                                <div class="form-text-dark">
                                    Enter the QR code string from the ticket, or just the numeric registration ID.
                                </div>
                            </div>

                            <div class="d-grid">
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-lg"
                                    :disabled="scanning || !manualValue"
                                >
                                    <span v-if="scanning" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                                    <i v-else class="bi bi-search me-2"></i>
                                    {{ scanning ? 'Looking up…' : 'Look Up' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Right: Result panel ────────────────────────────────── -->
            <div class="col-lg-7">
                <div class="content-card bg-dark h-100">
                    <h2 class="h5 fw-bold mb-4 text-champagne">Result</h2>

                    <!-- Idle state -->
                    <div v-if="state === 'idle'" class="py-5 text-center">
                        <i class="bi bi-qr-code display-1 d-block mb-3 opacity-25 text-champagne-muted"></i>
                        <p class="mb-0 text-champagne-muted">Scan or enter a registration to see the details here.</p>
                    </div>

                    <!-- Loading -->
                    <div v-else-if="state === 'loading'" class="py-5 text-center">
                        <div class="spinner-border text-warning mb-3" role="status"></div>
                        <div class="text-champagne-muted">Looking up registration…</div>
                    </div>

                    <!-- Error -->
                    <div v-else-if="state === 'error'" class="py-5 text-center">
                        <i class="bi bi-x-circle-fill display-1 d-block mb-3 text-danger"></i>
                        <h3 class="h5 fw-bold text-danger mb-2">Not Found</h3>
                        <p class="text-champagne-muted mb-4">{{ errorMessage }}</p>
                        <button type="button" class="btn btn-outline-gold" @click="reset">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Try Again
                        </button>
                    </div>

                    <!-- Success -->
                    <div v-else-if="state === 'found' && result">
                        <!-- Duplicate scan warning -->
                        <div v-if="alreadyAttended" class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1 fs-5 text-warning"></i>
                            <div>
                                <div class="fw-bold text-warning">Already Checked In</div>
                                <div class="small text-champagne-muted">This ticket was scanned at {{ formatDate(result.attended_at) }}.</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3 flex-shrink-0">
                                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-success">{{ alreadyAttended ? 'Registration Valid' : 'Check-In Successful' }}</div>
                                <div class="text-champagne-muted small">{{ alreadyAttended ? 'Duplicate scan detected' : 'Attendance recorded' }}</div>
                            </div>
                        </div>

                        <div class="result-card p-4 rounded-3 border mb-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-person-fill text-warning"></i>
                                        <span class="text-champagne-muted small">Name</span>
                                    </div>
                                    <div class="fw-bold fs-5 text-champagne">{{ result.name }}</div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-envelope-fill text-warning"></i>
                                        <span class="text-champagne-muted small">Email</span>
                                    </div>
                                    <div class="fw-semibold text-champagne text-break">{{ result.email }}</div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-telephone-fill text-warning"></i>
                                        <span class="text-champagne-muted small">Phone</span>
                                    </div>
                                    <div class="fw-semibold text-champagne">{{ result.phone }}</div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-music-note-beamed text-warning"></i>
                                        <span class="text-champagne-muted small">Concert</span>
                                    </div>
                                    <div class="fw-semibold text-champagne">{{ result.concert_title }}</div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-ticket-fill text-warning"></i>
                                        <span class="text-champagne-muted small">Tickets</span>
                                    </div>
                                    <div>
                                        <span class="badge rounded-pill text-bg-warning fs-6">{{ result.ticket_quantity }}</span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-hash text-warning"></i>
                                        <span class="text-champagne-muted small">Registration ID</span>
                                    </div>
                                    <div class="fw-semibold text-champagne">#{{ result.id }}</div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-calendar-check-fill text-warning"></i>
                                        <span class="text-champagne-muted small">Registered</span>
                                    </div>
                                    <div class="fw-semibold text-champagne">{{ formatDate(result.created_at) }}</div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-door-open-fill text-warning"></i>
                                        <span class="text-champagne-muted small">Checked In</span>
                                    </div>
                                    <div v-if="result.attended_at" class="fw-semibold text-success">
                                        {{ formatDate(result.attended_at) }}
                                    </div>
                                    <div v-else class="text-champagne-muted">—</div>
                                </div>

                                <div v-if="result.notes" class="col-12">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-chat-left-text-fill text-warning"></i>
                                        <span class="text-champagne-muted small">Notes</span>
                                    </div>
                                    <div class="fw-semibold text-champagne">{{ result.notes }}</div>
                                </div>

                                <div v-if="result.qr_code" class="col-12">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-qr-code text-warning"></i>
                                        <span class="text-champagne-muted small">QR Code</span>
                                    </div>
                                    <code class="small text-champagne-muted">{{ result.qr_code }}</code>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-gold" @click="reset">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Scan Another
                            </button>
                            <button type="button" class="btn btn-outline-success" @click="downloadTicket(result.id)">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Download Ticket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scan history -->
        <div v-if="history.length" class="content-card bg-dark mt-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 fw-bold mb-0 text-champagne">Scan History</h2>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="clearHistory">
                    <i class="bi bi-trash me-1"></i>Clear
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 table-dark-custom">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Name</th>
                            <th>Concert</th>
                            <th class="text-center">Tickets</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in history" :key="entry.scannedAt">
                            <td class="text-champagne-muted small text-nowrap">{{ formatTime(entry.scannedAt) }}</td>
                            <td class="fw-semibold text-champagne">{{ entry.name }}</td>
                            <td class="text-champagne-muted">{{ entry.concert_title }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill text-bg-warning">{{ entry.ticket_quantity }}</span>
                            </td>
                            <td class="text-center">
                                <span v-if="entry.duplicate" class="badge text-bg-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Duplicate
                                </span>
                                <span v-else class="badge text-bg-success">
                                    <i class="bi bi-check-lg me-1"></i>Checked In
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import jsQR from 'jsqr'
import { useTrmsStore } from '../../stores/api'

export default {
    name: 'ConcertScan',

    setup() {
        return { trmsStore: useTrmsStore() }
    },

    data() {
        return {
            mode: 'camera',           // 'camera' | 'manual'
            state: 'idle',            // 'idle' | 'loading' | 'found' | 'error'
            result: null,
            alreadyAttended: false,
            errorMessage: '',
            manualValue: '',
            scanning: false,

            // Camera
            cameraActive: false,
            cameraError: '',
            stream: null,
            scanLoopId: null,
        }
    },

    created() {
        this._history = []
    },

    computed: {
        history() {
            return this._history || []
        }
    },

    beforeUnmount() {
        this.stopCamera()
    },

    methods: {
        // ── Mode ────────────────────────────────────────────────────────
        switchMode(m) {
            if (m === this.mode) return
            this.stopCamera()
            this.mode = m
            if (m === 'manual') {
                this.$nextTick(() => this.$refs.manualInputEl?.focus())
            }
        },

        reset() {
            this.state = 'idle'
            this.result = null
            this.alreadyAttended = false
            this.errorMessage = ''
            this.manualValue = ''
            if (this.mode === 'camera') {
                this.startCamera()
            }
        },

        // ── Lookup ──────────────────────────────────────────────────────
        async lookup(payload) {
            if (this.scanning) return
            this.scanning = true
            this.state = 'loading'
            this.result = null
            this.alreadyAttended = false
            this.errorMessage = ''

            try {
                const localTime = new Date()
                const localTimestamp = `${localTime.getFullYear()}-${String(localTime.getMonth() + 1).padStart(2, '0')}-${String(localTime.getDate()).padStart(2, '0')} ${String(localTime.getHours()).padStart(2, '0')}:${String(localTime.getMinutes()).padStart(2, '0')}:${String(localTime.getSeconds()).padStart(2, '0')}`

                const response = await this.trmsStore.scanConcertRegistration({
                    ...payload,
                    attended_at: localTimestamp
                })
                this.result = response.data
                this.alreadyAttended = response.already_attended === true
                this.state = 'found'
                this.pushHistory(response.data, this.alreadyAttended)
            } catch (err) {
                this.state = 'error'
                this.errorMessage = err.message || 'Registration not found.'
            } finally {
                this.scanning = false
            }
        },

        pushHistory(record, duplicate = false) {
            this._history = [
                { ...record, scannedAt: new Date().toISOString(), duplicate },
                ...this._history.slice(0, 49)
            ]
            this.$forceUpdate()
        },

        clearHistory() {
            this._history = []
            this.$forceUpdate()
        },

        // ── Manual submit ───────────────────────────────────────────────
        submitManual() {
            if (!this.manualValue || this.scanning) return

            const value = this.manualValue.trim()
            // Numeric-only → treat as registration ID; otherwise treat as qr_code
            const isNumeric = /^\d+$/.test(value)
            const payload = isNumeric ? { reg_number: value } : { qr_code: value }

            this.lookup(payload)
        },

        // ── Camera (jsQR) ───────────────────────────────────────────────
        async startCamera() {
            this.cameraError = ''

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }
                })
                const video = this.$refs.videoEl
                video.srcObject = this.stream
                await video.play()
                this.cameraActive = true
                this.scanLoop()
            } catch (err) {
                if (err.name === 'NotAllowedError') {
                    this.cameraError = 'Camera permission denied. Please allow camera access and try again.'
                } else if (err.name === 'NotFoundError') {
                    this.cameraError = 'No camera found on this device.'
                } else {
                    this.cameraError = 'Unable to open camera: ' + (err.message || err.name)
                }
            }
        },

        stopCamera() {
            cancelAnimationFrame(this.scanLoopId)
            this.scanLoopId = null
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop())
                this.stream = null
            }
            if (this.$refs.videoEl) this.$refs.videoEl.srcObject = null
            this.cameraActive = false
        },

        scanLoop() {
            if (!this.cameraActive || !this.$refs.videoEl || !this.$refs.canvasEl) return

            const video  = this.$refs.videoEl
            const canvas = this.$refs.canvasEl
            const ctx    = canvas.getContext('2d', { willReadFrequently: true })

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width  = video.videoWidth
                canvas.height = video.videoHeight
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'dontInvert'
                })

                if (code && code.data && !this.scanning && this.state !== 'found') {
                    this.stopCamera()
                    this.lookup({ qr_code: code.data })
                    return
                }
            }

            this.scanLoopId = requestAnimationFrame(() => this.scanLoop())
        },

        // ── Helpers ─────────────────────────────────────────────────────
        downloadTicket(id) {
            const apiRoot = import.meta.env.VITE_API_URL || 'http://localhost:8000'
            window.open(`${apiRoot.replace(/\/$/, '')}/api/trms/concert/ticket/${id}`, '_blank', 'noopener,noreferrer')
        },

        formatDate(value) {
            if (!value) return '-'
            return new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }).format(new Date(value))
        },

        formatTime(iso) {
            if (!iso) return ''
            return new Intl.DateTimeFormat('id-ID', { timeStyle: 'short' }).format(new Date(iso))
        }
    }
}
</script>

<style scoped>
/* ── Nav pills ──────────────────────────────────────────────────── */
.nav-pills-gold .nav-link {
    color: rgba(234, 220, 194, 0.6);
    background: transparent;
    border: 1px solid rgba(234, 220, 194, 0.1);
    border-radius: 8px;
    transition:
        background 0.2s,
        color 0.2s,
        border-color 0.2s;
}

.nav-pills-gold .nav-link:hover {
    color: rgba(234, 220, 194, 0.85);
    background: rgba(234, 220, 194, 0.06);
}

.nav-pills-gold .nav-link.active,
.nav-pills-gold .nav-link.active:hover {
    color: #111420;
    background: var(--gold-color, #c8a45d);
    border-color: var(--gold-color, #c8a45d);
    font-weight: 600;
}

/* ── Scanner ────────────────────────────────────────────────────── */
.scanner-wrapper {
    aspect-ratio: 4 / 3;
    max-height: 360px;
    background: #0a0a12;
    border: 1px solid rgba(234, 220, 194, 0.1);
    border-radius: 10px;
}

.scanner-wrapper video {
    object-fit: cover;
    height: 100%;
}

.scan-overlay {
    pointer-events: none;
}

.scan-frame {
    width: 200px;
    height: 200px;
    border: 3px solid rgba(200, 164, 93, 0.85);
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(10, 10, 18, 0.55);
    position: relative;
}

.scan-frame::before,
.scan-frame::after {
    content: '';
    position: absolute;
    width: 24px;
    height: 24px;
    border-color: var(--gold-color, #c8a45d);
    border-style: solid;
}

.scan-frame::before {
    top: -4px;
    left: -4px;
    border-width: 4px 0 0 4px;
    border-radius: 6px 0 0 0;
}

.scan-frame::after {
    bottom: -4px;
    right: -4px;
    border-width: 0 4px 4px 0;
    border-radius: 0 0 6px 0;
}

/* ── Result card ────────────────────────────────────────────────── */
.result-card {
    background:
        linear-gradient(135deg, rgba(200, 164, 93, 0.06), transparent 50%),
        linear-gradient(180deg, #1a1f30 0%, #111420 100%);
    border-color: rgba(234, 220, 194, 0.1) !important;
}

/* ── Input group dark ───────────────────────────────────────────── */
.input-group-text-dark {
    background: rgba(234, 220, 194, 0.06) !important;
    border: 1px solid rgba(234, 220, 194, 0.15) !important;
    color: rgba(234, 220, 194, 0.5) !important;
}

/* ── Dark form controls ─────────────────────────────────────────── */
.form-control-dark {
    background: rgba(234, 220, 194, 0.06) !important;
    border: 1px solid rgba(234, 220, 194, 0.15) !important;
    color: rgba(234, 220, 194, 0.88) !important;
}

.form-control-dark:focus {
    border-color: rgba(200, 164, 93, 0.4) !important;
    box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.1) !important;
    background: rgba(234, 220, 194, 0.08) !important;
}

.form-control-dark::placeholder {
    color: rgba(234, 220, 194, 0.35);
}

.form-text-dark {
    color: rgba(234, 220, 194, 0.5);
    font-size: 0.8rem;
    margin-top: 0.3rem;
}

/* ── Dark table ─────────────────────────────────────────────────── */
:deep(.table-dark-custom) {
    color: rgba(234, 220, 194, 0.75);
}

:deep(.table-dark-custom thead th) {
    border-bottom: 1px solid rgba(234, 220, 194, 0.1);
    color: var(--gold-color, #c8a45d);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

:deep(.table-dark-custom tbody tr) {
    border-color: rgba(234, 220, 194, 0.06);
    transition: background 0.15s;
}

:deep(.table-dark-custom tbody tr:hover) {
    background: rgba(200, 164, 93, 0.06);
}

:deep(.table-dark-custom td) {
    border-color: rgba(234, 220, 194, 0.06);
}
</style>
