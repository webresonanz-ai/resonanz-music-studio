<template>
  <div class="fade-in-up">
    <!-- ══ PAGE HEADER ══════════════════════════════════════════════ -->
    <div class="members-header content-card mb-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
          <p class="members-eyebrow mb-1">Batavia Madrigal Singers</p>
          <h1 class="members-title mb-0">Members</h1>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2" @click="openCreate">
          <i class="bi bi-person-plus-fill"></i>
          <span>Add Member</span>
        </button>
      </div>

      <!-- Stats row -->
      <div class="stats-row mt-4">
        <div class="stat-pill">
          <span class="stat-value">{{ members.length }}</span>
          <span class="stat-label">Total</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-pill">
          <span class="stat-value text-success-custom">{{ totalActive }}</span>
          <span class="stat-label">Active</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-pill">
          <span class="stat-value" style="color: var(--muted-color)">{{ totalPassive }}</span>
          <span class="stat-label">Passive</span>
        </div>
        <div class="stat-divider d-none d-sm-block"></div>
        <template v-for="chip in allRoleChips" :key="chip.label">
          <div class="stat-pill d-none d-sm-flex">
            <span class="stat-value" :style="`color:${chip.color}`">{{ chip.count }}</span>
            <span class="stat-label">{{ chip.label }}</span>
          </div>
        </template>
      </div>
    </div>

    <!-- ══ FILTERS ═══════════════════════════════════════════════════ -->
    <div class="filters-bar mb-4">
      <!-- Row 1: search + view toggle + per-page -->
      <div class="filters-top-row">
        <div class="search-wrap" style="flex:1;min-width:0">
        <i class="bi bi-search search-icon"></i>
        <input
          v-model="search"
          type="text"
          class="search-input"
          placeholder="Search by name, nickname, or stage name…"
        />
        <button v-if="search" class="search-clear" @click="search = ''" aria-label="Clear search">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- View toggle + per-page -->
      <div class="view-controls">
        <div class="view-toggle" role="group" aria-label="View mode">
          <button class="view-btn" :class="{ active: viewMode === 'card' }"
            @click="viewMode = 'card'" title="Card view" aria-pressed="viewMode === 'card'">
            <i class="bi bi-grid-3x3-gap-fill"></i>
          </button>
          <button class="view-btn" :class="{ active: viewMode === 'list' }"
            @click="viewMode = 'list'" title="List view" aria-pressed="viewMode === 'list'">
            <i class="bi bi-list-ul"></i>
          </button>
        </div>
        <div class="perpage-wrap">
          <label class="perpage-label" :for="'perpage-select'">Show</label>
          <select id="perpage-select" v-model="perPage" class="perpage-select">
            <option :value="8">8</option>
            <option :value="16">16</option>
            <option :value="24">24</option>
            <option :value="48">48</option>
          </select>
        </div>
      </div>
      </div><!-- /.filters-top-row -->
      <div class="filter-chips">
        <button
          v-for="r in ['', ...ROLES]"
          :key="r"
          class="filter-chip"
          :class="{ active: filterRole === r }"
          @click="filterRole = r"
        >
          <span class="chip-dot" v-if="r" :style="`background:${roleColor(r)}`"></span>
          {{ r || "All Roles" }}
        </button>
      </div>
      <div class="filter-chips">
        <button
          class="filter-chip"
          :class="{ active: filterStatus === '' }"
          @click="filterStatus = ''"
        >
          All
        </button>
        <button
          class="filter-chip status-active"
          :class="{ active: filterStatus === 'active' }"
          @click="filterStatus = 'active'"
        >
          <span class="chip-dot bg-success-custom"></span>Active
        </button>
        <button
          class="filter-chip status-passive"
          :class="{ active: filterStatus === 'passive' }"
          @click="filterStatus = 'passive'"
        >
          <span class="chip-dot" style="background: var(--muted-color)"></span>Passive
        </button>
      </div>
    </div>

    <!-- ══ LOADING ════════════════════════════════════════════════════ -->
    <div v-if="loading" class="loading-state">
      <div class="loading-ring"></div>
      <p>Loading members…</p>
    </div>

    <template v-else>
      <!-- ══ EMPTY STATE ════════════════════════════════════════════ -->
      <div v-if="filteredMembers.length === 0" class="empty-state content-card">
        <div class="empty-icon"><i class="bi bi-people"></i></div>
        <h5 class="mt-3 mb-1">No members found</h5>
        <p class="text-muted mb-3">
          {{
            search || filterRole || filterStatus
              ? "Try adjusting your filters."
              : "Get started by adding your first member."
          }}
        </p>
        <button
          v-if="!search && !filterRole && !filterStatus"
          class="btn btn-primary btn-sm"
          @click="openCreate"
        >
          <i class="bi bi-person-plus-fill me-1"></i>Add First Member
        </button>
      </div>

      <!-- ══ CARD GRID ═════════════════════════════════════════════ -->
      <div v-if="viewMode === 'card'" class="members-grid">
        <article
          v-for="(member, idx) in pagedMembers"
          :key="member.id"
          class="member-card"
          :class="{ 'is-passive': member.status === 'passive' }"
          :style="`animation-delay:${idx * 0.04}s`"
        >
          <!-- Photo -->
          <div
            class="member-photo-wrap"
            @click="openDetail(member)"
            role="button"
            tabindex="0"
            :aria-label="`View details for ${member.name}`"
            @keydown.enter="openDetail(member)"
          >
            <img
              :src="member.avatar || defaultAvatar"
              :alt="member.name"
              class="member-photo"
              loading="lazy"
              @error="onImgError"
            />
            <div class="member-photo-overlay">
              <i class="bi bi-eye-fill"></i>
            </div>
            <!-- Role ribbon -->
            <div
              v-if="member.role"
              class="role-ribbon"
              :style="`background:${roleColor(member.role)}`"
            >
              {{ member.role }}
            </div>
          </div>

          <!-- Body -->
          <div class="member-body">
            <div class="member-name-row">
              <h6 class="member-name" :title="member.name">{{ member.name }}</h6>
              <span
                class="status-dot"
                :class="member.status === 'active' ? 'is-active' : 'is-passive'"
                :title="member.status"
              ></span>
            </div>
            <p class="member-sub">{{ member.stage_name || member.nickname || "–" }}</p>

            <div class="member-meta">
              <span class="meta-item" :title="'Section: ' + (member.section || 'N/A')">
                <i class="bi bi-music-note-beamed"></i>
                {{ member.section || "–" }}
              </span>
              <span class="meta-item" :title="'Year joined: ' + (member.year_join || 'N/A')">
                <i class="bi bi-calendar3"></i>
                {{ member.year_join || "–" }}
              </span>
              <span class="meta-item" :title="`${member.performances ?? 0} performances`">
                <i class="bi bi-star-fill" style="color: var(--gold-color)"></i>
                {{ member.performances ?? "0" }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="member-actions">
            <button class="action-btn" title="View details" @click="openDetail(member)">
              <i class="bi bi-eye"></i>
            </button>
            <button class="action-btn action-edit" title="Edit member" @click="openEdit(member)">
              <i class="bi bi-pencil"></i>
            </button>
            <button
              class="action-btn action-delete"
              title="Delete member"
              @click="confirmDelete(member)"
            >
              <i class="bi bi-trash3"></i>
            </button>
          </div>
        </article>
      </div>

      <!-- ══ LIST VIEW ══════════════════════════════════════════════ -->
      <div v-if="viewMode === 'list'" class="members-list">
        <div class="list-header">
          <span class="lh-avatar"></span>
          <span class="lh-name">Name</span>
          <span class="lh-role">Role</span>
          <span class="lh-section">Section</span>
          <span class="lh-year">Year</span>
          <span class="lh-shows">Shows</span>
          <span class="lh-status">Status</span>
          <span class="lh-actions"></span>
        </div>
        <div
          v-for="member in pagedMembers"
          :key="member.id"
          class="list-row"
          :class="{ 'is-passive': member.status === 'passive' }"
        >
          <span class="lh-avatar">
            <img :src="member.avatar || defaultAvatar" :alt="member.name"
              class="list-avatar" loading="lazy" @error="onImgError" />
          </span>
          <span class="lh-name">
            <span class="list-name" :title="member.name">{{ member.name }}</span>
            <span class="list-sub">{{ member.stage_name || member.nickname || '–' }}</span>
          </span>
          <span class="lh-role">
            <span v-if="member.role" class="list-role-dot"
              :style="`background:${roleColor(member.role)}`"></span>
            <span class="list-role-text">{{ member.role || '–' }}</span>
          </span>
          <span class="lh-section list-muted">{{ member.section || '–' }}</span>
          <span class="lh-year list-muted">{{ member.year_join || '–' }}</span>
          <span class="lh-shows list-muted">
            <i class="bi bi-star-fill" style="color:var(--gold-color);font-size:.7rem;margin-right:2px"></i>
            {{ member.performances ?? '0' }}
          </span>
          <span class="lh-status">
            <span class="list-status-badge" :class="member.status === 'active' ? 'badge-active' : 'badge-passive'">
              {{ member.status }}
            </span>
          </span>
          <span class="lh-actions">
            <button class="action-btn" title="View" @click="openDetail(member)"><i class="bi bi-eye"></i></button>
            <button class="action-btn action-edit" title="Edit" @click="openEdit(member)"><i class="bi bi-pencil"></i></button>
            <button class="action-btn action-delete" title="Delete" @click="confirmDelete(member)"><i class="bi bi-trash3"></i></button>
          </span>
        </div>
      </div>

      <!-- ══ PAGINATION ═════════════════════════════════════════════ -->
      <div v-if="totalPages > 1" class="pagination-bar">
        <span class="pagination-info">
          {{ paginationStart }}–{{ paginationEnd }} of {{ filteredMembers.length }}
        </span>
        <div class="pagination-controls">
          <button class="page-btn" :disabled="currentPage === 1" @click="goPage(1)" title="First page">
            <i class="bi bi-chevron-double-left"></i>
          </button>
          <button class="page-btn" :disabled="currentPage === 1" @click="goPage(currentPage - 1)" title="Previous page">
            <i class="bi bi-chevron-left"></i>
          </button>
          <button
            v-for="p in pageNumbers"
            :key="p"
            class="page-btn page-num"
            :class="{ active: p === currentPage, ellipsis: p === '…' }"
            :disabled="p === '…'"
            @click="p !== '…' && goPage(p)"
          >{{ p }}</button>
          <button class="page-btn" :disabled="currentPage === totalPages" @click="goPage(currentPage + 1)" title="Next page">
            <i class="bi bi-chevron-right"></i>
          </button>
          <button class="page-btn" :disabled="currentPage === totalPages" @click="goPage(totalPages)" title="Last page">
            <i class="bi bi-chevron-double-right"></i>
          </button>
        </div>
      </div>

      <!-- result summary (no pagination) -->
      <p v-else-if="filteredMembers.length > 0 && (search || filterRole || filterStatus)"
        class="result-count">
        Showing {{ filteredMembers.length }} of {{ members.length }} members
      </p>
    </template>

    <!-- ══════════════════════════════════════════════════════════════
             DETAIL MODAL
        ══════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="detailMember" class="modal-overlay" @click.self="closeDetail">
          <div
            class="modal-sheet modal-sheet--lg"
            role="dialog"
            aria-modal="true"
            :aria-label="`Detail of ${detailMember.name}`"
          >
            <!-- Profile banner -->
            <div class="detail-banner">
              <button class="modal-close-btn" @click="closeDetail" aria-label="Close">
                <i class="bi bi-x-lg"></i>
              </button>
              <div class="detail-avatar-wrap">
                <img
                  :src="detailMember.avatar || defaultAvatar"
                  :alt="detailMember.name"
                  class="detail-avatar"
                  @error="onImgError"
                />
              </div>
              <div class="detail-banner-info">
                <h4 class="detail-name">{{ detailMember.name }}</h4>
                <p class="detail-stagename" v-if="detailMember.stage_name">
                  "{{ detailMember.stage_name }}"
                </p>
                <p class="detail-stagename" v-else-if="detailMember.nickname">
                  {{ detailMember.nickname }}
                </p>
                <div class="d-flex gap-2 flex-wrap mt-2">
                  <span
                    class="detail-badge"
                    :class="detailMember.status === 'active' ? 'badge-active' : 'badge-passive'"
                  >
                    <span
                      class="status-dot"
                      :class="detailMember.status === 'active' ? 'is-active' : 'is-passive'"
                    ></span>
                    {{ detailMember.status }}
                  </span>
                  <span
                    v-if="detailMember.role"
                    class="detail-badge badge-role"
                    :style="`background:${roleColor(detailMember.role)}22;color:${roleColor(detailMember.role)};border-color:${roleColor(detailMember.role)}44`"
                  >
                    {{ detailMember.role }}
                  </span>
                  <span v-if="detailMember.performances" class="detail-badge badge-gold">
                    <i class="bi bi-star-fill me-1"></i>{{ detailMember.performances }} shows
                  </span>
                </div>
              </div>
            </div>

            <!-- Fields grid -->
            <div class="detail-body">
              <div class="detail-section">
                <p class="detail-section-title">Personal Info</p>
                <div class="detail-grid">
                  <div class="detail-field">
                    <span class="df-label">Nickname</span
                    ><span class="df-value">{{ detailMember.nickname || "–" }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Email</span>
                    <span class="df-value">
                      <a
                        v-if="detailMember.email"
                        :href="`mailto:${detailMember.email}`"
                        class="df-link"
                        >{{ detailMember.email }}</a
                      >
                      <template v-else>–</template>
                    </span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Phone</span
                    ><span class="df-value">{{ detailMember.phone || "–" }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Birth Place</span
                    ><span class="df-value">{{ detailMember.birth_place || "–" }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Birth Date</span
                    ><span class="df-value">{{ formatDate(detailMember.birth_date) }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Domicile</span
                    ><span class="df-value">{{ detailMember.domicile || "–" }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Field of Work</span
                    ><span class="df-value">{{ detailMember.field_of_work || "–" }}</span>
                  </div>
                </div>
              </div>
              <div class="detail-section">
                <p class="detail-section-title">Membership Info</p>
                <div class="detail-grid">
                  <div class="detail-field">
                    <span class="df-label">Section</span
                    ><span class="df-value">{{ detailMember.section || "–" }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Year Join</span
                    ><span class="df-value">{{ detailMember.year_join || "–" }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Join Date</span
                    ><span class="df-value">{{ formatDate(detailMember.join_date) }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Performances</span
                    ><span class="df-value">{{ detailMember.performances ?? "0" }}</span>
                  </div>
                </div>
              </div>
              <div class="detail-section">
                <p class="detail-section-title">Record</p>
                <div class="detail-grid">
                  <div class="detail-field">
                    <span class="df-label">Created At</span
                    ><span class="df-value">{{ formatDateTime(detailMember.created_at) }}</span>
                  </div>
                  <div class="detail-field">
                    <span class="df-label">Updated At</span
                    ><span class="df-value">{{ formatDateTime(detailMember.updated_at) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer-row">
              <button
                class="btn btn-outline-primary btn-sm"
                @click="
                  openEdit(detailMember);
                  closeDetail();
                "
              >
                <i class="bi bi-pencil me-1"></i>Edit Member
              </button>
              <button class="btn btn-secondary btn-sm" @click="closeDetail">Close</button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════
             CREATE / EDIT MODAL
        ══════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
          <div
            class="modal-sheet modal-sheet--lg"
            role="dialog"
            aria-modal="true"
            :aria-label="isEditing ? 'Edit Member' : 'Add Member'"
          >
            <div class="modal-header-row">
              <div class="d-flex align-items-center gap-2">
                <div class="modal-icon-wrap">
                  <i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-person-plus-fill'"></i>
                </div>
                <div>
                  <h5 class="modal-sheet-title mb-0">
                    {{ isEditing ? "Edit Member" : "Add Member" }}
                  </h5>
                  <p class="modal-sheet-sub mb-0">
                    {{ isEditing ? "Update member information" : "Fill in the details below" }}
                  </p>
                </div>
              </div>
              <button class="modal-close-btn" @click="closeForm" aria-label="Close">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="modal-body-scroll">
              <!-- Error banner -->
              <div v-if="formError" class="form-error-banner">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ formError }}
              </div>

              <form @submit.prevent="submitForm" novalidate>
                <!-- Section: Identity -->
                <p class="form-section-label">Identity</p>
                <div class="form-grid">
                  <div class="form-group" :class="{ 'has-error': v$.name }">
                    <label class="form-lbl">Full Name <span class="req">*</span></label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="form-inp"
                      placeholder="Full legal name"
                    />
                    <span v-if="v$.name" class="form-hint error">{{ v$.name }}</span>
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Nickname</label>
                    <input
                      v-model="form.nickname"
                      type="text"
                      class="form-inp"
                      placeholder="e.g. Andi"
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Stage Name</label>
                    <input
                      v-model="form.stage_name"
                      type="text"
                      class="form-inp"
                      placeholder="Performing name"
                    />
                  </div>
                  <div class="form-group" :class="{ 'has-error': v$.email }">
                    <label class="form-lbl">Email</label>
                    <input
                      v-model="form.email"
                      type="email"
                      class="form-inp"
                      placeholder="email@example.com"
                    />
                    <span v-if="v$.email" class="form-hint error">{{ v$.email }}</span>
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Phone</label>
                    <input v-model="form.phone" type="text" class="form-inp" placeholder="+62…" />
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Field of Work</label>
                    <input
                      v-model="form.field_of_work"
                      type="text"
                      class="form-inp"
                      placeholder="Occupation"
                    />
                  </div>
                </div>

                <!-- Section: Background -->
                <p class="form-section-label mt-4">Background</p>
                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-lbl">Birth Place</label>
                    <input
                      v-model="form.birth_place"
                      type="text"
                      class="form-inp"
                      placeholder="City of birth"
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Birth Date</label>
                    <input v-model="form.birth_date" type="date" class="form-inp" />
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Domicile</label>
                    <input
                      v-model="form.domicile"
                      type="text"
                      class="form-inp"
                      placeholder="Current city"
                    />
                  </div>
                </div>

                <!-- Section: Membership -->
                <p class="form-section-label mt-4">Membership</p>
                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-lbl">Voice Role</label>
                    <div class="role-picker">
                      <button
                        v-for="r in ROLES"
                        :key="r"
                        type="button"
                        class="role-option"
                        :class="{ active: form.role === r }"
                        :style="
                          form.role === r
                            ? `background:${roleColor(r)}22;border-color:${roleColor(r)};color:${roleColor(r)}`
                            : ''
                        "
                        @click="form.role = form.role === r ? '' : r"
                      >
                        {{ r }}
                      </button>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Status</label>
                    <div class="status-toggle">
                      <button
                        type="button"
                        class="status-option"
                        :class="{ active: form.status === 'active' }"
                        @click="form.status = 'active'"
                      >
                        <span class="chip-dot bg-success-custom"></span>Active
                      </button>
                      <button
                        type="button"
                        class="status-option"
                        :class="{ active: form.status === 'passive' }"
                        @click="form.status = 'passive'"
                      >
                        <span class="chip-dot" style="background: var(--muted-color)"></span>Passive
                      </button>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Section</label>
                    <input
                      v-model="form.section"
                      type="text"
                      class="form-inp"
                      placeholder="e.g. Soprano I"
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Year Join</label>
                    <input
                      v-model="form.year_join"
                      type="number"
                      class="form-inp"
                      min="1900"
                      max="2100"
                      placeholder="YYYY"
                    />
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Join Date</label>
                    <input v-model="form.join_date" type="date" class="form-inp" />
                  </div>
                  <div class="form-group">
                    <label class="form-lbl">Performances</label>
                    <input
                      v-model="form.performances"
                      type="number"
                      class="form-inp"
                      min="0"
                      placeholder="0"
                    />
                  </div>
                </div>

                <!-- Section: Photo -->
                <p class="form-section-label mt-4">Photo</p>
                <div class="avatar-row">
                  <img
                    :src="form.avatar || defaultAvatar"
                    alt="Preview"
                    class="avatar-preview"
                    @error="onImgError"
                  />
                  <div class="flex-fill">
                    <label class="form-lbl">Avatar URL</label>
                    <input
                      v-model="form.avatar"
                      type="url"
                      class="form-inp"
                      placeholder="https://…"
                    />
                    <span class="form-hint">Leave blank to use the default photo.</span>
                  </div>
                </div>
              </form>
            </div>

            <div class="modal-footer-row">
              <button class="btn btn-secondary" @click="closeForm" :disabled="submitting">
                Cancel
              </button>
              <button class="btn btn-primary" @click="submitForm" :disabled="submitting">
                <span
                  v-if="submitting"
                  class="spinner-border spinner-border-sm me-1"
                  role="status"
                ></span>
                {{ isEditing ? "Save Changes" : "Add Member" }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════
             DELETE CONFIRM MODAL
        ══════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="deleteTarget" class="modal-overlay" @click.self="cancelDelete">
          <div
            class="modal-sheet modal-sheet--sm"
            role="dialog"
            aria-modal="true"
            aria-label="Confirm delete"
          >
            <div class="delete-icon-wrap">
              <i class="bi bi-trash3-fill"></i>
            </div>
            <h5 class="text-center mt-3 mb-1">Delete Member</h5>
            <p class="text-center text-muted mb-4" style="font-size: 0.95rem">
              You're about to remove <strong>{{ deleteTarget.name }}</strong> permanently. This
              cannot be undone.
            </p>
            <div class="d-flex gap-2 justify-content-center">
              <button
                class="btn btn-secondary"
                style="min-width: 100px"
                @click="cancelDelete"
                :disabled="deleting"
              >
                Cancel
              </button>
              <button
                class="btn btn-danger"
                style="min-width: 100px"
                @click="doDelete"
                :disabled="deleting"
              >
                <span
                  v-if="deleting"
                  class="spinner-border spinner-border-sm me-1"
                  role="status"
                ></span>
                Delete
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
  <!-- /.fade-in-up -->
</template>

<script>
import { mapState, mapActions } from "pinia";
import { useBmsStore } from "../../stores/api";

const DEFAULT_AVATAR =
  "https://voca-land.sgp1.cdn.digitaloceanspaces.com/0/1757684222527/9465e2e8.jpg";
const ROLES = ["Sopran", "Alto", "Tenor", "Bass"];
const ROLE_COLORS = { Sopran: "#c0392b", Alto: "#b8860b", Tenor: "#1a6fa0", Bass: "#2e4057" };

const emptyForm = () => ({
  name: "",
  nickname: "",
  email: "",
  stage_name: "",
  birth_place: "",
  birth_date: "",
  domicile: "",
  phone: "",
  year_join: "",
  field_of_work: "",
  role: "",
  section: "",
  join_date: "",
  status: "active",
  performances: 0,
  avatar: "",
});

export default {
  name: "Members",

  computed: {
    ...mapState(useBmsStore, ["members"]),

    filteredMembers() {
      const q = this.search.trim().toLowerCase();
      return this.members.filter((m) => {
        const matchSearch =
          !q ||
          (m.name || "").toLowerCase().includes(q) ||
          (m.nickname || "").toLowerCase().includes(q) ||
          (m.stage_name || "").toLowerCase().includes(q);
        const matchRole = !this.filterRole || m.role === this.filterRole;
        const matchStatus = !this.filterStatus || m.status === this.filterStatus;
        return matchSearch && matchRole && matchStatus;
      });
    },

    totalActive() {
      return this.members.filter((m) => m.status === "active").length;
    },
    totalPassive() {
      return this.members.filter((m) => m.status === "passive").length;
    },

    allRoleChips() {
      return ROLES.map((r) => ({
        label: r,
        count: this.members.filter((m) => m.role === r).length,
        color: ROLE_COLORS[r] || "#555",
      })).filter((c) => c.count > 0);
    },

    isEditing() {
      return this.editingId !== null;
    },

    pagedMembers() {
      const start = (this.currentPage - 1) * this.perPage
      return this.filteredMembers.slice(start, start + this.perPage)
    },

    totalPages() {
      return Math.max(1, Math.ceil(this.filteredMembers.length / this.perPage))
    },

    paginationStart() {
      return this.filteredMembers.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1
    },

    paginationEnd() {
      return Math.min(this.currentPage * this.perPage, this.filteredMembers.length)
    },

    pageNumbers() {
      const total = this.totalPages
      const cur   = this.currentPage
      if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
      const pages = []
      if (cur <= 4) {
        pages.push(1, 2, 3, 4, 5, '…', total)
      } else if (cur >= total - 3) {
        pages.push(1, '…', total - 4, total - 3, total - 2, total - 1, total)
      } else {
        pages.push(1, '…', cur - 1, cur, cur + 1, '…', total)
      }
      return pages
    },
  },

  data() {
    return {
      ROLES,
      defaultAvatar: DEFAULT_AVATAR,
      loading: false,
      viewMode: 'card',
      search: "",
      filterRole: "",
      filterStatus: "",
      currentPage: 1,
      perPage: 16,
      detailMember: null,
      showForm: false,
      form: emptyForm(),
      editingId: null,
      formError: null,
      submitting: false,
      v$: {},
      deleteTarget: null,
      deleting: false,
    };
  },

  watch: {
    search()       { this.currentPage = 1 },
    filterRole()   { this.currentPage = 1 },
    filterStatus() { this.currentPage = 1 },
    perPage()      { this.currentPage = 1 },
  },

  async mounted() {
    this.loading = true;
    try {
      await this.fetchMembers();
    } finally {
      this.loading = false;
    }
  },

  methods: {
    ...mapActions(useBmsStore, ["fetchMembers", "createMember", "updateMember", "deleteMember"]),

    roleColor(role) {
      return ROLE_COLORS[role] || "#888";
    },

    goPage(p) {
      this.currentPage = Math.min(Math.max(1, p), this.totalPages)
    },

    formatDate(date) {
      if (!date) return "–";
      return new Date(date).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },
    formatDateTime(dt) {
      if (!dt) return "–";
      return new Date(dt).toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    onImgError(e) {
      e.target.src = DEFAULT_AVATAR;
    },

    lockBody() {
      document.body.style.overflow = "hidden";
    },
    unlockBody() {
      document.body.style.overflow = "";
    },

    openDetail(member) {
      this.detailMember = member;
      this.lockBody();
    },
    closeDetail() {
      this.detailMember = null;
      this.unlockBody();
    },

    openCreate() {
      this.editingId = null;
      this.form = emptyForm();
      this.formError = null;
      this.v$ = {};
      this.showForm = true;
      this.lockBody();
    },
    openEdit(member) {
      this.editingId = member.id;
      this.form = {
        name: member.name || "",
        nickname: member.nickname || "",
        email: member.email || "",
        stage_name: member.stage_name || "",
        birth_place: member.birth_place || "",
        birth_date: member.birth_date || "",
        domicile: member.domicile || "",
        phone: member.phone || "",
        year_join: member.year_join || "",
        field_of_work: member.field_of_work || "",
        role: member.role || "",
        section: member.section || "",
        join_date: member.join_date || "",
        status: member.status || "active",
        performances: member.performances ?? 0,
        avatar: member.avatar || "",
      };
      this.formError = null;
      this.v$ = {};
      this.showForm = true;
      this.lockBody();
    },
    closeForm() {
      this.showForm = false;
      this.formError = null;
      this.unlockBody();
    },

    validateForm() {
      const e = {};
      if (!this.form.name.trim()) e.name = "Name is required";
      if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email))
        e.email = "Invalid email address";
      this.v$ = e;
      return Object.keys(e).length === 0;
    },

    async submitForm() {
      if (!this.validateForm()) return;
      this.submitting = true;
      this.formError = null;
      try {
        const payload = { ...this.form };
        if (!payload.avatar) payload.avatar = DEFAULT_AVATAR;
        if (this.isEditing) await this.updateMember(this.editingId, payload);
        else await this.createMember(payload);
        this.closeForm();
      } catch (err) {
        this.formError = err.message || "Something went wrong. Please try again.";
      } finally {
        this.submitting = false;
      }
    },

    confirmDelete(member) {
      this.deleteTarget = member;
      this.lockBody();
    },
    cancelDelete() {
      this.deleteTarget = null;
      this.unlockBody();
    },
    async doDelete() {
      this.deleting = true;
      try {
        await this.deleteMember(this.deleteTarget.id);
        this.cancelDelete();
      } catch (err) {
        alert("Delete failed: " + (err.message || "Unknown error"));
        this.deleting = false;
      }
    },
  },
};
</script>

<style scoped>
/* ── Design tokens (mirror global vars) ──────────────────────────── */
:root {
  --gold: #c8a45d;
  --accent: #7f2432;
  --ink: #191b24;
  --muted: #6f6a61;
  --surface: #fffdf8;
  --hairline: rgba(34, 29, 20, 0.12);
  --radius: 8px;
}

/* ── Page header ─────────────────────────────────────────────────── */
.members-eyebrow {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold-color, #c8a45d);
}

.members-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink-color, #191b24);
  letter-spacing: -0.01em;
}

.members-header {
  /* override the content-card hover lift for the header */
  transition: none;
}

.members-header:hover {
  transform: none;
  box-shadow: var(--shadow-soft, 0 18px 48px rgba(19, 18, 16, 0.1));
}

/* Stats row */
.stats-row {
  display: flex;
  align-items: center;
  gap: 0;
  flex-wrap: wrap;
  border-top: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  padding-top: 1rem;
}

.stat-pill {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 1.25rem 0 0;
  min-width: 60px;
}

.stat-divider {
  width: 1px;
  height: 2rem;
  background: var(--hairline-color, rgba(34, 29, 20, 0.12));
  margin: 0 1.25rem 0 0;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1.1;
  color: var(--ink-color, #191b24);
}

.stat-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--muted-color, #6f6a61);
  margin-top: 2px;
}

.text-success-custom {
  color: #4a7c59 !important;
}

.bg-success-custom {
  background: #4a7c59 !important;
}

/* ── Filters bar ─────────────────────────────────────────────────── */
.filters-bar {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  color: var(--muted-color, #6f6a61);
  font-size: 0.9rem;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.6rem 2.5rem 0.6rem 2.4rem;
  border: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  border-radius: var(--radius-md, 8px);
  background: rgba(255, 253, 248, 0.9);
  color: var(--ink-color, #191b24);
  font-size: 0.9rem;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: var(--gold-color, #c8a45d);
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.18);
}

.search-clear {
  position: absolute;
  right: 0.7rem;
  border: 0;
  background: transparent;
  color: var(--muted-color, #6f6a61);
  cursor: pointer;
  padding: 0.1rem 0.3rem;
  font-size: 0.8rem;
  border-radius: 4px;
  transition: color 0.15s;
}

.search-clear:hover {
  color: var(--accent-color, #7f2432);
}

.filter-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.85rem;
  border: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  border-radius: 999px;
  background: rgba(255, 253, 248, 0.8);
  color: var(--muted-color, #6f6a61);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
}

.filter-chip:hover {
  border-color: var(--gold-color, #c8a45d);
  color: var(--ink-color, #191b24);
  background: rgba(200, 164, 93, 0.1);
}

.filter-chip.active {
  border-color: var(--gold-color, #c8a45d);
  background: rgba(200, 164, 93, 0.15);
  color: var(--ink-color, #191b24);
}

.chip-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ── Loading ─────────────────────────────────────────────────────── */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 0;
  color: var(--muted-color, #6f6a61);
  gap: 1rem;
}

.loading-ring {
  width: 42px;
  height: 42px;
  border: 3px solid rgba(200, 164, 93, 0.2);
  border-top-color: var(--gold-color, #c8a45d);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ── Empty state ─────────────────────────────────────────────────── */
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
  color: var(--gold-color, #c8a45d);
}

/* ── Member grid ─────────────────────────────────────────────────── */
.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 1.25rem;
}

.member-card {
  position: relative;
  border-radius: var(--radius-md, 8px);
  border: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  background: rgba(255, 253, 248, 0.96);
  box-shadow: 0 2px 12px rgba(19, 18, 16, 0.06);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  animation: fadeInUp 0.4s ease-out both;
  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease,
    border-color 0.22s ease;
}

.member-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(19, 18, 16, 0.13);
  border-color: rgba(200, 164, 93, 0.4);
}

.member-card.is-passive {
  opacity: 0.62;
  filter: grayscale(0.35);
}

.member-card.is-passive:hover {
  opacity: 0.85;
  filter: none;
}

/* Photo */
.member-photo-wrap {
  position: relative;
  height: 190px;
  overflow: hidden;
  background: #ede8df;
  cursor: pointer;
  flex-shrink: 0;
}

.member-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top center;
  transition: transform 0.35s ease;
}

.member-photo-wrap:hover .member-photo {
  transform: scale(1.04);
}

.member-photo-overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(16, 19, 31, 0.45);
  color: #fff;
  font-size: 1.4rem;
  opacity: 0;
  transition: opacity 0.22s ease;
}

.member-photo-wrap:hover .member-photo-overlay {
  opacity: 1;
}

.role-ribbon {
  position: absolute;
  top: 10px;
  left: 0;
  padding: 0.18rem 0.75rem 0.18rem 0.6rem;
  border-radius: 0 999px 999px 0;
  font-size: 0.7rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Body */
.member-body {
  padding: 0.85rem 0.9rem 0.5rem;
  flex: 1;
}

.member-name-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.4rem;
}

.member-name {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--ink-color, #191b24);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.member-sub {
  font-size: 0.78rem;
  font-style: italic;
  color: var(--muted-color, #6f6a61);
  margin: 0.1rem 0 0.6rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-dot.is-active {
  background: #4a7c59;
  box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.18);
}

.status-dot.is-passive {
  background: #aaa;
}

.member-meta {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.73rem;
  color: var(--muted-color, #6f6a61);
}

.meta-item i {
  font-size: 0.7rem;
}

/* Actions */
.member-actions {
  display: flex;
  border-top: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  padding: 0.45rem 0.65rem;
  gap: 0.35rem;
}

.action-btn {
  flex: 1;
  border: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  border-radius: 6px;
  background: transparent;
  color: var(--muted-color, #6f6a61);
  font-size: 0.82rem;
  padding: 0.32rem;
  cursor: pointer;
  transition: all 0.18s ease;
  line-height: 1;
}

.action-btn:hover {
  border-color: var(--ink-color, #191b24);
  color: var(--ink-color, #191b24);
  background: rgba(25, 27, 36, 0.06);
}

.action-btn.action-edit:hover {
  border-color: var(--gold-color, #c8a45d);
  color: var(--gold-color, #c8a45d);
  background: rgba(200, 164, 93, 0.08);
}

.action-btn.action-delete:hover {
  border-color: #c0392b;
  color: #c0392b;
  background: rgba(192, 57, 43, 0.07);
}

.result-count {
  text-align: center;
  margin-top: 1.25rem;
  font-size: 0.8rem;
  color: var(--muted-color, #6f6a61);
}

/* ── Modal system ────────────────────────────────────────────────── */
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
  border: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  box-shadow:
    0 32px 72px rgba(10, 10, 15, 0.36),
    0 0 0 1px rgba(200, 164, 93, 0.1);
  width: 100%;
  max-height: calc(100vh - 3rem);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-sheet--sm {
  max-width: 400px;
  padding: 2rem;
}

.modal-sheet--lg {
  max-width: 720px;
}

.modal-close-btn {
  border: 0;
  background: rgba(34, 29, 20, 0.06);
  border-radius: 8px;
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  cursor: pointer;
  color: var(--muted-color, #6f6a61);
  font-size: 0.85rem;
  transition:
    background 0.15s,
    color 0.15s;
  flex-shrink: 0;
}

.modal-close-btn:hover {
  background: rgba(34, 29, 20, 0.12);
  color: var(--ink-color, #191b24);
}

/* Detail modal */
.detail-banner {
  position: relative;
  background: linear-gradient(135deg, #171b27 0%, #222838 60%, #11141f 100%);
  padding: 1.75rem;
  display: flex;
  align-items: flex-end;
  gap: 1.25rem;
}

.detail-banner::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(200, 164, 93, 0.12), transparent 50%);
  pointer-events: none;
}

.modal-close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 2;
  color: rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.1);
}

.modal-close-btn:hover {
  background: rgba(255, 255, 255, 0.18);
  color: #fff;
}

.detail-avatar-wrap {
  flex-shrink: 0;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  border: 3px solid rgba(200, 164, 93, 0.5);
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
  z-index: 1;
}

.detail-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top;
}

.detail-banner-info {
  z-index: 1;
  min-width: 0;
}

.detail-name {
  color: #fff;
  font-weight: 800;
  font-size: 1.25rem;
  margin: 0;
  letter-spacing: -0.01em;
}

.detail-stagename {
  color: rgba(255, 255, 255, 0.6);
  font-style: italic;
  font-size: 0.85rem;
  margin: 0.2rem 0 0;
}

.detail-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
  border: 1px solid transparent;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.badge-active {
  background: rgba(74, 124, 89, 0.2);
  color: #6aad7e;
  border-color: rgba(74, 124, 89, 0.35);
}

.badge-passive {
  background: rgba(180, 180, 180, 0.15);
  color: #aaa;
  border-color: rgba(180, 180, 180, 0.3);
}

.badge-gold {
  background: rgba(200, 164, 93, 0.15);
  color: #c8a45d;
  border-color: rgba(200, 164, 93, 0.35);
}

.detail-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.detail-section {
}

.detail-section-title {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold-color, #c8a45d);
  margin: 0 0 0.75rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.75rem 1rem;
}

.detail-field {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.df-label {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--muted-color, #6f6a61);
}

.df-value {
  font-size: 0.88rem;
  color: var(--ink-color, #191b24);
  font-weight: 500;
}

.df-link {
  color: var(--accent-color, #7f2432);
  text-decoration: none;
}

.df-link:hover {
  text-decoration: underline;
}

/* Form modal */
.modal-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  flex-shrink: 0;
}

.modal-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(200, 164, 93, 0.2), rgba(127, 36, 50, 0.15));
  border: 1px solid rgba(200, 164, 93, 0.25);
  display: grid;
  place-items: center;
  font-size: 1.1rem;
  color: var(--gold-color, #c8a45d);
}

.modal-sheet-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--ink-color, #191b24);
}

.modal-sheet-sub {
  font-size: 0.78rem;
  color: var(--muted-color, #6f6a61);
}

.modal-body-scroll {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.form-error-banner {
  display: flex;
  align-items: center;
  padding: 0.65rem 0.9rem;
  border-radius: 8px;
  background: rgba(192, 57, 43, 0.08);
  border: 1px solid rgba(192, 57, 43, 0.25);
  color: #c0392b;
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}

.form-section-label {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold-color, #c8a45d);
  margin: 0 0 0.75rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.9rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.form-group.has-error .form-inp {
  border-color: #c0392b;
}

.form-lbl {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--ink-color, #191b24);
}

.req {
  color: #c0392b;
}

.form-inp {
  padding: 0.55rem 0.75rem;
  border: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  border-radius: 7px;
  background: rgba(255, 253, 248, 0.8);
  color: var(--ink-color, #191b24);
  font-size: 0.875rem;
  transition:
    border-color 0.18s,
    box-shadow 0.18s;
}

.form-inp:focus {
  outline: none;
  border-color: var(--gold-color, #c8a45d);
  box-shadow: 0 0 0 3px rgba(200, 164, 93, 0.16);
  background: #fffdf8;
}

.form-hint {
  font-size: 0.72rem;
  color: var(--muted-color, #6f6a61);
}

.form-hint.error {
  color: #c0392b;
}

/* Role picker & status toggle */
.role-picker,
.status-toggle {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.role-option,
.status-option {
  padding: 0.3rem 0.85rem;
  border: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  border-radius: 999px;
  background: rgba(255, 253, 248, 0.8);
  color: var(--muted-color, #6f6a61);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.role-option:hover,
.status-option:hover {
  border-color: var(--gold-color, #c8a45d);
  color: var(--ink-color, #191b24);
}

.status-option.active {
  border-color: var(--gold-color, #c8a45d);
  background: rgba(200, 164, 93, 0.12);
  color: var(--ink-color, #191b24);
}

/* Avatar row */
.avatar-row {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
}

.avatar-preview {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  flex-shrink: 0;
}

/* Delete modal */
.delete-icon-wrap {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(192, 57, 43, 0.1);
  border: 1px solid rgba(192, 57, 43, 0.2);
  display: grid;
  place-items: center;
  margin: 0 auto;
  font-size: 1.5rem;
  color: #c0392b;
}

/* Modal footer row */
.modal-footer-row {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--hairline-color, rgba(34, 29, 20, 0.12));
  flex-shrink: 0;
  background: rgba(245, 241, 233, 0.5);
}

/* Modal animation */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.22s ease;
}

.modal-enter-active .modal-sheet,
.modal-leave-active .modal-sheet {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-sheet,
.modal-leave-to .modal-sheet {
  opacity: 0;
  transform: translateY(16px) scale(0.98);
}

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 767px) {
  .members-title {
    font-size: 1.5rem;
  }

  .members-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.85rem;
  }

  .member-photo-wrap {
    height: 160px;
  }

  .modal-overlay {
    padding: 0;
    align-items: flex-end;
  }

  .modal-sheet {
    border-radius: 16px 16px 0 0;
    max-height: 92vh;
  }

  .modal-sheet--sm {
    padding: 1.5rem 1.25rem 2rem;
    max-width: 100%;
  }

  .stat-pill {
    padding: 0 0.75rem 0 0;
  }

  .stat-divider {
    margin: 0 0.75rem 0 0;
  }

  .filters-top-row { flex-direction: column; align-items: stretch; }
  .view-controls    { justify-content: flex-end; }

  /* List view collapses on mobile */
  .list-header { display: none; }
  .list-row {
    grid-template-columns: 44px 1fr auto;
    grid-template-rows: auto auto;
    gap: 0.3rem 0.6rem;
    padding: 0.75rem;
  }
  .lh-role, .lh-section, .lh-year, .lh-shows { display: none; }
  .lh-name  { grid-column: 2; grid-row: 1; }
  .lh-status { grid-column: 3; grid-row: 1; align-self: center; }
  .lh-actions { grid-column: 2 / -1; grid-row: 2; justify-content: flex-start; }

  .pagination-bar { flex-direction: column; gap: 0.6rem; align-items: center; }
}

/* ── View toggle ─────────────────────────────────────────────────── */
.filters-top-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.view-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}
.view-toggle {
    display: flex;
    border: 1px solid var(--hairline-color, rgba(34,29,20,.12));
    border-radius: 8px;
    overflow: hidden;
}
.view-btn {
    width: 36px;
    height: 34px;
    border: 0;
    background: rgba(255,253,248,0.8);
    color: var(--muted-color, #6f6a61);
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.16s, color 0.16s;
    display: grid;
    place-items: center;
}
.view-btn + .view-btn { border-left: 1px solid var(--hairline-color, rgba(34,29,20,.12)); }
.view-btn:hover { background: rgba(200,164,93,0.1); color: var(--ink-color,#191b24); }
.view-btn.active {
    background: linear-gradient(135deg, rgba(200,164,93,0.2), rgba(127,36,50,0.12));
    color: var(--ink-color, #191b24);
    font-weight: 700;
}
.perpage-wrap {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.perpage-label {
    font-size: 0.75rem;
    color: var(--muted-color, #6f6a61);
    font-weight: 600;
    white-space: nowrap;
}
.perpage-select {
    padding: 0.3rem 1.8rem 0.3rem 0.6rem;
    border: 1px solid var(--hairline-color, rgba(34,29,20,.12));
    border-radius: 7px;
    background: rgba(255,253,248,0.9) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%236f6a61' d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right 0.5rem center;
    appearance: none;
    font-size: 0.82rem;
    color: var(--ink-color, #191b24);
    cursor: pointer;
    transition: border-color 0.16s;
}
.perpage-select:focus {
    outline: none;
    border-color: var(--gold-color, #c8a45d);
    box-shadow: 0 0 0 3px rgba(200,164,93,0.15);
}

/* ── List view ───────────────────────────────────────────────────── */
.members-list {
    border: 1px solid var(--hairline-color, rgba(34,29,20,.12));
    border-radius: var(--radius-md, 8px);
    overflow: hidden;
    background: rgba(255,253,248,0.97);
}
.list-header {
    display: grid;
    grid-template-columns: 48px 1fr 90px 110px 70px 60px 90px 110px;
    align-items: center;
    padding: 0 1rem;
    height: 38px;
    background: linear-gradient(135deg, rgba(200,164,93,0.08), rgba(127,36,50,0.04));
    border-bottom: 1px solid var(--hairline-color, rgba(34,29,20,.12));
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted-color, #6f6a61);
    gap: 0.5rem;
}
.list-row {
    display: grid;
    grid-template-columns: 48px 1fr 90px 110px 70px 60px 90px 110px;
    align-items: center;
    padding: 0.55rem 1rem;
    gap: 0.5rem;
    border-bottom: 1px solid var(--hairline-color, rgba(34,29,20,.08));
    transition: background 0.15s;
}
.list-row:last-child { border-bottom: 0; }
.list-row:hover { background: rgba(200,164,93,0.05); }
.list-row.is-passive { opacity: 0.55; filter: grayscale(0.3); }
.list-row.is-passive:hover { opacity: 0.85; filter: none; }

.lh-avatar { display: flex; align-items: center; }
.list-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    object-position: top;
    border: 1px solid var(--hairline-color, rgba(34,29,20,.12));
}
.lh-name   { display: flex; flex-direction: column; min-width: 0; }
.list-name { font-size: 0.875rem; font-weight: 700; color: var(--ink-color,#191b24); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.list-sub  { font-size: 0.72rem; color: var(--muted-color,#6f6a61); font-style:italic; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.lh-role   { display: flex; align-items: center; gap: 0.35rem; }
.list-role-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.list-role-text { font-size: 0.8rem; font-weight: 600; color: var(--ink-color,#191b24); }
.list-muted { font-size: 0.8rem; color: var(--muted-color,#6f6a61); }
.lh-status  { display: flex; align-items: center; }
.list-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.18rem 0.6rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    border: 1px solid transparent;
}
.list-status-badge.badge-active  { background: rgba(74,124,89,0.1); color:#4a7c59; border-color:rgba(74,124,89,0.25); }
.list-status-badge.badge-passive { background: rgba(180,180,180,0.12); color:#888; border-color:rgba(180,180,180,0.25); }
.lh-actions { display: flex; align-items: center; gap: 0.3rem; justify-content: flex-end; }

/* ── Pagination ──────────────────────────────────────────────────── */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.5rem;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.pagination-info {
    font-size: 0.8rem;
    color: var(--muted-color, #6f6a61);
    font-weight: 600;
}
.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.page-btn {
    min-width: 34px;
    height: 34px;
    padding: 0 0.5rem;
    border: 1px solid var(--hairline-color, rgba(34,29,20,.12));
    border-radius: 7px;
    background: rgba(255,253,248,0.9);
    color: var(--ink-color, #191b24);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-grid;
    place-items: center;
    transition: all 0.16s ease;
}
.page-btn:hover:not(:disabled) {
    border-color: var(--gold-color, #c8a45d);
    background: rgba(200,164,93,0.1);
}
.page-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.page-btn.active {
    background: linear-gradient(135deg, rgba(200,164,93,0.25), rgba(127,36,50,0.15));
    border-color: var(--gold-color, #c8a45d);
    color: var(--ink-color, #191b24);
    font-weight: 800;
    box-shadow: 0 2px 8px rgba(200,164,93,0.2);
}
.page-btn.ellipsis {
    border-color: transparent;
    background: transparent;
    cursor: default;
    color: var(--muted-color, #6f6a61);
}
</style>
