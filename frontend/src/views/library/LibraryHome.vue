<template>
  <div class="fade-in-up">
    <div class="content-card mb-4">
      <p class="eyebrow mb-1">Library</p>
      <h1 class="page-title mb-0">Welcome to the Music Library</h1>
    </div>

    <div class="row g-4">
      <div class="col-12 col-md-6">
        <router-link to="/library/sheet-music" class="text-decoration-none">
          <div class="content-card library-card h-100">
            <div class="library-icon-wrap">
              <i class="bi bi-music-note"></i>
            </div>
            <h4 class="mt-3 mb-1">Sheet Music</h4>
            <p class="text-muted mb-0">
              Browse and search scores by genre, composer, arranger, and title.
            </p>
          </div>
        </router-link>
      </div>
      <div class="col-12 col-md-6">
        <router-link to="/library/costumes" class="text-decoration-none">
          <div class="content-card library-card h-100">
            <div class="library-icon-wrap">
              <i class="bi bi-person-badge"></i>
            </div>
            <h4 class="mt-3 mb-1">Costumes</h4>
            <p class="text-muted mb-0">
              Browse the costume collection with previews and condition info.
            </p>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LibraryHome',
}
</script>

<style scoped>
.eyebrow {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--gold-color, #c8a45d);
}
.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink-color, #191b24);
  letter-spacing: -0.01em;
}
.library-card {
  cursor: pointer;
  text-align: center;
  padding: 3rem 2rem;
}
.library-icon-wrap {
  display: inline-grid;
  place-items: center;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(200, 164, 93, 0.12);
  border: 2px solid rgba(200, 164, 93, 0.2);
}
.library-icon-wrap i {
  font-size: 2rem;
  color: var(--gold-color, #c8a45d);
}
</style>
