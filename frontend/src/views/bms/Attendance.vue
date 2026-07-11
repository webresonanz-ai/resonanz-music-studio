<template>
  <div class="fade-in-up">
    <!-- ══ PAGE HEADER ══════════════════════════════════════════════ -->
    <div class="content-card bg-dark mb-4">
      <div class="row g-4 align-items-center">
        <div class="col-12 col-lg-7">
          <p class="attendance-eyebrow mb-2">Batavia Madrigal Singers</p>
          <h1 class="display-5 fw-bold mb-3 text-champagne">Rehearsal Attendance</h1>
          <p class="lead text-champagne-muted mb-0">
            Select an upcoming concert, assign participating singers, and track their rehearsal
            attendance until performance day.
          </p>
        </div>
        <div class="col-12 col-lg-5">
          <div class="stats-panel rounded p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="stats-icon-wrap">
                <i class="bi bi-music-note-list text-warning fs-4"></i>
              </div>
              <div>
                <div class="fw-bold">Concert Cycle</div>
                <div class="text-white-50 small text-truncate" style="max-width: 220px">
                  {{ selectedConcert ? selectedConcert.title : "No concert selected" }}
                </div>
              </div>
            </div>
            <div class="d-flex gap-3 flex-wrap">
              <div class="mini-stat">
                <span class="mini-stat-val">{{ roster.length }}</span>
                <span class="mini-stat-lbl">Singers</span>
              </div>
              <div class="mini-stat">
                <span class="mini-stat-val">{{ rehearsals.length }}</span>
                <span class="mini-stat-lbl">Rehearsals</span>
              </div>
              <div class="mini-stat">
                <span class="mini-stat-val">{{ attendanceRate }}%</span>
                <span class="mini-stat-lbl">Present</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ CONCERT SELECTOR ═════════════════════════════════════════ -->
    <div class="content-card bg-dark mb-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <h2 class="h5 fw-bold mb-0">
          <i class="bi bi-calendar-event me-2 text-primary"></i>Select Concert
        </h2>
        <span class="text-muted small"
          >{{ concerts.length }} upcoming concert{{ concerts.length === 1 ? "" : "s" }}</span
        >
      </div>

      <div v-if="loadingConcerts" class="text-center py-4">
        <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
        <span class="text-muted ms-2">Loading concerts…</span>
      </div>

      <div v-else-if="concerts.length === 0" class="empty-hint py-4 text-center text-muted">
        <i class="bi bi-calendar-x display-4 d-block mb-2"></i>
        <p class="mb-0">
          No upcoming concert schedules found. Add a concert in
          <router-link to="/bms/events">Events</router-link> first.
        </p>
      </div>

      <div v-else class="concert-picker row g-3">
        <div v-for="concert in concerts" :key="concert.id" class="col-12 col-md-6 col-xl-4">
          <button
            type="button"
            class="concert-pick-card w-100 text-start"
            :class="{ active: selectedConcertId === concert.id }"
            @click="selectConcert(concert.id)"
          >
            <div class="d-flex gap-3 align-items-start">
              <div class="calendar-widget-sheet flex-shrink-0">
                <div class="calendar-widget-header text-uppercase">
                  {{ monthAbbr(concert.date) }}
                </div>
                <div class="calendar-widget-body">
                  <div class="calendar-widget-day">{{ dayNum(concert.date) }}</div>
                </div>
              </div>
              <div class="min-width-0 flex-grow-1">
                <span class="badge bg-warning text-dark mb-1">Concert</span>
                <h3 class="h6 fw-bold mb-1 text-truncate">{{ concert.title }}</h3>
                <p class="text-muted small mb-0">
                  <i class="bi bi-clock me-1"></i>{{ formatTime(concert.start_time) }} –
                  {{ formatTime(concert.end_time) }}
                </p>
              </div>
            </div>
          </button>
        </div>
      </div>
    </div>

    <template v-if="selectedConcertId">
      <!-- ══ LOADING DETAIL ═════════════════════════════════════════ -->
      <div v-if="loadingDetail" class="content-card bg-dark text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="text-muted mt-3">Loading roster &amp; rehearsals…</div>
      </div>

      <template v-else>
        <!-- ══ ROSTER ═════════════════════════════════════════════ -->
        <div class="content-card bg-dark mb-4">
          <!-- Roster Header -->
          <div
            class="roster-header-bar d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4"
          >
            <div>
              <h2 class="h5 fw-bold mb-1 d-flex align-items-center gap-2">
                <span class="roster-section-icon">
                  <i class="bi bi-people-fill"></i>
                </span>
                Concert Singers
              </h2>
              <p class="text-muted small mb-0">
                Members participating in
                <span class="fw-semibold text-body">{{ selectedConcert?.title }}</span>
              </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Sort controls -->
              <div v-if="roster.length > 0" class="sort-group d-flex gap-1">
                <button
                  type="button"
                  class="sort-btn"
                  :class="{ active: sortBy === 'name' }"
                  @click="sortBy = 'name'"
                  title="Sort by name"
                >
                  <i class="bi bi-sort-alpha-down me-1"></i>
                  <span class="d-none d-sm-inline">Name</span>
                </button>
                <button
                  type="button"
                  class="sort-btn"
                  :class="{ active: sortBy === 'role' }"
                  @click="sortBy = 'role'"
                  title="Sort by role"
                >
                  <i class="bi bi-tags me-1"></i>
                  <span class="d-none d-sm-inline">Role</span>
                </button>
                <button
                  type="button"
                  class="sort-btn"
                  :class="{ active: sortBy === 'rate' }"
                  @click="sortBy = 'rate'"
                  title="Sort by attendance rate"
                >
                  <i class="bi bi-bar-chart me-1"></i>
                  <span class="d-none d-sm-inline">Rate</span>
                </button>
              </div>
              <!-- Add singers button -->
              <button
                v-if="canManage"
                type="button"
                class="btn btn-primary btn-sm d-flex align-items-center gap-2"
                @click="showAddSingers = true"
              >
                <i class="bi bi-person-plus-fill"></i>
                <span class="d-none d-sm-inline">Add Singers</span>
                <span class="d-sm-none">Add</span>
              </button>
              <p v-if="!canManage" class="text-muted small mb-0 d-flex align-items-center gap-1">
                <i class="bi bi-lock-fill"></i>
                <span class="d-none d-md-inline">Admin, manager, or singers manager access required</span>
              </p>
            </div>
          </div>

          <!-- Count pill -->
          <div v-if="roster.length > 0" class="roster-count-bar mb-3">
            <span class="roster-count-pill">
              <i class="bi bi-mic-fill me-1"></i>
              {{ roster.length }} Singer{{ roster.length === 1 ? "" : "s" }}
            </span>
            <div class="roster-count-divider"></div>
            <span class="text-muted small">
              Avg. attendance:
              <span
                class="fw-bold"
                :class="
                  attendanceRate >= 80
                    ? 'text-success'
                    : attendanceRate >= 50
                      ? 'text-warning'
                      : 'text-danger'
                "
              >
                {{ attendanceRate }}%
              </span>
            </span>
          </div>

          <!-- Empty state -->
          <div v-if="roster.length === 0" class="roster-empty-state">
            <div class="roster-empty-icon">
              <i class="bi bi-person-dash"></i>
            </div>
            <h4 class="roster-empty-title">No singers assigned yet</h4>
            <p class="roster-empty-sub">Add members from the BMS roster to get started.</p>
            <button
              v-if="canManage"
              type="button"
              class="btn btn-primary btn-sm"
              @click="showAddSingers = true"
            >
              <i class="bi bi-person-plus-fill me-2"></i>Add First Singer
            </button>
          </div>

          <!-- Singer cards grid -->
          <div v-else class="singer-cards-grid">
            <div v-for="singer in sortedRoster" :key="singer.member_id" class="singer-card">
              <!-- Rate badge on top-right -->
              <div class="singer-card-rate" v-if="singerRate(singer.member_id) > 0">
                <span class="singer-rate-badge" :class="rateClass(singer.member_id)">
                  {{ singerRate(singer.member_id) }}%
                </span>
              </div>

              <!-- Avatar -->
              <div class="singer-card-avatar-wrap">
                <img
                  :src="singer.avatar || defaultAvatar"
                  :alt="singer.name"
                  class="singer-card-avatar"
                  @error="onImgError"
                />
                <!-- Presence indicator dot based on rate -->
                <span
                  v-if="singerRate(singer.member_id) > 0"
                  class="singer-status-dot"
                  :class="rateClass(singer.member_id)"
                ></span>
              </div>

              <!-- Info -->
              <div class="singer-card-info">
                <span class="singer-card-name">{{ singer.nickname }}</span>
                <span class="singer-card-role">{{ singer.role || singer.section || "—" }}</span>
              </div>

              <!-- Remove button -->
              <button
                v-if="canManage"
                type="button"
                class="singer-card-remove"
                title="Remove from concert"
                @click="removeSinger(singer.member_id)"
                aria-label="Remove singer"
              >
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- ══ REHEARSAL MANAGEMENT ══════════════════════════════ -->
        <div class="content-card bg-dark mb-4">
          <div
            class="rehearsal-mgmt-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4"
          >
            <div>
              <h2 class="h5 fw-bold mb-1 d-flex align-items-center gap-2">
                <span class="section-icon-wrap">
                  <i class="bi bi-list-check"></i>
                </span>
                Rehearsal Sessions
              </h2>
              <p class="text-muted small mb-0">
                Link practice schedules to track attendance for
                <span class="fw-semibold text-body">{{ selectedConcert?.title }}</span>
              </p>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="rehearsal-count-pill">
                <i class="bi bi-collection me-1"></i>
                {{ rehearsals.length }} Rehearsal{{ rehearsals.length === 1 ? "" : "s" }}
              </span>
              <button
                v-if="canManage"
                type="button"
                class="btn btn-sm btn-outline-primary"
                @click="showManageRehearsals = true"
              >
                <i class="bi bi-pencil me-1"></i>
                <span class="d-none d-sm-inline">Manage</span>
              </button>
            </div>
          </div>

          <!-- Rehearsal date pills -->
          <div v-if="rehearsals.length === 0" class="rehearsal-empty-hint">
            <i class="bi bi-calendar2-plus me-2 text-muted"></i>
            <span class="text-muted small">
              No rehearsals linked yet.
              <button
                v-if="canManage"
                type="button"
                class="btn btn-link btn-sm p-0 align-baseline"
                @click="showManageRehearsals = true"
              >
                Link rehearsals
              </button>
              <router-link v-else to="/bms/events" class="btn btn-link btn-sm p-0 align-baseline"
                >Add practice sessions in Events</router-link
              >
              first.
            </span>
          </div>

          <div v-else class="rehearsal-date-strip">
            <button
              v-for="reh in rehearsals"
              :key="reh.id"
              type="button"
              class="reh-date-pill"
              :class="{ active: selectedRehearsalId === reh.id }"
              @click="selectRehearsal(reh)"
              :title="reh.title"
            >
              <span class="reh-pill-month">{{ monthAbbr(reh.date) }}</span>
              <span class="reh-pill-day">{{ dayNum(reh.date) }}</span>
              <span class="reh-pill-time">{{ formatTime(reh.start_time) }}</span>
              <!-- Attendance dot indicator -->
              <span
                class="reh-pill-dot"
                :class="rehearsalDotClass(reh.id)"
                :title="rehearsalDotTitle(reh.id)"
              ></span>
            </button>
          </div>
        </div>

        <!-- ══ TAKE ATTENDANCE ════════════════════════════════════ -->
        <div class="content-card bg-dark">
          <div v-if="!selectedRehearsalId" class="attendance-prompt text-center py-5">
            <div class="attendance-prompt-icon">
              <i class="bi bi-hand-index-thumb"></i>
            </div>
            <h4 class="fw-bold mb-2 mt-3">Select a Rehearsal Date</h4>
            <p class="text-muted small mb-0">Pick a date above to start taking attendance.</p>
          </div>

          <template v-else>
            <!-- Header -->
            <div
              class="attend-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4"
            >
              <div>
                <h2 class="h5 fw-bold mb-1 d-flex align-items-center gap-2">
                  <span class="section-icon-wrap section-icon--accent">
                    <i class="bi bi-check2-square"></i>
                  </span>
                  Take Attendance
                </h2>
                <p class="text-muted small mb-0">
                  <i class="bi bi-calendar3 me-1"></i>
                  {{ formatDate(selectedRehearsal?.date) }}
                  <span class="mx-1">·</span>
                  <i class="bi bi-clock me-1"></i>
                  {{ formatTime(selectedRehearsal?.start_time) }}
                </p>
              </div>

              <div class="d-flex align-items-center gap-2 flex-wrap" v-if="canManage">
                <!-- Quick stats -->
                <div class="attend-quick-stats">
                  <span class="qs-present">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    {{ presentCount }}
                  </span>
                  <span class="qs-divider">/</span>
                  <span class="qs-total">{{ roster.length }}</span>
                </div>

                <!-- Select all toggle -->
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  @click="toggleSelectAll"
                  :disabled="savingBulk"
                >
                  <i class="bi me-1" :class="allPresent ? 'bi-square' : 'bi-check-all'"></i>
                  {{ allPresent ? "Clear All" : "Mark All Present" }}
                </button>

                <!-- Save button -->
                <button
                  type="button"
                  class="btn btn-sm btn-primary"
                  @click="saveBulkAttendance"
                  :disabled="savingBulk || !attendanceDirty"
                >
                  <span v-if="savingBulk" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-floppy me-1"></i>
                  Save Attendance
                </button>
              </div>
            </div>

            <!-- no roster -->
            <div v-if="roster.length === 0" class="attendance-prompt text-center py-4 text-muted">
              <p class="mb-0">Add singers to the concert roster first.</p>
            </div>

            <!-- Singer checklist -->
            <div v-else>
              <!-- Group by role/section -->
              <div v-for="group in singerGroups" :key="group.label" class="singer-group mb-4">
                <div class="singer-group-label">
                  <span>{{ group.label }}</span>
                  <span class="singer-group-count">{{ group.singers.length }}</span>
                </div>

                <div class="attendance-checklist">
                  <label
                    v-for="singer in group.singers"
                    :key="singer.member_id"
                    class="checklist-row"
                    :class="{ 'is-present': pendingPresent.includes(singer.member_id) }"
                  >
                    <input
                      type="checkbox"
                      class="checklist-cb"
                      :value="singer.member_id"
                      v-model="pendingPresent"
                      :disabled="!canManage || savingBulk"
                      @change="attendanceDirty = true"
                    />
                    <img
                      :src="singer.avatar || defaultAvatar"
                      :alt="singer.name"
                      class="checklist-avatar"
                      @error="onImgError"
                    />
                    <div class="checklist-info min-width-0 flex-grow-1">
                      <span class="checklist-name">{{ singer.nickname }}</span>
                      <span class="checklist-role">{{ singer.stage_name || "—" }}</span>
                    </div>
                    <span
                      class="checklist-status-badge"
                      :class="
                        pendingPresent.includes(singer.member_id)
                          ? 'status-present'
                          : 'status-absent'
                      "
                    >
                      <i
                        class="bi"
                        :class="
                          pendingPresent.includes(singer.member_id) ? 'bi-check-lg' : 'bi-x-lg'
                        "
                      ></i>
                      {{ pendingPresent.includes(singer.member_id) ? "Present" : "Absent" }}
                    </span>
                  </label>
                </div>
              </div>

              <!-- Floating save bar on mobile when dirty -->
              <transition name="slide-up">
                <div v-if="attendanceDirty && canManage" class="floating-save-bar">
                  <div class="d-flex align-items-center gap-3">
                    <span class="text-white-50 small">
                      <i class="bi bi-circle-fill text-warning me-1" style="font-size: 8px"></i>
                      Unsaved changes
                    </span>
                    <button
                      type="button"
                      class="btn btn-primary btn-sm ms-auto"
                      :disabled="savingBulk"
                      @click="saveBulkAttendance"
                    >
                      <span v-if="savingBulk" class="spinner-border spinner-border-sm me-1"></span>
                      <i v-else class="bi bi-floppy me-1"></i>
                      Save
                    </button>
                  </div>
                </div>
              </transition>
            </div>
          </template>
        </div>
      </template>
    </template>

    <!-- ══ MANAGE REHEARSALS MODAL ══════════════════════════════════ -->
    <Teleport to="body">
      <transition name="modal">
        <div
          v-if="showManageRehearsals"
          class="modal-overlay"
          @click.self="showManageRehearsals = false"
        >
          <div
            class="modal-sheet modal-sheet--lg"
            role="dialog"
            aria-modal="true"
            aria-label="Manage rehearsals"
          >
            <div class="modal-header-row">
              <div>
                <h5 class="modal-sheet-title mb-0">Manage Rehearsal Sessions</h5>
                <p class="modal-sheet-sub mb-0">
                  Link practice schedules to <strong>{{ selectedConcert?.title }}</strong>
                </p>
              </div>
              <button
                class="modal-close-btn"
                @click="showManageRehearsals = false"
                aria-label="Close"
              >
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="modal-search px-4 pt-3">
              <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input
                  v-model="rehearsalSearch"
                  type="text"
                  class="search-input"
                  placeholder="Search practice sessions…"
                />
              </div>
            </div>

            <div class="modal-body-scroll px-4 pb-2">
              <div v-if="availablePractices.length === 0" class="text-center text-muted py-5">
                <i class="bi bi-calendar2-minus display-4 d-block mb-2 opacity-50"></i>
                <p class="mb-0">No practice sessions found before this concert date.</p>
                <router-link to="/bms/events" class="btn btn-link btn-sm mt-1"
                  >Add them in Events</router-link
                >
              </div>
              <div v-else class="add-list">
                <div class="rehearsal-select-info mb-2 px-1">
                  <span class="text-muted small">
                    {{ pendingRehearsalIds.length }} of {{ availablePractices.length }} selected
                  </span>
                  <button
                    type="button"
                    class="btn btn-link btn-sm p-0"
                    @click="toggleAllRehearsals"
                  >
                    {{
                      pendingRehearsalIds.length === availablePractices.length
                        ? "Deselect all"
                        : "Select all"
                    }}
                  </button>
                </div>
                <label
                  v-for="practice in filteredPractices"
                  :key="practice.id"
                  class="add-row rehearsal-add-row"
                  :class="{ selected: pendingRehearsalIds.includes(practice.id) }"
                >
                  <input
                    type="checkbox"
                    class="form-check-input"
                    :value="practice.id"
                    v-model="pendingRehearsalIds"
                  />
                  <div class="reh-mini-cal flex-shrink-0">
                    <span class="reh-mini-month">{{ monthAbbr(practice.date) }}</span>
                    <span class="reh-mini-day">{{ dayNum(practice.date) }}</span>
                  </div>
                  <div class="min-width-0 flex-grow-1">
                    <span class="add-name">{{ practice.title }}</span>
                    <span class="add-meta">
                      <i class="bi bi-clock me-1"></i>
                      {{ formatTime(practice.start_time) }} – {{ formatTime(practice.end_time) }}
                    </span>
                  </div>
                </label>
              </div>
            </div>

            <div class="modal-footer-row">
              <button class="btn btn-secondary" @click="showManageRehearsals = false">
                Cancel
              </button>
              <button
                class="btn btn-primary"
                :disabled="savingRehearsals"
                @click="confirmManageRehearsals"
              >
                <span v-if="savingRehearsals" class="spinner-border spinner-border-sm me-1"></span>
                Save Rehearsal List
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ══ ADD SINGERS MODAL ════════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="showAddSingers" class="modal-overlay" @click.self="showAddSingers = false">
          <div
            class="modal-sheet modal-sheet--lg"
            role="dialog"
            aria-modal="true"
            aria-label="Add singers"
          >
            <div class="modal-header-row">
              <div>
                <h5 class="modal-sheet-title mb-0">Add Singers to Concert</h5>
                <p class="modal-sheet-sub mb-0">
                  Select active members to join {{ selectedConcert?.title }}
                </p>
              </div>
              <button class="modal-close-btn" @click="showAddSingers = false" aria-label="Close">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="modal-search px-4 pt-3">
              <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input
                  v-model="addSearch"
                  type="text"
                  class="search-input"
                  placeholder="Search by name, role, section…"
                />
              </div>
            </div>

            <div class="modal-body-scroll px-4 pb-2">
              <div v-if="availableToAdd.length === 0" class="text-center text-muted py-4">
                All active members are already on this concert roster.
              </div>
              <div v-else class="add-list">
                <label
                  v-for="member in filteredAvailable"
                  :key="member.id"
                  class="add-row"
                  :class="{ selected: pendingAdds.includes(member.id) }"
                >
                  <input
                    type="checkbox"
                    class="form-check-input"
                    :value="member.id"
                    v-model="pendingAdds"
                  />
                  <img
                    :src="member.avatar || defaultAvatar"
                    :alt="member.name"
                    class="add-avatar"
                    @error="onImgError"
                  />
                  <div class="min-width-0 flex-grow-1">
                    <span class="add-name">{{ member.name }}</span>
                    <span class="add-meta">{{ member.role || member.section || "–" }}</span>
                  </div>
                </label>
              </div>
            </div>

            <div class="modal-footer-row">
              <button class="btn btn-secondary" @click="showAddSingers = false">Cancel</button>
              <button
                class="btn btn-primary"
                :disabled="pendingAdds.length === 0 || addingSingers"
                @click="confirmAddSingers"
              >
                <span v-if="addingSingers" class="spinner-border spinner-border-sm me-1"></span>
                Add {{ pendingAdds.length || "" }} Singer{{ pendingAdds.length === 1 ? "" : "s" }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Toast -->
    <div v-if="toast" class="attendance-toast" :class="'toast-' + toast.type">
      <i
        class="bi"
        :class="toast.type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle'"
      ></i>
      {{ toast.message }}
    </div>
  </div>
</template>

<script>
import { mapActions } from "pinia";
import { useBmsStore } from "../../stores/api";
import { useAuthStore } from "../../stores/auth";

const DEFAULT_AVATAR =
  "https://voca-land.sgp1.cdn.digitaloceanspaces.com/0/1757684222527/9465e2e8.jpg";

export default {
  name: "Attendance",

  data() {
    return {
      defaultAvatar: DEFAULT_AVATAR,
      loadingConcerts: false,
      loadingDetail: false,
      concerts: [],
      selectedConcertId: null,
      detail: null,
      // Roster modal
      showAddSingers: false,
      addSearch: "",
      pendingAdds: [],
      addingSingers: false,
      // Rehearsal management modal
      showManageRehearsals: false,
      rehearsalSearch: "",
      pendingRehearsalIds: [],
      savingRehearsals: false,
      // Attendance taking
      selectedRehearsalId: null,
      selectedRehearsal: null,
      pendingPresent: [], // member_ids checked as present
      attendanceDirty: false,
      savingBulk: false,
      // Sort
      sortBy: "name",
      // Misc
      savingCell: null,
      toast: null,
      toastTimer: null,
      statusLegend: [
        { status: "present", label: "Present" },
        { status: "late", label: "Late" },
        { status: "absent", label: "Absent" },
        { status: "excused", label: "Excused" },
      ],
    };
  },

  computed: {
    canManage() {
      const role = useAuthStore().user?.role?.toLowerCase();
      return role === "admin" || role === "manager" || role === "singers_manager";
    },
    selectedConcert() {
      return (
        this.detail?.concert || this.concerts.find((c) => c.id === this.selectedConcertId) || null
      );
    },
    roster() {
      return this.detail?.roster || [];
    },
    sortedRoster() {
      const arr = [...this.roster];
      if (this.sortBy === "name") {
        arr.sort((a, b) => (a.name || "").localeCompare(b.name || ""));
      } else if (this.sortBy === "role") {
        arr.sort((a, b) => (a.role || a.section || "").localeCompare(b.role || b.section || ""));
      } else if (this.sortBy === "rate") {
        arr.sort((a, b) => this.singerRate(b.member_id) - this.singerRate(a.member_id));
      }
      return arr;
    },
    rehearsals() {
      return this.detail?.rehearsals || [];
    },
    availablePractices() {
      return this.detail?.available_practices || [];
    },
    filteredPractices() {
      const q = this.rehearsalSearch.trim().toLowerCase();
      if (!q) return this.availablePractices;
      return this.availablePractices.filter(
        (p) => (p.title || "").toLowerCase().includes(q) || (p.date || "").includes(q),
      );
    },
    attendanceMap() {
      return this.detail?.attendance || {};
    },
    availableToAdd() {
      const rosterIds = new Set(this.roster.map((r) => r.member_id));
      const members = this.detail?.available_members || [];
      return members.filter((m) => m.status === "active" && !rosterIds.has(m.id));
    },
    filteredAvailable() {
      const q = this.addSearch.trim().toLowerCase();
      if (!q) return this.availableToAdd;
      return this.availableToAdd.filter(
        (m) =>
          (m.name || "").toLowerCase().includes(q) ||
          (m.role || "").toLowerCase().includes(q) ||
          (m.section || "").toLowerCase().includes(q),
      );
    },
    attendanceRate() {
      if (!this.roster.length || !this.rehearsals.length) return 0;
      let total = 0;
      let present = 0;
      for (const singer of this.roster) {
        for (const reh of this.rehearsals) {
          const s = this.getStatus(singer.member_id, reh.id);
          if (s) {
            total++;
            if (s === "present" || s === "late") present++;
          }
        }
      }
      if (!total) return 0;
      return Math.round((present / total) * 100);
    },
    // Singers grouped by role for the checklist
    singerGroups() {
      const groups = {};
      for (const singer of this.roster) {
        const label = singer.role || singer.section || "Other";
        if (!groups[label]) groups[label] = [];
        groups[label].push(singer);
      }
      // Sort groups by role order
      const roleOrder = ["Sopran", "Alto", "Tenor", "Bass"];
      return Object.entries(groups)
        .sort(([a], [b]) => {
          const ai = roleOrder.indexOf(a);
          const bi = roleOrder.indexOf(b);
          if (ai === -1 && bi === -1) return a.localeCompare(b);
          if (ai === -1) return 1;
          if (bi === -1) return -1;
          return ai - bi;
        })
        .map(([label, singers]) => ({
          label,
          singers: singers.sort((a, b) => (a.name || "").localeCompare(b.name || "")),
        }));
    },
    presentCount() {
      return this.pendingPresent.length;
    },
    allPresent() {
      return this.roster.length > 0 && this.pendingPresent.length === this.roster.length;
    },
  },

  async mounted() {
    this.loadingConcerts = true;
    try {
      const result = await this.fetchAttendanceConcerts();
      this.concerts = result.concerts || [];
      if (this.concerts.length === 1) {
        await this.selectConcert(this.concerts[0].id);
      }
    } catch (err) {
      this.showToast(err.message || "Failed to load concerts", "error");
    } finally {
      this.loadingConcerts = false;
    }
  },

  beforeUnmount() {
    if (this.toastTimer) clearTimeout(this.toastTimer);
    document.body.style.overflow = "";
  },

  watch: {
    showAddSingers(val) {
      document.body.style.overflow = val ? "hidden" : "";
      if (!val) {
        this.pendingAdds = [];
        this.addSearch = "";
      }
    },
    showManageRehearsals(val) {
      document.body.style.overflow = val ? "hidden" : "";
      if (val) {
        // Pre-populate with currently linked ids
        this.pendingRehearsalIds = (this.detail?.linked_rehearsal_ids || []).map(Number);
        this.rehearsalSearch = "";
      }
    },
    selectedRehearsalId() {
      // Reset pending state when switching rehearsals
      this.attendanceDirty = false;
      this.loadPresentForRehearsal();
    },
  },

  methods: {
    ...mapActions(useBmsStore, [
      "fetchAttendanceConcerts",
      "fetchAttendanceDetail",
      "updateConcertRoster",
      "recordScheduleAttendance",
      "updateConcertRehearsals",
      "linkRehearsal",
      "recordBulkAttendance",
      "fetchAttendanceByDate",
    ]),

    // ── Concert selection ────────────────────────────────────────────
    async selectConcert(id) {
      if (this.selectedConcertId === id && this.detail) return;
      this.selectedConcertId = id;
      this.selectedRehearsalId = null;
      this.selectedRehearsal = null;
      this.pendingPresent = [];
      this.attendanceDirty = false;
      this.loadingDetail = true;
      try {
        this.detail = await this.fetchAttendanceDetail(id);
      } catch (err) {
        this.showToast(err.message || "Failed to load attendance data", "error");
        this.detail = null;
      } finally {
        this.loadingDetail = false;
      }
    },

    // ── Rehearsal selection ──────────────────────────────────────────
    selectRehearsal(reh) {
      this.selectedRehearsalId = reh.id;
      this.selectedRehearsal = reh;
      this.attendanceDirty = false;
    },

    // Load existing "present" ids for selected rehearsal from attendanceMap
    loadPresentForRehearsal() {
      if (!this.selectedRehearsalId) {
        this.pendingPresent = [];
        return;
      }
      const map = this.attendanceMap;
      const presentIds = [];
      for (const singer of this.roster) {
        const key = `${this.selectedRehearsalId}-${singer.member_id}`;
        const status = map[key];
        if (status === "present" || status === "late") {
          presentIds.push(singer.member_id);
        }
      }
      this.pendingPresent = presentIds;
    },

    // ── Attendance helpers ───────────────────────────────────────────
    rehearsalDotClass(rehearsalId) {
      const total = this.roster.length;
      if (!total) return "dot-none";
      let marked = 0;
      let present = 0;
      for (const singer of this.roster) {
        const s = this.getStatus(singer.member_id, rehearsalId);
        if (s) {
          marked++;
          if (s === "present" || s === "late") present++;
        }
      }
      if (!marked) return "dot-none";
      const rate = Math.round((present / marked) * 100);
      if (rate >= 80) return "dot-good";
      if (rate >= 50) return "dot-mid";
      return "dot-low";
    },

    rehearsalDotTitle(rehearsalId) {
      const total = this.roster.length;
      if (!total) return "No singers";
      let marked = 0;
      let present = 0;
      for (const singer of this.roster) {
        const s = this.getStatus(singer.member_id, rehearsalId);
        if (s) {
          marked++;
          if (s === "present" || s === "late") present++;
        }
      }
      if (!marked) return "Not recorded yet";
      return `${present}/${marked} present`;
    },

    toggleSelectAll() {
      if (this.allPresent) {
        this.pendingPresent = [];
      } else {
        this.pendingPresent = this.roster.map((s) => s.member_id);
      }
      this.attendanceDirty = true;
    },

    async saveBulkAttendance() {
      if (!this.canManage || !this.selectedRehearsalId) return;
      this.savingBulk = true;
      try {
        await this.recordBulkAttendance({
          concertScheduleId: this.selectedConcertId,
          scheduleId: this.selectedRehearsalId,
          presentMemberIds: this.pendingPresent,
          markAbsent: true,
        });
        // Update local attendance map
        for (const singer of this.roster) {
          const key = `${this.selectedRehearsalId}-${singer.member_id}`;
          if (this.pendingPresent.includes(singer.member_id)) {
            this.detail.attendance[key] = "present";
          } else {
            this.detail.attendance[key] = "absent";
          }
        }
        this.attendanceDirty = false;
        this.showToast(`Attendance saved — ${this.pendingPresent.length} present`);
      } catch (err) {
        this.showToast(err.message || "Failed to save attendance", "error");
      } finally {
        this.savingBulk = false;
      }
    },

    // ── Manage rehearsals modal ──────────────────────────────────────
    toggleAllRehearsals() {
      if (this.pendingRehearsalIds.length === this.availablePractices.length) {
        this.pendingRehearsalIds = [];
      } else {
        this.pendingRehearsalIds = this.availablePractices.map((p) => p.id);
      }
    },

    async confirmManageRehearsals() {
      this.savingRehearsals = true;
      try {
        await this.updateConcertRehearsals(this.selectedConcertId, this.pendingRehearsalIds);
        // Reset selection if selected rehearsal was removed
        if (
          this.selectedRehearsalId &&
          !this.pendingRehearsalIds.includes(this.selectedRehearsalId)
        ) {
          this.selectedRehearsalId = null;
          this.selectedRehearsal = null;
          this.pendingPresent = [];
          this.attendanceDirty = false;
        }
        this.showManageRehearsals = false;
        this.showToast("Rehearsal sessions updated");
      } catch (err) {
        this.showToast(err.message || "Failed to update rehearsals", "error");
      } finally {
        this.savingRehearsals = false;
      }
    },

    // ── Roster ───────────────────────────────────────────────────────
    async removeSinger(memberId) {
      if (!this.canManage) return;
      if (!confirm("Remove this singer from the concert roster?")) return;
      try {
        await this.updateConcertRoster(this.selectedConcertId, memberId, "remove");
        await this.selectConcert(this.selectedConcertId);
        this.showToast("Singer removed from roster");
      } catch (err) {
        this.showToast(err.message || "Failed to remove singer", "error");
      }
    },

    async confirmAddSingers() {
      if (!this.pendingAdds.length) return;
      this.addingSingers = true;
      try {
        for (const memberId of this.pendingAdds) {
          await this.updateConcertRoster(this.selectedConcertId, memberId, "add");
        }
        await this.selectConcert(this.selectedConcertId);
        this.showAddSingers = false;
        this.showToast(`${this.pendingAdds.length} singer(s) added`);
      } catch (err) {
        this.showToast(err.message || "Failed to add singers", "error");
      } finally {
        this.addingSingers = false;
      }
    },

    // ── Attendance helpers ───────────────────────────────────────────
    getStatus(memberId, scheduleId) {
      return this.attendanceMap[`${scheduleId}-${memberId}`] || null;
    },

    singerRate(memberId) {
      if (!this.rehearsals.length) return 0;
      let marked = 0;
      let present = 0;
      for (const reh of this.rehearsals) {
        const s = this.getStatus(memberId, reh.id);
        if (s) {
          marked++;
          if (s === "present" || s === "late") present++;
        }
      }
      if (!marked) return 0;
      return Math.round((present / marked) * 100);
    },

    rateClass(memberId) {
      const rate = this.singerRate(memberId);
      if (rate >= 80) return "rate-good";
      if (rate >= 50) return "rate-mid";
      return "rate-low";
    },

    // ── Formatters ───────────────────────────────────────────────────
    formatTime(value) {
      return String(value || "").slice(0, 5);
    },

    formatDate(dateStr) {
      if (!dateStr) return "";
      const [y, m, d] = dateStr.split("-").map(Number);
      return new Date(y, m - 1, d).toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },

    formatShortDate(dateStr) {
      if (!dateStr) return "";
      const [y, m, d] = dateStr.split("-").map(Number);
      return new Date(y, m - 1, d).toLocaleDateString("id-ID", { day: "numeric", month: "short" });
    },

    monthAbbr(dateStr) {
      if (!dateStr) return "";
      const [y, m, d] = dateStr.split("-").map(Number);
      return new Date(y, m - 1, d).toLocaleDateString("id-ID", { month: "short" }).toUpperCase();
    },

    dayNum(dateStr) {
      if (!dateStr) return "";
      return dateStr.split("-")[2];
    },

    onImgError(e) {
      e.target.src = DEFAULT_AVATAR;
    },

    showToast(message, type = "success") {
      this.toast = { message, type };
      if (this.toastTimer) clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => {
        this.toast = null;
      }, 3500);
    },

    statusLabel(status) {
      if (!status) return "Not recorded";
      return status.charAt(0).toUpperCase() + status.slice(1);
    },
  },
};
</script>

<style scoped>
/* ══ Dark theme overrides ════════════════════════════════════════ */
.content-card.bg-dark {
    --surface-color: rgba(234, 220, 194, 0.04);
    --hairline-color: rgba(234, 220, 194, 0.08);
    --text-color: rgba(234, 220, 194, 0.85);
    --muted-color: rgba(234, 220, 194, 0.45);
    --ink-color: rgba(234, 220, 194, 0.92);
    color: rgba(234, 220, 194, 0.78);
}

.concert-pick-card {
    background: rgba(234, 220, 194, 0.03) !important;
    border-color: rgba(234, 220, 194, 0.08) !important;
    color: rgba(234, 220, 194, 0.7) !important;
}

.concert-pick-card h3 {
    color: rgba(234, 220, 194, 0.85) !important;
}

.concert-pick-card .text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.concert-pick-card.active {
    background: rgba(200, 164, 93, 0.06) !important;
    border-color: rgba(200, 164, 93, 0.35) !important;
}

.calendar-widget-sheet {
    background: rgba(26, 31, 48, 0.95) !important;
    border-color: rgba(234, 220, 194, 0.1) !important;
}

.calendar-widget-header {
    background: #7f2432 !important;
    color: #fff !important;
}

.calendar-widget-day {
    color: rgba(234, 220, 194, 0.9) !important;
}

.empty-hint {
    color: rgba(234, 220, 194, 0.45) !important;
}

.roster-section-icon {
    background: rgba(200, 164, 93, 0.1) !important;
    color: #c8a45d !important;
}

.singer-card {
    background: rgba(234, 220, 194, 0.03) !important;
    border-color: rgba(234, 220, 194, 0.08) !important;
}

.singer-card:hover {
    border-color: rgba(200, 164, 93, 0.3) !important;
}

.singer-card-name {
    color: rgba(234, 220, 194, 0.85) !important;
}

.singer-card-role {
    color: rgba(234, 220, 194, 0.45) !important;
}

.singer-card-remove {
    background: rgba(234, 220, 194, 0.04) !important;
    border-color: rgba(234, 220, 194, 0.08) !important;
    color: rgba(234, 220, 194, 0.35) !important;
}

.singer-card-remove:hover {
    border-color: rgba(220, 53, 69, 0.4) !important;
    color: #e05050 !important;
    background: rgba(220, 53, 69, 0.08) !important;
}

.sort-btn {
    border-color: rgba(234, 220, 194, 0.1) !important;
    color: rgba(234, 220, 194, 0.4) !important;
    background: rgba(234, 220, 194, 0.03) !important;
}

.sort-btn:hover,
.sort-btn.active {
    border-color: rgba(200, 164, 93, 0.3) !important;
    color: #c8a45d !important;
    background: rgba(200, 164, 93, 0.08) !important;
}

.roster-count-bar {
    color: rgba(234, 220, 194, 0.5) !important;
}

.roster-count-pill {
    background: rgba(200, 164, 93, 0.08) !important;
    border-color: rgba(200, 164, 93, 0.2) !important;
    color: rgba(234, 220, 194, 0.7) !important;
}

.roster-count-divider {
    background: rgba(234, 220, 194, 0.1) !important;
}

.reh-date-pill {
    background: rgba(234, 220, 194, 0.03) !important;
    border-color: rgba(234, 220, 194, 0.1) !important;
}

.reh-date-pill:hover {
    border-color: rgba(200, 164, 93, 0.35) !important;
}

.reh-date-pill.active {
    background: linear-gradient(135deg, rgba(127, 36, 50, 0.15), rgba(200, 164, 93, 0.08)) !important;
    border-color: #7f2432 !important;
}

.reh-pill-month {
    color: #c8a45d !important;
}

.reh-pill-day {
    color: rgba(234, 220, 194, 0.9) !important;
}

.reh-pill-time {
    color: rgba(234, 220, 194, 0.4) !important;
}

.checklist-row {
    background: rgba(234, 220, 194, 0.03) !important;
    border-color: rgba(234, 220, 194, 0.08) !important;
}

.checklist-row:hover {
    border-color: rgba(200, 164, 93, 0.3) !important;
    background: rgba(200, 164, 93, 0.04) !important;
}

.checklist-row.is-present {
    border-color: rgba(76, 175, 125, 0.35) !important;
    background: rgba(76, 175, 125, 0.05) !important;
}

.checklist-name {
    color: rgba(234, 220, 194, 0.85) !important;
}

.checklist-role {
    color: rgba(234, 220, 194, 0.45) !important;
}

.singer-group-label {
    color: rgba(234, 220, 194, 0.5) !important;
}

.singer-group-count {
    background: rgba(234, 220, 194, 0.08) !important;
    color: rgba(234, 220, 194, 0.45) !important;
}

.attendance-prompt h4 {
    color: rgba(234, 220, 194, 0.8) !important;
}

.attendance-prompt .text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.rehearsal-count-pill {
    color: rgba(234, 220, 194, 0.7) !important;
}

.text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.roster-empty-title {
    color: rgba(234, 220, 194, 0.8) !important;
}

.roster-empty-sub {
    color: rgba(234, 220, 194, 0.45) !important;
}

.attendance-prompt .small.text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.floating-save-bar {
    background: linear-gradient(135deg, #1a1f30, #111420) !important;
    border: 1px solid rgba(234, 220, 194, 0.08) !important;
}

/* Modals */
.modal-overlay {
    background: rgba(8, 8, 14, 0.7) !important;
    backdrop-filter: blur(6px) !important;
}

.modal-sheet {
    background: linear-gradient(135deg, rgba(200, 164, 93, 0.04), transparent),
                linear-gradient(180deg, #1a1f30, #111420) !important;
    border: 1px solid rgba(234, 220, 194, 0.12) !important;
    color: rgba(234, 220, 194, 0.8) !important;
}

.modal-sheet-title {
    color: rgba(234, 220, 194, 0.88) !important;
}

.modal-sheet-sub {
    color: rgba(234, 220, 194, 0.45) !important;
}

.modal-header-row {
    border-bottom: 1px solid rgba(234, 220, 194, 0.08) !important;
}

.modal-footer-row {
    border-top: 1px solid rgba(234, 220, 194, 0.08) !important;
}

.modal-close-btn {
    color: rgba(234, 220, 194, 0.4) !important;
}

.modal-close-btn:hover {
    color: rgba(234, 220, 194, 0.7) !important;
    background: rgba(234, 220, 194, 0.06) !important;
}

.search-input {
    background: rgba(234, 220, 194, 0.04) !important;
    border-color: rgba(234, 220, 194, 0.12) !important;
    color: rgba(234, 220, 194, 0.8) !important;
}

.search-input::placeholder {
    color: rgba(234, 220, 194, 0.3) !important;
}

.search-input:focus {
    border-color: rgba(200, 164, 93, 0.4) !important;
    box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.1) !important;
}

.search-icon {
    color: rgba(234, 220, 194, 0.35) !important;
}

.add-row {
    border-color: rgba(234, 220, 194, 0.06) !important;
}

.add-row:hover {
    background: rgba(200, 164, 93, 0.04) !important;
}

.add-row.selected {
    background: rgba(200, 164, 93, 0.06) !important;
    border-color: rgba(200, 164, 93, 0.2) !important;
}

.add-name {
    color: rgba(234, 220, 194, 0.8) !important;
}

.add-meta {
    color: rgba(234, 220, 194, 0.45) !important;
}

.reh-mini-cal {
    background: rgba(26, 31, 48, 0.95) !important;
    border-color: rgba(234, 220, 194, 0.1) !important;
}

.reh-mini-month {
    background: #7f2432 !important;
    color: #fff !important;
}

.reh-mini-day {
    color: rgba(234, 220, 194, 0.85) !important;
}

.rehearsal-select-info .text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.modal-body-scroll .text-muted {
    color: rgba(234, 220, 194, 0.45) !important;
}

.attendance-toast {
    background: #1a1f30 !important;
    border: 1px solid rgba(234, 220, 194, 0.12) !important;
    color: rgba(234, 220, 194, 0.85) !important;
}

.toast-success {
    border-left: 3px solid #4caf7d !important;
}

.toast-error {
    border-left: 3px solid #e05050 !important;
}

.add-avatar {
    border-color: rgba(234, 220, 194, 0.1) !important;
}

.checklist-avatar {
    border-color: rgba(234, 220, 194, 0.1) !important;
}

.attendance-eyebrow {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold-color, #c8a45d);
}

.stats-panel {
  background: linear-gradient(135deg, #171b27 0%, #222838 100%);
  color: #fff;
  border: 1px solid rgba(200, 164, 93, 0.2);
}

.stats-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.mini-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 64px;
}

.mini-stat-val {
  font-size: 1.4rem;
  font-weight: 800;
  line-height: 1.1;
}

.mini-stat-lbl {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: rgba(255, 255, 255, 0.55);
}

.concert-pick-card {
  background: var(--surface-color);
  border: 1px solid var(--hairline-color);
  border-radius: var(--radius-md, 8px);
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.concert-pick-card:hover,
.concert-pick-card.active {
  border-color: rgba(200, 164, 93, 0.55);
  box-shadow: var(--shadow-soft, 0 8px 24px rgba(19, 18, 16, 0.08));
}

.concert-pick-card.active {
  background: rgba(200, 164, 93, 0.08);
}

.calendar-widget-sheet {
  width: 3.5rem;
  background: rgba(255, 253, 248, 0.9);
  border: 1px solid var(--hairline-color);
  border-radius: 8px;
  overflow: hidden;
  text-align: center;
}

.calendar-widget-header {
  background: var(--accent-color);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.15rem 0;
}

.calendar-widget-body {
  padding: 0.25rem 0 0.35rem;
}

.calendar-widget-day {
  font-size: 1.4rem;
  font-weight: 800;
  line-height: 1;
}

/* ══ REHEARSAL MANAGEMENT ═══════════════════════════════════════════ */
.section-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(127, 36, 50, 0.12);
  color: var(--accent-color, #7f2432);
  font-size: 0.9rem;
  flex-shrink: 0;
}

.section-icon--accent {
  background: rgba(200, 164, 93, 0.15);
  color: var(--gold-color, #c8a45d);
}

.rehearsal-count-pill {
  display: inline-flex;
  align-items: center;
  background: rgba(200, 164, 93, 0.1);
  border: 1px solid rgba(200, 164, 93, 0.25);
  color: var(--text-color);
  padding: 0.22rem 0.7rem;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 700;
}

.rehearsal-empty-hint {
  padding: 0.75rem 0;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: wrap;
}

/* ── Rehearsal date strip ──────────────────────────────────────────── */
.rehearsal-date-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
}

.reh-date-pill {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: 0.6rem 0.9rem 0.55rem;
  background: var(--surface-color);
  border: 1.5px solid var(--hairline-color);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.18s ease;
  min-width: 68px;
}

.reh-date-pill:hover {
  border-color: rgba(200, 164, 93, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.reh-date-pill.active {
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.12), rgba(200, 164, 93, 0.08));
  border-color: var(--accent-color, #7f2432);
  box-shadow: 0 0 0 3px rgba(127, 36, 50, 0.1);
}

.reh-pill-month {
  font-size: 0.64rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: var(--accent-color, #7f2432);
  text-transform: uppercase;
}

.reh-pill-day {
  font-size: 1.5rem;
  font-weight: 900;
  line-height: 1;
  color: var(--text-color);
}

.reh-pill-time {
  font-size: 0.65rem;
  color: var(--muted-color);
  font-weight: 600;
}

.reh-pill-dot {
  position: absolute;
  top: 6px;
  right: 7px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 1.5px solid var(--surface-color);
}

.reh-pill-dot.dot-none {
  background: var(--hairline-color);
  border-color: transparent;
}
.reh-pill-dot.dot-good {
  background: #4a7c59;
}
.reh-pill-dot.dot-mid {
  background: #b8860b;
}
.reh-pill-dot.dot-low {
  background: #c0392b;
}

/* ══ TAKE ATTENDANCE ════════════════════════════════════════════════ */
.attendance-prompt {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.attendance-prompt-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(200, 164, 93, 0.08);
  border: 2px dashed rgba(200, 164, 93, 0.25);
  display: grid;
  place-items: center;
  font-size: 1.75rem;
  color: var(--gold-color, #c8a45d);
}

/* Quick stats */
.attend-quick-stats {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 700;
  font-size: 0.9rem;
}

.qs-present {
  color: #4a7c59;
}
.qs-divider {
  color: var(--muted-color);
}
.qs-total {
  color: var(--muted-color);
}

/* Group labels */
.singer-group-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--muted-color);
  margin-bottom: 0.5rem;
}

.singer-group-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--hairline-color);
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--muted-color);
}

/* Checklist rows */
.attendance-checklist {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.checklist-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.85rem;
  background: var(--surface-color);
  border: 1.5px solid var(--hairline-color);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.checklist-row:hover {
  border-color: rgba(200, 164, 93, 0.35);
  background: rgba(200, 164, 93, 0.04);
}

.checklist-row.is-present {
  border-color: rgba(74, 124, 89, 0.4);
  background: rgba(74, 124, 89, 0.05);
}

.checklist-cb {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  accent-color: #4a7c59;
  cursor: pointer;
}

.checklist-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 1.5px solid var(--hairline-color);
}

.checklist-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.checklist-name {
  font-size: 0.88rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.checklist-role {
  font-size: 0.7rem;
  color: var(--muted-color);
}

.checklist-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  flex-shrink: 0;
  margin-left: auto;
}

.status-present {
  background: rgba(74, 124, 89, 0.15);
  color: #4a7c59;
}

.status-absent {
  background: rgba(192, 57, 43, 0.1);
  color: #c0392b;
}

/* Floating save bar */
.floating-save-bar {
  position: fixed;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1080;
  background: rgba(20, 20, 28, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(200, 164, 93, 0.25);
  border-radius: 12px;
  padding: 0.65rem 1.25rem;
  min-width: 260px;
  max-width: 90vw;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.25s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(12px);
}

/* ══ REHEARSAL MODAL ════════════════════════════════════════════════ */
.rehearsal-add-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.55rem 0.75rem;
}

.rehearsal-select-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.reh-mini-cal {
  width: 40px;
  text-align: center;
  background: var(--surface-color);
  border: 1px solid var(--hairline-color);
  border-radius: 7px;
  overflow: hidden;
  flex-shrink: 0;
}

.reh-mini-month {
  display: block;
  background: var(--accent-color, #7f2432);
  color: #fff;
  font-size: 0.58rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  padding: 0.1rem 0;
  text-transform: uppercase;
}

.reh-mini-day {
  display: block;
  font-size: 1.1rem;
  font-weight: 900;
  line-height: 1.3;
  padding-bottom: 0.1rem;
}
.roster-section-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(127, 36, 50, 0.12);
  color: var(--accent-color, #7f2432);
  font-size: 0.9rem;
  flex-shrink: 0;
}

/* Count bar */
.roster-count-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.roster-count-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.1), rgba(200, 164, 93, 0.1));
  border: 1px solid rgba(200, 164, 93, 0.25);
  color: var(--text-color);
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
}

.roster-count-divider {
  width: 1px;
  height: 14px;
  background: var(--hairline-color);
  flex-shrink: 0;
}

/* Sort group */
.sort-group {
  background: var(--surface-color);
  border: 1px solid var(--hairline-color);
  border-radius: 8px;
  padding: 3px;
  display: flex;
  gap: 2px;
}

.sort-btn {
  display: inline-flex;
  align-items: center;
  border: none;
  background: transparent;
  padding: 0.3rem 0.65rem;
  border-radius: 6px;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--muted-color);
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.sort-btn:hover {
  background: rgba(200, 164, 93, 0.1);
  color: var(--text-color);
}

.sort-btn.active {
  background: var(--accent-color, #7f2432);
  color: #fff;
}

/* Empty state */
.roster-empty-state {
  text-align: center;
  padding: 3rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.roster-empty-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(108, 117, 125, 0.08);
  border: 2px dashed rgba(108, 117, 125, 0.2);
  display: grid;
  place-items: center;
  font-size: 1.75rem;
  color: var(--muted-color);
  margin-bottom: 0.5rem;
}

.roster-empty-title {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.roster-empty-sub {
  font-size: 0.85rem;
  color: var(--muted-color);
  margin-bottom: 0.75rem;
}

/* Singer cards grid */
.singer-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.85rem;
}

@media (max-width: 575.98px) {
  .singer-cards-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.65rem;
  }
}

@media (max-width: 360px) {
  .singer-cards-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.singer-card {
  position: relative;
  background: var(--surface-color);
  border: 1px solid var(--hairline-color);
  border-radius: 12px;
  padding: 1.1rem 0.9rem 0.85rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.5rem;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    border-color 0.2s ease;
  overflow: hidden;
}

.singer-card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 3px;
  background: linear-gradient(90deg, var(--accent-color, #7f2432), var(--gold-color, #c8a45d));
  opacity: 0;
  transition: opacity 0.2s ease;
}

.singer-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(13, 13, 18, 0.12);
  border-color: rgba(200, 164, 93, 0.35);
}

.singer-card:hover::before {
  opacity: 1;
}

/* Rate badge top-right */
.singer-card-rate {
  position: absolute;
  top: 0.55rem;
  left: 0.55rem;
}

.singer-rate-badge {
  display: inline-block;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.01em;
}

.singer-rate-badge.rate-good {
  background: rgba(74, 124, 89, 0.15);
  color: #4a7c59;
}
.singer-rate-badge.rate-mid {
  background: rgba(184, 134, 11, 0.15);
  color: #b8860b;
}
.singer-rate-badge.rate-low {
  background: rgba(192, 57, 43, 0.12);
  color: #c0392b;
}

/* Avatar wrapper with status dot */
.singer-card-avatar-wrap {
  position: relative;
  display: inline-block;
}

.singer-card-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--hairline-color);
  transition: border-color 0.2s ease;
}

.singer-card:hover .singer-card-avatar {
  border-color: rgba(200, 164, 93, 0.5);
}

.singer-status-dot {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid var(--surface-color);
}

.singer-status-dot.rate-good {
  background: #4a7c59;
}
.singer-status-dot.rate-mid {
  background: #b8860b;
}
.singer-status-dot.rate-low {
  background: #c0392b;
}

/* Singer info */
.singer-card-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 0;
  width: 100%;
}

.singer-card-name {
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}

.singer-card-role {
  display: inline-block;
  margin-top: 0.2rem;
  font-size: 0.68rem;
  font-weight: 600;
  color: #fff;
  background: rgba(127, 36, 50, 0.75);
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  letter-spacing: 0.03em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

/* Remove button (top-right corner) */
.singer-card-remove {
  position: absolute;
  top: 0.45rem;
  right: 0.45rem;
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 50%;
  background: rgba(192, 57, 43, 0.08);
  color: var(--muted-color);
  cursor: pointer;
  font-size: 0.6rem;
  display: grid;
  place-items: center;
  opacity: 0;
  transition:
    opacity 0.15s,
    background 0.15s,
    color 0.15s;
}

.singer-card:hover .singer-card-remove {
  opacity: 1;
}

.singer-card-remove:hover {
  background: rgba(192, 57, 43, 0.18);
  color: #c0392b;
}

/* Roster header bar */
.roster-header-bar {
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--hairline-color);
  margin-bottom: 1rem;
}

/* ══ ROSTER SECTION ═══════════════════════════════════════════════ */
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
}

.modal-sheet {
  background: var(--surface-color, #fffdf8);
  border-radius: 14px;
  border: 1px solid var(--hairline-color);
  box-shadow: 0 32px 72px rgba(10, 10, 15, 0.36);
  width: 100%;
  max-height: calc(100vh - 3rem);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-sheet--lg {
  max-width: 560px;
}

.modal-header-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--hairline-color);
}

.modal-sheet-title {
  font-size: 1rem;
  font-weight: 700;
}
.modal-sheet-sub {
  font-size: 0.78rem;
  color: var(--muted-color);
}

.modal-close-btn {
  border: 0;
  background: rgba(34, 29, 20, 0.06);
  border-radius: 8px;
  width: 34px;
  height: 34px;
  cursor: pointer;
  color: var(--muted-color);
}

.modal-body-scroll {
  overflow-y: auto;
  flex: 1;
  max-height: 360px;
}

.modal-footer-row {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--hairline-color);
}

.search-wrap {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted-color);
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.55rem 0.75rem 0.55rem 2.4rem;
  border: 1px solid var(--hairline-color);
  border-radius: 8px;
  background: rgba(255, 253, 248, 0.9);
  font-size: 0.9rem;
}

.add-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.add-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--hairline-color);
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}

.add-row:hover,
.add-row.selected {
  background: rgba(200, 164, 93, 0.1);
  border-color: rgba(200, 164, 93, 0.4);
}

.add-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
}

.add-name {
  display: block;
  font-weight: 700;
  font-size: 0.88rem;
}
.add-meta {
  display: block;
  font-size: 0.72rem;
  color: var(--muted-color);
}

.attendance-toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 1100;
  padding: 0.75rem 1.1rem;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.25s ease;
}

.toast-success {
  background: #4a7c59;
  color: #fff;
}
.toast-error {
  background: #c0392b;
  color: #fff;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.22s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

@media (max-width: 767px) {
  .modal-overlay {
    padding: 0;
    align-items: flex-end;
  }
  .modal-sheet {
    border-radius: 16px 16px 0 0;
    max-height: 92vh;
  }
}
</style>
