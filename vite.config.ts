import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import inertia from '@inertiajs/vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        // Inertia v3 Vite plugin — automatic page resolution + SSR wiring.
        // SSR stays off until a `resources/js/ssr.ts` entry is added.
        inertia({ ssr: false }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
