import { defineConfig } from "vite"
import laravel from "laravel-vite-plugin"
import vue from "@vitejs/plugin-vue"

export default defineConfig({
    server: {
        // Inside Sail, changes made on macOS don't reach the container's
        // file watcher, so Vite would keep serving stale modules.
        watch: { usePolling: Boolean(process.env.LARAVEL_SAIL) },
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
