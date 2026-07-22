<template>
  <div class="fade-in-up">
    <div class="news-header">
      <div class="news-header-top">
        <div class="news-header-text">
          <p class="news-breadcrumb">TRMS News</p>
          <h1 class="news-title">Latest News</h1>
          <p class="news-desc">
            Stay updated with the latest announcements across all programs.
          </p>
        </div>
      </div>
    </div>

    <div class="content-card bg-dark">
      <div class="news-toolbar">
        <div class="news-toolbar-info">
          <h2 class="news-toolbar-heading">All News</h2>
          <p class="news-toolbar-count">{{ filteredNews.length }} article{{ filteredNews.length === 1 ? '' : 's' }}</p>
        </div>
        <div class="news-filter-pills">
          <button
            v-for="p in filterOptions"
            :key="p.id"
            class="news-pill"
            :class="{ 'news-pill--active': filterProgram === p.id }"
            @click="filterProgram = p.id"
          >
            {{ p.label }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="py-5 text-center">
        <div class="spinner-border text-warning mb-3" role="status"></div>
        <div class="text-champagne-muted">Loading news...</div>
      </div>

      <div v-else-if="filteredNews.length === 0" class="news-empty">
        <div class="news-empty-icon"><i class="bi bi-newspaper"></i></div>
        <h3 class="news-empty-title">No news articles found</h3>
        <p class="news-empty-text" v-if="filterProgram">No articles for the selected program.</p>
        <p class="news-empty-text" v-else>News articles will appear here once published.</p>
      </div>

      <template v-else>
        <article class="news-featured" @click="openDetailModal(filteredNews[0])">
          <div class="news-featured-badge">Latest</div>
          <h3 class="news-featured-title">{{ filteredNews[0].title }}</h3>
          <div class="news-featured-meta">
            <span class="news-featured-date"><i class="bi bi-calendar3 me-1"></i>{{ formatDate(filteredNews[0].published_at) }}</span>
            <span class="news-featured-progs">
              <span
                class="news-prog-badge"
                :class="'news-prog--' + p"
                v-for="p in (filteredNews[0].program_ids || [filteredNews[0].program_id || 'trms'])"
                :key="p"
              >{{ programLabel(p) }}</span>
            </span>
          </div>
          <p class="news-featured-excerpt">{{ excerpt(filteredNews[0].content, 300) }}</p>
          <span class="news-featured-cta">Read full article <i class="bi bi-arrow-right ms-1"></i></span>
        </article>

        <div class="news-grid">
          <article
            v-for="article in filteredNews.slice(1)"
            :key="article.id"
            class="news-card"
            @click="openDetailModal(article)"
          >
            <div class="news-card-progs">
              <span
                class="news-prog-badge"
                :class="'news-prog--' + p"
                v-for="p in (article.program_ids || [article.program_id || 'trms'])"
                :key="p"
              >{{ programLabel(p) }}</span>
            </div>
            <h4 class="news-card-title">{{ article.title }}</h4>
            <p class="news-card-excerpt">{{ excerpt(article.content, 150) }}</p>
            <div class="news-card-footer">
              <span class="news-card-date"><i class="bi bi-calendar3 me-1"></i>{{ formatDate(article.published_at) }}</span>
              <span class="news-card-read">Read <i class="bi bi-arrow-right ms-1"></i></span>
            </div>
          </article>
        </div>
      </template>
    </div>

    <Teleport to="body">
      <div class="modal fade" id="newsDetailModal" tabindex="-1" ref="detailModalEl">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="news-modal-content">
            <div class="news-modal-header">
              <h5 class="news-modal-title">{{ detailArticle ? detailArticle.title : '' }}</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="news-modal-body" v-if="detailArticle">
              <div class="news-modal-meta">
                <span
                  class="news-prog-badge"
                  :class="'news-prog--' + p"
                  v-for="p in (detailArticle.program_ids || [detailArticle.program_id || 'trms'])"
                  :key="p"
                >{{ programLabel(p) }}</span>
                <span class="news-modal-date"><i class="bi bi-calendar3 me-1"></i>{{ formatDate(detailArticle.published_at) }}</span>
              </div>
              <div class="news-modal-content-text">{{ detailArticle.content }}</div>
            </div>
            <div class="news-modal-footer">
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

export default {
  name: 'News',
  computed: {
    ...mapState(useTrmsStore, ['news']),
    sortedNews() {
      return [...this.news].sort((a, b) => {
        const dateA = a.published_at || ''
        const dateB = b.published_at || ''
        return dateB.localeCompare(dateA)
      })
    },
    filteredNews() {
      if (!this.filterProgram) return this.sortedNews
      return this.sortedNews.filter(a => {
        const ids = a.program_ids || [a.program_id || 'trms']
        return ids.includes(this.filterProgram)
      })
    }
  },
  data() {
    return {
      loading: false,
      filterProgram: '',
      detailArticle: null,
      detailModalInstance: null,
      filterOptions: [
        { id: '', label: 'All' },
        { id: 'trms', label: 'TRMS' },
        { id: 'bms', label: 'BMS' },
        { id: 'jco', label: 'JCO' },
        { id: 'trcc', label: 'TRCC' }
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
    ...mapActions(useTrmsStore, ['fetchNews']),

    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },

    excerpt(text, max = 200) {
      if (!text) return ''
      return text.length > max ? text.substring(0, max) + '...' : text
    },

    programLabel(programId) {
      const map = { trms: 'TRMS', bms: 'BMS', jco: 'JCO', trcc: 'TRCC' }
      return map[programId] || (programId || '').toUpperCase()
    },

    openDetailModal(article) {
      this.detailArticle = article
      this.showDetailModal()
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
    if (this.detailModalInstance) this.detailModalInstance.dispose()
  }
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   HEADER
   ═══════════════════════════════════════════════════════════════ */
.news-header {
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

.news-header::before {
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

.news-header-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.25rem;
}

.news-header-text {
  flex: 1;
  min-width: 0;
}

.news-breadcrumb {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--gold-color, #c8a45d);
  margin-bottom: 0.25rem;
}

.news-title {
  font-size: clamp(1.4rem, 3.5vw, 2.2rem);
  font-weight: 800;
  color: #fffdf8;
  margin-bottom: 0.35rem;
  line-height: 1.2;
}

.news-desc {
  font-size: 0.9rem;
  color: rgba(234, 220, 194, 0.55);
  margin-bottom: 0;
  max-width: 540px;
}

@media (max-width: 767.98px) {
  .news-header {
    padding: 1.15rem 1.15rem;
  }

  .news-title {
    font-size: 1.4rem;
  }

  .news-desc {
    font-size: 0.82rem;
    max-width: none;
  }
}

/* ═══════════════════════════════════════════════════════════════
   TOOLBAR
   ═══════════════════════════════════════════════════════════════ */
.news-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.85rem;
  margin-bottom: 1.5rem;
}

.news-toolbar-info {
  display: flex;
  align-items: baseline;
  gap: 0.65rem;
}

.news-toolbar-heading {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #fffdf8;
}

.news-toolbar-count {
  margin: 0;
  font-size: 0.82rem;
  color: rgba(234, 220, 194, 0.45);
}

.news-filter-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.news-pill {
  padding: 0.35rem 0.85rem;
  border: 1px solid rgba(234, 220, 194, 0.1);
  border-radius: 20px;
  background: rgba(234, 220, 194, 0.04);
  color: rgba(234, 220, 194, 0.55);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.news-pill:hover {
  background: rgba(200, 164, 93, 0.1);
  border-color: rgba(200, 164, 93, 0.25);
  color: rgba(234, 220, 194, 0.8);
}

.news-pill--active {
  background: rgba(200, 164, 93, 0.15);
  border-color: var(--gold-color, #c8a45d);
  color: var(--gold-color, #c8a45d);
}

/* ═══════════════════════════════════════════════════════════════
   EMPTY STATE
   ═══════════════════════════════════════════════════════════════ */
.news-empty {
  padding: 3.5rem 1.5rem;
  text-align: center;
}

.news-empty-icon {
  font-size: 3rem;
  color: rgba(234, 220, 194, 0.12);
  margin-bottom: 0.85rem;
}

.news-empty-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: rgba(234, 220, 194, 0.6);
  margin-bottom: 0.35rem;
}

.news-empty-text {
  color: rgba(234, 220, 194, 0.35);
  font-size: 0.85rem;
  margin-bottom: 0;
}

/* ═══════════════════════════════════════════════════════════════
   FEATURED ARTICLE
   ═══════════════════════════════════════════════════════════════ */
.news-featured {
  position: relative;
  padding: 1.5rem 1.75rem;
  margin-bottom: 1.25rem;
  border: 1px solid rgba(200, 164, 93, 0.15);
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(200, 164, 93, 0.08), transparent 50%),
    rgba(26, 31, 48, 0.6);
  cursor: pointer;
  transition: all 0.25s ease;
}

.news-featured:hover {
  border-color: rgba(200, 164, 93, 0.35);
  background: linear-gradient(135deg, rgba(200, 164, 93, 0.12), transparent 50%),
    rgba(26, 31, 48, 0.7);
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(10, 10, 18, 0.3);
}

.news-featured-badge {
  display: inline-block;
  padding: 0.2rem 0.7rem;
  border-radius: 20px;
  background: var(--gold-color, #c8a45d);
  color: #111420;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.7rem;
}

.news-featured-title {
  font-size: clamp(1.15rem, 2.5vw, 1.5rem);
  font-weight: 700;
  color: #fffdf8;
  margin-bottom: 0.5rem;
  line-height: 1.3;
}

.news-featured-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.75rem;
}

.news-featured-date {
  font-size: 0.8rem;
  color: rgba(234, 220, 194, 0.45);
}

.news-featured-progs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
}

.news-featured-excerpt {
  font-size: 0.9rem;
  color: rgba(234, 220, 194, 0.6);
  line-height: 1.6;
  margin-bottom: 0.85rem;
}

.news-featured-cta {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--gold-color, #c8a45d);
  transition: gap 0.2s ease;
  display: inline-flex;
  align-items: center;
}

.news-featured:hover .news-featured-cta {
  gap: 0.15rem;
}

/* ═══════════════════════════════════════════════════════════════
   NEWS GRID
   ═══════════════════════════════════════════════════════════════ */
.news-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

/* ── Card ──────────────────────────────────────────────────── */
.news-card {
  padding: 1.25rem 1.35rem;
  border: 1px solid rgba(234, 220, 194, 0.08);
  border-radius: 10px;
  background: rgba(26, 31, 48, 0.4);
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
}

.news-card:hover {
  border-color: rgba(200, 164, 93, 0.2);
  background: rgba(26, 31, 48, 0.7);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(10, 10, 18, 0.25);
}

.news-card-progs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-bottom: 0.5rem;
}

.news-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #fffdf8;
  margin-bottom: 0.4rem;
  line-height: 1.35;
}

.news-card-excerpt {
  font-size: 0.82rem;
  color: rgba(234, 220, 194, 0.5);
  line-height: 1.55;
  margin-bottom: 0.75rem;
  flex: 1;
}

.news-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding-top: 0.65rem;
  border-top: 1px solid rgba(234, 220, 194, 0.06);
}

.news-card-date {
  font-size: 0.72rem;
  color: rgba(234, 220, 194, 0.35);
}

.news-card-read {
  font-size: 0.72rem;
  font-weight: 600;
  color: rgba(200, 164, 93, 0.6);
  transition: color 0.2s ease;
}

.news-card:hover .news-card-read {
  color: var(--gold-color, #c8a45d);
}

/* ═══════════════════════════════════════════════════════════════
   PROGRAM BADGES
   ═══════════════════════════════════════════════════════════════ */
.news-prog-badge {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-radius: 20px;
  border: 1px solid transparent;
}

.news-prog--trms {
  color: #8bb9fe;
  background: rgba(110, 168, 254, 0.1);
  border-color: rgba(110, 168, 254, 0.16);
}

.news-prog--bms {
  color: #8bcfad;
  background: rgba(117, 183, 152, 0.1);
  border-color: rgba(117, 183, 152, 0.16);
}

.news-prog--jco {
  color: #ffe08a;
  background: rgba(255, 218, 106, 0.1);
  border-color: rgba(255, 218, 106, 0.16);
}

.news-prog--trcc {
  color: #8ae3f5;
  background: rgba(110, 223, 246, 0.1);
  border-color: rgba(110, 223, 246, 0.16);
}

/* ═══════════════════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════════════════ */
@media (max-width: 767.98px) {
  .news-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .news-toolbar-info {
    justify-content: space-between;
  }

  .news-filter-pills {
    justify-content: center;
  }

  .news-grid {
    grid-template-columns: 1fr;
  }

  .news-featured {
    padding: 1.15rem 1.15rem;
  }

  .news-card {
    padding: 1rem 1.15rem;
  }
}

@media (min-width: 768px) and (max-width: 991.98px) {
  .news-grid {
    gap: 0.85rem;
  }
}

/* ═══════════════════════════════════════════════════════════════
   DETAIL MODAL — self-contained, no :deep()
   ═══════════════════════════════════════════════════════════════ */
.news-modal-content {
  border: 1px solid rgba(234, 220, 194, 0.12);
  border-radius: 12px;
  background:
    linear-gradient(135deg, rgba(200, 164, 93, 0.06), transparent 50%),
    linear-gradient(180deg, #1a1f30 0%, #111420 100%);
  box-shadow: 0 20px 48px rgba(8, 8, 14, 0.5);
  color: rgba(234, 220, 194, 0.85);
}

.news-modal-header {
  background: linear-gradient(135deg, rgba(127, 36, 50, 0.18), rgba(200, 164, 93, 0.06));
  border-bottom: 1px solid rgba(234, 220, 194, 0.08);
  border-radius: 11px 11px 0 0;
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.news-modal-title {
  color: var(--gold-color, #c8a45d);
  font-weight: 700;
  margin: 0;
  font-size: 1.1rem;
  line-height: 1.3;
  padding-right: 0.5rem;
}

.news-modal-header .btn-close {
  filter: brightness(0) invert(0.8);
  opacity: 0.6;
  flex-shrink: 0;
}

.news-modal-header .btn-close:hover {
  opacity: 1;
}

.news-modal-body {
  padding: 1.25rem;
}

.news-modal-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 1rem;
}

.news-modal-date {
  font-size: 0.8rem;
  color: rgba(234, 220, 194, 0.45);
}

.news-modal-content-text {
  font-size: 0.92rem;
  line-height: 1.7;
  color: rgba(234, 220, 194, 0.75);
  white-space: pre-wrap;
}

.news-modal-footer {
  border-top: 1px solid rgba(234, 220, 194, 0.08);
  padding: 0.85rem 1.25rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
</style>
