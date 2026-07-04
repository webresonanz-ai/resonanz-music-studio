import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('src', import.meta.url)),
        },
    },
    optimizeDeps: {
        include: ['bootstrap'],
    },
    build: {
        // Warn only when a chunk exceeds 600 kB (Bootstrap CSS pushes the shared
        // vendor chunk close to that even after code-splitting).
        chunkSizeWarningLimit: 600,
    },
})
