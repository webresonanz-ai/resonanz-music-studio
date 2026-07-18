<template>
  <div class="art-director-page" ref="pageRef">
    <button class="back-button" @click="$emit('close')">
      <i class="bi bi-arrow-left"></i>
      <span>TRMS Home</span>
    </button>

    <section class="hero" ref="heroRef">
      <div class="hero-bg-img" ref="bgImgRef"></div>
      <div class="hero-overlay"></div>
      <div class="hero-particles">
        <span v-for="n in 24" :key="n" class="particle" :style="particleStyle(n)" />
      </div>
      <div class="hero-bg-lines"></div>
      <div class="hero-inner">
        <h1 class="hero-name" ref="nameRef">Avip Priatna</h1>
        <p class="hero-title" ref="titleRef">Art Director</p>
        <div class="hero-separator"><span></span></div>
        <p class="hero-tagline">The Resonanz Music Studio</p>
        <div class="scroll-hint">
          <span>Scroll to discover</span>
          <i class="bi bi-chevron-down"></i>
        </div>
      </div>
    </section>

    <section class="bio" id="bio" ref="bioSection">
      <div class="section-container">
        <div class="bio-grid">
          <div class="bio-image-col" ref="bioImageCol">
            <div class="bio-portrait" ref="bioPortrait">
              <div class="portrait-frame">
                <div class="portrait-placeholder">
                  <img
                    src="/avip-priatna-1.webp"
                    alt="Avip Priatna Portrait"
                    class="portrait-img"
                    width="600" height="800"
                    loading="lazy"
                  />
                </div>
                <div class="portrait-glow"></div>
              </div>
              <div class="portrait-corner top-left"></div>
              <div class="portrait-corner top-right"></div>
              <div class="portrait-corner bottom-left"></div>
              <div class="portrait-corner bottom-right"></div>
            </div>
          </div>
          <div class="bio-text-col" ref="bioTextCol">
            <span class="section-tag">Biography</span>
            <h2 class="section-title">The Vision Behind the Music</h2>
            <div class="bio-text">
              <p ref="bioLine1">
                Avip Priatna, Mag. Art., Indonesia’s foremost conductor and an MDW Vienna alumnus,
                is the music director of Batavia Madrigal Singers (BMS) and The Resonanz Children’s
                Choir (TRCC).
              </p>
              <p ref="bioLine2">
                Under his leadership, these world-class choirs achieved historic victories at the
                prestigious European Grand Prix (EGP) for Choral Singing (TRCC in 2018; BMS in
                2022), setting global benchmarks. Regularly awarded global Best Conductor and Best
                Song Interpreter, his stellar international judging career peaked in 2026 when he
                was appointed President of the Jury at the EGP, becoming the first Asian conductor
                to hold the role.
              </p>
              <p ref="bioLine3">
                He also founded the Jakarta Concert Orchestra (JCO) and The Resonanz Music Studio
                (TRMS) for advanced vocal training. For his cultural impact, he holds Austria’s
                Decoration of Honor in Gold, Cultural Award from the Indonesian Ministry of
                Education and Culture, and an honorary membership in Italy's ANDCI.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="achievements" id="achievements" ref="achievementsSection">
      <div class="section-container">
        <div class="achievements-header">
          <span class="section-tag light">Achievements</span>
          <h2 class="section-title light">Latest Milestones</h2>
          <div class="achievements-subtitle">
            A selection of recent accomplishments and recognition
          </div>
        </div>
        <div class="achievements-grid">
          <div
            v-for="(item, i) in achievementList"
            :key="i"
            class="achievement-card"
            :ref="(el) => setCardRef(el, i)"
            :style="{ transitionDelay: `${i * 0.12}s` }"
          >
            <div class="achievement-number">{{ String(i + 1).padStart(2, "0") }}</div>
            <div class="achievement-year">{{ item.year }}</div>
            <h3 class="achievement-title">{{ item.title }}</h3>
            <p class="achievement-desc">{{ item.desc }}</p>
            <div class="achievement-line"></div>
            <div class="achievement-shine"></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: "ArtDirector",
  emits: ["close"],
  data() {
    return {
      achievementList: [
        {
          year: "2026",
          title: "Grand Prize Winner – 30th Béla Bartók International Choir Competition",
          desc: "As Artistic Director and Conductor of The Resonanz Children's Choir (TRCC), Avip Priatna led the choir to win the Grand Prize, the highest overall honor at the prestigious competition in Debrecen, Hungary.",
        },
        {
          year: "2026",
          title: "Recipient of the Gulyás György Conducting Prize",
          desc: "Avip Priatna received the Gulyás György Conducting Prize (Conductor Prize) for his outstanding interpretation of contemporary choral works and expressive conducting, recognizing his exceptional artistic leadership on the international stage.",
        },
        {
          year: "2025",
          title: "Named One of Tatler Asia's Most Influential Indonesians",
          desc: "Avip Priatna was recognized by Tatler Asia as one of Indonesia's most influential figures, honoring his decades-long contribution to classical music, choral excellence, and Indonesia's cultural presence on the world stage.",
        },
        {
          year: "2025",
          title: "Led Indonesia's Cultural Mission at Expo 2025 Osaka",
          desc: "As conductor of Batavia Madrigal Singers, Avip Priatna led the ensemble as one of Indonesia's official cultural representatives at Expo 2025 Osaka, Kansai, Japan, presenting Indonesian choral artistry to an international audience and strengthening Indonesia's cultural diplomacy.",
        },
      ],
      cardRefs: [],
      observer: null,
      particleStyles: [],
    };
  },
  mounted() {
    this.$nextTick(() => {
      this.initHeroAnimation();
      this.initParallax();
      this.initScrollReveal();
    });
  },
  beforeUnmount() {
    if (this.observer) this.observer.disconnect();
    if (this._mouseHandler) document.removeEventListener("mousemove", this._mouseHandler);
    if (this._scrollHandler) window.removeEventListener("scroll", this._scrollHandler);
  },
  methods: {
    particleStyle(n) {
      const seed = n * 7.3;
      return {
        left: `${(Math.sin(seed * 1.1) * 0.5 + 0.5) * 100}%`,
        top: `${(Math.cos(seed * 0.9) * 0.5 + 0.5) * 100}%`,
        width: `${2 + (n % 3) * 1.5}px`,
        height: `${2 + (n % 3) * 1.5}px`,
        animationDelay: `${(n % 5) * 1.2}s`,
        animationDuration: `${5 + (n % 4) * 2}s`,
      };
    },
    setCardRef(el, i) {
      this.cardRefs[i] = el;
    },
    initHeroAnimation() {
      const nameEl = this.$refs.nameRef;
      const titleEl = this.$refs.titleRef;
      if (nameEl) nameEl.classList.add("revealed");
      setTimeout(() => {
        if (titleEl) titleEl.classList.add("revealed");
      }, 400);
    },
    initParallax() {
      const bg = this.$refs.bgImgRef;
      if (!bg) return;
      this._mouseHandler = (e) => {
        const rect = bg.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
        const y = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
        bg.style.transform = `scale(1.08) translate(${x * 12}px, ${y * 8}px)`;
      };
      document.addEventListener("mousemove", this._mouseHandler);
    },
    initScrollReveal() {
      const elements = [
        { el: this.$refs.bioImageCol, klass: "reveal-left" },
        { el: this.$refs.bioTextCol, klass: "reveal-right" },
        { el: this.$refs.bioLine1, klass: "reveal-line" },
        { el: this.$refs.bioLine2, klass: "reveal-line" },
        { el: this.$refs.bioLine3, klass: "reveal-line" },
      ];
      elements.forEach(({ el, klass }) => {
        if (!el) return;
        el.classList.add(klass);
      });
      this.cardRefs.forEach((el) => {
        if (el) el.classList.add("reveal-card");
      });
      this.observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            el.classList.add("visible");
            if (el.classList.contains("reveal-line")) {
              const idx = ["bioLine1", "bioLine2", "bioLine3"].indexOf(
                Object.keys(this.$refs).find((k) => this.$refs[k] === el),
              );
              el.style.transitionDelay = `${0.3 + idx * 0.15}s`;
            }
            this.observer.unobserve(el);
          });
        },
        { threshold: 0.15, rootMargin: "0px 0px -40px 0px" },
      );
      elements.forEach(({ el }) => {
        if (el) this.observer.observe(el);
      });
      this.cardRefs.forEach((el) => {
        if (el) this.observer.observe(el);
      });
    },
  },
};
</script>

<style scoped>
.art-director-page {
  min-height: 100vh;
  background: #0d0f17;
  font-family: "Cormorant Garamond", "Segoe UI", serif;
  overflow-x: hidden;
}

.back-button {
  position: fixed;
  top: 1.5rem;
  left: 1.5rem;
  z-index: 100;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  border: 1px solid rgba(200, 164, 93, 0.25);
  border-radius: 999px;
  background: rgba(13, 15, 23, 0.6);
  backdrop-filter: blur(12px);
  color: #c8a45d;
  text-decoration: none;
  font-family: "Segoe UI", sans-serif;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.back-button:hover {
  background: rgba(200, 164, 93, 0.12);
  border-color: #c8a45d;
  color: #e8d5a5;
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(200, 164, 93, 0.12);
}

/* ── Hero ─────────────────────────────────────────────── */
.hero {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  overflow: hidden;
  background: #0d0f17;
}
.hero-bg-img {
  position: absolute;
  inset: -20px;
  z-index: 0;
  background: url("/avip-priatna-3.webp") no-repeat center center / cover;
  filter: grayscale(100%) brightness(40%) contrast(1.2);
  transition: transform 0.15s ease-out;
  will-change: transform;
}
.hero-overlay {
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    radial-gradient(
      ellipse at 50% 40%,
      transparent 0%,
      rgba(13, 15, 23, 0.3) 50%,
      rgba(13, 15, 23, 0.7) 100%
    ),
    linear-gradient(
      180deg,
      rgba(13, 15, 23, 0.4) 0%,
      transparent 30%,
      transparent 70%,
      rgba(13, 15, 23, 0.6) 100%
    );
  pointer-events: none;
}
.hero::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: 2;
  background-image:
    linear-gradient(rgba(200, 164, 93, 0.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(200, 164, 93, 0.025) 1px, transparent 1px);
  background-size: 60px 60px;
  mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.25), transparent 65%);
  pointer-events: none;
}
.hero-particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 3;
}
.particle {
  position: absolute;
  border-radius: 50%;
  background: rgba(200, 164, 93, 0.25);
  animation: floatParticle linear infinite;
  will-change: transform;
}
.hero-bg-lines {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}
.hero-bg-lines::before,
.hero-bg-lines::after {
  content: "";
  position: absolute;
  left: 50%;
  width: 1px;
  height: 200%;
  background: linear-gradient(to bottom, transparent, rgba(200, 164, 93, 0.06), transparent);
  animation: lineSway 8s ease-in-out infinite alternate;
}
.hero-bg-lines::before {
  margin-left: -160px;
  animation-delay: -3s;
}
.hero-bg-lines::after {
  margin-left: 160px;
  animation-delay: -1s;
}

@keyframes floatParticle {
  0% {
    transform: translateY(0) scale(1);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-120vh) scale(0.5);
    opacity: 0;
  }
}
@keyframes lineSway {
  0% {
    transform: translateY(-10%) rotate(-2deg);
  }
  100% {
    transform: translateY(10%) rotate(2deg);
  }
}

.hero-inner {
  position: relative;
  z-index: 4;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem;
}

.hero-name {
  margin: 0;
  font-family: "Cormorant Garamond", serif;
  font-size: clamp(3rem, 8vw, 6rem);
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.04em;
  line-height: 1.1;
  text-shadow: 0 2px 40px rgba(200, 164, 93, 0.15);
  opacity: 0;
  transform: translateY(30px);
  transition: all 1s cubic-bezier(0.22, 1, 0.36, 1);
}
.hero-name.revealed {
  opacity: 1;
  transform: translateY(0);
}
.hero-title {
  margin: 0.5rem 0 0;
  font-family: "Cormorant Garamond", serif;
  font-size: clamp(1.1rem, 2.5vw, 1.5rem);
  font-weight: 300;
  color: #c8a45d;
  letter-spacing: 0.45em;
  text-transform: uppercase;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.4s;
}
.hero-title.revealed {
  opacity: 0.85;
  transform: translateY(0);
}
.hero-separator {
  margin: 1.2rem 0 0.6rem;
  overflow: hidden;
}
.hero-separator span {
  display: block;
  width: 60px;
  height: 1px;
  background: linear-gradient(90deg, transparent, #c8a45d, transparent);
  animation: sepExpand 1.2s cubic-bezier(0.22, 1, 0.36, 1) 0.8s both;
}
@keyframes sepExpand {
  from {
    width: 0;
    opacity: 0;
  }
  to {
    width: 60px;
    opacity: 1;
  }
}
.hero-tagline {
  margin: 0;
  font-family: "Segoe UI", sans-serif;
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(200, 164, 93, 0.4);
  animation: fadeInUp 1s ease 1s both;
}

@media (min-width: 992px) and (orientation: landscape) {
  .hero-bg-img {
    background-position-y: -870px;
    background-position-x: -330px;
    background-size: 125%;
  }
}

.scroll-hint {
  margin-top: 2.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  color: rgba(200, 164, 93, 0.4);
  font-family: "Segoe UI", sans-serif;
  font-size: 0.65rem;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  animation: fadeInUp 1s ease 1.8s both;
}
.scroll-hint i {
  font-size: 1rem;
  animation: bounceDown 2.2s ease-in-out infinite;
}
@keyframes bounceDown {
  0%,
  100% {
    transform: translateY(0);
    opacity: 0.6;
  }
  50% {
    transform: translateY(7px);
    opacity: 1;
  }
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ── Biography ─────────────────────────────────────────── */
.bio {
  position: relative;
  padding: 7rem 0;
  background:
    radial-gradient(ellipse at 25% 40%, rgba(200, 164, 93, 0.05), transparent 50%),
    radial-gradient(ellipse at 75% 60%, rgba(127, 36, 50, 0.04), transparent 45%),
    linear-gradient(180deg, #0d0f17 0%, #131826 40%, #171c2d 70%, #0d0f17 100%);
}
.bio::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(200, 164, 93, 0.1), transparent);
}
.section-container {
  width: min(100%, 1100px);
  margin: 0 auto;
  padding: 0 2rem;
}
.bio-grid {
  display: grid;
  grid-template-columns: 1fr 1.3fr;
  gap: 4.5rem;
  align-items: center;
}
.bio-image-col {
  display: flex;
  justify-content: center;
}
.bio-portrait {
  position: relative;
  width: 100%;
  max-width: 360px;
  aspect-ratio: 3 / 4;
}
.portrait-frame {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}
.portrait-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(160deg, rgba(200, 164, 93, 0.06), rgba(127, 36, 50, 0.1));
  border: 1px solid rgba(200, 164, 93, 0.12);
  color: rgba(200, 164, 93, 0.2);
  font-size: 4rem;
  position: relative;
  z-index: 1;
  transition: all 0.6s ease;
}
.portrait-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
}
.portrait-frame:hover .portrait-placeholder {
  border-color: rgba(200, 164, 93, 0.3);
  background: linear-gradient(160deg, rgba(200, 164, 93, 0.1), rgba(127, 36, 50, 0.15));
}
.portrait-glow {
  position: absolute;
  inset: -20px;
  z-index: 0;
  background: radial-gradient(circle at 50% 50%, rgba(200, 164, 93, 0.06), transparent 60%);
  pointer-events: none;
}
.portrait-corner {
  position: absolute;
  width: 20px;
  height: 20px;
  border-color: rgba(200, 164, 93, 0.2);
  border-style: solid;
  transition: all 0.5s ease;
  z-index: 2;
}
.bio-portrait:hover .portrait-corner {
  border-color: rgba(200, 164, 93, 0.5);
}
.portrait-corner.top-left {
  top: -4px;
  left: -4px;
  border-width: 1px 0 0 1px;
}
.portrait-corner.top-right {
  top: -4px;
  right: -4px;
  border-width: 1px 1px 0 0;
}
.portrait-corner.bottom-left {
  bottom: -4px;
  left: -4px;
  border-width: 0 0 1px 1px;
}
.portrait-corner.bottom-right {
  bottom: -4px;
  right: -4px;
  border-width: 0 1px 1px 0;
}

.bio-text-col {
  color: rgba(255, 253, 248, 0.85);
}
.section-tag {
  display: inline-block;
  margin-bottom: 0.75rem;
  font-family: "Segoe UI", sans-serif;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: #c8a45d;
}
.section-tag.light {
  color: #c8a45d;
}
.section-title {
  margin: 0 0 1.75rem;
  font-family: "Cormorant Garamond", serif;
  font-size: clamp(2rem, 3.5vw, 2.8rem);
  font-weight: 600;
  color: #fff;
  letter-spacing: 0.02em;
  line-height: 1.2;
}
.section-title.light {
  color: #fff;
}
.bio-text p {
  margin: 0 0 1.2rem;
  font-family: "Cormorant Garamond", serif;
  font-size: 1.15rem;
  line-height: 1.75;
  color: rgba(255, 253, 248, 0.68);
}
.bio-text p:last-child {
  margin-bottom: 0;
}

/* ── Achievements ──────────────────────────────────────── */
.achievements {
  position: relative;
  padding: 7rem 0 8rem;
  background:
    radial-gradient(ellipse at 70% 20%, rgba(127, 36, 50, 0.06), transparent 50%),
    radial-gradient(ellipse at 30% 80%, rgba(200, 164, 93, 0.04), transparent 50%),
    linear-gradient(180deg, #131826 0%, #0d0f17 50%, #101420 100%);
}
.achievements::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(200, 164, 93, 0.08), transparent);
}
.achievements-header {
  text-align: center;
  margin-bottom: 4rem;
}
.achievements-subtitle {
  margin-top: 0.75rem;
  font-family: "Cormorant Garamond", serif;
  font-size: 1.1rem;
  color: rgba(255, 253, 248, 0.4);
}
.achievements-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}
.achievement-card {
  position: relative;
  padding: 2.5rem 2rem 2.5rem 3rem;
  border: 1px solid rgba(200, 164, 93, 0.08);
  border-radius: 4px;
  background: rgba(13, 15, 23, 0.35);
  backdrop-filter: blur(4px);
  transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
  overflow: hidden;
  cursor: default;
}
.achievement-card::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, rgba(200, 164, 93, 0.03), transparent 60%);
  opacity: 0;
  transition: opacity 0.5s ease;
}
.achievement-card:hover {
  border-color: rgba(200, 164, 93, 0.25);
  background: rgba(13, 15, 23, 0.55);
  transform: translateY(-6px);
  box-shadow:
    0 20px 50px rgba(0, 0, 0, 0.3),
    0 0 40px rgba(200, 164, 93, 0.04);
}
.achievement-card:hover::before {
  opacity: 1;
}
.achievement-card:hover .achievement-line {
  width: 3px;
  height: 60px;
  background: #c8a45d;
  box-shadow: 0 0 18px rgba(200, 164, 93, 0.4);
}
.achievement-card:hover .achievement-number {
  color: rgba(200, 164, 93, 0.15);
}
.achievement-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 60%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(200, 164, 93, 0.04), transparent);
  transform: skewX(-25deg);
  transition: left 0.6s ease;
}
.achievement-card:hover .achievement-shine {
  left: 150%;
}
.achievement-number {
  position: absolute;
  right: 1.5rem;
  top: 1.25rem;
  font-family: "Cormorant Garamond", serif;
  font-size: 3.5rem;
  font-weight: 700;
  color: rgba(200, 164, 93, 0.06);
  line-height: 1;
  transition: color 0.5s ease;
  pointer-events: none;
}
.achievement-year {
  font-family: "Segoe UI", sans-serif;
  font-size: 0.75rem;
  font-weight: 700;
  color: #c8a45d;
  letter-spacing: 0.15em;
  margin-bottom: 0.6rem;
}
.achievement-title {
  margin: 0 0 0.7rem;
  font-family: "Cormorant Garamond", serif;
  font-size: 1.3rem;
  font-weight: 600;
  color: #fff;
  line-height: 1.35;
  position: relative;
  z-index: 1;
}
.achievement-desc {
  margin: 0;
  font-family: "Cormorant Garamond", serif;
  font-size: 1rem;
  line-height: 1.65;
  color: rgba(255, 253, 248, 0.5);
  position: relative;
  z-index: 1;
}
.achievement-line {
  position: absolute;
  left: 0;
  top: 2.5rem;
  width: 3px;
  height: 35px;
  background: rgba(200, 164, 93, 0.2);
  border-radius: 999px;
  transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* ── Scroll Reveal ──────────────────────────────────────── */
.reveal-left,
.reveal-right,
.reveal-line,
.reveal-card {
  opacity: 0;
  transition: all 0.9s cubic-bezier(0.22, 1, 0.36, 1);
}
.reveal-left {
  transform: translateX(-40px);
}
.reveal-right {
  transform: translateX(40px);
}
.reveal-line {
  transform: translateY(20px);
  transition-duration: 0.7s;
}
.reveal-card {
  transform: translateY(30px);
  transition-duration: 0.8s;
}
.reveal-left.visible,
.reveal-right.visible {
  opacity: 1;
  transform: translateX(0);
}
.reveal-line.visible {
  opacity: 1;
  transform: translateY(0);
}
.reveal-card.visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── Responsive ────────────────────────────────────────── */
@media (max-width: 991.98px) {
  .bio-grid {
    grid-template-columns: 1fr;
    gap: 3rem;
  }
  .bio-image-col {
    order: -1;
  }
  .bio-portrait {
    max-width: 280px;
  }
  .achievements-grid {
    grid-template-columns: 1fr;
  }
  .achievement-card {
    padding: 2rem 1.5rem 2rem 2.5rem;
  }
}
@media (max-width: 575.98px) {
  .hero-inner {
    padding: 1.5rem;
  }
  .back-button span {
    display: none;
  }
  .back-button {
    padding: 0.5rem 0.8rem;
    top: 1rem;
    left: 1rem;
  }
  .bio,
  .achievements {
    padding: 4rem 0;
  }
  .section-container {
    padding: 0 1.25rem;
  }
  .achievement-card {
    padding: 1.5rem 1.25rem 1.5rem 1.75rem;
  }
  .achievement-number {
    font-size: 2.5rem;
    right: 1rem;
    top: 1rem;
  }
}
</style>
