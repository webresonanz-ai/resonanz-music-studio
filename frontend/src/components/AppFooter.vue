<template>
    <footer class="app-footer mt-5 pt-4">
        <div class="row gy-4">
            <div class="col-md-4">
                <h5 class="footer-heading">
                    <span class="heading-accent"></span>
                    The Resonanz Music Studio
                </h5>
                <p class="footer-text">Empowering lives through music education and performance excellence.</p>
            </div>
            <div class="col-md-4">
                <h5 class="footer-heading">
                    <span class="heading-accent"></span>
                    Quick Links
                </h5>
                <ul class="list-unstyled footer-links">
                    <li v-for="item in quickLinks" :key="item.label">
                        <a :href="item.link" class="footer-link">
                            <i :class="'bi ' + item.icon + ' link-icon'"></i>{{ item.label }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="footer-heading">
                    <span class="heading-accent"></span>
                    Connect With Us
                </h5>
                <div class="social-links">
                    <a href="https://www.facebook.com/TheResonanzMusicStudio/" class="social-link"
                        aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/theresonanz/" class="social-link" aria-label="Instagram"><i
                            class="bi bi-instagram"></i></a>
                    <a href="https://www.youtube.com/channel/UCdb2I2qatGylIK_9oZpRQqg" class="social-link"
                        aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="https://twitter.com/theresonanz" class="social-link" aria-label="Twitter"><i
                            class="bi bi-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copy">&copy; 2026 The Resonanz Music Studio. All rights reserved.</p>
        </div>
    </footer>
</template>

<script>
export default {
    name: 'AppFooter',
    setup() {
        const quickLinks = [
            { label: 'About Us', link: '#', icon: 'bi-info-circle' },
            { label: 'Contact', link: '#', icon: 'bi-envelope' },
            { label: 'Privacy Policy', link: '#', icon: 'bi-shield-check' },
            { label: 'Terms of Service', link: '#', icon: 'bi-file-text' }
        ]

        return {
            quickLinks
        }
    }
}
</script>

<style scoped>
.app-footer {
    position: relative;
    z-index: 1;
    padding: 2.5rem 1.5rem 1.5rem;
    border: 1px solid rgba(234, 220, 194, 0.1);
    border-radius: 12px 12px 0 0;
    background:
        linear-gradient(135deg, rgba(200, 164, 93, 0.1), transparent 46%),
        linear-gradient(180deg, #1a1f30 0%, #111420 100%);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.04) inset,
        0 -4px 32px rgba(10, 10, 18, 0.2);
    backdrop-filter: blur(8px);
}

.app-footer::before {
    content: '';
    position: absolute;
    inset: 0 0 auto 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(200, 164, 93, 0.3), transparent);
}

/* ── Headings ─────────────────────────────────────────────── */
.footer-heading {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--gold-color, #c8a45d);
    letter-spacing: 0.02em;
    margin-bottom: 1rem;
}

.heading-accent {
    display: block;
    width: 3px;
    height: 16px;
    border-radius: 999px;
    background: var(--gold-color, #c8a45d);
    box-shadow: 0 0 8px rgba(200, 164, 93, 0.35);
    flex-shrink: 0;
}

/* ── Body text ────────────────────────────────────────────── */
.footer-text {
    color: rgba(234, 220, 194, 0.72);
    font-size: 0.875rem;
    line-height: 1.65;
    margin-bottom: 0;
}

/* ── Quick links ──────────────────────────────────────────── */
.footer-links {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.footer-link {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.35rem 0;
    color: rgba(234, 220, 194, 0.72);
    font-size: 0.875rem;
    text-decoration: none;
    transition:
        color 0.2s ease,
        transform 0.2s ease;
    min-height: 32px;
}

.footer-link:hover {
    color: var(--gold-color, #c8a45d) !important;
    transform: translateX(4px);
}

.link-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: rgba(234, 220, 194, 0.07);
    border: 1px solid rgba(234, 220, 194, 0.08);
    font-size: 0.75rem;
    color: var(--gold-color, #c8a45d);
    flex-shrink: 0;
    transition:
        background 0.2s,
        border-color 0.2s;
}

.footer-link:hover .link-icon {
    background: rgba(200, 164, 93, 0.15);
    border-color: rgba(200, 164, 93, 0.3);
}

/* ── Social links ─────────────────────────────────────────── */
.social-links {
    display: flex;
    gap: 0.5rem;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid rgba(234, 220, 194, 0.12);
    background: rgba(234, 220, 194, 0.06);
    color: rgba(234, 220, 194, 0.7);
    font-size: 1.15rem;
    text-decoration: none;
    transition:
        background 0.2s ease,
        color 0.2s ease,
        border-color 0.2s ease,
        transform 0.2s ease;
}

.social-link:hover {
    color: var(--gold-color, #c8a45d) !important;
    background: rgba(200, 164, 93, 0.12);
    border-color: rgba(200, 164, 93, 0.3);
    transform: translateY(-2px);
}

/* ── Bottom bar ───────────────────────────────────────────── */
.footer-bottom {
    margin-top: 2rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(234, 220, 194, 0.08);
    text-align: center;
}

.footer-copy {
    color: rgba(234, 220, 194, 0.55);
    font-size: 0.8rem;
    margin-bottom: 0;
}
</style>
