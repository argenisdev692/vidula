import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        tailwindcss(),
        react({
            // React 19 Compiler for automatic optimizations
            babel: {
                plugins: [
                    ['babel-plugin-react-compiler', {
                        target: '19'
                    }]
                ],
            },
        }),
    ],
    server: {
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
