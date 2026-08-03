import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // storefront.js é um entry point separado do app.js (Inertia) —
            // vitrine e painel são bundles independentes, cada superfície
            // carrega só o JS que realmente usa (Fase 4).
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/storefront.js'],
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
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});