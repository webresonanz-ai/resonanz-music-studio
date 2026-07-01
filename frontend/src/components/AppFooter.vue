<template>
    <footer class="app-footer mt-5 pt-4">
        <div class="row gy-4">
            <div class="col-md-4">
                <h5 class="text-dark">The Resonanz Music Studio</h5>
                <p class="text-muted">Empowering lives through music education and performance excellence.</p>
            </div>
            <div class="col-md-4">
                <h5 class="text-dark">Quick Links</h5>
                <ul class="list-unstyled">
                    <li v-for="item in quickLinks" :key="item.label">
                        <a :href="item.link" class="footer-link text-muted text-decoration-none">
                            <i :class="'bi ' + item.icon + ' me-2'"></i>{{ item.label }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="text-dark">Connect With Us</h5>
                <div class="social-links">
                    <a href="https://www.facebook.com/TheResonanzMusicStudio/" class="text-muted me-3"
                        aria-label="Facebook"><i class="bi bi-facebook fs-4"></i></a>
                    <a href="https://www.instagram.com/theresonanz/" class="text-muted me-3" aria-label="Instagram"><i
                            class="bi bi-instagram fs-4"></i></a>
                    <a href="https://www.youtube.com/channel/UCdb2I2qatGylIK_9oZpRQqg" class="text-muted me-3"
                        aria-label="YouTube"><i class="bi bi-youtube fs-4"></i></a>
                    <a href="https://twitter.com/theresonanz" class="text-muted" aria-label="Twitter"><i
                            class="bi bi-twitter fs-4"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center mt-4 pt-3 border-top">
            <p class="text-muted mb-0">&copy; 2026 The Resonanz Music Studio. All rights reserved.</p>
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
