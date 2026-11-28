import { defineConfig } from "vite"
import laravel from "laravel-vite-plugin"
import vue from "@vitejs/plugin-vue"

export default defineConfig({
    server: {
        // Inside Sail, changes made on macOS don't reach the container's
        // file watcher, so Vite would keep serving stale modules.
        // Polling every file is expensive: skip the big folders that never
        // hold frontend code.
        watch: {
            usePolling: Boolean(process.env.LARAVEL_SAIL),
            interval: 300,
            ignored: [
                "**/vendor/**",
                "**/storage/**",
                "**/bootstrap/cache/**",
                "**/public/build/**",
                "**/.git/**",
                "**/local/**",
            ],
        },
    },
    plugins: [
        laravel({
            input: "resources/js/app.ts",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
})
