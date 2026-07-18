<template>
  <Transition name="overlay">
    <div v-if="visible" class="loading-overlay">
      <div class="loading-overlay__content">
        <div class="loading-overlay__ornament loading-overlay__ornament--top">
          <span class="ornament-line"></span>
          <span class="ornament-diamond">&#9670;</span>
          <span class="ornament-line"></span>
        </div>

        <div class="loading-overlay__logo">
          <img src="/logo_resonanz.webp" alt="The Resonanz Music Studio" width="85" height="85" />
        </div>

        <h1 class="loading-overlay__title">
          The Resonanz
          <span class="loading-overlay__sub">Music Studio</span>
        </h1>

        <p class="loading-overlay__tagline">Where Music Finds Its Voice</p>

        <div class="loading-overlay__ornament loading-overlay__ornament--bottom">
          <span class="ornament-line"></span>
          <span class="ornament-diamond">&#9670;</span>
          <span class="ornament-line"></span>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
export default {
  name: 'LoadingScreen',
  props: {
    visible: {
      type: Boolean,
      default: true
    }
  }
}
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 99998;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(180deg, #0a0a12 0%, #0f111b 40%, #141a26 100%);
  overflow: hidden;
}

.loading-overlay__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.65rem;
  position: relative;
  z-index: 1;
}

.loading-overlay__ornament {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.ornament-line {
  display: block;
  width: 100px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(200, 164, 93, 0.5), transparent);
}

.ornament-diamond {
  color: var(--gold-color, #c8a45d);
  font-size: 0.55rem;
  opacity: 0.5;
}

.loading-overlay__logo img {
  width: 85px;
  height: auto;
  display: block;
  filter: drop-shadow(0 0 20px rgba(200, 164, 93, 0.15));
}

.loading-overlay__title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-weight: 700;
  font-size: 2.4rem;
  color: var(--gold-color, #c8a45d);
  text-align: center;
  line-height: 1.15;
  letter-spacing: 0.06em;
  margin: 0;
}

.loading-overlay__sub {
  display: block;
  font-weight: 400;
  font-size: 1.35rem;
  letter-spacing: 0.35em;
  color: rgba(234, 220, 194, 0.8);
  margin-top: 0.05rem;
  text-transform: uppercase;
}

.loading-overlay__tagline {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-weight: 300;
  font-style: italic;
  font-size: 1.05rem;
  color: rgba(234, 220, 194, 0.45);
  margin: 0.15rem 0 0 0;
  letter-spacing: 0.04em;
}

.overlay-leave-active {
  transition: opacity 0.65s ease;
  pointer-events: none;
}

.overlay-leave-to {
  opacity: 0;
}

@media (max-width: 576px) {
  .loading-overlay__title {
    font-size: 1.8rem;
  }

  .loading-overlay__sub {
    font-size: 1.1rem;
  }

  .loading-overlay__logo img {
    width: 65px;
  }

  .loading-overlay__tagline {
    font-size: 0.95rem;
  }

  .ornament-line {
    width: 60px;
  }
}
</style>
