import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import viteCompression from 'vite-plugin-compression'

export default defineConfig({
    plugins: [
        vue(),
        viteCompression({
            algorithm: 'brotliCompress',
            ext: '.br',
            threshold: 1024,
        }),
        viteCompression({
            algorithm: 'gzip',
            ext: '.gz',
            threshold: 1024,
        }),
        // Non-blocking CSS — swap rel=stylesheet for rel=preload + onload
        {
            name: 'async-css',
            enforce: 'post',
            apply: 'build',
            transformIndexHtml: {
                order: 'post',
                handler(html) {
                    return html.replace(
                        /<link rel="stylesheet"([^>]*)>/g,
                        (_match, attrs) => {
                            if (attrs.includes('media=')) return _match;
                            return `<link rel="preload"${attrs} as="style" onload="this.onload=null;this.rel='stylesheet'"><noscript>${_match}</noscript>`;
                        },
                    );
                },
            },
        },
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('src', import.meta.url)),
        },
    },
    optimizeDeps: {
        include: ['bootstrap'],
    },
    build: {
        target: 'es2020',
        cssCodeSplit: true,
        chunkSizeWarningLimit: 300,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/vue')) {
                        return 'vendor-vue'
                    }
                    if (id.includes('node_modules/pinia')) {
                        return 'vendor-pinia'
                    }
                    if (id.includes('node_modules/vue-router')) {
                        return 'vendor-router'
                    }
                    if (id.includes('node_modules/bootstrap')) {
                        return 'vendor-bootstrap'
                    }
                    if (id.includes('node_modules/html2canvas')) {
                        return 'vendor-html2canvas'
                    }
                    if (id.includes('node_modules/jsqr')) {
                        return 'vendor-jsqr'
                    }
                    if (id.includes('node_modules/bootstrap-icons')) {
                        return 'vendor-bootstrap-icons'
                    }
                },
            },
        },
    },
})
