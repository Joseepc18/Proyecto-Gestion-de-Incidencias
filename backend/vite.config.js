import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Alias al árbol de assets del frontend (única fuente de verdad de style.css y los
// diálogos vanilla), que el panel Blade reutiliza a través de @vite.
const shared = fileURLToPath(new URL('../frontend/assets', import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/panel.css', 'resources/js/panel.js'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@shared': shared,
        },
    },
    server: {
        fs: {
            // Permite servir los assets compartidos (fuera de la raíz del backend) en modo dev.
            allow: ['..', shared],
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
