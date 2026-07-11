<template>
  <div class="fade-in-up">
    <div class="schedules-header">
      <div class="schedules-header-top">
        <div class="schedules-header-text">
          <p class="schedules-breadcrumb">TRMS Schedules</p>
          <h1 class="schedules-title">Schedule Calendar</h1>
          <p class="schedules-desc">
            Manage your music studio classes, lessons, and events in one place.
          </p>
        </div>
        <div class="schedules-header-actions" v-if="canManageSchedule">
          <button class="schedules-add-btn" @click="openAddModal">
            <i class="bi bi-plus-lg"></i>
            <span class="schedules-add-label">Add Schedule</span>
          </button>
        </div>
      </div>
    </div>

    <div class="content-card bg-dark">
      <div class="calendar-toolbar">
        <button
          class="calendar-nav-btn"
          @click="prevMonth"
          aria-label="Previous month"
        >
          <i class="bi bi-chevron-left"></i>
        </button>
        <h2 class="calendar-month-label">{{ monthYearLabel }}</h2>
        <button
          class="calendar-nav-btn"
          @click="goToToday"
          aria-label="Go to today"
        >
          <i class="bi bi-calendar-check"></i>
        </button>
        <button
          class="calendar-nav-btn"
          @click="nextMonth"
          aria-label="Next month"
        >
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>

      <div class="calendar-scroll">
        <div class="calendar-grid">
          <div class="calendar-weekdays">
            <div class="calendar-header" v-for="(dayName, index) in dayNames" :key="dayName">
              <span class="calendar-header-full">{{ dayName }}</span>
              <span class="calendar-header-short">{{ dayNamesShort[index] }}</span>
            </div>
          </div>
          <div class="calendar-body">
            <div class="calendar-week" v-for="week in calendarWeeks" :key="week.key">
              <div
                class="calendar-day-cell"
                v-for="day in week.days"
                :key="day.dateKey"
                :class="{
                  'other-month': !day.isCurrentMonth,
                  today: day.isToday,
                  'has-schedules': getSchedulesForDate(day.dateKey).length > 0,
                  'read-only': !canManageSchedule,
                }"
                @click="handleDayClick(day)"
              >
                <div class="day-number-wrap">
                  <span class="day-number">{{ day.dayNumber }}</span>
                </div>
                <div class="schedule-dots" v-if="getSchedulesForDate(day.dateKey).length > 0">
                  <span
                    class="schedule-dot"
                    v-for="schedule in getSchedulesForDate(day.dateKey).slice(0, 3)"
                    :key="schedule.id"
                    :class="scheduleDotClass(schedule.type)"
                    @click.stop="handleScheduleClick(schedule)"
                  ></span>
                  <span
                    class="schedule-dot schedule-dot-more"
                    v-if="getSchedulesForDate(day.dateKey).length > 3"
                  ></span>
                </div>
                <div class="schedule-chips">
                  <span
                    class="schedule-chip"
                    v-for="schedule in getSchedulesForDate(day.dateKey).slice(0, 3)"
                    :key="schedule.id"
                    :class="{ 'schedule-chip-readonly': !canManageSchedule }"
                    @click.stop="handleScheduleClick(schedule)"
                  >
                    {{ schedule.title }}
                  </span>
                  <span class="more-schedules" v-if="getSchedulesForDate(day.dateKey).length > 3">
                    +{{ getSchedulesForDate(day.dateKey).length - 3 }} more
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ScheduleFormModal
      ref="scheduleFormModal"
      :loading="loading"
      :success-message="successMessage"
      :error-message="errorMessage"
      @submit="submitSchedule"
      @delete="deleteSchedule"
    />

    <Teleport to="body">
      <div class="modal fade" id="scheduleDetailModal" tabindex="-1" ref="scheduleDetailModal">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content schedule-detail-modal">
            <div class="modal-header">
              <h5 class="modal-title">
                {{ selectedSchedule ? selectedSchedule.title : "Schedule Detail" }}
              </h5>
              <button
                type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body" v-if="selectedSchedule">
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="badge" :class="typeBadgeClass(selectedSchedule.type)">{{
                  typeLabel(selectedSchedule.type)
                }}</span>
                <span class="text-champagne-muted">{{ formatDate(selectedSchedule.date) }}</span>
              </div>
              <p class="mb-3 text-champagne-muted">
                {{ selectedSchedule.description || "No description provided." }}
              </p>
              <div class="mb-3" v-if="selectedSchedule.venue">
                <span class="text-champagne-muted small d-block mb-1">Venue</span>
                <span class="text-champagne"
                  ><i class="bi bi-geo-alt me-1"></i>{{ selectedSchedule.venue }}</span
                >
              </div>
              <div class="mb-3" v-if="selectedSchedule.concert_code">
                <span class="text-champagne-muted small d-block mb-1">Concert Code</span>
                <span class="badge bg-warning text-dark">{{ selectedSchedule.concert_code }}</span>
              </div>
              <div
                class="mb-3"
                v-if="selectedSchedule.program_ids && selectedSchedule.program_ids.length > 0"
              >
                <span class="text-champagne-muted small d-block mb-1"
                  >Programs / Collaborating Groups</span
                >
                <div class="d-flex flex-wrap gap-1">
                  <span
                    v-for="progId in selectedSchedule.program_ids"
                    :key="progId"
                    class="badge program-badge text-uppercase"
                  >
                    {{ getProgramName(progId) }}
                  </span>
                </div>
              </div>
              <div class="d-flex gap-4 text-champagne-muted small">
                <span
                  ><i class="bi bi-clock me-1"></i> {{ selectedSchedule.start_time }} -
                  {{ selectedSchedule.end_time }}</span
                >
              </div>
            </div>
            <div class="modal-footer">
              <button
                v-if="canManageSchedule"
                class="btn btn-outline-gold"
                @click="openEditFromDetail"
              >
                <i class="bi bi-pencil me-2"></i> Edit
              </button>
              <button
                v-if="canManageSchedule"
                class="btn btn-outline-danger"
                @click="deleteFromDetail"
              >
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
import { Modal } from "bootstrap";
import { mapState, mapActions } from "pinia";
import { useTrmsStore } from "../../stores/api";
import { useAuthStore } from "../../stores/auth";
import ScheduleFormModal from "../../components/trms/ScheduleFormModal.vue";

export default {
  name: "Schedules",
  components: {
    ScheduleFormModal,
  },
  computed: {
    ...mapState(useTrmsStore, ["schedules"]),
    canManageSchedule() {
      const authStore = useAuthStore();
      const role = authStore.user?.role?.toLowerCase();
      return role === "admin" || role === "manager";
    },
    monthYearLabel() {
      return this.currentMonth.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
      });
    },
    dayNames() {
      return ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    },
    dayNamesShort() {
      return ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    },
    calendarWeeks() {
      const weeks = [];
      const year = this.currentMonth.getFullYear();
      const month = this.currentMonth.getMonth();
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const startDayOfWeek = firstDay.getDay();
      const daysInMonth = lastDay.getDate();

      const prevMonthLastDay = new Date(year, month, 0).getDate();

      let dayCounter = 1;
      let nextMonthDayCounter = 1;

      const totalWeeks = Math.ceil((startDayOfWeek + daysInMonth) / 7);

      for (let w = 0; w < totalWeeks; w++) {
        const days = [];
        for (let d = 0; d < 7; d++) {
          const dayIndex = w * 7 + d;
          let dayNumber;
          let dateKey;
          let isCurrentMonth = true;
          let isToday = false;

          if (dayIndex < startDayOfWeek) {
            dayNumber = prevMonthLastDay - startDayOfWeek + dayIndex + 1;
            const prevMonth = month === 0 ? 11 : month - 1;
            const prevYear = month === 0 ? year - 1 : year;
            dateKey = `${prevYear}-${String(prevMonth + 1).padStart(2, "0")}-${String(dayNumber).padStart(2, "0")}`;
            isCurrentMonth = false;
          } else if (dayCounter > daysInMonth) {
            dayNumber = nextMonthDayCounter;
            nextMonthDayCounter++;
            const nextMonth = month === 11 ? 0 : month + 1;
            const nextYear = month === 11 ? year + 1 : year;
            dateKey = `${nextYear}-${String(nextMonth + 1).padStart(2, "0")}-${String(dayNumber).padStart(2, "0")}`;
            isCurrentMonth = false;
          } else {
            dayNumber = dayCounter;
            dateKey = `${year}-${String(month + 1).padStart(2, "0")}-${String(dayNumber).padStart(2, "0")}`;
            dayCounter++;
            const today = new Date();
            isToday =
              today.getDate() === dayNumber &&
              today.getMonth() === month &&
              today.getFullYear() === year;
          }

          days.push({
            dayNumber,
            dateKey,
            isCurrentMonth,
            isToday,
          });
        }
        weeks.push({ key: `week-${w}`, days });
      }

      return weeks;
    },
  },
  data() {
    return {
      currentMonth: new Date(),
      loading: false,
      successMessage: "",
      errorMessage: "",
      selectedSchedule: null,
      scheduleDetailModalInstance: null,
    };
  },
  async mounted() {
    this.loading = true;
    try {
      await this.fetchSchedules();
    } finally {
      this.loading = false;
    }
  },
  methods: {
    ...mapActions(useTrmsStore, {
      storeDeleteSchedule: "deleteSchedule",
      fetchSchedules: "fetchSchedules",
      createSchedule: "createSchedule",
      updateSchedule: "updateSchedule",
    }),
    prevMonth() {
      this.currentMonth = new Date(
        this.currentMonth.getFullYear(),
        this.currentMonth.getMonth() - 1,
        1,
      );
    },
    nextMonth() {
      this.currentMonth = new Date(
        this.currentMonth.getFullYear(),
        this.currentMonth.getMonth() + 1,
        1,
      );
    },
    goToToday() {
      this.currentMonth = new Date();
    },
    getSchedulesForDate(dateKey) {
      return this.schedules
        .filter((s) => s.date === dateKey)
        .sort((a, b) => a.start_time.localeCompare(b.start_time));
    },
    formatDate(dateStr) {
      if (!dateStr) return "";
      const [year, month, day] = dateStr.split("-").map(Number);
      return new Date(year, month - 1, day).toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },
    typeBadgeClass(type) {
      const map = {
        lesson: "bg-primary",
        practice: "bg-success",
        concert: "bg-warning text-dark",
        exam: "bg-danger",
        other: "bg-secondary",
      };
      return map[type] || "bg-secondary";
    },
    typeLabel(type) {
      const map = {
        lesson: "Lesson",
        practice: "Practice",
        concert: "Concert",
        exam: "Exam",
        other: "Other",
      };
      return map[type] || type;
    },
    getProgramName(progId) {
      const map = {
        trms: "TRMS",
        bms: "BMS",
        jco: "JCO",
        trcc: "TRCC",
      };
      return map[progId] || progId.toUpperCase();
    },
    scheduleDotClass(type) {
      const map = {
        lesson: "schedule-dot-lesson",
        practice: "schedule-dot-practice",
        concert: "schedule-dot-concert",
        exam: "schedule-dot-exam",
        other: "schedule-dot-other",
      };
      return map[type] || "schedule-dot-other";
    },
    openAddModal() {
      if (!this.canManageSchedule) return;
      this.clearFormMessages();
      this.$refs.scheduleFormModal.openAdd();
    },
    handleDayClick(day) {
      if (!day.isCurrentMonth) return;

      if (this.canManageSchedule) {
        this.openDayModal(day.dateKey);
        return;
      }

      const schedules = this.getSchedulesForDate(day.dateKey);
      if (schedules.length > 0) {
        this.openDetailModal(schedules[0]);
      }
    },
    handleScheduleClick(schedule) {
      if (this.canManageSchedule) {
        this.openEditModal(schedule);
        return;
      }
      this.openDetailModal(schedule);
    },
    openDayModal(dateKey) {
      if (!this.canManageSchedule) return;
      this.clearFormMessages();
      this.$refs.scheduleFormModal.openDay(dateKey);
    },
    openEditModal(schedule) {
      if (!this.canManageSchedule) return;
      this.clearFormMessages();
      this.$refs.scheduleFormModal.openEdit(schedule);
    },
    openDetailModal(schedule) {
      this.selectedSchedule = schedule;
      this.showDetailModal();
    },
    openEditFromDetail() {
      if (!this.canManageSchedule) return;
      this.hideDetailModal();
      setTimeout(() => {
        this.openEditModal(this.selectedSchedule);
      }, 300);
    },
    async submitSchedule(payload) {
      if (!this.canManageSchedule) return;
      this.loading = true;
      this.clearFormMessages();
      try {
        if (payload.mode === "edit") {
          await this.updateSchedule(payload.scheduleId, payload.data);
          this.successMessage = "Schedule updated successfully.";
        } else {
          await this.createSchedule(payload.data);
          this.successMessage = "Schedule added successfully.";
        }
        await this.fetchSchedules();
        if (payload.mode === "add") {
          setTimeout(() => this.$refs.scheduleFormModal.hide(), 800);
        }
      } catch (error) {
        this.errorMessage = error.message || "Unable to save schedule.";
      } finally {
        this.loading = false;
      }
    },
    async deleteSchedule(scheduleId) {
      if (!this.canManageSchedule) return;
      if (!confirm("Are you sure you want to delete this schedule?")) return;

      this.loading = true;
      this.clearFormMessages();
      try {
        await this.storeDeleteSchedule(scheduleId);
        this.successMessage = "Schedule deleted successfully.";
        await this.fetchSchedules();
        setTimeout(() => this.$refs.scheduleFormModal.hide(), 800);
      } catch (error) {
        this.errorMessage = error.message || "Unable to delete schedule.";
      } finally {
        this.loading = false;
      }
    },
    async deleteFromDetail() {
      if (!this.canManageSchedule) return;
      if (!this.selectedSchedule) return;
      if (!confirm("Are you sure you want to delete this schedule?")) return;

      this.loading = true;
      this.clearFormMessages();
      try {
        await this.storeDeleteSchedule(this.selectedSchedule.id);
        this.successMessage = "Schedule deleted successfully.";
        await this.fetchSchedules();
        this.hideDetailModal();
      } catch (error) {
        this.errorMessage = error.message || "Unable to delete schedule.";
      } finally {
        this.loading = false;
      }
    },
    clearFormMessages() {
      this.successMessage = "";
      this.errorMessage = "";
    },
    showDetailModal() {
      const el = this.$refs.scheduleDetailModal;
      if (!el) return;
      this.scheduleDetailModalInstance = Modal.getOrCreateInstance(el);
      this.scheduleDetailModalInstance.show();
    },
    hideDetailModal() {
      if (this.scheduleDetailModalInstance) {
        this.scheduleDetailModalInstance.hide();
      }
    },
  },
  beforeUnmount() {
    if (this.scheduleDetailModalInstance) {
      this.scheduleDetailModalInstance.dispose();
    }
  },
};
</script>

<style scoped>
/* ── Schedules header ─────────────────────────────────────── */
.schedules-header {
  position: relative;
  margin-bottom: 1.5rem;
  padding: 1.5rem 1.75rem;
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 14px;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.12), transparent 46%),
    linear-gradient(135deg, #10131f 0%, #202736 58%, #121722 100%);
  box-shadow:
    0 1px 0 rgba(255, 255, 255, 0.03) inset,
    0 20px 44px rgba(10, 10, 18, 0.28);
  overflow: hidden;
}

.schedules-header::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 3px;
  background: linear-gradient(
    90deg,
    var(--accent-color, #7f2432),
    var(--gold-color, #c8a45d),
    rgba(234, 220, 194, 0.6)
  );
  opacity: 0.8;
}

.schedules-header-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.25rem;
}

.schedules-header-text {
  flex: 1;
  min-width: 0;
}

.schedules-breadcrumb {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--gold-color, #c8a45d);
  margin-bottom: 0.25rem;
}

.schedules-title {
  font-size: clamp(1.4rem, 3.5vw, 2.2rem);
  font-weight: 800;
  color: #fffdf8;
  margin-bottom: 0.35rem;
  line-height: 1.2;
}

.schedules-desc {
  font-size: 0.9rem;
  color: rgba(234, 220, 194, 0.55);
  margin-bottom: 0;
  max-width: 540px;
}

.schedules-header-actions {
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.schedules-add-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.6rem 1.35rem;
  border: 1px solid #9d7d3b;
  border-radius: 10px;
  color: #17130a;
  background: linear-gradient(180deg, #d6b66c 0%, var(--gold-color, #c8a45d) 100%);
  box-shadow:
    0 8px 24px rgba(122, 94, 39, 0.3),
    0 0 0 1px rgba(200, 164, 93, 0.2) inset;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
  position: relative;
  z-index: 1;
}

.schedules-add-btn::after {
  content: "";
  position: absolute;
  inset: -2px;
  border-radius: 12px;
  background: radial-gradient(ellipse at center, rgba(200, 164, 93, 0.35), transparent 70%);
  opacity: 0;
  transition: opacity 0.3s ease;
  z-index: -1;
}

.schedules-add-btn:hover::after {
  opacity: 1;
}

.schedules-add-btn:hover {
  border-color: #8f6e2f;
  color: #111;
  background: linear-gradient(180deg, #e1c47f 0%, #b99245 100%);
  transform: translateY(-2px);
  box-shadow:
    0 14px 32px rgba(122, 94, 39, 0.4),
    0 0 0 1px rgba(200, 164, 93, 0.3) inset;
}

.schedules-add-btn:active {
  transform: translateY(0);
  box-shadow:
    0 4px 12px rgba(122, 94, 39, 0.25),
    0 0 0 1px rgba(200, 164, 93, 0.2) inset;
}

.schedules-add-btn i {
  font-size: 1.1rem;
}

/* ── Floating action button (mobile) ──────────────────────── */
.schedules-fab {
  display: none;
}

/* ── Calendar toolbar ──────────────────────────────────────────── */
.calendar-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.calendar-month-label {
  margin: 0;
  font-weight: 700;
  font-size: clamp(1.1rem, 3vw, 1.75rem);
  text-align: center;
  flex: 1;
  line-height: 1.3;
  color: var(--gold-color, #c8a45d);
}

.calendar-nav-btn {
  flex-shrink: 0;
  width: 2.5rem;
  height: 2.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 1px solid rgba(200, 164, 93, 0.3);
  border-radius: 8px;
  background: rgba(200, 164, 93, 0.06);
  color: var(--gold-color, #c8a45d);
  font-size: 0.95rem;
  cursor: pointer;
  transition:
    background 0.2s,
    border-color 0.2s,
    color 0.2s,
    transform 0.2s,
    box-shadow 0.2s;
}

.calendar-nav-btn:hover {
  background: rgba(200, 164, 93, 0.14);
  border-color: rgba(200, 164, 93, 0.5);
  color: #fffdf8;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(122, 94, 39, 0.2);
}

.calendar-nav-btn:active {
  transform: translateY(0);
  background: rgba(200, 164, 93, 0.2);
  box-shadow: none;
}

/* ── Calendar scroll/grid ──────────────────────────────────────── */
.calendar-scroll {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.calendar-grid {
  display: flex;
  flex-direction: column;
  min-width: 280px;
}

.calendar-weekdays,
.calendar-week {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

/* ── Header row ────────────────────────────────────────────────── */
.calendar-header {
  text-align: center;
  font-weight: 700;
  font-size: 0.8rem;
  padding: 0.65rem 0.25rem;
  color: var(--gold-color, #c8a45d);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-bottom: 1px solid rgba(234, 220, 194, 0.1);
}

.calendar-header-short {
  display: none;
}

/* ── Day cells ─────────────────────────────────────────────────── */
.calendar-day-cell {
  min-height: 6.5rem;
  border: 1px solid rgba(234, 220, 194, 0.06);
  border-top: none;
  padding: 0.45rem 0.35rem 0.5rem;
  cursor: pointer;
  transition:
    background 0.2s ease,
    box-shadow 0.2s ease;
  background: rgba(234, 220, 194, 0.03);
  display: flex;
  flex-direction: column;
  align-items: center;
  color: rgba(234, 220, 194, 0.85);
}

.calendar-day-cell:hover {
  background: rgba(200, 164, 93, 0.08);
  z-index: 1;
  box-shadow: inset 0 0 0 1px rgba(200, 164, 93, 0.25);
}

.calendar-day-cell.other-month {
  background: rgba(234, 220, 194, 0.02);
  color: rgba(234, 220, 194, 0.38);
  cursor: default;
}

.calendar-day-cell.other-month:hover {
  background: rgba(234, 220, 194, 0.02);
  box-shadow: none;
}

.calendar-day-cell.read-only {
  cursor: default;
}

.calendar-day-cell.read-only:hover {
  background: rgba(234, 220, 194, 0.03);
  box-shadow: none;
}

.calendar-day-cell.read-only.has-schedules {
  cursor: pointer;
}

.calendar-day-cell.read-only.has-schedules:hover {
  background: rgba(200, 164, 93, 0.08);
  box-shadow: inset 0 0 0 1px rgba(200, 164, 93, 0.2);
}

.calendar-day-cell.has-schedules {
  background: rgba(200, 164, 93, 0.06);
}

/* ── Day number ────────────────────────────────────────────────── */
.day-number-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
  margin-bottom: 0.35rem;
}

.day-number {
  width: 1.75rem;
  height: 1.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  color: rgba(234, 220, 194, 0.85);
}

.calendar-day-cell.today .day-number {
  background: var(--gold-color, #c8a45d);
  color: #111420;
  border-radius: 50%;
  font-weight: 800;
  box-shadow: 0 0 12px rgba(200, 164, 93, 0.4);
}

/* ── Schedule dots (mobile) ────────────────────────────────────── */
.schedule-dots {
  display: none;
  justify-content: center;
  align-items: center;
  gap: 0.2rem;
  flex-wrap: wrap;
  width: 100%;
  margin-top: auto;
  padding-top: 0.15rem;
}

.schedule-dot {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 50%;
  flex-shrink: 0;
}

.schedule-dot-lesson {
  background: #4a8fe0;
}
.schedule-dot-practice {
  background: #4caf7d;
}
.schedule-dot-concert {
  background: var(--gold-color, #c8a45d);
}
.schedule-dot-exam {
  background: #e05050;
}
.schedule-dot-other {
  background: rgba(234, 220, 194, 0.45);
}
.schedule-dot-more {
  background: rgba(234, 220, 194, 0.3);
}

/* ── Schedule chips (desktop) ──────────────────────────────────── */
.schedule-chips {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  width: 100%;
  margin-top: 0.15rem;
}

.schedule-chip {
  font-size: 0.72rem;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  background: rgba(200, 164, 93, 0.12);
  color: rgba(234, 220, 194, 0.85);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: background 0.2s ease;
  text-align: left;
  border: 1px solid rgba(200, 164, 93, 0.08);
}

.schedule-chip:hover {
  background: rgba(200, 164, 93, 0.22);
}

.schedule-chip-readonly {
  cursor: pointer;
}

.schedule-chip-readonly:hover {
  background: rgba(200, 164, 93, 0.18);
}

.more-schedules {
  font-size: 0.7rem;
  color: rgba(234, 220, 194, 0.55);
  padding: 0.15rem 0.4rem;
  text-align: center;
}

/* ── Program badge in modal ────────────────────────────────────── */
.program-badge {
  background: rgba(234, 220, 194, 0.1) !important;
  border: 1px solid rgba(234, 220, 194, 0.1) !important;
  color: rgba(234, 220, 194, 0.85) !important;
  font-weight: 500;
  padding: 0.3rem 0.55rem;
  font-size: 0.75rem;
}

/* ── Header responsive ────────────────────────────────────── */
@media (max-width: 767.98px) {
  .schedules-header {
    padding: 1.15rem 1.15rem;
  }

  .schedules-header-top {
    flex-direction: column;
    gap: 1rem;
  }

  .schedules-header-actions {
    width: 100%;
  }

  .schedules-add-btn {
    width: 100%;
    justify-content: center;
    padding: 0.65rem 1.15rem;
    font-size: 0.85rem;
  }

  .schedules-title {
    font-size: 1.4rem;
  }

  .schedules-desc {
    font-size: 0.82rem;
    max-width: none;
  }
}

/* ── Responsive ────────────────────────────────────────────────── */
@media (max-width: 767.98px) {
  .calendar-header-full {
    display: none;
  }

  .calendar-header-short {
    display: inline;
  }

  .calendar-header {
    font-size: 0.7rem;
    padding: 0.5rem 0.15rem;
  }

  .calendar-day-cell {
    min-height: 3.75rem;
    padding: 0.3rem 0.15rem 0.35rem;
  }

  .day-number {
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.78rem;
  }

  .schedule-dots {
    display: flex;
  }

  .schedule-chips {
    display: none;
  }
}

@media (min-width: 768px) and (max-width: 991.98px) {
  .calendar-day-cell {
    min-height: 5.5rem;
  }

  .schedule-chip {
    font-size: 0.68rem;
    padding: 0.15rem 0.3rem;
  }
}

@media (min-width: 992px) {
  .calendar-day-cell {
    min-height: 7rem;
  }
}

:deep(.schedule-detail-modal) {
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 12px;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  box-shadow: 0 20px 48px rgba(8, 8, 14, 0.5);
  color: rgba(234, 220, 194, 0.85);
}

:deep(.schedule-detail-modal .modal-header) {
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.2), rgba(200, 164, 93, 0.08));
  border-bottom: 1px solid rgba(234, 220, 194, 0.08);
  border-radius: 11px 11px 0 0;
  padding: 1rem 1.25rem;
}

:deep(.schedule-detail-modal .modal-title) {
  color: var(--gold-color, #c8a45d);
  font-weight: 700;
}

:deep(.schedule-detail-modal .modal-body) {
  padding: 1.25rem;
}

:deep(.schedule-detail-modal .modal-footer) {
  border-top: 1px solid rgba(234, 220, 194, 0.08);
  padding: 0.85rem 1.25rem;
}

:deep(.schedule-detail-modal .btn-close) {
  filter: brightness(0) invert(0.8);
  opacity: 0.6;
}

:deep(.schedule-detail-modal .btn-close:hover) {
  opacity: 1;
}
</style>
