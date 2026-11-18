import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/contact.js',
                'resources/js/insights.js',
                'resources/js/testimonials.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                // Alpine and the focus plugin are 128 KB of the bundle and
                // change only when the dependency versions do. Without this
                // they sit in the same file as app code, so every edit to our
                // own scripts changes the hash of a 130 KB chunk and every
                // visitor downloads all of it again on the next deploy.
                //
                // Splitting them means the vendor chunk keeps its hash across
                // deploys that do not touch package.json, and the per-page
                // entries only reach the pages that load them.
                manualChunks(id) {
                    if (id.includes('node_modules/alpinejs') || id.includes('node_modules/@alpinejs')) {
                        return 'alpine';
                    }
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});