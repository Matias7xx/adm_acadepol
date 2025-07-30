import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { globSync } from 'glob';

// Todas as páginas para garantir que estejam no manifest
const pageFiles = globSync('resources/js/Pages/**/*.vue');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js', 
                'resources/css/app.css',
                ...pageFiles // FORÇAR inclusão de todas as páginas
            ],
            refresh: true,
            publicDirectory: 'public',
            buildDirectory: 'build',
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
    build: {
        chunkSizeWarningLimit: 2000, // Aumentar limite para aceitar bundle maior
        rollupOptions: {
            output: {
                manualChunks: {
                    // separar vendor (bibliotecas externas)
                    vendor: ['vue', '@inertiajs/vue3', 'axios']
                }
            }
        }
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});