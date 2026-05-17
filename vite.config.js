import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/js/app.jsx',
                'resources/js/admin/notifications.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Vendor: React core — ładowany jako pierwszy
                    if (id.includes('node_modules/react/') || id.includes('node_modules/react-dom/')) {
                        return 'vendor-react';
                    }
                    // Vendor: Inertia
                    if (id.includes('node_modules/@inertiajs/')) {
                        return 'vendor-inertia';
                    }
                    // Ciężkie marketing-only komponenty (poniżej foldu) w osobnym chunk
                    if (
                        id.includes('Components/Marketing/CostCalculatorV2') ||
                        id.includes('Components/Marketing/SaasLandingSection')
                    ) {
                        return 'marketing-heavy';
                    }
                },
            },
        },
        // Ostrzeżenie gdy chunk > 500 kB
        chunkSizeWarningLimit: 500,
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/js/tests/setup.js'],
        alias: {
            '@': '/resources/js',
        },
    },
});
