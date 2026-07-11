<template>
  <Transition name="overlay">
    <div v-if="visible" class="loading-overlay">
      <div class="loading-overlay__particles">
        <span
          v-for="(p, i) in particleConfigs"
          :key="i"
          class="loading-particle"
          :style="{
            left: p.left + '%',
            animationDelay: p.delay + 's',
            animationDuration: p.duration + 's',
            width: p.size + 'px',
            height: p.size + 'px',
            opacity: p.opacity
          }"
        />
      </div>

      <div class="loading-overlay__content">
        <div class="loading-overlay__ornament loading-overlay__ornament--top">
          <span class="ornament-line"></span>
          <span class="ornament-diamond">&#9670;</span>
          <span class="ornament-line"></span>
        </div>

        <div class="loading-overlay__logo">
          <img src="/logo_resonanz.png" alt="The Resonanz Music Studio" />
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

        <div class="loading-overlay__indicator">
          <div class="loading-overlay__bar"></div>
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
  },
  data() {
    return {
      particleConfigs: [
        { left: 5, delay: 0.3, duration: 6.0, size: 2, opacity: 0.4 },
        { left: 15, delay: 1.8, duration: 5.0, size: 3, opacity: 0.3 },
        { left: 25, delay: 0.7, duration: 7.0, size: 1.5, opacity: 0.5 },
        { left: 38, delay: 2.5, duration: 4.5, size: 2.5, opacity: 0.35 },
        { left: 50, delay: 1.2, duration: 5.5, size: 2, opacity: 0.45 },
        { left: 62, delay: 3.0, duration: 6.5, size: 3, opacity: 0.3 },
        { left: 75, delay: 0.5, duration: 5.0, size: 1.5, opacity: 0.4 },
        { left: 85, delay: 2.0, duration: 4.0, size: 2, opacity: 0.5 },
        { left: 92, delay: 1.5, duration: 5.8, size: 2.5, opacity: 0.35 }
      ]
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
  background:
    radial-gradient(ellipse at 50% 35%, rgba(200, 164, 93, 0.07) 0%, transparent 55%),
    radial-gradient(ellipse at 50% 65%, rgba(127, 36, 50, 0.04) 0%, transparent 45%),
    linear-gradient(180deg, #0a0a12 0%, #0f111b 40%, #141a26 100%);
  overflow: hidden;
}

/* ── Particles ─────────────────────────────── */
.loading-overlay__particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.loading-particle {
  position: absolute;
  bottom: -10px;
  border-radius: 50%;
  background: rgba(200, 164, 93, 0.3);
  animation: lo-float-up linear infinite;
}

@keyframes lo-float-up {
  0% {
    transform: translateY(0) scale(1);
    opacity: 0;
  }
  8% {
    opacity: 1;
  }
  85% {
    opacity: 0.6;
  }
  100% {
    transform: translateY(-100vh) scale(0.4);
    opacity: 0;
  }
}

/* ── Content ───────────────────────────────── */
.loading-overlay__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.65rem;
  position: relative;
  z-index: 1;
}

/* ── Ornament lines ────────────────────────── */
.loading-overlay__ornament {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.loading-overlay__ornament--top {
  animation: lo-fade-down 0.7s ease 0.15s both;
}

.loading-overlay__ornament--bottom {
  animation: lo-fade-up 0.7s ease 1.1s both;
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

/* ── Logo ──────────────────────────────────── */
.loading-overlay__logo {
  animation: lo-fade-in 0.9s ease 0.35s both;
}

.loading-overlay__logo img {
  width: 85px;
  height: auto;
  display: block;
  filter: drop-shadow(0 0 20px rgba(200, 164, 93, 0.15));
  animation: lo-float 4s ease-in-out infinite;
}

@keyframes lo-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

/* ── Title ─────────────────────────────────── */
.loading-overlay__title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-weight: 700;
  font-size: 2.4rem;
  color: var(--gold-color, #c8a45d);
  text-align: center;
  line-height: 1.15;
  letter-spacing: 0.06em;
  margin: 0;
  animation: lo-fade-up 0.8s ease 0.55s both;
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

/* ── Tagline ───────────────────────────────── */
.loading-overlay__tagline {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-weight: 300;
  font-style: italic;
  font-size: 1.05rem;
  color: rgba(234, 220, 194, 0.45);
  margin: 0.15rem 0 0 0;
  letter-spacing: 0.04em;
  animation: lo-fade-up 0.8s ease 0.85s both;
}

/* ── Loading bar ───────────────────────────── */
.loading-overlay__indicator {
  width: 180px;
  height: 2px;
  background: rgba(200, 164, 93, 0.1);
  border-radius: 999px;
  overflow: hidden;
  margin-top: 0.4rem;
  box-shadow: 0 0 12px rgba(200, 164, 93, 0.04);
  animation: lo-fade-up 0.6s ease 1.35s both;
}

.loading-overlay__bar {
  width: 100%;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(200, 164, 93, 0.03) 20%,
    var(--gold-color, #c8a45d) 50%,
    rgba(200, 164, 93, 0.03) 80%,
    transparent 100%
  );
  background-size: 200% 100%;
  animation: lo-shimmer 1.6s ease-in-out infinite;
}

@keyframes lo-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ── Element entrance keyframes ────────────── */
@keyframes lo-fade-in {
  from { opacity: 0; transform: scale(0.92); }
  to { opacity: 1; transform: scale(1); }
}

@keyframes lo-fade-up {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes lo-fade-down {
  from { opacity: 0; transform: translateY(-12px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ── Overlay exit transition ───────────────── */
.overlay-leave-active {
  transition:
    opacity 0.65s ease,
    backdrop-filter 0.65s ease;
  pointer-events: none;
}

.overlay-leave-to {
  opacity: 0;
  backdrop-filter: blur(0px);
}

/* ── Responsive ────────────────────────────── */
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

  .loading-overlay__indicator {
    width: 140px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .loading-particle,
  .loading-overlay__logo img,
  .loading-overlay__bar {
    animation: none !important;
  }

  .loading-overlay__ornament--top,
  .loading-overlay__ornament--bottom,
  .loading-overlay__logo,
  .loading-overlay__title,
  .loading-overlay__tagline,
  .loading-overlay__indicator {
    animation: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
}
</style>
